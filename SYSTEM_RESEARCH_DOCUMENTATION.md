# SentryLink System Research Documentation

Last verified against code + live DB: 2026-04-14  
Repository root: `/opt/lampp/htdocs/syntrelink`

## 1. Canonical Documentation Status

This is the canonical handoff document for the current implementation.

- `README.md` is unrelated to SentryLink (it documents a different project) and must not be used as system truth.
- `SentryLink_System_Plan_v3.md` is a historical/planning artifact, not an implementation contract.
- `SYSTEM_DOCUMENTATION.md` is generally useful but not as strict/updated as this file for implementation-level behavior.

For future AI/code contributors:

- Treat this file + current `app/` code as source of truth.
- Do not add new tables, new flows, or new roles unless explicitly requested by stakeholders.
- Prefer aligning code to existing patterns over introducing new architecture.

## 2. Current Runtime Stack

- Framework: CodeIgniter `4.7.2`
- PHP: `8.3.6` (runtime observed)
- Composer requirement: PHP `^8.2`
- DB driver: `MySQLi`
- DB engine/version observed: MariaDB `10.4.32`
- Session storage: filesystem (`writable/session`)
- Timezone: `Asia/Manila` (`Config\App::$appTimezone`)
- Environment default: development (`.env`)
- Mail: SMTP via `App\Libraries\MailerService`, loading PHPMailer from `syntrelink/PHPMailer/src/*`

## 3. Active Code Areas

Primary implementation is in:

- `app/Controllers/*`
- `app/Libraries/*`
- `app/Helpers/portal_helper.php`
- `app/Views/*`
- `app/Config/*`

Legacy compatibility tree:

- `syntrelink/` exists, but CI4 routes handle old entry URLs by redirecting into new portals.

## 4. Role Model and Access Rules

Normalized roles:

- `student`
- `ssg` (officer)
- `admin`
- `director`

Role normalization:

- Legacy `staff` is normalized to `ssg` in `PortalService::normalizeRole()`.

Filters:

- `guest` filter redirects authenticated users away from login pages.
- `role` filter enforces route-level role allowlists.

## 5. Route Contract (Actual)

Defined in `app/Config/Routes.php` and verified via `php spark routes` on 2026-04-14.

### 5.1 Web routes by portal

- `/` and `/dashboard` -> `Home::index` (role redirect)
- Student auth/pages under `/s/*`
- Officer auth/pages under `/o/*`
- Admin auth/pages under `/admin/*`
- Director auth/pages under `/director/*`

### 5.2 Legacy redirects kept

- `/login.php` -> `/s/auth/login`
- `/loginadmin.php` -> `/admin/auth/login`
- `/forgot_password.php` -> `/s/auth/forgot-password`
- `/logout.php` -> `/s/auth/logout`
- `/officers/loginofficers.php` -> `/o/auth/login`
- Same aliases under `/syntrelink/...` path variants

### 5.3 API routes currently registered

- `GET /api/qr/generate` -> `StudentController::generateQr` (`student`)
- `POST /api/qr/validate` -> `OfficerController::validateQr` (`ssg`)
- `GET /api/gate-log/{eventId}` -> `OfficerController::gateLogFeed` (`ssg,admin,director`)
- `GET /api/notifications` -> `StudentController::notificationsFeed` (all roles)
- `POST /api/notifications/read` -> `StudentController::markNotificationsRead` (all roles)
- `GET /api/events/{eventId}/attendee-cache` -> `OfficerController::attendeeCache` (`ssg`)

### 5.4 Important route gaps (code exists, route missing)

The following controller methods exist but are not currently mapped in `Routes.php`:

- `StudentController::ticketStateFeed()`
- `OfficerController::gateActivityStateFeed()`

Views already call these expected endpoints:

- `/api/student/ticket-state`
- `/api/gate-activity-state`

This mismatch is a current implementation gap to keep in mind during maintenance.

## 6. Core Implemented Business Flows

### 6.1 Authentication and session concurrency

Source: `PortalService::login()`, `resolveAuthenticatedUser()`, `hasActiveSessionConflict()`.

- Login checks:
  - account exists and active (`is_active = 1`, `deleted_at IS NULL`)
  - role allowed by portal
  - password hash verifies
  - session conflict window check (single active session policy)
