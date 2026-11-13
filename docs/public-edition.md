# Public edition changes

- Moved `app/` to `src/`; updated the loader and view paths.
- Added a dedicated `public/` document root and local router.
- Updated upload creation/deletion paths for the new document root.
- Replaced database configuration literals with environment values.
- Removed the duplicate combined installer SQL, old migrations, business-directory
  imports, old release reports, upload contents, and web installer.
- Kept one schema and one explicitly synthetic seed template.
- Removed all original account hashes; CLI setup creates new demo hashes.
- Replaced personal names, emails, phone numbers, addresses, and identifiers.
- Removed real business directory entries and replaced them with two fictional entries.
- Replaced the original raster hero with an original demo SVG.
- Removed unused Leaflet source distributions and source maps; preserved runtime files.

Routing and business workflows were retained. No email sender, payment processor,
GPS service, or LLM integration was added. Existing security checks are useful
building blocks; this edition is a portfolio demo, not a production security audit.
