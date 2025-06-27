# Performance Evaluation System - Software Architecture Design Document

## Table of Contents
1. [System Architecture Overview](#system-architecture-overview)
2. [Component Inventory](#component-inventory)
3. [Component Relationship Diagrams](#component-relationship-diagrams)
4. [Interface Definitions and API Contracts](#interface-definitions-and-api-contracts)
5. [Data Architecture](#data-architecture)
6. [Deployment Architecture](#deployment-architecture)
7. [Security Architecture](#security-architecture)
8. [Integration Patterns](#integration-patterns)
9. [Performance Requirements](#performance-requirements)
10. [Error Handling and Logging](#error-handling-and-logging)
11. [Monitoring and Observability](#monitoring-and-observability)
12. [Version Control and Change Management](#version-control-and-change-management)
13. [Technical Specifications by Layer](#technical-specifications-by-layer)

---

## System Architecture Overview

### High-Level Design Patterns and Principles

#### Architectural Patterns
- **Model-View-Controller (MVC)** - Separation of concerns between data, presentation, and business logic
- **Repository Pattern** - Data access abstraction layer
- **Factory Pattern** - Object creation and dependency injection
- **Observer Pattern** - Event-driven notifications and logging
- **Strategy Pattern** - Flexible evaluation algorithms and reporting formats

#### Design Principles
- **SOLID Principles**
  - Single Responsibility: Each class has one reason to change
  - Open/Closed: Open for extension, closed for modification
  - Liskov Substitution: Derived classes must be substitutable for base classes
  - Interface Segregation: Many client-specific interfaces
  - Dependency Inversion: Depend on abstractions, not concretions

- **DRY (Don't Repeat Yourself)** - Code reusability through shared components
- **KISS (Keep It Simple, Stupid)** - Simple, maintainable solutions
- **YAGNI (You Aren't Gonna Need It)** - Implement only required features
- **Separation of Concerns** - Clear boundaries between system layers

#### Architectural Style
**Layered Architecture** with the following tiers:
```
┌─────────────────────────────────────┐
│         Presentation Layer          │ ← Web UI, Templates, Assets
├─────────────────────────────────────┤
│         Application Layer           │ ← Controllers, Authentication
├─────────────────────────────────────┤
│         Business Logic Layer        │ ← Domain Classes, Services
├─────────────────────────────────────┤
│         Data Access Layer           │ ← Database Abstraction
├─────────────────────────────────────┤
│         Infrastructure Layer        │ ← Database, File System, External APIs
└─────────────────────────────────────┘
```

---

## Component Inventory

### Core Components

#### 1. Authentication & Authorization Components

**User Management Service**
- **File**: `classes/User.php`
- **Responsibilities**: User authentication, session management, password security
- **Technical Specifications**:
  - PHP 7.4+ with password_hash() for secure password storage
  - Session-based authentication with CSRF protection
  - Role-based access control (RBAC)
  - Failed login attempt tracking and account lockout
- **Dependencies**: Database connection, session management
- **Interfaces**: IUserRepository, IAuthenticationService

**Authentication Middleware**
- **File**: `includes/auth.php`
- **Responsibilities**: Request authentication, authorization checks, session validation
- **Technical Specifications**:
  - Session timeout management (configurable, default 3600s)
  - CSRF token generation and validation
  - Role-based route protection
- **Dependencies**: User service, session storage
- **Interfaces**: IAuthenticationMiddleware

#### 2. Business Logic Components

**Employee Management Service**
- **File**: `classes/Employee.php`
- **Responsibilities**: Employee CRUD operations, organizational hierarchy, team management
- **Technical Specifications**:
  - Hierarchical data management with manager-employee relationships
  - Soft delete functionality for data integrity
  - Search and filtering capabilities
  - Bulk operations support
- **Dependencies**: Database connection, audit logging
- **Interfaces**: IEmployeeRepository, IHierarchyService

**Evaluation Management Service**
- **File**: `classes/Evaluation.php`
- **Responsibilities**: Evaluation lifecycle, scoring algorithms, workflow management
- **Technical Specifications**:
  - JSON-based flexible evaluation data storage
  - Weighted scoring calculation (Expected Results: 40%, Skills: 25%, Responsibilities: 25%, Values: 10%)
  - Status workflow (draft → submitted → reviewed → approved)
  - Comment and feedback management
- **Dependencies**: Employee service, period service, notification service
- **Interfaces**: IEvaluationRepository, IScoringService, IWorkflowService

**Evaluation Period Management Service**
- **File**: `classes/EvaluationPeriod.php`
- **Responsibilities**: Period lifecycle, scheduling, overlap validation
- **Technical Specifications**:
  - Flexible period types (monthly, quarterly, semi-annual, annual, custom)
  - Overlap detection and validation
  - Automatic period generation
  - Period status management
- **Dependencies**: Database connection, validation service
- **Interfaces**: IPeriodRepository, ISchedulingService

**Job Template Management Service**
- **File**: `classes/JobTemplate.php`
- **Responsibilities**: Job template CRUD operations, assignment of KPIs, competencies, and values
- **Technical Specifications**:
  - Management of job templates with associated responsibilities, KPIs, competencies, and values
  - Weighting and scoring configuration for each template component
- **Dependencies**: Database connection, KPI service, Competency service, Value service
- **Interfaces**: IJobTemplateRepository

**Performance Metrics Service (KPIs, Competencies, Values)**
- **Files**: `classes/CompanyKPI.php`, `classes/Competency.php`, `classes/CompanyValues.php`
- **Responsibilities**: Management of company-wide performance metrics
- **Technical Specifications**:
  - CRUD operations for KPIs, competencies, and company values
  - Categorization and management of competencies
- **Dependencies**: Database connection
- **Interfaces**: IKPIRepository, ICompetencyRepository, IValueRepository

#### 3. Data Access Components

**Database Connection Manager**
- **File**: `config/database.php`
- **Responsibilities**: Database connection pooling, query execution, transaction management
- **Technical Specifications**:
  - PDO-based MySQL connection with prepared statements
  - Connection pooling and reuse
  - Transaction support with rollback capabilities
  - Query logging and performance monitoring
- **Dependencies**: MySQL 8.0+, PDO extension
- **Interfaces**: IConnectionManager, IQueryExecutor

**Repository Base Class**
- **Responsibilities**: Common CRUD operations, query building, result mapping
- **Technical Specifications**:
  - Generic repository pattern implementation
  - Parameterized query building
  - Result set mapping to domain objects
  - Pagination and sorting support
- **Dependencies**: Database connection manager
- **Interfaces**: IRepository<T>

#### 4. Presentation Components

**Template Engine**
- **Files**: `templates/header.php`, `templates/footer.php`
- **Responsibilities**: HTML rendering, layout management, component reuse
- **Technical Specifications**:
  - PHP-based templating with output buffering
  - Component-based architecture
  - XSS protection through htmlspecialchars()
  - Responsive Bootstrap 5.3 integration
- **Dependencies**: Authentication service, configuration
- **Interfaces**: ITemplateEngine, IViewRenderer

**Asset Management**
- **Files**: `assets/css/style.css`, `assets/js/app.js`
- **Responsibilities**: Static asset serving, optimization, caching
- **Technical Specifications**:
  - CSS custom properties for theming
  - JavaScript ES6+ with backward compatibility
  - Asset minification and compression
  - CDN integration for external libraries
- **Dependencies**: Web server configuration
- **Interfaces**: IAssetManager

#### 5. Configuration Components

**Application Configuration**
- **File**: `config/config.php`
- **Responsibilities**: Environment-specific settings, feature flags, constants
- **Technical Specifications**:
  - Environment-based configuration loading
  - Secure credential management
  - Feature toggle implementation
  - Performance tuning parameters
- **Dependencies**: Environment variables, file system
- **Interfaces**: IConfigurationProvider

**Database Configuration**
- **File**: `config/database.php`
- **Responsibilities**: Database connection parameters, optimization settings
- **Technical Specifications**:
  - Connection string management
  - Pool size configuration
  - Timeout and retry settings
  - SSL/TLS configuration
- **Dependencies**: Database server
- **Interfaces**: IDatabaseConfiguration

### Supporting Components

#### 6. Utility Components

**Validation Service**
- **Responsibilities**: Input validation, business rule enforcement, data sanitization
- **Technical Specifications**:
  - Rule-based validation engine
  - Custom validation rules
  - Internationalization support
  - Error message management
- **Dependencies**: Configuration service
- **Interfaces**: IValidationService

**Logging Service**
- **Responsibilities**: Application logging, audit trails, error tracking
- **Technical Specifications**:
  - PSR-3 compliant logging interface
  - Multiple log levels (DEBUG, INFO, WARN, ERROR, FATAL)
  - Structured logging with JSON format
  - Log rotation and archival
- **Dependencies**: File system, database
- **Interfaces**: ILogger, IAuditLogger

**Notification Service**
- **Responsibilities**: Email notifications, system alerts, user communications
- **Technical Specifications**:
  - SMTP integration with authentication
  - Template-based email composition
  - Queue-based asynchronous processing
  - Delivery tracking and retry logic
- **Dependencies**: SMTP server, template engine
- **Interfaces**: INotificationService, IEmailService

---

## Component Relationship Diagrams

### System Context Diagram
```mermaid
graph TB
    subgraph "External Actors"
        HR[HR Administrator]
        MGR[Manager]
        EMP[Employee]
        SMTP[SMTP Server]
        DB[(MySQL Database)]
    end
    
    subgraph "Performance Evaluation System"
        WEB[Web Application]
    end
    
    HR --> WEB
    MGR --> WEB
    EMP --> WEB
    WEB --> SMTP
    WEB --> DB
```

### Component Dependency Diagram
```mermaid
graph TD
    subgraph "Presentation Layer"
        UI[Web UI]
        TMPL[Templates]
        ASSETS[Assets]
    end
    
    subgraph "Application Layer"
        AUTH[Authentication]
        CTRL[Controllers]
        MIDDLEWARE[Middleware]
    end
    
    subgraph "Business Logic Layer"
        USER[User Service]
        EMP[Employee Service]
        EVAL[Evaluation Service]
        PERIOD[Period Service]
        JT[Job Template Service]
        METRICS[Performance Metrics Service]
    end
    
    subgraph "Data Access Layer"
        REPO[Repositories]
        CONN[Connection Manager]
    end
    
    subgraph "Infrastructure Layer"
        DB[(Database)]
        FS[File System]
        MAIL[Mail Server]
    end
    
    UI --> CTRL
    TMPL --> UI
    ASSETS --> UI
    CTRL --> AUTH
    CTRL --> MIDDLEWARE
    AUTH --> USER
    CTRL --> EMP
    CTRL --> EVAL
    CTRL --> PERIOD
    CTRL --> JT
    CTRL --> METRICS
    USER --> REPO
    EMP --> REPO
    EVAL --> REPO
    PERIOD --> REPO
    JT --> REPO
    METRICS --> REPO
    REPO --> CONN
    CONN --> DB
    USER --> FS
    EVAL --> MAIL
```

### Data Flow Diagram
```mermaid
graph LR
    subgraph "User Request Flow"
        A[User Request] --> B[Authentication Check]
        B --> C[Authorization Check]
        C --> D[Controller Action]
        D --> E[Business Logic]
        E --> F[Data Access]
        F --> G[Database]
        G --> H[Response Generation]
        H --> I[Template Rendering]
        I --> J[User Response]
    end
    
    subgraph "Evaluation Process Flow"
        K[Create Evaluation] --> L[Select Employee]
        L --> M[Choose Period]
        M --> N[Fill Evaluation Form]
        N --> O[Calculate Scores]
        O --> P[Save Draft]
        P --> Q[Submit for Review]
        Q --> R[Approval Process]
        R --> S[Generate Reports]
    end
```

### Communication Patterns
```mermaid
sequenceDiagram
    participant U as User
    participant C as Controller
    participant A as Auth Service
    participant B as Business Service
    participant D as Database
    
    U->>C: HTTP Request
    C->>A: Authenticate User
    A->>D: Validate Session
    D-->>A: Session Data
    A-->>C: Authentication Result
    C->>B: Execute Business Logic
    B->>D: Data Operations
    D-->>B: Query Results
    B-->>C: Business Result
    C-->>U: HTTP Response
```

### User Interaction Sequences

#### Complete Evaluation Workflow Sequence
```mermaid
sequenceDiagram
    participant HR as HR Admin
    participant MGR as Manager
    participant EMP as Employee
    participant SYS as System
    participant DB as Database
    participant EMAIL as Email Service

    Note over HR,EMAIL: 1. Setup Phase
    HR->>SYS: Create Job Templates
    SYS->>DB: Store Templates with KPIs/Competencies
    HR->>SYS: Create Evaluation Period
    SYS->>DB: Store Period Configuration
    HR->>SYS: Assign Templates to Employees
    SYS->>DB: Update Employee Records

    Note over HR,EMAIL: 2. Evaluation Creation
    MGR->>SYS: Access Evaluation System
    SYS->>SYS: Authenticate & Authorize
    MGR->>SYS: Create New Evaluation
    SYS->>DB: Fetch Employee Job Template
    SYS->>DB: Initialize Evaluation with Template Data
    SYS->>MGR: Display Evaluation Form (KPIs, Competencies, etc.)

    Note over HR,EMAIL: 3. Evaluation Completion
    MGR->>SYS: Fill KPI Scores & Comments
    MGR->>SYS: Rate Competencies
    MGR->>SYS: Evaluate Responsibilities
    MGR->>SYS: Score Company Values
    MGR->>SYS: Save Draft
    SYS->>DB: Store All Section Data
    SYS->>SYS: Calculate Weighted Overall Rating
    SYS->>DB: Update Overall Rating
    MGR->>SYS: Submit Evaluation
    SYS->>DB: Update Status to 'submitted'
    SYS->>EMAIL: Send Notification to HR
    SYS->>EMAIL: Send Notification to Employee

    Note over HR,EMAIL: 4. Review & Approval
    HR->>SYS: Review Submitted Evaluation
    SYS->>DB: Fetch Complete Evaluation Data
    HR->>SYS: Approve/Request Changes
    alt Approved
        SYS->>DB: Update Status to 'approved'
        SYS->>EMAIL: Send Approval Notification
    else Changes Requested
        SYS->>DB: Update Status to 'draft'
        SYS->>EMAIL: Send Revision Request
    end

    Note over HR,EMAIL: 5. Employee Access
    EMP->>SYS: View Own Evaluation
    SYS->>SYS: Verify Employee Access Rights
    SYS->>DB: Fetch Employee's Evaluations
    SYS->>EMP: Display Read-Only Evaluation
```

#### Job Template Management Sequence
```mermaid
sequenceDiagram
    participant HR as HR Admin
    participant SYS as System
    participant DB as Database

    Note over HR,DB: Job Template Creation
    HR->>SYS: Access Job Template Management
    SYS->>DB: Fetch Available KPIs, Competencies, Values
    SYS->>HR: Display Template Creation Form
    HR->>SYS: Enter Template Details
    HR->>SYS: Select KPIs with Weights
    HR->>SYS: Select Competencies with Levels
    HR->>SYS: Define Responsibilities
    HR->>SYS: Assign Company Values
    HR->>SYS: Save Template
    SYS->>DB: Store Template Configuration
    SYS->>DB: Store KPI Assignments
    SYS->>DB: Store Competency Requirements
    SYS->>DB: Store Responsibility Definitions
    SYS->>DB: Store Value Assignments
    SYS->>HR: Confirm Template Created

    Note over HR,DB: Template Assignment
    HR->>SYS: Assign Template to Employee
    SYS->>DB: Update Employee Record
    SYS->>DB: Link Employee to Job Template
    SYS->>HR: Confirm Assignment
```

#### Authentication & Authorization Flow
```mermaid
sequenceDiagram
    participant USER as User
    participant WEB as Web Server
    participant AUTH as Auth Service
    participant SESSION as Session Store
    participant DB as Database

    Note over USER,DB: Login Process
    USER->>WEB: Submit Login Credentials
    WEB->>AUTH: Validate Credentials
    AUTH->>DB: Query User Record
    DB-->>AUTH: Return User Data
    AUTH->>AUTH: Verify Password Hash
    alt Valid Credentials
        AUTH->>SESSION: Create Session
        SESSION-->>AUTH: Return Session ID
        AUTH->>DB: Update Last Login
        AUTH-->>WEB: Authentication Success
        WEB-->>USER: Set Session Cookie & Redirect
    else Invalid Credentials
        AUTH->>DB: Log Failed Attempt
        AUTH-->>WEB: Authentication Failed
        WEB-->>USER: Display Error Message
    end

    Note over USER,DB: Subsequent Requests
    USER->>WEB: Request Protected Resource
    WEB->>SESSION: Validate Session
    SESSION-->>WEB: Session Data
    WEB->>AUTH: Check Permissions
    AUTH->>DB: Query User Roles
    DB-->>AUTH: Return Role Data
    AUTH-->>WEB: Authorization Result
    alt Authorized
        WEB-->>USER: Serve Protected Content
    else Unauthorized
        WEB-->>USER: Access Denied
    end
```

#### Evaluation Data Flow with Weighted Scoring
```mermaid
sequenceDiagram
    participant MGR as Manager
    participant FORM as Evaluation Form
    participant CALC as Score Calculator
    participant DB as Database

    Note over MGR,DB: Score Input & Calculation
    MGR->>FORM: Enter KPI Scores
    FORM->>CALC: Calculate KPI Section Score
    CALC->>CALC: Apply KPI Weights from Template
    CALC-->>FORM: Return Weighted KPI Score

    MGR->>FORM: Rate Competencies
    FORM->>CALC: Calculate Competency Score
    CALC->>CALC: Apply Competency Weights
    CALC-->>FORM: Return Weighted Competency Score

    MGR->>FORM: Score Responsibilities
    FORM->>CALC: Calculate Responsibility Score
    CALC->>CALC: Apply Responsibility Weights
    CALC-->>FORM: Return Weighted Responsibility Score

    MGR->>FORM: Rate Company Values
    FORM->>CALC: Calculate Values Score
    CALC->>CALC: Apply Values Weights
    CALC-->>FORM: Return Weighted Values Score

    FORM->>CALC: Calculate Overall Rating
    CALC->>CALC: Combine All Weighted Scores
    CALC-->>FORM: Return Overall Rating (0.00-5.00)

    MGR->>FORM: Save Evaluation
    FORM->>DB: Store All Section Scores
    FORM->>DB: Store Individual Comments
    FORM->>DB: Store Overall Rating
    FORM->>DB: Update Timestamps
    DB-->>FORM: Confirm Save Success
    FORM-->>MGR: Display Success Message
```

---

## Interface Definitions and API Contracts

### Authentication Interfaces

```php
interface IAuthenticationService
{
    public function authenticate(string $username, string $password): AuthResult;
    public function validateSession(string $sessionId): bool;
    public function logout(string $sessionId): void;
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool;
}

interface IAuthorizationService
{
    public function hasPermission(int $userId, string $permission): bool;
    public function getUserRoles(int $userId): array;
    public function checkAccess(int $userId, string $resource, string $action): bool;
}
```

### Business Logic Interfaces

```php
interface IUserRepository
{
    public function findById(int $id): ?User;
    public function findByUsername(string $username): ?User;
    public function create(UserData $userData): int;
    public function update(int $id, UserData $userData): bool;
    public function delete(int $id): bool;
}

interface IEvaluationService
{
    public function createEvaluation(EvaluationData $data): int;
    public function updateEvaluation(int $id, EvaluationData $data): bool;
    public function calculateScore(EvaluationData $data): float;
    public function submitForReview(int $evaluationId): bool;
    public function approveEvaluation(int $evaluationId): bool;
}

interface IEmployeeService
{
    public function getEmployee(int $id): ?Employee;
    public function getTeamMembers(int $managerId): array;
    public function getHierarchy(int $rootId = null): array;
    public function createEmployee(EmployeeData $data): int;
    public function updateEmployee(int $id, EmployeeData $data): bool;
}

interface IJobTemplateService
{
    public function getTemplate(int $id): ?JobTemplate;
    public function createTemplate(JobTemplateData $data): int;
    public function updateTemplate(int $id, JobTemplateData $data): bool;
    public function assignKpi(int $templateId, int $kpiId, float $weight): bool;
    public function assignCompetency(int $templateId, int $competencyId, string $level, float $weight): bool;
}
```

### Data Access Interfaces

```php
interface IRepository
{
    public function find(int $id): ?object;
    public function findAll(array $criteria = [], int $limit = null, int $offset = null): array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function count(array $criteria = []): int;
}

interface IConnectionManager
{
    public function getConnection(): PDO;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
    public function executeQuery(string $sql, array $params = []): PDOStatement;
}
```

### API Response Contracts

```php
class ApiResponse
{
    public bool $success;
    public mixed $data;
    public ?string $message;
    public ?array $errors;
    public int $statusCode;
    public array $metadata;
}

class PaginatedResponse extends ApiResponse
{
    public int $total;
    public int $page;
    public int $perPage;
    public int $totalPages;
    public bool $hasNext;
    public bool $hasPrevious;
}
```

---

## Data Architecture

### Database Schema Design

#### Entity Relationship Diagram
```mermaid
erDiagram
    USERS {
        int user_id PK
        string username
        string email
        string password_hash
        enum role
        boolean is_active
        timestamp last_login
        timestamp created_at
        timestamp updated_at
    }

    EMPLOYEES {
        int employee_id PK
        int user_id FK
        string employee_number
        string first_name
        string last_name
        string position
        string department
        int manager_id FK
        int job_template_id FK
        date hire_date
        string phone
        text address
        boolean active
        timestamp created_at
        timestamp updated_at
    }

    JOB_POSITION_TEMPLATES {
        int id PK
        string position_title
        string department
        text description
        int created_by FK
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    COMPANY_KPIS {
        int id PK
        string kpi_name
        text kpi_description
        string measurement_unit
        string category
        enum target_type
        int created_by FK
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    COMPETENCIES {
        int id PK
        string competency_name
        text description
        int category_id FK
        enum competency_type
        boolean is_active
        timestamp created_at
    }

    COMPETENCY_CATEGORIES {
        int id PK
        string category_name
        text description
        int parent_id FK
        boolean is_active
        timestamp created_at
    }

    COMPANY_VALUES {
        int id PK
        string value_name
        text description
        int sort_order
        int created_by FK
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    EVALUATION_PERIODS {
        int period_id PK
        string period_name
        date start_date
        date end_date
        enum status
        text description
        timestamp created_at
        timestamp updated_at
    }

    EVALUATIONS {
        int evaluation_id PK
        int employee_id FK
        int evaluator_id FK
        int period_id FK
        int job_template_id FK
        decimal overall_rating
        text overall_comments
        text development_goals
        text strengths
        enum status
        timestamp submitted_at
        timestamp reviewed_at
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }

    JOB_TEMPLATE_KPIS {
        int id PK
        int job_template_id FK
        int kpi_id FK
        decimal weight_percentage
        timestamp created_at
    }

    JOB_TEMPLATE_COMPETENCIES {
        int id PK
        int job_template_id FK
        int competency_id FK
        enum required_level
        decimal weight_percentage
        timestamp created_at
    }

    JOB_TEMPLATE_RESPONSIBILITIES {
        int id PK
        int job_template_id FK
        text responsibility_description
        decimal weight_percentage
        timestamp created_at
    }

    JOB_TEMPLATE_VALUES {
        int id PK
        int job_template_id FK
        int value_id FK
        decimal weight_percentage
        timestamp created_at
    }

    EVALUATION_KPI_RESULTS {
        int id PK
        int evaluation_id FK
        int kpi_id FK
        decimal target_value
        decimal achieved_value
        decimal score
        text comments
        decimal weight_percentage
        timestamp updated_at
        timestamp created_at
    }

    EVALUATION_COMPETENCY_RESULTS {
        int id PK
        int evaluation_id FK
        int competency_id FK
        enum required_level
        enum achieved_level
        decimal score
        text comments
        decimal weight_percentage
        timestamp updated_at
        timestamp created_at
    }

    EVALUATION_RESPONSIBILITY_RESULTS {
        int id PK
        int evaluation_id FK
        int responsibility_id FK
        decimal score
        text comments
        decimal weight_percentage
        timestamp updated_at
        timestamp created_at
    }

    EVALUATION_VALUE_RESULTS {
        int id PK
        int evaluation_id FK
        int value_id FK
        decimal score
        text comments
        decimal weight_percentage
        timestamp updated_at
        timestamp created_at
    }

    USERS ||--|{ EMPLOYEES : "has"
    USERS ||--|{ EVALUATIONS : "creates"
    EMPLOYEES ||--o{ EVALUATIONS : "is evaluated in"
    EMPLOYEES ||--o{ EMPLOYEES : "manages"
    JOB_POSITION_TEMPLATES ||--|{ EMPLOYEES : "is assigned"
    JOB_POSITION_TEMPLATES ||--|{ EVALUATIONS : "is based on"
    EVALUATION_PERIODS ||--|{ EVALUATIONS : "occurs in"

    JOB_POSITION_TEMPLATES ||--|{ JOB_TEMPLATE_KPIS : "has"
    COMPANY_KPIS ||--|{ JOB_TEMPLATE_KPIS : "is assigned via"
    JOB_POSITION_TEMPLATES ||--|{ JOB_TEMPLATE_COMPETENCIES : "has"
    COMPETENCIES ||--|{ JOB_TEMPLATE_COMPETENCIES : "is assigned via"
    JOB_POSITION_TEMPLATES ||--|{ JOB_TEMPLATE_RESPONSIBILITIES : "has"
    JOB_POSITION_TEMPLATES ||--|{ JOB_TEMPLATE_VALUES : "has"
    COMPANY_VALUES ||--|{ JOB_TEMPLATE_VALUES : "is assigned via"

    COMPETENCY_CATEGORIES ||--|{ COMPETENCIES : "categorizes"
    COMPETENCY_CATEGORIES ||--o{ COMPETENCY_CATEGORIES : "has parent"

    EVALUATIONS ||--|{ EVALUATION_KPI_RESULTS : "has"
    COMPANY_KPIS ||--|{ EVALUATION_KPI_RESULTS : "is measured in"
    EVALUATIONS ||--|{ EVALUATION_COMPETENCY_RESULTS : "has"
    COMPETENCIES ||--|{ EVALUATION_COMPETENCY_RESULTS : "is measured in"
    EVALUATIONS ||--|{ EVALUATION_RESPONSIBILITY_RESULTS : "has"
    JOB_TEMPLATE_RESPONSIBILITIES ||--|{ EVALUATION_RESPONSIBILITY_RESULTS : "is measured in"
    EVALUATIONS ||--|{ EVALUATION_VALUE_RESULTS : "has"
    COMPANY_VALUES ||--|{ EVALUATION_VALUE_RESULTS : "is measured in"
```

### Data Models

#### Core Domain Models

```php
class User
{
    private int $userId;
    private string $username;
    private string $email;
    private string $passwordHash;
    private UserRole $role;
    private bool $isActive;
    private ?DateTime $lastLogin;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    
    // Methods for business logic
    public function authenticate(string $password): bool;
    public function hasPermission(string $permission): bool;
    public function updateLastLogin(): void;
}

class Employee
{
    private int $employeeId;
    private ?int $userId;
    private string $employeeNumber;
    private string $firstName;
    private string $lastName;
    private ?string $position;
    private ?string $department;
    private ?int $managerId;
    private ?DateTime $hireDate;
    private ?string $phone;
    private ?string $address;
    private bool $active;
    
    // Business methods
    public function getFullName(): string;
    public function getManager(): ?Employee;
    public function getDirectReports(): array;
    public function isManagerOf(Employee $employee): bool;
}

class Evaluation
{
    private int $evaluationId;
    private int $employeeId;
    private int $evaluatorId;
    private int $periodId;
    private EvaluationData $expectedResults;
    private EvaluationData $skillsCompetencies;
    private EvaluationData $keyResponsibilities;
    private EvaluationData $livingValues;
    private ?float $overallRating;
    private EvaluationStatus $status;
    
    // Business methods
    public function calculateOverallRating(): float;
    public function canBeEditedBy(User $user): bool;
    public function submit(): void;
    public function approve(): void;
}
```

### Storage Strategies

#### Primary Storage
- **Database**: MySQL 8.0+ with InnoDB engine
- **Connection Pooling**: PDO with persistent connections
- **Indexing Strategy**:
  - Primary keys on all tables
  - Foreign key indexes for relationships
  - Composite indexes for common query patterns
  - Full-text indexes for search functionality

#### Caching Strategy
- **Application-level caching**: PHP APCu for configuration and session data
- **Database query caching**: MySQL query cache for repeated queries
- **Static asset caching**: Browser caching with ETags and cache headers

#### Backup and Recovery
- **Daily automated backups** with 30-day retention
- **Point-in-time recovery** capability
- **Backup verification** and restoration testing
- **Disaster recovery plan** with RTO < 4 hours, RPO < 1 hour

---
## Security Architecture

### Authentication Mechanisms

#### Multi-Factor Authentication (Future Enhancement)
```php
interface IMFAService
{
    public function generateTOTP(int $userId): string;
    public function validateTOTP(int $userId, string $token): bool;
    public function generateBackupCodes(int $userId): array;
    public function validateBackupCode(int $userId, string $code): bool;
}
```

#### Session Management
```php
class SecureSessionManager
{
    private const SESSION_TIMEOUT = 3600; // 1 hour
    private const REGENERATE_INTERVAL = 300; // 5 minutes
    
    public function startSession(): void;
    public function regenerateId(): void;
    public function validateSession(): bool;
    public function destroySession(): void;
    public function isExpired(): bool;
}
```

### Authorization Framework

#### Role-Based Access Control (RBAC)
```yaml
Roles:
  hr_admin:
    permissions:
      - user.create
      - user.read
      - user.update
      - user.delete
      - employee.create
      - employee.read
      - employee.update
      - employee.delete
      - evaluation.create
      - evaluation.read
      - evaluation.update
      - evaluation.delete
      - period.create
      - period.read
      - period.update
      - period.delete
      - report.generate
      - system.configure
      
  manager:
    permissions:
      - employee.read (team only)
      - evaluation.create (team only)
      - evaluation.read (team only)
      - evaluation.update (own only)
      - report.view (team only)
      
  employee:
    permissions:
      - evaluation.read (own only)
      - profile.update (own only)
```

### Data Protection Mechanisms

#### Encryption at Rest
- **Database Encryption**: MySQL Transparent Data Encryption (TDE)
- **File System Encryption**: LUKS for Linux systems
- **Backup Encryption**: AES-256 encryption for backup files
- **Key Management**: Hardware Security Module (HSM) or cloud KMS

#### Encryption in Transit
- **HTTPS/TLS 1.3**: All web traffic encrypted
- **Database Connections**: SSL/TLS for database connections
- **API Communications**: Certificate-based authentication
- **Email**: STARTTLS for SMTP communications

#### Data Sanitization
```php
class DataSanitizer
{
    public function sanitizeInput(mixed $input): mixed;
    public function sanitizeOutput(mixed $output): mixed;
    public function validateEmail(string $email): bool;
    public function validatePassword(string $password): ValidationResult;
    public function escapeHtml(string $html): string;
    public function preventSqlInjection(string $query, array $params): string;
}
```

### Security Headers and Policies

#### HTTP Security Headers
```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' cdnjs.cloudflare.com"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

#### Content Security Policy
```javascript
const cspPolicy = {
    'default-src': ["'self'"],
    'script-src': ["'self'", "'unsafe-inline'", "cdn.jsdelivr.net", "cdnjs.cloudflare.com"],
    'style-src': ["'self'", "'unsafe-inline'", "cdn.jsdelivr.net", "cdnjs.cloudflare.com"],
    'img-src': ["'self'", "data:", "*.gravatar.com"],
    'font-src': ["'self'", "cdnjs.cloudflare.com"],
    'connect-src': ["'self'"],
    'frame-ancestors': ["'none'"],
    'base-uri': ["'self'"],
    'form-action': ["'self'"]
};
```

### Vulnerability Management

#### Security Scanning
- **Static Code Analysis**: PHPStan, Psalm for PHP code analysis
- **Dependency Scanning**: Composer audit for vulnerable packages
- **Infrastructure Scanning**: Nessus or OpenVAS for system vulnerabilities
- **Web Application Scanning**: OWASP ZAP for runtime security testing

#### Penetration Testing Schedule
```yaml
Frequency:
  Internal Testing: Quarterly
  External Testing: Bi-annually
  Code Review: Every release
  Dependency Audit: Monthly

Scope:
  - Authentication and authorization
  - Input validation and sanitization
  - Session management
  - Database security
  - Infrastructure hardening
  - Social engineering resistance
```

---

## Integration Patterns

### External System Dependencies

#### Email Integration
```php
interface IEmailService
{
    public function sendEmail(EmailMessage $message): bool;
    public function sendBulkEmail(array $messages): BulkEmailResult;
    public function getDeliveryStatus(string $messageId): DeliveryStatus;
}

class SMTPEmailService implements IEmailService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private bool $encryption;
    
    public function configure(SMTPConfig $config): void;
    public function testConnection(): bool;
}
```

#### LDAP/Active Directory Integration (Future)
```php
interface IDirectoryService
{
    public function authenticate(string $username, string $password): bool;
    public function getUserInfo(string $username): DirectoryUser;
    public function getGroupMembership(string $username): array;
    public function syncUsers(): SyncResult;
}

class LDAPDirectoryService implements IDirectoryService
{
    private string $server;
    private string $baseDn;
    private string $bindDn;
    private string $bindPassword;
    
    public function connect(): bool;
    public function search(string $filter, array $attributes = []): array;
}
```

#### Single Sign-On (SSO) Integration (Future)
```php
interface ISSOProvider
{
    public function initiateLogin(string $returnUrl): string;
    public function handleCallback(array $samlResponse): SSOResult;
    public function logout(string $sessionId): void;
}

class SAMLSSOProvider implements ISSOProvider
{
    private string $entityId;
    private string $ssoUrl;
    private string $sloUrl;
    private string $certificate;
    
    public function validateAssertion(string $assertion): bool;
    public function extractUserAttributes(string $assertion): array;
}
```

### API Integration Patterns

#### RESTful API Design
```php
abstract class BaseApiController
{
    protected function jsonResponse(mixed $data, int $statusCode = 200): JsonResponse;
    protected function errorResponse(string $message, int $statusCode = 400): JsonResponse;
    protected function validateRequest(array $rules): ValidationResult;
    protected function paginate(array $data, int $page, int $perPage): PaginatedResponse;
}

class EvaluationApiController extends BaseApiController
{
    public function index(Request $request
