# Presensi Berbasis Foto

Web-based school attendance system using photo evidence, role-based access control, validation workflows, and PDF reporting. The local development setup uses SQLite; MySQL remains supported for deployments that need the original database routines.

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
- SQLite for local development; MySQL is supported for production deployments
- Blade, Tailwind CSS, Oxbow UI, Vite, and pnpm
- PHPUnit, Laravel Pint, and Larastan

## Frontend direction

- Oxbow-inspired semantic tokens and utility vocabulary
- Asymmetric bento composition for role dashboards
- Utility-first tables, filters, forms, and status badges for operational pages
- One shared document shell and navigation system across every role
- Editorial detail sheets, camera capture states, validation workspaces, and print-ready reports
- Server-rendered Blade partials preserve the existing routes and workflows

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel and SQLite
- Composer
- SQLite support in PHP (`pdo_sqlite`)
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

The default `.env.example` configuration uses `database/database.sqlite`; create it if it does not exist, or set `DB_DATABASE` to an absolute SQLite path.

```bash
mkdir -p database
touch database/database.sqlite
php artisan migrate --seed
pnpm build
php artisan serve
```

Open `http://127.0.0.1:8000`. Camera access requires browser permission and a secure context, such as HTTPS or localhost.

In local development, `DatabaseSeeder` creates deterministic demo data through `DemoDatabaseSeeder`. Demo accounts use the password configured by `DEMO_SEED_PASSWORD` (default: `password123`):

| Role | Username |
|---|---|
| Tata Usaha | `tu.demo` |
| Wali Kelas | `wali.demo` |
| Guru Piket | `piket.demo` |
| Guru BK | `bk.demo` |
| Pengurus Kelas | `pengurus.demo` |
| Siswa | `siswa.demo` |

The demo seeder is restricted to `local` and `testing` environments. Run it explicitly when needed with `php artisan db:seed --class=DemoDatabaseSeeder`.

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
- The integrity migration adds unique constraints; audit duplicate legacy profile links before applying it. For a throwaway local database, use `php artisan migrate:fresh --seed`.
- SQLite skips MySQL-only stored procedures and functions; teacher creation uses the application transaction path instead.
- Production seeding runs reference data only. Do not run `DemoDatabaseSeeder` against production.
