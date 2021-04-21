<?php
declare(strict_types=1);
namespace MHN\Referenten\Controller;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use \MHN\Referenten\Domain\Repository\MaschedRepository;
use \MHN\Referenten\DB;
use \MHN\Referenten\Tpl;
use \MHN\Referenten\SpreadsheetReader;

/**
 * Controller für die MA-Schedule-JSON-Dateien
 */
class MaschedController implements \MHN\Referenten\Interfaces\Singleton
{
    use \MHN\Referenten\Traits\Singleton;

    /** @var MaschedRepository|null */
    private $maschedRepository = null;

    /** @var int */
    private $jahr = 0;
    private $eventKennzeichen = "";

    /**
     * Konstruktor
     *
     * @return void
     */
    public function __construct()
    {
        $this->maschedRepository = MaschedRepository::getInstance();
        $this->jahr = (int)date('Y');
        $this->eventKennzeichen = "MA" . $this->jahr;
    }

    /**
     * Hauptroutine
     *
     * @return void
     */
    public function run()
    {
        $clearCache = false;

        if (!empty($_GET['clearCache'])) {
            $clearCache = true;
        }

        if (isset($_FILES['bloecke']) && $_FILES['bloecke']['error'] === UPLOAD_ERR_OK) {
            $this->ladeBloecke();
            $clearCache = true;
        }

        if (isset($_FILES['programm']) && $_FILES['programm']['error'] === UPLOAD_ERR_OK) {
            $this->ladeProgramm();
            $clearCache = true;
        }

        if ($clearCache) {
            $this->maschedRepository->clearCache((int)date('Y'));
        }

        $this->show();
    }

    /**
     * Liest die im HTML-Feld "bloecke" übertragene Datei mit der Blockung ein.
     *
     * @return int Anzahl der Blöcke
     */
    private function ladeBloecke()
    {
        try {
            $sheet = SpreadsheetReader::getSheetFromUploadedFile('bloecke');
        } catch (\RuntimeException $e) {
            die('Der Dateityp wird nicht unterstützt oder konnte nicht erkannt werden.');
        }

        $anzahl = 0;
        foreach ($sheet->getRowIterator() as $row) {
            // erste Zeile enthält die Überschriften
            if (!isset($indexes)) {
                $indexes = array_flip($row);
                continue;
            }

            if (!$row[$indexes['typ']] || !$row[$indexes['datum']] || !$row[$indexes['uhrzeit_beginn']] || !$row[$indexes['uhrzeit_ende']]) {
                continue;
            }

            // Bisherige Konfiguration löschen. Beim Lesen der ersten Zeile durchführen.
            if ($anzahl === 0) {
                DB::query('DELETE FROM bloecke WHERE jahr=%d', $this->jahr);
            }

            $titel = $row[$indexes['titel']];
            $typ = $row[$indexes['typ']];

            try {
                $beginn = SpreadsheetReader::makeDateTimeFromDateAndTime($row[$indexes['datum']], $row[$indexes['uhrzeit_beginn']]);
                $ende = SpreadsheetReader::makeDateTimeFromDateAndTime($row[$indexes['datum']], $row[$indexes['uhrzeit_ende']], $beginn);
            } catch (\Exception $e) {
                die('Ein Datum oder eine Uhrzeit konnte nicht gelesen werden. Bitte auf Tippfehler überprüfen.');
            }

            switch (strtolower($typ)) {
                case 'slot':
                    $typ = MaschedRepository::TYP_FREI;
                    break;
                case 'pause':
                    $typ = MaschedRepository::TYP_PAUSE;
                    break;
                default:
                    die('Ungültiger Wert in Spalte "typ":' . $typ);
            }

            DB::query('INSERT INTO bloecke SET titel="%s", typ="%s", beginn="%s", ende="%s", jahr=%d', $titel, $typ, $beginn->format('Y-m-d H:i:s'), $ende->format('Y-m-d H:i:s'), $this->jahr);
            ++$anzahl;
        }

        return $anzahl;
    }

