# Umer Sajid — Personal Dynamic Website (v1)

Folder-wise, cPanel-ready personal portfolio for Umer Sajid, MS in Software
Engineering student at the University of AJK, Muzaffarabad.

## What is included

- Front-end: modern 3D-style hero (CSS/JS only, no heavy libraries), About,
  Services, Contact/Inquiry form.
- Services listed: website building and fixing, video editing, voiceover,
  scripts, social media posts and management.
- Back-end: PHP + MySQL (`backend/config.php`, `backend/database.php`).
- Admin panel: `admin/login.php` (real session + password_verify login) +
  `admin/dashboard.php` (list clients, add a client, change client status,
  view recent inquiries) + `admin/change-password.php`.
- User panel: `user/register.php` + `user/login.php` (real session +
  password_verify login) + `user/dashboard.php` (services and that user's
  own inquiries, matched by email).
- One-time setup: `setup.php` creates the seed admin account (see below).
  DELETE setup.php after first use.
- Database: `database.sql` creates `users`, `admins`, `clients`, `services`,
  `inquiries` tables, plus starter service rows.

## Placeholders left in v1 (do not treat as verified)

- **Facebook and Instagram profile links** in `index.php` are placeholders
  (`facebook.com/placeholder-umersajid`, `instagram.com/placeholder-umersajid`).
  Replace them with Umer's exact profile URLs once confirmed.
- LinkedIn link is `https://linkedin.com/umersajid`, as supplied by Umer.
  It has not been independently verified in this build.
- **Authentication is real now (PHP sessions + password_hash /
  password_verify).** Admin and user dashboards redirect to their login
  pages when not logged in, and both have working logout links.
- **Seed admin credentials (PLACEHOLDER — MUST be changed on first login):**
  email `admin@umer-website.local`, password `ChangeMe123!`. This is NOT a
  real personal password; it exists only so the owner can log in once.
  Create it by running `setup.php` once after importing `database.sql`
  (setup.php refuses to run if an admin already exists), log in, and you
  will be forced to change the password on `admin/change-password.php`
  before the dashboard opens. Then DELETE `setup.php` from the server —
  do not leave it on a live site, and do not use the placeholder password
  with real client data.
- User accounts are self-registered at `user/register.php` (passwords are
  stored only as password_hash values, never in plain text).
- **No real credentials are in the code.** `backend/config.php` uses
  environment-style placeholders (`YOUR_DB_USERNAME`, `YOUR_DB_PASSWORD`).
  Never commit real passwords to GitHub.
- No hosting, GitHub, or InfinityFree deployment was attempted in this build.

## Run locally (XAMPP)

1. Copy the whole `umer-website` folder into XAMPP's `htdocs/` folder.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin, create a database, and import `database.sql`.
4. Set the database name/user/password in `backend/config.php`
   (or via `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` environment variables).
5. Open `http://localhost/umer-website/` in a browser.
6. Run `http://localhost/umer-website/setup.php` once to create the seed
   admin, log in at `admin/login.php`, change the placeholder password
   when forced, then delete `setup.php`.
7. Without a database, pages still load and show a clear
   "database not connected yet" message instead of crashing, and the
   login/dashboard pages redirect or show that notice instead of
   exposing data.

## Deploy to InfinityFree / cPanel (later, same folders — no rebuild)

1. Upload the contents of `umer-website/` to `public_html/` (InfinityFree
   file manager or cPanel File Manager / FTP).
2. In the host's control panel, create a MySQL database and user.
3. In phpMyAdmin, import `database.sql`.
4. Update `backend/config.php` with the host's database host, name, user
   and password (or set the environment variables if the host supports them).
5. Visit the site's live URL, run `setup.php` once, change the seed admin
   password, then delete `setup.php`.
6. Check Home (contact form saves to `inquiries`), Admin Dashboard (add a
   client, change its status) and User Panel (register, log in, see your
   own inquiries).
7. Replace the Facebook/Instagram placeholder links before sharing the site
   with clients.

Note: GitHub Pages alone cannot run this dynamic PHP/MySQL site — it is for
static files only. Use GitHub for code/revisions, and a PHP host
(InfinityFree for preview, paid cPanel later) for the live dynamic site.
