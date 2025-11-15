# PHP Performance Evaluation System - Developer Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Development Environment Setup](#development-environment-setup)
3. [Code Architecture](#code-architecture)
4. [Database Schema](#database-schema)
5. [API Reference](#api-reference)
6. [Frontend Development](#frontend-development)
7. [Backend Development](#backend-development)
8. [Testing](#testing)
9. [Customization and Extension](#customization-and-extension)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

## Introduction

This Developer Guide provides technical information for developers working with the PHP Performance Evaluation System. It covers the system architecture, development environment setup, coding standards, database schema, API reference, and customization options.

### Target Audience
This guide is intended for:
- PHP developers working on the system
- Frontend developers customizing the user interface
- Database administrators managing the system database
- Integration developers connecting external systems
- Quality assurance engineers testing the system

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- Docker (for containerized development)
- Basic understanding of MVC architecture
- Knowledge of PHP, JavaScript, and SQL

## Development Environment Setup

### System Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache 2.4 or Nginx 1.18
- **Docker**: 20.10 or higher (optional)
- **Composer**: 2.0 or higher
- **Node.js**: 14.0 or higher (for frontend development)

### Local Development Setup

#### Traditional Setup (without Docker)
1. Clone the repository:
   ```bash
   git clone https://github.com/your-org/php-performance-evaluation.git
   cd php-performance-evaluation
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Configure the database:
   - Create a new MySQL database
   - Import the database schema from `sql/001_database_setup.sql`
   - Update database configuration in `config/database.php`

4. Configure the application:
   - Copy `.env.example` to `.env`
   - Update environment variables in `.env`

5. Set up the web server:
   - Configure Apache or Nginx to point to the `public` directory
   - Ensure URL rewriting is enabled
   - Set appropriate file permissions

6. Access the application:
   - Open your browser and navigate to the configured URL
   - Log in with default credentials (admin/admin123)

#### Docker Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/your-org/php-performance-evaluation.git
   cd php-performance-evaluation
   ```

2. Build and start the containers:
   ```bash
   docker-compose up -d
   ```

3. Initialize the database:
   ```bash
   docker-compose exec web php scripts/initialize_database.php
   ```

4. Access the application:
   - Open your browser and navigate to http://localhost:8080
   - Log in with default credentials (admin/admin123)

### IDE Configuration

#### VS Code Setup
1. Install recommended extensions:
   - PHP Intelephense
   - PHP Debug
   - MySQL
   - Docker
   - ESLint
   - Prettier

2. Configure workspace settings:
   ```json
   {
     "php.validate.executablePath": "/usr/bin/php",
     "php.suggest.basic": false,
     "editor.formatOnSave": true,
     "editor.codeActionsOnSave": {
       "source.fixAll.eslint": true
     }
   }
   ```

#### PhpStorm Setup
1. Configure PHP interpreter:
   - Go to Settings → PHP
   - Select CLI interpreter (PHP 7.4+)

2. Configure database connection:
   - Go to Settings → Database
   - Add MySQL database connection
   - Test connection

3. Configure deployment:
   - Go to Settings → Deployment
   - Add SFTP/FTP server configuration
   - Set up path mappings

### Development Tools

#### Code Quality Tools
1. PHP CodeSniffer:
   ```bash
   composer require --dev squizlabs/php_codesniffer
   vendor/bin/phpcs --standard=PSR12 src/
   ```

2. PHP Mess Detector:
   ```bash
   composer require --dev phpmd/phpmd
   vendor/bin/phpmd src/ text phpmd.xml
   ```

3. PHPStan:
   ```bash
   composer require --dev phpstan/phpstan
   vendor/bin/phpstan analyse src/
   ```

#### Testing Tools
1. PHPUnit:
   ```bash
   composer require --dev phpunit/phpunit
   vendor/bin/phpunit tests/
   ```

2. Behat (for BDD):
   ```bash
   composer require --dev behat/behat
   vendor/bin/behat --init
   ```

#### Frontend Tools
1. Install Node.js dependencies:
   ```bash
   npm install
   ```

2. Run development server:
   ```bash
   npm run dev
   ```

3. Build for production:
   ```bash
   npm run build
   ```

## Code Architecture

### MVC Architecture
The system follows a Model-View-Controller (MVC) architecture pattern:

#### Models
- Located in `classes/` directory
- Handle data logic and database interactions
- Examples: [`Employee.php`](classes/Employee.php), [`Evaluation.php`](classes/Evaluation.php)

#### Views
- Located in `public/` and `templates/` directories
- Handle presentation logic
- Examples: [`dashboard.php`](public/dashboard.php), [`header.php`](templates/header.php)

#### Controllers
- Integrated into view files (PHP-based controllers)
- Handle application logic and user input
- Examples: [`dashboard.php`](public/dashboard.php), [`api/employees.php`](public/api/employees.php)

### Directory Structure

```
/
├── classes/                 # Model classes
├── config/                  # Configuration files
├── docker/                  # Docker configuration
├── docs/                    # Documentation
├── includes/                # Shared components
├── public/                  # Public web root
│   ├── api/                 # API endpoints
│   ├── assets/              # CSS, JS, images
│   └── ...                  # View files
├── scripts/                 # Utility scripts
├── sql/                     # Database schema files
├── templates/               # Reusable templates
├── tests/                   # Test files
└── uploads/                 # File uploads
```

### Key Classes

#### User Management
- [`User.php`](classes/User.php): User authentication and authorization
- [`Employee.php`](classes/Employee.php): Employee data management

#### Evaluation System
- [`Evaluation.php`](classes/Evaluation.php): Core evaluation functionality
- [`EvaluationPeriod.php`](classes/EvaluationPeriod.php): Evaluation period management
- [`EvaluationWorkflow.php`](classes/EvaluationWorkflow.php): Evaluation workflow control

#### Evidence Management
- [`EvidenceManager.php`](classes/EvidenceManager.php): Evidence collection and management
- [`GrowthEvidenceJournal.php`](classes/GrowthEvidenceJournal.php): Evidence journaling

#### Reporting and Analytics
- [`DashboardAnalytics.php`](classes/DashboardAnalytics.php): Dashboard data generation
- [`ReportGenerator.php`](classes/ReportGenerator.php): Report generation

### Configuration Files

#### Application Configuration
- [`config/config.php`](config/config.php): Main application configuration
## Database Schema

### Schema Overview
The database schema is designed to support the performance evaluation system with a focus on evidence-based evaluations, user management, and reporting capabilities.

### Core Tables

#### users
Stores user account information and authentication data.

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'manager', 'employee') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### employees
Stores employee information and organizational hierarchy.

```sql
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    employee_number VARCHAR(20) UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    department VARCHAR(100),
    manager_id INT,
    hire_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (manager_id) REFERENCES employees(id)
);
```

#### evaluations
Stores evaluation records and scores.

```sql
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    evaluation_period_id INT NOT NULL,
    overall_score DECIMAL(5,2),
    kpi_score DECIMAL(5,2),
    competency_score DECIMAL(5,2),
    responsibility_score DECIMAL(5,2),
    value_score DECIMAL(5,2),
    status ENUM('draft', 'submitted', 'reviewed', 'approved') DEFAULT 'draft',
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (evaluator_id) REFERENCES employees(id),
    FOREIGN KEY (evaluation_period_id) REFERENCES evaluation_periods(id)
);
```

#### evidence
Stores performance evidence entries.

```sql
CREATE TABLE evidence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    dimension ENUM('kpi', 'competency', 'responsibility', 'value') NOT NULL,
    dimension_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    evidence_date DATE NOT NULL,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (evaluator_id) REFERENCES employees(id)
);
```

#### evaluation_periods
Stores evaluation timeframe information.

```sql
CREATE TABLE evaluation_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('monthly', 'quarterly', 'semi_annual', 'annual', 'custom') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Supporting Tables

#### job_position_templates
Stores job templates and evaluation criteria.

```sql
CREATE TABLE job_position_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    description TEXT,
    kpi_weight DECIMAL(5,2) DEFAULT 30.0,
    competency_weight DECIMAL(5,2) DEFAULT 25.0,
    responsibility_weight DECIMAL(5,2) DEFAULT 25.0,
    value_weight DECIMAL(5,2) DEFAULT 20.0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### company_kpis
Stores KPI definitions.

```sql
CREATE TABLE company_kpis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    measurement_method VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### competencies
Stores competency definitions.

```sql
CREATE TABLE competencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### company_values
Stores company value definitions.

```sql
CREATE TABLE company_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### notifications
Stores system notifications.

```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### audit_log
Stores system audit trail.

```sql
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Database Relationships

#### User-Employee Relationship
- Each user can have one employee record
- Each employee must have one user record
- One-to-one relationship

#### Manager-Employee Relationship
- Each employee can have one manager
- Each manager can have multiple employees
- One-to-many relationship (self-referencing)

#### Evaluation-Employee Relationship
## API Reference

### Authentication API

#### Login
Authenticate user and receive session token.

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
    "username": "string",
    "password": "string"
}
```

**Response:**
```json
{
    "success": true,
    "token": "string",
    "user": {
        "id": 1,
        "username": "string",
        "email": "string",
        "first_name": "string",
        "last_name": "string",
        "role": "admin|manager|employee"
    }
}
```

#### Logout
Invalidate user session.

**Endpoint:** `POST /api/logout`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### User API

#### Get Current User
Get current user information.

**Endpoint:** `GET /api/user/current`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "user": {
        "id": 1,
        "username": "string",
        "email": "string",
        "first_name": "string",
        "last_name": "string",
        "role": "admin|manager|employee",
        "employee": {
            "id": 1,
            "employee_number": "string",
            "position": "string",
            "department": "string",
            "manager_id": 2
        }
    }
}
```

#### Update User
Update user information.

**Endpoint:** `PUT /api/user/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "first_name": "string",
    "last_name": "string",
    "email": "string"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User updated successfully",
    "user": {
        "id": 1,
        "username": "string",
        "email": "string",
        "first_name": "string",
        "last_name": "string",
        "role": "admin|manager|employee"
    }
}
```

#### Change Password
Change user password.

**Endpoint:** `POST /api/user/change-password`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "current_password": "string",
    "new_password": "string"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

### Employee API

#### Get Employees
Get list of employees with filtering and pagination.

**Endpoint:** `GET /api/employees`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `limit` (integer): Items per page (default: 20)
- `search` (string): Search term
- `department` (string): Filter by department
- `position` (string): Filter by position
- `manager_id` (integer): Filter by manager

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "employee_number": "string",
            "first_name": "string",
            "last_name": "string",
            "email": "string",
            "position": "string",
            "department": "string",
            "manager": {
                "id": 2,
                "first_name": "string",
                "last_name": "string"
            }
        }
    ],
    "pagination": {
        "total": 100,
        "page": 1,
        "limit": 20,
        "pages": 5
    }
}
```

#### Get Employee
Get employee details by ID.

**Endpoint:** `GET /api/employees/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "employee_number": "string",
        "first_name": "string",
        "last_name": "string",
        "email": "string",
        "position": "string",
        "department": "string",
        "manager": {
            "id": 2,
            "first_name": "string",
            "last_name": "string"
        },
        "team": [
            {
                "id": 3,
                "first_name": "string",
                "last_name": "string",
                "position": "string"
            }
        ]
    }
}
```

#### Create Employee
Create new employee.

**Endpoint:** `POST /api/employees`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "first_name": "string",
    "last_name": "string",
    "email": "string",
    "position": "string",
    "department": "string",
    "manager_id": 2,
    "hire_date": "YYYY-MM-DD"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Employee created successfully",
    "data": {
        "id": 1,
        "employee_number": "string",
        "first_name": "string",
        "last_name": "string",
        "email": "string",
        "position": "string",
        "department": "string",
        "manager_id": 2,
        "hire_date": "YYYY-MM-DD"
    }
}
```

#### Update Employee
Update employee information.

**Endpoint:** `PUT /api/employees/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "first_name": "string",
    "last_name": "string",
    "email": "string",
    "position": "string",
    "department": "string",
    "manager_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "message": "Employee updated successfully",
    "data": {
        "id": 1,
        "employee_number": "string",
        "first_name": "string",
        "last_name": "string",
        "email": "string",
        "position": "string",
        "department": "string",
        "manager_id": 2
    }
}
```

#### Delete Employee
Delete employee (soft delete).

**Endpoint:** `DELETE /api/employees/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "message": "Employee deleted successfully"
}
```

### Evaluation API

#### Get Evaluations
Get list of evaluations with filtering and pagination.

**Endpoint:** `GET /api/evaluations`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `limit` (integer): Items per page (default: 20)
- `employee_id` (integer): Filter by employee
- `evaluator_id` (integer): Filter by evaluator
- `period_id` (integer): Filter by evaluation period
- `status` (string): Filter by status

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "employee": {
                "id": 1,
                "first_name": "string",
                "last_name": "string"
            },
            "evaluator": {
                "id": 2,
                "first_name": "string",
                "last_name": "string"
            },
            "period": {
                "id": 1,
                "name": "string"
            },
            "overall_score": 4.2,
            "status": "draft",
            "created_at": "YYYY-MM-DD HH:MM:SS"
        }
    ],
    "pagination": {
        "total": 50,
        "page": 1,
        "limit": 20,
        "pages": 3
    }
}
```

#### Get Evaluation
Get evaluation details by ID.

**Endpoint:** `GET /api/evaluations/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "employee": {
            "id": 1,
            "first_name": "string",
            "last_name": "string"
        },
        "evaluator": {
            "id": 2,
            "first_name": "string",
            "last_name": "string"
        },
        "period": {
            "id": 1,
            "name": "string",
            "start_date": "YYYY-MM-DD",
            "end_date": "YYYY-MM-DD"
        },
        "overall_score": 4.2,
        "kpi_score": 4.0,
        "competency_score": 4.3,
        "responsibility_score": 4.1,
        "value_score": 4.5,
        "status": "draft",
        "comments": "string",
        "evidence": [
            {
                "id": 1,
                "dimension": "kpi",
                "rating": 4,
                "comments": "string",
                "evidence_date": "YYYY-MM-DD"
            }
        ],
        "created_at": "YYYY-MM-DD HH:MM:SS",
        "updated_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

#### Create Evaluation
Create new evaluation.

**Endpoint:** `POST /api/evaluations`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "employee_id": 1,
    "evaluation_period_id": 1,
    "comments": "string"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Evaluation created successfully",
    "data": {
        "id": 1,
        "employee_id": 1,
        "evaluator_id": 2,
        "evaluation_period_id": 1,
        "overall_score": null,
        "status": "draft",
        "comments": "string",
        "created_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

#### Update Evaluation
Update evaluation information.

**Endpoint:** `PUT /api/evaluations/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "overall_score": 4.2,
    "kpi_score": 4.0,
    "competency_score": 4.3,
    "responsibility_score": 4.1,
    "value_score": 4.5,
    "status": "submitted",
    "comments": "string"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Evaluation updated successfully",
    "data": {
        "id": 1,
        "employee_id": 1,
        "evaluator_id": 2,
        "evaluation_period_id": 1,
        "overall_score": 4.2,
        "kpi_score": 4.0,
        "competency_score": 4.3,
        "responsibility_score": 4.1,
        "value_score": 4.5,
        "status": "submitted",
        "comments": "string",
        "updated_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

#### Submit Evaluation
Submit evaluation for review.

**Endpoint:** `POST /api/evaluations/{id}/submit`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "message": "Evaluation submitted successfully",
    "data": {
        "id": 1,
        "status": "submitted",
        "updated_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

### Evidence API

#### Get Evidence
Get list of evidence entries with filtering and pagination.

**Endpoint:** `GET /api/evidence`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `limit` (integer): Items per page (default: 20)
- `employee_id` (integer): Filter by employee
- `evaluator_id` (integer): Filter by evaluator
- `dimension` (string): Filter by dimension
- `date_from` (string): Filter by date from (YYYY-MM-DD)
- `date_to` (string): Filter by date to (YYYY-MM-DD)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "employee": {
                "id": 1,
                "first_name": "string",
                "last_name": "string"
            },
            "evaluator": {
                "id": 2,
                "first_name": "string",
                "last_name": "string"
            },
            "dimension": "kpi",
            "rating": 4,
            "comments": "string",
            "evidence_date": "YYYY-MM-DD",
            "created_at": "YYYY-MM-DD HH:MM:SS"
        }
    ],
    "pagination": {
        "total": 200,
        "page": 1,
        "limit": 20,
        "pages": 10
    }
}
```

#### Get Evidence Entry
Get evidence entry details by ID.

**Endpoint:** `GET /api/evidence/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "employee": {
            "id": 1,
            "first_name": "string",
            "last_name": "string"
        },
        "evaluator": {
            "id": 2,
            "first_name": "string",
            "last_name": "string"
        },
        "dimension": "kpi",
        "dimension_id": 1,
        "rating": 4,
        "comments": "string",
        "evidence_date": "YYYY-MM-DD",
        "is_approved": true,
        "created_at": "YYYY-MM-DD HH:MM:SS",
        "updated_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

