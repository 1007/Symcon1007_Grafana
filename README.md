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
In IPSymcon ist keine Konfiguration noetig.

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

## 7. Changelog

Version 1.0	Startup

## 8. ToDoListe
Aggregationsstufen optimieren.

Dokumentation verbessern.

