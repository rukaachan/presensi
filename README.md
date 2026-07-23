# Presensi Berbasis Foto

Web-based school attendance system using photo evidence, role-based access control, validation workflows, and PDF reporting.

## Features

- Photo attendance with duplicate-submission checks
- Attendance history and status summaries
- Class-officer attendance validation
- Student, teacher, class, department, and account management
- Attendance filtering and PDF exports
- Activity logs and role-restricted routes

## Roles

1. Student
2. Homeroom teacher
3. Class officer
4. Duty teacher
5. Counseling teacher
6. Administration staff

## Technology

- Laravel 12 and PHP 8.2+
- MySQL
- Blade, Bootstrap, Vite, and pnpm
- PHPUnit, Laravel Pint, and Larastan

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel and MySQL
- Composer
- MySQL
- Node.js 20 or newer
- pnpm 10.30.1

## Installation

```bash
git clone https://github.com/rukaachan/presensi.git
cd presensi
composer install
pnpm install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database, then update the `DB_*` values in `.env`.

```bash
php artisan migrate --seed
pnpm build
php artisan serve
```

Open `http://127.0.0.1:8000`. Camera access requires browser permission and a secure context, such as HTTPS or localhost.

For frontend development, run `pnpm dev` in a second terminal.

## Quality Checks

```bash
composer check
```

This runs formatting checks, PHPStan analysis, and PHPUnit tests. Individual commands:

```bash
composer lint
composer test
composer format
pnpm build
```

GitHub Actions runs the same quality gate and frontend build for pull requests and pushes to `main`.

## Production Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Keep `.env`, credentials, uploaded photos, and generated assets out of Git.
- Configure HTTPS before enabling camera attendance for remote users.
- Back up the database and uploaded attendance evidence before deployment or migration.
