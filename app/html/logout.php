<?php
namespace MHN\Referenten;
/**
* Logout
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';

Tpl::set('htmlTitle', 'Logout');
Tpl::set('title', 'Logout');
Tpl::set('navId', 'logout');

if (Auth::istEingeloggt()) {
    Auth::logOut();
}

Tpl::sendHead();

Tpl::render('logout');

Tpl::submit();

?>
