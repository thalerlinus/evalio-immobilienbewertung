# Laravel + Vue.js Projekt - Struktur Dokumentation

## 📁 Erstellte Startstruktur

Dieses Projekt wurde mit einer modernen Startstruktur eingerichtet, die folgende Komponenten und Layouts enthält:

### 🧭 Navigation & Layout

#### 1. **MainLayout** (`resources/js/Layouts/MainLayout.vue`)
- Haupt-Layout-Komponente für das gesamte Projekt
- Integriert Navbar und Footer
- Flexible Struktur für verschiedene Seitentypen
- Props für Titel, Benutzer-Authentifizierung und Footer-Anzeige

#### 2. **Navbar** (`resources/js/Components/Navbar.vue`)
- Moderne, responsive Navigation
- Automatische Benutzer-Authentifizierung
- Mobile-freundliches Hamburger-Menü
- Dropdown für eingeloggte Benutzer
- Deutsche Beschriftung

#### 3. **Footer** (`resources/js/Components/Footer.vue`)
- Umfassendes Footer mit Unternehmensinformationen
- Social Media Links
- Strukturierte Navigationslinks
- Kontaktinformationen und Öffnungszeiten
- Responsive Grid-Layout

### 📄 Seiten

#### 1. **Welcome** (`resources/js/Pages/Welcome.vue`)
- Moderne Startseite mit Hero-Section
- Feature-Übersicht
- Laravel Ecosystem Integration
- Gradient-Design mit Call-to-Action

#### 2. **About** (`resources/js/Pages/About.vue`)
- "Über uns" Seite mit Mission, Vision und Werten
- Strukturierte Inhaltsblöcke
- Responsive Grid-Layout

#### 3. **Services** (`resources/js/Pages/Services.vue`)
- Übersicht der angebotenen Dienstleistungen
- Icon-basierte Darstellung
- 6 Standard-Services mit Beschreibungen

#### 4. **Contact** (`resources/js/Pages/Contact.vue`)
- Kontaktseite mit Formular und Informationen
- Interaktives Kontaktformular
- Kontaktinformationen mit Icons
- Vue.js Reactive Form Handling

### 🛣️ Routen

Die folgenden Routen wurden eingerichtet (`routes/web.php`):

- `/` - Welcome (Startseite)
- `/about` - Über uns
- `/services` - Services
- `/contact` - Kontakt
- `/dashboard` - Dashboard (authentifiziert)

### 🎨 Design-Features

- **Tailwind CSS** für konsistentes Styling
- **Responsive Design** für alle Bildschirmgrößen
- **Indigo/Blue** Farbschema als Basis
- **Hover-Effekte** und Übergänge
- **Accessibility-freundlich** mit ARIA-Labels
- **Deutsche Texte** und Beschriftungen

### 🚀 Verwendung

#### Layout verwenden:
```vue
<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
</script>

<template>
    <MainLayout title="Seitentitel" :user="$page.props.auth?.user">
        <!-- Inhalt hier -->
    </MainLayout>
</template>
```

#### Navigation anpassen:
Die Navigation kann in `resources/js/Components/Navbar.vue` angepasst werden. Neue Links einfach in den entsprechenden Bereichen hinzufügen.

#### Footer anpassen:
Footer-Inhalte können in `resources/js/Components/Footer.vue` bearbeitet werden. Kontaktdaten, Links und Social Media sind einfach anpassbar.

### 🔧 Anpassungsmöglichkeiten

1. **Farben**: Tailwind-Klassen austauschen (z.B. `indigo-600` zu `blue-600`)
2. **Inhalte**: Texte in den Vue-Komponenten bearbeiten
3. **Navigation**: Links in der Navbar-Komponente hinzufügen/entfernen
4. **Footer**: Zusätzliche Abschnitte oder Links hinzufügen
5. **Seiten**: Neue Pages im `resources/js/Pages/` Ordner erstellen

### 📱 Responsive Features

- Mobile-First Design
- Hamburger-Menü für kleine Bildschirme
- Flexible Grid-Layouts
- Touch-freundliche Interaktionen
- Optimierte Abstände für verschiedene Bildschirmgrößen

### 🔒 Sicherheits-Features

- Authentifizierung durch Laravel Breeze
- CSRF-Schutz
- XSS-Schutz durch Vue.js
- Sanitized User Input in Formularen

Diese Struktur bietet eine solide Grundlage für die Entwicklung moderner Webanwendungen mit Laravel und Vue.js.
