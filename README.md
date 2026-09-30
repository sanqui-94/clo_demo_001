# Clinic Demo

A small PHP + SQLite website for a clinic: a public site (clinic info, services, doctors and their schedules) and an admin panel to manage that content.

## Requirements

- PHP 8.1+ with the `pdo_sqlite` extension (`php -m | grep pdo_sqlite`)

## Initialize the database

`database/init.php` creates the SQLite database (`database/clinic.sqlite`), all its tables, and the admin user. Run it once per environment, from the project root:

```sh
php database/init.php
```

It prints the admin login:

```
Created admin user.
  Username: admin
  Password: 3f9c2a7be41d0c85
Save this password now; it will not be shown again.
```

The password is random and only ever shown here. Save it in a password manager. You can change it later from the admin settings page.

Running the script again is safe: it won't touch existing tables, data, or the admin user. It only runs from the command line; opening it in a browser returns 403.

### Lost the admin password

Delete the admin user and run the init script again to get a new password. Clinic content is kept.

```sh
php -r 'require "includes/db.php"; db()->exec("DELETE FROM admin_users WHERE username = \"admin\"");'
php database/init.php
```

## Run locally

From the project root, start PHP's built-in server **with `router.php`**:

```sh
php -S localhost:8000 router.php
```

- Public site: http://localhost:8000/public
- Admin panel: http://localhost:8000/admin

Always include `router.php`. It makes the built-in server behave like the production server:

- Folder URLs without a trailing slash (`/admin`) redirect to `/admin/`. Without the router, the page's links and redirects break and you get a 404.
- `includes/` and `database/` are blocked (403), as their `.htaccess` files do in production.

`router.php` is for local development only. Hostinger's web server never uses it, and if someone requests it directly there it returns 404.

## Deploy to Hostinger

1. Upload the project files (File Manager, FTP, or `git clone` over SSH).
2. Create the database, using **one** of these:
   - **With SSH** (available on most Hostinger web hosting plans): connect, `cd` into the project folder, and run `php database/init.php`. Save the printed password.
   - **Without SSH:** run `php database/init.php` on your own computer, save the printed password, then upload `database/clinic.sqlite` into the `database/` folder on the server.
3. Make sure the web server can write to the `database/` folder (permissions `755`), not just the `.sqlite` file. SQLite creates temporary files next to the database while saving changes. Doctor photos are saved in `public/images/doctors/`, so that folder must be writable too.
4. Check the private folders are blocked: open `https://your-site/database/clinic.sqlite` and `https://your-site/includes/config.php`. Both must return **403 Forbidden**, not a download or a page.

`includes/` and `database/` must never be reachable from the web. Each has an `.htaccess` that denies access. If the host allows it, keep the project outside `public_html` and serve only `public/` and `admin/`.

Set the `APP_ENV` environment variable to `production` on the server to hide PHP errors from visitors.

## Languages

The interface (buttons, labels, messages) is available in Spanish and English. Spanish is the default; the **ES | EN** links in the header switch language, and the choice is remembered in a cookie for a year.

Content entered in the admin (clinic description, services, doctor bios) is not translated: it shows as written in both languages.

Interface texts live in `includes/lang/es.php` and `includes/lang/en.php`. To add or change one:

1. Add the same key to **both** files, e.g. `'services.add' => 'Añadir servicio'` and `'services.add' => 'Add service'`.
2. Print it in a page with `<?= e(t('services.add')) ?>`. Placeholders work too: `t('welcome', ['name' => $name])` with `'welcome' => 'Hola, {name}'`.

A key missing in English falls back to Spanish; a key missing in both shows the key itself, so it's easy to spot.

The default language is `DEFAULT_LANG` in `includes/config.php`.

## Project layout

| Folder | Contents |
|---|---|
| `public/` | Public pages, `css/`, and uploaded doctor photos in `images/doctors/` |
| `admin/` | Admin panel pages and `css/` |
| `includes/` | Shared PHP: `config.php`, `db.php`, `functions.php`, `auth.php` (login, sessions, CSRF), `admin_layout.php`, `public_layout.php` (public site header, footer and clinic details), `schedules.php` (doctor schedules), `photos.php` (doctor photo uploads), `i18n.php` and `lang/` (translations) |
| `database/` | `schema.sql`, `init.php`, and the SQLite file (not committed) |
| `router.php` | Local development router for `php -S` (not used in production) |

## Configuration

Settings live in `includes/config.php`: app name, timezone, database path, the admin username used by `init.php`, `SESSION_TIMEOUT` (admins are logged out after 30 minutes of inactivity), and `MAX_PHOTO_BYTES` (largest doctor photo accepted, 2 MB). To raise the photo limit, PHP's `upload_max_filesize` and `post_max_size` must be raised too (on Hostinger: hPanel → PHP Configuration).

Environment variables:

| Variable | Default | Purpose |
|---|---|---|
| `APP_ENV` | `development` | Set to `production` on the server to hide PHP errors from visitors |
| `DB_PATH` | `database/clinic.sqlite` | Use a different database file, e.g. a throwaway one for testing: `DB_PATH=/tmp/test.sqlite php database/init.php` |
| `BOOKING_URL` | *(empty)* | Online appointment system opened by the "Pedir cita" button on doctor pages. While empty, the button calls the clinic's phone (or emails it if there is no phone) |

If the database file is missing or empty, pages fail with "Database not found … Run: php database/init.php" instead of creating an empty file.
