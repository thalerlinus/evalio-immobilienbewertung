# Berechnungslogik der Immobilienbewertung

Diese Dokumentation erklärt Schritt für Schritt, wie die Berechnung der Restnutzungsdauer (RND) und der AfA-Prozentsätze in der Evalio-Anwendung funktioniert.

---

## 📋 Übersicht

Das System berechnet die **Restnutzungsdauer (RND)** einer Immobilie basierend auf:
- Gebäudealter (für die Berechnung auf maximal 75 Jahre gedeckelt)
- Immobilientyp und dessen Gesamtnutzungsdauer (GND)
- Durchgeführten Renovierungen und deren Umfang
- Zeitpunkt der Renovierungen

Das Ergebnis wird verwendet, um eine Empfehlung für die steuerliche Abschreibung (AfA) zu geben. Netto- und Bruttopreise werden getrennt geführt; aktuell kommt ein Umsatzsteuersatz von 19 % zum Einsatz.

---

## 📦 Basisdaten aus Seedern

Alle Parameter, Punkte und Preise stammen aus den Laravel-Seedern im Verzeichnis `database/seeders`. Anpassungen an diesen Dateien wirken sich unmittelbar auf die Berechnung aus.

### Immobilientypen & Standardpreise

| Key | Bezeichnung | GND (Jahre) | Standardpreis netto (€) | Preis nur auf Anfrage |
|-----|-------------|-------------|-------------------------|-----------------------|
| eigentumswohnung | Eigentumswohnung | 80 | 1259 | Nein |
| einfamilienhaus | Einfamilienhaus | 80 | 1218 | Nein |
| zweifamilienhaus | Zweifamilienhaus | 80 | 1302 | Nein |
| dreifamilienhaus | Dreifamilienhaus | 80 | 1302 | Nein |
| mfh_4_10 | Mehrfamilienhaus mit 4–10 WE | 80 | 1428 | Nein |
| mfh_10_plus | Mehrfamilienhaus mit mehr als 10 WE | 80 | – | Nein |
| wgh_10_minus | Wohn- & Geschäftshaus bis 10 Einheiten | 80 | 1428 | Nein |
| wgh_10_plus | Wohn- & Geschäftshaus mit mehr als 10 Einheiten | 80 | – | Nein |
| gewerbeobjekt | Gewerbeobjekt | 60 | – | Ja |
| sonstiges | Sonstiges | – | – | Ja |

*Quelle:* `database/seeders/PropertyTypeSeeder.php`. Preise werden netto gespeichert; die Bruttodarstellung erfolgt später über Multiplikation mit dem MwSt.-Faktor (aktuell 1,19).

### Zusatzpakete & GA-Preise

| Key | Bezeichnung | Kategorie | Netto (€) |
|-----|-------------|-----------|-----------|
| besichtigung | Besichtigung | package | 294 |
| online | Online | package | 42 |

*Quelle:* `database/seeders/GaPricingSeeder.php`. Die ursprünglichen Bruttopreise (z. B. 350 € für die Besichtigung) werden im Seeder um 19 % reduziert und als Netto-Wert persistiert.

### Renovierungskategorien & Punktelimits

| Key | Bezeichnung | Maximalpunkte |
|-----|-------------|---------------|
| baeder_wc | Bäder und WC-Anlagen | 2 |
| innenausbau | Innenausbau | 2 |
| fenster_tueren | Fenster und Außentüren | 2 |
| heizung | Heizung | 2 |
| leitungen | Leitungen | 2 |
| dach_waermeschutz | Dach / Wärmeschutz | 4 |
| aussenwaende | Außenwände / Dämmung | 4 |

Gewichtungen nach Umfang (*Quelle:* `RenovationExtentWeightSeeder.php`):

| Umfang (%) | Gewicht |
|------------|---------|
| 0 | 0.0 |
| 20 | 0.2 |
| 40 | 0.4 |
| 60 | 0.6 |
| 80 | 0.8 |
| 100 | 1.0 |

