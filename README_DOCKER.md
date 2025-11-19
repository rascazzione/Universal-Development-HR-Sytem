# Docker Development Environment
## PHP Universal-Development-HR-System

### 🚀 Quick Start

Get the development environment running in under 5 minutes:

```bash
# 1. Initial setup (first time only)
make install

# 2. Start the environment
make up

# 3. Access the application
open http://localhost:8080
```

**Default Login:**
- Username: `admin`
- Password: `admin123`

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Development Workflow](#development-workflow)
5. [Available Commands](#available-commands)
6. [Configuration](#configuration)
7. [Troubleshooting](#troubleshooting)
8. [Advanced Usage](#advanced-usage)

---

## Prerequisites

### Required Software
- **Docker** 20.10+ ([Install Docker](https://docs.docker.com/get-docker/))
- **Docker Compose** 2.0+ ([Install Docker Compose](https://docs.docker.com/compose/install/))
- **Make** (usually pre-installed on Linux/macOS)

### System Requirements
- **CPU**: 2+ cores
- **Memory**: 2GB+ available RAM
- **Disk**: 5GB+ available space
- **OS**: Linux, macOS, or Windows with WSL2

### Verify Installation
```bash
docker --version          # Should show 20.10+
docker-compose --version  # Should show 2.0+
make --version            # Should show GNU Make
```

---

## Installation

### 1. Clone and Setup
```bash
# Navigate to project directory
cd /path/to/your/project

# Initial setup
make install
```

This will:
- Copy `.env.example` to `.env`
- Create necessary log directories
- Set up the development environment

### 2. Configure Environment (Optional)
Edit `.env` file to customize settings:
```bash
# Database settings
DB_NAME=performance_evaluation
DB_USER=app_user
DB_PASSWORD=your_secure_password

# Port settings (if 8080 is in use)
WEB_PORT=8081
DB_PORT=3307
```

### 3. Start the Environment
```bash
make up
```

Wait for the startup process to complete. You'll see:
```
✅ Database is ready
✅ Web server is ready
🌐 Application URL: http://localhost:8080
```

---

## Usage

### Starting Development
```bash
# Start everything
make up

# Check status
make status

# View logs
make logs
```

### Daily Development
```bash
# View real-time logs
make logs

# Access container shell
make shell

# Run health checks
make health
```

### Stopping Development
```bash
# Stop containers (preserves data)
make down

# Reset environment (fresh database)
make reset

# Complete cleanup
make destroy
```

---

## Development Workflow

### 🔄 Hot Reload
Code changes are immediately reflected:
1. Edit any PHP file
2. Refresh browser
3. Changes are live instantly

### 💾 Data Persistence
- Database data survives container restarts
- Only removed with `make reset` or `make destroy`

### 🔧 Configuration Changes
1. Edit `.env` file
2. Run `make restart`
3. Changes take effect

### 🗄️ Database Access
```bash
# MySQL command line
make mysql

# Database shell
make shell-db

# Backup database
make backup

# Restore from backup
make restore BACKUP=backup_20231201_120000.sql
```

---

## Available Commands

### Core Commands
| Command | Description |
|---------|-------------|
| `make up` | Start the development environment |
| `make down` | Stop the development environment |
| `make restart` | Restart all services |
| `make status` | Show container status and resource usage |

### Development Commands
| Command | Description |
|---------|-------------|
| `make logs` | View application logs (all services) |
| `make logs-web` | View web server logs only |
| `make logs-db` | View database logs only |
| `make shell` | Access web container shell |
| `make shell-db` | Access database container shell |
| `make mysql` | Access MySQL command line |

### Maintenance Commands
| Command | Description |
|---------|-------------|
| `make reset` | Reset environment (clear data, keep images) |
| `make destroy` | Completely destroy the environment |
| `make clean` | Clean up unused Docker resources |
| `make health` | Run comprehensive health checks |

### Advanced Commands
| Command | Description |
|---------|-------------|
| `make build` | Build containers without cache |
| `make rebuild` | Rebuild and restart containers |
| `make backup` | Create database backup |
| `make restore BACKUP=file` | Restore database from backup |
| `make update` | Update container images |

---

## Configuration

### Environment Variables (.env)
```bash
# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_HOST=mysql
DB_NAME=performance_evaluation
DB_USER=app_user
DB_PASSWORD=secure_dev_password
DB_ROOT_PASSWORD=root_dev_password

# Ports
WEB_PORT=8080
DB_PORT=3306

# PHP Settings
PHP_MEMORY_LIMIT=256M
PHP_MAX_EXECUTION_TIME=300

# Container User Mapping
WWWUSER=1000
WWWGROUP=1000
```

### Volume Mounts
- **Application Code**: `./:/var/www/html` (hot reload)
- **Database Data**: `mysql_data:/var/lib/mysql` (persistence)
- **Logs**: `./docker/logs:/var/log` (debugging)

### Network Configuration
- **Web Application**: http://localhost:8080
- **Database**: localhost:3306
- **Health Check**: http://localhost:8080/health-check.php

---

## Troubleshooting

### Common Issues

#### 🔴 Port Already in Use
```bash
# Error: Port 8080 is already in use
# Solution: Change port in .env
WEB_PORT=8081
make restart
```

#### 🔴 Container Won't Start
```bash
# Check logs
make logs

# Rebuild containers
make rebuild

# Check Docker daemon
docker info
```

#### 🔴 Database Connection Failed
```bash
# Check database health
make health

# Reset database
make reset

# Access database directly
make mysql
```

#### 🔴 Permission Issues
Bind mounts keep the host file ownership. If your application writes to `config/soft_skills`
or other shared directories but the changes show up under `/tmp`, rebuild the container with
`WWWUSER`/`WWWGROUP` values that match your host user so the PHP process retains write access.
```bash
# Ensure the container user matches your host user (prevents /tmp fallbacks)
WWWUSER=$(id -u) WWWGROUP=$(id -g) docker compose build web
docker compose up -d --force-recreate web
```

#### 🔴 Out of Disk Space
```bash
# Clean up Docker resources
make clean

# Check disk usage
df -h
```

### Health Monitoring
```bash
# Quick health check
make health

# Continuous monitoring
./docker/scripts/health-check.sh --watch

# Detailed container stats
make status
```

### Log Analysis
```bash
# All logs
make logs

# Web server errors
make logs-web | grep ERROR

# Database slow queries
make logs-db | grep "Query_time"

# PHP errors
tail -f docker/logs/php/error.log
```

---

## Advanced Usage

### Custom PHP Configuration
Edit `docker/web/php.ini` and rebuild:
```bash
make rebuild
```

### Custom Apache Configuration
Edit `docker/web/apache.conf` and rebuild:
```bash
make rebuild
```

### Database Optimization
Edit `docker-compose.yml` MySQL command section:
```yaml
command: >
  --innodb-buffer-pool-size=512M
  --max-connections=200
```

### Multiple Environments
```bash
# Development
docker-compose up -d

# Production-like
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Debugging with Xdebug
Xdebug is pre-configured for development:
1. Set breakpoints in your IDE
2. Configure IDE to listen on port 9003
3. Debug requests will automatically connect

### Performance Monitoring
```bash
# Container resource usage
docker stats

# Application performance
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8080

# Database performance
make mysql
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Slow_queries';
```

---

## File Structure

```
project_root/
├── docker-compose.yml              # Main orchestration
├── docker-compose.override.yml     # Development overrides
├── .env.example                    # Environment template
├── .env                           # Environment variables (created)
├── Makefile                       # Quick commands
├── docker/
│   ├── web/
│   │   ├── Dockerfile             # Web container definition
│   │   ├── apache.conf            # Apache configuration
│   │   ├── php.ini               # PHP settings
│   │   └── health-check.php      # Health endpoint
│   ├── scripts/
│   │   ├── deploy.sh             # Deployment automation
│   │   ├── reset.sh              # Reset environment
│   │   ├── destroy.sh            # Complete cleanup
│   │   └── health-check.sh       # Health monitoring
│   └── logs/                     # Centralized logging
│       ├── apache/
│       ├── php/
│       └── mysql/
└── [application files...]
```

---

## Security Considerations

### Development Security
- Containers run in isolated network
- Database credentials in environment files
- No production secrets in development

### Best Practices
```bash
# Regular updates
make update

# Clean unused resources
make clean

# Monitor for issues
make health

# Backup important data
make backup
```

---

## Support

### Getting Help
1. Check this README
2. Run `make health` for diagnostics
3. Check logs with `make logs`
4. Review Docker documentation

### Useful Resources
- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [PHP Docker Images](https://hub.docker.com/_/php)
- [MySQL Docker Images](https://hub.docker.com/_/mysql)

---

## Contributing

### Making Changes
1. Test changes locally with `make up`
2. Run health checks with `make health`
3. Document any new features
4. Update this README if needed

### Adding New Services
1. Add service to `docker-compose.yml`
2. Update health checks in `health-check.sh`
3. Add relevant Makefile commands
4. Update documentation

---

**🎉 Happy Coding!**

Your Docker development environment is ready for rapid iteration and development of the PHP Universal-Development-HR-System.
