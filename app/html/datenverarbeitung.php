<?php
declare(strict_types=1);

namespace MHN\Referenten;

/**
 * Kenntnisnahme zur Datenverarbeitung
 *
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use MHN\Referenten\Auth;
use MHN\Referenten\DB;
use MHN\Referenten\Benutzer;
use MHN\Referenten\Tpl;

require_once '../lib/base.inc.php';

Auth::intern('datenverarbeitung');

Tpl::set('title', 'Verarbeitung personenbezogener Daten');
Tpl::set('htmlTitle', 'Verarbeitung personenbezogener Daten');
Tpl::set('navId', 'datenverarbeitung');
Tpl::sendHead();

ensure($_REQUEST['id'], ENSURE_INT_GT, 0, Auth::getUID());
ensure($_REQUEST['id'], ENSURE_INT_GT, 0, Auth::getUID());

$b = laden($_REQUEST['id']);

if (!Auth::ist($b->get('id'))) {
    die('Fehlende Berechtigung');
}

// als Funktion, weil es zweimal hier gebraucht wird
function laden(int $uid)
{
    $b = Benutzer::lade($uid);

    if ($b === null) {
        die('ID ungültig');
    }

    return $b;
}

// wenn irgendein Feld (z.B. E-Mail) gesendet wurde, soll gespeichert werden
if (isset($_REQUEST['submit'])) {
    if (!empty($_REQUEST['kenntnisnahme_informationspflicht_persbez_daten'])) {
        $b->set('kenntnisnahme_informationspflicht_persbez_daten', 'now');
        $b->set('kenntnisnahme_informationspflicht_persbez_daten_text', Tpl::render('Datenschutz/kenntnisnahme-text', false));
        $b->save();
        $b = laden($b->get('id'));
    }
    if (!empty($_REQUEST['einwilligung_persbez_zusatzdaten'])) {
        $b->set('einwilligung_persbez_zusatzdaten', 'now');
        $b->set('einwilligung_persbez_zusatzdaten_text', Tpl::render('Datenschutz/einwilligung-text', false));
        $b->save();
        $b = laden($b->get('id'));
    }
}

Tpl::set('kenntnisnahme_informationspflicht_persbez_daten', $b->get('kenntnisnahme_informationspflicht_persbez_daten'));
Tpl::set('kenntnisnahme_informationspflicht_persbez_daten_text', $b->get('kenntnisnahme_informationspflicht_persbez_daten_text'));
Tpl::set('einwilligung_persbez_zusatzdaten', $b->get('einwilligung_persbez_zusatzdaten'));
Tpl::set('einwilligung_persbez_zusatzdaten_text', $b->get('einwilligung_persbez_zusatzdaten_text'));

Tpl::render('Datenschutz/form');

Tpl::submit();