Zeitfaktoren pro Renovierungszeitraum (*Quelle:* `RenovationTimeFactorSeeder.php`). Die Werte beziehen sich auf die Berechnungsnummern 1–7 aus der Kundenvorgabe:

| Zeitfenster | Bäder/WC | Innenausbau | Fenster/Außentüren | Heizung | Leitungen | Dach/Wärmeschutz | Außenwände |
|-------------|---------:|------------:|--------------------:|--------:|----------:|-----------------:|-----------:|
| nicht durchgeführt | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 |
| weiß ich nicht | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 |
| In den letzten 5 Jahren | 1.0 | 1.0 | 1.0 | 1.0 | 1.0 | 1.0 | 1.0 |
| In den letzten 5–10 Jahren | 0.5 | 1.0 | 1.0 | 1.0 | 1.0 | 0.75 | 0.75 |
| In den letzten 10–15 Jahren | 0.0 | 1.0 | 0.5 | 0.5 | 1.0 | 0.5 | 0.5 |
| In den letzten 15–20 Jahren | 0.0 | 0.5 | 0.0 | 0.0 | 0.5 | 0.25 | 0.25 |
| Vor über 20 Jahren | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 | 0.0 |

### Score-Formelsets (RND-Koeffizienten)

Die Koeffizienten werden je halben Score-Punkt definiert. `alter_schwelle` ist aktuell für alle Einträge auf `25` gesetzt. `rel_alter_min` markiert den Schwellwert des relativen Alters, ab dem die erweiterte Formel aktiv wird.

| Score | a | b | c | rel_alter_min |
|-------|-----|-----|-----|---------------|
| 0.0 | 1.2500 | 2.6250 | 1.5250 | 0.60 |
| 0.5 | 1.2500 | 2.6250 | 1.5250 | 0.60 |
| 1.0 | 1.2500 | 2.6250 | 1.5000 | 0.58 |
| 1.5 | 1.1634 | 2.4504 | 1.3564 | 0.58 |
| 2.0 | 1.0677 | 2.2757 | 1.2878 | 0.55 |
| 2.5 | 0.9630 | 2.1010 | 1.2505 | 0.55 |
| 3.0 | 0.9033 | 1.9263 | 1.2505 | 0.55 |
| 3.5 | 0.8167 | 1.7571 | 1.1819 | 0.48 |
| 4.0 | 0.7301 | 1.5770 | 1.0932 | 0.38 |
| 4.5 | 0.7013 | 1.5140 | 1.0932 | 0.38 |
| 5.0 | 0.6725 | 1.4578 | 1.0850 | 0.33 |
| 5.5 | 0.6438 | 1.3982 | 1.0709 | 0.33 |
| 6.0 | 0.6100 | 1.3395 | 1.0567 | 0.30 |
| 6.5 | 0.5863 | 1.2783 | 1.0425 | 0.28 |
| 7.0 | 0.5575 | 1.2193 | 1.0283 | 0.25 |
| 7.5 | 0.5288 | 1.1597 | 1.0142 | 0.23 |
| 8.0 | 0.5000 | 1.1000 | 1.0000 | 0.20 |
| 8.5 | 0.4830 | 1.0635 | 0.9935 | 0.19 |
| 9.0 | 0.4660 | 1.0270 | 0.9860 | 0.19 |
| 9.5 | 0.4490 | 0.9950 | 0.9859 | 0.18 |
| 10.0 | 0.4320 | 0.9540 | 0.9811 | 0.18 |
| 10.5 | 0.4150 | 0.9175 | 0.9764 | 0.17 |
| 11.0 | 0.3980 | 0.8810 | 0.9717 | 0.17 |
| 11.5 | 0.3810 | 0.8445 | 0.9670 | 0.17 |
| 12.0 | 0.3640 | 0.8080 | 0.9622 | 0.16 |
| 12.5 | 0.3470 | 0.7715 | 0.9575 | 0.16 |
| 13.0 | 0.3300 | 0.7350 | 0.9528 | 0.15 |
| 13.5 | 0.3170 | 0.7055 | 0.9517 | 0.15 |
| 14.0 | 0.3040 | 0.6760 | 0.9506 | 0.14 |
| 14.5 | 0.2910 | 0.6465 | 0.9496 | 0.14 |
| 15.0 | 0.2780 | 0.6170 | 0.9485 | 0.13 |
| 15.5 | 0.2650 | 0.5875 | 0.9474 | 0.13 |
| 16.0 | 0.2520 | 0.5580 | 0.9463 | 0.12 |
| 16.5 | 0.2390 | 0.5285 | 0.9453 | 0.12 |
| 17.0 | 0.2260 | 0.4990 | 0.9442 | 0.11 |
| 17.5 | 0.2130 | 0.4695 | 0.9431 | 0.11 |
| 18.0 | 0.2000 | 0.4400 | 0.9420 | 0.10 |
| 18.5 | 0.2000 | 0.4400 | 0.9420 | 0.10 |
| 19.0 | 0.2000 | 0.4400 | 0.9420 | 0.10 |
| 19.5 | 0.2000 | 0.4400 | 0.9420 | 0.10 |
| 20.0 | 0.2000 | 0.4400 | 0.9420 | 0.10 |

