# Architecture

`public/index.php` defines routes and delegates to controllers in `src/Controllers`.
`src/bootstrap.php` loads environment-backed configuration, starts a session, and
registers the class loader. Controllers use `Database` (PDO) and render PHP views.
Role middleware protects owner, partner, and admin routes. Public profiles query
only the contact/media visibility flags used by their views.

The database schema is separate from the seed template. `scripts/setup.php` checks
that the database is empty, installs the schema, hashes the configured demo password,
then loads synthetic records. Upload creation and deletion use `PUBLIC_PATH` after
the document-root move. Stored upload URLs remain relative to the public root.

The public folder contains only application entry points, static assets and uploads.
Source, configuration, SQL, logs, and `.env` live outside the web document root.
