# User Workflows Guide

> Complete guide for using the Clinic Management System

---

## 📋 Table of Contents
- [Getting Started](#-getting-started)
- [Common Workflows](#-common-workflows)
- [Role-Specific Guides](#-role-specific-guides)
- [New Features v1.1.0](#-new-features-v110)

---

## 🚀 Getting Started

### First Login

1. Open `http://localhost:8080`
2. Enter your credentials (see Quick Reference below)
3. Click **Sign in**
4. You'll see your role-specific dashboard

### Quick Reference - Demo Accounts

| Role    | Email                 | Password      |
| ------- | --------------------- | ------------- |
| Admin   | `admin@clinic.test`   | `Admin@123`   |
| Doctor  | `doctor@clinic.test`  | `Doctor@123`  |
| Staff   | `staff@clinic.test`   | `Staff@123`   |
| Patient | `patient@clinic.test` | `Patient@123` |

---

## 🔄 Common Workflows

### 1. Booking an Appointment

**Who can do this:** Admin, Staff, Patient

1. Go to **Appointments** in the sidebar
2. Click **Book Appointment**
3. Select a **Patient** (or it's pre-selected for patient role)
4. Select a **Doctor**
5. Pick a **Date** and **Time**
6. Click **Save**

> ⚠️ The system automatically checks for scheduling conflicts

### 2. Managing Bills

**Who can do this:** Admin, Staff

1. Go to **Billing / Payment**
2. To create a new bill:
   - Click **Create Bill**
   - Select Patient
   - Enter items and amounts
   - Click **Save**
3. To record a payment:
   - Find the bill
   - Click **Record Payment**
   - Enter amount paid
   - Click **Save**

### 3. Adding a New Doctor

**Who can do this:** Admin

1. Go to **Doctor Management**
2. Click **Add Doctor**
3. Fill in:
   - Name, Email, Password
   - Specialization, Qualification
   - Department, Consultation Fee
4. Click **Save**

> ✅ This automatically creates a login account for the doctor

### 4. Creating Doctor Schedule

**Who can do this:** Admin, Doctor (own schedule)

1. Go to **Doctor Management** → Select doctor → **Schedule**
   - Or for doctors: Go to **Appointments** → **My Schedule**
2. Click **Add Slot**
3. Select **Day of Week** and **Time Range**
4. Click **Save**

---

## 👤 Role-Specific Guides

### Admin Dashboard
- **Doctors/Staff/Patients Count** - Total active users
- **Today's Appointments** - Quick overview
- **Revenue Summary** - Monthly income

### Doctor Dashboard  
- **My Appointments Today** - Pending consultations
- **Quick Status Update** - Mark appointments complete/cancelled

### Staff Dashboard
- **Today's Schedule** - All appointments
- **Quick Actions** - Book appointment, register patient

### Patient Dashboard
- **My Appointments** - Upcoming visits
- **My Bills** - Payment status

---

## 🆕 New Features v1.1.0

### Password Reset

**Forgot your password?**

1. On the login page, click **"Forgot your password?"**
2. Enter your email address
3. Click **Send Reset Link**
4. Check your email for the reset link
5. Click the link and enter a new password (min 8 chars, 1 uppercase, 1 number)

> 📧 In demo mode, the reset token is logged to the browser console

### CSV Report Exports

**Download reports as CSV files:**

1. Log in as **Admin** or **Staff**
2. Go to **Reports**
3. Select date range (From/To)
4. Click one of:
   - **📥 Export Appointments CSV**
   - **📥 Export Billing CSV**
5. File downloads automatically with name like:
   - `clinic_appointments_report_2026-01-01_to_2026-02-01.csv`
   - `clinic_billing_report_2026-01-01_to_2026-02-01.csv`

### Security Features

| Feature             | What it does                                 |
| ------------------- | -------------------------------------------- |
| **Rate Limiting**   | Locks account after 5 failed logins (15 min) |
| **CSRF Protection** | Prevents cross-site request forgery          |
| **HSTS**            | Forces HTTPS in production                   |
| **Secure Sessions** | HttpOnly cookies prevent XSS token theft     |

---

## ⚠️ Troubleshooting

### "Invalid credentials" error
- Check caps lock
- Verify email is correct
- After 5 failed attempts, wait 15 minutes

### Can't book appointment
- Check doctor's schedule (they must have available slots)
- Ensure no time conflicts with existing appointments

### Export buttons not visible
- Only visible to Admin and Staff roles
- Try hard refresh (Ctrl+Shift+R)

### Page not loading
- Check if the server is running: `python run.py`
- For Docker: `docker ps` and `docker compose up -d --build`

---

## 📞 Support

For technical issues, check:
1. Browser console for errors (F12)
2. Docker logs: `docker logs cms_app`
3. Database connection in docker-compose.yml

---

**Happy Managing! 🏥**
