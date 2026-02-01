# 🏥 Clinic Management System - Client Handover Guide

**Version:** 1.1.1
**Delivery Date:** 2026-02-01

Welcome to your new Clinic Management System! This document provides everything you need to run, use, and test the system.

---

## 🆕 What's New in v1.1.1

- **Password Reset**: Forgot your password? Click the link on the login page
- **CSV Exports**: Download appointments and billing reports from Reports page
- **Rate Limiting**: Account protection after 5 failed login attempts
- **Improved Security**: HSTS, CSRF protection, secure sessions

---

## ⚡ Quick Start (Recommended)

The easiest way to run the system is using **Docker**. This ensures all dependencies (PHP, MySQL, Server) are set up automatically.

### 1. Requirements
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed on your machine.

### 2. Run the System
Open your terminal (Command Prompt/PowerShell) in the project folder and run:

```bash
docker compose up -d --build
```

### 3. Access the System
Once the command finishes, open your browser and visit:
👉 **[http://localhost:8080](http://localhost:8080)**

---

## 🔑 Login Credentials (Demo Accounts)

The system comes pre-loaded with these demo accounts for you to test each role:

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

We have included an automated test script that simulates a real user journey (Admin -> Dept -> Doctor -> Patient -> Booking).

### 1. Run the Test
In your terminal, run:

```bash
python3 e2e_test.py
```

### 2. Expected Output
You will see logs indicating success:
```text
[INFO] Logging in as admin... success
[INFO] Creating Department... success
[INFO] Creating Doctor... success
[INFO] Patient booking appointment... success
[INFO] E2E Test Completed Successfully
```
If you see this, the system is 100% functional.

---

## 🔧 Manual Setup (Without Docker)

If you cannot use Docker, follow these steps to install manually:

1.  **Install XAMPP/WAMP/MAMP** (Apache + MySQL + PHP).
2.  **Database:**
    *   Open PHPMyAdmin.
    *   Create a database named `clinic_management`.
    *   Import `database/schema.sql`.
    *   Import `database/seed.sql`.
3.  **Backend Config:**
    *   Set environment variables (or edit `backend/src/config/config.php` temporarily) to match your DB credentials.
4.  **Run:**
    *   Point your web server (Apache) to the `backend/public` folder.
    *   **Important:** Ensure `backend/public/assets` is a valid link to the `assets` folder in the root.

---

## ❓ Troubleshooting

-   **"Port 8080 is already in use"**: Open `docker-compose.yml` and change `"8080:80"` to `"8081:80"`. Access at `http://localhost:8081`.
-   **"Database connection error"**: Ensure the `cms_db` container is running (`docker compose ps`).
-   **"404 Not Found" on assets**: Ensure the symlink in the Dockerfile is correct (already fixed in this delivery).

---
**Enjoy your Clinic Management System!**