- On successful login:
  - new random `session_token` stored in `users`
  - session ID regenerated
  - role/user/session token stored in session
- Session validity requires:
  - matching DB `session_token`
  - matching normalized role
  - account still active and not soft-deleted
- Heartbeat:
  - updates `session_last_seen_at` periodically
- Logout:
  - nulls current DB token if it matches
  - destroys local session state

### 6.2 Student first-login email setup

Source: `PortalService::login()` + `AuthController::emailSetup()`.

- If `student` has `email_verified = 0`, login redirects to `/s/settings/email-setup`.
- Email setup flow:
  - send 6-digit code
  - code validity: 10 minutes
  - updates `users.email`, `users.email_verified = 1`
  - establishes authenticated session and logs `EMAIL_VERIFIED`

### 6.3 Event lifecycle and gate preparation

Source: `BaseController::closeExpiredEvents()`, `AdminController::events()`, `PortalService::prepareEventGate()`.

- Event statuses in use:
  - `draft`, `open`, `ongoing`, `closed`, `cancelled`
- Auto-close behavior:
  - on every controller boot, expired events with active-like statuses are set to `closed`
- Admin prepare/start gate:
  - validates event exists and not ended/cancelled
  - rebuilds `event_attendee_cache` from paid/free valid tickets
  - sets event status to `ongoing`

### 6.4 Ticket import flow (CSV receipts)

Source: `AdminController::importReceipts()`, `importConfirm()`, `importSuccess()`.

- Upload CSV with `receipt_id` + `student_no`
- Validation rejects:
  - missing values
  - duplicates within CSV
  - unknown student
  - existing receipt in DB
  - existing ticket for student+event
- Confirm step inserts tickets in transaction:
  - `payment_status = 'paid'`
  - generated `ticket_code` (`TKT-YYYY-XXXXXX`)
  - notification to each student
  - audit action `CSV_RECEIPT_IMPORT`

### 6.5 QR generation and gate validation

Source: `PortalService` + `OfficerController::validateQr()` + `StudentController::generateQr()`.

Live QR token:

- HMAC-signed payload with `uid`, `sid`, `eid`, `jti`, `iat`, `exp`
- expires in 10 seconds with 5-second validation grace

Download QR token:

- payload includes `kind=download`, ticket id, and persistent key (`ptk`)
- key stored in `tickets.download_qr_key`
- one successful scan consumes key (set to NULL)

Validation behavior:

- signature + payload structure checked
- event match checked
- event must be `ongoing` and not ended
- ticket must be paid/free and active
- live QR one-time replay protection via `qr_blacklist.token_jti`
- gate state toggles `in` <-> `out` based on latest admission log

## 7. Data Contract (Live DB Verified)

Live database: `syntrelink_db`  
Verification source: `information_schema` + `SHOW CREATE TABLE` queries against running MariaDB.

### 7.0 Current table inventory

- `users`
- `events`
- `activities`
- `tickets`
- `admissions`
- `notifications`
- `audit_logs`
- `qr_blacklist`
- `event_attendee_cache`
- `ci_sessions`
- `login_attempts`

### 7.1 Live row-count snapshot (2026-04-14)

- `users`: 9
- `events`: 4
- `activities`: 3
- `tickets`: 3
- `admissions`: 2
- `notifications`: 1
- `audit_logs`: 28
- `qr_blacklist`: 14
- `event_attendee_cache`: 1
- `ci_sessions`: 0
- `login_attempts`: 0

### 7.2 `users` (verified schema)

- Columns:
  - `id`, `student_id`, `first_name`, `last_name`, `email`, `password_hash`
  - `session_token`, `session_last_seen_at`
  - `role` enum(`student`,`ssg`,`admin`,`director`)
  - `house`, `year_level`, `course`, `profile_photo`
  - `is_active`, `email_verified`, `created_at`, `updated_at`, `deleted_at`
- Unique:
  - `student_id`, `email`
- Important:
  - No `name` column in live schema.
  - No legacy `password` column in live schema.

### 7.3 `events` (verified schema)

- `id`, `title`, `description`, `venue`
- `event_date`, `start_time`, `end_time`
- `is_free`, `ticket_price`, `max_capacity`
- `status`, `created_by`, `deleted_at`, `created_at`, `updated_at`
- `status` enum values: `draft`, `open`, `ongoing`, `closed`, `cancelled`

