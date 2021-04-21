<?php
namespace MHN\Referenten;
/**
* Vortragsliste
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';

Auth::intern();

Tpl::set('htmlTitle', 'Vortragsliste');
Tpl::set('navId', 'Vortragsliste');
Tpl::set('title', 'Meine Beiträge');
Tpl::sendHead();

// Felder mit |s bzw |s* nur mit sichtbarkeit
const felder = ['vid', 'uid', 'vTitel', 'beitragsjahr', 'beitragsform'];
// Felder, bei denen nur nach Übereinstimmung statt nach Substring gesucht wird (müssen auch in felder aufgeführt sein)
const felder_eq = ['vid', 'uid'];

ensure($_REQUEST['uid'], ENSURE_INT_GT, 0, Auth::getUID());

if ($_REQUEST['uid'] === Auth::getUID() && !Auth::hatRecht('referent')) {
    die("Fehlende Rechte.");
}
if ($_REQUEST['uid'] !== Auth::getUID() && !Auth::hatRecht('ma-pt')) {
    die("Fehlende Rechte.");
}
$benutzer = Benutzer::lade($_REQUEST['uid']);
if (!$benutzer) {
    die('Benutzer existiert nicht');
}

if (isset($_POST['neu'])) {
    $v = Vortrag::neu($_REQUEST['uid']);

    Tpl::pause();
    header('Location: vortrag.php?vid=' . $v->get('vid'));
    exit;
}

if ($_REQUEST['uid']) {
    $uid = (int)$_REQUEST['uid'];

    Tpl::set('userId', $uid);

    // TODO Pagination?? oder einfach suche einschränken lassen und die erst 50 zeigen...
    $vids = DB::query('SELECT v.vid FROM vortraege v JOIN events e ON v.eid = e.eid JOIN benutzerZuVortraege bzv ON v.vid = bzv.vid WHERE v.deleted = false AND bzv.uid=%d  ORDER BY e.datum_letzter_tag DESC, v.vTitel LIMIT 500', $uid)->get_column();

    // Alle Mitglieder laden
    $ergebnisse = [];
        foreach ($vids as $vid) {
            $v = Vortrag::lade($vid, false);

            if ($v === null) {
                break;
            }

            $uids = $v->get('uids');

            $referenten = [];

            foreach ($uids as $uid) {
                $daten = [];
                $b = Benutzer::lade($uid);

                if ($b === null) {
                    break;
                }

                $daten['fullName'] = $b->get('fullName');
                $daten['profilbild'] = $b->get('profilbild');
                $referenten[$uid] = $daten;
            }

            // auszugebende Daten speichern und an Tpl übergeben

            if($v->get('eid')) {
                $event = Event::lade($v->get('eid'));

                if ($event === null) {
                    break;
                }
            }
            $e = [
                'vid' => $v->get('vid'),
                'beitragsjahr' => $event->get('beitragsjahr'),
                'veranstaltung' => $event->get('bezeichnung'),
                'referenten' => $referenten,
                'vTitel' => $v->get('vTitel'),
                'beitragsform' => $v->get('beitragsform'),
                'deleted' => $v->get('deleted'),
                'locked' => $v->get('locked'),
                'publish' => $v->get('publish'),
            ];

            $ergebnisse[] = $e;
        }
    Tpl::set('ergebnisse', $ergebnisse);


}

Tpl::render('vortragsliste');
    
Tpl::submit();

?>
