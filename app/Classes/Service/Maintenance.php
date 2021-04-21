<?php
declare(strict_types=1);

namespace MHN\Referenten\Service;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use MHN\Referenten\DB;
use MHN\Referenten\Tpl;

/**
 * regelmäßige Wartungsarbeiten.
 *
 * Die Wartungsskript liegen in lib/maintenance.d und haben das Format "$sort.$name.$interval.(php|sql)"
 */
class Maintenance implements \MHN\Referenten\Interfaces\Singleton
{
    use \MHN\Referenten\Traits\Singleton;

    /** @var string */
    const dir = __DIR__ . '/../../lib/maintenance.d';

    /** @var string */
    const lockfile = '/tmp/maintenance.lock';

    /** @var int */
    private $lastMaintenanceTime = 0;

    /**
     * Leitet die Wartung ein.
     */
    public function run()
    {
        if (file_exists(self::lockfile)) {
            $this->lastMaintenanceTime = filemtime(self::lockfile);
        }
        // maximal alle 60 Sekunden
        if ($this->lastMaintenanceTime + 60 >= time()) {
            return;
        }
        $this->performUpdates();
    }

    /**
     * Ruft die Wartungsskripts auf.
     *
     * @throws \LogicException wenn eine Datei einen ungültien Typ hat.
     * @throws \RuntimeException bei Datenbank-Fehlern
     */
    private function performUpdates()
    {
        $dir = dir(self::dir);

        $files = [];
        while ($file = $dir->read()) {
            if ($file{0} === '.') {
                continue;
            }

            $files[] = $file;
        }

        natsort($files);

        foreach ($files as $file) {
            $type = pathinfo($file, PATHINFO_EXTENSION);

            // Feststellen, ob der jeweilige Intervall schon wieder abgelaufen ist.
            if ($type === 'sql' || $type === 'php') {
                $parts = explode('.', pathinfo($file, PATHINFO_FILENAME));
                $interval = $parts[count($parts) - 1];
                if ($this->lastMaintenanceTime + $interval >= time()) {
                    continue;
                }
            }

            switch ($type) {
                case 'sql':
                    DB::read(self::dir . '/' . $file);
                    break;
                case 'php':
                    include_once self::dir . '/' . $file;
                    break;
                case 'md':
                    // evtl. eine README.md
                    break;
                default:
                    throw new \LogicException("Unbekannter Dateityp in maintenance.d: $file", 1518357299);
                    exit;
            }
        }
    }
}

