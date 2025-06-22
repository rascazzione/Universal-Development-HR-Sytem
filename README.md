# PHP Performance Evaluation System

A comprehensive web-based employee performance evaluation system built with PHP and MySQL. This system digitizes the traditional paper-based evaluation process with user authentication, role-based access control, and automated reporting.

## Features

### Core Functionality
- **User Authentication & Authorization** - Secure login with role-based access (HR Admin, Manager, Employee)
- **Employee Management** - Complete employee database with organizational hierarchy
- **Performance Evaluations** - Digital evaluation forms matching traditional templates
- **Flexible Evaluation Periods** - Support for monthly, quarterly, annual, and custom evaluation cycles
- **Automated Reporting** - PDF generation and performance analytics
- **Dashboard Analytics** - Role-specific dashboards with key metrics

### Evaluation Template
Based on the provided performance evaluation templates, the system includes:
- **Expected Results** (40% weight) - Achievement of objectives, quality, productivity, initiative
- **Skills, Knowledge & Competencies** (25% weight) - Technical skills, communication, problem-solving, teamwork
- **Key Responsibilities** (25% weight) - Job knowledge, reliability, adaptability
- **Living Our Values** (10% weight) - Integrity, respect, excellence, innovation
- **Overall Rating** - 1-5 scale with weighted calculation

### User Roles & Permissions
- **HR Administrator** - Full system access, user management, evaluation oversight
- **Manager** - Create/edit evaluations for direct reports, view team performance
- **Employee** - View own evaluations and performance history

## System Requirements

- **PHP** 7.4 or higher
- **MySQL** 8.0 or higher
- **Web Server** (Apache/Nginx)
- **Extensions**: PDO, JSON, OpenSSL

## Installation

### 1. Clone/Download the Project
```bash
git clone <repository-url>
cd performance_evaluation_system
```

### 2. Database Setup
1. Create a MySQL database named `performance_evaluation`
2. Import the database schema:
```bash
mysql -u root -p performance_evaluation < sql/database_setup.sql
```

### 3. Configuration
1. Update database credentials in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'performance_evaluation');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

2. Configure application settings in `config/config.php` as needed.

### 4. Web Server Setup
Configure your web server to serve files from the `public/` directory.

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. File Permissions
Ensure proper permissions for file uploads and logs:
```bash
chmod 755 public/
chmod 644 public/*.php
```

## Default Login Credentials

After installation, you can log in with the default administrator account:
- **Username**: admin
- **Password**: admin123

**⚠️ Important**: Change the default password immediately after first login!

## Usage

### For HR Administrators
1. **User Management** - Create user accounts for managers and employees
2. **Employee Management** - Add employee records and set up organizational hierarchy
3. **Evaluation Periods** - Create and manage evaluation cycles
4. **System Oversight** - Monitor all evaluations and generate reports

### For Managers
1. **Team Management** - View and manage direct reports
2. **Create Evaluations** - Conduct performance evaluations for team members
3. **Track Progress** - Monitor evaluation completion and team performance

### For Employees
1. **View Evaluations** - Access personal evaluation history
2. **Performance Tracking** - Monitor ratings and feedback over time
3. **Profile Management** - Update personal information

## File Structure

```
performance_evaluation_system/
├── config/                 # Configuration files
│   ├── database.php       # Database configuration
│   └── config.php         # Application settings
├── classes/               # PHP classes
│   ├── User.php          # User management
│   ├── Employee.php      # Employee management
│   ├── Evaluation.php    # Evaluation handling
│   └── EvaluationPeriod.php # Period management
├── includes/              # Include files
│   └── auth.php          # Authentication functions
├── templates/             # HTML templates
│   ├── header.php        # Common header
│   └── footer.php        # Common footer
├── public/               # Public web files
│   ├── index.php         # Main entry point
│   ├── login.php         # Login page
│   ├── dashboard.php     # Dashboard
│   └── evaluation/       # Evaluation pages
├── assets/               # Static assets
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript files
└── sql/                 # Database files
    └── database_setup.sql # Database schema
```

## Security Features

- **Password Hashing** - Secure password storage using PHP's password_hash()
- **CSRF Protection** - Cross-site request forgery prevention
- **SQL Injection Prevention** - Prepared statements for all database queries
- **XSS Protection** - Input sanitization and output encoding
- **Session Security** - Secure session handling with timeout
- **Role-based Access Control** - Granular permissions system

## Development

### Adding New Features
1. Create new classes in the `classes/` directory
2. Add new pages in the appropriate `public/` subdirectory
3. Update navigation in `includes/auth.php`
4. Add database migrations to `sql/` directory

### Customization
- **Evaluation Template** - Modify `classes/Evaluation.php` to change evaluation criteria
- **Styling** - Update `assets/css/style.css` for visual customization
- **Permissions** - Adjust role permissions in `config/config.php`

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and user has proper permissions

2. **Permission Denied Errors**
   - Check file permissions on web server
   - Ensure web server user can read PHP files

3. **Session Issues**
   - Verify session directory is writable
   - Check PHP session configuration

4. **Login Problems**
   - Verify default admin user exists in database
   - Check password hashing compatibility

### Debug Mode
Enable debug mode in `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Support

For technical support or feature requests:
1. Check the troubleshooting section above
2. Review the system logs for error messages
3. Consult the PHP and MySQL documentation

## License

This project is developed for internal use. Please ensure compliance with your organization's software policies.

## Changelog

### Version 1.0.0
- Initial release
- Complete evaluation system implementation
- User authentication and role management
- Dashboard and reporting features
- Mobile-responsive design

---

**Note**: This system is designed to replace paper-based evaluation processes while maintaining the same evaluation criteria and workflow. All evaluation data is stored securely and can be exported for compliance purposes.

## 🚀 Developer Resources

- **[Developer Guide](README_DEV.md)** - Complete development environment management
- **[Quick Reference](QUICK_REFERENCE.md)** - Essential commands cheat sheet
- **[Database Migrations](docs/DATABASE_MIGRATION_IMPLEMENTATION.md)** - Migration system documentation
- **[Architecture Design](docs/ARCHITECTURE_DESIGN.md)** - System architecture overview
- **[Docker Setup](DOCKER_SETUP_COMPLETE.md)** - Docker environment details

### Quick Start for Developers
```bash
# Start development environment
make up

# Access application
open http://localhost:8080

# Default login: admin / admin123
```

For detailed development instructions, see **[README_DEV.md](README_DEV.md)**.