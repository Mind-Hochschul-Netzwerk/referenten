<?php
declare(strict_types=1);
namespace MHN\Referenten\Service;

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

use PHPMailer\PHPMailer\PHPMailer;

/**
 * send emails
 */
class EmailService implements \MHN\Referenten\Interfaces\Singleton
{
    use \MHN\Referenten\Traits\Singleton;

    private $mailer = null;

    private function __construct()
    {
        if (!getenv('SMTP_HOST') || getenv('SMTP_HOST') === 'log') {
            return;
        }

        $this->mailer = new PHPMailer(true);

        $this->mailer->isSMTP();
        $this->mailer->Host = getenv('SMTP_HOST');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = getenv('SMTP_USER');
        $this->mailer->Password = getenv('SMTP_PASSWORD');
        switch (getenv('SMTP_SECURE')) {
            case "ssl":
            case "smtps":
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            case "tls":
            case "starttls":
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
            default:
                throw new \Exception('unexpected value for SMTP_SECURE');
                break;
        }
        $this->mailer->Port = getenv('SMTP_PORT');
        $this->mailer->setFrom(getenv('FROM_ADDRESS'), 'Mind-Hochschul-Netzwerk');
        $this->mailer->addReplyTo('referentenbetreuung@' . getenv('DOMAINNAME'), 'Referentenbetreuung (Mind-Akademie)');
        $this->mailer->CharSet = 'utf-8';
    }

    public function send(string $address, string $subject, string $body): bool
    {
        if ($this->mailer === null) {
            error_log("
--------------------------------------------------------------------------------
SMTP_HOST is not set in .env
Mail to: $address
Subject: $subject

$body
--------------------------------------------------------------------------------
");
            return true;
        }

        $this->mailer->ClearAddresses();
        $this->mailer->ClearCCs();
        $this->mailer->ClearBCCs();

        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;

        try {
            $this->mailer->addAddress($address);
            return $this->mailer->send();
        } catch (\Exception $e) {
            return false;
        }
    }
}
