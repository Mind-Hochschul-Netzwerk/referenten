<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

// Für Frontend-Skripte: Buffering aktivieren
if (!defined('no_output_buffering')) {
    ob_start();

    register_shutdown_function(function () {
        Tpl::onShutdown();
    });
}

/**
 * Output-Buffering und Template-Engine
 */
class Tpl
{
    private static $vars = ['htmlHead' => '', 'htmlBody' => '', 'htmlFoot' => ''];
    private static $headSent = false;
    private static $bodySent = false;

    public static $bodyTmp = '';
    public static $disableOnShutdown = false;

    /**
     * Setzt eine Variable*
     *
     * @param string $var
     * @param mixed $val
     * @param bool $escape HTML-Kontrollzeichen ersetzen (bei arrays auch rekursiv)
     * @throws \InvalidArgumentException Wenn $val ein Objekt enthält und $escape==true ist
     */
    public static function set(string $var, $val, $escape = true)
    {
        if ($escape) {
            if (is_array($val)) {
                array_walk_recursive($val, function (&$v) {
                    self::escape($v);
                });
            } else {
                $val = self::escape($val);
            }
        }
        self::$vars[$var] = $val;
    }

    /**
     * HTML-Kontrollzeichen ersetzen
     *
     * @param mixed $value
     * @return mixed
     */
    private static function escape($value) {
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        if (is_object($value)) {
            if (get_class($value) == 'DateTime') {
                $value = clone $value;
            } else {
                throw new \InvalidArgumentException('Nicht unterstütztes Objekt gesetzt: ' . get_class($value), 1493681395);
            }
        }
        if (is_array($value)) {
            throw new \InvalidArgumentException('Ungültiger Datentyp (array)', 1506035012);
        }
        return $value;
    }

    public static function push($var, $val)
    {
        if (!isset(self::$vars[$var])) {
            self::$vars[$var] = [];
        }
        self::$vars[$var][] = $val;
    }

    /**
     * Fügt einen String zum HTML-Head hinzu
     * @param string $text
     */
    public static function headPut($text)
    {
        self::$vars['htmlHead'] .= $text;
    }

    /**
     * Zwischen den Aufrufen von headStart() und headEnd() werden Ausgaben in den Head geschrieben (z.B. CSS)
     */
    public static function headStart()
    {
        ob_start();
    }

    public static function headEnd()
    {
        self::headPut(ob_get_contents());
        ob_end_clean();
    }

    /**
     * Fügt einen String zum HTML-Foot hinzu
     * @param string $text
     */
    public static function footPut($text)
    {
        self::$vars['htmlFoot'] .= $text;
    }

    /**
     * Zwischen den Aufrufen von footStart() und footEnd() werden Ausgaben ans Ende des Footers gehängt (z.B. Javascript)
     */
    public static function footStart()
    {
        ob_start();
    }

    public static function footEnd()
    {
        self::footPut(ob_get_contents());
        ob_end_clean();
    }

    /**
     * Sendet den HTML-Head.
     * Sollte möglichst früh ausgelöst werden, damit der Client schonmal das CSS laden kann.
     * wird ggf. automatisch von submit ausgelöst
     */
    public static function sendHead()
    {
        if (self::$headSent) {
            return;
        }

        // Ausgabe des Bodys unterbrechen
        $body = ob_get_contents();
        ob_end_clean();

        // Head senden
        self::render('Layout/htmlHead');
        flush();
        self::$headSent = true;

        // Ausgabe des Bodys fortsetzen
        ob_start();
        echo $body;
    }

    /**
     * Sendet den HTML-Fuß.
     */
    public static function sendFoot()
    {
        self::render('Layout/htmlFoot');
    }

    /**
     * Stellt die komplette Seite im Standard-Layout dar
     * $layout ist das Layout-Template
     * @param string $layout
     */
    public static function submit($layout = 'Layout/layout')
    {
        if (self::$bodySent) {
            return;
        }
        self::sendHead();
        self::$vars['htmlBody'] = ob_get_contents();
        ob_end_clean();
        self::render($layout);
        self::sendFoot();
        self::$bodySent = true;
    }

    /**
     * Stellt ein Template dar.
     *
     * @param string $tpl
     * @param bool $display ausgeben und nicht nur zurückgegeben
     * @throws \UnexpectedValueException wenn das Template nicht existiert
     * @return string gerenderter Inhalt
     */
    public static function render($tpl, $display = true)
    {
        // Template darstellen
        extract(self::$vars);
        if (!is_file("/var/www/Resources/Private/Templates/$tpl.tpl.php")) {
            throw new \UnexpectedValueException("Template $tpl existiert nicht.", 1493681481);
        }

        ob_start();
        include "/var/www/Resources/Private/Templates/$tpl.tpl.php";
        $contents = ob_get_contents();
        ob_end_clean();

        if ($display) {
            echo $contents;
        }

        return $contents;
    }

    /**
     * Spätestens am Ende der Skriptausführung muss die Seite gerendert werden.
     */
    public static function onShutdown()
    {
        if (!self::$disableOnShutdown) {
            self::submit();
        }
    }

    /**
     * Output-Buffering unterbrechen
     */
    public static function pause()
    {
        self::$bodyTmp = ob_get_contents();
        self::$disableOnShutdown = true;
        ob_end_clean();
    }

    /**
     * Output-Buffering wieder aufnehmen
     */
    public static function resume()
    {
        ob_start();
        echo self::$bodyTmp;
        self::$disableOnShutdown = false;
    }
}
