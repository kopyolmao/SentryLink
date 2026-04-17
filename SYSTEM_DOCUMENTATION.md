# SentryLink System Documentation

## Project Overview
SentryLink is a web-based school event ticketing and gate-monitoring system for ACLC Mandaue Campus. Its main purpose is to replace paper tickets with digital tickets and QR codes. Students use the system to view tickets and show a live QR code. Officers scan the QR code at the gate. Admins manage events, student records, ticket imports, and reports. Directors have read-only access for monitoring.

This documentation is based on the active CodeIgniter 4 implementation found in the `app/` folder, the route definitions in `app/Config/Routes.php`, and the database structure in `ci4_schema_upgrade.sql`. The older plain PHP files inside the `syntrelink/` folder are legacy files and are no longer the main implementation.

## 1. Purpose of System Modeling Diagrams and Data/Process Flow

### Why system modeling diagrams are important
System modeling diagrams help students, teachers, developers, and evaluators understand how a system works before they read the code line by line.

- An **ERD** shows the data structure of the system.
- A **UML class diagram** shows the main software classes and how they work together.
- A **Use Case diagram** shows what each type of user can do in the system.

These diagrams are useful because they:

- make the system easier to explain in school documentation
- show the connection between users, processes, and data
- help identify missing features or design problems
- support future maintenance and improvement

### What data flow means in this project
Data flow means how information moves inside the system.

In SentryLink, the main data flow is:

1. A user logs in.
2. The system checks the login credentials, CAPTCHA, and role.
3. The system loads the correct dashboard based on the role.
4. Admins create events and import receipt data to generate tickets.
5. Students receive tickets and open a live QR code for ongoing events.
6. Officers scan the QR code at the gate.
7. The system validates the QR code, checks the event and ticket, and saves an admission log.
8. Admins and directors view reports, logs, and event activity.

### What process flow means in this project
Process flow means the order of actions inside the system.

The main process flow of SentryLink is:

- **Authentication flow**: login, session check, role-based access
- **Event management flow**: create event, update event, add activities, start event, close event
- **Ticketing flow**: import receipt CSV, validate records, create tickets, notify students
- **Gate flow**: generate QR, scan QR, validate QR, save check-in/check-out logs
- **Monitoring flow**: view dashboards, reports, notifications, and audit logs

## 2. ERD Section

### ERD purpose in this project
The ERD explains what data the system stores and how the tables are connected. In SentryLink, the ERD is important because the system depends on user accounts, events, tickets, admissions, notifications, and audit records.

### Cardinality guide

- **1:1** means one record matches one record.
- **1:M** means one record can connect to many records.
- **M:N** means many records connect to many records. This is usually solved by a linking table.

### Main entities and attributes

#### 1. `users`
Purpose: stores all system users such as students, officers, admins, and directors.

Main attributes:

- `id`
- `student_id`
- `first_name`
- `last_name`
- `email`
- `password_hash`
- `role`
- `house`
- `year_level`
- `course`
- `session_token`
- `session_last_seen_at`
- `email_verified`
- `is_active`
- `deleted_at`
- `created_at`
- `updated_at`

#### 2. `events`
Purpose: stores school events managed by admins.

Main attributes:

- `id`
- `title`
- `description`
- `venue`
- `event_date`
- `start_time`
- `end_time`
- `is_free`
- `ticket_price`
- `max_capacity`
- `status`
- `created_by`
- `deleted_at`
- `created_at`
- `updated_at`

#### 3. `activities`
Purpose: stores smaller activities under a specific event.

Main attributes:

- `id`
- `event_id`
- `title`
- `type`
- `house_name`
- `start_time`
- `end_time`
- `venue_area`
- `description`
- `deleted_at`

#### 4. `tickets`
Purpose: stores student tickets for events.

Main attributes:

- `id`
- `user_id`
- `event_id`
- `ticket_code`
- `receipt_id`
- `payment_status`
- `download_qr_key`
- `download_qr_created_at`
- `issued_at`
- `updated_at`
- `deleted_at`

Notes:

- A student should only have one active ticket per event.
- Tickets are commonly created during CSV receipt import by the admin.

#### 5. `admissions`
Purpose: stores gate scan logs for entry and exit.

Main attributes:

- `id`
- `ticket_id`
- `user_id`
- `event_id`
- `scanned_by`
- `scanned_at`
- `gate_location`
- `status`

Notes:

- The current gate flow mainly uses `in` and `out`.
- Older records may still contain legacy values such as `admitted`.

#### 6. `notifications`
Purpose: stores messages sent to users.

Main attributes:

- `id`
- `user_id`
- `title`
- `message`
- `type`
- `is_read`
- `created_at`

#### 7. `audit_logs`
Purpose: stores important system actions for accountability.

Main attributes:

- `id`
- `user_id`
- `action`
- `target_type`
- `target_id`
- `ip_address`
- `user_agent`
- `created_at`

#### 8. `qr_blacklist`
Purpose: stores already-used live QR token IDs to stop QR replay.

Main attributes:

- `id`
- `token_jti`
- `user_id`
- `event_id`
- `used_at`

#### 9. `event_attendee_cache`
Purpose: stores a prepared attendee list for an event gate.

Main attributes:

- `id`
- `event_id`
- `user_id`
- `student_id`
- `full_name`
- `course`
- `year_level`
- `payment_status`
- `generated_at`

Note:

- Session data is not included as a main ERD entity because the current project uses file-based CodeIgniter sessions, not a database session table.

### Relationships and cardinality

| Relationship | Cardinality | Meaning |
|---|---|---|
| `users` to `tickets` | 1:M | One student can have many tickets across different events. |
| `events` to `tickets` | 1:M | One event can have many tickets. |
| `events` to `activities` | 1:M | One event can contain many activities. |
| `tickets` to `admissions` | 1:M | One ticket can create many gate logs because the student can go in and out. |
| `users` to `admissions` by `user_id` | 1:M | One student can have many admission records. |
| `users` to `admissions` by `scanned_by` | 1:M | One officer can scan many admission records. |
| `events` to `admissions` | 1:M | One event can have many gate logs. |
| `users` to `notifications` | 1:M | One user can receive many notifications. |
| `users` to `audit_logs` | 1:M | One user can create many audit log entries. |
| `users` to `events` by `created_by` | 1:M | One admin can create many events. |
| `events` to `event_attendee_cache` | 1:M | One event can have many cached attendees. |
| `users` to `event_attendee_cache` | 1:M | One user can appear in many event attendee caches. |
| `users` to `qr_blacklist` | 1:M | One user can have many used QR tokens. |
| `events` to `qr_blacklist` | 1:M | One event can have many used QR tokens. |

### Many-to-many relationships resolved by linking tables

- **Students and Events** have an **M:N** relationship.
  - A student can join many events.
  - An event can have many students.
  - This is resolved by the `tickets` table.

### Text ERD

```mermaid
erDiagram
    USERS ||--o{ EVENTS : creates
    USERS ||--o{ TICKETS : owns
    EVENTS ||--o{ TICKETS : has
    EVENTS ||--o{ ACTIVITIES : contains
    TICKETS ||--o{ ADMISSIONS : produces
    USERS ||--o{ ADMISSIONS : attends
    USERS ||--o{ ADMISSIONS : scans
    EVENTS ||--o{ ADMISSIONS : records
    USERS ||--o{ NOTIFICATIONS : receives
    USERS ||--o{ AUDIT_LOGS : creates
    USERS ||--o{ QR_BLACKLIST : uses
