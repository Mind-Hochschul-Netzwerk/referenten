# Maintenance-Skripts

Die Dateien in diesem Ordner werden aufgerufen, um regelmäßige Wartungsarbeiten durchzuführen.

Namensschema: `$sort.$name.$intervall.(php|sql)`

`$intervall` ist der Zeitintervall in dem das Skript aufgerufen werden soll in Sekunden. Maximal erlaubt ist 60.

Die maximale Laufzeit der Skripts soll 1 Sekunde nicht übersteigen!