<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
* Gibt die zur Veröffentlichung markierten Daten (Referenten + Vortraege) für ein gegebenes Event zurück.
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

use MHN\Referenten\Benutzer;
use MHN\Referenten\Vortrag;
use function Sodium\add;

const no_output_buffering = true; // Mitteilung an tpl.inc.php, dass es sich um ein Backend-Skript handelt

require_once '../../lib/base.inc.php';

header('Content-Type: application/json');

ob_start();

register_shutdown_function(function () {
    rueckgabe('error');
});

set_error_handler(function($errno, $errstr) {
    rueckgabe('error', [], '[' . $errno . '] ' . $errstr);
});

function rueckgabe(string $status, $data = [], $errstr = null)
{
    static $gemeldet = false;
    if ($gemeldet) {
        return;
    }
    $gemeldet = true;

    $text = [];
    if ($errstr !== null) {
        $text['errstr'] = $errstr;
    }
    if (ob_get_contents() !== "") {
        $text['buffer_contents'] = ob_get_contents();
    }
    ob_end_clean();

    $msg = [
        'status' => $status,
        'data' => $data,
        'errorMessage' => $text,
    ];

    echo json_encode($msg);

    exit;
}

ensure($_REQUEST['kennzeichen'], ENSURE_STRING, 0, 'MA' . date('Y'));

$ignoreCharactersAtOrder = '0123456789"\'-_ ?!.:@';
$vTitelWithoutIgnoredCharacters = 'v.vTitel';
foreach (str_split($ignoreCharactersAtOrder) as $character) {
    $vTitelWithoutIgnoredCharacters = 'REPLACE(' . $vTitelWithoutIgnoredCharacters . ', "' . DB::_($character) . '", "")';
}

$ids = DB::query('
    SELECT v.vid AS vid
    FROM vortraege v
        JOIN benutzerZuVortraege bzv ON v.vid = bzv.vid
        JOIN benutzer b ON b.uid=bzv.uid
        JOIN events e ON e.eid = v.eid
    WHERE
        v.deleted = false AND
        b.publish = true AND
        v.publish = true AND
        e.kennzeichen = "%s"
    ORDER BY
        v.programm_beginn IS NULL,
        v.programm_beginn,
        v.programm_raum,
        ' . $vTitelWithoutIgnoredCharacters . '
    LIMIT 500
    ', $_REQUEST['kennzeichen'])->get_column();
$ids = array_unique($ids);

$data= [];
foreach ($ids as $id) {
    $v = Vortrag::lade($id);
    if ($v === null) {
        trigger_error('Beitrag mit der ID ' . $id. ' nicht gefunden.', E_USER_ERROR);
    }

    $referenten = [];

    foreach ($v->get('uids') as $uid) {
        $referent = Benutzer::lade($uid);
        if ($referent === null) {
            continue;
        }
        $referenten[] = [
            'name' => $referent->get('fullName'),
            'kurzvita' => $referent->get('kurzvita'),
        ];
    }

    $data[] = [
        'vid' => $v->get('vid'),
        'referenten' => $referenten,
        'vTitel' => $v->get('vTitel'),
        'beitragsform' => $v->get('beitragsform'),
        'beitragssprache' => $v->get('beitragssprache'),
        'beschrTeilnehmeranzahl' => $v->get('beschrTeilnehmeranzahl'),
        'maxTeilnehmeranzahl' => $v->get('maxTeilnehmeranzahl'),
        'abstract' => $v->get('abstract'),
        'programm_raum' => $v->get('programm_raum'),
        'programm_beginn' => $v->get('programm_beginn'),
        'programm_ende' => $v->get('programm_ende'),
    ];
}

rueckgabe('success', $data);
