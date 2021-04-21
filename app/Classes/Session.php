<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

/**
 * intialisiert die Session
 */
class Session
{
    public static function start()
    {
        ini_set('session.use_only_cookies', '1');
        session_name('session');
        session_start();
    }

    public static function destroy()
    {
        session_destroy();
    }
}