#### Create Evidence
Create new evidence entry.

**Endpoint:** `POST /api/evidence`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "employee_id": 1,
    "dimension": "kpi",
    "dimension_id": 1,
    "rating": 4,
    "comments": "string",
    "evidence_date": "YYYY-MM-DD"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Evidence created successfully",
    "data": {
        "id": 1,
        "employee_id": 1,
        "evaluator_id": 2,
        "dimension": "kpi",
        "dimension_id": 1,
        "rating": 4,
        "comments": "string",
        "evidence_date": "YYYY-MM-DD",
        "is_approved": true,
        "created_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

#### Update Evidence
Update evidence entry.

**Endpoint:** `PUT /api/evidence/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "rating": 4,
    "comments": "string",
    "evidence_date": "YYYY-MM-DD"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Evidence updated successfully",
    "data": {
        "id": 1,
        "employee_id": 1,
        "evaluator_id": 2,
        "dimension": "kpi",
        "dimension_id": 1,
        "rating": 4,
        "comments": "string",
        "evidence_date": "YYYY-MM-DD",
        "updated_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

#### Delete Evidence
Delete evidence entry.

**Endpoint:** `DELETE /api/evidence/{id}`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
    "success": true,
    "message": "Evidence deleted successfully"
}
```

### Dashboard API

#### Get Dashboard Data
Get dashboard data based on user role.

**Endpoint:** `GET /api/dashboard`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response (HR Admin):**
```json
{
    "success": true,
    "data": {
        "organization_stats": {
            "total_employees": 100,
            "total_evaluations": 50,
            "completion_rate": 75,
            "average_rating": 4.2
        },
        "department_performance": [
            {
                "department": "Engineering",
                "average_rating": 4.3,
                "completion_rate": 80
            },
            {
                "department": "Sales",
                "average_rating": 4.1,
                "completion_rate": 70
            }
        ],
        "recent_evaluations": [
            {
                "id": 1,
                "employee": {
                    "first_name": "string",
                    "last_name": "string"
                },
                "overall_score": 4.2,
                "status": "completed",
                "updated_at": "YYYY-MM-DD HH:MM:SS"
            }
        ]
    }
}
```

**Response (Manager):**
```json
{
    "success": true,
    "data": {
        "team_stats": {
            "team_size": 10,
            "evaluations_completed": 7,
            "completion_rate": 70,
            "average_rating": 4.2
        },
        "team_performance": [
            {
                "id": 1,
                "first_name": "string",
                "last_name": "string",
                "position": "string",
                "overall_rating": 4.2,
                "evidence_count": 15
            }
        ],
        "coaching_opportunities": [
            {
                "id": 1,
                "first_name": "string",
                "last_name": "string",
                "area": "competency",
                "current_rating": 3.2,
                "target_rating": 4.0
            }
        ]
    }
}
```

**Response (Employee):**
```json
{
    "success": true,
    "data": {
        "personal_stats": {
            "current_rating": 4.2,
            "evidence_count": 15,
            "completed_evaluations": 3,
            "pending_evaluations": 1
        },
        "recent_evidence": [
            {
                "id": 1,
                "dimension": "kpi",
                "rating": 4,
                "comments": "string",
                "evidence_date": "YYYY-MM-DD"
            }
        ],
        "goals_progress": [
            {
                "id": 1,
                "title": "string",
                "progress": 75,
                "target_date": "YYYY-MM-DD"
            }
        ]
    }
}
```

### Report API

#### Generate Report
Generate performance report.

**Endpoint:** `POST /api/reports/generate`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
    "report_type": "individual|team|department|organization",
    "employee_id": 1,
    "department": "string",
    "period_id": 1,
    "format": "pdf|excel|csv"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Report generated successfully",
    "data": {
        "report_id": 1,
        "download_url": "string",
        "expires_at": "YYYY-MM-DD HH:MM:SS"
    }
}
```

#### Download Report
Download generated report.

**Endpoint:** `GET /api/reports/{id}/download`

**Request Headers:**
```
Authorization: Bearer <token>
```

**Response:**
File download

### Error Responses

All API endpoints return consistent error responses:

```json
{
    "success": false,
    "error": {
        "code": "error_code",
        "message": "Error message",
        "details": "Additional error details"
    }
}
```

#### Common Error Codes
- `UNAUTHORIZED` (401): Authentication required or invalid
- `FORBIDDEN` (403): Insufficient permissions
- `NOT_FOUND` (404): Resource not found
- `VALIDATION_ERROR` (422): Request validation failed
- `INTERNAL_ERROR` (500): Server internal error
- Each employee can have multiple evaluations
- Each evaluation belongs to one employee
- One-to-many relationship

#### Evidence-Employee Relationship
- Each employee can have multiple evidence entries
- Each evidence entry belongs to one employee
- One-to-many relationship

#### Evaluation-Evidence Relationship
- Each evaluation can have multiple evidence entries
- Each evidence entry can be associated with multiple evaluations
- Many-to-many relationship (through evaluation_evidence table)

### Database Optimization

#### Indexes
```sql
-- User indexes
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- Employee indexes
CREATE INDEX idx_employees_employee_number ON employees(employee_number);
CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_employees_manager_id ON employees(manager_id);
CREATE INDEX idx_employees_department ON employees(department);

