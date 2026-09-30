# Clinic Demo

A small PHP + SQLite website for a clinic: a public site (clinic info, services, doctors and their schedules) and an admin panel to manage that content.

## Requirements

- PHP 8.1+ with the `pdo_sqlite` extension (`php -m | grep pdo_sqlite`)

## Setup

```sh
php database/init.php
```

This creates `database/clinic.sqlite` with all tables and a default admin user:

- Username: `admin`
- Password: `changeme`

Change the password after your first login. Running the script again is safe; it won't overwrite existing data.

## Run locally

```sh
php -S localhost:8000
```

- Public site: http://localhost:8000/public/
- Admin panel: http://localhost:8000/admin/

## Project layout

| Folder | Contents |
|---|---|
| `public/` | Public pages, `css/`, and uploaded doctor photos in `images/doctors/` |
| `admin/` | Admin panel pages and `css/` |
| `includes/` | Shared PHP: `config.php`, `db.php`, `functions.php` |
| `database/` | `schema.sql`, `init.php`, and the SQLite file (not committed) |

## Configuration

Settings live in `includes/config.php`: app name, timezone, database path, and the default admin login used by `init.php`.

Set the `APP_ENV` environment variable to `production` to hide PHP errors from visitors.

## Deployment notes

`includes/` and `database/` must never be reachable from the web. Each has an `.htaccess` that denies access on Apache (Hostinger). If the host allows it, point the web root at a folder that doesn't contain them.
