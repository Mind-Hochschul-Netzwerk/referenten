<?php
namespace MHN\Referenten\Traits;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

/**
 * Trait für Singletons
 */
trait Singleton
{
    /** @var \MHN\Interfaces\Singleton|null */
    private static $instance = null;

    /**
     * Gibt die Instanz der Klasse zurück
     *
     * @return \MHN\Interfaces\Singleton
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Kopieren und Instanziieren von Extern verbieten
     */
    private function __clone()
    {
    }

    /**
     * Kopieren und Instanziieren von Extern verbieten
     */
    private function __construct()
    {
    }
}
