# IPSymconLuftdaten

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Das Projekt _Luftdaten.info_ wurde von _OK Lab Stuttgart_ initiiert, um flächendeckend die Belastung mit Feinstaub zu messen. Um das erreichen zu können, wurden eine einfache Meßstation entwickelt, die man ohne spezielle Kenntnisse bauen und in Betrieb nehmen kann. Neben dem zentralen Sensor für Feinstaub (PM2.5 und PM10) gibt weiter optionale Sensoren für Temperatur, Luftfeuchtigkeit und Luftdruck. Die Station kommuniziert über WLAN.

Die Messdaten werden von der Station zyklisch an _Luftdaten.info_ und _Madavi.de_ übergeben und stehen dort zum Abruf bereit.
Eine lokale Sensor-Station kann man auf grundsätzlich auf zwei Arten einbinden
 - pull per http-get: das funktioniert nicht besonders gut, da das Modul nur während der Messungen aufwacht und zu anderen Zeit nicht erreichbar ist.
 - push per http-post: die Station kann die Daten nicht nur an die o.g. API's übergeben sondern auch zusätzlich an eine lokale API.<br>
   Diese Variante ist hier per WebHook realisiert.

Hinweis: aus _Luftdaten.info_ wird _Sensor.community_ - wenn sich etwas relevantes ändert wird das Modul laufend adaptiert, wenn der Prozess abgeschlossen ist, wird die Dokumentation angepasst.

## 2. Voraussetzungen

 - IP-Symcon ab Version 5.3<br>
   Version 4.4 mit Branch _ips_4.4_ (nur noch Fehlerkorrekturen)
 - eine eigene Sensor-Station gemäß Anleitung von https://luftdaten.info oder ein ausgewähler Sensor von http://deutschland.maps.luftdaten.info.

## 3. Installation

### a. Laden des Moduls

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconLuftdaten.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _Luftdaten.info_ auswählen.
Es werden zwei Module angeboten:
 - lokale Sensor-Station<br>
 das ist für den Abruf der Daten einer lokal installierte Sensor-Station mit allen Sensoren.
 - öffentliche Webseite<br>
das dient dem Abruf von Sensordaten von http://deutschland.maps.luftdaten.info. Es muss für jeden Sensor eine eigene Instanz angelegt werden.

#### Konfiguration für ein eigenen Luftdaten-Sensor

In dem Konfigurationsdialog müssen die Sensoren des Moduls angegeben werden, von denen die Daten übernommen werden sollen. Dien Angabe entspricht der Konfigurationswebseite des Sensor-Moduls.

Es wird hierfür ein WebHoom _hook/Luftdaten_ eingerichtet.

Auf der Konfigurationsseite des Sensor-Moduls muss die Datenübertragung noch parametriert werden

| Eigenschaft          | Beschreibung |
| :------------------- | :----------- |
| An eigene API senden | aktivieren |
| Server	           | IP-Adresse des IPS-Servers |
| Pfad	               | _/hook/Luftdaten_ |
| Port	               | _3777_ |

Es wird nur eine lokale Sensor-Station unterstützt.

#### Konfiguration für Sensoren von http://deutschland.maps.luftdaten.info

In dem Konfigurationsdialog muss die ID des Sensors eingegeben werden sowie der Type Sensor. Hinweis: in den öffentlichen Daten wird für jeden Sensor eine eigenen ID vergeben, also eine typische Messstation (Feinstaub + Temperatur) sind zwei Sensoren auf der Karten und müssen im IPS getrennt angelegt werden.
Zur Unterstützung der Konfiguration gibt es die Schaltfläche _Prüfe Konfigurætion_, die sowohl prüft, ob die Sensor-ID vorhanden ist als auch den gefundenen Sensortyp ausgibt.

## 4. Funktionsreferenz

### zentrale Funktion

`LuftdatenPublic_UpdateData(int $InstanzID)`

ruft die Daten von dem jeweiligen Sensor ab. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

## 5. Konfiguration:

### Variablen (LuftdatenPublic)

| Eigenschaft             | Typ     | Standardwert    | Beschreibung |
| :---------------------- | :------ | :-------------- | :----------- |
| Instanz deaktivieren    | boolean | false           | Instanz temporär deaktivieren |
|                         |         |                 | |
| Aktualisiere Daten ...  | integer | 60              | Aktualisierungsintervall, Angabe in Sekunden |
|                         |         |                 | |
| Sensor-ID               | string  |                 | Sensor-ID |
| Sensor                  | boolean |                 | aktivieren des Typs mit der o.g. Sensor-ID verknüpften Sensors |

Anmerkung: die Ermittlung der Messwerte wird in der jeweiligen Messstation eingetragen; der Standarwert sind 150s. Um also alle Messungen mitzubekommen muss man ein kürzeres Intervall wählen (daher 60s).