*Quelle:* `database/seeders/ScoreFormulaSeeder.php`.

---

## 🔢 Berechnungsschritte im Detail

### 1. Eingabedaten sammeln

Die Berechnung benötigt folgende Eingaben:

| Eingabe | Beschreibung | Beispiel |
|---------|--------------|----------|
| **Immobilientyp** | Art der Immobilie (z.B. Einfamilienhaus) | `einfamilienhaus` |
| **Baujahr** | Jahr der Fertigstellung | `1990` |
| **Anschaffungsjahr** | Jahr des Kaufs | `2020` |
| **Steuerjahr** | Betrachtetes Steuerjahr | `2025` |
| **Renovierungen** | Liste der durchgeführten Renovierungen | siehe unten |

#### Renovierungsdaten
Für jede Renovierungskategorie wird erfasst:
- **Kategorie** (z.B. Dach, Heizung, Fenster)
- **Umfang** in Prozent (0%, 25%, 50%, 75%, 100%)
- **Zeitfenster** (vor wie vielen Jahren: 0-5, 6-10, 11-15, 16-20, >20 Jahre oder "nicht")

---

### 2. Gesamtnutzungsdauer (GND) ermitteln

Jeder Immobilientyp hat eine festgelegte **Gesamtnutzungsdauer (GND)** gemäß der Tabelle „Immobilientypen & Standardpreise“. Die GND kann bei Bedarf manuell überschrieben werden (`gnd_override`).

---

### 3. Alter der Immobilie berechnen

```
Ermittlungsjahr = max(Anschaffungsjahr, Steuerjahr)
Ermittlungsjahr (gedeckelt) = min(Ermittlungsjahr, Baujahr + 75)
Alter = max(0, Ermittlungsjahr (gedeckelt) - Baujahr)
Relatives Alter = Alter / GND
```

**Beispiel:**
```
Baujahr: 1990
Anschaffungsjahr: 2020
Steuerjahr: 2025
GND: 80 Jahre

→ Ermittlungsjahr = max(2020, 2025) = 2025
→ Ermittlungsjahr (gedeckelt) = min(2025, 1990 + 75) = 2025
→ Alter = 2025 - 1990 = 35 Jahre
→ Relatives Alter = 35 / 80 = 0,4375 (43,75 %)
```

---

### 4. Renovierungs-Score berechnen

Der Score bewertet, wie gut die Immobilie renoviert wurde. Er reicht von **0 bis 20 Punkten**.

#### 4.1 Berechnung pro Renovierungskategorie

Für jede Kategorie (z. B. Dach, Heizung, Fenster) wird berechnet:

