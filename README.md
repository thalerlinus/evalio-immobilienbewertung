# Laravel 12 + Vue.js 3 + Inertia.js Setup

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12-red" alt="Laravel 12">
<img src="https://img.shields.io/badge/Vue.js-3-green" alt="Vue.js 3">
<img src="https://img.shields.io/badge/Inertia.js-2-purple" alt="Inertia.js">
<img src="https://img.shields.io/badge/PHP-8.4-blue" alt="PHP 8.4">
<img src="https://img.shields.io/badge/Tailwind-3-cyan" alt="Tailwind CSS">
</p>

## Über dieses Projekt

Dies ist eine moderne Full-Stack Webanwendung basierend auf:

### Backend
- **Laravel 12** - Das neueste Laravel Framework
- **PHP 8.4** - Modernste PHP-Version
- **Laravel Sail** - Docker-basierte Entwicklungsumgebung
- **Laravel Horizon** - Queue-Management Dashboard
- **Laravel Sanctum** - API-Authentifizierung

### Frontend
- **Vue.js 3** - Reactive JavaScript Framework
- **Inertia.js 2** - SPA ohne API (Server-side rendering)
- **Vite** - Blitzschneller Build-Tool
- **Tailwind CSS 3** - Utility-first CSS Framework
- **Ziggy** - Laravel Route-Helper für JavaScript
- **Fontawesome** - Icons etc.

### Entwicklungstools
- **Laravel Breeze** - Einfache Authentifizierung
- **Laravel Pint** - PHP Code Style Fixer
- **PHPUnit** - PHP Testing Framework

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Projekt starten

### Voraussetzungen
- Docker & Docker Compose
- Git

### Installation & Start

1. **Repository klonen**
   ```bash
   git clone <repository-url>
   cd laravel-app
   ```

2. **Laravel Sail starten**
   ```bash
   ./vendor/bin/sail up -d
   ```

3. **Dependencies installieren** (falls noch nicht geschehen)
   ```bash
   ./vendor/bin/sail composer install
   ./vendor/bin/sail npm install
   ```

4. **Umgebungskonfiguration**
   ```bash
   ./vendor/bin/sail artisan key:generate
   cp .env.example .env
   ```

5. **Datenbank migrieren**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

6. **Frontend entwickeln**
   ```bash
   ./vendor/bin/sail npm run dev
   ```

### Verfügbare Dienste

- **Webserver**: http://localhost (Port 80)
- **Vite Dev Server**: http://localhost:5173
- **MySQL**: localhost:3306
- **Redis**: localhost:6379
- **Mailpit**: http://localhost:8025

### Nützliche Befehle

```bash
# Sail Container starten
./vendor/bin/sail up -d

# Sail Container stoppen
./vendor/bin/sail down

# Artisan Befehle ausführen
./vendor/bin/sail artisan <command>

# Composer Befehle
./vendor/bin/sail composer <command>

# NPM Befehle
./vendor/bin/sail npm <command>

# Tests ausführen
./vendor/bin/sail test

# Code-Style prüfen
./vendor/bin/sail pint
```

## Projektstruktur

### Frontend (Vue.js + Inertia)
```
resources/
├── js/
│   ├── app.js          # Haupteinstiegspunkt
│   ├── bootstrap.js    # Bootstrap & Axios Setup
│   ├── Components/     # Vue-Komponenten
│   ├── Layouts/        # Layout-Komponenten
│   └── Pages/          # Inertia-Seiten
├── css/
│   └── app.css         # Tailwind CSS
└── views/
    └── app.blade.php   # Haupt-Template
```

### Backend (Laravel)
```
app/
├── Http/
│   ├── Controllers/    # Controller
│   ├── Middleware/     # Middleware
│   └── Requests/       # Form Requests
├── Models/             # Eloquent Models
└── Providers/          # Service Provider
```

## About Laravel

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