-- Evaluation indexes
CREATE INDEX idx_evaluations_employee_id ON evaluations(employee_id);
CREATE INDEX idx_evaluations_evaluator_id ON evaluations(evaluator_id);
CREATE INDEX idx_evaluations_period_id ON evaluations(evaluation_period_id);
CREATE INDEX idx_evaluations_status ON evaluations(status);

-- Evidence indexes
CREATE INDEX idx_evidence_employee_id ON evidence(employee_id);
CREATE INDEX idx_evidence_evaluator_id ON evidence(evaluator_id);
CREATE INDEX idx_evidence_dimension ON evidence(dimension);
CREATE INDEX idx_evidence_date ON evidence(evidence_date);
```

#### Views
```sql
-- Employee with manager view
CREATE VIEW vw_employee_manager AS
SELECT 
    e.id,
    e.first_name,
    e.last_name,
    e.position,
    e.department,
    m.first_name AS manager_first_name,
    m.last_name AS manager_last_name
FROM employees e
LEFT JOIN employees m ON e.manager_id = m.id;

-- Evaluation summary view
CREATE VIEW vw_evaluation_summary AS
SELECT 
    e.id,
    ep.name AS period_name,
    emp.first_name AS employee_first_name,
    emp.last_name AS employee_last_name,
    eval.first_name AS evaluator_first_name,
    eval.last_name AS evaluator_last_name,
    e.overall_score,
    e.status,
    e.created_at
