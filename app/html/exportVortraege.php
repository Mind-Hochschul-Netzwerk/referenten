<?php
namespace MHN\Referenten;
/**
 * Created by PhpStorm.
 * User: guido
 * Date: 08.04.17
 * Time: 15:04
 */

use MHN\Referenten\Vortrag;
use MHN\Referenten\Benutzer;

const no_output_buffering = true;
require_once '../lib/base.inc.php';

Auth::intern();

if (!Auth::hatRecht('ma-pt')) die("Fehlende Rechte.");

header("Content-Disposition: attachment; filename=\"vortraege.csv\"");
header("Content-Type: text/csv; charset=utf-8");

$ids = explode(",",  $_GET['var']);

// Reihenfolge der Einträge (+ sämtliche in Vortrag::felder)
$spalten = ['vid', 'uid', 'titel', 'vorname', 'nachname', 'email', 'geschlecht', 'affiliation', 'kurzvita'];
foreach(array_keys(Vortrag::felder) as $spalte) {
    if (in_array($spalte, $spalten, true)) {
        continue;
    }
    $spalten[] = $spalte;
}

$kopfzeile = [];
foreach ($spalten as $spalte) {
    // einige werden nach außen hin umbenant, damit es besser zu den Bezeichnungen im MaschedController passt
    switch ($spalte) {
        case "vTitel":
            $kopfzeile[$spalte] = "beitrag_titel";
            break;
        case "beitragsform":
            $kopfzeile[$spalte] = "beitrag_typ";
            break;
        case "programm_raum":
            $kopfzeile[$spalte] = "beitrag_raum";
            break;
        case "programm_beginn":
            $kopfzeile["datum"] = "datum";
            $kopfzeile[$spalte] = "uhrzeit_beginn";
            break;
        case "programm_ende":
            $kopfzeile[$spalte] = "uhrzeit_ende";
            break;
        default:
            $kopfzeile[$spalte] = $spalte;
    }
}

$table = [$kopfzeile];

foreach ($ids as $id) {

    $v = Vortrag::lade($id);
    if ($v === null) {
        continue;
    }

    $benutzer = Benutzer::lade($v->get('uid'));

    $zeile = $kopfzeile;
    $zeile['vid'] = $v->get('vid');
    $zeile['uid'] = $v->get('uid');
    $zeile['titel'] = $benutzer->get('titel');
    $zeile['vorname'] = $benutzer->get('vorname');
    $zeile['nachname'] = $benutzer->get('nachname');
    $zeile['email'] = $benutzer->get('email');
    $zeile['geschlecht'] = $benutzer->get('geschlecht');
    $zeile['affiliation'] = $benutzer->get('affiliation');
    $zeile['kurzvita'] = $benutzer->get('kurzvita');

    foreach(array_keys(Vortrag::felder) as $spalte) {
        $value = $v->get($spalte);

        // Daten in Tabelle eintragen und ggf. Format so anpassen wie im MaschedController beim Import erwartet
        if (gettype($value) === 'object' && get_class($value) === 'DateTime') {
            switch ($spalte) {
                case "programm_beginn":
                    $zeile["datum"] = $value->format('Y-m-d');
                    $zeile[$spalte] = $value->format('H:i:s');
                    break;
                case "programm_ende":
                    $zeile[$spalte] = $value->format('H:i:s');
                    break;
                default:
                    $zeile[$spalte] = $value->format('Y-m-d H:i:s');
                    break;
            }
        } else {
            switch ($spalte) {
                case "beitragsform":
                    $zeile[$spalte] = [
                        'v' => 'Vortrag',
                        'w' => 'Workshop',
                        's' => 'sonstiges',
                    ][$value];
                    break;
                default:
                    $zeile[$spalte] = $value;
            }
        }
    }

    $table[] = $zeile;
}

$datei = fopen("php://output", 'w');
foreach ($table as $zeile) {
    fputcsv($datei, $zeile, ',');

}
fclose($datei);

