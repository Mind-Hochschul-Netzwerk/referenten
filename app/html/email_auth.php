<?php
namespace MHN\Referenten;
/**
* E-Mail-Token verifizieren
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';

Auth::intern();

Tpl::set('htmlTitle', 'E-Mail-Verifikation');
Tpl::set('title', 'E-Mail-Verifikation');
Tpl::set('navId', 'bearbeiten');
Tpl::sendHead();

$m = Benutzer::lade(Auth::getUID());

// ein neuer Token wurde angefordert
if (isset($_REQUEST['new_token'])) {
    $m->initEmailAuth(false);
    $m->save();
    die("Dir wurde eine neue Bestätigungsmail für die Änderung deiner E-Mail-Adresse geschickt. Bitte klicke innerhalb von " . ((strtotime($m->get('new_email_token_expire_time')) - time()) / 3600) . " Stunden auf den Link.");
}

ensure($_REQUEST['token'], ENSURE_STRING) or die('Es wurde kein Token übergeben.');

if (!$m->get('new_email_token')) {
    die("Deine E-Mail-Adresse wurde bereits verifiziert.");
}

if ($m->get('new_email_token') != $_REQUEST['token']) {
    die("Der Token ist falsch. Hast du zwischenzeitlich einen neuen angefordert?");
}

if (!$m->get('new_email')) {
    die("Der Token ist abgelaufen. Deine E-Mail-Adresse wurde auf die alte zurückgesetzt.");
}

// Alles klar
$m->finishEmailAuth();
$m->save();

echo 'Deine E-Mail-Adresse wurde erfolgreich zu ' . $m->get('email') . ' geändert.';

Tpl::submit();

?>
