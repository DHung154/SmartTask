# Testing checklist

## Automated

```bash
composer test
composer lint
```

The unit test covers progress normalization and completion behavior. The CI
workflow additionally lints every PHP source file.

## Manual browser checks

- Register, log in, log out, and reject an invalid CSRF form submission.
- Create, edit, mark complete, delete and restore a task.
- Add two or more tasks with the same deadline and confirm all appear in Calendar.
- Check Kanban state changes, report CSV export and activity log.
- Confirm a normal user cannot access the admin API endpoint.
- Run `scripts/backup_database.php` and confirm an SQL dump appears in `storage/backups`.
