## Referententool

Referententool für Akademie und Symposium (http://referenten.mind-hochschul-netzwerk.de)

## Container lokal bauen und starten

### Target "dev" (Entwicklung)

Die Akademie muss bereits laufen (oder in docker-compose.base.yml die Zeile ganz unten beim Netzwerk `akademie` die Zeile `external: true` entfernen)

    $ composer install -d app
    $ make quick-image
    $ make dev

Der Login ist dann im Browser unter [http://referenten.docker.localhost](http://referenten.docker.localhost) erreichbar. Die Sicherheitswarnung wegen des Zertifikates kann weggeklickt werden.

* Benutzername: Webteam
* Passwort: webteam1

### Target "prod" (Production)

Die Akademie muss bereits laufen (oder in docker-compose.base.yml die Zeile ganz unten beim Netzwerk `akademie` die Zeile `external: true` entfernen)

    $ make prod

## Login-Rollen

Admin: 

* Benutzerkennung "webteam@mind-hochschul-netzwerk.de", 
* Passwort "webteam1"

Programmteam: 

* Benutzername "pt@mhn.de", 
* Passwort "ma-pt"

Referent: 

* Benutzername "referent@mhn.de", 
* Passwort "referent"

## Automatische Updates

Falls Änderungen ein Update an der Datenbank erforderlich machen, kann ein Update-Skript in `update.d` abgelegt werden, das die nötigen Änderungen vornimmt und dann beim Start des Containers geladen wird. Möglich sind PHP-Skripte (Endung .php) und SQL-Dateien (Endung .sql). Schlägt ein SQL-Query fehl, werden die nachfolgenden Queries in der Datei nicht mehr ausgeführt. Nachfolgende Update-Skripte werden aber trotzdem geladen.
