# Clinic Demo

A small PHP + SQLite website for a clinic: a public site (clinic info, services, specialties, doctors and their schedules) and an admin panel to manage that content.

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

Run it again after pulling changes that add tables to `schema.sql`, e.g. specialties (`specialties`, `doctor_specialties`). It adds the missing tables and leaves existing data alone. This applies to the live site too.

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

The whole project goes into the site's `public_html` folder. The root `.htaccess` then:

- sends the bare domain to the homepage in `public/`
- blocks `.git/`, `README.md` and `router.php` (404); `includes/` and `database/` have their own `.htaccess` (403)
- turns off folder listings
- sets `APP_ENV=production`, which hides PHP errors from visitors

URLs: `https://your-domain/` (redirects to `/public/`) and `https://your-domain/admin/`.

### 1. Prepare the hosting (hPanel)

1. **PHP version:** Advanced → PHP Configuration → choose PHP **8.1 or newer** (8.3 recommended).
2. **Extensions:** in the same screen, under PHP Extensions, check `pdo_sqlite` is enabled.
3. **HTTPS:** Security → SSL → install the free certificate, and turn on "Force HTTPS". The admin login cookie is only sent over HTTPS once it's on.
4. **Empty `public_html`:** in File Manager, delete Hostinger's placeholder files (e.g. `default.php`). The Git deploy needs an empty folder.

### 2. Upload the code

**With Git (recommended, updates are one click):** Advanced → Git → Create a new repository:

- Repository: `https://github.com/sanqui-94/clo_demo_001.git`
- Branch: `main`
- Directory: leave empty (deploys into `public_html`)

Then press **Deploy**. To update the site later, merge to `main` and press Deploy again. The database and uploaded photos are not in git, so deploys don't touch them.

**Without Git:** upload the project files into `public_html` with File Manager or FTP, keeping the folder structure. Include the hidden `.htaccess` files (root, `includes/`, `database/`).

### 3. Create the database

**With SSH** (Advanced → SSH Access → enable; it shows the command, usually `ssh -p 65002 u123456789@your-server-ip`):

```sh
cd domains/your-domain/public_html
php -v                     # must be 8.1+; if not, use the full path, e.g. /opt/alt/php83/usr/bin/php
php database/init.php      # prints the admin password once: save it
```

**Without SSH:** run `DB_PATH=/tmp/clinic.sqlite php database/init.php` on your own computer, save the printed password, and upload `/tmp/clinic.sqlite` as `public_html/database/clinic.sqlite`. Don't upload your local development database unless you want its content live.

### 4. Permissions

Folders `755`, files `644` (Hostinger's defaults). PHP runs as your hosting user, so it can write to:

- `database/` (the folder, not only the `.sqlite` file: SQLite creates temporary files next to it while saving)
- `public/images/doctors/` (uploaded photos)

### 5. Check the deploy

| Open | Expected |
|---|---|
| `https://your-domain/` | Redirects to `/public/` and shows the homepage |
| `https://your-domain/admin/` | Login page; the password from step 3 works |
| `https://your-domain/database/clinic.sqlite` | **403** (never a download) |
| `https://your-domain/includes/config.php` | **403** |
| `https://your-domain/.git/config` | **404** |
| `https://your-domain/public/images/doctors/` | **403** (no file list) |

Then upload a doctor photo in the admin and check it shows on the staff page. That proves both writable folders work.

To confirm PHP errors are hidden, create `public_html/public/env-check.php` containing `<?php require __DIR__ . '/../includes/config.php'; var_dump(APP_ENV);`, open it (it must print `string(10) "production"`), then **delete it**.

### Backups

Everything the admin enters lives in `database/clinic.sqlite` and `public/images/doctors/`. Download both before risky changes; Hostinger's own backups (Files → Backups) also include them.

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