    /**
     * Liest die im HTML-Feld "programm" übertragene Datei mit dem Programm ein.
     *
     * @return int Anzahl der Programmpunkte
     */
    private function ladeProgramm()
    {
        try {
            $sheet = SpreadsheetReader::getSheetFromUploadedFile('programm');
        } catch (\RuntimeException $e) {
            die('Der Dateityp wird nicht unterstützt oder konnte nicht erkannt werden.');
        }

        $anzahl = 0;
        foreach ($sheet->getRowIterator() as $row) {
            // erste Zeile enthält die Überschriften
            if (!isset($indexes)) {
                $indexes = array_flip($row);
                $missingIndexes = array_diff(['vid', 'beitrag_titel', 'beitrag_raum', 'beitrag_typ', 'datum', 'uhrzeit_beginn', 'uhrzeit_ende'], array_flip($indexes));
                if (count($missingIndexes) > 0) {
                    die('Fehlende Spalte in der Datei: ' . implode(', ', $missingIndexes));
                }
                continue;
            }

            if (!$row[$indexes['vid']] || !$row[$indexes['beitrag_typ']] || !$row[$indexes['datum']] || !$row[$indexes['uhrzeit_beginn']] || !$row[$indexes['uhrzeit_ende']]) {
                continue;
            }

            // Bisherige Konfiguration löschen. Beim Lesen der ersten Zeile durchführen.
            if ($anzahl === 0) {
                DB::query('DELETE FROM rahmenprogramm WHERE jahr=%d', $this->jahr);
                DB::query('UPDATE vortraege SET programm_raum=NULL, programm_beginn=NULL, programm_ende=NULL WHERE eid IN (SELECT eid FROM events WHERE kennzeichen="%s")', $this->eventKennzeichen);
            }

            $id = $row[$indexes['vid']];
            $titel = $row[$indexes['beitrag_titel']];
            $raum = $row[$indexes['beitrag_raum']];
            $typ = $row[$indexes['beitrag_typ']];
            $typ = array_search(strtolower($typ), \MHN\Referenten\Util::BEITRAGSFORMEN);
            if (!$typ) {
                $typ = 's';
            }
            try {
                $beginn = SpreadsheetReader::makeDateTimeFromDateAndTime($row[$indexes['datum']], $row[$indexes['uhrzeit_beginn']]);
                $ende = SpreadsheetReader::makeDateTimeFromDateAndTime($row[$indexes['datum']], $row[$indexes['uhrzeit_ende']], $beginn);
            } catch (\Exception $e) {
                die('Ein Datum oder eine Uhrzeit konnte nicht gelesen werden. Bitte auf Tippfehler überprüfen.');
            }

            // zu numerischen IDs gibt es einen entsprechenden Beitrags-Datensatz, nicht-numerische IDs sind Rahmenprogramm
            if (is_numeric($id)) {
                DB::query('UPDATE vortraege SET programm_raum="%s", programm_beginn="%s", programm_ende="%s" WHERE vid=%d', $raum, $beginn->format('Y-m-d H:i:s'), $ende->format('Y-m-d H:i:s'), $id);
            } elseif (preg_match('/^[0-9a-zA-Z-_]+$/', $id)) {
                DB::query('INSERT INTO rahmenprogramm SET id="%s", titel="%s", raum="%s", beitragsform="%s", beginn="%s", ende="%s", jahr=%d', $id, $titel, $raum, $typ, $beginn->format('Y-m-d H:i:s'), $ende->format('Y-m-d H:i:s'), $this->jahr);
            } else {
                die('Eine ID enthält ungültige Zeichen: ' . $id);
            }

            ++$anzahl;
        }

        return $anzahl;
    }

    /**
     * Anzeige
     *
     * @return void
     */
    function show()
    {
        Tpl::set('bloecke', $this->maschedRepository->getBloecke($this->jahr));
        Tpl::set('rahmenprogramm', $this->maschedRepository->getRahmenprogramm($this->jahr));
        Tpl::set('maschedTime', new \DateTime('@' . $this->maschedRepository->getCacheTime($this->jahr)));
        Tpl::render('Masched/masched');
    }
}
