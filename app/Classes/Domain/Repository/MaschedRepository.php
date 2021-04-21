<?php
declare(strict_types=1);
namespace MHN\Referenten\Domain\Repository;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use MHN\Referenten\DB;
use MHN\Referenten\Util;
use MHN\Referenten\Vortrag;
use MHN\Referenten\Benutzer;

/**
 * Verwaltet die MA-Schedule-JSON-Dateien
 */
class MaschedRepository implements \MHN\Referenten\Interfaces\Singleton
{
    use \MHN\Referenten\Traits\Singleton;

    /** @var string %d wird durch das Jahr ersetzt */
    const CACHE_FILENAME = '/tmp/masched_%d.json';

    const TYP_FREI = 'f';
    const TYP_PAUSE = 'p';

    const TYPE_COLORS = [
        's' => '#d6e3bc',
        'v' => '#ec7f62',
        'w' => '#b8cce4',
        'r' => '#e5b8b7',
    ];

    /** @var int[] */
    const PHOTO_SIZES = [200, 400, 600, 800, 1000];

    /**
     * Gibt den Dateinamen des Caches zurück.
     *
     * @param int year
     * @return string
     */
    private function getCacheFilename(int $year): string
    {
        if ($year < 2000 || $year > date('Y') + 1) {
            throw new \OutOfRangeException('ungültiges Jahr: ' . $year, 1505940869);
        }
        return sprintf(self::CACHE_FILENAME, $year);
    }

    /**
     * Löscht den Cache. Muss immer dann aufgerufen werden, wenn sich etwas am Programm ändert.
     *
     * @param int $year Jahr
     * @return void
     */
    public function clearCache(int $year)
    {
        $cacheFilename = self::getCacheFilename($year);
        if (is_file($cacheFilename)) {
            unlink($cacheFilename);
        }
        try {
            $this->sendGlobalSyncSchedule();
        } catch (\RuntimeException $e) {
            error_log('sendGlobalSyncSchedule failed: ' . $e->getMessage());
            die('Die Push-Nachricht an die App konnte nicht gesendet werden: ' . strip_tags($e->getMessage()));
        }
    }

