# TodoPHP

TodoPHP is a PHP MVC task-management application for a course final project.
It runs with Laragon/MySQL and includes task CRUD, authentication, custom
lists, calendar, Kanban, reports, activity log, attachments, email reminders,
JSON API, role middleware, Eloquent and deployment files.

## Quick start with Laragon

1. Start Apache and MySQL in Laragon.
2. Create/import the database from `database_setup.sql`. This is the only SQL file required.
3. Copy `.env.example` to `.env` and set local database values.
4. Install dependencies:

```powershell
D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -d extension=zip D:\laragon\bin\composer\composer.phar install
```

5. Open `http://todophp.test`.

## Main features

- MVC routing with Composer autoloading and PDO prepared statements.
- Registration, login, CSRF protection, session regeneration and profile update.
- Task CRUD, priority, progress, deadline, file upload, search, soft delete and restore.
- Calendar with several tasks per day, Kanban, reports, CSV export and activity log.
- Daily overdue email reminder script and database backup script.
- File cache for dashboard data, MySQL email queue with retry worker, and JSON-line application logs.
- Eloquent-backed JSON API protected by hashed Bearer tokens and admin middleware.
- PHPUnit, PSR-12 checker configuration, GitHub Actions workflow, issue/PR templates.
- Docker Compose deployment for multiple users.

## Quality commands

```powershell
composer test
composer lint
```

See [API documentation](docs/API.md), [deployment](docs/DEPLOYMENT.md), and
the [presentation demo script](docs/DEMO_SCRIPT.md).
