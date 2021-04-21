<?php
namespace MHN\Referenten;
/**
* Mitgliedersuche
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';

Auth::intern();

Tpl::set('htmlTitle', 'Benutzersuche');
Tpl::set('navId', 'benutzersuche');
Tpl::set('title', 'Benutzersuche');
Tpl::sendHead();

if (!Auth::hatRecht('ma-pt')) die("Fehlende Rechte.");

// Felder mit |s bzw |s* nur mit sichtbarkeit
const felder = ['uid', 'vorname', 'nachname', 'email', 'mensa_nr', 'CONCAT(vorname, " ", nachname)'];
// Felder, bei denen nur nach Übereinstimmung statt nach Substring gesucht wird (müssen auch in felder aufgeführt sein)
const felder_eq = ['uid', 'mensa_nr'];

// TODO filter einbauen über beschaeftigung, auskunft_* und für mvread für aufgabe_*

ensure($_GET['q'], ENSURE_STRING);

if ($_GET['q']) {
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

    $AND = ['(1=1)'];
    foreach ($begriffe as $b) {
        $OR = [];
        foreach (felder as $feld) {
            $f = '(';
            if (strpos($feld, '|')) {
                list($feld, $sichtbarkeitsfeld) = explode('|', $feld);
                if ($sichtbarkeitsfeld == 's') $sichtbarkeitsfeld = 'sichtbarkeit_' . $feld;
                $f .= "$sichtbarkeitsfeld = 1 AND ";
            }
            if (in_array($feld, felder_eq)) {
                $f .= "$feld = '" . DB::_($b) . "')";
            } else {
                $f .= "$feld LIKE '%" . DB::_($b) . "%')";
            }
            $OR[] = $f;
        }
        
        $AND[] = '(' . implode(' OR ', $OR) . ')';
    }

    // TODO Pagination?? oder einfach suche einschränken lassen und die erst 50 zeigen...
    $uids = DB::query("SELECT uid FROM benutzer WHERE deleted = false AND " . implode(' AND ', $AND) . " ORDER BY nachname, vorname LIMIT 50")->get_column();

    // Das Programmteam darf auch nach gelöschten Benutzern suchen
    if (Auth::hatRecht('ma-pt')) {
        $uid = DB::query('SELECT uid FROM benutzer WHERE uid=%d OR email="%s"', (int)$_GET['q'], $_GET['q'])->get();
        if ($uid) {
            array_unshift($uids, $uid);
        }
        $uids = array_unique($uids);
    }

    // Alle Mitglieder laden
    $ergebnisse = [];
    if (count($uids)) {
        foreach ($uids as $uid) {
            $r = Benutzer::lade($uid, true);

            if ($r === null) {
                break;
            }

            $beitragsjahr = null;
            if ($vid = Vortrag::ladeAktuellsteVIDzuUID($uid)) {
                if ($v = Vortrag::lade($vid)) {
                    if ($v && $e = Event::lade($v->get('eid'))) {
                        $beitragsjahr = $e->get('beitragsjahr');
                    }
                }
            }

            // auszugebende Daten speichern und an Tpl übergeben
            $e = [
                'uid' => $r->get('uid'),
                'last_login' => $r->get('last_login'),
                'fullName' => $r->get('fullName'),
                'email' => $r->get('email'),
                'deleted' => $r->get('deleted'),
                'locked' => $r->get('locked'),
                'publish' => $r->get('publish'),
                'profilbild' => $r->get('profilbild') ? ('thumbnail-' . $r->get('profilbild')) : null,
                'beitragsjahr' => $beitragsjahr,
            ];
            
            $ergebnisse[] = $e;
        }
    }
    Tpl::set('ergebnisse', $ergebnisse);
    
}

Tpl::render('benutzersuche');
    
Tpl::submit();

?>
