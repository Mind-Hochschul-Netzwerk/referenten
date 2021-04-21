<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

/**
* Datenbank-Result
*/
class DB_Result
{
    private $res;

    public function __construct(\mysqli_result $result)
    {
        $this->res = $result;
    }

    /**
     * gibt einen einzelnen Wert zurück
     */
    public function get()
    {
        if (!$this->count()) {
            return null;
        }

        // erster Eintrag (muss nicht Index 0 haben)
        foreach ($this->get_row() as $cell) {
            break;
        }

        return $cell;
    }

    /**
     * gibt die (erste) Spalte im Ergebnis zurück
     */
    public function get_column() : array
    {
        if (!$this->count()) {
            return [];
        }
        $data = [];

        while ($row = $this->get_row()) {
            // erste Spalte (muss nicht Index 0 haben)
            foreach ($row as $col) {
                break;
            }
            $data[] = $col;
        }

        return $data;
    }

    /**
     * Gibt den nächsten Datensatz als assoziatives Array zurück
     *
     * @return array|null null, falls es keinen weiteren Datensatz mehr gibt
     */
    public function get_row()
    {
        return $this->res->fetch_assoc();
    }

    /**
     * gibt ein Array aus assoziativen Arrays für allen gefundenen Datensätzen zurück
     */
    public function get_all() : array
    {
        $rows = [];
        while ($row = $this->get_row()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Anzahl der Datensätze
     */
    public function count() : int
    {
        return $this->res->num_rows;
    }

    public function __destruct()
    {
        return $this->res->free();
    }
}
