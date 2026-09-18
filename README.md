# Upmonia

Upmonia is an uptime monitoring application for websites and services. It runs periodic health checks (HTTP, TCP, and other check types), automatically detects incidents, and sends alerts through configured channels.

## Features

- **Monitors** — periodic checks against HTTP/TCP and other targets
- **Incident tracking** — automatic recording of outage start/end times and a timeline
- **Alert channels** — notifications via channels such as email (Resend)
- **Status pages** — a public status page for selected monitors
- **Maintenance windows** — silence alerts during planned maintenance
- **Multi-tenant** — organization/membership-based access
- **Rollups** — raw check results are downsampled into 1-minute/1-hour rollups, with raw data kept for a limited retention window

## Tech Stack

- Laravel + Livewire
- Tailwind CSS (Vite)
- MySQL

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Local database runs via Docker:

```bash
docker compose up -d
php artisan migrate
```

Start the dev server:

```bash
composer run dev
```

This runs `php artisan serve`, the queue listener, and the Vite dev server together.

## Environment Variables

See `config/upmonia.php` for the key `.env` settings:

- `RESEND_API_KEY`, `RESEND_FROM` — outbound email (Resend)
- `DEFAULT_MONITOR_REGION` — the monitoring region label for this deployment
- `CHECK_RETENTION_HOURS` — how long raw check results are retained
- `UPMONIA_ALLOW_PRIVATE_TARGETS` — allow private/internal network targets in development (always disabled in production)
- `DEPLOY_TOKEN` — enables triggering migrate/optimize via the `/deploy` endpoint on hosting without SSH access

## License

This project is built on the [Laravel](https://laravel.com) framework.