```
Punkte = Max. Punkte × Umfangsgewicht × Zeitfaktor
```

Die Maximalpunkte je Kategorie, die Umfangsgewichte und die Zeitfaktoren sind oben tabellarisch dokumentiert und stammen direkt aus den Seedern. Dadurch lässt sich jede Eingabe exakt zuordnen.

**Beispiel Dach:**
```
Kategorie: Dach / Wärmeschutz
Max. Punkte: 4
Umfang: 100 % → Gewicht 1,0
Zeitfenster: 0–5 Jahre → Faktor 1,0

→ Punkte = 4 × 1,0 × 1,0 = 4 Punkte
```

#### 4.2 Gesamtscore

Alle Kategorie-Punkte werden addiert, zunächst auf halbe Punkte gerundet und anschließend mit einer Stelle hinter dem Komma gespeichert:

```
Score (Roh) = Summe aller Kategorie-Punkte
Score (halbe Schritte) = round(Score × 2) / 2
Score (final) = round(min(20, max(0, Score (halbe Schritte))), 1)
```

**Beispiel:**
```
Dach: 4,0 Punkte
Heizung: 3,2 Punkte
Fenster: 2,4 Punkte
...
Summe: 14,7 Punkte

→ Score gerundet = round(14,7 × 2) / 2 = 15,0 Punkte
```

---

### 5. Formel auswählen (einfach vs. erweitert)

Je nach Score und Immobilienalter wird eine Formel ausgewählt:

#### Einfache Formel
```
RND = GND - Alter
```

Diese Variante greift, wenn keine erweiterte Formel ausgelöst wird – typischerweise bei jungen Objekten und niedrigen Scores.

#### Erweiterte Formel
```
RND = a × (Alter² / GND) - b × Alter + c × GND
```

Die Koeffizienten `a`, `b`, `c` stammen aus dem Score-Formelset. Die erweiterte Formel wird verwendet, wenn **entweder** das relative Alter ≥ `rel_alter_min` **oder** das absolute Alter ≥ `alter_schwelle` (derzeit 25 Jahre) ist. Fehlt ein vollständiger Koeffizientensatz, fällt die Logik auf die einfache Formel zurück.

**Beispiel erweiterte Formel:**
```
Score: 15,0
a = -0,0125
b = 0,5
c = 1,0
Alter = 35 Jahre
GND = 80 Jahre

RND = -0,0125 × (35² / 80) - 0,5 × 35 + 1,0 × 80
    = -0,0125 × (1225 / 80) - 17,5 + 80
    = -0,0125 × 15,3125 - 17,5 + 80
    = -0,1914 - 17,5 + 80
    = 62,31 Jahre
```

---

### 6. Intervall berechnen

Die berechnete RND wird in ein Intervall umgewandelt (standardmäßig in 5-Jahres-Schritten):

```
Schrittweite = 5 Jahre
Min = floor(RND / Schrittweite) × Schrittweite
Max = ceil(RND / Schrittweite) × Schrittweite

Wenn (Max - Min) < Schrittweite:
  Max = Min + Schrittweite

Max = min(GND, Max)
Min = max(0, Min)
```

**Beispiel:**
```
RND = 62,31 Jahre
Schritt = 5

→ Min = floor(62,31 / 5) × 5 = 60 Jahre
→ Max = ceil(62,31 / 5) × 5 = 65 Jahre
→ Intervall: 60-65 Jahre
```

---

### 7. AfA-Prozentsatz berechnen

Der jährliche Abschreibungssatz (AfA) wird aus der RND berechnet. Einzelwert und Intervallgrenzen werden jeweils auf zwei Nachkommastellen gerundet:

```
AfA (%) = round(100 / RND, 2)
AfA_from (%) = round(100 / RND_max, 2)
AfA_to (%) = round(100 / RND_min, 2)
```

**Beispiel:**
```
RND = 62,31 Jahre

→ AfA = round(100 / 62,31, 2) = 1,60 % pro Jahr
```

