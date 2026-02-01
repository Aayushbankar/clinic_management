## Clinic Management System (CMS) — AI Agent Specification

### 1) Architecture
- **Frontend**: Vanilla JS SPA
  - Entry: `frontend/index.html`
  - UI logic: `assets/app.js`
  - Styles: `assets/app.css`
  - Served by: `backend/public/index.php`
- **Backend**: PHP JSON API
  - Entry: `backend/public/api.php` (routes via `Router`)
  - DB: PDO MySQL (`backend/src/core/Database.php`)
  - Auth: sessions + RBAC (`backend/src/middleware/Auth.php`)
  - CSRF: token required for POST/PUT/PATCH/DELETE (`backend/src/middleware/Csrf.php`)
- **Database**: MySQL 8+
  - DDL: `database/schema.sql`
  - Seed: `database/seed.sql`

### 2) Docker runtime (target for testing)
- `docker-compose.yml`
  - `db`: MySQL 8.4, initializes schema+seed via `/docker-entrypoint-initdb.d`
  - `app`: PHP 8.3 Apache, docroot set to `/var/www/html/backend/public`

Ports:
- App/UI/API: `http://localhost:8080/`
- MySQL: `localhost:3306` (root/root, cms/cms by default)

### 3) Environment variables (backend)
- `CMS_DB_HOST` (docker: `db`)
- `CMS_DB_PORT` (default `3306`)
- `CMS_DB_NAME` (default `clinic_management`)
- `CMS_DB_USER` (default `root`, docker: `cms`)
- `CMS_DB_PASS` (default empty, docker: `cms`)
- `CMS_DEBUG` (`1` enables detailed API error payloads)
- `CMS_CORS_ORIGINS` (optional, comma-separated)

### 4) Security model
- **Session cookie** based auth, stored server-side
- **CSRF** token:
  - obtain: `GET /auth/csrf`
  - send on state-changing requests in header: `X-CSRF-Token: <token>`
  - exception: `POST /auth/login` does not require CSRF
- **RBAC**:
  - enforced server-side in controllers via `Auth::requireRoles([...])`
  - some endpoints scope results by role (doctor/patient “me” views)

### 5) API contract (routes)
All API calls go through:
- `GET|POST|PUT|PATCH|DELETE /api.php?route=/...`
Responses are JSON:
- success: `{ ok: true, data: ..., meta: ... }`
- error: `{ ok: false, error: { message, details } }`

Auth:
- `GET /auth/csrf`
- `POST /auth/login` body: `{ email, password }`
- `POST /auth/logout`
- `GET /auth/me`

Core entities:
- Users (admin only): `/users`, `/users/{id}`
- Doctors: `/doctors`, `/doctors/me`, `/doctors/{id}`
- Staff (admin only): `/staff`, `/staff/{id}`
- Patients: `/patients`, `/patients/me`, `/patients/{id}`
- Departments: `/departments`, `/departments/{id}`
- Medicines: `/medicines`, `/medicines/{id}`

Scheduling / Appointments:
- Doctor schedule: `/doctor-schedule` (GET supports `doctor_id`), `/doctor-schedule/{id}`
- Appointments: `/appointments`, `/appointments/{id}`
  - create checks:
    - doctor exists and active
    - appointment time is within doctor schedule day/time
    - unique slot (doctor/date/time)
    - max patients/day (from schedule) not exceeded

Billing/Payments:
- Bills: `/billing`, `/billing/{id}`
- Add payment: `POST /billing/{id}/payments`
  - prevents overpaying due amount

Reports:
- `GET /reports/dashboard`
- `GET /reports/appointments-per-doctor?from=YYYY-MM-DD&to=YYYY-MM-DD`
- `GET /reports/revenue?from=YYYY-MM-DD&to=YYYY-MM-DD`
- `GET /reports/patient-history` (patient auto-scoped; others can pass `patient_id`)

Settings:
- `GET /settings/clinic`
- `PUT|PATCH /settings/clinic` (admin only)

Feedback:
- `GET /feedback` (admin/staff)
- `POST /feedback` (patient)

### 6) DB mapping (tables → modules)
- `users`: auth identities (admin/doctor/staff/patient)
- `doctors`, `staff`, `patients`: role profiles linked to `users.user_id`
- `departments`: doctor grouping
- `doctor_schedule`: schedule windows + max patients/day
- `appointments`: bookings, unique slot per doctor/date/time
- `patient_history`: clinical history entries (doctor writes via endpoint)
- `medicines`: inventory
- `billing`, `billing_items`, `payments`: billing and payments
- `clinic_settings`: clinic profile
- `feedback`: patient feedback
- `reports`: kept in schema for extensibility; current reports generated live

### 7) How to add a new module (agent playbook)
1. **DB**: add/alter table(s) in `database/schema.sql` (plus seed if needed)
2. **Model**: create `backend/src/models/<X>Model.php` with PDO prepared statements
3. **Controller**: create `backend/src/controllers/<X>Controller.php`
4. **Route**: register in `backend/public/api.php`
5. **Frontend**:
   - add navigation entry (if needed) in `navItemsForRole()`
   - implement `renderX()` in `assets/app.js`
   - use `API.get/post/put/patch/del` and rely on toasts + modals

### 8) Quick smoke test (agent)
1. `docker compose up -d --build`
2. `GET /api.php?route=/` should return ok
3. Login with seeded admin user
4. Create department + create doctor + book appointment + create bill + add payment

