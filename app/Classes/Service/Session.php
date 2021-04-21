<?php
declare(strict_types=1);

namespace MHN\Referenten\Service;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

/**
 * Verwaltet die Session
 */
class Session implements \MHN\Referenten\Interfaces\Singleton
{
    use \MHN\Referenten\Traits\Singleton;

    /** @var int */
    const TIMEOUT_IN_SECONDS = 12 * 60 * 60;

    /** @var int|null */
    private $inactivityTime = null;

    /**
     * Gibt die Zeit seit der letzten Aktivität zurück
     *
     * @return int
     */
    public function getInactivityTimeInSeconds(): int
    {
        if ($this->inactivityTime !== null) {
            return $this->inactivityTime;
        }

        $this->start();

        if (isset($_SESSION['time'])) {
            $this->inactivityTime = time() - $_SESSION['time'];
        } else {
            $this->inactivityTime = 0;
        }

        $_SESSION['time'] = time();

        return $this->inactivityTime;
    }

    /**
     * Startet eine Session, falls nicht schon eine gestartet wurde.
     *
     * @return void
     */
    public function start()
    {
        if ($this->isActive()) {
            return;
        }

        // Session-Cookie: mit HTTP-Only-Flag und ggf. mit Secure-Flag
        session_set_cookie_params(self::TIMEOUT_IN_SECONDS, '/', '', $this->isConnectionSecure(), true);
        session_start();
    }

    /**
     * Wurde die Session gestartet?
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Wird die Seite über HTTPS aufgerufen? Das ist in der Produktivumgebung immer der Fall, beim Entwickeln nie.
     *
     * @return bool
     */
    public function isConnectionSecure(): bool
    {
        return isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
    }

    /**
     * Weist eine neue Session-ID zu, um die Gefahr eines Session-Highjackings zu verringern.
     *
     * @return void
     */
    public function regenerateId()
    {
        $this->start();
        session_regenerate_id();
    }
}
