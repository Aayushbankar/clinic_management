## Clinic Management System (CMS) — Human Guide

This CMS is a complete **PHP + MySQL** clinic system with a modern responsive UI and role-based access.

### What’s inside
- **Backend**: PHP JSON API + session auth + CSRF protection (`backend/public/api.php`)
- **Frontend**: single-page UI served by PHP (`frontend/index.html`, `assets/app.js`, `assets/app.css`)
- **Database**: MySQL schema + seed demo data (`database/schema.sql`, `database/seed.sql`)

### Roles / Panels
- **Admin**: full system access
- **Staff (Receptionist)**: patients, appointments, medicines, billing/payments
- **Doctor**: schedule, appointments status, patient history write
- **Patient**: own appointments/bills/history, feedback

---

## Run with Docker (recommended)

### Start

```bash
docker compose up -d --build
```

Open:
- App UI: `http://localhost:8080/`
- API health: `http://localhost:8080/api.php?route=/`

### Stop

```bash
docker compose down
```

### Reset database (wipe all data)

```bash
docker compose down -v
docker compose up -d --build
```

---

## Demo logins (seeded)
- Admin: `admin@clinic.test` / `Admin@123`
- Doctor: `doctor@clinic.test` / `Doctor@123`
- Staff: `staff@clinic.test` / `Staff@123`
- Patient: `patient@clinic.test` / `Patient@123`

---

## Common workflows

### Book an appointment (staff / patient)
- Go to **Appointments**
- Click **Book Appointment**
- Choose doctor + date/time
- The system will block:
  - booking outside doctor schedule
  - double booking same doctor/date/time
  - exceeding max patients/day for that doctor

### Billing + payment (staff)
- Go to **Billing / Payment**
- Create a bill with item lines
- Add payment(s); system prevents paying more than due

### Doctor schedule (doctor)
- Doctors can view/add/edit **their own** schedule
- Admin can manage any doctor schedule

---

## Local (non-docker) run
See the main project `README.md` for MySQL import + running the PHP server locally.

