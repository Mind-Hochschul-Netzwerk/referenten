<?php
namespace MHN\Referenten;
/**
* Login
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';
Tpl::set('htmlTitle', 'Login');
Tpl::set('navId', 'login');

ensure($_REQUEST['uid'], ENSURE_STRING);
ensure($_REQUEST['a'], ENSURE_STRING);

if (!$_REQUEST['uid'] and !empty($_REQUEST['password'])) {
    Tpl::set('error_username_leer', true);
}

if ($_REQUEST['uid']) {
    // mögliche IDs (unter Umständen können das mehrere sein, wenn nämlich eine E-Mail-Adresse von zwei Personen geteilt wird)
    $ids = DB::query('SELECT uid FROM benutzer WHERE uid=%d OR email="%s" OR new_email="%s"', $_REQUEST['uid'], $_REQUEST['uid'], $_REQUEST['uid'])->get_column();

    // keinen Hinweis geben, ob die ID gefunden wurde!

    // Passwort vergessen?
    if (isset($_REQUEST['passwort_vergessen'])) {
        $passwordExpireTime_minutes = ((strtotime(Config::newPasswordExpireTime) - time()) / 60);
        foreach ($ids as $id) {

            $m = Benutzer::lade($id);
            $new_password = Password::randomString(Config::newPasswordLength);
            $m->set('new_password', $new_password);
            $m->save();

            // E-Mail mit dem neuen Passwort schicken (nur an die alte Adresse)
            $m->sendEmail('Neues Passwort',
'Hallo ' . $m->get('fullName') . ",

Du hast ein neues Passwort für deinen Zugang zum MinD-Hochschul-Netzwerk
angefordert.

Es wurde ein neues Passwort festgelegt: $new_password

Bitte melde dich innerhalb der nächsten $passwordExpireTime_minutes Minuten unter
https://referenten." . getenv('DOMAINNAME') . " an und ändere dein Passwort.

Das alte Passwort bleibt bis zum Login mit dem neuen Passwort ebenfalls gültig.
", '', 'email');
        }

        Tpl::set('lost_password', true);
        Tpl::set('passwordExpireTime_minutes', $passwordExpireTime_minutes);
    // Login
    } else if (isset($_REQUEST['password'])) {
        ensure($_REQUEST['password'], ENSURE_SET);
        foreach ($ids as $id) {
            if ($passwordType = Auth::checkPassword($_REQUEST['password'], $id)) {
                $u = Auth::logIn($id);

                // falls dem User sein altes Passwort wieder eingefallen ist, obwohl er ein neues angefordert hatte, wird das neue ungültig
                if ($passwordType == 'password') {
                    $_SESSION['passwortwechsel'] = false;
                    $u->set('new_password', '');
                    $u->save();
                }

                // zur Startseite. Von dort aus wird ggf. auf passwortwechsel.php oder aktivieren.php weiter geleitet (Auth::intern()).
                Tpl::pause();
                header('Location: /');
                exit;
            }
        }
        Tpl::set('error_passwort_falsch', true);#
    }
}

Tpl::sendHead();
Tpl::render('login');

Tpl::submit();

?>