### 7.4 `tickets` (verified schema)

- `id`, `user_id`, `event_id`, `ticket_code`, `receipt_id`, `payment_status`
- `download_qr_key`, `download_qr_created_at`
- `payment_verified_by`, `issued_at`, `updated_at`, `deleted_at`
- Unique:
  - `ticket_code`
  - `receipt_id`
  - `uk_user_event (user_id, event_id)`

### 7.5 `admissions` (verified schema)

- `id`, `ticket_id`, `user_id`, `event_id`, `scanned_by`
- `scanned_at`, `gate_location`, `status`, `rejection_reason`

Status behavior:

- Column type is `varchar(20)` with default `'admitted'` (not enum-restricted).
- Live data currently contains: `in`, `out`.
- Code still supports legacy/read values: `admitted`, `duplicate`, `rejected`.

### 7.6 Other tables used by app logic

- `notifications`: `id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`
- `audit_logs`: `id`, `user_id`, `action`, `target_type`, `target_id`, `ip_address`, `user_agent`, `created_at`
- `qr_blacklist`: `token_jti`, `user_id`, `event_id`, `used_at`
- `event_attendee_cache`: `event_id`, `user_id`, `student_id`, `full_name`, `course`, `year_level`, `payment_status`, `generated_at`
- `activities`: `event_id`, `title`, `type`, `house_name`, `start_time`, `end_time`, `venue_area`, `description`, `deleted_at`

### 7.7 System tables present in DB

- `ci_sessions`
  - `id`, `ip_address`, `timestamp`, `data`
  - Exists in DB but runtime is currently configured for file sessions.
- `login_attempts`
  - `id`, `ip_address`, `identifier`, `attempt_count`, `last_attempt`, `locked_until`
  - Present but no active controller flow currently writes/reads it.

### 7.8 Foreign-key map (with delete behavior)

- `activities.event_id -> events.id` (`ON DELETE CASCADE`)
- `admissions.ticket_id -> tickets.id` (`ON DELETE CASCADE`)
- `admissions.user_id -> users.id` (`ON DELETE CASCADE`)
- `admissions.event_id -> events.id` (`ON DELETE CASCADE`)
- `admissions.scanned_by -> users.id` (`ON DELETE CASCADE`)
- `audit_logs.user_id -> users.id` (`ON DELETE SET NULL`)
- `events.created_by -> users.id` (`ON DELETE SET NULL`)
- `event_attendee_cache.event_id -> events.id` (`ON DELETE CASCADE`)
- `event_attendee_cache.user_id -> users.id` (`ON DELETE CASCADE`)
- `notifications.user_id -> users.id` (`ON DELETE CASCADE`)
- `tickets.user_id -> users.id` (`ON DELETE CASCADE`)
- `tickets.event_id -> events.id` (`ON DELETE CASCADE`)
- `tickets.payment_verified_by -> users.id` (`ON DELETE SET NULL`)

## 8. Helper/Utility Contract

`app/Helpers/portal_helper.php` currently provides shared UI/date/status functions used across controllers/views, including:

- URL/escape/layout helpers (`app_url`, `h`, `shell_*`)
- time helpers (`portal_timezone`, `portal_now`, `event_has_ended`)
- status helpers (`ticket_status_badge/label`, `event_status_badge/label`, `admission_status_badge/label`)

Do not remove/rename these without updating all dependent controllers/views.

## 9. Known Drift and Risks

### 9.1 Known code/document mismatch fixed in this doc

- Older references to CAPTCHA/ALTCHA/Turnstile are not present in current auth code.
- Current login forms do not perform CAPTCHA challenge checks.

### 9.2 Known runtime/code issues to keep visible

- API route gaps for ticket-state and gate-activity-state (see section 5.4).
- `app/Controllers/ApiController.php` is currently a stray/corrupted file and not part of active routing.
- The custom helper functions `portal_now` and `event_has_ended` were restored recently because missing helper definitions caused runtime failure.
- `PortalService::login()` still has legacy fallback to `$user['password']`, but live `users` table has no `password` column.
- `app/Commands/RepairSystem.php` and `ci4_schema_upgrade.sql` still reference legacy columns/shape (`users.name`, `users.password`, `role='staff'`) that do not exist in current live schema; running them on this schema may fail and should be reviewed before execution.
- Three role views are currently truncated and do not call `shell_end()`:
  - `app/Views/admin/students.php`
  - `app/Views/officer/dashboard.php`
  - `app/Views/student/dashboard.php`
