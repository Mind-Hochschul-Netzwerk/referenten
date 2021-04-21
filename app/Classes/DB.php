<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use MHN\Referenten\Config;

DB::init(Config::$mysqlHost, Config::$mysqlUser, Config::$mysqlPassword, Config::$mysqlDatabase);

/**
* Datenbankschnittstelle
*/
class DB
{
    private static $mysqli = null;

    /**
     * Initialisierung
     *
     * @throws \LogicException wenn die Verbindung schon hergestellt wurde
     * @throws \RuntimeException wenn die Verbindung fehlschlägt
     * @return void
     */
    public static function init(string $host, string $user, string $password, string $database)
    {
        if (self::$mysqli) {
            throw new \LogicException('Datenbank ist bereits initialisiert', 1493681955);
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            self::$mysqli = new \mysqli($host, $user, $password, $database);
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Keine Verbindung zur Datenbank: ' . $e->getMessage(), 1493681965);
        }

        self::$mysqli->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
    }

    /**
     * Lädt die Datei $file und führt die SQL-Queries darin aus.
     *
     * @return void
     * @throws \RuntimeException wenn ein Query fehlschlägt.
     */
    public static function read(string $file)
    {
        $queries = file_get_contents($file);
        try {
            $result = self::$mysqli->multi_query($queries);
            while (self::$mysqli->more_results()) {
                self::$mysqli->next_result();
                $result = self::$mysqli->use_result();
                if ($result !== false) {
                    $result->close();
                }
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Datenbank-Fehler: ' . $e->getMessage() . ' (Query: ' . $queries . ')', 1494792465);
        }
    }

    /**
     * Sendet einen Query an die Datenbank.
     * wird u.a. von query() aufgerufen
     *
     * @return DB_Result|true
     * @throws \RuntimeException wenn der Query fehlschlägt.
     */
    public static function actualQuery(string $query)
    {
        try {
            $result = self::$mysqli->query($query);
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Datenbank-Fehler: ' . $e->getMessage() . ' (Query: ' . $query . ')', 1493682314);
        }

        if (is_object($result)) {
            return new DB_Result($result);
        } else {
            return true;
        }
    }

    /**
     * Sendet einen Query an MySQL.
     *
     * @param string $query. Kann Platzhalter für die Parameter enthalten (sprintf-Syntax)
     * @param mixed ...$params weitere optionale Parameter, die in $query eingefügt werden und escaped werden
     * @return DB_Result|true
     * @throws \RuntimeException wenn der Query fehlschlägt
     */
    public static function query(string $query, ...$params)
    {
        if (count($params)) {
            if (is_array($params[0])) {
                // nötig, wenn query von einer der DB::get-Funktionen aufgerufen wird
                $params = $params[0];
            }

            array_walk($params, function (&$var) {
                if (is_string($var)) {
                    $var = self::_($var);
                }
            });

            $sqlquery = vsprintf($query, $params);
        } else {
            $sqlquery = $query;
        }

        return self::actualQuery($sqlquery);
    }

    /**
     * ID des zuletzt eingefügten Datensatzes
     */
    public static function insert_id()
    {
        return self::$mysqli->insert_id;
    }

    /**
     * Anzahl der vom letzten Query (UPDATE) betroffenen Zeilen
     */
    public static function affected_rows()
    {
        return self::$mysqli->affected_rows;
    }

    /**
     * escape (alias)
     */
    public static function _(string $string) : string
    {
        return self::$mysqli->real_escape_string($string);
    }
}
