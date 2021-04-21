<?php
namespace MHN\Referenten;
/**
 * Vortragssuche
 *
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 */

require_once '../lib/base.inc.php';

Auth::intern();

Tpl::set('htmlTitle', 'Vortragssuche');
Tpl::set('navId', 'vortragssuche');
Tpl::set('title', 'Vortragssuche');
Tpl::sendHead();

if (!Auth::hatRecht('ma-pt')) die("Fehlende Rechte.");

const EID_ALLE_EVENTS = 999;

const felder = ['v.vid', 'v.vTitel', 'v.eid', 'v.abstract', 'v.anmerkungen', 'b.uid', 'b.vorname', 'b.nachname', 'CONCAT(b.vorname, " ", b.nachname)', 'b.email'];
// Felder, bei denen nur nach Übereinstimmung statt nach Substring gesucht wird (müssen auch in felder aufgeführt sein)
const felder_eq = ['v.vid', 'bzv.uid', 'v.eid'];

// TODO filter einbauen über beschaeftigung, auskunft_* und für mvread für aufgabe_*

ensure($_GET['q'], ENSURE_STRING);
ensure($_GET['eid'], ENSURE_INT, 0, EID_ALLE_EVENTS);
Tpl::set('q', $_GET['q']);
Tpl::set('eid', $_GET['eid']);

    $allEvents = Event::getListOfAllEvents();

    $events =  [];
    $events[EID_ALLE_EVENTS] = 'Über alle Events suchen';
    foreach ($allEvents as $key => $value) {
        $events[$value['eid']] = $value['bezeichnung'];
    }

    Tpl::set('events', $events);

    // Strings die in Anführungszeichen stehen, müssen wirklich genau so vorkommen -> vor dem aufsplitten an Leerzeichen ersetzen
    $literalMap = [];
    $_GET['q'] = preg_replace_callback('/"([^"]+)"/', function ($matches) use (&$literalMap) {
        $key = Password::randomString(64);
        $literalMap[$key] = $matches[1];
        return ' ' . $key . ' ';
    }, $_GET['q']);

    $_GET['q'] = preg_replace('/\s+/', ' ', $_GET['q']);
    $begriffe = explode(' ', trim($_GET['q']));

    // zurück ersetzen
    array_walk($begriffe, function (&$begriff) use ($literalMap) {
        if (isset($literalMap[$begriff])) {
            $begriff = $literalMap[$begriff];
        }
    });

    $AND = [];

    if ($_GET['eid'] !== EID_ALLE_EVENTS) {
        $AND[] = '(' . 'v.eid=' . $_GET['eid'] . ')';
    }


    foreach ($begriffe as $b) {
        $OR = [];
        foreach (felder as $feld) {
            $f = '(';
            if (in_array($feld, felder_eq)) {
                $f .= "$feld = '" . DB::_($b) . "')";
            } else {
                $f .= "$feld LIKE '%" . DB::_($b) . "%')";
            }
            $OR[] = $f;
        }

        $AND[] = '(' . implode(' OR ', $OR) . ')';
    }

    // nur das Programmteam darf auch nach gelöschten Vorträgen suchen
    if (!Auth::hatRecht('ma-pt')) {
        $AND[] = 'v.deleted = false';
    }

    $vids = DB::query("SELECT v.vid FROM vortraege v JOIN benutzerZuVortraege bzv ON v.vid = bzv.vid JOIN benutzer b ON b.uid=bzv.uid JOIN events e ON e.eid = v.eid WHERE " . implode(' AND ', $AND) . " ORDER BY v.eid DESC, v.programm_beginn, v.vTitel LIMIT 500")->get_column();
    $vids = array_unique($vids);

    // Alle Vorträge laden
    $ergebnisse = [];
    if (count($vids)) {
        foreach ($vids as $vid) {
            $v = Vortrag::lade($vid, true);

            if ($v === null) {
                break;
            }

            $uids = $v->get('uids');

            $referenten = [];

            foreach ($uids as $uid) {
                $daten = [];
                $b = Benutzer::lade($uid);

                if ($b === null) {
                    continue;
                };

                $referenten[$uid] = [
                    'name' => $b->get('fullName'),
                    'publish' => $b->get('publish'),
                    'locked' => $b->get('locked'),
                ];
            }

            if($v->get('eid')) {
                $event = Event::lade($v->get('eid'));

                if ($event === null) {
                    break;
                }
            }

            // auszugebende Daten speichern und an Tpl übergeben
            $ergebnisse[] = [
                'vid' => $v->get('vid'),
                'beitragsjahr' => $event->get('beitragsjahr'),
                'veranstaltung' => $event->get('bezeichnung'),
                'referenten' => $referenten,
                'vTitel' => $v->get('vTitel'),
                'beitragsform' => $v->get('beitragsform'),
                'deleted' => $v->get('deleted'),
                'locked' => $v->get('locked'),
                'publish' => $v->get('publish'),
                'programm_beginn' => $v->get('programm_beginn') ? $v->get('programm_beginn') : null,
            ];
        }
    }
    Tpl::set('ergebnisse', $ergebnisse);

Tpl::render('vortragssuche');

Tpl::submit();

?>