- `officer/manual_lookup` view expects `$result['admission_count']`, but `OfficerController::manualLookup()` does not select `admission_count`.
- Password eye icon exists in admin create forms (`admin/students`, `admin/accounts`) but no JS handler currently runs there, so toggle behavior is not implemented on those screens.

## 10. Guardrails for Future AI Contributors

When continuing this project:

1. Do not invent new tables or statuses unless explicitly requested.
2. Preserve role names exactly (`student`, `ssg`, `admin`, `director`).
3. Preserve event statuses exactly (`draft`, `open`, `ongoing`, `closed`, `cancelled`) unless requirements change.
4. Preserve gate-state behavior (`in`/`out` toggle) and compatibility for legacy `admitted`.
5. Keep legacy URL redirects in place unless a migration/deprecation plan is approved.
6. Keep CSV import validation strict; do not auto-ignore duplicates.
7. Keep single-active-session behavior unless explicit policy change is requested.
8. Treat this file as a required update target whenever flows/routes/schema change.

## 11. Quick Verification Checklist (Before Any Major Change)

- `php -l app/Helpers/portal_helper.php`
- `php spark --version`
- `php spark routes` (check route map before documenting endpoint availability)
- Verify any new SQL references against existing tables/columns in codebase

## 12. Authentication, Password, and Account Security Details

### 12.1 Password storage and verification

- Passwords are stored as bcrypt hashes using PHP `password_hash(..., PASSWORD_BCRYPT)`.
- Login uses `password_verify()` against `users.password_hash`.
- No plaintext password column exists in live `users` table.
- Temporary passwords from reset flow are generated server-side and then hashed before database update.

### 12.2 Password policy enforcement points

- Policy checks are applied when admin creates student/staff accounts via `PortalService::passwordPolicyErrors()`.
- Current policy:
  - minimum length 8
  - at least one uppercase letter
  - at least one number
  - at least one symbol

### 12.3 Password reset behavior (all roles)

- Guest flow:
  - per-role forgot-password pages (`/s|o|admin|director/auth/forgot-password`)
  - only active + verified email accounts can reset
- Authenticated flow:
  - settings page asks user to re-enter currently bound verified email
  - on success, system generates and emails a temporary password
- Reset side effects:
  - updates `users.password_hash`
  - clears `session_token` + `session_last_seen_at`
  - adds audit log
  - sends in-app notification

### 12.4 Password show/hide UI behavior

- Login page (`auth/login.php`) has a working eye toggle:
  - toggles input type `password` <-> `text`
  - updates icon `visibility` <-> `visibility_off`
  - updates `aria-label` and `aria-pressed`
- Admin create-student and create-account forms show eye icons in markup but currently do not include JS to perform toggle.

## 13. Frontend Behavior Details by Page

### 13.1 Shared app shell (`portal_helper.php`)

- Layout built by `shell_start()` / `shell_end()`.
- Role-specific left-nav items are hardcoded per role.
- Role-specific logout links:
  - student: `/s/auth/logout`
  - officer: `/o/auth/logout`
  - admin: `/admin/auth/logout`
  - director: `/director/auth/logout`
- Theme uses Bootstrap plus custom CSS variables and gradients.

### 13.2 Auth pages

- `auth/login.php`
  - role-specific copy, action URL, and forgot-password URL injected by controller
  - show/hide password eye button is implemented
  - no CAPTCHA/ALTCHA/Turnstile checks in current code
- `auth/email_setup.php`
  - two-step student onboarding:
    - send code
    - verify code
  - verification code input max length is 6
- `auth/forgot_password.php`
  - role-specific accent and return-to-login link
  - submits email only

### 13.3 Student pages

- `student/my_qr.php`
  - shows event details for current ongoing paid/free ticket
  - refreshes QR every 9 seconds
  - countdown UI updates every second from 10s
  - polls ticket-state hash every 5 seconds and reloads if changed
  - shows offline warning banner when QR refresh fails or browser goes offline
  - downloadable one-time QR:
    - button enabled only when API returns availability
    - export generated in hidden buffer at high resolution (960x960)
    - downloadable QR is consumed after first successful scan
