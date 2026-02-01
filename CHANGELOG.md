# Changelog

All notable changes to the Clinic Management System are documented here.

## [1.1.1] - 2026-02-01

### Fixes
- **CSV Export**: Files now download with proper `.csv` extension and descriptive filenames
  - Format: `clinic_{type}_report_{from}_to_{to}.csv`
  - Uses fetch + blob download instead of window.open

### UI/UX
- **Forgot Password**: Added "Forgot your password?" link on login page
- **Password Reset Modal**: Clean modal UI for password reset flow
- **Export Buttons**: Added export buttons to Reports page (admin/staff)

---

## [1.1.0] - 2026-02-01

### Security Enhancements
- **Rate Limiting**: Added brute-force protection on login
  - 5 failed attempts within 5 minutes triggers 15-minute lockout
  - Tracks by IP address and email
  - New `login_rate_limits` database table
  - New `RateLimiter.php` middleware

- **Password Reset Flow**: Secure token-based password recovery
  - 64-character cryptographically secure tokens
  - 1-hour token expiry
  - Single-use tokens (invalidated after use)
  - Strong password policy: min 8 chars, 1 uppercase, 1 number
  - New endpoints: `/auth/request-password-reset`, `/auth/reset-password`

- **HSTS Header**: Strict-Transport-Security for HTTPS enforcement
  - Automatically enabled when HTTPS detected
  - Configurable via `hsts_enabled` in config

- **CSRF Exemptions**: Public auth routes exempted from CSRF
  - Login, password reset request, and password reset execution

### Performance Optimizations
- **New Database Indexes**:
  - `idx_appointments_status_date` - faster appointment reports
  - `idx_billing_date` - faster billing lookups
  - `idx_patients_blood_group` - faster patient filtering
  - `idx_medicines_expiry` - faster expiry checks

### Features
- **CSV Export**: Download reports as CSV files
  - `GET /reports/appointments/csv?from=YYYY-MM-DD&to=YYYY-MM-DD`
  - `GET /reports/billing/csv?from=YYYY-MM-DD&to=YYYY-MM-DD`
  - Excel-compatible UTF-8 encoding with BOM

### UI/UX Improvements
- **Empty State Component**: Beautiful placeholders for empty tables
- **Table Skeleton Loading**: Shimmer animation during data loading
- **Tooltip Component**: Hover tooltips with `data-tip` attribute

### Files Added
- `backend/src/middleware/RateLimiter.php`
- `backend/src/models/PasswordResetModel.php`

### Files Modified
- `database/schema.sql` - 2 new tables, 4 new indexes
- `backend/public/api.php` - new routes and requires
- `backend/src/controllers/AuthController.php` - rate limiting, password reset
- `backend/src/controllers/ReportController.php` - CSV export methods
- `backend/src/core/Security.php` - HSTS header support
- `backend/src/middleware/Csrf.php` - route exemptions
- `assets/app.css` - new components (~80 lines)

---

## [1.0.0] - 2026-01-29

### Initial Release
- Complete PHP + MySQL clinic management system
- Role-based access: Admin, Doctor, Staff, Patient
- Modules: Appointments, Billing, Patients, Doctors, Medicines, Reports
- Premium UI with "Obsidian & Aurora" theme
- Docker-based deployment
- CSRF protection, bcrypt passwords, session auth