    /**
     * Löst eine Push-Nachricht an alle Clients aus, dass sie sich synchronisieren sollen.
     *
     * @return void
     * @throws \RuntimeException wenn die Kommunikation mit dem Server fehlschlägt.
     */
    public function sendGlobalSyncSchedule()
    {
        $gcmServerUrl = getenv('GCM_SERVER_URL');
        if (empty($gcmServerUrl)) {
            return;
        }

        $payload = json_encode([
            'sync_jitter' => (int)getenv('GCM_SYNC_JITTER'),
        ]);

        $curl = curl_init($gcmServerUrl);
        curl_setopt($curl, CURLOPT_URL, $gcmServerUrl . '/send/global/sync_schedule');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: key=' . getenv('GCM_API_KEY'),
            'Content-Type: application/octet-stream',
        ));

        $response = curl_exec($curl);
        if ($response === false) {
            throw new \RuntimeException('curl error: ' . curl_error($curl), 1506449988);
        }

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status >= 400) {
            throw new \RuntimeException('GCM server replied with status ' . $status . ': ' . $response, 1506450653);
	}

        curl_close($curl);
    }

    /**
     * Gibt die Zeit der letzten Cache-Aktualisierung zurück.
     *
     * @param int $year Jahr
     * @return int 0 falls der Cache leer ist.
     */
    public function getCacheTime(int $year): int
    {
        $cacheFilename = self::getCacheFilename($year);
        return is_file($cacheFilename) ? filemtime($cacheFilename) : 0;
    }

    /**
     * Gibt das Manifest zurück.
     *
     * @param int $year Jahr
     * @return string JSON
     */
    public function getManifest(int $year)
    {
        $cacheFilename = self::getCacheFilename($year);

        if (!is_file($cacheFilename)) {
            self::generateJson($year, $cacheFilename);
        }

        // Manifest erstellen
        return json_encode([
            'format' => 'iosched-json-v1',
            'data_files' => ['ma_' . $year . '_' . self::getCacheTime($year) . '.json'],
        ]);
    }

    /**
     * Gibt den Programmplan zurück.
     *
     * @param int $year Jahr
     * @return string JSON
     */
    public function getSchedule(int $year): string
    {
        $cacheFilename = self::getCacheFilename($year);

        if (!is_file($cacheFilename)) {
            self::generateJson($year, $cacheFilename);
        }

        return file_get_contents($cacheFilename);
    }

    /**
     * Erzeugt das JSON und speichert es in einer Datei.
     *
     * @param int $year Jahr
     * @param string $filename Dateiname
     * @return void
     */
    public function generateJson(int $year, string $filename)
    {
        $blocks = [];
        foreach ($this->getBloecke($year) as $block) {
            $blocks[] = [
                'title' => $block['titel'],
                'type' => array_search($block['typ'], ['free' => self::TYP_FREI, 'break' => self::TYP_PAUSE]),
                'start' => $this->formatTimeForJSON($block['beginn']),
                'end' => $this->formatTimeForJSON($block['ende']),
            ];
        }

        $sessions = [];
        $referenten = [];
        $raeume = [];

        // Das Kennzeichen vom Event wird aktuell noch hart aus dem Jahr gesetzt, bis die API so angepasst wurde, dass sie als Parameter das Eventkennzeichen nutzt.
        $eventKennzeichen = "MA" . $year;

        $vids = DB::query('SELECT vid FROM vortraege WHERE deleted = false AND publish = true AND eid IN (SELECT eid FROM events WHERE kennzeichen="%s") AND programm_beginn IS NOT NULL ORDER BY programm_beginn', $eventKennzeichen)->get_column();

        // Alle Mitglieder laden
        $ergebnisse = [];
        foreach ($vids as $vid) {
            $r = Vortrag::lade($vid);
            $uid = $r->get('uid');

            if (!isset($referenten[$uid])) {
                $referenten[$uid] = Benutzer::lade($uid);
            }
            $benutzer = $referenten[$uid];

            $raum = (string)$r->get('programm_raum');
            $raeume[$this->getRaumId($raum)] = $raum;

            $session = [
                'id' => $year . '_' . $r->get('vid'),
                'description' => $r->get('abstract'),
                'title' => $r->get('vTitel'),
                'tags' => ['TYPE_SESSION', self::getSessionType($r->get('beitragsform'))],
                'mainTag' => self::getSessionType($r->get('beitragsform')),
                'startTimestamp' => $this->formatTimeForJSON($r->get('programm_beginn')),
                'endTimestamp' => $this->formatTimeForJSON($r->get('programm_ende')),
                'speakers' => [$year . '_' . $r->get('uid')],
                'room' => $this->getRaumId($r->get('programm_raum')),
                'color' => self::TYPE_COLORS[$r->get('beitragsform')],
            ];

            $profilbild = $benutzer->get('profilbild');
            if ($profilbild) {
                $version = filemtime('/var/www/html/profilbilder/' . $profilbild);
                $session['photoUrl'] = 'https://referenten.' . getenv('DOMAINNAME') . '/masched/photos/__w-' . implode('-', self::PHOTO_SIZES) . '__/v' . $version . '/' . $profilbild;
            }

            $sessions[] = $session;
        }

        foreach ($this->getRahmenprogramm($year) as $punkt) {
            $raeume[$this->getRaumId($punkt['raum'])] = $punkt['raum'];
            $sessions[] = [
                'id' => $year . '_rahmen_' . $punkt['id'],
                'title' => $punkt['titel'],
                'tags' => ['TYPE_SESSION', self::getSessionType($punkt['beitragsform'])],
                'mainTag' => self::getSessionType($punkt['beitragsform']),
                'startTimestamp' => $this->formatTimeForJSON($punkt['beginn']),
                'endTimestamp' => $this->formatTimeForJSON($punkt['ende']),
                'speakers' => [],
                'room' => $this->getRaumId($punkt['raum']),
                'color' => self::TYPE_COLORS[$punkt['beitragsform']],
            ];
        }

        $speakers = [];
        foreach ($referenten as $id=>$benutzer) {
            $speakers[] = [
                'id' => $year . '_' . $id,
                'name' => $benutzer->get('fullName'),
                'bio' => $benutzer->get('kurzvita'),
            ];
        }

        $rooms = [];
        foreach ($raeume as $id => $name) {
            $rooms[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        $masched = [
            'rooms' => $rooms,
            'blocks' => $blocks,
            'sessions' => $sessions,
            'speakers' => $speakers,
            'tags' => $this->getTags(),
        ];

        // Programm speichern
        $fp = fopen($filename, 'w');
        fwrite($fp, json_encode($masched));
        fclose($fp);
    }

    /**
     * Sendet ein Profilbild in der angeforderten Größe
     *
     * @param string $filename
     * @param int $size Breite in Pixeln
     * @return void
     */
    public function sendPhoto(string $filename, int $size)
    {
        require_once '/var/www/lib/resizeImage.inc.php';

        $origin = '/var/www/html/profilbilder/' . $filename;
        $target = '/tmp/w' . $size . '-' . $filename;

        if (!is_file($origin) || !in_array($size, self::PHOTO_SIZES, true)) {
            http_response_code(404);
            exit;
        }

        $pos = strrpos($filename, '.');
        $type = $pos ? substr($filename, $pos + 1) : 'unknown';

        header('Content-Type: image/jpeg');

        if (!is_file($target)) {
            resizeImage($origin, $target, $type, 'jpeg', $size, $size);
        }

        readfile($target);
    }

    /**
     * Gibt die Blöcke zurück.
     *
     * @param int $year Jahr
     * @return array
     */
    public function getBloecke(int $year)
    {
        $bloecke = [];

        $result = DB::query('SELECT titel, typ, beginn, ende FROM bloecke WHERE jahr=%d ORDER BY beginn', $year);
        while ($block = $result->get_row()) {
            $bloecke[] = [
                'titel' => $block['titel'],
                'typ' => $block['typ'],
                'beginn' => new \DateTime($block['beginn']),
                'ende' => new \DateTime($block['ende']),
            ];
        }

        return $bloecke;
    }

    /**
     * Gibt das Rahmenprogramm zurück.
     *
     * @param int $year Jahr
     * @return array
     */
    public function getRahmenprogramm(int $year)
    {
        $rahmenprogramm = [];

        $result = DB::query('SELECT id, titel, beitragsform, raum, beginn, ende FROM rahmenprogramm WHERE jahr=%d ORDER BY beginn', $year);
        while ($punkt = $result->get_row()) {
            $rahmenprogramm[] = [
                'id' => $punkt['id'],
                'titel' => $punkt['titel'],
                'raumId' => self::getRaumId($punkt['raum']),
                'raum' => $punkt['raum'],
                'beitragsform' => $punkt['beitragsform'],
                'beitragsformText' => Util::getBeitragsformAsText($punkt['beitragsform']),
                'beginn' => new \DateTime($punkt['beginn']),
                'ende' => new \DateTime($punkt['ende']),
            ];
        }

        return $rahmenprogramm;
    }

    /**
     * Wandelt einen Raum-Namen in eine Raum-ID um.
     *
     * @param string $raum
     * @return string
     */
    private function getRaumId(string $raum)
    {
        return preg_replace('/[^0-9A-Z_-]/', '_', strtoupper(trim($raum)));
    }

    /**
     * Formatiert eine Zeit für das JSON-Ouput
     *
     * @param \DateTime $time
     * @return string
     */
    private function formatTimeForJSON(\DateTime $time): string
    {
        $timeCopy = clone $time;
        $timeCopy->setTimeZone(new \DateTimeZone('UTC'));
        return $timeCopy->format('Y-m-d') . 'T' . $timeCopy->format('H:i:s') . 'Z';
    }

    /**
     * Gibt den Typ der Session zurück
     *
     * @param string $type Beitragsform (ein Zeichen)
     * @return string
     */
    private function getSessionType(string $type) {
        return 'TYPE_' . strtoupper(Util::getBeitragsformAsText($type));
    }

    /**
     * Gibt den Abschnitt "tags" für die Schedule-Datei zurück.
     *
     * @return array
     */
    private function getTags()
    {
        $tags = [];
        foreach (['v', 'w', 's', 'r'] as $short) {
            $name =
            $tags[] = [
                'category' => 'TYPE',
                'tag' => $this->getSessionType($short),
                'name' => Util::getBeitragsformAsText($short),
                'abstract' => '',
                'order_in_category' => 10,
                'color' => self::TYPE_COLORS[$short],
            ];
        }
        return $tags;
    }
}
