# SentryLink — Detailed System Plan (v2.0)
**Project:** SentryLink  
**Framework:** CodeIgniter 4 (CI4)  
**Type:** Web-Based QR Authentication & Event Ticketing System  
**Institution:** ACLC Mandaue Campus  
**Purpose:** Paperless, QR-driven ticketing for school events  
**Revision:** v3.1 — Zero-cost stack confirmed: Gmail SMTP (App Password), Cloudflare Turnstile (free tier), all libraries open-source

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Roles & Responsibilities](#2-system-roles--responsibilities)
3. [Architecture Overview](#3-architecture-overview)
4. [Database Design](#4-database-design)
5. [Dynamic QR Code System](#5-dynamic-qr-code-system)
6. [Authentication & Security](#6-authentication--security)
7. [Module Breakdown](#7-module-breakdown)
8. [URL Structure & Routing](#8-url-structure--routing)
9. [Session Management](#9-session-management)
10. [UI/UX Plan](#10-uiux-plan)
11. [Event & Activity Management](#11-event--activity-management)
12. [Ticket System](#12-ticket-system)
13. [Gate Scanning System](#13-gate-scanning-system)
14. [Notifications & Logs](#14-notifications--logs)
15. [Anti-Abuse & Edge Case Handling](#15-anti-abuse--edge-case-handling)
16. [File & Folder Structure](#16-file--folder-structure)
17. [Third-Party Libraries & Tools](#17-third-party-libraries--tools)
18. [Development Phases & Timeline](#18-development-phases--timeline)
19. [Testing Plan](#19-testing-plan)
20. [Deployment Checklist](#20-deployment-checklist)

---

## 1. Project Overview

SentryLink transforms ACLC Mandaue Campus's event ticketing from physical paper tickets into a web-based, QR-authenticated system. Students are issued unique dynamic QR codes that refresh every 10 seconds. SSG/SSC officers at event gates scan these QR codes to admit students. Administrators manage events, activities, student records, and CSV receipt imports through a protected backend.

> **Zero-Cost Stack:** Every library, tool, and external service used in SentryLink is free at $0. No paid APIs, no subscriptions, no usage billing. Outgoing email uses the developer-owned SentryLink Gmail account via Gmail SMTP (free). Bot protection uses Cloudflare Turnstile (free). All other dependencies are open-source PHP/JS libraries.

### Goals
- Eliminate paper tickets entirely
- Prevent ticket fraud via dynamic, time-bound QR codes
- Streamline gate admission for events with large student volumes
- Support events with multiple overlapping activities and house booths
- Maintain a complete audit trail of attendance

---

## 2. System Roles & Responsibilities

| Role | Description | Access Level |
|---|---|---|
| **Student** | Enrolled student of ACLC Mandaue | View events, view own QR code (generated after CSV import), view own ticket history |
| **SSG/SSC Officer** | Student Government officer stationed at event gates | Scan QR codes, mark student as admitted, view real-time gate logs |
| **Admin** | School administrator or system manager | Full control: manage events, activities, students, CSV import, reports, system settings |
| **School Director** | Singular top-level oversight account | **View-only access**: monitor event status, view admission in/out logs, view system-wide reports — cannot create, edit, or delete any data. Cannot be deleted or demoted. |

> **Note on Developer Access:** The Developer has **no account in the system**. All schema changes, configuration updates, and direct data corrections are performed via hardcoded changes at the server/database level, outside of the application. No developer-facing UI or role exists within SentryLink.

---

## 3. Architecture Overview

```
Browser (Student / SSC / Admin)
        │
        ▼
  CodeIgniter 4 (MVC)
  ├── Controllers
  ├── Models
  ├── Views (CI4 templating)
  ├── Filters (Auth, CSRF, XSS, RateLimit)
  └── Libraries (QRSigner, AuditLogger, Notifier)
        │
        ▼
  MySQL Database (with proper indexing)
        │
        ▼
  QR Library (chillerlan/php-qrcode)
        │
        ▼
  Session Handler (CI4 database-backed sessions)
        │
        ▼
  Job Queue (CI4 Tasks / cron) — notifications, cleanup, reminders
```

### Key Technical Decisions

| Concern | Decision | Reason |
|---|---|---|
| QR token design | **Stateless HMAC-signed payload** | No DB write per token generation; scales to 1000s of students |
| Used token tracking | **Blacklist table only** (store only redeemed tokens) | Minimal DB writes; O(1) lookup |
| Race condition prevention | **Atomic DB UPDATE with affected-rows check** | Prevents duplicate admission at simultaneous scans |
| QR time tolerance | **±5 second grace window** | Accounts for network delay and server load timing |
| Gate log updates | **Long polling (30-second hold)** | Better than 5-second polling; simpler than WebSockets for v1 |
| Soft deletes | **`deleted_at` timestamp on all major tables** | Preserves audit integrity; no hard data loss |
| Receipt ID integrity | **Duplicate receipt_id detection at DB + app layer** | Blocks same receipt number being imported twice; all tickets sourced exclusively from Admin CSV import |
| Offline fallback | **Preloaded per-event attendee cache (read-only)** | Prevents queue buildup when internet drops at gate |
| Notifications | **Queue-based dispatch via CI4 Tasks** | Prevents email/notification spikes from blocking requests |
| Admin path | **Obfuscation + full auth + rate limiting** | Obfuscation alone is not security; all three together are |

---

## 4. Database Design

### Soft Delete Strategy
All major tables include a `deleted_at DATETIME NULL` column. Records with a non-null `deleted_at` are treated as deleted. CI4 Model's `$useSoftDeletes = true` handles this automatically. Hard deletes are disabled in the application layer.

Tables with soft delete: `users`, `events`, `activities`, `tickets`  
Tables without (append-only audit/log data): `admissions`, `audit_logs`, `notifications`, `qr_blacklist`

---

### Table: `users`
| Column | Type | Notes |
|---|---|---|
| id | INT PK AUTO_INCREMENT | |
| student_id | VARCHAR(20) UNIQUE | School-issued student ID |
| first_name | VARCHAR(100) | |
| last_name | VARCHAR(100) | |
| email | VARCHAR(150) UNIQUE | |
| password_hash | VARCHAR(255) | bcrypt, cost 12 |
| role | ENUM('student','ssg','admin','director') | |
| house | VARCHAR(100) NULL | |
| year_level | VARCHAR(50) NULL | |
| course | VARCHAR(100) NULL | |
| profile_photo | VARCHAR(255) NULL | Stored path; optimized on upload |
| is_active | TINYINT(1) DEFAULT 1 | |
| created_at | DATETIME | |
| updated_at | DATETIME | |
| deleted_at | DATETIME NULL | Soft delete |

**Indexes:** `role`, `student_id`, `email`, `is_active`

---

### Table: `qr_blacklist`
> Replaces the old `qr_tokens` table. Instead of writing a row for every token generated (which caused 2,000+ writes/minute at scale), we only write a row when a token is **successfully used**. Unscanned tokens simply expire — they are never stored.

| Column | Type | Notes |
|---|---|---|
| id | INT PK AUTO_INCREMENT | |
| token_jti | VARCHAR(64) UNIQUE | JTI claim (unique token ID from HMAC payload) |
| user_id | INT FK → users.id | |
| event_id | INT FK → events.id | |
| used_at | DATETIME | When it was scanned |

**Indexes:** `token_jti` (PRIMARY lookup), `user_id`, `event_id`  
**Cleanup:** Cron job deletes rows older than 24 hours daily (expired tokens can never be replayed anyway)

---

### Table: `events`
| Column | Type | Notes |
|---|---|---|
| id | INT PK AUTO_INCREMENT | |
| title | VARCHAR(255) | |
| description | TEXT | |
| venue | VARCHAR(255) | |
| event_date | DATE | |
| start_time | TIME | |
| end_time | TIME | |
| is_free | TINYINT(1) DEFAULT 0 | |
| ticket_price | DECIMAL(10,2) NULL | NULL if free |
| max_capacity | INT NULL | NULL = unlimited |
| status | ENUM('draft','open','ongoing','closed','cancelled') | |
| banner_image | VARCHAR(255) NULL | |
| created_by | INT FK → users.id | |
| created_at | DATETIME | |
| updated_at | DATETIME | |
| deleted_at | DATETIME NULL | Soft delete |

**Indexes:** `status`, `event_date`, `created_by`

---

### Table: `activities`
| Column | Type | Notes |
|---|---|---|
| id | INT PK AUTO_INCREMENT | |
| event_id | INT FK → events.id | |
| title | VARCHAR(255) | |
| type | ENUM('school_prepared','house_booth','competition','other') | |
| house_name | VARCHAR(100) NULL | |
| start_time | TIME | |
| end_time | TIME | |
| venue_area | VARCHAR(255) NULL | |
| description | TEXT NULL | |
| deleted_at | DATETIME NULL | Soft delete |

**Indexes:** `event_id`, `type`

---

### Table: `tickets`
| Column | Type | Notes |
|---|---|---|
| id | INT PK AUTO_INCREMENT | |
| user_id | INT FK → users.id | |
| event_id | INT FK → events.id | |
| ticket_code | VARCHAR(100) UNIQUE | UUID v4 |
| receipt_id | VARCHAR(255) UNIQUE | Receipt number generated by the external cashier system; provided by student to cashier; imported via CSV |
| payment_status | ENUM('pending','paid','free','cancelled') | `pending` = CSV imported but unverified; `paid` = admin-confirmed; `free` = no payment required |
| payment_verified_by | INT FK → users.id NULL | |
| issued_at | DATETIME | Timestamp when ticket was created via CSV import or free-event assignment |
| updated_at | DATETIME | |
| deleted_at | DATETIME NULL | Soft delete (cancelled tickets) |

**Indexes:** `(user_id, event_id)` UNIQUE (one ticket per student per event), `payment_status`, `receipt_id`  
**Duplicate Receipt Guard:** `receipt_id` has a UNIQUE index — enforced at both DB level and app level. The same receipt number cannot be imported twice for any event.

---

### Table: `admissions`
| Column | Type | Notes |
|---|---|---|
| id | INT PK AUTO_INCREMENT | |
| ticket_id | INT FK → tickets.id | |
| user_id | INT FK → users.id | |
| event_id | INT FK → events.id | |
| scanned_by | INT FK → users.id | SSG/SSC officer |
| scanned_at | DATETIME | |
| gate_location | VARCHAR(100) NULL | |
| status | ENUM('admitted','rejected','duplicate') | |
| rejection_reason | VARCHAR(255) NULL | |

**Indexes:** `(user_id, event_id)`, `scanned_by`, `scanned_at`, `event_id`  
**Duplicate Guard:** Application-layer check + DB UNIQUE on `(user_id, event_id)` for admitted rows (partial unique index or enforced in transaction)

---

### Table: `event_attendee_cache`
> Read-only offline fallback. Preloaded before an event goes `ongoing`. Officers' devices fetch and store this in `sessionStorage` (JS) as a last resort when internet drops.

| Column | Type | Notes |
|---|---|---|
| id | INT PK AUTO_INCREMENT | |
| event_id | INT FK → events.id | |
| user_id | INT FK → users.id | |
| student_id | VARCHAR(20) | Denormalized for fast lookup |
| full_name | VARCHAR(255) | Denormalized |
| course | VARCHAR(100) | Denormalized |
| year_level | VARCHAR(50) | Denormalized |
| payment_status | ENUM('paid','free') | Only confirmed tickets |
| generated_at | DATETIME | When cache was built |

**Populated by:** Admin clicking "Lock & Prepare Gate" when setting event to `ongoing`  
**Indexes:** `(event_id, student_id)`

---

### Table: `sessions` (CI4 DB sessions)
| Column | Type |
|---|---|
| id | VARCHAR(128) PK |
| ip_address | VARCHAR(45) |
| timestamp | INT UNSIGNED |
```
Student opens /s/my-qr
     │
     ▼
JavaScript timer fires every 9 seconds
     │
     ▼
AJAX GET /api/qr/generate
     │
     ▼
Server builds payload, signs with HMAC, returns token string
     ▼
chillerlan/php-qrcode renders QR image from token
     │
     ▼
QR image + 10-second countdown displayed to student
     │
     ▼
Repeat every 9 seconds
```

**No DB write occurs during this entire flow.**

### Token Validation Flow (Server Side, on Scan)

```
1. Receive token string via POST /api/qr/validate
2. Split on "." → payload_b64 + sig_b64
3. Recompute HMAC-SHA256(payload_b64, QR_SECRET_KEY)
4. Compare with sig_b64 (constant-time comparison via hash_equals())
   → Mismatch: reject "Invalid QR"
5. base64url_decode(payload_b64) → parse JSON
6. Check: exp + 5 > now (with ±5 second grace window for network delay)
   → Expired: reject "QR code has expired"
7. Check: jti NOT IN qr_blacklist (SELECT WHERE token_jti = ?)
   → Found: reject "QR already used"
8. Begin DB transaction:
   a. INSERT INTO qr_blacklist (token_jti, user_id, event_id, used_at) → this is atomic
   b. If INSERT fails due to UNIQUE constraint → duplicate scan, reject
   c. Verify student has paid/free ticket for this event
   d. Check no existing admission for (user_id, event_id)
   e. INSERT admission record
   f. COMMIT
9. Return: { status: 'admitted', student: { name, photo, course, year } }
```

### Race Condition Prevention (Critical Fix)

The atomic INSERT into `qr_blacklist` on `token_jti` (which has a UNIQUE index) acts as a distributed lock:

- Officer A scans → INSERT jti → succeeds → proceed to admit
- Officer B scans same QR milliseconds later → INSERT jti → **UNIQUE constraint violation** → reject "Already used"

This is handled at the **database engine level** — no application-level locking needed. Both officers get a deterministic result with no duplicate admission possible.

### Grace Window for Network Delay

```php
// Instead of:
if ($payload->exp < time()) { reject(); }

// Use:
if ($payload->exp + 5 < time()) { reject(); }  // 5-second grace
```

This prevents false rejections when:
- The AJAX request to generate a new QR is slow
- There is network latency between student's phone and server
- Server is under load and timestamp comparison is slightly delayed

### QR Auto-Refresh (Frontend: `qr-refresh.js`)

```javascript
const REFRESH_INTERVAL = 9000; // ms (just before 10s expiry)
const GRACE = 5;               // matches server-side grace window

let countdown = 10;
const ring = document.getElementById('countdown-ring');
const qrImage = document.getElementById('qr-img');

async function refreshQR() {
    try {
        const res = await fetch('/api/qr/generate', { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Server error');
        const data = await res.json();
        qrImage.src = data.qr_image_url;
        countdown = 10;
    } catch (e) {
        showOfflineWarning();
    }
}

setInterval(refreshQR, REFRESH_INTERVAL);
setInterval(() => {
    countdown--;
    updateCountdownUI(countdown);
    if (countdown <= 0) countdown = 10;
}, 1000);

window.addEventListener('offline', showOfflineWarning);
window.addEventListener('online', () => { hideOfflineWarning(); refreshQR(); });
```

---

## 6. Authentication & Security

### Separate Login Panels

Each role has a completely separate login URL, controller, and view — no shared login page:

| Role | Login URL | Controller |
|---|---|---|
| Student | `/s/auth/login` | `App\Controllers\Student\AuthController` |
| SSG/SSC | `/o/auth/login` | `App\Controllers\Officer\AuthController` |
| Admin | `/[ADMIN_PATH]/auth/login` | `App\Controllers\Admin\AuthController` |
| School Director | `/[SD_PATH]/auth/login` | `App\Controllers\Director\AuthController` |

- `ADMIN_PATH` and `SD_PATH` are set in `.env` — not in the codebase
- These URLs are not linked from any public-facing page
- Wrong paths return generic 404 — no hint about real admin URL
- **Important:** Obfuscation is NOT relied upon as security. Full auth + rate limiting + lockout are the real protection. The hidden path is a minor extra layer only.

### Password Security
- `password_hash()` with `PASSWORD_BCRYPT`, cost factor 12
- Minimum: 8 characters, 1 uppercase, 1 number, 1 symbol (server-side enforced)
- Passwords never in logs, never in responses

### Forgot Password (Login Page)
Available on every role's login page. Flow:

```
User clicks "Forgot Password" on login page
     ↓
User enters their registered email address
     ↓
System generates a secure random temporary password
     ↓
System sends temporary password to the registered email via SMTP
     ↓
User logs in using the temporary password
     ↓
User is prompted immediately to set a new password before accessing their dashboard
```

> This flow applies to all roles: Student, SSG/SSC Officer, Admin, and School Director.

### New User Email Setup (First Login)
When Admin creates a new account, no email is set by default. On first login:

```
New user logs in with system-assigned credentials
     ↓
System detects: email not yet verified
     ↓
User is shown an "Email Setup" prompt before reaching dashboard
     ↓
User inputs their email address
     ↓
System generates a 6-digit verification code → sent to that email via SMTP
     ↓
User inputs the verification code in the prompt
     ↓
Email verified and saved → user proceeds to dashboard
```

This verified email is then used for all future password resets triggered via "Forgot Password."

### Reset Password (In Dashboard)
Available in each role's dashboard under **Settings → Security**:

```
User clicks "Reset Password" in dashboard settings
     ↓
System sends a verification code to the user's registered/verified email
     ↓
User enters the code
     ↓
User sets a new password (with confirmation)
     ↓
Session invalidated → user redirected to login
```

### SQL Injection Prevention
- All queries via CI4 Query Builder with parameterized bindings exclusively
- Raw queries forbidden in codebase
- CI4 `$db->escape()` used wherever values are dynamic
- Input validation via CI4 `Validation` library before any DB interaction

### Input Sanitization & Output Encoding
- `htmlspecialchars()` on all view output
- CI4 XSS filter enabled globally in `app/Config/Filters.php`
- File uploads: MIME-type verified server-side (not just extension), whitelisted types only
- Profile photos optimized and resized on upload (GD library) — prevents oversized image uploads
- Pagination limits enforced on all admin data tables (max 100 rows per page) to prevent memory issues

### CSRF Protection
- CI4 built-in CSRF token on every form (cookie + hidden field)
- CSRF token regenerated after each POST
- AJAX requests include `X-CSRF-TOKEN` header

### Rate Limiting & Brute Force Protection
- Login: 5 attempts per 15 minutes per IP + per identifier
- After 5 failed: account locked 30 minutes + notification to account owner email
- API endpoints (`/api/qr/validate`, `/api/qr/generate`): rate limited separately via a `RateLimitFilter` (token bucket, stored in DB or cache)
  - `/api/qr/generate`: max 20 requests/minute per user (prevents abuse of generation endpoint)
  - `/api/qr/validate`: max 60 requests/minute per officer IP (enough for rapid gate scanning)

### CAPTCHA — Dual-Layer Security (All Login Pages)

All login pages enforce a two-stage CAPTCHA that must be completed before the form can be submitted:

**Layer 1 — Cloudflare Turnstile (No-Puzzle)**
- User clicks the Cloudflare Turnstile checkbox ("I'm human")
- Cloudflare validates silently in the background — no puzzles, no image grids
- On success: Cloudflare emits a token; Layer 2 is unlocked

**Layer 2 — System-Generated Text CAPTCHA Image**
- System generates a random alphanumeric string (e.g., `aB3kR7`)
- The string is rendered as a **distorted image** server-side using PHP's GD Library (noise, random fonts, slight rotation)
- The image is displayed in a popup/modal that appears after Layer 1 passes
- User types the characters shown in the image into an input field
- System verifies the typed value against the session-stored original (case-insensitive)
- On success: login form submission is allowed

```
User visits login page
     ↓
Clicks Cloudflare Turnstile checkbox
     ↓
Cloudflare validates → emits token (no puzzle)
     ↓
Popup appears: shows system-generated text CAPTCHA image
     ↓
User types the letters/numbers shown in the image
     ↓
System validates the typed value (server-side, session-stored)
     ↓
Both layers passed → login form unlocked → user submits credentials
```

> **Why dual-layer?** Cloudflare Turnstile blocks bots at the network level. The custom GD text CAPTCHA adds a second challenge that even if Cloudflare tokens were spoofed, still requires a human to read a server-generated image. Together they defend against both automated and semi-automated attacks.

### Security Headers
Set via `BaseController::initController()` or `.htaccess`:
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

### Receipt ID Integrity
- All paid ticket entries originate exclusively from Admin CSV import — students and officers cannot manually submit receipt numbers
- `receipt_id` column has a UNIQUE constraint at the DB level, preventing the same receipt from being imported more than once
- The receipt number is generated by an **external cashier system** (developed by a separate team); SentryLink only stores and references it — it does not generate, validate, or process payment amounts
- Admin import screen flags duplicate `receipt_id` rows in the CSV before committing to the database
- **Payment is entirely physical** — no payment gateway, no GCash API, no online transaction handling exists in SentryLink


---

## 7. Module Breakdown

### 7.1 Student Module
- **Dashboard** — upcoming events, QR code status, notifications
- **My QR Code** — live dynamic QR that refreshes every 10 seconds with countdown ring; only visible after Admin has imported the student's receipt via CSV for an event. QR is encrypted and generated server-side after import.
- **My Tickets** — list of all tickets (past and upcoming) with status
- **Profile** — update personal info, upload/crop profile photo
- **Settings → Security** — reset password (requires email verification)
- **Email Setup (First Login)** — new accounts created by Admin prompt the student to register and verify their email before accessing the dashboard; this email is used for future password resets
- **Notifications** — bell icon with unread count, notification list

### 7.2 SSG/SSC Officer Module
- **Dashboard** — active events for today, my scan count
- **QR Scanner** — camera-based via jsQR/ZXing-js; overlaid student info card on scan result
- **Gate Log** — real-time admission list (long polling); auto-updates when new admission occurs
- **Manual Lookup** — search by student ID when QR fails; verifies via photo + ticket status
- **Offline Mode** — if internet drops, scanner disables and a read-only attendee list loads from `sessionStorage` cache (preloaded before event)
- **Profile** — change password only

### 7.3 Admin Module
- **Dashboard** — stat cards: students, active events, tickets today, admissions today
- **Event Management** — CRUD; status transitions; "Lock & Prepare Gate" button (builds attendee cache, transitions to `ongoing`)
- **Activity Management** — CRUD per event
- **Student Management** — view/add/edit/deactivate; bulk CSV import with validation report
- **CSV Receipt Import** — Admin imports a CSV file (columns: `receipt_id, student_no, event_id`) received physically from the Cashier. The system:
  1. Parses and validates each row (checks for duplicates, unknown student IDs, unknown event IDs)
  2. Shows a pre-import validation report (valid rows / skipped rows with reasons)
  3. On confirm: inserts tickets with `payment_status = 'paid'` for each valid row
  4. Triggers system to generate an encrypted QR code for each newly admitted student
  5. QR codes become visible in the respective student's dashboard immediately after refresh
- **Ticket Management** — view all tickets; filter by event/status; export CSV; no manual payment submission
- **Admission Logs** — full log with filters (event, date, officer, status)
- **Notification Manager** — broadcast to all, or filter by course/year/house
- **Reports** — attendance per event; export PDF (dompdf) and CSV
- **Audit Logs** — read-only; filter by action, user, date range
- **Admin Account Management** — create/edit/deactivate admin and officer accounts
- **System Settings** — system name, logo, QR grace window, rate limit thresholds, session timeout per role. *(SMTP/email credentials are developer-configured in `.env` and are not exposed in the admin UI.)*

### 7.4 School Director Module
- **View-Only Access** — the School Director cannot create, edit, or delete any data
- **Event Status Overview** — view all events and their current status (draft/open/ongoing/closed/cancelled)
- **Admission In/Out Logs** — view real-time and historical admission logs for all events
- **System-Wide Reports** — view attendance and summary reports (PDF/CSV download allowed)
- **Audit Log Viewer** — read-only access with filters (action, user, date range)
- No access to student management, ticket management, system settings, or account management

---

## 8. URL Structure & Routing

### Public Routes
```
/                              → Redirect based on session role
/s/auth/login                  → Student login
/s/auth/forgot-password        → Student forgot password (email-based)
/o/auth/login                  → Officer login
/o/auth/forgot-password        → Officer forgot password (email-based)
/[ADMIN_PATH]/auth/login       → Admin login
/[ADMIN_PATH]/auth/forgot-password → Admin forgot password (email-based)
/[SD_PATH]/auth/login          → School Director login
/[SD_PATH]/auth/forgot-password → School Director forgot password (email-based)
```

### Student Routes (`role = student`)
```
/s/dashboard
/s/my-qr
/s/my-tickets
/s/profile
/s/settings
/s/settings/email-setup        → First-login email registration + verification
/s/settings/reset-password     → In-dashboard password reset (requires email verification)
/s/notifications
/s/auth/logout
```

### Officer Routes (`role = ssg`)
```
/o/dashboard
/o/scanner
/o/gate-log
/o/gate-log/lookup
/o/auth/logout
```

### Admin Routes (`role = admin`)
```
/[ADMIN_PATH]/dashboard
/[ADMIN_PATH]/events
/[ADMIN_PATH]/events/create
/[ADMIN_PATH]/events/{id}/edit
/[ADMIN_PATH]/events/{id}/activities
/[ADMIN_PATH]/events/{id}/gate-prepare
/[ADMIN_PATH]/students
/[ADMIN_PATH]/students/import           → General student CSV import
/[ADMIN_PATH]/tickets
/[ADMIN_PATH]/tickets/import-receipts   → CSV receipt import (receipt_id, student_no, event_id)
/[ADMIN_PATH]/admissions
/[ADMIN_PATH]/notifications/broadcast
/[ADMIN_PATH]/reports
/[ADMIN_PATH]/audit-logs
/[ADMIN_PATH]/settings
/[ADMIN_PATH]/admins
/[ADMIN_PATH]/auth/logout
```

### School Director Routes (`role = director`)
```
/[SD_PATH]/dashboard
/[SD_PATH]/events                       → View-only event list and status
/[SD_PATH]/admissions                   → View-only in/out admission logs
/[SD_PATH]/reports                      → View and download reports
/[SD_PATH]/audit-logs                   → View-only audit log
/[SD_PATH]/auth/logout
```

### API Endpoints (AJAX, rate-limited)
```
GET  /api/qr/generate                  → Student: stateless token + QR image
POST /api/qr/validate                  → Officer: validate scanned token
GET  /api/gate-log/{event_id}          → Officer: long-poll for new admissions
GET  /api/notifications                → Student/Officer: unread count + list
POST /api/notifications/read           → Mark as read
GET  /api/events/{id}/attendee-cache   → Officer: fetch preloaded attendee list
```

---

## 9. Session Management

### Configuration (`app/Config/Session.php`)
- **Driver:** `DatabaseHandler` (CI4 native)
- **Session table:** `ci_sessions`
- **Cookie name:** Set in `.env` (not the default `ci_session`)
- **Cookie secure:** `true` (HTTPS only)
- **Cookie SameSite:** `Strict`
- **HttpOnly:** `true`
- **Session expiry:** Configurable per role via Admin settings (default: 2 hours inactivity)
- **Regeneration:** On every login (prevents session fixation)

### Multi-Session / Tab Detection
- On login: session fingerprint computed as `hash(IP + User-Agent + role)`
- If second login detected with different fingerprint while session is active:
  - **Default:** Invalidate old session → log in new device → notify old device via in-app notification
  - **Strict mode (configurable):** Block new login, show "You are already logged in elsewhere"

### Back-Button Prevention
- After logout: `Cache-Control: no-store, no-cache, must-revalidate` + `Pragma: no-cache` headers
- Same headers on all authenticated pages
- All auth controllers check session validity on every request; invalid session → login redirect
- Login pages have `history.replaceState()` to replace the entry in browser history — pressing back after login does not re-expose the login page

### Auto-Redirect When Already Logged In
- Visiting any login page with an active valid session → redirect to the role's dashboard
- Handled by `RedirectIfAuthenticatedFilter` applied to all `/*/auth/login` routes

---

## 10. UI/UX Plan

### Design Principles
- **Mobile-First** — primary device is a phone (student QR display, officer scanning)
- **Minimal Click Paths** — Student to QR: 2 taps. Officer to scanner: 1 tap.
- **Offline Awareness** — banner shown immediately when connection is lost
- **Accessibility** — WCAG AA contrast ratios, large tap targets (minimum 44px), descriptive ARIA labels on scanner controls

### Student UI
- Bottom navigation: Home | Events | My QR | Tickets | Profile
- QR page: Full-screen QR with circular countdown ring (10-second animated SVG ring)
- Offline banner: "No internet — your QR cannot be refreshed. Please connect and try again."
- Event cards: Banner image, name, date, free/price badge, activity count badge

### Officer UI
- Fullscreen camera view is the default landing
- After scan: slide-up card with student photo, name, ID, course, event ticket status
  - Green card = Admitted
  - Red card = Rejected (with specific reason)
  - Orange card = Already Admitted (duplicate scan)
- Sound feedback: chime on success, buzz on rejection
- Offline mode banner: scanner disabled, "Offline — Showing preloaded attendee list"
- Manual lookup accessible via icon button overlaid on scanner view

### Admin UI
- Sidebar (desktop) / hamburger (mobile)
- Dashboard stat cards with quick-action buttons
- All data tables: search bar, column sort, filter dropdowns, pagination (max 100 rows/page)
- Export buttons (CSV, PDF) on all major tables
- Color-coded status badges

### General
- **Theme:** Dark navy + Gold (ACLC school colors; adjust as directed)
- **Font:** Inter — self-hosted to avoid external font dependency on school network
- **Icons:** Bootstrap Icons
- **JS Framework:** Vanilla JS + Bootstrap 5 only (no React/Vue — reduces complexity for school environment)

---

## 11. Event & Activity Management

### Event Lifecycle
```
Draft → Open → [Lock & Prepare Gate] → Ongoing → Closed
                        │
                 Builds attendee cache
                 Locks ticket sales
          └→ Cancelled (any time before Ongoing)
```

- **Draft:** Admin-only; hidden from students
- **Open:** Visible to students; tickets purchasable/joinable
- **Ongoing:** Gate scanning active; ticket sales locked; attendee cache built on transition
- **Closed:** Event ended; no new admissions; reports available
- **Cancelled:** Soft deleted; ticket holders notified via broadcast

### "Lock & Prepare Gate" Action
When Admin clicks this:
1. Event status set to `ongoing`
2. System queries all confirmed tickets for this event
3. Denormalized data inserted into `event_attendee_cache`
4. Officers' devices will fetch this cache on their next `/api/events/{id}/attendee-cache` request and store in `sessionStorage`
5. Admin sees confirmation: "Gate prepared. X students in attendee list."

### Activity Management
- Each event has multiple activities with their own time windows
- Activity types: `school_prepared`, `house_booth`, `competition`, `other`
- Displayed to students as a visual timeline on event detail page
- Soft-deleted activities are excluded from student views

---

## 12. Ticket System

### Physical Payment — No In-System Payment Handling
SentryLink does **not** process, collect, or verify payments. All payments are made **physically** through the school cashier. The external cashier system (developed by a separate team) generates a `receipt_id` for each transaction. SentryLink only receives and stores this receipt number — it has no knowledge of payment amounts, methods, or GCash transactions.

### How Paid Tickets Enter the System (CSV Import Flow)
```
Student pays physically at the Cashier
