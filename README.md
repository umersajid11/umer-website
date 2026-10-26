# Umer Sajid — Personal Dynamic Website (v2 upgrade)

## What is new in v2 (all in this folder, NOT deployed by this build)

- Hero: owner's real photo in a rounded gradient-border frame in the site
  header ONLY (not in the slider). Large hero slider uses 3 generated
  service/IT images, in order: `images/slider-website.webp` (website/IT),
  `images/slider-video.webp` (video editing),
  `images/slider-voiceover.webp` (voiceover/social) — clean copies of the
  supplied media-generation files. Slider auto-advances every 5s, has
  dots/arrows, pauses on hover or focus, and works on mobile.
  Photo file: `images/profile.jpg`
  (copied from the owner's Facebook profile DP reference — see note below).
- Intro: MS in Software Engineering + One-year diploma from China +
  motivational line: “Dream big, work honestly, and let every small step
  take you further.”
- Services expanded to 8 (Website Design, Website Fixing & Maintenance,
  Video Editing, Voiceover & Scripts, Social Media Management, Logo &
  Thumbnail Design, SEO & AdSense Setup, Content Writing), each with a
  beautiful detail page at `service.php?slug=...` and a “Start this service”
  button that pre-selects the service in the contact form.
- User registration is now detailed: full name, email, phone/WhatsApp
  (optional, encouraged), city, service needed, project detail (min.
  20 characters), budget range — stored in the `users` table. This filters
  casual messages so only serious work requests come through.
- Admin ↔ user messaging portal: new `messages` table
  (`user_id, sender_role admin/user, message, inquiry_id, created_at`).
  Admin picks a registered user, sees the full conversation and replies;
  the reply appears in that user's portal. Admin replies to an inquiry
  also land in the user's portal when the inquiry email matches a
  registered user. Users reply from their own dashboard.
- Admin login moved: NO Admin link in the front-page top navigation any
  more — only a discreet Admin link in the footer. User Panel stays
  prominent in the navigation. `admin/login.php` is restyled as a card.
- Admin dashboard rebuilt chart-wise (pure CSS, no external libraries):
  summary cards (clients, inquiries, users, messages), CSS bars for
  clients/inquiries by status, registered-user detail table, messaging
  area, Add Client, client/inquiry status updates.
- User panel rebuilt e-commerce-style: profile summary, services as
  product-like cards with View Details / Start, requests/orders with
  new/contacted/done badges, a new-request form, and messages with admin.
- Shared catalogue: `backend/services.php` (detail text, what's included,
  who it's for) and `backend/messages.php` (messaging helpers).
- Database: `database.sql` now includes the new `users` columns, a `slug`
  column on `services`, the 8 seed services, and the `messages` table.
  Existing live databases must run the ALTER statements at the top of
  `database.sql` before this code is deployed (otherwise registration
  falls back to the old 3-column insert and project detail is not stored).

## Photo note — please read before sharing widely

- `images/profile.jpg` is the owner's Facebook profile DP reference photo,
  verified as JPEG image bytes — but it is only **150×150 px, 4.1 KB**,
  so it will look soft if shown large. It is used for now exactly as
  supplied (no stock face, no other person). For a sharper hero, the owner
  should send a higher-resolution photo later; then simply replace
  `images/profile.jpg` — no code change is needed.
- No other personal photos have been sent yet, so slides 2–3 deliberately
  use gradients/icons, not photos of other people.

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

- **Facebook and Instagram profile links** in `index.php` are CONFIRMED
  (not placeholders): Facebook
  `https://www.facebook.com/profile.php?id=61595644480292` (Rang Birangi
  Duniya Page) and Instagram `https://www.instagram.com/ii_umermughal/`.
  Do not change them in this upgrade.
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
