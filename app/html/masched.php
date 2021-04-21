<?php
namespace MHN\Referenten;
/**
 * Verwaltung der MA-Schedule-Dateien
 *
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

require_once '../lib/base.inc.php';

use MHN\Referenten\Controller\MaschedController;

Auth::intern();

Tpl::set('htmlTitle', 'Programm-Import');
Tpl::set('title', 'Programm-Import');
Tpl::set('navId', 'admin');

if (!Auth::hatRecht('ma-pt')) die("Fehlende Rechte.");

MaschedController::getInstance()->run();