### Variablen (LuftdatenLocal)

| Eigenschaft             | Typ     | Standardwert    | Beschreibung |
| :---------------------- | :------ | :-------------- | :----------- |
| Webhook                 | string  | /hook/Luftdaten | Bezeichnung des Webhook, anzupassen wenn mehr als eine Instanz im Einsatz ist |
|                         |         |                 | |
| installierte Sensoren   | boolean |                 | aktivieren der Typen der verbauten Sensoren |

### Schaltflächen (LuftdatenPublic)

| Bezeichnung         | Beschreibung |
| :------------------ | :----------- |
| Prüfe Konfiguration | ruft einen Datensatz ab und prüft die Konfiguration dagegen |
| Aktualisiere Daten  | führt eine sofortige Aktualisierung durch |

## 6. Anhang

GUIDs

- Modul: `{F3ACD08B-992B-4B5B-8B84-5128AED488C0}`
- Instanzen:
  - LuftdatenPublic: `{60899603-A710-4B6C-A0C4-5F373251BE46}`
  - LuftdatenLocal: `{7BE33479-C99A-4706-8315-ECD3FBDFBA2C}`

## 7. Versions-Historie

- 1.22 @ 02.01.2025 14:28
  - interne Änderung
  - update submodule CommonStubs

- 1.21 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.20 @ 03.11.2023 11:06
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.19 @ 04.07.2023 14:44
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.18 @ 09.12.2022 09:14
  - Neu: Bezeichnung des Webhook kann angepasst werden
  - update submodule CommonStubs

- 1.17.1 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.17 @ 07.07.2022 12:24
  - einige Funktionen (GetFormElements, GetFormActions) waren fehlerhafterweise "protected" und nicht "private"
  - interne Funktionen sind nun private und ggfs nur noch via IPS_RequestAction() erreichbar
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert
  - update submodule CommonStubs

- 1.16.2 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.16.1 @ 10.05.2022 15:06
  - update submodule CommonStubs

- 1.16 @ 01.05.2022 16:50
  - IPS-Version ist nun minimal 6.0
  - Anzeige der Modul/Bibliotheks-Informationen, Referenzen und Timer
  - Implememtierung einer Update-Logik
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)
  - diverse interne Änderungen

- 1.15.1 @ 06.01.2022 16:37
  - automatischer Retry nach HTTP-Server-Error

- 1.15 @ 29.12.2021 17:38
  - API-URL hat sich geändert
  - Anzeige der Modul/Bibliotheks-Informationen

- 1.14 @ 25.09.2021 09:11
  - Lärmsensor (DNMS) hinzugefügt

- 1.13 @ 14.07.2021 18:40
  - PHP_CS_FIXER_IGNORE_ENV=1 in github/workflows/style.yml eingefügt
  - Schalter "Instanz ist deaktiviert" umbenannt in "Instanz deaktivieren"

- 1.12 @ 23.07.2020 09:50
  - LICENSE.md hinzugefügt
  - define's durch statische Klassen-Variablen ersetzt
  - library.php in local.php umbenannt
  - lokale Funktionen aus common.php in locale.php verlagert

- 1.11 @ 30.12.2019 10:56
  - Anpassungen an IPS 5.3
    - Formular-Elemente: 'label' in 'caption' geändert
  - Fix in CreateVarProfile()

- 1.10 @ 10.10.2019 17:27
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
    - Dokumentation-URL in module.json
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer

- 1.9 @ 09.08.2019 14:32
  - Schreibfehler korrigiert

- 1.8 @ 26.04.2019 16:44
  - Übersetzung korrigiert

- 1.7 @ 29.03.2019 16:19
  - SetValue() abgesichert

- 1.6 @ 21.03.2019 17:04
  - Anpassungen IPS 5, Abspaltung von Branch _ips_4.4_
  - Schalter, um ein Modul (temporär) zu deaktivieren
  - form.json in GetConfigurationForm() abgebildet
  - Konfigurations-Element IntervalBox -> NumberSpinner

- 1.5 @ 23.01.2019 18:18
  - curl_errno() abfragen

- 1.4 @ 30.12.2018 15:26
  - Werte für den Sensor _BMP280_ haben nicht (wie in der Doku steht) den Prefix _BMP_ sondern _BMP280_

- 1.3 @ 22.12.2018 11:37
  - Fehler in der http-Kommunikation nun nicht mehr mit _echo_ (also als **ERROR**) sondern mit _LogMessage_ als **NOTIFY**

- 1.2 @ 21.12.2018 13:10
  - Standard-Konstanten verwenden

- 1.1 @ 17.09.2018 17:13
  - Versionshistorie dazu
  - define's der Variablentypen
  - Schaltfläche mit Link zu README.md im Konfigurationsdialog

- 1.0 @ 18.05.2018 15:31
  - Initiale Version
