# 🚀 Quick Reference - Development Commands

## Essential Commands

```bash
# Start development environment
make up

# Stop environment
make down

# View logs
make logs

# Check status
make status
```

## Database Migrations

```bash
# Run pending migrations
make migrate

# Check migration status
make migrate-status

# Create new migration
make migrate-create

# Validate migrations
make migrate-validate

# Create backup
make backup
```

## Development Tools

```bash
# Access web container shell
make shell

# Access MySQL shell
make mysql

# Restart services
make restart

# Run health checks
make health
```

## Emergency Commands

```bash
# Reset environment (DANGER: loses data)
make reset

# Complete cleanup (DANGER: removes everything)
make destroy

# Restore from backup
make restore BACKUP=filename.sql
```

## Application Access

- **Web Application**: http://localhost:8080
- **Database**: localhost:3306
- **Default Login**: admin / admin123

## File Locations

- **Migrations**: `sql/migrations/`
- **Logs**: `docker/logs/`
- **Backups**: `backups/`
- **Config**: `.env`

---

**💡 Tip**: Run `make help` to see all available commands!