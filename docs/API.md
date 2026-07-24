# JSON API

The API uses a Bearer token and returns JSON only. Tokens are stored hashed in
the database. Generate a token locally:

```powershell
D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe scripts\generate_api_token.php test@example.com
```

Use the value printed once as `TOKEN`:

```bash
curl -H "Authorization: Bearer TOKEN" http://todophp.test/api/v1/summary
curl -H "Authorization: Bearer TOKEN" http://todophp.test/api/v1/tasks
curl -X POST http://todophp.test/api/v1/tasks -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -d '{"title":"Read API docs","priority":"high","progress":25}'
```

Endpoints:

| Method | Path | Permission |
| --- | --- | --- |
| GET | `/health` | public |
| GET | `/api/v1/tasks` | token owner |
| POST | `/api/v1/tasks` | token owner |
| GET | `/api/v1/summary` | token owner |
| GET | `/api/v1/admin/summary` | admin token |

The API validates title, priority, date format and progress before writing.
