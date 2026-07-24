# Deployment

## Local Laragon

1. Start Apache and MySQL in Laragon.
2. Import the single `database_setup.sql` file. It creates the complete schema.
3. Copy `.env.example` to `.env` and set database values.
4. Run Composer install with Laragon PHP.
5. Point the virtual host document root to `public`.

## Docker

```bash
docker compose up --build
```

Open `http://localhost:8080`. For production, replace all sample credentials
in `docker-compose.yml` with secret environment variables, set `APP_DEBUG=false`,
enable HTTPS at the reverse proxy, and schedule `scripts/run_reminders.cmd` or
the equivalent server cron command.
