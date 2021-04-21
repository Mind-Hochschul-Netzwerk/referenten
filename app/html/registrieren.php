<?php
namespace MHN\Referenten;
/**
* Registrieren
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';
Tpl::set('htmlTitle', 'Registrieren');
Tpl::set('navId', 'registrieren');

ensure($_REQUEST['id'], ENSURE_STRING);
ensure($_REQUEST['a'], ENSURE_STRING);

if (empty($_REQUEST['id']) and (isset($_REQUEST['password']) or isset($_REQUEST['password2']))) {
    Tpl::set('error_username_leer', true);
} else {
    Tpl::set('error_username_leer', false);
}

if ((isset($_REQUEST['password']) || isset($_REQUEST['password2'])) && ($_REQUEST['password'] !== $_REQUEST['password2'])) {
    Tpl::set('error_passwort_ungleich', true);
} else {
    Tpl::set('error_passwort_ungleich', false);
}

if ($_REQUEST['id']) {
    $id = DB::query('SELECT uid FROM benutzer WHERE email="%s"', $_REQUEST['id'])->get();

    if ($id !== null) {
        Tpl::set('error_registriert', true);
    } else {
        $user = Benutzer::neu('');
        $user->setEmail($_REQUEST['id']);
        $user->set('password', $_REQUEST['password']);

        $user->set('kenntnisnahme_informationspflicht_persbez_daten', 'now');
        $user->set('kenntnisnahme_informationspflicht_persbez_daten_text', Tpl::render('Datenschutz/kenntnisnahme-text', false));
        $user->set('einwilligung_persbez_zusatzdaten', 'now');
        $user->set('einwilligung_persbez_zusatzdaten_text', Tpl::render('Datenschutz/einwilligung-text', false));

        $user->save();

        DB::query("INSERT INTO rechte ( uid, recht) VALUES ( %d, 'referent')", $user->get('uid'));

        Auth::logIn($user->get('uid'));

        // zur Startseite. Von dort aus wird ggf. auf passwortwechsel.php oder aktivieren.php weiter geleitet (Auth::intern()).
        Tpl::pause();
        header('Location: /');
        exit;
    }
}
    
Tpl::sendHead();
Tpl::render('registrieren');

Tpl::submit();

?>
