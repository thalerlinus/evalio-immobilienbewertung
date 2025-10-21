# Berechnungslogik der Immobilienbewertung

Diese Dokumentation erklärt Schritt für Schritt, wie die Berechnung der Restnutzungsdauer (RND) und der AfA-Prozentsätze in der Evalio-Anwendung funktioniert.

---

## 📋 Übersicht

Das System berechnet die **Restnutzungsdauer (RND)** einer Immobilie basierend auf:
- Gebäudealter
- Immobilientyp und dessen Gesamtnutzungsdauer (GND)
- Durchgeführten Renovierungen und deren Umfang
- Zeitpunkt der Renovierungen

Das Ergebnis wird verwendet, um eine Empfehlung für die steuerliche Abschreibung (AfA) zu geben.

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

Jeder Immobilientyp hat eine festgelegte **Gesamtnutzungsdauer (GND)**:

```
Beispiele:
- Einfamilienhaus: 80 Jahre
- Mehrfamilienhaus: 80 Jahre
- Gewerbeobjekt: 50 Jahre
```

Die GND kann auch manuell überschrieben werden (`gnd_override`).

---

### 3. Alter der Immobilie berechnen

```
Ermittlungsjahr = max(Anschaffungsjahr, Steuerjahr)
Alter = Ermittlungsjahr - Baujahr
Relatives Alter = Alter / GND
```

**Beispiel:**
```
Baujahr: 1990
Anschaffungsjahr: 2020
Steuerjahr: 2025
GND: 80 Jahre

→ Ermittlungsjahr = max(2020, 2025) = 2025
→ Alter = 2025 - 1990 = 35 Jahre
→ Relatives Alter = 35 / 80 = 0,4375 (43,75%)
```

---

### 4. Renovierungs-Score berechnen

Der Score bewertet, wie gut die Immobilie renoviert wurde. Er reicht von **0 bis 20 Punkten**.

#### 4.1 Berechnung pro Renovierungskategorie

Für jede Kategorie (z.B. Dach, Heizung, Fenster) wird berechnet:

```
Punkte = Max. Punkte × Umfangsgewicht × Zeitfaktor
```

| Faktor | Beschreibung | Werte |
|--------|--------------|-------|
| **Max. Punkte** | Maximale Punktzahl dieser Kategorie | z.B. Dach = 4 Punkte |
| **Umfangsgewicht** | Gewichtung nach Renovierungsumfang | 0% = 0, 25% = 0,25, 50% = 0,5, 75% = 0,75, 100% = 1,0 |
| **Zeitfaktor** | Gewichtung nach Zeit seit Renovation | 0-5 Jahre = 1,0; 6-10 Jahre = 0,8; 11-15 Jahre = 0,6; usw. |

**Beispiel Dach:**
```
Kategorie: Dach
Max. Punkte: 4
Umfang: 100%
Zeitfenster: 0-5 Jahre

→ Umfangsgewicht = 1,0
→ Zeitfaktor = 1,0
→ Punkte = 4 × 1,0 × 1,0 = 4 Punkte
```

#### 4.2 Gesamtscore

Alle Kategorie-Punkte werden addiert und auf halbe Punkte gerundet:

```
Score (Roh) = Summe aller Kategorie-Punkte
Score (gerundet) = round(Score × 2) / 2
Score (final) = min(20, max(0, Score gerundet))
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

Wird verwendet bei jungen Immobilien ohne größere Renovierungen.

#### Erweiterte Formel
```
RND = a × (Alter² / GND) - b × Alter + c × GND
```

Wird verwendet, wenn:
- Das relative Alter größer als ein Schwellenwert ist ODER
- Das absolute Alter größer als ein Schwellenwert ist

Die Koeffizienten `a`, `b`, `c` sind in der Datenbank hinterlegt und abhängig vom Score.

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
Schritt = 5 Jahre
Min = floor(RND / Schritt) × Schritt
Max = ceil(RND / Schritt) × Schritt

Wenn (Max - Min) < Schritt:
    Max = Min + Schritt

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

Der jährliche Abschreibungssatz (AfA) wird aus der RND berechnet:

```
AfA (%) = 100 / RND
```

**Beispiel:**
```
RND = 62,31 Jahre

→ AfA = 100 / 62,31 = 1,60% pro Jahr
```

---

### 8. Empfehlung generieren

Basierend auf der RND wird eine Empfehlung gegeben:

| RND | Empfehlung |
|-----|------------|
| ≥ 25 Jahre | ✅ "Gutachten ist sinnvoll, Beauftragung empfehlen" |
| < 25 Jahre | ❌ "Gutachten ist nicht sinnvoll, keine Beauftragung ermöglichen" |

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
AfA: 100 / 62,77 = 1,59% pro Jahr
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
| `app/Services/RndCalculatorService.php` | Hauptlogik der Berechnung |
| `app/Models/Calculation.php` | Datenmodell für gespeicherte Berechnungen |
| `app/Models/ScoreFormulaSet.php` | Formelkoeffizienten pro Score |
| `app/Models/RenovationCategory.php` | Renovierungskategorien mit Max. Punkten |
| `app/Models/RenovationTimeFactor.php` | Zeitfaktoren nach Renovierungszeitpunkt |
| `app/Models/RenovationExtentWeight.php` | Gewichtungen nach Renovierungsumfang |

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
- **Rundungen**: RND wird auf 2 Dezimalstellen gerundet, AfA ebenfalls.
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
