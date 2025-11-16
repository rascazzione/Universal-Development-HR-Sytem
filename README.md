# Universal Development HR System

A comprehensive web-based Human Resources management system built with PHP and MySQL, designed to streamline employee evaluation, performance management, talent development, and organizational analytics. This system digitizes traditional HR processes with modern web technology, providing role-based access control and automated reporting capabilities..

## 🚀 Quick Start

Get the system running in under 5 minutes:

```bash
# 1. Initial setup (first time only)
make install

# 2. Start the environment
make up

# 3. Access the application
open http://localhost:8080
```

**Default Login Credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ **Important**: Change the default password immediately after first login!

## 📋 Table of Contents

1. [Features](#features)
2. [System Requirements](#system-requirements)
3. [Installation](#installation)
4. [User Roles & Permissions](#user-roles--permissions)
5. [Core Modules](#core-modules)
6. [Development](#development)
7. [Troubleshooting](#troubleshooting)
8. [Security Features](#security-features)
9. [Support](#support)

## ✨ Features

### 🎯 Performance Management
- **360-Degree Evaluations** - Comprehensive feedback from multiple sources
- **Self-Assessments** - Employee-driven performance reviews
- **Manager Evaluations** - Structured performance assessments
- **Evidence-Based Reviews** - Documented performance evidence and achievements
- **Flexible Evaluation Periods** - Monthly, quarterly, annual, and custom cycles
- **Weighted Scoring System** - Configurable evaluation criteria with weights

### 👥 Employee Management
- **Complete Employee Database** - Comprehensive employee profiles and records
- **Organizational Hierarchy** - Manager-subordinate relationships
- **Department Management** - Organizational structure administration
- **Employee Import/Export** - Bulk data management with CSV support
- **Job Templates** - Standardized role definitions and requirements

### 📊 Analytics & Reporting
- **Role-Specific Dashboards** - HR Admin, Manager, and Employee views
- **Performance Analytics** - Trends, insights, and comparative analysis
- **Automated Reporting** - PDF generation and performance summaries
- **KPI Tracking** - Key Performance Indicators monitoring
- **Evidence-Based Insights** - Data-driven decision support

### 🎓 Talent Development
- **Individual Development Plans (IDPs)** - Personalized growth strategies
- **Goal Setting & OKRs** - Objectives and Key Results management
- **Skills Assessment** - Technical and soft skills evaluation
- **Competency Management** - Skill gap analysis and development tracking
- **Achievement Journals** - Documented accomplishments and milestones

### 🏆 Recognition & Engagement
- **Kudos System** - Peer recognition and appreciation platform
- **Achievement Tracking** - Milestone and accomplishment recording
- **Feedback Mechanisms** - Continuous feedback collection and management
- **Upward Feedback** - Anonymous feedback for management improvement

### 🔧 System Administration
- **User Authentication & Authorization** - Secure login with role-based access
- **Audit Logging** - Comprehensive activity tracking and compliance
- **System Settings** - Configurable application parameters
- **Data Import/Export** - Flexible data management capabilities
- **Health Monitoring** - System performance and status tracking

## 🖥️ System Requirements

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

## 🛠️ Installation

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

## 👤 User Roles & Permissions

### HR Administrator
- **Full System Access** - Complete administrative control
- **User Management** - Create and manage user accounts
- **Employee Management** - Add/edit employee records and hierarchy
- **Evaluation Oversight** - Monitor all evaluations and generate reports
- **System Configuration** - Manage system settings and parameters
- **Department Management** - Organizational structure administration

### Manager
- **Team Management** - View and manage direct reports
- **Create Evaluations** - Conduct performance evaluations for team members
- **Performance Tracking** - Monitor team performance and progress
- **Feedback Management** - Provide and manage employee feedback
- **Goal Setting** - Set and track team objectives and OKRs
- **Development Planning** - Create and manage IDPs for team members

### Employee
- **Self-Assessment** - Complete self-evaluations and assessments
- **View Evaluations** - Access personal evaluation history and feedback
- **Performance Tracking** - Monitor ratings and progress over time
- **Goal Management** - Set and track personal development goals
- **Achievement Recording** - Document accomplishments and evidence
- **Feedback Participation** - Provide feedback to peers and managers

## 📁 Core Modules

### 📈 Performance Evaluation Module
- **Evaluation Workflow** - Structured evaluation process with states
- **Evidence Aggregation** - Automated evidence collection and analysis
- **Multi-Source Feedback** - 360-degree evaluation capabilities
- **Scoring Engine** - Weighted calculation and rating system
- **Evaluation Templates** - Standardized evaluation forms and criteria

### 📊 Analytics Dashboard
- **HR Analytics** - Organization-wide insights and metrics
- **Manager Dashboard** - Team performance and management tools
- **Employee Dashboard** - Personal performance and development view
- **Real-time Metrics** - Live data updates and notifications
- **Custom Reports** - Flexible reporting and data export

### 🎓 Development & Learning
- **IDP Management** - Individual Development Plan creation and tracking
- **Skills Assessment** - Technical and soft skills evaluation
- **Competency Framework** - Comprehensive skill management system
- **Learning Resources** - Development recommendations and resources
- **Progress Tracking** - Development goal monitoring and analytics

### 🏆 Recognition & Engagement
- **Kudos Platform** - Peer recognition and appreciation system
- **Achievement Tracking** - Milestone and accomplishment recording
- **Feedback Systems** - Continuous feedback collection
- **Engagement Metrics** - Employee engagement tracking and analysis

### 📋 Data Management
- **Import/Export** - CSV-based bulk data operations
- **Employee Records** - Comprehensive employee information management
- **Document Management** - File uploads and attachment handling
- **Audit Trail** - Complete activity logging and compliance

## 🔧 Development

### Available Commands

#### Core Commands
| Command | Description |
|---------|-------------|
| `make up` | Start the development environment |
| `make down` | Stop the development environment |
| `make restart` | Restart all services |
| `make status` | Show container status and resource usage |

#### Development Commands
| Command | Description |
|---------|-------------|
| `make logs` | View application logs (all services) |
| `make logs-web` | View web server logs only |
| `make logs-db` | View database logs only |
| `make shell` | Access web container shell |
| `make mysql` | Access MySQL command line |

#### Maintenance Commands
| Command | Description |
|---------|-------------|
| `make reset` | Reset environment (clear data, keep images) |
| `make destroy` | Completely destroy the environment |
| `make clean` | Clean up unused Docker resources |
| `make health` | Run comprehensive health checks |

#### Advanced Commands
| Command | Description |
|---------|-------------|
| `make backup` | Create database backup |
| `make restore BACKUP=file` | Restore database from backup |
| `make rebuild` | Rebuild and restart containers |
| `make update` | Update container images |

### Development Workflow

#### 🔄 Hot Reload
Code changes are immediately reflected:
1. Edit any PHP file
2. Refresh browser
3. Changes are live instantly

#### 💾 Data Persistence
- Database data survives container restarts
- Only removed with `make reset` or `make destroy`

#### 🔧 Configuration Changes
1. Edit `.env` file
2. Run `make restart`
3. Changes take effect

#### 🗄️ Database Access
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

## 🔒 Security Features

- **Password Hashing** - Secure password storage using PHP's password_hash()
- **CSRF Protection** - Cross-site request forgery prevention
- **SQL Injection Prevention** - Prepared statements for all database queries
- **XSS Protection** - Input sanitization and output encoding
- **Session Security** - Secure session handling with timeout
- **Role-based Access Control** - Granular permissions system
- **Audit Logging** - Comprehensive activity tracking
- **Input Validation** - Data validation and sanitization
- **File Upload Security** - Secure file handling and validation

## 🔧 Configuration

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
```

### Volume Mounts
- **Application Code**: `./:/var/www/html` (hot reload)
- **Database Data**: `mysql_data:/var/lib/mysql` (persistence)
- **Logs**: `./docker/logs:/var/log` (debugging)

### Network Configuration
- **Web Application**: http://localhost:8080
- **Database**: localhost:3306
- **Health Check**: http://localhost:8080/health-check.php

## 🐛 Troubleshooting

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
```bash
# Fix file permissions
make shell
chown -R www-data:www-data /var/www/html
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

## 📚 File Structure

```
universal_development_hr_system/
├── docker-compose.yml              # Main orchestration
├── docker-compose.override.yml     # Development overrides
├── .env.example                    # Environment template
├── .env                           # Environment variables (created)
├── Makefile                       # Quick commands
├── config/                        # Configuration files
│   ├── database.php               # Database configuration
│   └── config.php                 # Application settings
├── classes/                       # PHP classes
│   ├── User.php                   # User management
│   ├── Employee.php               # Employee management
│   ├── Evaluation.php             # Evaluation handling
│   └── [20+ other classes]        # Core business logic
├── public/                        # Public web files
│   ├── index.php                  # Main entry point
│   ├── login.php                  # Login page
│   ├── dashboard.php               # Dashboard
│   ├── admin/                     # Admin interfaces
│   ├── api/                       # API endpoints
│   ├── employees/                 # Employee management
│   ├── evaluation/                # Evaluation interfaces
│   └── [other modules]            # Feature modules
├── sql/                           # Database files
│   ├── 001_database_setup.sql     # Main schema
│   └── [migration files]         # Database migrations
├── docker/                        # Docker configuration
│   ├── web/
│   │   ├── Dockerfile             # Web container definition
│   │   ├── apache.conf            # Apache configuration
│   │   └── php.ini               # PHP settings
│   ├── scripts/                   # Management scripts
│   └── logs/                      # Centralized logging
└── docs/                          # Documentation
```

## 🤝 Support

### Getting Help
1. Check this README
2. Run `make health` for diagnostics
3. Check logs with `make logs`
4. Review Docker documentation

### Useful Resources
- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)

### Contributing
1. Test changes locally with `make up`
2. Run health checks with `make health`
3. Document any new features
4. Update this README if needed

---

## 📄 License

This project is developed for internal use. Please ensure compliance with your organization's software policies.

## 📈 Version History

### Version 1.0.0
- Initial release
- Complete HR management system
- Performance evaluation framework
- User authentication and role management
- Dashboard and analytics features
- Mobile-responsive design
- Docker-based deployment

---

**🎉 Thank you for choosing Universal Development HR System!**

Your comprehensive HR management platform is ready for deployment. This system provides a modern, scalable solution for employee evaluation, performance management, and organizational development.