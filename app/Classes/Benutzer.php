<?php
namespace MHN\Referenten;
/**
 * Repräsentiert ein Benutzer
 *
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 */
use DateTime;
use MHN\Referenten\Service\EmailService;

class Benutzer {
    private $data = null;
    private $delete = false;
    private $sendAuthMailOnSave = false; // E-Mail-Adresse soll geändert werden -> Mail zur Verifkation verschicken
    private $tellOld = true; // alte E-Mail-Adresse informieren, wenn die E-Mail gewechselt wird

    // Felder und Defaults
    const felder = [
        'uid' => null,
        'email' => '',
        'password' => 'dummy',
        'titel' => '',
        'vorname' => '',
        'nachname' => '',
        'geschlecht' => 'u',
        'registrierungsdatum' => null,
        'profilbild' => '',
        'profilbild_x' => null,
        'profilbild_y' => null,
        'mensa_nr' => '',
        'mhn_mitglied' => false,
        'telefon' => '',
        'mobil'=>'',
        'kurzvita'=>'',
        'affiliation' => '',
        'new_email'=>'',
        'new_email_token'=>'',
        'new_email_token_expire_time'=>null,
        'db_modified'=>null,
        'last_login'=>null,
        'new_password'=>'',
        'new_password_expire_time'=>null,
        'deleted'=>false,
        'locked' => false,
        'publish' => false,
        'aufnahmen' => false,
        'kenntnisnahme_informationspflicht_persbez_daten' => null,
        'kenntnisnahme_informationspflicht_persbez_daten_text' => '',
        'einwilligung_persbez_zusatzdaten' => null,
        'einwilligung_persbez_zusatzdaten_text' => '',
        'datenschutz_bereinigt' => false,
        'datenschutz_bereinigung_termin' => null,
    ];

    /**
     * Lädt ein Benutzer aus der Datenbank und gibt ein Benutzer-Objekt zurück (oder null)
     */
    public static function lade($uid, $auchGeloeschte = false) {
        $m = new self($uid, $auchGeloeschte);

        if (!$m->data) {
            return null;
        }

        return $m;
    }

    /**
     * Erzeugt ein Benutzer-Objekt für ein neues Benutzer
     */
    public static function neu($new_password) {
        $r = new self(0, false, true);
        $r->data = self::felder;

        // neues Passwort generieren und zum Ändern vormerken
        $r->setData('password', $new_password); // in password speichern statt new_password wegen der LDAP-Anbindung und Single-Sign-On
        $r->setData('new_password', 'new password'); // das bedeutet beim ersten Login ändern
        $r->setData('new_password_expire_time', '+100 years');

        $r->setData('registrierungsdatum', 'now');
        $r->setData('db_modified', 'now');

        return $r;
    }

    /**
     * privater Konstruktor, um das direkte Erstellen von Objekten zu verhindern
     * Benutze die Funktion Benutzer::lade($uid)
     */
    private function __construct($uid, $auchGeloeschte, $neu = false) {
        if ($neu) {
            return;
        }

        $this->data = DB::query('SELECT '. implode(',', array_keys(self::felder)) . ' FROM benutzer WHERE uid=%d ' . ($auchGeloeschte ? '' : 'AND deleted=false'), $uid)->get_row();

        if (!$this->data) {
            return;
        }

        // typsicheres Setzen der Daten
        foreach ($this->data as $key => $value) {
            $this->setData($key, $value, false);
        }

        // verstrichene Fristen
        if ($this->data['new_email_token_expire_time'] !== null && $this->data['new_email_token_expire_time'] < new DateTime()) {
            $this->setData('new_email', '');
            $this->setData('new_email_token_expire_time', null);
            $this->save();
        }
        if ($this->data['new_password_expire_time'] !== null && $this->data['new_password_expire_time'] < new DateTime()) {
            $this->setData('new_password', '');
            $this->setData('new_password_expire_time', null);
            $this->save();
        }

        // löschen von Benutzerdaten bei denen kein berechtiges Interesse zur Speicherung mehr besteht
        if (!Auth::hatRecht('ma-pt', $uid) && !$this->data['datenschutz_bereinigt'] && $this->data['datenschutz_bereinigung_termin'] !== null && $this->data['datenschutz_bereinigung_termin'] < new DateTime()) {
            $this->prepareDatenschutzBereinigung();
            $this->save();
        }
    }

