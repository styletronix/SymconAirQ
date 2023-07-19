# Air Q
Dieses Modul verbindet sich mit einem über HTTP erreichbaren Air-Q und liest dessen Daten aus.

## Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

## 1. Funktionsumfang
- Zyklisches auslesen aller Messwerte des Air-Q.
- Bildung von gleitenden Mittelwerten für verschiedene Zeiträume wie für Auswertung der Warnschwellen nach WHO oder EU Richtlinien notwendig.

#### Neu ab Version 1.2
- Lesen und schreiben der Air-Q Konfiguration (Science Version).
- Verwendung eines WebHook um Daten von externen Air-Qs empfangen zu können. (Siehe [PHP-Befehlsreferenz](#7-php-befehlsreferenz))

## 2. Voraussetzungen

- IP-Symcon ab Version 5.5
- Air-Q von https://www.air-q.com/ und direktem Zugriff auf die Weboberfläche. (Getestet mit der Science-Version)

## 3. Software-Installation

* Über den Module Store das 'Air Q'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
https://github.com/styletronix/SymconAirQ

## 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Air Q'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

### Konfigurationsseite:
* `URL` Link zum Air-Q. z.B. http://192.168.0.5

* `Kennwort` Gerätekennwort

* `Aktualisierungsinterval` 
 Aktualisierung der Daten aus Air-Q alle X Sekunden. 

>Der schnellste aktualisierungszeitraum der Sensoren von Air-Q liegt bei etwa 2 Sekunden. Falls ein so geringer Wert gewählt wird, sollte bei Archivierung der Variblen unbedingt die Reduzierung der Daten aktiviert werden.

* `Aktualisierungsinterval für gleitenden Durchschnitt` 
Aktualisiert alle x Sekunden alle gleitenden Durchschnittswerte. Zusätzlich werden auch die aktuellen Daten vom Air-Q abgeholt.
>Die berechnung des gleitenden Durschnitts wird in den Einstellungen der Sensoren vorgenommen.
Für die entsprechenden Variablen der Sensorwerte muss die Archivierung manuell aktiviert werden damit der Durchschnitt berechnet werden kann.
>
>Da die Berechnung resourcenintensiv sein kann und die intrne Auflösung auf 1 Minute eingestellt ist, wird empfohlen diesen Wert nicht unter 60 Sekunden einzustellen.

* __Sensoren__<br>
Liste mit allen verfügbaren Sensoren.
>Zum füllen der Liste können Sie die Schaltfläche `Erstelle Variablen für alle empfangenen Datenfelder` verwenden, nachdem die Grundkonfiguration abgeschlossen wurde.

### Sensoren Konfiguration:
* `Aktiviert` Aktiviert die Variablen für den Sensor.

* `Sensor` Interne Sensorbezeichnung. 
> Es wird Groß- / Kleinschreibung unterschieden 
* `Anzeigename`
Der Anzeigename wird für die Variable des Sensors verwendet.
* `Multiplikator` Faktor, mit dem der gemessene Wert multipliziert wird, bevor er in die Variable geschrieben wird. (Standard: 1)

* `Versatz` Versatz, der zum Messwert hinzugefügt werden soll. (Standard: 0)

* `Grenzwerte` Liste mit Grenzwerten und gleitenden Durchschnittsberechnungen.

### Grenzwerte:
* `Bezeichnung` Interne Bezeichnung. _Wird nicht weiter verwendet._

* `Zeitspanne für gleitenden Durchschnitt` Die Zeitspanne über die der gleitende Durchschnitt für den Messwert gebildet werden soll. 
>1 Stunde = 60 Minuten  /  1 Tag = 1.440 Minuten / 1 Monat = 43.200* Minuten / 1 Jahr = 534.600* Minuten  *) Annäherungswert der intern zur Kalkulation verwendet wird.
>
>Bei einem Wert von `0` wird der Eintrag lediglich als Grenzwert vrewendet. Bei über- oder unterschreiten wird die Variable `[Sensorname] Status` auf den Wert `Dringlichkeit` gesetzt.
>
>Sie können mehrere Grenzwerte mit verschiedenen Dringlichkeitsstufen sowohl für reine Grenzwerte als auch für gleitende Durchschnitte konfigurieren.
>
>Bei überschreitung der Grenzwerte wird der Status auf den Wert der höchsten Dringlichkeit gestellt. 
>
>Um zu vermeiden dass gleitende Durchschnitte mehrfach berechnet werden, sollten sie darauf achten, dass bei gleichen Zeiträumen auch exakt die gleiche Anzahl an Minuten eingetragen wurde. Andernfalls wird für jeden Eintrag eine neue Variable erstellt, selbst wenn z.b. im Zeitraum von einem Jahr der eingegebene Wert nur um 1 Minute abweicht.

* `Oberer Grenzwert` Oberer Wert, bei dessen überschreitung der Status auf den Wert `Dringlichkeit` gesetzt wird.

* `Unterer Grenzwert` Unterer Wert, bei dessen überschreitung der Status auf den Wert `Dringlichkeit` gesetzt wird.

* `Dringlichkeit`
Wert der bei über- oder Unterschreiten des Grenzwerts gesetzt wird.
>- 0: OK
>- 1: Information
>- 2: Warnung
>- 3: Gefahr
>
> Wenn mehrere Grenzwerte überschritten werden, wird die Statusvariable für den Grenzwert aif den hier angegebenen Wert gesetzt. Die Statusvariable für den Sensor wird auf die höchste Dringlichkeitsstufe der überschrittenen Messwerte gesetzt.

## 5. Statusvariablen und Profile

Die Standard Statusvariablen werden automatisch angelegt. Das Löschen einzelner Variablen führt nicht zur fehlfunktion. Diese werden aber beim nächsten start des Moduls erneut angelegt.

unbekannte Variablen und Sensoren können in der Konfiguration anhand der gelieferten Daten des Air-Q automatisch erstellt werden.

## Statusvariablen

Siehe Air-Q Dokumentation.

## Profile

Alle notwendigen Profile werden automatisch erstellt und beginnen mit SXAIRQ.
Für jeden Sensor wird ein eigenes Profil generiert um Grenzwerte getrennt einstellenzu können.


## 6. WebFront

Das Modul besitzt keine spezielle WebFront funktion.

## 7. PHP-Befehlsreferenz


### `bool SXAIRQ_TestConnection(int $InstanzID);`
Prüft, ob der Air-Q erreichbar ist und die Daten korrekt ausgelesen werden können.
Der Air-Q muss vor der Verwendung dieser Funktion vollständig konfiguriert und erreichbar sein.

Liefert 'true' bei erfolg und 'false' bei Fehler.
Zusätzlich sind Informationen im DEBUG Fenster zu finden.

#### Beispiel:
```php
$success = SXAIRQ_TestConnection(12345);
```

### `SXAIRQ_Update(int $InstanzID, boolean $includeAggregated = false);`
Aktualisiert die Daten des Air-Q sofort.
Der Air-Q muss vor der Verwendung dieser Funktion vollständig konfiguriert und erreichbar sein.
Bei $includeAggregated = true werden zusätzlich die gleitenden Durchschnittswerte berechnet. Wenn false, dann werden die zuletzt berechneten Werte als Referenz genommen.

#### Beispiel:
```php
SXAIRQ_Update(12345, true);
```

### `SXAIRQ_GetDataDecoded(int $InstanzID);`

Liest die aktuellen Daten aus Air-Q und gibt diese als unbearbeitetes Array mit Key, Value daten aus.
Liefert Null bei Fehler.

#### Beispiel:
```php
$data = SXAIRQ_GetDataDecoded(12345);

foreach ($data as $key => $val){
	if (is_array($val)) {
		$value = $val[0];
		$errorRate = $val[1];
	} else {
		$value = $val;
		$errorRate = null;
	}

	print ('Sensor ' . $key . ' - Value: ' . $value . "\n");
}
```

### `SXAIRQ_SetDeviceConfig(int $InstanceID, array $data)`

Setzt eine oder mehrere Einstellungen im Air-Q. Siehe hierzu das JSOn Format für die Geräteeisntellung unter https://docs.air-q.com/.

Liefert ein Array mit 'id' und 'content' zurück, falls der Aufruf erfolgreich war. Sonst ist der Rückgabewert null.

#### Beispiel:
```php
	// Air-Q neu starten:
	$result = SXAIRQ_SetDeviceConfig(12345, ['reset' => true]);
	print_r ($result);
}
```

```php
	// Zusätzliches WLAN eintragen.:
	$result = SXAIRQ_SetDeviceConfig(12345,
		[
  			'WiFissid' => 'Ihre WLAN-SSID',
  			'WiFipass' => 'Ihr WLAN-Key',
			'reset' => true
		]);
	print_r ($result);

```

### `SXAIRQ_GetDeviceConfig(int $InstanceID)`

Ruft die Konfiguration des Air-Q ab und liefert diese als Array.

#### Beispiel:
```php
	$result = SXAIRQ_GetDeviceConfig(12345);
	print_r ($result);

```

### `SXAIRQ_StoreDataFromHTTPPost(int $InstanceID, $data)`

Wertet die Daten, welche direkt vom Air-Q per Webhook geliefert wurden aus.

#### Beispiel zur WebHook verwendung:
In Symcon wird folgendes Webhook erstellt:
`http://meineDomain.de/hook/airq`

Im Skript zum Webhook wird folgender Code hinterlegt:
```php
	$data = json_decode(file_get_contents("php://input"));
	foreach (IPS_GetInstanceListByModuleID('{75D0E69C-5431-A726-2ADC-D6EBA6B623E9}') as $id){
    	if (GetValueString( IPS_GetObjectIDByIdent('DeviceID',$id) ) == $data['DeviceID']){
        	SXAIRQ_StoreDataFromHTTPPost($id, $data);
    	}
	}
```

Und im Air-Q wird mit folgendem Code die Konfiguration zum automatischen senden der Daten aktiviert:
```php
$InstanceID = 1234; // Die ID der Air-Q Instanz eintragen !!

$config = [
  'httpPOST' => [
    'URL' => 'http://meineDomain.de/hook/airq',
    'Headers' => ['Content-Type' => 'application/json'],
    'averages' => true,
    'delay' => 30
  ]
];

print_r(SXAIRQ_SetDeviceConfig($InstanceID, $config));
```


Als Ergebnis wird der Air-Q alle 30 Sekunden die Durchschnittswerte an IP-Symcon senden.
Im Webhook wird automatisch nach einer Air-Q Instanz mit der richtigen DeviceID gesucht und dort die Daten aktualisiert.

Hierdurch kann ein WebHook für mehrere Air-Qs verwendet werden. Es muss jedoch zwingend die Seriennummer in der Variable `DeviceID` hinterlegt sein.

Selbstverständlich kann für den WebHook auch die IP-Magic Adresse des Connect Dienstes verwendet werden. So kann ein externer Air-Q aktiv die Daten an IP-Symcon senden, nachdem er einmalig mit direkter Verbindung konfiguriert wurde.