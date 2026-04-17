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
