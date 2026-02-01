## Clinic Management System (PHP + MySQL)

This project implements the **Clinic Management System** described in `requirement.pdf`:

- **Panels/Roles**: Admin, Doctor, Staff (Receptionist), Patient
- **Modules**: Doctors, Staff, Patients, Appointments + Doctor Schedule (with conflict checks), Departments, Medicines, Billing/Payments, Reports, Settings, Feedback, Profile, Logout

Project structure:
- `database/`: MySQL schema + seed data
- `backend/`: PHP JSON API + PHP-served frontend for easy testing
- `frontend/`: frontend SPA source (served by `backend/public/index.php`)
- `assets/`: handcrafted CSS/JS + logo
- `docs/`: AI agent spec
- `HANDOVER_GUIDE.md`: **Start Here** - Complete Client Guide (Setup, Usage, Testing)
- `functional_specification.md`: Detailed Role & Requirement Verification


---

## 📚 Quick Links

- 👉 **[Client Handover Guide (HANDOVER_GUIDE.md)](HANDOVER_GUIDE.md)** - **Read this first!** Contains setup, login info, and user manuals.
- 📋 **[Functional Spec (functional_specification.md)](functional_specification.md)** - Detailed Requirement Verification.
- 🤖 **[AI Agent Spec (docs/AI_AGENT_SPEC.md)](docs/AI_AGENT_SPEC.md)** - Technical details for developers/agents.

---

## Docker (recommended)

```bash
docker compose up -d --build
```

Open `http://localhost:8080/`

See `HANDOVER_GUIDE.md` for full instructions.

---

### 1) Database setup (MySQL 8+)

Create a database (example `clinic_management`) and import the SQL:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS clinic_management CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
mysql -u root -p clinic_management < database/schema.sql
mysql -u root -p clinic_management < database/seed.sql
```

---

### 2) Configure backend

The backend reads DB credentials from environment variables:

- `CMS_DB_HOST` (default `127.0.0.1`)
- `CMS_DB_PORT` (default `3306`)
- `CMS_DB_NAME` (default `clinic_management`)
- `CMS_DB_USER` (default `root`)
- `CMS_DB_PASS` (default empty)
- `CMS_DEBUG` (set to `1` to return stack traces in API errors)

Example:

```bash
export CMS_DB_HOST=127.0.0.1
export CMS_DB_PORT=3306
export CMS_DB_NAME=clinic_management
export CMS_DB_USER=root
export CMS_DB_PASS='your_password'
export CMS_DEBUG=1
```

---

### 3) Run the app (single command)

Serve everything (frontend + API) from one PHP server:

```bash
php -S 127.0.0.1:8000 -t backend/public
```

Open:
- `http://127.0.0.1:8000/` (frontend)
- `http://127.0.0.1:8000/api.php?route=/` (API health)

---

### 4) Demo credentials (from `database/seed.sql`)

- **Admin**: `admin@clinic.test` / `Admin@123`
- **Doctor**: `doctor@clinic.test` / `Doctor@123`
- **Staff**: `staff@clinic.test` / `Staff@123`
- **Patient**: `patient@clinic.test` / `Patient@123`

---

## Role manuals (what each role can do)

### Admin
- Full access to all modules
- Create/edit/delete doctors (creates doctor login account)
- Create/edit/delete staff (creates staff login account)
- Create/edit/delete patients (creates patient login account)
- Manage departments, medicines, schedules, appointments, billing/payments
- View reports (appointments per doctor, revenue)
- Update clinic settings

### Staff (Reception)
- Manage patients (create/edit/delete)
- Book and reschedule appointments (system validates doctor schedules and prevents double booking)
- Manage medicines
- Create bills and record payments
- View doctors directory, departments, reports (dashboard summary)

### Doctor
- View their appointments and update appointment status (scheduled/completed/cancelled/no_show)
- Manage their own schedule (and view it)
- Add patient history entries
- View patients list and basic information

### Patient
- View and book appointments (for their own account)
- Cancel scheduled appointments (their own)
- View bills and payment status
- View their patient history (via reports endpoint)
- Submit feedback

---

## Key security notes

- Passwords are stored as **bcrypt hashes**
- Uses **server-side sessions** with `HttpOnly` cookies
- Uses **CSRF token** for POST/PUT/PATCH/DELETE requests (`X-CSRF-Token`)
- Uses **prepared statements (PDO)** everywhere to prevent SQL injection

