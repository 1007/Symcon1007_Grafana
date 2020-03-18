### IP-Symcon Modul // Grafana
---
## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Systemanforderungen](#2-systemanforderungen)
3. [Installation](#3-installation)
4. [Konfiguration](#4-konfiguration)
5. [Grafana](#5-grafana)
6. [Grafana Tips](#6-grafanatips)
7. [Changelog](#7-changelog)
8. [ToDo Liste](#8-todoliste)


## 1. Funktionsumfang
Dieses Modul bietet Grafana direkten Zugang zu allen geloggten Variablen. 
Alle geloggten Variablen stehen automatisch in Grafana als Metrics zu Verfuegung.


## 2. Systemanforderungen
- IP-Symcon ab Version 4.x
- Grafana Installation
- Grafana Plugin JSON by simpod

## 3. Installation
Über die Kern-Instanz "Module Control" folgende URL hinzufügen:

`https://github.com/1007/Symcon1007_Grafana`

Instanz hinzufuegen.

## 4. Konfiguration
Das Modul ist unter den Kerninstanzen zu finden.
In der Konfiguration kann ein User und Passwort vergeben werden.
Dies muss mit der Einstellung in Grafana Data Sources /JSON
uebereinstimmen. Aktiviere unter AUTH - Basic auth.
Siehe Bild unter Punkt 5.
Beides ohne Authorisierung ist auch moeglich.
Der Test im Grafana-Plugin zeigt auch dann
"Data source is working" wenn Authorisierung fehlerhaft, weil
nur die Verbindung getestet wird nicht die Authorisierung.


Wer den Debug sich anschauen will, ist die Instanz in den Kerninstanzen zu finden.

## 5. Grafana
Konfiguration des Plugins JSON by simpo
zum Beispiel:
![Plugin](imgs/DataSources.png?raw=true "Plugin")

Grafiken in Grafana erstellen:

- Einloggen mit Port 3000.
- Dashboard erstellen
- Darin ein Panel, oder mehrere, erstellen
- Add Query . Darin JSON auswaehlen
- Unter Metric koennen alle geloggten Variablen ausgewaehlt werden.
- Erster Wert ist die ID
- Zweiter Wert ist der Name fuer die Legende ( aenderbar)
- URL auswaehlen fuer Webfront/IPSView unter Menuepunkt Share.

[Erste Schritte mit Grafana von Attain](https://github.com/1007/Symcon1007_Grafana/blob/master/imgs/Grafana.pdf)

[Mischen Grafiktypen](https://github.com/1007/Symcon1007_Grafana/blob/master/imgs/Mischen%20von%20Grafiktypen.pdf)

## 6. GrafanaTips
Aenderung in der Konfigurationsdatei von Grafana sollen nicht in der defaults.ini
gemacht werden.Die Datei defaults.ini kopieren nach custom.ini oder grafana.ini.

Dienst neu starten.

Sollten die Grafiken im Webfront nicht angezeigt werden folgendes aendern in ini-Datei:


Von:

	allow_embedding: false
	cookie_samesite: lax


Nach:

	allow_embedding: true
	cookie_samesite: none


Neustart nicht vergessen


Wenn ihr in der Userverwaltung User nur mit Login habt.
Dann kommt im Webfront/IPSView einmalig ein Anmeldebildschirm.

Wer nur den Graph braucht, ohne die Auswahlmoeglichkeiten fuer Zeiten,
nimmt am Besten nur den Link unter Share-Panel-Embed

Hintergrundfarbe auf "Transparent" setzen funktioniert nicht.
In die index.html folgende Zeile einfuegen

	<link rel="stylesheet" href="https://....../user/Grafana/mygrafana.css" type="text/css">


Entsprechend die mygrafana.css erstellen und folgendes eintragen.

	@charset "UTF-8";

	.panel-container {
	background-color: #xxxxxx !important;
	border: 0px solid #FFFFFF !important;
	}




## 7. Changelog

Version 1.0	Startup
Version 1.1 Verschiedene Fehler behoben. Doku verbessert.

## 8. ToDoListe
Aggregationsstufen optimieren.

Dokumentation verbessern.

