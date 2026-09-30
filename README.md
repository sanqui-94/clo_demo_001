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

```sh
php -S localhost:8000
```

- Public site: http://localhost:8000/public/
- Admin panel: http://localhost:8000/admin/

## Deploy to Hostinger

1. Upload the project files (File Manager, FTP, or `git clone` over SSH).
2. Create the database, using **one** of these:
   - **With SSH** (available on most Hostinger web hosting plans): connect, `cd` into the project folder, and run `php database/init.php`. Save the printed password.
   - **Without SSH:** run `php database/init.php` on your own computer, save the printed password, then upload `database/clinic.sqlite` into the `database/` folder on the server.
3. Make sure the web server can write to the `database/` folder (permissions `755`), not just the `.sqlite` file. SQLite creates temporary files next to the database while saving changes.
4. Check the private folders are blocked: open `https://your-site/database/clinic.sqlite` and `https://your-site/includes/config.php`. Both must return **403 Forbidden**, not a download or a page.

`includes/` and `database/` must never be reachable from the web. Each has an `.htaccess` that denies access. If the host allows it, keep the project outside `public_html` and serve only `public/` and `admin/`.

Set the `APP_ENV` environment variable to `production` on the server to hide PHP errors from visitors.

## Project layout

| Folder | Contents |
|---|---|
| `public/` | Public pages, `css/`, and uploaded doctor photos in `images/doctors/` |
| `admin/` | Admin panel pages and `css/` |
| `includes/` | Shared PHP: `config.php`, `db.php`, `functions.php` |
| `database/` | `schema.sql`, `init.php`, and the SQLite file (not committed) |

## Configuration

Settings live in `includes/config.php`: app name, timezone, database path, and the admin username used by `init.php`.
