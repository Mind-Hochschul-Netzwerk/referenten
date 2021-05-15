<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use MHN\Referenten\Config;

/**
* Passwort-Hashing und Token-Generierung
*/
class Password
{
    const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';

    /**
     * Prüft, ob $hash zu $plaintext passt
     *
     * @param string $hash
     * @param string $plaintext
     * @param int|null $uid User-Id
     * @throws \UnexepectedValueException wenn $uid===null ist, aber zum Hashen benötigt wird
     * @throws \UnexepectedValueException wenn $hash einen unbekannten Hash-Type enthält
     */
    public static function check(string $hash, string $plaintext, $uid = null) : bool
    {
        if (!$hash) {
            return false;
        }

        if ($hash{0} === ':') {
            $elements = explode(':', $hash);
            $type = $elements[1];
        } else {
            if ($uid === null) {
                throw new \UnexpectedValueException('$uid===null, aber alter Hash mit gehashter User-ID getestet', 1493681735);
            }
            return $hash === md5("$uid-" . md5($plaintext));
        }

        switch ($type) {
        case 'A':
            return md5($plaintext) === $elements[2];
        case 'B':
            $salt = $elements[2];
            $realhash = $elements[3];
            return md5("$salt-" . md5($plaintext)) === $realhash;
        case 'pbkdf2':
            $algo = $elements[2];
            $iterations = (int)$elements[3];
            $length = (int)$elements[4];
            $salt = base64_decode($elements[5], true);
            $realhash = $elements[6];
            return base64_encode(hash_pbkdf2($algo, $plaintext, $salt, $iterations, $length, true)) === $realhash;
        default:
            throw new \UnexpectedValueException('Der Passwort-Typ in der Datenbank ist unbekannt', 1493681740);
        }
    }

    public static function hash(string $plaintext) : string
    {
        $salt = self::randomString(Config::passwordSaltSize);
        $hash = hash_pbkdf2(Config::passwordAlgo, $plaintext, $salt, Config::passwordIterations, Config::passwordLength, true);
        return implode(':', ['', 'pbkdf2', Config::passwordAlgo, Config::passwordIterations, Config::passwordLength, base64_encode($salt), base64_encode($hash)]);
    }

        /**
     * gibt einen zufälligen String zurück. Zeichensatz: a-z A-Z 0-9
     */
    public static function randomString(int $length) : string
    {
        $string = '';
        do {
            $string .= self::CHARS[rand(0, strlen(self::CHARS) - 1)];
        } while (strlen($string) < $length);
        return $string;
    }
}
