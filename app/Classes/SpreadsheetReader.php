<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

/**
 * Liest Tabellen (XLSX, ODS, CSV)
 */
class SpreadsheetReader
{
    /**
     * Liest eine hochgeladene Datei.
     *
     * @param string $name Name im HTML-Formular
     * @return Box\Spout\Reader\SheetInterface erste Tabelle
     * @throws \InvalidArgumentException wenn der Dateiupload fehlgeschlagen war
     * @throws \RuntimeException wenn der Dateityp nicht unterstützt wird oder nicht erkannt wurde
     */
    public static function getSheetFromUploadedFile(string $name)
    {
        if (empty($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Dateiupload fehlgeschlagen.', 1505677171);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($_FILES[$name]['tmp_name']);

        // genauer Typ konnte nicht erkannt werden:
        if ($type === 'application/octet-stream') {
            // Auf Dateiendung vertrauen. Dateiendung ist alles ab dem letzten Punkt im Dateinamen
            $pos = strrpos($_FILES[$name]['name'], '.');
            $ext = ($pos === false) ? false : strtolower(substr($_FILES[$name]['name'], $pos+1));
            if (!in_array($ext, ['ods', 'xlsx', 'csv'], true)) {
                $ext = false;
            }
        } else {
            $ext = array_search($type, [
                'csv' => 'text/csv',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ], true
            );
        }
        if ($ext === false) {
            throw new \RuntimeException('Dateiformat wird nicht unterstützt: ' . $type, 1505677023);
        }

        $path = '/tmp/SpreadsheetReader.' . $ext;
        move_uploaded_file($_FILES[$name]['tmp_name'], $path);

        switch ($ext) {
            case 'csv':
                $reader = ReaderFactory::create(Type::CSV);
                break;
            case 'ods':
                $reader = ReaderFactory::create(Type::ODS);
                break;
            case 'xlsx':
                $reader = ReaderFactory::create(Type::XLSX);
                break;
        }

        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            return $sheet;
        }
    }

    /**
     * Erstellt ein \DateTime-Objekt zu einem Datum, das möglicherweise nicht automatisch erkannt wurde.
     *
     * @param \DateTime|string $date das Datum
     * @return \DateTime
     */
    public static function makeDateTimeFromDate($date): \DateTime
    {
        if (is_object($date) && get_class($date) === 'DateTime') {
            return $date;
        } elseif (is_string($date) && preg_match('/^20[0-9][0-9]-[01][0-9]-[0123][0-9]$/', $date)) {
            return new \DateTime($date);
        } elseif (is_string($date) && preg_match('/^[0123][0-9]\.[01][0-9]\.20[0-9][0-9]$/', $date)) {
            return \DateTime::createFromFormat('d.m.Y', $date);
        } else {
            throw new \InvalidArgumentException('$date hat ein ungültiges Format oder einen ungültigen Datentyp.');
        }
    }

    /**
     * Fasst ein Datum und eine Uhrzeit zu einem \DateTime-Objekt zusammen.
     * Nötig, weil je nach Dateiformat Uhrzeiten mal als DateTime und mal als DateInterval geliefert werden.
     *
     * @param \DateTime|string $date das Datum (0:00 Uhr)
     * @param \DateTime|\DateInterval|string $time die Uhrzeit
     * @param \DateTime|null $after mindeste Zeitangabe, falls nicht null. Ansonsten werden 24 Stunden dazu gerechnet.
     * @return \DateTime
     */
    public static function makeDateTimeFromDateAndTime($date, $time, $after = null): \DateTime
    {
        $dateTime = clone self::makeDateTimeFromDate($date);

        // TODO evtl. ein Problem, wenn ausgerechnet an dem Tag die Uhren umgestellt werden.

        if (!is_object($time)) {
            if (!is_string($time) || !preg_match('/^[0-9][0-9]:[0-9][0-9]:[0-9][0-9]$/', $time)) {
                throw new \InvalidArgumentException('$time hat einen falschen Datentyp: ' . gettype($time));
            }
            list($hours, $minutes, $seconds) = explode(':', $time);
            $time = \DateInterval::createFromDateString("$hours hours, $minutes minutes, $seconds seconds");
        }

        if (get_class($time) === 'DateTime') {
            $time = \DateInterval::createFromDateString($time->format('H') . ' hours, ' . $time->format('i') . ' minutes, ' . $time->format('s') . ' seconds');
        }

        if (get_class($time) === 'DateInterval') {
            $dateTime->add($time);
        } else {
            throw new \InvalidArgumentException('$time hat einen unbekannten Datentyp: ' . get_class($time));
        }

        if (is_object($after) && get_class($after) == 'DateTime') {
            $oneDay = \DateInterval::createFromDateString('1 day');
            while ($dateTime < $after) {
                $dateTime->add($oneDay);
            }
        }

        return $dateTime;
    }
}
