<?php
namespace MHN\Referenten;
/**
* Passwortwechsel (bei Benutzeraktivierung oder Passwort vergessen)
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';

Auth::intern('passwortwechsel'); // Benutzer mit Passwortwechselzwang zulassen

// keine anderen zulassen, damit nicht das aktuelle Passwort ohne Eingabe des alten geändert werden kann
if (!$_SESSION['passwortwechsel']) {
    header('Location: /');
    exit;
}

Tpl::set('htmlTitle', 'Passwort festlegen');
Tpl::set('title', 'Neues Passwort festlegen');
Tpl::set('navId', 'start');

ensure($_REQUEST['password'], ENSURE_STRING);
ensure($_REQUEST['password2'], ENSURE_STRING);

$u = Benutzer::lade(Auth::getUID(), true);

if ($_REQUEST['password']) {
    if ($_REQUEST['password'] != $_REQUEST['password2']) {
        Tpl::set('wiederholung_falsch', true);
    } else {
        $u->set('password', $_REQUEST['password']);
        $u->save();
        $_SESSION['passwortwechsel'] = false;
        Tpl::pause();
        header('Location: /');
        exit;
    }
}

Tpl::sendHead();

Tpl::render('passwortwechsel');

Tpl::submit();

?>
