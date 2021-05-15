<?php
namespace MHN\Referenten;
/**
* Rechteverwaltung, Login und Logout
*
* Namen der Rechte:
 * - rechte: Kann Rechte ändern. Impliziert alle Rechte!
 * - referent: Referent. Kann seine Personendaten und die Daten zu seinem Vortrag anpassen.
 * - ma-pt: Mind-Akademie Programmteam. Kann alle Referentendaten und deren Vorträge lesen und ändern.
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

Auth::init();

class Auth {
    public static function init() {
        if (!isset($_SESSION['uid'])) {
            $_SESSION['uid'] = null;
            $_SESSION['passwortwechsel'] = null;
        }
    }

    public static function istEingeloggt() {
        return (isset($_SESSION['uid']) && $_SESSION['uid'] !== null);
    }

    /**
    * Prüft, ob ein Mitglied interne Seiten sehen darf und leitet sonst zu den entsprechenden Formularen
    */
    public static function intern($context = 'intern') {
        if (!self::istEingeloggt()) {
            Tpl::pause();
            header("Location: /login.php");
            exit;
        } else if ($_SESSION['passwortwechsel']) {
            if ($context === 'passwortwechsel') return;
            Tpl::pause();
            header("Location: /passwortwechsel.php");
            exit;
        } else {
            if ($context === 'datenverarbeitung') return;

            $b = Benutzer::lade(Auth::getUID());
            if ($b !== null && ($b->get('kenntnisnahme_informationspflicht_persbez_daten') === null || $b->get('einwilligung_persbez_zusatzdaten') === null)) {
                Tpl::pause();
                header("Location: /datenverarbeitung.php");
                exit;
            }
        }
    }

    /**
    * Loggt den User $uid ein
    */
    public static function logIn($uid) {
        $u = Benutzer::lade($uid, true);

        assert(!is_null($u));

        $_SESSION['uid'] = $uid;

        $u->set('last_login', 'now');

        if ($u->get('new_password')) { // falls ein Einmalpasswort gesetzt ist
            $_SESSION['passwortwechsel'] = true;
        } else {
            $_SESSION['passwortwechsel'] = false;
        }

        $u->save();

        return $u;
    }

    /**
    * Loggt den User aus
    */
    public static function logOut() {
        if (!self::istEingeloggt()) {
            return;
        }

        Session::destroy();
        Session::start();
        self::init();
        Tpl::pause();
    }

    /**
    * Prüft, ob der User oder ein anderes Mitglied ein Recht hat
    */
    public static function hatRecht($recht, $uid = null) {
        if ($uid === null) {
            // nicht eingloggte Benutzer haben keine Rechte
            if (!self::istEingeloggt()) {
                return false;
            }

            $uid = self::getUID();
        }

        // Rechtverwaltung impliziert alle Rechte
        if ($recht != 'rechte' and self::hatRecht('rechte', $uid)) {
            return true;
        }

        return DB::query('SELECT uid FROM rechte WHERE uid=%d AND recht="%s"', $uid, $recht)->count() > 0;
    }

    /**
    * die ID des Users
    */
    public static function getUID(): int {
        return (int)$_SESSION['uid'];
    }

    /**
    * prüft, ob der User eine bestimmte ID hat
    */
    public static function ist($uid) {
        return self::getUID() == $uid;
    }

    /**
     * prüft, ob der User eine der übergebenen IDs hat
     *
     * @param array of userIDs
     */
    public static function istIn($uids) {
        return in_array(self::getUID(), $uids);
    }

    /**
    * überprüft ein Passwort
    * Gibt zurück, ob es sich bei dem Passwort um das normale Passwort oder um ein Einmalpasswort handelt
    */
    public static function checkPassword($password, $uid = null) {
        if ($uid === null) $uid = self::getUID();

        $hash = DB::query('SELECT password FROM benutzer WHERE uid=%d', $uid)->get();
        $newHash = DB::query('SELECT new_password FROM benutzer WHERE new_password_expire_time > NOW() AND uid=%d', $uid)->get();

        if (Password::check($hash, $password, $uid)) {
            if ($newHash == 'new_password') return 'new_password'; // d.h. das Passwort wurde zum Ändern vorgemerkt (-> neuer Benutzer)
            return 'password';
        }
        if ($newHash !== null && Password::check($newHash, $password, $uid)) {
            return 'new_password';
        }
        return false;
    }
}

?>
