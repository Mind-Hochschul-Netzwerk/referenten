SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `benutzer` (
  `uid` int(5) UNSIGNED NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `titel` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `vorname` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `nachname` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `geschlecht` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'u',
  `registrierungsdatum` date DEFAULT NULL,
  `profilbild` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `profilbild_x` int(5) UNSIGNED DEFAULT NULL,
  `profilbild_y` int(5) UNSIGNED DEFAULT NULL,
  `mensa_nr` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mhn_mitglied` tinyint(1) NOT NULL DEFAULT '0',
  `telefon` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mobil` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `kurzvita` text COLLATE utf8_unicode_ci NOT NULL,
  `affiliation` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `new_email` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `new_email_token` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `new_email_token_expire_time` datetime DEFAULT NULL,
  `db_modified` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `new_password` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `new_password_expire_time` datetime DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `aufnahmen` tinyint(1) NOT NULL DEFAULT '0',
  `datenschutz_bereinigt` tinyint(1) NOT NULL DEFAULT '0',
  `datenschutz_bereinigung_termin` DATE,
  `kenntnisnahme_informationspflicht_persbez_daten` datetime DEFAULT NULL,
  `kenntnisnahme_informationspflicht_persbez_daten_text` text NOT NULL DEFAULT '',
  `einwilligung_persbez_zusatzdaten` datetime DEFAULT NULL,
  `einwilligung_persbez_zusatzdaten_text` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PACK_KEYS=0;

INSERT INTO `benutzer` (`uid`, `email`, `password`, `titel`, `vorname`, `nachname`, `geschlecht`, `registrierungsdatum`, `profilbild`, `profilbild_x`, `profilbild_y`, `mensa_nr`, `mhn_mitglied`, `telefon`, `mobil`, `kurzvita`, `new_email`, `new_email_token`, `new_email_token_expire_time`, `db_modified`, `last_login`, `new_password`, `new_password_expire_time`, `deleted`, `locked`, `publish`, `datenschutz_bereinigt`, `datenschutz_bereinigung_termin`, `kenntnisnahme_informationspflicht_persbez_daten`, `kenntnisnahme_informationspflicht_persbez_daten_text`, `einwilligung_persbez_zusatzdaten`, `einwilligung_persbez_zusatzdaten_text`) VALUES
(1, 'webteam@mind-hochschul-netzwerk.de', ':pbkdf2:sha256:10000:128:XWabzb6lAU4T4x7jrZXdAg==:90DX2XkoJpPJnFv7WvuKgeKxC+OpHrmsiXPeBGPKYm3Ctia8/uj4+R+TlipgfJoSMcMizefqEn8WPFdspcxsBNoUmsrvrTQz+dc/K8iOq12FdbNrPCy48duPemYW19pAgEW3BwZ4xMq1/5YZDx3yvKwJbk0xE3wAMIy7y1aYjKc=', '', 'Web', 'Team', 'u', '2017-03-16 10:03:17', '', NULL, NULL, '1234', 1, '+49 1234 56789', '+49 02356 12345', 'Kurzvita', '', '', '1970-01-01 00:00:00', '2016-10-30 10:03:17', '2018-05-30 17:11:07', '', '1970-01-01 00:00:00', 0, 1, 1, 0, NOW() + INTERVAL 1 YEAR, NOW(), '', NOW(), ''),
(2, 'pt@mhn.de', ':pbkdf2:sha256:10000:128:6QOkaaaPd44Tgdd2uFrlUQ==:DYb0j4sI6U79DLR4uMGgoCf+zxj2rg6xZwxHI/FiGGX3ADG/0fXqEEnUuJVSTKCaVkSpqvqULymSp26BRqsQU12gffKU8Fg2c1F9qeWTTfDk+IlNFHZAUUMYNnMZ7649YuW3ilUBWamAr/ghcA9UaQnwbh+09s3C5rwQ94GHV8c=', '', 'MA', 'Programmteam', 'm', '2017-03-16 10:03:17', '', NULL, NULL, '1234', 1,'+49 1234 56789', '+49 02356 12345', 'Kurzvita', '', '', '1970-01-01 00:00:00', '2016-10-30 10:03:17', '2016-10-30 17:11:07', '', '1970-01-01 00:00:00', 0, 1, 1, 0, NOW() + INTERVAL 1 YEAR, NOW(), '', NOW(), ''),
(3, 'referent@mhn.de', ':pbkdf2:sha256:10000:128:NOwDz7lNRqvE2iq42tiLxg==:bGLpYp0nBlWGjBwXnakYrIrnXhtoO6XgxRA6xw83tCvvtWyUa+SQLm2yFI5e4qPGYr5vBofwhnw9Jxi5tIPqsYrqpNFKr4pUeWNBGUDwUfankE3dad1lUqgwHzm7yhKyPgo7tkhVpbojvaEcB+gUQIEd6BdnGLTPdE4c1dBJ290=', '', 'MA', 'Referent', 'm', '2017-03-16 10:03:17', '', NULL, NULL, '1234', 0, '+49 1234 56789', '+49 02356 12345', 'Kurzvita', '', '', '1970-01-01 00:00:00', '2016-10-30 10:03:17', '2016-10-30 17:11:07', '', '1970-01-01 00:00:00', 0, 1, 1, 0, NOW() + INTERVAL 1 YEAR, NOW(), '', NOW(), ''),
(4, 'referent2@mhn.de', ':pbkdf2:sha256:10000:128:NOwDz7lNRqvE2iq42tiLxg==:bGLpYp0nBlWGjBwXnakYrIrnXhtoO6XgxRA6xw83tCvvtWyUa+SQLm2yFI5e4qPGYr5vBofwhnw9Jxi5tIPqsYrqpNFKr4pUeWNBGUDwUfankE3dad1lUqgwHzm7yhKyPgo7tkhVpbojvaEcB+gUQIEd6BdnGLTPdE4c1dBJ290=', '', 'MA', 'Referent', 'm', '2017-03-16 10:03:17', '', NULL, NULL, '1234', 0, '+49 1234 56789', '+49 02356 12345', 'Kurzvita', '', '', '1970-01-01 00:00:00', '2016-10-30 10:03:17', '2016-10-30 17:11:07', '', '1970-01-01 00:00:00', 0, 1, 1, 0, NOW() + INTERVAL 1 YEAR, NOW(), '', NOW(), ''),
(5, 'referent3@mhn.de', ':pbkdf2:sha256:10000:128:NOwDz7lNRqvE2iq42tiLxg==:bGLpYp0nBlWGjBwXnakYrIrnXhtoO6XgxRA6xw83tCvvtWyUa+SQLm2yFI5e4qPGYr5vBofwhnw9Jxi5tIPqsYrqpNFKr4pUeWNBGUDwUfankE3dad1lUqgwHzm7yhKyPgo7tkhVpbojvaEcB+gUQIEd6BdnGLTPdE4c1dBJ290=', '', 'MA', 'Referent', 'm', '2017-03-16 10:03:17', '', NULL, NULL, '1234', 0, '+49 1234 56789', '+49 02356 12345', 'Kurzvita', '', '', '1970-01-01 00:00:00', '2016-10-30 10:03:17', '2016-10-30 17:11:07', '', '1970-01-01 00:00:00', 0, 1, 1, 0, NOW() + INTERVAL 1 YEAR, NOW(), '', NOW(), ''),
(6, 'referent4@mhn.de', ':pbkdf2:sha256:10000:128:NOwDz7lNRqvE2iq42tiLxg==:bGLpYp0nBlWGjBwXnakYrIrnXhtoO6XgxRA6xw83tCvvtWyUa+SQLm2yFI5e4qPGYr5vBofwhnw9Jxi5tIPqsYrqpNFKr4pUeWNBGUDwUfankE3dad1lUqgwHzm7yhKyPgo7tkhVpbojvaEcB+gUQIEd6BdnGLTPdE4c1dBJ290=', '', 'MA', 'Referent', 'm', '2017-03-16 10:03:17', '', NULL, NULL, '1234', 0, '+49 1234 56789', '+49 02356 12345', 'Kurzvita', '', '', '1970-01-01 00:00:00', '2016-10-30 10:03:17', '2017-06-30 17:11:07', '', '1970-01-01 00:00:00', 0, 1, 1, 0, NOW() + INTERVAL 1 YEAR, NOW(), '', NOW(), '');

-- User: webteam@mind-hochschul-netzwerk.de   Pw: webteam1
-- User: pt@mhn.de         Pw: ma-pt
-- User: referent@mhn.de   Pw: referent

CREATE TABLE `vortraege` (
  `vid` int(10) UNSIGNED NOT NULL,
  `eid` int(10) UNSIGNED NOT NULL,
  `vTitel` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `beitragsform` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'v',
  `beitragssprache` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'd',
  `beschrTeilnehmeranzahl` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n',
  `maxTeilnehmeranzahl` varchar(3) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `praefZeit` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `anmerkungen` text CHARACTER SET utf8 NOT NULL,
  `abstract` text CHARACTER SET utf8 NOT NULL,
  `equipment_beamer` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_computer` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_wlan` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_lautsprecher` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_mikrofon` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_flipchart` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_sonstiges` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_sonstiges_beschreibung` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `anlagezeitpunkt` datetime DEFAULT NULL,
  `db_modified` datetime DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `datenschutz_bereinigt` tinyint(1) NOT NULL DEFAULT '0',
  `datenschutz_bereinigung_termin` DATE,
  `programm_raum` varchar(255) NOT NULL DEFAULT '',
  `programm_beginn` DATETIME DEFAULT NULL,
  `programm_ende` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vortraege` (`vid`, `eid`, `vTitel`, `beitragsform`, `beitragssprache`, `beschrTeilnehmeranzahl`, `maxTeilnehmeranzahl`, `praefZeit`, `anmerkungen`, `abstract`, `equipment_beamer`, `equipment_computer`, `equipment_wlan`, `equipment_lautsprecher`, `equipment_mikrofon`, `equipment_flipchart`, `equipment_sonstiges`, `equipment_sonstiges_beschreibung`, `anlagezeitpunkt`, `locked`, `publish`, `datenschutz_bereinigt`, `datenschutz_bereinigung_termin`, `programm_raum`, `programm_beginn`, `programm_ende`
) VALUES
  (1, 1, 'Make MHN-Webteam great again!', 'v', 'd', 'n', '99', 'kann immmer!', 'I promise, it will be great!', 'blablabla', 1, 1, 1, 1, 1, 1, 1, 'Freunde', '2016-04-08 10:03:17', 0, 0, 0, NOW() + INTERVAL 1 YEAR, 'Raum A', '2016-04-08 10:03:17', '2016-04-08 11:00:17'),
  (2, 2, 'Vortrag MA2017', 'v', 'd', 'n', '99', 'kann immmer!', 'I promise, it will be great!', 'blablabla', 1, 1, 1, 1, 1, 1, 1, 'Geld', '2017-04-08 10:03:17', 1, 1, 0, NOW() + INTERVAL 1 YEAR, 'Raum A', '2016-04-08 10:03:17', '2016-04-08 11:00:17'),
  (3, 3, 'Vortrag MA2018', 'v', 'd', 'y', '99', 'kann immmer!', 'I promise, it will be great!', 'blablabla', 1, 0, 1, 1, 0, 1, 1, 'Teilnehmer wären schön :)', '2018-04-08 10:03:17', 1, 1, 0, NOW() + INTERVAL 1 YEAR, 'Raum A', '2016-04-08 10:03:17', '2016-04-08 11:00:17'),
  (4, 5, 'Vortrag SY2019', 'v', 'd', 'y', '99', 'kann immmer!', 'I promise, it will be great!', 'blablabla', 1, 1, 1, 1, 1, 1, 1, 'Teilnehmer wären schön :)', '2018-04-08 10:03:17', 1, 1, 0, NOW() + INTERVAL 1 YEAR, 'Raum A', '2016-04-08 10:03:17', '2016-04-08 11:00:17'),
  (5, 6, 'Vortrag MA2020', 'v', 'd', 'y', '99', 'kann immmer!', 'I promise, it will be great!', 'blablabla', 1, 1, 1, 1, 1, 1, 1, 'Teilnehmer wären schön :)', '2018-04-08 10:03:17', 1, 1, 0, NOW() + INTERVAL 1 YEAR, 'Raum A', '2016-04-08 10:03:17', '2016-04-08 11:00:17');

CREATE TABLE `events` (
  `eid` int(10) UNSIGNED NOT NULL,
  `kennzeichen` varchar(10) NOT NULL,
  `bezeichnung` varchar(255) NOT NULL,
  `datum_letzter_tag` DATETIME DEFAULT NULL,
  `loeschdatum_daten` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `events` (`eid`, `kennzeichen`, `bezeichnung`, `datum_letzter_tag`, `loeschdatum_daten`) VALUES
(1, 'MA2016', 'MinD-Akademie 2016', '2016-10-03 23:59:59', '2016-11-03 23:59:59'),
(2, 'MA2017', 'MinD-Akademie 2017', '2017-10-03 23:59:59', '2017-11-03 23:59:59'),
(3, 'MA2018', 'MinD-Akademie 2018', '2018-10-06 23:59:59', '2018-11-06 23:59:59'),
(4, 'MA2019', 'MinD-Akademie 2019', '2019-10-06 23:59:59', '2019-11-06 23:59:59'),
(5, 'SY2019', 'MHN-Symposium 2019', '2019-04-25 23:59:59', '2019-05-25 23:59:59'),
(6, 'MA2020', 'MinD-Akademie 2020', '2020-10-06 23:59:59', '2020-11-06 23:59:59'),
(7, 'MA2018T', 'MinD-Akademie 2018 - Test', '2018-10-06 23:59:59', '2018-07-3 14:00:00'),
(8, 'MA2018T2', 'MinD-Akademie 2018 - Test2', '2018-10-06 23:59:59', '2018-07-10 15:00:00');

CREATE TABLE `rechte` (
  `rid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `recht` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rechte` (`rid`, `uid`, `recht`) VALUES
(1, 1, 'rechte'),
(2, 2, 'referent'),
(3, 2, 'ma-pt'),
(4, 3, 'referent'),
(5, 4, 'referent'),
(6, 5, 'referent'),
(7, 5, 'ma-pt'),
(8, 6, 'referent');

CREATE TABLE `benutzerZuVortraege` (
  `id` int(10) UNSIGNED NOT NULL,
  `uid` int(5) UNSIGNED NOT NULL,
  `vid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `benutzerZuVortraege` (`id`, `uid`, `vid`) VALUES
(1, 3, 1),
(2, 3, 2),
(3, 1, 3),
(4, 3, 3),
(5, 1, 4),
(6, 3, 4),
(7, 1, 5);

ALTER TABLE `benutzer`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `vortraege`
  ADD PRIMARY KEY (`vid`);

ALTER TABLE `events`
  ADD PRIMARY KEY (`eid`);

ALTER TABLE `rechte`
  ADD PRIMARY KEY (`rid`);

ALTER TABLE `benutzerZuVortraege`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `benutzer`
  MODIFY `uid` int(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `vortraege`
  MODIFY `vid` int(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `events`
  MODIFY `eid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `rechte`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `benutzerZuVortraege`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE bloecke (
    titel VARCHAR(255) NOT NULL,
    typ CHAR(1) NOT NULL,
    beginn DATETIME NOT NULL,
    ende DATETIME NOT NULL,
    jahr INT(4) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rahmenprogramm (
    id VARCHAR(255),
    titel VARCHAR(255) NOT NULL,
    raum VARCHAR(255) NOT NULL,
    beitragsform CHAR(1) NOT NULL,
    beginn DATETIME NOT NULL,
    ende DATETIME NOT NULL,
    jahr INT(4) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
