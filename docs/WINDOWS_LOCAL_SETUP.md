# 🖥️ Windows Local Setup Guide

> Run the Clinic Management System on Windows using XAMPP or standalone PHP/MySQL.

---

##  Quick Start (Recommended)

The easiest way to run the application on Windows is using the unified Python script:

```powershell
python run.py
```

This will automatically:
-  Detect PHP and MySQL (XAMPP or standalone)
-  Check that MySQL service is running
-  Create database if it doesn't exist
-  Import schema and seed data if tables are missing
-  Start the development server
-  Display demo login credentials

**Requirements:**
- Python 3.x (usually pre-installed or via Microsoft Store)
- XAMPP **OR** standalone PHP 8.x + MySQL 8.x

---

##  Prerequisites

### Option A: XAMPP (Easiest)

1. Download [XAMPP](https://www.apachefriends.org/download.html) (PHP 8.2+)
2. Install to default location (`C:\xampp`)
3. Open XAMPP Control Panel
4. Start **MySQL** service (Apache is NOT required)

### Option B: Standalone PHP + MySQL

1. **PHP 8.x**: Download from [windows.php.net](https://windows.php.net/download/)
   - Choose **VS16 x64 Thread Safe** ZIP
   - Extract to `C:\php`
   - Add `C:\php` to system PATH
   - Enable `pdo_mysql` in `php.ini`

2. **MySQL 8.x**: Download from [dev.mysql.com](https://dev.mysql.com/downloads/installer/)
   - Install MySQL Server
   - Remember the root password
   - Add `C:\Program Files\MySQL\MySQL Server 8.4\bin` to PATH

---

##  Running the Application

### Method 1: Automated (Recommended)

```powershell
python run.py
```

### Method 2: Manual

1. **Create Database**:
   ```powershell
   mysql -u root -e "CREATE DATABASE clinic_management CHARACTER SET utf8mb4;"
   mysql -u root clinic_management < database\schema.sql
   mysql -u root clinic_management < database\seed.sql
   ```

2. **Set Environment Variables**:
   ```powershell
   $env:CMS_DB_HOST = "127.0.0.1"
   $env:CMS_DB_NAME = "clinic_management"
   $env:CMS_DB_USER = "root"
   $env:CMS_DB_PASS = ""
   ```

3. **Start Server**:
   ```powershell
   php -S 127.0.0.1:8000 -t backend\public backend\public\index.php
   ```

4. **Access**: http://127.0.0.1:8000

---

##  Demo Credentials

| Role    | Email                 | Password      |
| ------- | --------------------- | ------------- |
| Admin   | `admin@clinic.test`   | `Admin@123`   |
| Doctor  | `doctor@clinic.test`  | `Doctor@123`  |
| Staff   | `staff@clinic.test`   | `Staff@123`   |
| Patient | `patient@clinic.test` | `Patient@123` |

---

##  Troubleshooting

### MySQL is not running

**Solution:** Start MySQL from XAMPP Control Panel or run:
```powershell
net start MySQL84
```

### PHP is not recognized

**Solution:** Add PHP to your PATH:
- XAMPP: `C:\xampp\php`
- Standalone: `C:\php`

### Port 8000 is already in use

**Solution:** Edit `run.py` and change `server_port` to `8080` or another port.

### Database connection failed

**Solution:** Verify MySQL is running on port 3306:
```powershell
netstat -an | findstr 3306
```

---

##  Key Files

| File | Description |
| ---- | ----------- |
| `run.py` | Unified startup script |
| `database/schema.sql` | Database structure |
| `database/seed.sql` | Demo data |
| `backend/public/` | Web entry point |

---

**Happy coding!**
