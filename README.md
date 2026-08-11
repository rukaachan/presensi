# Presensi Berbasis Foto

Web-based school attendance system using photo evidence, role-based access control, validation workflows, and PDF reporting. The local development setup uses SQLite; MySQL remains supported through portable Laravel services and constraints.

## Features

- Photo attendance with idempotent duplicate protection and private evidence storage
- Attendance history and status summaries
- Class-officer attendance validation
- Student, teacher, class, department, and account management
- Attendance filtering and PDF exports
- Append-only audit events, compatibility activity logs, and role-restricted routes

## Attendance policy

- Every student has one required daily check-in.
- Optional session events (breaks, check-out, or special schedules) are configurable and do not block the daily check-in.
- The default catalog keeps the three legacy break validation codes for route and data compatibility.
- Attendance dates and operational windows use `ATTENDANCE_TIMEZONE` (default: `Asia/Jakarta`).
- The state, permission, correction, and retention contract is documented in [`docs/attendance-policy.md`](docs/attendance-policy.md).
- The legacy migration design is documented in [`docs/attendance-migration.md`](docs/attendance-migration.md).

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
- Legacy triggers, procedures, and functions are deprecated and removed by migration; teacher and attendance workflows use portable application transaction paths instead.
- Attendance evidence is stored on the configured private disk (`ATTENDANCE_EVIDENCE_DISK`, default `local`) and served only through an authorized route.
- Production seeding runs reference data only. Do not run `DemoDatabaseSeeder` against production.
