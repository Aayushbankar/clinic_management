# 🖥️ Windows Local Setup Guide (Without Docker)

> Step-by-step instructions for running the Clinic Management System on Windows without Docker.

---

## ⚡ Quick Start (Automated)

For a one-command setup, use the PowerShell script:

```powershell
# Run as Administrator
.\setup.ps1
```

This will automatically:
- ✅ Install PHP 8.3 and MySQL 8.4 (if missing)
- ✅ Create database and dedicated user
- ✅ Import schema and seed data
- ✅ Start the development server

**Script Options:**
| Command                | Description               |
| ---------------------- | ------------------------- |
| `.\setup.ps1`          | Full setup + start server |
| `.\setup.ps1 -Install` | Install dependencies only |
| `.\setup.ps1 -Start`   | Start server (skip setup) |
| `.\setup.ps1 -Reset`   | Reset database + start    |

---

## 📋 Manual Setup Prerequisites

Before starting, ensure you have the following installed:

### 1. PHP 8.3+

1. Download PHP from [windows.php.net](https://windows.php.net/download/)
   - Choose **VS16 x64 Thread Safe** ZIP
2. Extract to `C:\php`
3. Add `C:\php` to your system **PATH**:
   - Open **Settings** → **System** → **About** → **Advanced system settings**
   - Click **Environment Variables**
   - Under **System variables**, find `Path` and click **Edit**
   - Add `C:\php`
4. Enable required extensions in `C:\php\php.ini`:
   - Copy `php.ini-development` to `php.ini`
   - Uncomment (remove `;`) these lines:
     ```ini
     extension=pdo_mysql
     extension=mysqli
     extension=openssl
     extension=mbstring
     ```
5. Verify installation:
   ```powershell
   php -v
   # Should show PHP 8.3.x
   ```

### 2. MySQL 8.4+

1. Download MySQL Installer from [dev.mysql.com](https://dev.mysql.com/downloads/installer/)
2. Run the installer and choose **Server only** (or **Full** if you want MySQL Workbench)
3. During setup:
   - Set root password (remember this!)
   - Keep default port `3306`
   - Configure MySQL as a Windows Service
4. Verify installation:
   ```powershell
   mysql --version
   # Should show mysql Ver 8.4.x
   ```

> [!TIP]
> If `mysql` is not recognized, add `C:\Program Files\MySQL\MySQL Server 8.4\bin` to your PATH.

### 3. Git (Optional but Recommended)

Download from [git-scm.com](https://git-scm.com/download/win) for cloning the repository.

---

## 🛠️ Setup Steps

### Step 1: Clone or Download the Project

```powershell
git clone <repository-url>
cd clinic_management_cursor
```

Or download and extract the ZIP file.

---

### Step 2: Create the Database

Open **PowerShell** or **Command Prompt** and run:

```powershell
# Login to MySQL as root
mysql -u root -p
```

Enter your root password when prompted, then run these SQL commands:

```sql
-- Create database
CREATE DATABASE clinic_management CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Exit MySQL
EXIT;
```

---

### Step 3: Import Schema and Seed Data

From the project root directory:

```powershell
# Import database schema (creates all tables)
mysql -u root -p clinic_management < database\schema.sql

# Import demo/seed data (creates demo users and sample data)
mysql -u root -p clinic_management < database\seed.sql
```

---

### Step 4: Set Environment Variables

Open **PowerShell** and set the environment variables:

#### Option A: Temporary (Current Session Only)

```powershell
$env:CMS_DB_HOST = "127.0.0.1"
$env:CMS_DB_PORT = "3306"
$env:CMS_DB_NAME = "clinic_management"
$env:CMS_DB_USER = "root"
$env:CMS_DB_PASS = "your_mysql_root_password"
$env:CMS_DEBUG = "1"
```

#### Option B: Permanent (Recommended)

1. Open **Settings** → **System** → **About** → **Advanced system settings**
2. Click **Environment Variables**
3. Under **User variables**, click **New** and add each:

| Variable Name | Value                      |
| ------------- | -------------------------- |
| `CMS_DB_HOST` | `127.0.0.1`                |
| `CMS_DB_PORT` | `3306`                     |
| `CMS_DB_NAME` | `clinic_management`        |
| `CMS_DB_USER` | `root`                     |
| `CMS_DB_PASS` | `your_mysql_root_password` |
| `CMS_DEBUG`   | `1`                        |

> [!WARNING]
> Replace `your_mysql_root_password` with your actual MySQL root password.

---

### Step 5: Start the PHP Development Server

Navigate to the project root and run:

```powershell
php -S 127.0.0.1:8000 -t backend\public
```

You should see:

```
PHP 8.3.x Development Server started at http://127.0.0.1:8000
Document root is C:\path\to\clinic_management_cursor\backend\public
Press Ctrl+C to quit.
```

---

### Step 6: Access the Application

Open your browser and navigate to:

```
http://127.0.0.1:8000
```

---

## 🔑 Demo Credentials

Use these accounts to log in and explore the system:

| Role    | Email                 | Password      |
| ------- | --------------------- | ------------- |
| Admin   | `admin@clinic.test`   | `Admin@123`   |
| Doctor  | `doctor@clinic.test`  | `Doctor@123`  |
| Staff   | `staff@clinic.test`   | `Staff@123`   |
| Patient | `patient@clinic.test` | `Patient@123` |

---

## 🔧 Troubleshooting

### PHP is not recognized

**Solution:** Ensure `C:\php` is in your system PATH. Restart PowerShell/CMD after adding it.

### MySQL is not recognized

**Solution:** Add MySQL's `bin` folder to PATH:
```
C:\Program Files\MySQL\MySQL Server 8.4\bin
```

### Access denied for user 'root'

**Solution:** Verify your `CMS_DB_PASS` environment variable matches your MySQL root password.

### PDO Exception: could not find driver

**Solution:** Enable `pdo_mysql` in `php.ini`:
1. Open `C:\php\php.ini`
2. Find `;extension=pdo_mysql` and remove the `;`
3. Restart the PHP server

### Port 8000 is already in use

**Solution:** Use a different port:
```powershell
php -S 127.0.0.1:8080 -t backend\public
```
Then access via `http://127.0.0.1:8080`

### MySQL Service Not Running

**Solution:** Start the MySQL service:
```powershell
net start MySQL84
```

Or via **Services** (Win+R → `services.msc` → find MySQL → Start)

---

## 📁 Project Structure

```
clinic_management_cursor/
├── backend/
│   ├── public/
│   │   ├── api.php      ← API entry point
│   │   └── index.php    ← Frontend SPA
│   └── src/             ← PHP source code
├── assets/
│   ├── app.js           ← Frontend JavaScript
│   └── app.css          ← Styles
├── database/
│   ├── schema.sql       ← Database structure
│   └── seed.sql         ← Demo data
└── docs/                ← Documentation
```

---

## 🔄 Database Reset

To reset the database and start fresh:

```powershell
mysql -u root -p -e "DROP DATABASE clinic_management; CREATE DATABASE clinic_management CHARACTER SET utf8mb4;"
mysql -u root -p clinic_management < database\schema.sql
mysql -u root -p clinic_management < database\seed.sql
```

---

## 📝 Quick Reference Commands

| Action              | Command                                   |
| ------------------- | ----------------------------------------- |
| Start server        | `php -S 127.0.0.1:8000 -t backend\public` |
| Check PHP version   | `php -v`                                  |
| Check MySQL version | `mysql --version`                         |
| Login to MySQL      | `mysql -u root -p`                        |
| Start MySQL service | `net start MySQL84`                       |
| Stop MySQL service  | `net stop MySQL84`                        |

---

## 🚀 Next Steps

1. Explore the [API Documentation](openapi.yaml)
2. Read the [User Workflows Guide](HUMAN_README.md)
3. Check the [Handover Guide](HANDOVER_GUIDE.md) for deployment info

---

**Happy coding! 🩺**
