# SentryLink

SentryLink is a CodeIgniter 4 school event ticketing and gate-monitoring system for ACLC Mandaue Campus. It replaces paper tickets with digital tickets and QR codes for student entry monitoring.

## Main Features

- Role-based login for students, officers, admins, and directors
- Admin event management, activity setup, student records, ticket records, and reports
- CSV receipt import for paid ticket generation
- Student dashboard, tickets, notifications, profile, password settings, and live QR page
- Officer gate scanner, manual lookup, gate logs, and offline sync support
- Director monitoring dashboards, admissions, audit logs, events, and reports
- Live QR validation with replay protection and gate activity tracking
- Forgot-password and tokenized reset-password flow

## Current Implementation

The active application is the CodeIgniter 4 implementation in `app/`, served through `public/index.php`. Some older plain PHP files are retained for recovery/reference, but the CI4 routes and controllers are the main system.

Key files:

- `app/Config/Routes.php` - route definitions
- `app/Controllers/` - role controllers and API handlers
- `app/Libraries/PortalService.php` - shared business logic
- `app/Views/` - role dashboards and pages
- `database_schema.sql` - schema-only database structure safe for repository use
- `ci4_schema_upgrade.sql` - schema upgrade script for newer tables/columns
- `SYSTEM_DOCUMENTATION.md` - system documentation
- `System Design Blueprint.docx` - source design blueprint

## Requirements

- PHP 8.2 or newer
- MySQL or MariaDB
- Apache/XAMPP/LAMPP, or PHP built-in server for local development
- PHP extensions required by CodeIgniter, including `intl`, `mbstring`, and `mysqli`

## Local Setup

1. Place the project in your web server directory, for example `/opt/lampp/htdocs/syntrelink`.
2. Create a local `.env` from `env` if needed and configure your local app/database values.
3. Create a MySQL database named `syntrelink_db`.
4. Import `database_schema.sql`, then apply `ci4_schema_upgrade.sql` if your database is missing newer tables/columns.
5. Make sure the `writable/` directory is writable by the web server.
6. Run locally with Apache/LAMPP or with:

```bash
php spark serve --host 127.0.0.1 --port 8080
```

Then open `http://127.0.0.1:8080`.

## Git Notes

Runtime files are intentionally ignored:

- `.env`
- cache, logs, sessions, debugbar output, and uploads under `writable/`
- local database dumps containing user/session data, including `syntrelink_db.sql`
- local recovery zip/backups
- unrelated local Topless project artifacts found in the restored web root

Do not commit real production credentials. Keep deployment-specific secrets in `.env` or the server environment.
# SentryLink
