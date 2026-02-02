# 🏥 Clinic Management System - Client Handover Guide

**Version:** 1.2.0  
**Delivery Date:** 2026-02-02

Welcome to your new Clinic Management System! This document provides everything you need to run, use, and test the system.

---

## 🆕 What's New in v1.2.0

- **Cross-Platform Runner**: Single `run.py` script works on Windows and Linux
- **Automatic Setup**: Database creation and seeding handled automatically
- **Password Reset**: Forgot your password? Click the link on the login page
- **CSV Exports**: Download appointments and billing reports from Reports page
- **Rate Limiting**: Account protection after 5 failed login attempts
- **Improved Security**: HSTS, CSRF protection, secure sessions

---

## ⚡ Quick Start

### Windows (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/download.html) with PHP 8.2+
2. Start MySQL from XAMPP Control Panel
3. Open PowerShell in the project folder and run:

```powershell
python run.py
```

4. Open browser: **http://127.0.0.1:8000**

### Linux (Docker)

```bash
./run.py
# Or manually:
docker compose up -d --build
```

Open browser: **http://localhost:8080**

---

## 🔑 Login Credentials (Demo Accounts)

| Role        | Email                 | Password      | Access Level        |
| :---------- | :-------------------- | :------------ | :------------------ |
| **Admin**   | `admin@clinic.test`   | `Admin@123`   | Full Control        |
| **Doctor**  | `doctor@clinic.test`  | `Doctor@123`  | Medical & Schedule  |
| **Staff**   | `staff@clinic.test`   | `Staff@123`   | Reception & Billing |
| **Patient** | `patient@clinic.test` | `Patient@123` | Booking & History   |

---

## 📘 User Manual & Features

### 👨‍💼 1. Admin Panel
**Goal:** Manage the entire clinic operation.
- **Manage Doctors/Staff:** Go to "Doctor Management" or "Staff Management" to add new employees.
- **Departments:** Create medical departments (e.g., Cardiology, Pediatrics) under "Departments".
- **Reports:** View "Reports" to see appointments per doctor and revenue generated.

### 🩺 2. Doctor Panel
**Goal:** Manage appointments and patient care.
- **Dashboard:** View today's summary.
- **Appointments:** See your daily schedule. Mark patients as "Completed" or "No Show" after visits.
- **My Schedule:** (If enabled) View your working days and hours (Monday-Sunday).

### 💁‍♀️ 3. Staff (Reception) Panel
**Goal:** Front-desk operations.
- **Book Appointments:** Go to "Appointments" -> "Book Appointment". Select a doctor and time slot for a patient.
- **Billing:** create invoices for patients under "Billing".
- **Payments:** Record cash/card payments against invoices.

### 👤 4. Patient Panel
**Goal:** Self-service for patients.
- **Book Online:** Schedule appointments with available doctors.
- **History:** View past appointments and payment status.
- **Feedback:** Submit ratings and comments about their visit.

---

## 🧪 How to Verify (End-to-End Testing)

We have included an automated test script that simulates a real user journey.

### Run the Test

```bash
python e2e_test.py
```

### Expected Output

```text
[INFO] Logging in as admin... success
[INFO] Creating Department... success
[INFO] Creating Doctor... success
[INFO] Patient booking appointment... success
[INFO] E2E Test Completed Successfully
```

If you see this, the system is 100% functional.

---

## ❓ Troubleshooting

| Issue | Solution |
| ----- | -------- |
| "Port 8080 is already in use" | Change port in `docker-compose.yml` or `run.py` |
| "Database connection error" | Ensure MySQL is running (XAMPP or Docker) |
| "404 Not Found" on assets | Restart the server with `python run.py` |
| Login fails with 500 error | Database may be empty; re-run `python run.py` |

---

## 📁 Project Structure

```
clinic_management_cursor/
├── run.py              ← Start here! Unified runner
├── backend/            ← PHP API and frontend
├── database/           ← SQL schema and seed data
├── docs/               ← Documentation
└── docker-compose.yml  ← Linux/Docker setup
```

---

**Enjoy your Clinic Management System! 🏥**