FROM evaluations e
JOIN evaluation_periods ep ON e.evaluation_period_id = ep.id
JOIN employees emp ON e.employee_id = emp.id
JOIN employees eval ON e.evaluator_id = eval.id;
```

#### Stored Procedures
```sql
-- Calculate evaluation score
DELIMITER //
CREATE PROCEDURE sp_calculate_evaluation_score(
    IN p_evaluation_id INT,
    OUT p_score DECIMAL(5,2)
)
BEGIN
    DECLARE v_kpi_weight DECIMAL(5,2);
    DECLARE v_competency_weight DECIMAL(5,2);
    DECLARE v_responsibility_weight DECIMAL(5,2);
    DECLARE v_value_weight DECIMAL(5,2);
    
    DECLARE v_kpi_score DECIMAL(5,2);
    DECLARE v_competency_score DECIMAL(5,2);
    DECLARE v_responsibility_score DECIMAL(5,2);
    DECLARE v_value_score DECIMAL(5,2);
    
    -- Get dimension weights
    SELECT kpi_weight, competency_weight, responsibility_weight, value_weight
    INTO v_kpi_weight, v_competency_weight, v_responsibility_weight, v_value_weight
    FROM job_position_templates jpt
    JOIN employees e ON jpt.id = e.job_template_id
    JOIN evaluations ev ON e.id = ev.employee_id
    WHERE ev.id = p_evaluation_id;
    
    -- Get dimension scores
    SELECT AVG(rating) INTO v_kpi_score
    FROM evidence
    WHERE evaluation_id = p_evaluation_id AND dimension = 'kpi';
    
    SELECT AVG(rating) INTO v_competency_score
    FROM evidence
    WHERE evaluation_id = p_evaluation_id AND dimension = 'competency';
    
    SELECT AVG(rating) INTO v_responsibility_score
    FROM evidence
    WHERE evaluation_id = p_evaluation_id AND dimension = 'responsibility';
    
    SELECT AVG(rating) INTO v_value_score
    FROM evidence
    WHERE evaluation_id = p_evaluation_id AND dimension = 'value';
    
    -- Calculate overall score
    SET p_score = (v_kpi_score * v_kpi_weight + 
                   v_competency_score * v_competency_weight + 
                   v_responsibility_score * v_responsibility_weight + 
                   v_value_score * v_value_weight) / 100;
