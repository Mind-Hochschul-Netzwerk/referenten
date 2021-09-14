<?php
namespace MHN\Referenten;

use MHN\Referenten\Vortrag;
use MHN\Referenten\Benutzer;

require_once '../lib/base.inc.php';

Auth::intern();

if (!Auth::hatRecht('ma-pt')) die("Fehlende Rechte.");

$ids = explode(",",  $_GET['var']);

$files = [];
$entryNames = [];

foreach ($ids as $id) {
    $v = Vortrag::lade($id);
    if ($v === null) {
        continue;
    }

    $benutzerId = $v->get('uid');
    if (isset($files[$benutzerId])) {
        continue;
    }

    $benutzer = Benutzer::lade($benutzerId);

    $vorname = preg_replace('/[^a-zA-Z-., ]/', '_', $benutzer->get('vorname'));
    $nachname = preg_replace('/[^a-zA-Z-., ]/', '_', $benutzer->get('nachname'));
    $publish = $benutzer->get('publish');

    $profilbild = $benutzer->get('profilbild');

    if ($profilbild and is_file("profilbilder/$profilbild")) {
        $files[$benutzerId] = $profilbild;
        $ext = pathinfo($profilbild, PATHINFO_EXTENSION);
        $noPublish = $publish ? '' : ", NOCH NICHT FREIGEGEBEN";
        $entryNames[$benutzerId] = "$nachname, $vorname (ID $benutzerId$noPublish).$ext";
    }
}

if (!$files) {
    echo "Zu den ausgewählten Vorträgen gibt es noch keine Profilbilder.";
    Tpl::submit();
    exit;
}

$zip = new \ZipArchive();
$zipFileName = tempnam(sys_get_temp_dir(), 'profilbilder.zip');
$zip->open($zipFileName, \ZipArchive::CREATE);
foreach ($files as $benutzerId=>$fileName) {
    $zip->addFile('profilbilder/' . $fileName, $entryNames[$benutzerId]);
}
$zip->close();

ob_end_clean();
header("Content-Type: application/zip, application/octet-stream");
header("Content-Disposition: attachment; filename=\"profilbilder.zip\"");
header("Content-Length: " . filesize($zipFileName));

readfile($zipFileName);
unlink($zipFileName);
