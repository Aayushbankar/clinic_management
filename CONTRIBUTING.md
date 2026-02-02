# Contributing to Clinic Management System

Thank you for considering contributing to the Clinic Management System!

## 🚀 Getting Started

### Prerequisites
- Python 3.x
- PHP 8.3+ (via XAMPP or standalone)
- MySQL 8.x
- Git

### Development Setup

```bash
# Clone
git clone <repository-url>
cd clinic_management_cursor

# Run (Auto-detects Docker vs Native)
python run.py

# Access at http://127.0.0.1:8000 (Windows) or http://localhost:8080 (Docker)
```

## 🔧 Development Workflow

### Code Style
- **PHP**: Follow PSR-12
- **JavaScript**: ES6+ with consistent indentation
- **CSS**: BEM-like naming, CSS custom properties

### Testing

```bash
# Run E2E tests
python e2e_test.py
```

### Making Changes

1. Create a feature branch: `git checkout -b feature/your-feature`
2. Make your changes
3. Test thoroughly
4. Submit a pull request

## 📁 Project Structure

```
backend/src/
├── controllers/    # HTTP handlers
├── models/         # Database access layer
├── core/           # Auth, Database, Response helpers
└── middleware/     # CSRF, Rate limiting
```

## 🔐 Security Guidelines

- Always use prepared statements (PDO)
- Validate and sanitize all inputs
- Check authorization for every endpoint
- Never expose sensitive data in responses

## 📝 Commit Messages

Use conventional commits:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation
- `refactor:` Code refactoring
- `test:` Test additions

## 📄 License

By contributing, you agree that your contributions will be licensed under MIT.
