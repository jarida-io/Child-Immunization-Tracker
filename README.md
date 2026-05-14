# Child Immunization Tracker

<div align="center">

**An open-source digital immunization tracking system for community health workers and parents in Kenya**

[![PHP](https://img.shields.io/badge/Backend-PHP%208.x-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/Database-MySQL-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)

</div>

## Table of Contents

- [What It Does](#what-it-does)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [User Roles](#user-roles)
- [Features](#features)
- [Current Status](#current-status)
- [Screenshots](#screenshots)
- [How to Run Locally](#how-to-run-locally)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)

## What It Does

The Child Immunization Tracker is a web-based system that helps parents, healthcare workers, and administrators in Kenya manage child immunization records against the national immunization schedule. It automates vaccination scheduling, tracks completion and missed doses, generates printable vaccination reports, and sends email reminders to parents ahead of upcoming appointments.

The system is designed for low-resource settings: it runs on a standard LAMP stack (Linux, Apache, MySQL, PHP) and requires no cloud infrastructure beyond a Gmail SMTP account for email delivery.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x with PDO (MySQL) |
| Frontend | HTML5 / CSS3 / JavaScript (vanilla) |
| Email | PHPMailer 6.x via Gmail SMTP |
| PDF generation | Dompdf 3.x |
| Icons | Font Awesome 6.x |
| Server | Apache (`.htaccess` URL rewriting) |
| Auth | Session-based + email two-factor authentication |

## Database Schema

The system uses ten tables. The full DDL for the TFA table is in `tfa_setup.sql`; the remaining tables are created during initial setup.

| Table | Purpose |
|---|---|
| `users` | All system users: guardians, healthcare workers, caregivers, admins |
| `children` | Child records linked to a guardian, with vaccination card path |
| `vaccines` | Vaccine master data: name, disease prevented, recommended age (days from birth), dose info |
| `vaccination_schedule` | Scheduled, completed, and missed vaccine doses per child |
| `vaccination_cards` | Uploaded card images with verification status |
| `notifications` | In-app appointment reminders per user/child |
| `tfa_codes` | 6-digit email two-factor authentication codes (10-minute TTL) |
| `password_resets` | Time-limited password reset tokens (1-hour TTL) |
| `caregiver_assignments` | Many-to-many: caregivers assigned to children |
| `system_logs` | Full audit trail of all user actions |

### Key Relationships

```
users
 ├── children (guardian_id)
 │    ├── vaccination_schedule (vaccine_id → vaccines)
 │    ├── vaccination_cards
 │    └── caregiver_assignments ↔ users (caregiver)
 ├── notifications (user_id, child_id)
 └── system_logs (user_id)
```

## User Roles

The system has four roles with distinct dashboards and permissions:

| Role | Who | Key Permissions |
|---|---|---|
| **Guardian** | Parent or primary caregiver | Register children, upload vaccination cards, view schedules, print PDF schedule, receive notifications |
| **HealthcareGiver** | Clinic or hospital staff | Select any child, mark today's scheduled vaccines as completed |
| **SocialCaregiver** | Community health worker | Assigned to specific children; view vaccination status |
| **Admin** | Facility administrator | Full access: manage vaccines, verify uploaded cards, mark vaccinations complete (any date), generate reports, manage user roles, view audit logs |

> Healthcare workers can only mark vaccinations complete for the **current date** — backdating is restricted to Admin.

## Features

### Authentication & Security

- User registration with national ID, phone number, email, and county location
- Password hashing using PHP `password_hash()` (bcrypt)
- **Email-based two-factor authentication**: 6-digit code sent via PHPMailer after login; 10-minute expiry; auto-submits on 6-digit entry
- Password reset via time-limited email token (SHA256, 1-hour TTL)
- Session management with activity tracking and `session_regenerate_id()` on login
- All 47 Kenyan counties available in location dropdown

### Child Registration

- Register children with name, date of birth, gender, and unique Health ID (birth certificate / baby ID number)
- Upload vaccination card image (JPEG, PNG, or PDF; max 5 MB)
- Age calculated automatically (years, months, days from DOB)
- On registration, the system auto-generates the child's complete vaccination schedule based on Kenya's national immunization schedule

### Automated Vaccination Scheduling

- Reads vaccine master data (recommended age in days from birth) and calculates due dates for every dose
- Creates a pending schedule entry for each vaccine on child registration
- Marks doses as **Missed** automatically if the due date has passed and the status is still Pending
- Statuses: **Pending → Completed** or **Missed**

### Email Notifications & Reminders

- Automatic in-app reminder created 7 days before each vaccine due date
- Email notifications sent (via PHPMailer / Gmail SMTP) when:
  - A vaccination is marked as completed
  - A vaccination is marked as missed
  - An appointment reminder is due
- In-app notification centre grouped by child
- Unread count badge on navigation bar

### Vaccination Card Management

- Parents upload card images or PDFs during child registration (`uploads/children/`)
- Admin dashboard shows unverified cards queue
- Admin verification workflow: mark verified, record administration date, add notes (AJAX submission)

### PDF Schedule Printing

- Any guardian can generate a PDF of their child's complete vaccination schedule
- Generated using Dompdf; includes child info, vaccine names, diseases prevented, due dates, and completion status

### Reporting & Analytics (Admin)

Dashboard statistics:
- Total children, total users
- Vaccinations: completed, pending, missed, upcoming

Filterable reports:
- Children list
- Completed / pending / missed vaccinations (with guardian and caregiver names)
- Users directory
- System activity log (last 100 entries with user attribution)

### Vaccine Master Management (Admin)

Full CRUD for the vaccine catalogue:
- Vaccine name, disease prevented, recommended age (days from birth), dose number
- Route and site of administration, known side effects, dose description

### Audit Logging

Every significant action — login, registration, vaccination marked complete/missed, card verification, role change — is recorded in `system_logs` with user ID and timestamp.

## Current Status

Working prototype. Tested with 50+ simulated child records across 3 health worker accounts. The core immunization tracking workflow (register child → auto-schedule → mark complete / missed → notify guardian) is fully functional.

**What is production-ready:**
- Role-based access control and authentication (including 2FA)
- Automated vaccination scheduling per Kenya's national immunization schedule
- Email notifications and in-app reminders
- Vaccination card upload and admin verification
- PDF schedule printing
- Admin reporting and audit logging

**In progress / planned:**
- SMS appointment reminders (email-only currently; SMS gateway integration is planned as part of the ClimateShield AI alert pipeline)
- Environment-variable-based configuration (credentials are currently hardcoded for local development)
- Zone / sub-county coverage analytics

## Screenshots

[Add 2–3 screenshots here — child list screen, dashboard, and add-child form from your local dev environment]

## How to Run Locally

**Requirements**: PHP 8.x, MySQL, Apache (XAMPP or WAMP works well on Windows)

```bash
# 1. Clone the repository
git clone https://github.com/jarida-io/Child-Immunization-Tracker.git

# 2. Place the folder in your web server's document root
#    e.g. C:/xampp/htdocs/ on XAMPP

# 3. Import the TFA table
#    In phpMyAdmin or MySQL CLI:
mysql -u root -p your_database < tfa_setup.sql

# 4. Configure your database connection
#    Edit includes/db.php — set host, database name, username, password

# 5. Configure email (PHPMailer)
#    Edit includes/auth.php — set Gmail address and app-specific password
#    (Generate an app password at myaccount.google.com → Security → App passwords)

# 6. Install Composer dependencies
composer install

# 7. Visit the app
http://localhost/Child-Immunization-Tracker
```

> For production deployment, move credentials to environment variables and configure SMTP settings via a `.env` file rather than hardcoding them.

## Roadmap

This tracker is the data backbone for **ClimateShield AI** — Jarida's ML-powered, climate-responsive immunization alert platform.

ClimateShield AI will extend this system by:
- Ingesting real-time weather data for Kenyan counties
- Predicting disease outbreak risk 7–14 days ahead (cholera, malaria, pneumonia, meningitis)
- Querying this tracker for under-vaccinated children in high-risk counties
- Sending SMS/USSD alerts to parents and community health workers before outbreaks peak

See: [github.com/jarida-io/climateshield-ai](https://github.com/jarida-io/climateshield-ai)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for setup instructions, coding standards, and contribution guidelines.

## License

Apache 2.0 — see [LICENSE](LICENSE)

```
Copyright 2024 Jarida Open Source

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0
```
