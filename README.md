### IP-Symcon Modul // Grafana
---
## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Systemanforderungen](#2-systemanforderungen)
3. [Installation](#3-installation)
4. [Konfiguration](#4-konfiguration)
5. [Grafana](#5-grafana)
6. [Grafana Tips](#6-grafana tips)
7. [Changelog](#7-changelog)
8. [ToDo Liste](#8-todo liste)


## 1. Funktionsumfang


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

## 6. Grafana Tips
Aenderung in der Konfigurationsdatei von Grafana sollen nicht in der defaults.ini
gemacht werden. Die Datei defaults.ini kopieren nach custom.ini oder grafana.ini.
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


## 7. Changelog

## 8. ToDo Liste
Aggregationsstufen optimieren