    /**
     * Liest eine Eigenschaft
     */
    public function get($feld) {
        switch ($feld) {
            case 'id':
            case 'uid':
                return (int)$this->data['uid'];
                break;
            case 'fullName':
                $titel = $this->data['titel'];
                $vorname = $this->data['vorname'];
                $nachname = $this->data['nachname'];
                $fn = $titel;
                if ($fn) $fn .= ' ';
                $fn .= $vorname;
                if ($fn) $fn .= ' ';
                $fn .= $nachname;
                if (!$fn) {
                    $fn = $this->data['email'];
                }
                if (!$fn) {
                    $fn = 'ID ' . $this->data['uid'];
                }
                return $fn;
            default:
                if (isset($this->data[$feld])) return $this->data[$feld];
                else if (in_array($feld, array_keys(self::felder))) return null;     // Eigenschaft existiert, aber ist null
                else die("Unbekannte Eigenschaft: " . $feld);
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
            case 'id':
            case 'uid':
                $defaultType = 'integer';
                break;
            case 'profilbild_x':
            case 'profilbild_y':
                $defaultType = 'integer';
                if (gettype($value) === 'NULL') {
                    $value = 0;
                }
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
            case 'registrierungsdatum':
            case 'new_email_token_expire_time':
            case 'db_modified':
            case 'last_login':
            case 'new_password_expire_time':
            case 'kenntnisnahme_informationspflicht_persbez_daten':
            case 'einwilligung_persbez_zusatzdaten':
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
     * Ändert eine Eigenschaft, sofern sie nicht schreibgeschützt ist
     *
     * @param string $feld
     * @param mixed $wert
     * @return mixed
     * @throws \LogicException wenn versucht wird, eine schreibgeschützte Eigenschaft zu ändern
     * @throws \OutOfRangeException wenn die Eigenschaft unbekannt ist
     */
    public function set(string $feld, $wert)
    {
        switch ($feld) {
            case 'id':
            case 'uid':
            case 'username':
                throw new \LogicException("Eigenschaft $feld ist schreibgeschützt", 1493682836);
            case 'email':
                throw new \LogicException("Verwende setEmail(), um den Wert zu ändern.", 1494002758);
                break;
            case 'new_email_token':
            case 'new_email_token_expire_time':
                throw new \LogicException("Eigenschaft $feld ist privat. Verwende initEmailAuth(), um den Wert zu ändern.", 1493682856);
            // Neue E-Mail-Adresse setzen und Verfikationsprozess einleiten
            case 'new_email':
                $this->setData('new_email', $wert);
                $this->initEmailAuth(true); // Verifizierungsprozess einleiten und alte Mail informieren
                break;
            // Einmal-Passwort festlegen (oder löschen) und Gültigkeitsdauer setzen
            case 'new_password':
                if ($wert !== '') {
                    $this->setData('new_password', Password::hash($wert));
                    $this->setData('new_password_expire_time', Config::newPasswordExpireTime);
                } else {
                    $this->setData('new_password', '');
                    $this->setData('new_password_expire_time', null);
                }
                break;
            // neues Passwort festlegen. Ein Einmal-Passwort verliert dadurch seine Gültigkeit.
            case 'password':
                $this->setData('password', Password::hash($wert));
                $this->set('new_password', '');
                break;
            // Daten zum Löschen vormerken
            case 'deleted':
                $this->data[$feld] = $wert;
                if ($wert) { // keine Probe auf den aktuellen Wert, da sonst ggf. bei Reload nach dem Löschen die bereits gelöschten Daten aus dem Formular wieder gespeichert werden.
                    $this->delete = true;
                }
                break;
            case 'kenntnisnahme_informationspflicht_persbez_daten':
            case 'einwilligung_persbez_zusatzdaten':
                $this->data[$feld] = $this->makeDateTime($wert);
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
     * Setzt die E-Mail-Adresse.
     *
     * @param string $email
     * @throws \RuntimeException falls schon ein anderes Benutzer diese Adresse verwendet.
     * @return void
     */
    public function setEmail($email)
    {
        $id = self::getIdByEmail($email);
        if ($id !== null && (int)$id !== (int)$this->get('uid')) {
            throw new \RuntimeException('Doppelte Verwendung der E-Mail-Adresse ' . $email, 1494003025);
        }

        $this->setData('email', $email, 0);
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
     * Gibt eine User-ID zu einer E-Mail-Adresse zurück.
     * Durchsucht *nur* die aktuellen Adressen, nicht die noch zu setzenden.
     *
     * @param string $email
     * @return int|null User-ID falls gefunden
     */
    public static function getIdByEmail($email)
    {
        if (!$email) {
            return null;
        }
        return DB::query('SELECT uid FROM benutzer WHERE email="%s"', $email)->get();
    }

    /**
     * Bereitet das Löschen der Daten vor.
     *
     * Rechte-Zuordnungen werden nicht gelöscht.
     *
     * Danach muss noch save() aufgerufen werden.
     *
     * @return void
     */
    private function prepareDelete()
    {
        $this->setData('deleted', true);
        $this->setData('password', 'geloescht');
        $this->setData('new_password', 'geloescht');

        // Profilbild-Datei löschen
        if ($this->get('profilbild') && is_file('profilbilder/' . $this->get('profilbild'))) {
            unlink('profilbilder/' . $this->get('profilbild'));
            unlink('profilbilder/thumbnail-' . $this->get('profilbild'));
        }

        // Datensatz leeren
        foreach (self::felder as $feld => $default) {
            switch ($feld) {
                // nicht zu leerende Felder
                case 'id':
                case 'uid':
                case 'titel':
                case 'vorname':
                case 'nachname':
                case 'kurzvita':
                case 'deleted':
                case 'datenschutz_bereinigt':
                case 'datenschutz_bereinigung_termin':
                case 'locked':
                case 'publish':
                case 'aufnahmen':
                case 'kenntnisnahme_informationspflicht_persbez_daten':
                case 'kenntnisnahme_informationspflicht_persbez_daten_text':
                case 'einwilligung_persbez_zusatzdaten':
                case 'einwilligung_persbez_zusatzdaten_text':
                case 'password':
                case 'new_password':
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
     * Setzt die Löschfrist auf das Datum des Events, falls dies weiter in der Zukunft liegt als die bisherige Löschfrist
     *
     * @return void
     */
    public function updateLoeschfristByEventId($eid)
    {
        $termin = DB::query('SELECT loeschdatum_daten FROM events WHERE eid = %d', $eid)->get();
        if ($termin && (!$this->get('datenschutz_bereinigung_termin') || $this->makeDateTime($termin) > $this->get('datenschutz_bereinigung_termin'))) {
            $this->set('datenschutz_bereinigung_termin', $termin);
        }
    }

    /**
     * Speichert den Benutzer in der Datenbank
     *
     * @return void
     */
    public function save()
    {
        if (!$this->data['deleted'] && $this->sendAuthMailOnSave) {
            // falls eine E-Mail geändert werden soll: Bestätigungsmail schicken
            $this->sendEmailAuthMail();
        }

        // Query bauen
        $key_value_pairs = [];
        foreach (array_keys(self::felder) as $feld) {
            if ($feld === 'id') {
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
        if ($this->data['uid'] === null) {
            DB::actualQuery("INSERT INTO benutzer SET \n" . implode(",\n    ", $key_value_pairs));
            $this->setData('uid', DB::insert_id());
        } else {
            DB::actualQuery("UPDATE benutzer SET \n" . implode(",\n    ", $key_value_pairs) . "\nWHERE uid=" . ((int)$this->get('uid')));
        }
    }

    /**
     * Leitet den Verfikationsprozess für den Wechsel der E-Mail-Adresse ein
     * bei $tellOld == true wird eine Information an die alte Adresse geschickt
     */
    public function initEmailAuth($tellOld = true) {
        $this->data['new_email_token'] = Password::randomString(32);
        $this->data['new_email_token_expire_time'] = Config::newEmailTokenExpireTime;
        $this->sendAuthMailOnSave = true; // beim Speichern wird eine E-Mail verschickt
        $this->tellOld = $tellOld; // alte E-Mail-Adresse informieren
    }

    /**
     * Verifikation der neuen E-Mail-Adresse
     */
    public function finishEmailAuth() {
        $this->data['email'] = $this->data['new_email'];
        $this->data['new_email'] = '';
        $this->data['new_email_token'] = '';
        $this->data['new_email_token_expire_time'] = null;
        $this->sendAuthMailOnSave = false;
    }

    /**
     * Bricht den Verifizierungsprozess für eine neue E-Mail ggf. ab (wenn doch wieder die alte E-Mail-Adresse verwendet werden soll).
     * Wird von benutzer.php grundsätzlich immer dann aufgerufen, wenn die E-Mail-Adresse sich nicht ändert.
     */
    public function cancelEmailAuth() {
        $this->data['new_email'] = '';
        $this->data['new_email_token'] = '';
        $this->data['new_email_token_expire_time'] = null;
        $this->sendAuthMailOnSave = false;
    }

    /**
     * Sendet eine E-Mail
     * $to kann 'email' oder 'new_email' oder 'both' sein, ansonsten wird ggf. an beide Adressen gesendet.
     * @param string $subject
     * @param string $body
     * @throws \RuntimeException wenn eine E-Mail nicht versandt werden konnte.
     */
    public function sendEmail($subject, $body, $to = 'both'): void
    {
        $to_list = [];
        if ($to === 'email' || $to === 'both') {
            $to_list[] = $this->data['email'];
        }
        if ($to === 'new_email' || $to === 'both') {
            $to_list[] = $this->data['new_email'];
        }

        foreach ($to_list as $TO) {
            if (!(EmailService::getInstance()->send($TO, $subject, $body))) {
                throw new \RuntimeException('Beim Versand der E-Mail an ' . $TO . ' (ID ' . $this->data['id'] . ') ist ein Fehler aufgetreten.', 1522422201);
            }
        }
    }

    /**
     * Sendet die Bestätigungsmail für eine gewechselte E-Mail-Adresse.
     */
    private function sendEmailAuthMail() {
        if ($this->data['deleted']) return;

        // Token zusenden
        $this->sendEmail('E-Mail-Aktivierung',
            'Hallo ' . $this->get('fullName') . ',

Bitte rufe die folgende Adresse auf, um die Änderung deiner E-Mail-Adresse im MinD-Hochschul-Netzwerk zu bestätigen:

https://referenten.' . getenv('DOMAINNAME') . '/email_auth.php?token=' . urlencode($this->data['new_email_token']) . '

Der Link hat eine Gültigkeit von ' . ((strtotime(Config::newEmailTokenExpireTime) - time()) / 3600) . ' Stunden.
', '', 'new_email');


        // Info an die alte Adresse
        if ($this->tellOld) {
            $this->sendEmail('Deine E-Mail-Adresse wurde geändert.',
                'Hallo ' . $this->get('fullName') . ',

Jemand (hoffentlich du selbst!) möchte deine gespeicherte E-Mail-Adresse im MinD-Hochschul-Netzwerk ändern.

Die neue Adresse lautet ' . $this->data['new_email']  . '.

Wenn dich diese E-Mail überrascht, nimm bitte Kontakt mit dem IT-Team auf.
', '', 'email');
        }

        $this->sendAuthMailOnSave = false; // nicht nochmal
    }

    /**
     * Gibt alle IDs von Beiträgen zurück die einem Benutzer zugeordnet sind
     *
     * @return array mit der "vid" von allen Beiträgen
     */
    public function getAllVortraege() {
        return DB::query('SELECT vid FROM benutzerZuVortraege WHERE uid=%d ', $this->data['uid'])->get_column();
    }

    /**
     * Füge einen Vortrag zum Benutzer hinzu.
     *
     * @param $vid des Vortrags
     */
    public function addVortrag($vid) {
        if(Vortrag::lade($vid)) {
            DB::query('INSERT INTO benutzerZuVortraege SET uid=%d, vid=%d', $this->data['uid'], $vid);
        } else {
            die('Der Beitrag konnte dem Benutzer nicht zugeordnet werden, da kein Beitrag mit der ID (ID: ' . $vid . ') gefunden wurde.');
        }
    }

    /**
     * Entferne die Zuordnung von einem Vortrag zum Benutzer
     *
     * @param $vid des Vortrags
     */
    public function deleteVortrag($vid) {
        DB::query('DELETE FROM benutzerZuVortraege WHERE uid=$d AND vid=%d ', $this->data['uid'], $vid);
    }
}
