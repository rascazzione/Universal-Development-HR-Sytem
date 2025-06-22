# 🚀 Developer Guide - PHP Performance Evaluation System

## Quick Start (5 Minutes)

```bash
# 1. Clone and setup
git clone <repository-url>
cd web_object_classification

# 2. Start development environment
make up

# 3. Access application
open http://localhost:8080
```

**Default Login**: `admin` / `admin123`

---

## 📋 Table of Contents

- [Environment Setup](#environment-setup)
- [Daily Development Workflow](#daily-development-workflow)
- [Database Management](#database-management)
- [Common Tasks](#common-tasks)
- [Troubleshooting](#troubleshooting)
- [Advanced Usage](#advanced-usage)

---

## 🛠️ Environment Setup

### Prerequisites
- **Docker** (20.10+)
- **Docker Compose** (2.0+)
- **Make** (for command shortcuts)
- **Git**

### Initial Setup

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd web_object_classification
   ```

2. **Environment Configuration**
   ```bash
   # Copy environment template
   cp .env.example .env
   
   # Edit if needed (optional for development)
   nano .env
   ```

3. **Start Environment**
   ```bash
   make up
   ```

4. **Verify Setup**
   ```bash
   make status
   make migrate-status
   ```

---

## 🔄 Daily Development Workflow

### Starting Your Day
```bash
# Start environment
make up

# Check migration status
make migrate-status

# View logs if needed
make logs
```

### During Development
```bash
# View real-time logs
make logs

# Access container shell for debugging
make shell

# Check database directly
make mysql
```

### End of Day
```bash
# Stop environment (keeps data)
make down

# Or keep running in background (recommended)
# Just close terminal
```

---

## 🗄️ Database Management

### Migration Commands

| Command | Purpose | When to Use |
|---------|---------|-------------|
| `make migrate` | Run pending migrations | After pulling code, daily startup |
| `make migrate-status` | Check migration state | Verify current database version |
| `make migrate-create` | Create new migration | Adding new tables/columns |
| `make migrate-validate` | Validate migration files | Before committing changes |
| `make backup` | Create database backup | Before major changes |

### Creating Database Changes

1. **Create Migration**
   ```bash
   make migrate-create
   # Enter description: "Add user preferences table"
   ```

2. **Edit Migration File**
   ```bash
   # File created: sql/migrations/2025_06_22_HHMMSS_add_user_preferences_table.sql
   nano sql/migrations/2025_06_22_HHMMSS_add_user_preferences_table.sql
   ```

3. **Add Your SQL**
   ```sql
   -- Migration: Add user preferences table
   -- Created: 2025-06-22 10:30:00

   USE performance_evaluation;

   START TRANSACTION;

   CREATE TABLE user_preferences (
       id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       preference_key VARCHAR(100) NOT NULL,
       preference_value TEXT,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
       UNIQUE KEY unique_user_preference (user_id, preference_key)
   );

   COMMIT;

   SELECT 'Migration Add user preferences table completed successfully' as result;
   ```

4. **Test Migration**
   ```bash
   make migrate-validate
   make migrate
   ```

5. **Verify Results**
   ```bash
   make migrate-status
   make mysql
   # In MySQL: DESCRIBE user_preferences;
   ```

### Database Operations

```bash
# Access MySQL shell
make mysql

# Create backup
make backup

# View database logs
make logs-db

# Reset database (DANGER: loses all data)
make reset
```

---

## 🔧 Common Tasks

### Application Development

```bash
# View application logs
make logs-web

# Access web container for debugging
make shell

# Restart services after config changes
make restart

# View all container status
make status
```

### Code Changes

```bash
# Files are automatically synced (hot reload)
# Edit files in your IDE, changes appear immediately

# For PHP configuration changes, restart:
make restart
```

### Testing

```bash
# Run health checks
make health

# Validate migrations
make migrate-validate

# Check container resource usage
make status
```

---

## 🚨 Troubleshooting

### Common Issues

#### 1. **Containers Won't Start**
```bash
# Check Docker status
docker ps

# View detailed logs
make logs

# Rebuild containers
make rebuild
```

#### 2. **Database Connection Errors**
```bash
# Check MySQL container
docker ps | grep mysql

# View database logs
make logs-db

# Restart database
make restart
```

#### 3. **Migration Failures**
```bash
# Check migration status
make migrate-status

# View detailed error
make logs

# Restore from backup if needed
make restore BACKUP=backup_20250622_120000.sql
```

#### 4. **Permission Issues**
```bash
# Fix file permissions
sudo chown -R $USER:$USER .

# Restart containers
make restart
```

#### 5. **Port Conflicts**
```bash
# Check what's using port 8080
lsof -i :8080

# Change port in .env file
nano .env
# Edit: WEB_PORT=8081

# Restart
make down && make up
```

### Emergency Recovery

#### Reset Everything
```bash
# DANGER: This removes all data
make destroy
make up
```

#### Restore from Backup
```bash
# List available backups
ls backups/

# Restore specific backup
make restore BACKUP=backup_20250622_120000.sql
```

---

## 🔍 Advanced Usage

### Environment Customization

#### Custom PHP Configuration
```bash
# Edit PHP settings
nano docker/web/php.ini

# Restart to apply
make restart
```

#### Custom Database Settings
```bash
# Edit database configuration
nano docker-compose.yml

# Rebuild and restart
make rebuild
```

### Development Tools

#### Debugging
```bash
# Enable Xdebug (if configured)
# Edit .env
ENABLE_XDEBUG=true

# Restart
make restart
```

#### Database Administration
```bash
# Access MySQL with full privileges
make mysql

# Or use external tool:
# Host: localhost
# Port: 3306
# User: root
# Password: (from .env DB_ROOT_PASSWORD)
```

### Performance Monitoring

```bash
# Monitor resource usage
make status

# View detailed container stats
docker stats

# Monitor logs in real-time
make logs
```

---

## 📁 Project Structure

```
web_object_classification/
├── .env                    # Environment configuration
├── .env.example           # Environment template
├── Makefile              # Development commands
├── README_DEV.md         # This file
├── docker-compose.yml    # Container orchestration
├── 
├── sql/                  # Database files
│   ├── migrations/       # Migration files
│   ├── migration_runner.php # Migration management
│   └── fixes/           # Schema fix scripts
├── 
├── scripts/             # Utility scripts
│   ├── backup_database.sh
│   └── apply_schema_fixes.sh
├── 
├── config/              # Application configuration
├── classes/             # PHP classes
├── public/              # Web accessible files
├── templates/           # HTML templates
└── docs/               # Documentation
```

---

## 🎯 Best Practices

### Development Workflow

1. **Always start with migrations**
   ```bash
   make migrate-status
   make migrate
   ```

2. **Create backups before major changes**
   ```bash
   make backup
   ```

3. **Validate migrations before committing**
   ```bash
   make migrate-validate
   ```

4. **Use descriptive migration names**
   ```bash
   # Good: "Add employee performance metrics table"
   # Bad: "Update database"
   ```

### Code Organization

1. **Keep migrations atomic** - One logical change per migration
2. **Always use transactions** - Wrap changes in START TRANSACTION/COMMIT
3. **Add proper foreign keys** - Maintain referential integrity
4. **Include rollback information** - Document how to reverse changes

### Team Collaboration

1. **Pull before starting work**
   ```bash
   git pull
   make migrate
   ```

2. **Commit migrations with code**
   ```bash
   git add sql/migrations/
   git commit -m "Add user preferences feature with migration"
   ```

3. **Share environment issues** - Document solutions in this file

---

## 🆘 Getting Help

### Quick Reference
```bash
# Show all available commands
make help

# Check system status
make status

# View logs
make logs

# Get migration status
make migrate-status
```

### Documentation
- **Database Migrations**: [`docs/DATABASE_MIGRATION_IMPLEMENTATION.md`](docs/DATABASE_MIGRATION_IMPLEMENTATION.md)
- **Architecture**: [`docs/ARCHITECTURE_DESIGN.md`](docs/ARCHITECTURE_DESIGN.md)
- **Docker Setup**: [`DOCKER_SETUP_COMPLETE.md`](DOCKER_SETUP_COMPLETE.md)

### Support Channels
1. Check this README first
2. Review error logs: `make logs`
3. Check migration status: `make migrate-status`
4. Create backup before experimenting: `make backup`

---

## 🎉 Happy Coding!

This development environment is designed to be:
- **Fast** - Hot reload for immediate feedback
- **Safe** - Automated backups and migration safety
- **Consistent** - Same environment for all developers
- **Simple** - One-command operations for common tasks

**Remember**: When in doubt, `make backup` first! 🛡️

---

*Last updated: June 22, 2025*