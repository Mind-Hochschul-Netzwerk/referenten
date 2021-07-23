<?php
namespace MHN\Referenten;
/**
* Vortrag bearbeiten
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

// Liste der vom Vortrag änderbaren Strings, deren Werte nicht geprüft werden
const bearbeiten_strings_ungeprueft = ['vTitel', 'kurztitel', 'beitragsform', 'beitragssprache', 'beschrTeilnehmeranzahl', 'maxTeilnehmeranzahl', 'praefZeit', 'anmerkungen', 'abstract', 'equipment_sonstiges_beschreibung'];

// Liste der vom Vortrag änderbaren Integer, deren Werte nicht geprüft werden
const bearbeiten_int_ungeprueft = ['eid'];

// Liste der vom Mitglied änderbaren Booleans
const bearbeiten_bool_ungeprueft = ['equipment_beamer', 'equipment_computer', 'equipment_wlan', 'equipment_lautsprecher', 'equipment_mikrofon', 'equipment_flipchart', 'equipment_sonstiges'];

// Liste der vom Programmteam (recht=ma-pt) änderbaren Strings
const bearbeiten_strings_admin = ['programm_raum', 'programm_beginn', 'programm_ende'];

// Liste der vom Programmteam (recht=ma-pt) änderbaren Booleans
const bearbeiten_bool_admin = ['locked', 'publish'];

require_once '../lib/base.inc.php';

Auth::intern();

Tpl::set('htmlTitle', 'Beitrag bearbeiten');
Tpl::set('navId', 'vortrag');
Tpl::sendHead();

ensure($_REQUEST['uid'], ENSURE_INT_GT, 0, Auth::getUID());

if (empty($_REQUEST['vid'])) {
    $vid = Vortrag::ladeAktuellsteVIDzuUID($_REQUEST['uid']);

    if(empty($vid) and !Auth::istIn($_REQUEST['uids'])) die("Fehlende Rechte.");
} else {
    $vid = $_REQUEST['vid'];
}

$v = laden($vid);

if ($v === null) {
    die('Die Vortrags-ID ist ungültig. Möglicherweise wurde der Vortrag gelöscht.');
}

if (!(Auth::hatRecht('ma-pt') || Auth::istIn($v->get('uids')))) {
    die("Fehlende Rechte.");
}

if (isset($_REQUEST['vTitel']) && $v->get('locked') && !(Auth::hatRecht('ma-pt'))) {
    die("Schreibgeschützte Beiträge darf nur das Programmteam ändern.");
}

// als Funktion, weil es zweimal hier gebraucht wird
function laden($vid) {
    $v = Vortrag::lade($vid, Auth::hatRecht('ma-pt'));

    if ($v !== null) {
        foreach (array_keys(Vortrag::felder) as $feld) {
            Tpl::set($feld, $v->get($feld));
        }

        $userIDs = $v->get('uids');

        $referenten = [];

        $fullNames = '';

        foreach ($userIDs as $uid) {
            $daten = [];
            $b = Benutzer::lade($uid, true) or die('Benutzer nicht gefunden. Fehler in der Datenbank.');
            $referenten[$uid] = [
                'name' => $b->get('fullName'),
                'locked' => $b->get('locked'),
                'publish' => $b->get('publish'),
            ];
        }

        Tpl::set('referenten', $referenten);
        Tpl::set('uids', implode(', ', $userIDs));

        $events = Event::getListOfAllEvents();

        $events_prep =  [];
        foreach ($events as $key => $value) {
            $events_prep[$value['eid']] = $value['bezeichnung'];
        }

        Tpl::set('events', $events_prep);
    }

    return $v;
}

// Löschen
if (isset($_REQUEST['delete'])) {
    $v->set('deleted', true);
    $v->save();
    if (!Auth::hatRecht('ma-pt')) {
        Tpl::pause();
        header('Location: vortragsliste.php');
        exit;
    }
} elseif (isset($_REQUEST['undelete']) && Auth::hatRecht('ma-pt')) {
    $v->set('deleted', false);
    $v->save();
}

// wenn irgendein Feld (z.B. Vortragstitel) gesendet wurde, soll gespeichert werden
if (isset($_REQUEST['vTitel'])) {
    foreach (bearbeiten_strings_ungeprueft as $key) {
        ensure($_REQUEST[$key], ENSURE_STRING);
        if ($key == 'kurztitel') { // max. length: 24 characters
            $_REQUEST[$key] = mb_substr($_REQUEST[$key], 0, 24);
        }
        $v->set($key, $_REQUEST[$key]);
        Tpl::set($key, $_REQUEST[$key]);
    }

    foreach (bearbeiten_int_ungeprueft as $key) {
        ensure($_REQUEST[$key], ENSURE_INT);

        if($key === 'eid') {
            $e = Event::lade($_REQUEST[$key]);

            if ($e === null) {
                die("Kein Event zur Event-ID gefunden.");
            } else if ($e->isInPast() && !Auth::hatRecht('ma-pt')) {
                die("Nur Mitglieder des Programmteams können einen Beitrag zu einem bereits vergangenen Event zuordnen.");
            }
        }
        $v->set($key, $_REQUEST[$key]);
        Tpl::set($key, $_REQUEST[$key]);
    }

    foreach (bearbeiten_bool_ungeprueft as $key) {
        ensure($_REQUEST[$key], ENSURE_BOOL);
        $v->set($key, $_REQUEST[$key]);
        Tpl::set($key, $_REQUEST[$key]);
    }

    // nur für das Programmteam
    if (Auth::hatRecht('ma-pt')) {
        foreach (bearbeiten_strings_admin as $key) {
            ensure($_REQUEST[$key], ENSURE_STRING);
            $v->set($key, $_REQUEST[$key]);
            Tpl::set($key, $_REQUEST[$key]);
        }

        foreach (bearbeiten_bool_admin as $key) {
            ensure($_REQUEST[$key], ENSURE_BOOL);
        }
        // Booleans prüfen (hier wird ggf. das Mitglied gelöscht, indem 'deleted' auf true gesetzt wird. Den Rest besorgt Mitglied::)
        foreach (bearbeiten_bool_admin as $key) {
            $v->set($key, $_REQUEST[$key]);
            Tpl::set($key, $_REQUEST[$key]);
        }
        if (isset($_REQUEST['uids'])) {
            // Ersetzt alle Zeichenfolgen in denen keine Ziffer vorkommt durch ein Semikolon.
            // Dadurch werden Leerzeichen, Texteingaben und doppelte Semikolon beseitigt.
            $_REQUEST['uids'] = preg_replace('/\D+/', ',', $_REQUEST['uids']);
            $uids = array_unique(explode(',', $_REQUEST['uids']));


            // Hinzufügen von Referenten zur DB:
            $uids_add = array_diff($uids, $v->getAllUserIds());

            foreach ($uids_add as $uid) {
                $v->addUser($uid);
            }

            // Entfernen von Referenten:
            $uids_rm = array_diff($v->getAllUserIds(), $uids);

            foreach ($uids_rm as $uid) {
                $v->deleteUser($uid);
            }

            Tpl::set('uids', implode(', ', $v->get('uids')));
        }
    }
    // Speichern
    $v->set('db_modified', 'now');
    $v->save();
    Tpl::set('data_saved_info', true);

    // und neu laden (insb. beim Löschen wichtig, sonst müssten alls Keys einzeln zurückgesetzt werden)
    laden($v->get('vid'));
}

Tpl::render('vortrag');

Tpl::submit();

