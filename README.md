# SorSU Scheduling System

## Overview

SorSU Scheduling System is a Laravel-based web app for generating and managing conflict-free class schedules with a two-level approval workflow (Program Head → Department Head). It covers curriculum setup, room allocation, faculty load, and role-based dashboards.

---

## Features

- Multi-role access: Admin, Department Head, Program Head, Instructor, Student
- Schedule generation with conflict checks (time, room, instructor)
- Curriculum, program, subject, and room management
- Faculty load management and assignment constraints
- Two-level schedule approval with notifications and audit trail
- Role-specific dashboards and schedule visibility

---

## Tech Stack

**Backend**

- PHP 8.2+
- Laravel 12
- MySQL/MariaDB (optional) or SQLite (default)

**Frontend**

- Vite
- Tailwind CSS 4
- Bootstrap 5.3

**Tooling**

- Composer
- Node.js + npm
- Pest (testing)

---

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ (or 20+)
- MySQL/MariaDB (optional) or SQLite

---

## Setup

### Quick Setup (recommended)

```bash
composer run setup
```

This runs `composer install`, creates `.env`, generates the app key, runs migrations, installs npm packages, and builds assets.

### Manual Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# SQLite (default)
# create database/database.sqlite
php artisan migrate --seed

npm install
npm run build
```

### Using MySQL/MariaDB

Update `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sorsu_scheduling
DB_USERNAME=root
DB_PASSWORD=
```

Then run:

```bash
php artisan migrate --seed
```

---

## Running Locally

```bash
composer run dev
```

This starts:

- Laravel dev server
- Queue listener
- Vite dev server

Then open: `http://localhost:8000`

---

## Useful Commands

```bash
php artisan test
php artisan migrate:fresh --seed
npm run build
npm run dev
```

---

## Project Structure

```
app/                # Controllers, Models, Services, Policies
config/             # Laravel configuration
database/           # Migrations and seeders
resources/          # Views, JS, CSS/Sass
routes/             # Route definitions
public/             # Public assets and entry point
```

---

## Notes

- Default `.env` uses SQLite. Create `database/database.sqlite` if you keep SQLite.
- Email is configured to log by default (`MAIL_MAILER=log`).

---
