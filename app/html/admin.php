<?php
namespace MHN\Referenten;
/**
* Admin-Panel
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

require_once '../lib/base.inc.php';

Auth::intern();

Tpl::set('htmlTitle', 'Referententool');
Tpl::set('title', 'Referententool');
Tpl::set('navId', 'admin');

if (!Auth::hatRecht('ma-pt')) die("Fehlende Rechte.");

Tpl::set('rechte', DB::query('SELECT m.vorname AS vorname, m.nachname AS nachname, r.recht AS recht, r.uid AS uid FROM rechte r INNER JOIN benutzer m ON m.uid = r.uid ORDER BY r.recht, m.nachname, m.vorname')->get_all());

Tpl::render('admin');

Tpl::submit();
