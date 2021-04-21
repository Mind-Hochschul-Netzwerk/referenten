<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
 * Startseite
 *
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use MHN\Referenten\Auth;
use MHN\Referenten\Tpl;

require_once '../lib/base.inc.php';

Auth::intern();

Tpl::set('navId', 'home');

Tpl::sendHead();

Tpl::render('Home/home');

Tpl::submit();