---

### 8. Empfehlung generieren

Grundlage ist die untere Grenze der Ersteinschätzung (RND_min). Sie steuert, ob direkt eine Gutachten-Beauftragung angeboten oder lediglich ein Kontakt empfohlen wird:

| Ersteinschätzung von | Empfehlung |
|----------------------|------------|
| < 50 Jahre | ✅ "Gutachten ist sinnvoll, Beauftragung empfehlen" |
| ≥ 50 Jahre | ❌ "Gutachten ist nicht sinnvoll, Kontaktaufnahme empfehlen" |

---

## 📊 Beispiel-Berechnung komplett

### Eingaben:
```
Immobilientyp: Einfamilienhaus (GND = 80 Jahre)
Baujahr: 1990
Anschaffungsjahr: 2020
Steuerjahr: 2025

Renovierungen:
- Dach: 100%, vor 3 Jahren (Zeitfenster: 0-5 Jahre)
- Heizung: 100%, vor 4 Jahren (Zeitfenster: 0-5 Jahre)
- Fenster: 75%, vor 8 Jahren (Zeitfenster: 6-10 Jahre)
- Elektrik: 50%, vor 12 Jahren (Zeitfenster: 11-15 Jahre)
- Sanitär: nicht renoviert
```

### Schritt 1: Alter berechnen
```
Ermittlungsjahr = max(2020, 2025) = 2025
Ermittlungsjahr (gedeckelt) = min(2025, 1990 + 75) = 2025
Alter = 2025 - 1990 = 35 Jahre
Relatives Alter = 35 / 80 = 0,4375
```

### Schritt 2: Score berechnen
```
Dach:     4,0 × 1,0 × 1,0 = 4,00 Punkte
Heizung:  4,0 × 1,0 × 1,0 = 4,00 Punkte
Fenster:  3,0 × 0,75 × 0,8 = 1,80 Punkte
Elektrik: 3,0 × 0,5 × 0,6 = 0,90 Punkte
Sanitär:  2,0 × 0,0 × 0,0 = 0,00 Punkte

Score (Roh) = 10,70 Punkte
Score (gerundet) = 11,0 Punkte
```

### Schritt 3: Formel anwenden
```
Score = 11,0 → erweiterte Formel wird verwendet
a = -0,015, b = 0,6, c = 1,05

RND = -0,015 × (35² / 80) - 0,6 × 35 + 1,05 × 80
    = -0,015 × 15,3125 - 21 + 84
    = -0,23 - 21 + 84
    = 62,77 Jahre (gerundet: 62,77)
```

### Schritt 4: Intervall und AfA
```
Intervall: 60-65 Jahre
AfA: round(100 / 62,77, 2) = 1,59 % pro Jahr
```

### Schritt 5: Empfehlung
```
RND ≥ 25 Jahre
→ "Gutachten ist sinnvoll, Beauftragung empfehlen"
```

---

## 📁 Relevante Dateien im Projekt

| Datei | Beschreibung |
|-------|--------------|
| `app/Services/RndCalculatorService.php` | Hauptlogik der Berechnung und AfA-Ermittlung |
| `app/Services/OfferBuilderService.php` | Aggregiert Netto-/Bruttopreise, Rabatte und MwSt. |
| `app/Models/Calculation.php` | Datenmodell für gespeicherte Berechnungen |
| `app/Models/ScoreFormulaSet.php` | Formelkoeffizienten pro Score |
| `app/Models/RenovationCategory.php` | Renovierungskategorien mit Max. Punkten |
| `app/Models/RenovationTimeFactor.php` | Zeitfaktoren nach Renovierungszeitpunkt |
| `app/Models/RenovationExtentWeight.php` | Gewichtungen nach Renovierungsumfang |
| `database/seeders/*.php` | Quelle sämtlicher Tabellenwerte (siehe Abschnitte oben) |

---

## 🔍 Debug-Informationen

Das System speichert Debug-Daten in jedem Calculation-Record:

