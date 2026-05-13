# Child Immunization Tracker

An open-source digital immunization tracking system for community health workers and parents in Kenya.

## What It Does

- Register children and track their immunization records
- Manage vaccination schedules per Kenya's national immunization schedule
- Send SMS appointment reminders to parents via a mobile SMS gateway
- Provide health worker dashboards with immunization status reports
- Generate vaccination coverage reports by zone

## Tech Stack

- Backend: PHP 8.x, MySQL
- Frontend: HTML / CSS / JavaScript
- SMS: mobile SMS gateway integration
- Auth: Two-factor authentication

## Current Status

Working prototype. Tested with 50+ simulated child records across 3 health worker accounts. SMS delivery confirmed under 8 seconds average via SMS gateway sandbox testing.

## Screenshots

[Add 2-3 screenshots here — local dev environment is fine]

## How to Run Locally

1. Clone: git clone github.com/jarida-io/Child-Immunization-Tracker
2. Set up PHP/MySQL environment (XAMPP or WAMP)
3. Import tfa_setup.sql into your MySQL database
4. Configure database credentials in includes/config.php
5. Set SMS gateway API credentials in includes/sms.php
6. Visit http://localhost/Child-Immunization-Tracker

## Roadmap

This tracker is the data backbone for ClimateShield AI — our ML-powered climate-responsive immunization alert platform. See: github.com/jarida-io/climateshield-ai

## License

Apache 2.0 — see LICENSE
