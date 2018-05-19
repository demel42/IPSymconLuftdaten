# Syslog

Modul für IP-Symcon ab Version 4.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)

## 1. Funktionsumfang

## 2. Voraussetzungen

 - IPS 4.x
 - Luftdaten.info

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min Ver. 4.x) Konsole öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconLuftdaten.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _(sonstiges)_ und als Gerät _Luftdaten_ auswählen.

In dem Konfigurationsdialog den ....

## 4. Funktionsreferenz

### zentrale Funktion

`Luftdaten_UpdateData(int $InstanzID)`

ruft die Daten von dem jeweiligen Sensor ab. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

## 5. Konfiguration:

### Variablen

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :-----------------------: | :-----:  | :----------: | :----------------------------------------------------------------------------------------------------------: |
| UpdateDataInterval        | integer  | 5            | Angabe in Minuten |

### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :------------------------------------------------: |
| Aktualisiere Daten           | führt eine sofortige Aktualisierung durch |

## 6. Anhang

GUIDs

- Modul: `{F3ACD08B-992B-4B5B-8B84-5128AED488C0}`
- Instanzen:
  - IPSymconLuftdatenPublic: `{60899603-A710-4B6C-A0C4-5F373251BE46}`
  - IPSymconLuftdatenLocal: `{7BE33479-C99A-4706-8315-ECD3FBDFBA2C}`
