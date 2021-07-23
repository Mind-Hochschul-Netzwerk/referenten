<?php
namespace MHN\Referenten;
/**
 * Benutzerdaten bearbeiten
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/


// Liste der vom Benutzer änderbaren Strings, deren Werte nicht geprüft werden
// const bearbeiten_strings_ungeprueft = ['titel', 'mensa_nr', 'strasse', 'adresszusatz', 'plz', 'ort', 'land', 'strasse2', 'adresszusatz2', 'plz2', 'ort2', 'land2', 'telefon', 'mobil', 'homepage', 'sprachen', 'hobbys', 'interessen', 'studienort', 'studienfach', 'unityp', 'schwerpunkt', 'nebenfach', 'abschluss', 'zweitstudium', 'hochschulaktivitaeten', 'stipendien', 'auslandsaufenthalte', 'praktika', 'beruf', 'aufgabe_sonstiges_beschreibung'];

const bearbeiten_strings_ungeprueft = ['titel', 'vorname', 'nachname', 'geschlecht', 'mensa_nr', 'telefon', 'mobil', 'kurzvita', 'affiliation'];

// Liste der vom Benutzer änderbaren Booleans
const bearbeiten_bool_ungeprueft = ['aufnahmen', 'mhn_mitglied'];

// Liste der vom Programmteam (recht=ma-pt) änderbaren Strings
const bearbeiten_strings_admin = ['email'];

// Liste der vom Programmteam (recht=ma-pt) änderbaren Booleans
const bearbeiten_bool_admin = ['locked', 'publish', 'deleted'];

require_once '../lib/base.inc.php';
require_once '../lib/resizeImage.inc.php';

Auth::intern();

Tpl::set('htmlTitle', 'Mein Profil');
Tpl::set('navId', 'benutzer');
Tpl::sendHead();

ensure($_REQUEST['uid'], ENSURE_INT_GTEQ, 0, Auth::getUID());

$r = laden(!isset($_POST['neu']) ? $_REQUEST['uid'] : 0);

if (!Auth::ist($r->get('uid')) and !Auth::hatRecht('ma-pt')) die("Fehlende Berechtigung");

// als Funktion, weil es zweimal hier gebraucht wird
function laden($uid) {
    if ($uid) {
        $r = Benutzer::lade($uid, Auth::hatRecht('ma-pt') || Auth::hatRecht('referent')) or die("ID ungültig");
    } elseif (Auth::hatRecht('ma-pt')) {
        $r = Benutzer::neu(Password::randomString(32));
    } else {
        die("Fehlende Berechtigung");
    }
    foreach (array_keys(Benutzer::felder) as $feld) {
        Tpl::set($feld, $r->get($feld));
    }

    if ($r->get('new_email')) {
        Tpl::set('email', $r->get('new_email'));
    }
    return $r;
}


// wenn irgendein Feld (z.B. E-Mail) gesendet wurde, soll gespeichert werden
if (isset($_REQUEST['vorname'])) {
    // nur für das Programmteam
    if (Auth::hatRecht('ma-pt')) {
        foreach (bearbeiten_strings_admin as $key) {
            ensure($_REQUEST[$key], ENSURE_STRING);
            if ($key === 'email') {
                $r->setEmail($_REQUEST[$key]);
            } else {
                $r->set($key, $_REQUEST[$key]);
            }
            Tpl::set($key, $_REQUEST[$key]);
        }

        // Booleans prüfen (hier wird ggf. das Mitglied gelöscht, indem 'deleted' auf true gesetzt wird. Den Rest besorgt Mitglied::)
        foreach (bearbeiten_bool_admin as $key) {
            ensure($_REQUEST[$key], ENSURE_BOOL);
        }
        foreach (bearbeiten_bool_admin as $key) {
            $r->set($key, $_REQUEST[$key]);
            Tpl::set($key, $_REQUEST[$key]);
        }

        $key = 'geschlecht';
        ensure($_REQUEST[$key], ENSURE_STRING);
        if (!preg_match('/^[mwud]$/', $_REQUEST[$key])) die("Wert für $key ungültig.");
        $r->set($key, $_REQUEST[$key]);
        Tpl::set($key, $_REQUEST[$key]);

        $key = 'rechte';
        ensure($_REQUEST[$key], ENSURE_STRING);
        $rechte = explode(',', preg_replace('/\s/', '', $_REQUEST[$key]));
    }

    // Passwort ändern
    ensure($_REQUEST['new_password'], ENSURE_SET); // nicht ENSURE_STRING, weil dabei ein trim() durchgeführt wird
    ensure($_REQUEST['new_password2'], ENSURE_SET);
    ensure($_REQUEST['password'], ENSURE_SET);

    if ($_REQUEST['new_password'] and !$_REQUEST['new_password2'] and !$_REQUEST['password'] and Auth::checkPassword($_REQUEST['new_password'], $r->get('uid'))) {
        // nichts tun. Der Passwort-Manager des Users hat das Passwort eingefügt und autocomplete=off ignoriert
    } else if (($_REQUEST['password'] or $_REQUEST['new_password2']) and !$_REQUEST['new_password']) {
        Tpl::set('new_password_error', true);
    } else if ($_REQUEST['new_password']) {
        Tpl::set('set_new_password', true);
        if ($_REQUEST['new_password'] != $_REQUEST['new_password2']) {
            Tpl::set('new_password2_error', true);
        } else {
            // Die Benutzerverwaltung darf Passwörter ohne Angabe des eigenen Passworts ändern, außer das eigene
            if (Auth::hatRecht('ma-pt') and !Auth::ist($r->get('uid'))) {
                $r->set('password', $_REQUEST['new_password']);
            } else if (Auth::checkPassword($_REQUEST['password'])) {
                $r->set('password', $_REQUEST['new_password']);
            } else {
                Tpl::set('old_password_error', true);
            }
        }
    }

    // Ändern der Eigenschaften, die auch dann noch geändert werden dürfen, wenn das Profil gesichtet wurde
    foreach (['mensa_nr', 'telefon', 'mobil', 'aufnahmen'] as $key) {
        if (in_array($key, bearbeiten_strings_ungeprueft, true)) {
            ensure($_REQUEST[$key], ENSURE_STRING);
        } elseif (in_array($key, bearbeiten_bool_ungeprueft, true)) {
            ensure($_REQUEST[$key], ENSURE_BOOL, false);
        }
        $r->set($key, $_REQUEST[$key]);
        Tpl::set($key, $_REQUEST[$key]);
    }

    if (!$r->get('locked') || Auth::hatRecht('ma-pt')) {
        foreach (bearbeiten_bool_ungeprueft as $key) {
            ensure($_REQUEST[$key], ENSURE_BOOL, false);
            $r->set($key, $_REQUEST[$key]);
            Tpl::set($key, $_REQUEST[$key]);
        }

        foreach (bearbeiten_strings_ungeprueft as $key) {
            ensure($_REQUEST[$key], ENSURE_STRING);
            $r->set($key, $_REQUEST[$key]);
            Tpl::set($key, $_REQUEST[$key]);
        }

        // neues Profilbild
        if (isset($_FILES['profilbild']) and $_FILES['profilbild']['error'] == UPLOAD_ERR_OK) {
            // zuerst versuchen, den Dateityp zu ermitteln
            $format = null;
            switch ($_FILES['profilbild']['type']) {
            case 'image/jpeg':
                $type = 'jpeg';
                break;
            case 'image/png':
                $type = 'png';
                break;
            }
            if (!$type and preg_match('/\.jpe?g$/i', $_FILES['profilbild']['name'])) {
                $type = 'jpeg';
            } else if (!$type and preg_match('/\.png$/i', $_FILES['profilbild']['name'])) {
                $type = 'png';
            }
            if ($type) {
                // Dateiname zufällig wählen
                $fileName = $r->get('id') . '-' . Password::randomString(16) . '.jpeg';

                // Datei und Thumbnail erstellen
                list($size_x, $size_y) = resizeImage($_FILES['profilbild']['tmp_name'], 'profilbilder/' . $fileName, $type, 'jpeg', Config::profilbildMaxWidth, Config::profilbildMaxHeight);
                resizeImage($_FILES['profilbild']['tmp_name'], 'profilbilder/thumbnail-' . $fileName, $type, 'jpeg', Config::thumbnailMaxWidth, Config::thumbnailMaxHeight);

                // altes Profilbild löschen
                if ($r->get('profilbild') and is_file('profilbilder/' . $r->get('profilbild'))) {
                    unlink('profilbilder/' . $r->get('profilbild'));
                    unlink('profilbilder/thumbnail-' . $r->get('profilbild'));
                }

                $r->set('profilbild', $fileName);
                $r->set('profilbild_x', $size_x);
                $r->set('profilbild_y', $size_y);
            } else {
                Tpl::set('profilbild_format_unbekannt', true);
            }
        }

        // Profilbild löschen
        ensure($_REQUEST['bildLoeschen'], ENSURE_BOOL);
        if ($_REQUEST['bildLoeschen']) {
            // altes Profilbild löschen
            if ($r->get('profilbild') and is_file('profilbilder/' . $r->get('profilbild'))) {
                unlink('profilbilder/' . $r->get('profilbild'));
                unlink('profilbilder/thumbnail-' . $r->get('profilbild'));
            }
            $r->set('profilbild', '');
        }
    }

    // Speichern
    $r->set('db_modified', 'now');
    $r->save();
    Tpl::set('data_saved_info', true);

    // Rechte aktualisieren
    if (Auth::hatRecht('rechte')) {
        DB::query("DELETE FROM rechte WHERE uid=%d", $r->get('id'));
        ensure($_REQUEST['rechte'], ENSURE_STRING);
        $_REQUEST['rechte'] = preg_replace('/\s+/', ',', $_REQUEST['rechte']);
        $_REQUEST['rechte'] = preg_replace('/,+/', ',', $_REQUEST['rechte']);
        $rechte = array_unique(explode(',', $_REQUEST['rechte']));
        if (Auth::ist($r->get('id')) and (!in_array('rechte', $rechte))) {
            die('Du kannst dir das Recht zur Rechtverwaltung nicht selbst entziehen.');
        }
        foreach (array_unique($rechte) as $recht) {
            DB::query("INSERT INTO rechte SET uid=%d, recht='%s'", $r->get('id'), strtolower($recht));
        }
    }
    // und neu laden (insb. beim Löschen wichtig, sonst müssten alls Keys einzeln zurückgesetzt werden)
    $r = laden($r->get('uid'));
}

// Rechte aus Datenbank laden
if ($r->get('uid')) {
    Tpl::set('rechte', implode(', ', DB::query("SELECT recht FROM rechte WHERE uid=%d", $r->get('uid'))->get_column()));
} else {
    // Voreinstellung für neuen Benutzer
    Tpl::set('rechte', 'referent');
}

Tpl::render('benutzer');

Tpl::submit();
