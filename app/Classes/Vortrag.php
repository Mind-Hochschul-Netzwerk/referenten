<?php
namespace MHN\Referenten;
/**
* Repräsentiert ein Vortrag
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

use DateTime;

class Vortrag {
    private $data = null;
    private $delete = false;
    private $sendAuthMailOnSave = false; // E-Mail-Adresse soll geändert werden -> Mail zur Verifkation verschicken
    private $tellOld = true; // alte E-Mail-Adresse informieren, wenn die E-Mail gewechselt wird

    // Felder und Defaults
    const felder = [
        'vid' => null,
        'eid' => null,
        'vTitel' => '',
        'kurztitel' => '',
        'beitragsform' => '',
        'beitragssprache' => '',
        'beschrTeilnehmeranzahl' => '',
        'maxTeilnehmeranzahl' => '',
        'praefZeit' => '',
        'anmerkungen' => '',
        'abstract' => '',
        'equipment_beamer' => false,
        'equipment_computer' => false,
        'equipment_wlan' => false,
        'equipment_lautsprecher' => false,
        'equipment_mikrofon' => false,
        'equipment_flipchart' => false,
        'equipment_sonstiges' => false,
        'equipment_sonstiges_beschreibung' => '',
        'anlagezeitpunkt' => null,
        'db_modified' => null,
        'deleted' => false,
        'locked' => false,
        'publish' => false,
        'programm_raum' => '',
        'programm_beginn' => null,
        'programm_ende' => null,
        'datenschutz_bereinigt' => false,
        'datenschutz_bereinigung_termin' => null,
    ];

    /**
    * Lädt ein Vortrag aus der Datenbank und gibt ein Vortrag-Objekt zurück (oder null)
    */
    public static function lade($vid, $auchGeloeschte = false) {
        $v = new self($vid, $auchGeloeschte);

        if (!$v->data) {
            return null;
        }

        return $v;
    }

    /**
     * Lädt die Vortrags-ID von dem aktuellsten Vortrag zu einem Benutzer anhand seiner Benutzer-ID
     *
     * @return $vid|null - ID des Vortrags | null im Fehlerfall
     */
    public static function ladeAktuellsteVIDzuUID($uid, $auchGeloeschte = false)
    {

        if(empty($uid)) {
            return null;
        }

        $vid = DB::query('SELECT v.vid AS vid FROM vortraege v JOIN benutzerZuVortraege bzv ON v.vid = bzv.vid WHERE bzv.uid = %d ' . ($auchGeloeschte ? '' : 'AND deleted=false ') . 'ORDER BY anlagezeitpunkt DESC LIMIT 1', $uid)->get();

        if (empty($vid)) {
            return null;
        }

        return $vid;
    }

    /**
    * Erzeugt ein Vortrag-Objekt für ein neuen Vortrag
    *
    * @param int $uid User-ID des Referenten
    */
    public static function neu(int $uid) {
        $v = new self(0, false, true);
        $v->data = self::felder;

        // Als default beim Anlegen eines neuen Beitrags wird die nächste Mind-Akademie als Event zugeordnet, da dies am wahrscheinlichsten ist.
        $ma = Event::getNextEvent();

        if (!$ma) {
            die('Das nächste anstehende Event konnte nicht ermittelt werden!');
        }

        $v->set('eid', $ma['eid']);
        $v->set('anlagezeitpunkt', 'now');
        $v->save();

        // Vortrag::addUser() muss nach dem Speichern aufgerufen werden.
        // Da die Methode die Vortrags-ID benötigt, die bei einem neuen Eintrag erst nach dem Speichern von der DB vergeben wird.
        $v->addUser($uid);

        return $v;
    }

    /**
    * privater Konstruktor, um das direkte Erstellen von Objekten zu verhindern
    * Benutze die Funktion Vortrag::lade($vid)
    */
    private function __construct($vid, $auchGeloeschte, $neu = false) {
        if ($neu) {
            return;
        }

        $this->data = DB::query('SELECT '. implode(',', array_keys(self::felder)) . ' FROM vortraege WHERE vid=%d' . ($auchGeloeschte ? '' : ' AND deleted=false'), $vid)->get_row();

        if (!$this->data) {
            return;
        }

        // typsicheres Setzen der Daten
        foreach ($this->data as $key => $value) {
            $this->setData($key, $value, false);
        }

        // löschen von Daten bei denen kein berechtiges Interesse zur Speicherung mehr besteht
        if (!$this->data['datenschutz_bereinigt'] && $this->data['datenschutz_bereinigung_termin'] !== null && $this->data['datenschutz_bereinigung_termin'] < new \DateTime()) {
            $this->prepareDatenschutzBereinigung();
            $this->save();
        }
    }

    /**
    * Liest eine Eigenschaft
    */
    public function get($feld) {
        switch ($feld) {
        case 'vid':
            if (!$this->data['vid']) die("ID des neuen Benutzers existiert noch nicht. Erst save() aufrufen.");
            return $this->data['vid'];
            break;
        case 'uid':
            // ToDo: [0], um nur den ersten Referenten zurckzugeben ist ein schlechtes Provisorium. Muss ich noch anpassen!
            return $this->getAllUserIds()[0];
            break;
        case 'uids':
            return $this->getAllUserIds();
            break;
        default:
            if (isset($this->data[$feld])) {
                return $this->data[$feld];
            } else if (in_array($feld, array_keys(self::felder))) {
                // Eigenschaft existiert, aber ist null
                return null;
            } else {
                die("Unbekannte Eigenschaft: " . $feld);
            }
        }
    }

    /**
     * typsicheres Setzen der Daten.
     *
     * @param bool $strictTypes Datentypen überprüfen. Bei false wird konvertiert.
     * @throws \TypeError, wenn $checkType === true ist und der Datentype nicht stimmt
     * @throws \OutOfRangeException wenn die Eigenschaft unbekannt ist
     */
    private function setData(string $key, $value, $strictTypes = true)
    {
        if (!in_array($key, array_keys(self::felder), true)) {
            throw new \OutOfRangeException("Property unknown: $key", 1493682897);
        }

        $defaultType = gettype(self::felder[$key]);
        switch ($key) {
            case 'vid':
            case 'eid':
                $defaultType = 'integer';
        }

        $type = gettype($value);

        if ($type === 'NULL') {
            if ($defaultType !== 'NULL' && !in_array($key, ['delete_user_id', 'db_modified_user_id'])) {
                throw new \TypeError('Value for ' . $key . ' may not be null.', 1494774389);
            } else {
                $this->data[$key] = null;
            }
            return;
        }

        if ($defaultType !== 'NULL' && $strictTypes && $defaultType !== $type) {
            throw new \TypeError("Value for $key is expected to be $defaultType, $type given.", 1494774567);
        }

        switch ($key) {
            case 'anlagezeitpunkt':
            case 'db_modified':
            case 'programm_beginn':
            case 'programm_ende':
            case 'datenschutz_bereinigung_termin':
                $this->data[$key] = $this->makeDateTime($value);
                return;
            default:
                if ($defaultType === 'integer') {
                    $this->data[$key] = (int)$value;
                } elseif ($defaultType === 'string') {
                    $this->data[$key] = (string)$value;
                } elseif ($defaultType === 'boolean') {
                    $this->data[$key] = (bool)$value;
                } elseif ($defaultType === 'float') {
                    $this->data[$key] = (float)$value;
                } else {
                    throw new \TypeError("Invalid data type for $key: $type.", 1494775686);
                }
                return;
        }
    }

    /**
    * Ändert eine Eigenschaft
    */
    public function set($feld, $wert) {
        switch ($feld) {
            // Daten zum Löschen vormerken
            case 'deleted':
                $this->data[$feld] = $wert;
                if ($wert) { // keine Probe auf den aktuellen Wert, da sonst ggf. bei Reload nach dem Löschen die bereits gelöschten Daten aus dem Formular wieder gespeichert werden.
                    $this->delete = true;
                }
                break;
            case 'eid':
                $this->setData($feld, (int)$wert);
                $this->updateLoeschfristByEventId();
                break;
            case 'datenschutz_bereinigung_termin':
                if ($this->makeDateTime($wert) > new \DateTime('now')) {
                    $this->setData('datenschutz_bereinigt', false);
                }
                $this->setData('datenschutz_bereinigung_termin', $wert);
                break;
            default:
                $this->setData($feld, $wert);
                break;
        }
        return true;
    }

    /**
     * Erstellt ein DateTime-Objekt (oder null)
     *
     * @var null|string|int|DateTime $dateTime string (für strtotime), int (Timestamp) oder DateTime
     * @throw \TypeError wenn $dateTime einen nicht unterstützten Datentyp hat
     * @return DateTime|null
     */
    private function makeDateTime($dateTime)
    {
        $type = gettype($dateTime);
        if ($type === 'NULL') {
            return null;
        } elseif ($type === 'integer') {
            if ($dateTime === 0) {
                return null;
            }
            return new \DateTime('@' . $dateTime);
        } elseif ($type === 'string') {
            if ($dateTime === '') {
                return null;
            }
            return new \DateTime($dateTime);
        } elseif ($type === 'object' && get_class($dateTime) === 'DateTime') {
            return $dateTime;
        } else {
            throw new \TypeError("Value is expected to be DateTime, null, string or integer. $type given.", 1494775564);
        }
    }

    /**
     * Setzt die Löschfrist auf das Datum des Events, falls dies weiter in der Zukunft liegt als die bisherige Löschfrist
     *
     * @return void
     */
    private function updateLoeschfristByEventId()
    {
        $termin = DB::query('SELECT loeschdatum_daten FROM events WHERE eid = %d', $this->get('eid'))->get();
        if ($termin && (!$this->get('datenschutz_bereinigung_termin') || $this->makeDateTime($termin) > $this->get('datenschutz_bereinigung_termin'))) {
            $this->set('datenschutz_bereinigung_termin', $termin);
        }
    }

    /**
     * Bereitet das Löschen der Daten vor.
     * Danach muss noch save() aufgerufen werden.
     *
     * @return void
     */
    private function prepareDelete()
    {
        $this->setData('deleted', true);

        // Datensatz leeren
        foreach (self::felder as $feld => $default) {
            switch ($feld) {
                // nicht zu leerende Felder:
                case 'vid':
                case 'uid':
                case 'vTitel':
                case 'kurztitel':
                case 'abstract':
                case 'eid':
                case 'beitragsform':
                case 'beitragssprache':
                case 'deleted':
                case 'datenschutz_bereinigt':
                case 'datenschutz_bereinigung_termin':
                case 'locked':
                case 'publish':
                case 'programm_raum':
                case 'programm_beginn':
                case 'programm_ende':
                    break;
                case 'db_modified':
                    $this->setData('db_modified', 'now');
                    break;
                // übrige Felder mit Standardwert überschreiben
                default:
                    $this->setData($feld, $default);
                    break;
            }
        }
    }

    /**
     * Datenschutz-Bereinigung (anschließend muss noch save() aufgerufen werden!)
     *
     * @return void
     */
    private function prepareDatenschutzBereinigung()
    {
        $deleted = $this->get('deleted');
        $this->prepareDelete();
        $this->setData('deleted', $deleted);
        $this->setData('datenschutz_bereinigt', true);
    }

    /**
     * Speichert den Benutzer in der Datenbank
     *
     * @return void
     */
    public function save()
    {

        // Query bauen
        $key_value_pairs = [];
        foreach (array_keys(self::felder) as $feld) {
            if ($feld === 'vid') {
                continue;
            }

            $value = $this->data[$feld];

            if ($value === false) {
                $key_value_pairs[] = "$feld = 0";
            } elseif ($value === true) {
                $key_value_pairs[] = "$feld = 1";
            } elseif ($value === null) {
                $key_value_pairs[] = "$feld = NULL";
            } elseif (gettype($value) === 'integer' || gettype($value) === 'double') {
                $key_value_pairs[] = "$feld = $value";
            } elseif (gettype($value) === 'string') {
                $key_value_pairs[] = sprintf("$feld = '%s'", DB::_($this->data[$feld]));
            } elseif (gettype($value) === 'object' && get_class($value) === 'DateTime') {
                $key_value_pairs[] = sprintf("$feld = '%s'", $value->format('Y-m-d H:i:s'));
            }
        }

        // neuen Benutzer anlegen
        if ($this->data['vid'] === null) {
            DB::actualQuery("INSERT INTO vortraege SET \n" . implode(",\n    ", $key_value_pairs));
            $this->setData('vid', DB::insert_id());
        } else {
            DB::actualQuery("UPDATE vortraege SET \n" . implode(",\n    ", $key_value_pairs) . "\nWHERE vid=" . ((int)$this->get('vid')));
        }
    }

    /**
     * Gibt alle IDs von Benutzern zurück die einem Vortrag zugeordnet sind
     *
     * @return array mit der "uid"
     */
    public function getAllUserIds() {
        return DB::query('SELECT uid FROM benutzerZuVortraege WHERE vid=%d ', $this->data['vid'])->get_column();
    }

    /**
     * Fügt ein Benutzer zu einem Vortrag hinzu
     *
     * @param $uid des Benutzers
     */
    public function addUser($uid) {
        if (!$this->data['vid']) {
            die('Der Beitrag muss zuerst in der Datenbank angelegt werden, bevor ein Benutzer zugeordnet werden kann');
        }

        if($b = Benutzer::lade($uid)) {
            DB::query('INSERT INTO benutzerZuVortraege SET uid=%d, vid=%d', $uid, $this->data['vid']);
            $b->updateLoeschfristByEventId($this->get('eid'));
            $b->save();
        } else {
            die('Der Benutzer konnte dem Beitrag nicht zugeordnet werden, da kein Benutzer mit der ID (ID: ' . $uid . ') gefunden wurde.');
        }
    }

    /**
     * Entferne die Zuordnung von einem Benutzer zum Vortrag
     *
     * @param $uid des Benutzers
     */
    public function deleteUser($uid) {
        DB::query('DELETE FROM benutzerZuVortraege WHERE uid=%d AND vid=%d ', $uid, $this->data['vid']);
    }

}

?>
