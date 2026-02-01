# Clinic Management System

> A modern, secure, and fully-featured clinic management solution built with PHP 8.3, MySQL 8, and vanilla JavaScript.

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Table of Contents

- [Features](#-features)
- [Screenshots](#-screenshots)
- [Quick Start](#-quick-start)
- [Architecture](#-architecture)
- [Security](#-security)
- [API Documentation](#-api-documentation)
- [User Roles](#-user-roles)
- [Configuration](#-configuration)
- [Development](#-development)
- [Changelog](#-changelog)

---

## ✨ Features

### Core Modules
| Module           | Description                                                  |
| ---------------- | ------------------------------------------------------------ |
| **Dashboard**    | Role-specific KPIs, today's appointments, revenue summary    |
| **Doctors**      | Profiles, schedules, specializations, consultation fees      |
| **Staff**        | Receptionist management with full patient/appointment access |
| **Patients**     | Registration, contact info, appointment history              |
| **Appointments** | Smart scheduling with conflict detection, status tracking    |
| **Departments**  | Organize doctors by specialty area                           |
| **Medicines**    | Inventory tracking with stock management                     |
| **Billing**      | Invoice generation, payment tracking, due amounts            |
| **Reports**      | Appointments per doctor, revenue analysis, CSV exports       |
| **Settings**     | Clinic name, address, contact information                    |
| **Feedback**     | Patient satisfaction tracking                                |

### v1.1.0 Highlights
- 🔐 **Rate Limiting** - Brute-force protection (5 attempts → 15 min lockout)
- 🔑 **Password Reset** - Secure token-based recovery with 1-hour expiry
- 📊 **CSV Exports** - Download appointments & billing with descriptive filenames
- 🛡️ **HSTS** - Strict Transport Security for production
- ⚡ **Performance** - 4 composite database indexes
- 🎨 **UI Polish** - Empty states, skeleton loading, tooltips

---

## 📸 Screenshots

| Login                             | Dashboard             |
| --------------------------------- | --------------------- |
| Modern login with forgot password | Role-specific metrics |

| Appointments     | Reports            |
| ---------------- | ------------------ |
| Smart scheduling | CSV export buttons |

---

## 🚀 Quick Start

### Docker (Recommended)

```bash
# Clone and start
git clone <repository-url>
cd clinic_management_cursor
docker compose up -d --build

# Open http://localhost:8080
```

### Demo Credentials

| Role    | Email                 | Password      |
| ------- | --------------------- | ------------- |
| Admin   | `admin@clinic.test`   | `Admin@123`   |
| Doctor  | `doctor@clinic.test`  | `Doctor@123`  |
| Staff   | `staff@clinic.test`   | `Staff@123`   |
| Patient | `patient@clinic.test` | `Patient@123` |

### Manual Setup

```bash
# 1. Database
mysql -u root -p -e "CREATE DATABASE clinic_management CHARACTER SET utf8mb4;"
mysql -u root -p clinic_management < database/schema.sql
mysql -u root -p clinic_management < database/seed.sql

# 2. Environment
export CMS_DB_HOST=127.0.0.1
export CMS_DB_NAME=clinic_management
export CMS_DB_USER=root
export CMS_DB_PASS=your_password

# 3. Run
php -S 127.0.0.1:8000 -t backend/public
```

---

## 🏗️ Architecture

```
clinic_management_cursor/
├── backend/
│   ├── public/
│   │   ├── api.php          # API router (single entry point)
│   │   └── index.php        # SPA entry point
│   └── src/
│       ├── controllers/     # Business logic
│       ├── models/          # Database access
│       ├── core/            # Auth, Database, Security
│       └── middleware/      # CSRF, Rate limiting
├── assets/
│   ├── app.js               # Frontend SPA (vanilla JS)
│   ├── app.css              # Responsive dark theme
│   └── logo.svg             # Clinic logo
├── database/
│   ├── schema.sql           # Tables, indexes, constraints
│   └── seed.sql             # Demo data
├── docs/
│   ├── openapi.yaml         # OpenAPI 3.0 specification
│   ├── HUMAN_README.md      # User workflows guide
│   ├── HANDOVER_GUIDE.md    # Client setup guide
│   └── AI_AGENT_SPEC.md     # Technical spec
└── docker-compose.yml       # Docker orchestration
```

### Tech Stack

| Layer         | Technology              |
| ------------- | ----------------------- |
| **Backend**   | PHP 8.3, Apache 2.4     |
| **Database**  | MySQL 8.4               |
| **Frontend**  | Vanilla JavaScript SPA  |
| **Styling**   | Custom CSS (dark theme) |
| **Container** | Docker + Docker Compose |

---

## 🔐 Security

### Authentication & Authorization
- **bcrypt** password hashing
- **HttpOnly** session cookies
- **CSRF** double-submit cookie pattern
- **Role-based** access control (RBAC)

### Attack Prevention
| Protection        | Implementation                    |
| ----------------- | --------------------------------- |
| SQL Injection     | PDO prepared statements           |
| XSS               | Output encoding + CSP headers     |
| CSRF              | Token validation on state changes |
| Brute Force       | Rate limiting (5 attempts/15 min) |
| Session Hijacking | HttpOnly + Secure flags           |

### Security Headers
```
Content-Security-Policy: default-src 'self'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000 (prod)
Referrer-Policy: no-referrer
```

---

## 📖 API Documentation

Full OpenAPI 3.0 specification: [`docs/openapi.yaml`](docs/openapi.yaml)

### Key Endpoints

| Method | Endpoint                       | Description         |
| ------ | ------------------------------ | ------------------- |
| `POST` | `/auth/login`                  | Authenticate user   |
| `POST` | `/auth/request-password-reset` | Request reset token |
| `GET`  | `/reports/dashboard`           | Role-specific stats |
| `GET`  | `/reports/appointments/csv`    | Export appointments |
| `GET`  | `/reports/billing/csv`         | Export billing      |
| `GET`  | `/appointments`                | List appointments   |
| `POST` | `/appointments`                | Book appointment    |

### Response Format
```json
{
  "ok": true,
  "data": { ... },
  "meta": { "total": 50, "page": 1 }
}
```

### Error Format
```json
{
  "ok": false,
  "error": {
    "message": "Validation failed",
    "details": { "email": "Required" }
  }
}
```

---

## 👥 User Roles

### Admin
✅ Full system access  
✅ Manage doctors, staff, patients  
✅ Configure clinic settings  
✅ View all reports and analytics  

### Doctor
✅ View & update own appointments  
✅ Manage own schedule  
✅ Add patient history  
✅ View patient information  

### Staff (Reception)
✅ Manage patients  
✅ Book & reschedule appointments  
✅ Manage medicines inventory  
✅ Create bills & record payments  

### Patient
✅ Book own appointments  
✅ View bills & payment status  
✅ Submit feedback  
✅ View medical history  

---

## ⚙️ Configuration

### Environment Variables

| Variable      | Default             | Description       |
| ------------- | ------------------- | ----------------- |
| `CMS_DB_HOST` | `127.0.0.1`         | Database host     |
| `CMS_DB_PORT` | `3306`              | Database port     |
| `CMS_DB_NAME` | `clinic_management` | Database name     |
| `CMS_DB_USER` | `root`              | Database user     |
| `CMS_DB_PASS` | ``                  | Database password |
| `CMS_DEBUG`   | `0`                 | Enable debug mode |

### Config File
Edit `backend/src/config.php` for:
- CORS allowed origins
- Session settings
- HSTS toggle (`hsts_enabled`)

---

## 🛠️ Development

### Running Tests
```bash
# E2E tests
python e2e_test.py
```

### Rebuilding
```bash
docker compose down && docker compose up -d --build
```

### Database Reset
```bash
docker compose down -v
docker compose up -d --build
```

---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

### Latest: v1.1.0
- Rate limiting on login
- Password reset flow
- CSV exports with proper filenames
- HSTS security header
- Database indexes
- Empty states & skeleton loading

---

## 📚 Documentation

| Document                                         | Purpose           |
| ------------------------------------------------ | ----------------- |
| [CHANGELOG.md](CHANGELOG.md)                     | Version history   |
| [docs/HUMAN_README.md](docs/HUMAN_README.md)     | User workflows    |
| [docs/HANDOVER_GUIDE.md](docs/HANDOVER_GUIDE.md) | Client setup      |
| [docs/openapi.yaml](docs/openapi.yaml)           | API specification |
| [docs/AI_AGENT_SPEC.md](docs/AI_AGENT_SPEC.md)   | Technical details |

---

## 📄 License

MIT License - See [LICENSE](LICENSE) for details.

---

**Built with ❤️ for modern clinic management**