- `student/my_tickets.php`
  - polls `/api/student/ticket-state` every 5s and reloads on hash change
- `student/profile.php`
  - editable: first name, last name, course, year level, house
  - read-only display: student ID, email
- `student/settings.php`
  - shows email verification badge
  - links to reset-password flow

### 13.4 Officer pages

- `officer/scanner.php`
  - requires selected ongoing event
  - camera control buttons: start/stop
  - uses `jsQR` from CDN
  - posts scanned payload to `/api/qr/validate`
  - protects from rapid duplicate submission with `busy` lock and 1.5s cooldown
  - result panel shows status + message + student info
  - handles camera unsupported/permission errors
- `officer/gate_log.php`
  - polls `/api/gate-log/{eventId}` every 10s to refresh rows
- `officer/manual_lookup.php`
  - event + student ID search
  - displays ticket status badge and admission state text
  - current mismatch: uses `admission_count` field that controller query does not provide

### 13.5 Admin pages

- `admin/events.php`
  - create/edit event in one screen
  - free event checkbox controls business logic (price forced to 0 server-side)
  - actions per event row:
    - edit
    - activities
    - start event & prepare gate
    - cancel (soft-delete + status cancelled)
  - textarea auto-resizes on input
- `admin/import_receipts.php`
  - custom file picker UI updates selected file name text
  - expected CSV header explicitly shown: `receipt_id,student_no`
- `admin/import_confirm.php`
  - shows valid/invalid counts and rows
  - confirm creates tickets; cancel aborts session import data
- `admin/admissions.php`
  - filters by event and status
  - polls `/api/gate-activity-state` every 5s and reloads on state hash change
  - current route gap prevents this feed from resolving
- `admin/broadcast.php`
  - optional filters: role/course/year/house
  - sends title + message broadcast to matching active users
- `admin/students.php`
  - course selector supports “Other” input field in markup
  - delete-student modal markup present
  - file currently truncated at unfinished script block, so related JS behaviors are not currently implemented
- `admin/accounts.php`
  - create officer/admin accounts
  - toggle active/inactive per row
  - password eye icon in UI markup but no JS handler present

### 13.6 Director pages

- `director/dashboard.php`
  - metrics + recent events + latest gate activity
  - polls `/api/gate-activity-state` every 5s and reloads on change
  - current route gap prevents this feed from resolving
- `director/events.php`, `director/admissions.php`, `director/reports.php`
  - read-only data views with filters and badges

## 14. API Response Contracts (Current Implementation)

### 14.1 `GET /api/qr/generate`

Returns keys:

- `token`
- `qr_image` (Google Chart URL format)
- `event_title`
- `expires_in` (=10)
- `download_token`
- `download_file_name`
- `download_available`
- `gate_state`
- `gate_state_label`
- `gate_state_badge`
- `next_action`
- `next_action_label`
- `next_action_copy`
- `last_status_label`
- `last_scanned_at`

If no active ticket:

- HTTP `404` with `{ "error": "No active event ticket found." }`

### 14.2 `POST /api/qr/validate`

Input JSON:

- `token`
- `event_id`

Output status patterns:

- success: `status` in `in` or `out`, plus `student` object
- duplicate: `status = duplicate`
- error: `status = error` with message

### 14.3 `GET /api/gate-log/{eventId}`

- Returns `{ logs: [...] }` with transformed `status` label and badge class.

### 14.4 `GET /api/notifications`

- Returns `{ unread, notifications }`.

### 14.5 `POST /api/notifications/read`

- Accepts JSON `{ all: true }` or `{ id: number }`
- Returns `{ ok: true }`

### 14.6 Unmapped but implemented controller APIs

- `StudentController::ticketStateFeed()` returns hash and counts
- `OfficerController::gateActivityStateFeed()` returns `state_hash`

These are used by views but currently not registered in `Routes.php`.

## 15. Unused, Legacy, and Inconsistent Artifacts

- Unused/dead views in current routing:
  - `app/Views/admin/settings.php`
  - `app/Views/student/reset_password.php`
- Corrupted placeholder controller:
  - `app/Controllers/ApiController.php` contains text error payload, not executable controller code
- Live DB includes `ci_sessions` and `login_attempts` tables, but current runtime behavior:
  - sessions use filesystem
  - no active login-attempt lockout logic in controllers
