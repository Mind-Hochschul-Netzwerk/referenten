<?php
declare(strict_types=1);
namespace MHN\Referenten;

/**
* Konfiguration
* Lädt teilweise Umgebungsvariablen
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

class Config {
    // PBKDF2-Zutaten für das Passwort-Hashing (passwort.inc.php)
    const passwordSaltSize = 16;
    const passwordAlgo = 'sha256';
    const passwordIterations = 10000;
    const passwordLength = 128;
    
    const newEmailTokenExpireTime = '+24 hours'; // Zeit, in der eine neue E-Mail-Adresse aktiviert werden muss. Format für strtodate.
    const newPasswordLength = 12; // Anzahl der Zeichen für ein automatisch generiertes Passwort
    const newPasswordExpireTime = '+30 minutes'; // Zeit, in der eine Einmal-Passwort verwendet werden muss. Format für strtodate.
    
    const rootURL = 'http://referenten.mind-hochschul-netzwerk.de/'; // mit / am Ende
    const emailFrom = 'Mein MHN <noreply@referenten.mind-hochschul-netzwerk.de>'; // Absendeadresse von E-Mails
    
    // Maximale Größe von Profilbildern
    const profilbildMaxWidth  = 1000000;
    const profilbildMaxHeight = 1000000;
    const thumbnailMaxWidth  = 300;
    const thumbnailMaxHeight = 300;
    
    // MySQL-Zugangsdaten (werden als Environment-Variablen übergeben, s.u.)
    public static $mysqlHost;
    public static $mysqlUser;
    public static $mysqlPassword;
    public static $mysqlDatabase;
    
    // OpenID-Connect-Daten (werden als Environment-Variablen übergeben, s.u.)
    public static $openIDURL;
    public static $openIDRedirectURL;
    public static $openIDConnectClientID;
    public static $openIDConnectClientSecret;
}

Config::$mysqlHost      = getenv('MYSQL_HOST');
Config::$mysqlUser      = getenv('MYSQL_USER');
Config::$mysqlPassword  = getenv('MYSQL_PASSWORD');
Config::$mysqlDatabase  = getenv('MYSQL_DATABASE');

setlocale(LC_TIME, 'german', 'deu_deu', 'deu', 'de_DE', 'de');  

?>
