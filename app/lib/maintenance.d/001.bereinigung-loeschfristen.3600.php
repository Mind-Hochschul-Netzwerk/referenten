<?php
declare(strict_types=1);

namespace MHN\Referenten;

/**
 * Löscht Daten von Vorträge bei denen kein berechtigtes Interesse zur Speicherung mehr besteht.
 *
 * @author Guido Drechsel <mhn@guido-drechsel.de>
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

// Datenschutz: Benutzerdaten löschen, wenn Löschdatum überschritten ist
$uids = DB::query('SELECT uid FROM benutzer WHERE deleted = false AND datenschutz_bereinigt = false AND datenschutz_bereinigung_termin IS NOT NULL AND datenschutz_bereinigung_termin < NOW()')->get_column();

foreach ($uids as $uid) {
    $b = Benutzer::lade($uid);
}

// Datenschutz: nicht benötigte Vortragsdaten löschen, wenn Löschdatum überschritten ist
$vids = DB::query('SELECT vid FROM vortraege WHERE deleted = false AND datenschutz_bereinigt = false AND datenschutz_bereinigung_termin IS NOT NULL AND datenschutz_bereinigung_termin < NOW()')->get_column();

foreach ($vids as $vid) {
    $v = Vortrag::lade($vid);
}
