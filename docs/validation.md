# Validation

Verified during public-edition preparation:

- PHP 8.4.27 syntax: 59 application/configuration/entry/setup files passed.
- Owned browser JavaScript: Node.js syntax check passed.
- MariaDB 11.8.6 bootstrap: the complete schema and synthetic seed imported successfully
  with newly generated password hashes.
- HTTP checks: `/login`, `/register`, and `/partner/register` returned 200 and CSRF fields.
- `/.env`, `/config/config.php`, `/database/schema.sql`, `/src/bootstrap.php`, and
  the removed `/install` route returned 404 through the local router.
- First-party source contact/credential scan and packaged-file inspection passed.

Limitations:

- A running PHP-to-MySQL integration session was not available in this environment;
  authenticated workflows, role transitions, uploads, tag activation, booking,
  and lost-mode actions still need an end-to-end check with local MySQL/MariaDB.
- Browser screenshot automation was unavailable; the screenshot paths remain explicit
  placeholders. No successful visual QA or physical NFC writing test is claimed.
- The included CI workflow adds PHP syntax and CLI database setup checks on GitHub.
