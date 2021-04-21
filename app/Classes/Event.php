<?php

namespace MHN\Referenten;

/**
 * Repräsentiert ein Event
 *
 * @author Guido Drechsel <mhn@guido-drechsel.de>
 */

use DateTime;

class Event
{
    const felder = [
        'eid' => null, 'kennzeichen' => '', 'bezeichnung' => '', 'datum_letzter_tag' => null, 'loeschdatum_daten' => null];

    // Felder und Defaults
    private $data = null;

    /**
     * privater Konstruktor, um das direkte Erstellen von Objekten zu verhindern
     * Benutze die Funktion Event::lade($eid)
     * @param int $eid
     * @param bool $neu
     */
    private function __construct($eid, $neu = false)
    {
        if ($neu) {
            return;
        }
        $data = DB::query('SELECT ' . implode(',', array_keys(self::felder)) . ' FROM events WHERE eid=%d ', $eid)->get_row();
        if (!$data) {
            return;
        }

        // typsicheres Setzen der Daten
        foreach ($data as $key => $value) {
            $this->setData($key, $value, false);
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
            case 'eid':
                $defaultType = 'integer';
        }

        $type = gettype($value);

        if ($type === 'NULL') {
            if ($defaultType !== 'NULL') {
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
            case 'datum_letzter_tag':
            case 'loeschdatum_daten':
                $this->data[$key] = $this->makeDateTime($key, $value);
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
     * Ändert eine Eigenschaft, sofern sie nicht schreibgeschützt ist
     *
     * @param string $feld
     * @param mixed $wert
     * @return gibt true zurück wenn die Ausführung der Methode erfolgreich war
     * @throws \LogicException wenn versucht wird, eine schreibgeschützte Eigenschaft zu ändern
     * @throws \OutOfRangeException wenn die Eigenschaft unbekannt ist
     */
    public function set(string $feld, $wert)
    {
        switch ($feld) {
            case 'eid':
                throw new \LogicException("Eigenschaft $feld ist schreibgeschützt", 1493682836);
            default:
                $this->setData($feld, $wert);
                break;
        }
        return true;
    }

    /**
     * Erstellt ein DateTime-Objekt (oder null)
     *
     * @var string $fieldName Name des Feldes, das geändert wird
     * @var null|string|int|DateTime $dateTime string (für strtotime), int (Timestamp) oder DateTime
     * @throw \TypeError wenn $dateTime einen nicht unterstützten Datentyp hat
     * @return DateTime|null
     */
    private function makeDateTime($fieldName, $dateTime)
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
            throw new \TypeError("Value for $fieldName is expected to be DateTime, null, string or integer. $type given.", 1494775564);
        }
    }

    /**
     * Lädt ein Event aus der Datenbank und gibt ein Event-Objekt zurück (oder null)
     * @param int $eid
     */
    public static function lade($eid)
    {
        $e = new self($eid);

        if (!$e->data) {
            return null;
        }

        return $e;
    }

    /**
     * Speichert das Event in der Datenbank
     *
     * @return void
     */
    public function save()
    {

        // Query bauen
        $key_value_pairs = [];
        foreach (array_keys(self::felder) as $feld) {
            if ($feld === 'eid') {
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

        // neues Event anlegen
        if ($this->data['eid'] === null) {
            DB::actualQuery("INSERT INTO events SET \n" . implode(",\n    ", $key_value_pairs));
            $this->setData('eid', DB::insert_id());
        } else {
            DB::actualQuery("UPDATE events SET \n" . implode(",\n    ", $key_value_pairs) . "\nWHERE eid=" . ((int)$this->get('eid')));
        }
    }

    /**
     * Liest eine Eigenschaft
     *
     * @param string $feld
     * @throws \LogicException wenn die ID erfragt wird, obwohl sie noch nicht existiert.
     * @throws \OutOfRangeException wenn die Eigenschaft unbekannt ist
     */
    public function get($feld)
    {
        switch ($feld) {
            case 'eid':
                return (int)$this->data['eid'];
            case 'beitragsjahr':
                return (int)$this->get('datum_letzter_tag')->format('Y');
            default:
                if (in_array($feld, array_keys(self::felder), true)) {
                    return $this->data[$feld];
                } else {
                    throw new \OutOfRangeException('Unbekannte Eigenschaft: ' . $feld, 1493682787);
                }
        }
    }

    /**
     * Prüft, ob ein Event in dr Vergangenheit liegt
     *
     * @return boolean - true, wenn das "datum_letzter_tag" kleiner dem aktuellen Zeitpunkt ist
     */
    public function isInPast()
    {
        return ($this->get('datum_letzter_tag') < new \DateTime());
    }

    /**
     * Abfrage aller Events
     *
     * @return array Liste der Events, jeweils mit den Keys "eid" und "bezeichnung"
     */
    public static function getListOfAllEvents()
    {
        return DB::query('SELECT eid, bezeichnung, datum_letzter_tag FROM events ORDER BY datum_letzter_tag DESC')->get_all();
    }

    /**
     * Abfrage des nächsten zukünftigen Events
     *
     * @return array mit der "eid", "bezeichnung" und dem "datum_letzter_tag" von der nächsten Mind-Akademie
     */
    public static function getNextEvent()
    {
        return DB::query('SELECT eid, bezeichnung, datum_letzter_tag FROM events WHERE datum_letzter_tag > NOW() ORDER BY datum_letzter_tag LIMIT 1')->get_row();
    }
}