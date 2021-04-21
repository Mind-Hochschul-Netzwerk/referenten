<?php
namespace MHN\Referenten;
/**
* Meine PHP-Util Klasse
*
* @author Guido Drechsel <mensa@guido-drechsel.de>
*/
 
class Util {
    const BEITRAGSFORMEN = [
        'w' => 'workshop',
        's' => 'sonstiges',
        'r' => 'rahmenprogramm',
        'v' => 'vortrag',
    ];

    /**
     * Liefert zu den DB-Kürzeln für die Beitragsform den vollständigen Titel zurück
     * @param $var
     *
     * @return string
     */
    public static function getBeitragsformAsText($var) {
        if (!isset(self::BEITRAGSFORMEN[$var])) {
            $var = 'v';
        }
        return ucfirst(self::BEITRAGSFORMEN[$var]);
    }
}
 
?>