```json
{
  "score_raw": 10.7,
  "score_rounded": 11.0,
  "relative_age": 0.4375,
  "use_advanced_formula": true,
  "formula": {
    "score": 11.0,
    "a": -0.015,
    "b": 0.6,
    "c": 1.05,
    "alter_schwelle": 25,
    "rel_alter_min": 0.3
  }
}
```

Diese Informationen helfen bei der Nachvollziehbarkeit und Fehlersuche.

---

## 💶 Preiszusammenstellung (Netto → Brutto)

Die Angebotspreise werden im `OfferBuilderService` auf Basis der oben genannten Seed-Daten berechnet:

1. **Netto-Positionen sammeln** – Basierend auf dem Immobilientyp (`price_standard_eur`) und optionalen GA-Paketen aus der `ga_pricings`-Tabelle entstehen Netto-Posten (`line_items`). Null-Werte führen zu „Preis auf Anfrage“.
2. **Rabatt anwenden** – Hinterlegte Rabattcodes reduzieren den Nettobetrag prozentual. Der Abzug wird vor der Umsatzsteuer berechnet und ist auf den Nettosubtotal begrenzt.
3. **MwSt. aufschlagen** – Auf den rabattbereinigten Netto-Endbetrag wird die Umsatzsteuer (derzeit 19 %) gerundet in ganzen Eurocent berechnet.
4. **Brutto ausweisen** – Der Bruttobetrag ergibt sich aus Netto minus Rabatt plus MwSt. und wird in der Oberfläche inkl. Steuer angezeigt.

Sämtliche Zwischenergebnisse (Netto, Rabatt, MwSt., Brutto) werden im Angebot gespeichert und können bei Bedarf in Mails oder PDFs wiederverwendet werden.

---

## ⚙️ Konfigurationsmöglichkeiten

### Intervall-Schrittweite ändern
Die Standard-Schrittweite für Intervalle beträgt 5 Jahre. Dies ist als Konstante definiert:

```php
private const DEFAULT_INTERVAL_STEP = 5;
```

### Neue Formeln hinzufügen
Formeln werden in der `score_formula_sets` Tabelle verwaltet. Jeder Score (0.0 bis 20.0) kann eine eigene Formel haben.

### Renovierungskategorien anpassen
Kategorien, deren Gewichtung (max_points) und Zeitfaktoren werden in der Datenbank verwaltet und können über das Admin-Interface angepasst werden.

---

## 📝 Hinweise

- **Validierung**: Alle Eingaben werden validiert. Fehlende oder ungültige Werte führen zu Fehlermeldungen.
- **Transaktionssicherheit**: Die gesamte Berechnung läuft in einer Datenbank-Transaktion.
- **Rundungen**: Score wird auf 0,5 Punkte (und anschließend auf eine Nachkommastelle) gerundet; RND und AfA auf 2 Dezimalstellen; Netto-/Bruttobeträge werden in ganzen Eurocent gespeichert.
- **Begrenzungen**: RND kann nie negativ sein und nicht größer als GND.
- **Score-Limit**: Der Score ist auf maximal 20,0 Punkte begrenzt.

---

## 🎯 Zusammenfassung

Die Berechnungslogik ist ein mehrstufiger Prozess:

1. **Eingaben validieren** → Sicherstellen, dass alle nötigen Daten vorhanden sind
2. **Alter berechnen** → Aus Baujahr und Ermittlungsjahr
3. **Score ermitteln** → Renovierungen bewerten (0-20 Punkte)
4. **Formel wählen** → Einfach oder erweitert, abhängig von Alter und Score
5. **RND berechnen** → Mit der gewählten Formel
6. **Intervall bilden** → In 5-Jahres-Schritten
7. **AfA berechnen** → 100 / RND
8. **Empfehlung geben** → Gutachten sinnvoll oder nicht

Das Ergebnis wird gespeichert und kann für Angebote und Berichte verwendet werden.
