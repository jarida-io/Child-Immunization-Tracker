# Contributing to Child Immunization Tracker

Thank you for your interest in contributing. This project is part of Jarida's open-source health technology portfolio and is a direct component of the ClimateShield AI platform. Contributions that improve reliability, security, and coverage of Kenya's national immunization schedule are especially welcome.

## Table of Contents

- [How to Contribute](#how-to-contribute)
- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)
- [Community Guidelines](#community-guidelines)

## How to Contribute

We welcome:

- **Bug fixes**: Issues with vaccination scheduling logic, role permissions, or email delivery
- **Security improvements**: Move hardcoded credentials to environment variables; input sanitisation; XSS prevention
- **SMS integration**: Connect an SMS gateway (Africa's Talking or similar) to replace/complement email notifications
- **Schema documentation**: Export full DDL for all tables as a `database.sql` setup file
- **Coverage analytics**: Sub-county / zone-level vaccination coverage reports
- **Export functionality**: CSV/Excel export for admin reports
- **Documentation**: Improve inline comments, setup guides, or API documentation

## Development Setup

### Requirements

- PHP 8.x
- MySQL 5.7+ or MariaDB 10.x
- Apache with `mod_rewrite` enabled (XAMPP or WAMP recommended for local dev)
- Composer

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/jarida-io/Child-Immunization-Tracker.git
   cd Child-Immunization-Tracker
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up the database**
   ```sql
   CREATE DATABASE immunization_tracker;
   USE immunization_tracker;
   SOURCE tfa_setup.sql;
   -- Additional table DDL is forthcoming in database.sql (see roadmap)
   ```

4. **Configure the database connection**

   Edit `includes/db.php`:
   ```php
   $host = 'localhost';
   $dbname = 'immunization_tracker';
   $username = 'your_mysql_user';
   $password = 'your_mysql_password';
   ```

5. **Configure email (PHPMailer)**

   Edit `includes/auth.php`:
   ```php
   $mail->Username = 'your_gmail_address@gmail.com';
   $mail->Password = 'your_gmail_app_password';
   ```
   Generate a Gmail app password at **myaccount.google.com → Security → App passwords**.

   > For production, move these values to environment variables (`.env` + `$_ENV`). Hardcoded credentials are intentionally avoided in production and are a known open issue.

6. **Place the project in your web server root**

   XAMPP example: `C:/xampp/htdocs/Child-Immunization-Tracker/`

7. **Visit the app**

   `http://localhost/Child-Immunization-Tracker`

## Project Structure

```
Child-Immunization-Tracker/
├── includes/
│   ├── db.php                  # PDO MySQL connection
│   ├── auth.php                # TFA, login, registration, PHPMailer config
│   ├── functions.php           # Vaccination scheduling, notifications, audit logging
│   └── update_location.php     # AJAX handler: user location update
├── pages/
│   ├── auth/
│   │   ├── login.php           # Login form → 2FA redirect
│   │   ├── register.php        # New user registration
│   │   ├── tfa_verify.php      # Email 2FA code verification
│   │   ├── forgot_password.php # Password reset email
│   │   └── reset_password.php  # New password form
│   ├── parent/
│   │   ├── dashboard.php       # Guardian: list children, progress
│   │   ├── register_child.php  # Register child + upload vaccination card
│   │   ├── child_profile.php   # View vaccination schedule and history
│   │   └── print_schedule.php  # PDF schedule via Dompdf
│   ├── doctor/
│   │   └── doctor_dashboard.php # Mark today's vaccines complete
│   ├── caregiver/
│   │   └── caregiver_dashboard.php # Assign/unassign children
│   ├── admin/
│   │   ├── admin_dashboard.php  # Stats and recent activity
│   │   ├── manage_vaccines.php  # CRUD vaccine master data
│   │   ├── verify_vacc_cards.php # Review and verify uploaded cards
│   │   ├── manage_roles.php     # Change user roles
│   │   ├── reports.php          # Filterable reports
│   │   └── system_logs.php      # Audit trail
│   ├── navbar.php               # Shared navigation header
│   ├── notifications.php        # In-app notification centre
│   └── logout.php
├── assets/
│   ├── css/styles.css           # Unified stylesheet
│   ├── js/scripts.js            # Mobile menu, minimal JS
│   └── child.webp               # App logo/mascot
├── uploads/children/            # Vaccination card uploads (timestamped filenames)
├── vendor/                      # Composer dependencies (PHPMailer, Dompdf)
├── composer.json
├── tfa_setup.sql                # TFA codes table DDL
└── .htaccess                    # Apache URL rewriting
```

## Coding Standards

### PHP

- Use **PDO prepared statements** for all database queries — no raw string interpolation with user input
- Sanitise all output with `htmlspecialchars()` before rendering in HTML to prevent XSS
- Use `password_hash()` and `password_verify()` for all password handling
- Keep page logic (PHP) and presentation (HTML) in the same file for now (this is the existing pattern); do not introduce a new templating system without discussion
- Validate file uploads: check MIME type, extension whitelist, and file size before `move_uploaded_file()`

### Database

- All queries must use PDO prepared statements with named or positional parameters
- No `SELECT *` — always name the columns you need
- Log all data-modifying operations to `system_logs`

### Commit Message Format

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add CSV export for missed vaccinations report
fix: prevent HealthcareGiver from backdating vaccinations
security: move PHPMailer credentials to environment variables
docs: add full database DDL as database.sql
refactor: extract notification logic into separate function
```

## Testing

There is currently no automated test suite. Testing is done manually against the local XAMPP environment.

**When submitting a bug fix or new feature, please include in your PR description:**

- The scenario you tested (e.g. "Registered a child, confirmed vaccination schedule was generated correctly for all 10 doses")
- The user role you used (Guardian / HealthcareGiver / SocialCaregiver / Admin)
- Any edge cases you checked

**Priority areas for adding automated tests:**
- Vaccination scheduling logic in `includes/functions.php` (pure PHP — easy to unit test)
- TFA code generation and expiry logic
- File upload validation

## Pull Request Process

1. Fork the repository and create a branch from `master`:
   ```bash
   git checkout -b fix/your-description
   ```
2. Make your changes following the coding standards above
3. Describe in the PR what you changed and how you tested it
4. Link any related issues
5. A maintainer will review, request changes if needed, and merge

### PR Checklist

- [ ] No credentials committed (no real email addresses, passwords, or API keys)
- [ ] PDO prepared statements used for any new queries
- [ ] Output sanitised with `htmlspecialchars()` for any new user-facing data
- [ ] File upload validation present for any new upload endpoints
- [ ] Manual test steps described in the PR description

## Issue Reporting

### Bug Reports

Please include:
- What you expected to happen
- What actually happened
- Steps to reproduce
- PHP error log output if applicable
- The user role you were logged in as

### Security Issues

Do not open a public issue for security vulnerabilities (especially credential exposure or SQL injection). Email **hello@jarida.io** with details.

## Community Guidelines

- Be respectful and constructive
- If you are new to PHP or web development, start with issues labelled `good first issue`
- For larger changes (new modules, schema changes), open an issue to discuss the approach before writing code
- Remember this system handles child health data — accuracy and correctness matter above all else

## Contact

- **Bug reports and feature requests**: [GitHub Issues](https://github.com/jarida-io/Child-Immunization-Tracker/issues)
- **General questions**: [GitHub Discussions](https://github.com/jarida-io/Child-Immunization-Tracker/discussions)
- **Security concerns**: hello@jarida.io

## License

By contributing, you agree that your contributions will be licensed under the Apache 2.0 License.
