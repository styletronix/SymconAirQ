# Air Q
Dieses Modul verbindet sich mit einem über HTTP erreichbaren Air-Q und liest dessen Daten aus.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang
- Zyklisches auslesen aller Messwerte des Air-Q.
- Bildung von gleitenden Mittelwerten für verschiedene Zeiträume wie für Auswertung der Warnschwellen nach WHO oder EU Richtlinien notwendig.

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5
- Air-Q von https://www.air-q.com/ und direktem Zugriff auf die Weboberfläche. (Getestet mit der Science-Version)

### 3. Software-Installation

* Über den Module Store das 'Air Q'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
https://github.com/styletronix/SymconAirQ

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Air Q'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
URL      | Link zum Air-Q. z.B. http://192.168.0.5
Kennwort | Gerätekennwort
Aktualisierungsinterval | Automatische Aktualisierung alle X Sekunden.
Refresh interval average calculation

### 5. Statusvariablen und Profile

Die Standard Statusvariablen werden automatisch angelegt. Das Löschen einzelner Variablen führt nicht zur fehlfunktion. Diese werden aber beim nächsten start des Moduls erneut angelegt.

unbekannte Variablen und Sensoren können in der Konfiguration anhand der gelieferten Daten des Air-Q automatisch erstellt werden.

#### Statusvariablen

Siehe Air-Q Dokumentation.

#### Profile

Alle notwendigen Profile werden automatisch erstellt und beginnen mit SXAIRQ.
Für jeden Sensor wird ein eigenes Profil generiert um Grenzwerte getrennt einstellenzu können.


### 6. WebFront

Das Modul besitzt keine spezielle WebFront funktion.

### 7. PHP-Befehlsreferenz


`bool SXAIRQ_TestConnection(integer $InstanzID);`
Prüft, ob der Air-Q erreichbar ist und die Daten korrekt ausgelesen werden können.
Der Air-Q muss vor der Verwendung dieser Funktion vollständig konfiguriert und erreichbar sein.

Liefert 'true' bei erfolg und 'false' bei Fehler.
Zusätzlich sind Informationen im DEBUG Fenster zu finden.

Beispiel:
`$success = SXAIRQ_TestConnection(12345);`



`void SXAIRQ_Update(integer $InstanzID, boolean $includeAggregated = false);`
Aktualisiert die Daten des Air-Q sofort.
Der Air-Q muss vor der Verwendung dieser Funktion vollständig konfiguriert und erreichbar sein.
Bei $includeAggregated = true werden zusätzlich die gleitenden Durchschnittswerte berechnet. Wenn false, dann werden die zuletzt berechneten Werte als Referenz genommen.

Beispiel:
`SXAIRQ_Update(12345);`
`SXAIRQ_Update(12345, true);`