END //
DELIMITER ;
```

### Database Migration

#### Migration Scripts
- [`sql/001_database_setup.sql`](sql/001_database_setup.sql): Initial database setup
- [`sql/002_job_templates_structure.sql`](sql/002_job_templates_structure.sql): Job templates structure
- [`sql/003_phase3_advanced_features.sql`](sql/003_phase3_advanced_features.sql): Advanced features
- [`sql/004_comprehensive_enhancements.sql`](sql/004_comprehensive_enhancements.sql): Comprehensive enhancements
- [`sql/005_skills_specification_system.sql`](sql/005_skills_specification_system.sql): Skills specification
- [`sql/006_evaluation_workflow_enhancements.sql`](sql/006_evaluation_workflow_enhancements.sql): Workflow enhancements

#### Running Migrations
```bash
# Using MySQL command line
mysql -u username -p database_name < sql/001_database_setup.sql
mysql -u username -p database_name < sql/002_job_templates_structure.sql
mysql -u username -p database_name < sql/003_phase3_advanced_features.sql
mysql -u username -p database_name < sql/004_comprehensive_enhancements.sql
mysql -u username -p database_name < sql/005_skills_specification_system.sql
mysql -u username -p database_name < sql/006_evaluation_workflow_enhancements.sql

```
- [`config/database.php`](config/database.php): Database connection settings

#### Catalog Configuration
- [`config/competency_catalog.php`](config/competency_catalog.php): Competency definitions
- [`config/kpi_catalog.php`](config/kpi_catalog.php): KPI definitions

### Database Schema

#### Core Tables
- `users`: User accounts and authentication
- `employees`: Employee information and hierarchy
- `evaluations`: Evaluation records and scores
- `evidence`: Performance evidence entries
- `evaluation_periods`: Evaluation timeframes

#### Relationship Tables
- `user_roles`: Role-based access control
- `job_position_templates`: Job templates and criteria
- `company_kpis`: KPI definitions
- `competencies`: Competency definitions
- `company_values`: Company value definitions

#### Supporting Tables
- `notifications`: System notifications
- `audit_log`: System audit trail
- `files`: File attachments
- `settings`: System configuration

### Frontend Architecture

#### HTML Structure
- Semantic HTML5 markup
- Responsive design with Bootstrap
- Accessible form elements
- Consistent page structure

#### CSS Organization
- Modular CSS architecture
- Component-based styling
- Responsive design patterns
- Theme customization options

#### JavaScript Architecture
- Modular JavaScript with ES6 modules
- Event-driven programming
- AJAX for dynamic content
- Client-side validation

### API Architecture

#### RESTful API Design
- Resource-oriented URLs
- HTTP methods for operations
- JSON for data exchange
- Consistent response format

#### Authentication
- Token-based authentication
- Session management
- Role-based access control
- API rate limiting

#### Error Handling
- HTTP status codes
- Consistent error response format
- Detailed error messages
- Logging and monitoring