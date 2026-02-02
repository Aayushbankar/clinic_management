# 🖥️ Windows XAMPP Setup Guide

> Quick setup for running the Clinic Management System using XAMPP on Windows.

---

## ⚡ One-Command Setup

```powershell
python run.py
```

This script automatically:
1. ✅ Detects XAMPP PHP and MySQL
2. ✅ Verifies MySQL is running
3. ✅ Creates database and imports data
4. ✅ Starts the development server

---

## 📋 Prerequisites

1. **XAMPP** - Download from [apachefriends.org](https://www.apachefriends.org/download.html)
   - Choose PHP 8.2 or 8.3 version
   - Install to default location (`C:\xampp`)

2. **Python 3** - Usually pre-installed on Windows 10/11
   - Or install from Microsoft Store

---

## 🚀 Setup Steps

### Step 1: Start MySQL

1. Open **XAMPP Control Panel**
2. Click **Start** next to **MySQL**
3. Wait for it to turn green

> **Note:** Apache is NOT required. The script uses PHP's built-in server.

### Step 2: Run the Application

```powershell
cd path\to\clinic_management_cursor
python run.py
```

### Step 3: Access the Application

Open your browser: **http://127.0.0.1:8000**

---

## 🔑 Demo Credentials

| Role    | Email                 | Password      |
| ------- | --------------------- | ------------- |
| Admin   | `admin@clinic.test`   | `Admin@123`   |
| Doctor  | `doctor@clinic.test`  | `Doctor@123`  |
| Staff   | `staff@clinic.test`   | `Staff@123`   |
| Patient | `patient@clinic.test` | `Patient@123` |

---

## 🔧 Troubleshooting

### "MySQL is not running"

- Open XAMPP Control Panel
- Start MySQL service
- Or run: `C:\xampp\mysql\bin\mysqld.exe`

### "PHP not found"

The script should auto-detect XAMPP. If not:
```powershell
$env:PATH = "C:\xampp\php;" + $env:PATH
python run.py
```

### Port 8000 in use

Edit `run.py` line 26: change `"server_port": "8000"` to `"8080"`

---

## 📁 What the Script Does

1. **Finds PHP/MySQL** in XAMPP or system PATH
2. **Checks MySQL** is accepting connections on port 3306
3. **Creates database** `clinic_management` if missing
4. **Imports schema** from `database/schema.sql`
5. **Imports seed data** from `database/seed.sql`
6. **Starts PHP server** at http://127.0.0.1:8000

---

**Enjoy your Clinic Management System! 🏥**
