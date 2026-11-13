# PetPass Rescue

A PHP application that connects a pet's QR/NFC identity with its owner, rescue information, and pet-service partners.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-003545?logo=mariadb&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/License-MIT-green)

## Features

- Owner, partner, and administrator dashboards with role checks.
- Pet profiles, configurable public fields, media, and emergency contacts.
- QR generation and tag activation linked to public pet profiles.
- Lost-pet cases, sightings, and scan history.
- Map and directory filters for pet-related services.
- Partner profiles, products, services, appointments, hotel bookings, and restaurant reservations.
- CSRF protection, password hashing, prepared SQL queries, and upload MIME checks.

This public edition contains synthetic data only. Most interface text is Portuguese;
some labels also support Spanish. QR/NFC links are implemented; physical NFC writing
depends on the browser, HTTPS, and compatible hardware.

**Scope:** notifications are recorded/simulated internally. This is not an email,
SMS, payment, or live GPS integration. The FAQ bot uses stored keyword answers,
not a language model. Plan and GPS tables do not imply working payment or tracking services.

## Screenshots

**Screenshot 1:** `docs/screenshot-1.png`  
**Screenshot 2:** `docs/screenshot-2.png`

<!-- Screenshot slot: docs/screenshot-1.png — public home -->
<!-- Screenshot slot: docs/screenshot-2.png — login or owner dashboard -->
See [screenshot instructions](docs/screenshots.md). The filenames are placeholders for your local demo captures.

## Run locally

Requirements: PHP 8.1+, MySQL 8 or MariaDB 10.6+, and PHP extensions
`pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, and `session`.

1. Download or clone this repository and open its directory in a terminal.
2. Copy the environment example:

   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell: `Copy-Item .env.example .env`.

3. Create a **new, empty demo database** and a local database user. For example,
   run this as a database administrator; use the same values in `.env`:

   ```sql
   CREATE DATABASE petpass_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'petpass_demo'@'localhost' IDENTIFIED BY 'change-me-local-only';
   GRANT ALL PRIVILEGES ON petpass_demo.* TO 'petpass_demo'@'localhost';
   ```

4. Edit `.env` with your local host, port, database, user, and password. Set a demo
   login password of at least eight characters in `DEMO_PASSWORD`. Keep `APP_ENV=local`.
5. Install the schema and synthetic fixtures:

   ```bash
   php scripts/setup.php
   ```

   Setup refuses to change a database that already has tables. Do not import the
   seed directly: setup generates password hashes from your local configuration.

6. Start the application from the repository root:

   ```bash
   php -S localhost:8000 -t public public/router.php
   ```

7. Open <http://localhost:8000>. Use the password from `DEMO_PASSWORD` with:

   | Role | Demo account |
   | --- | --- |
   | Owner | `tutor@example.com` |
   | Admin | `admin@example.com` |
   | Pet shop | `petshop@example.com` |
   | Veterinary | `veterinaria@example.com` |
   | Hotel | `hotel@example.com` |
   | Restaurant | `restaurante@example.com` |

   Public pet: `/pet/PET-LUNA2026`. Unclaimed tag: `/activate/TAG-NOVA-7F2A9Q`;
   its demo activation secret also comes from `DEMO_PASSWORD`.

## Structure

```text
src/                 Controllers, views, authentication, routing, helpers
public/              The only web document root
public/assets/       CSS, JavaScript, local third-party assets, demo illustrations
public/uploads/      Runtime uploads (ignored)
config/              Environment loader and configuration
database/            Schema and synthetic seed template
scripts/             CLI setup and public-file checks
storage/             Runtime logs (ignored)
docs/                Architecture, scope, validation, screenshot slots
```

## Hosting

Use PHP hosting with MySQL/MariaDB and set the document root to `public/`.
Use HTTPS, set `APP_URL`, set `APP_ENV=production` and `APP_DEBUG=false`, and create
fresh accounts instead of exposing the seeded demo credentials. GitHub Pages and
static Vercel hosting cannot run this PHP application. The legacy web installer
was removed; database setup is a CLI operation.

## What I learned

This project was practice in separating public and private pet data, keeping
role-specific screens organized, and connecting tag identifiers to database records.
It also made the limits of simulated integrations clearer.

## Validation and license

See [validation](docs/validation.md), [architecture](docs/architecture.md), and
[public-edition changes](docs/public-edition.md). First-party code and demo artwork:
[MIT](LICENSE). Bundled dependencies retain their [own licenses](THIRD_PARTY_NOTICES.md).
