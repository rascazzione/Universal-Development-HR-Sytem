# Test Data Population Script - Technical Specification

## Overview
This document outlines the complete plan for creating a test data population script for the PHP Performance Evaluation System. The script will provide a comprehensive, realistic dataset for testing all system features.

## Requirements Summary
- **Data Volume**: 25-30 users, moderate complexity
- **Reset Behavior**: Complete reset (preserve system settings only)
- **User Access**: Document all usernames and passwords for testing
- **Evaluation States**: Mix of completed, in-progress, and draft evaluations
- **Periods**: 2-3 evaluation periods with realistic timeframes

## Database Schema Analysis

### Core Tables and Relationships
```
users (authentication)
├── employees (profile data)
│   ├── manager_id → employees.employee_id (hierarchy)
│   └── job_template_id → job_position_templates.id
├── departments (organizational structure)
├── evaluation_periods (time periods)
└── evaluations (performance reviews)
    ├── evaluation_kpi_results
    ├── evaluation_competency_results
    ├── evaluation_responsibility_results
    ├── evaluation_value_results
    └── evaluation_section_weights
```

### Reference Data Tables
```
company_kpis (performance indicators)
competency_categories → competencies (skills catalog)
company_values (organizational values)
job_position_templates (role definitions)
├── job_template_kpis
├── job_template_competencies
├── job_template_responsibilities
└── job_template_values
```

## Test Data Structure Design

### 1. Organizational Hierarchy
```
CEO (HR Admin)
├── IT Department Manager
│   ├── Senior Developer
│   ├── Junior Developer
│   └── DevOps Engineer
├── Sales Department Manager
│   ├── Senior Sales Rep
│   ├── Sales Rep
│   └── Sales Coordinator
├── HR Department Manager
│   ├── HR Specialist
│   └── Recruiter
├── Marketing Department Manager
│   ├── Marketing Specialist
│   └── Content Creator
└── Finance Department Manager
    ├── Accountant
    └── Financial Analyst
```

### 2. User Roles Distribution
- **1 HR Administrator**: Full system access
- **5 Managers**: Department heads with team management rights
- **20-25 Employees**: Various levels and departments

### 3. Job Templates (8-10 templates)
1. **CEO/Executive**
2. **Department Manager**
3. **Senior Developer**
4. **Junior Developer**
5. **Sales Representative**
6. **HR Specialist**
7. **Marketing Specialist**
8. **Accountant**
9. **Administrative Assistant**
10. **Customer Support**

### 4. KPIs by Category (15-20 total)
- **Sales**: Revenue Target, Customer Acquisition, Deal Closure Rate
- **Quality**: Code Quality, Bug Resolution Time, Customer Satisfaction
- **Productivity**: Project Completion, Task Efficiency, Output Volume
- **Leadership**: Team Development, Strategic Planning, Decision Making
- **Innovation**: Process Improvement, New Ideas Implementation

### 5. Competencies by Type (12-15 total)
- **Technical**: Programming, System Administration, Data Analysis
- **Soft Skills**: Communication, Problem Solving, Time Management
- **Leadership**: Team Leadership, Strategic Thinking, Conflict Resolution
- **Core**: Adaptability, Learning Agility, Customer Focus

### 6. Company Values (5 values)
1. **Integrity**: Acting with honesty and strong moral principles
2. **Excellence**: Striving for the highest quality in everything we do
3. **Innovation**: Embracing creativity and new ideas
4. **Collaboration**: Working together effectively
5. **Customer Focus**: Putting customers at the center

## Script Architecture

### Main Script Structure
```php
populate_test_data.php
├── Configuration & Setup
├── Database Reset Functions
├── Data Generation Classes
│   ├── UserGenerator
│   ├── DepartmentGenerator
│   ├── JobTemplateGenerator
│   ├── EmployeeGenerator
│   ├── EvaluationPeriodGenerator
│   └── EvaluationGenerator
├── Execution Flow
└── Documentation Output
```

### Key Functions

#### 1. Database Reset
```php
function resetDatabase() {
    // Clear all tables except system_settings
    // Reset auto-increment counters
    // Preserve system configuration
}
```

#### 2. User Generation
```php
function generateUsers() {
    // Create HR admin
    // Create department managers
    // Create employees with realistic usernames
    // Document all credentials
}
```

#### 3. Organizational Structure
```php
function createDepartments() {
    // IT, Sales, HR, Marketing, Finance departments
    // Assign managers to departments
}

function createEmployeeHierarchy() {
    // Establish manager-employee relationships
    // Ensure realistic reporting structure
}
```

#### 4. Job Templates & Components
```php
function createJobTemplates() {
    // Create role-specific templates
    // Assign appropriate KPIs, competencies, responsibilities
    // Set realistic weights and targets
}
```

#### 5. Evaluation Data
```php
function createEvaluationPeriods() {
    // Previous period (completed)
    // Current period (in progress)
    // Future period (draft)
}

function generateEvaluations() {
    // Create evaluations for all employees
    // Mix of completion states
    // Realistic scores and comments
}
```

## Data Generation Details

### User Credentials Pattern
```
Format: [role].[lastname] / [role]123
Examples:
- admin.system / admin123
- manager.smith / manager123
- john.doe / employee123
```

### Realistic Names Database
```php
$firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Lisa', 'Robert', 'Emily', 'James', 'Maria'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
```

### Performance Score Distribution
- **Excellent (4.5-5.0)**: 20% of evaluations
- **Good (3.5-4.4)**: 50% of evaluations
- **Satisfactory (2.5-3.4)**: 25% of evaluations
- **Needs Improvement (1.5-2.4)**: 5% of evaluations

### Evaluation Status Distribution
- **Approved**: 60% (previous period)
- **Reviewed**: 15% (current period)
- **Submitted**: 15% (current period)
- **Draft**: 10% (current and future periods)

## Sample Data Examples

### Sample KPI Results
```php
// Sales Representative KPI
[
    'kpi_name' => 'Monthly Sales Target',
    'target_value' => 50000.00,
    'achieved_value' => 52500.00,
    'score' => 4.2,
    'comments' => 'Exceeded monthly target by 5%. Excellent performance in Q3.'
]
```

### Sample Competency Assessment
```php
// Developer Competency
[
    'competency_name' => 'Programming Skills',
    'required_level' => 'advanced',
    'achieved_level' => 'advanced',
    'score' => 4.0,
    'comments' => 'Strong technical skills, consistently delivers quality code.'
]
```

### Sample Comments Library
```php
$positiveComments = [
    'Consistently exceeds expectations and delivers high-quality work.',
    'Shows excellent leadership skills and mentors junior team members.',
    'Demonstrates strong problem-solving abilities and innovative thinking.',
    'Excellent communication skills and works well with cross-functional teams.'
];

$improvementComments = [
    'Would benefit from improved time management and prioritization skills.',
    'Needs to work on communication with stakeholders and team members.',
    'Should focus on developing technical skills in emerging technologies.',
    'Could improve attention to detail in project deliverables.'
];
```

## Execution Flow

### Phase 1: Setup and Reset
1. Validate database connection
2. Backup existing data (optional)
3. Clear all tables except system_settings
4. Reset auto-increment counters

### Phase 2: Foundation Data
1. Create departments
2. Generate company KPIs
3. Create competency categories and competencies
4. Set up company values

### Phase 3: Job Templates
1. Create job position templates
2. Assign KPIs to templates with targets
3. Assign competencies with required levels
4. Add key responsibilities
5. Link company values

### Phase 4: Users and Employees
1. Generate user accounts with roles
2. Create employee records
3. Establish manager-employee relationships
4. Assign job templates to employees

### Phase 5: Evaluation Framework
1. Create evaluation periods
2. Generate evaluations for employees
3. Populate KPI results with realistic data
4. Add competency assessments
5. Complete responsibility ratings
6. Add company values scores
7. Generate realistic comments

### Phase 6: Documentation
1. Output user credentials list
2. Generate data summary report
3. Create testing scenarios guide

## Output Documentation

### User Credentials Report
```
=== TEST USER CREDENTIALS ===

HR ADMINISTRATORS:
- Username: admin.system | Password: admin123 | Role: hr_admin

DEPARTMENT MANAGERS:
- Username: manager.smith | Password: manager123 | Role: manager | Department: IT
- Username: manager.jones | Password: manager123 | Role: manager | Department: Sales

EMPLOYEES:
- Username: john.doe | Password: employee123 | Role: employee | Department: IT
- Username: jane.smith | Password: employee123 | Role: employee | Department: Sales
```

### Data Summary Report
```
=== TEST DATA SUMMARY ===

Users Created: 28
- HR Admins: 1
- Managers: 5
- Employees: 22

Departments: 5
Job Templates: 10
KPIs: 18
Competencies: 14
Company Values: 5

Evaluation Periods: 3
- 2024 Q3 (Completed): 25 evaluations
- 2024 Q4 (Current): 25 evaluations (mixed status)
- 2025 Q1 (Future): 25 evaluations (draft)

Total Evaluations: 75
```

## Error Handling and Validation

### Pre-execution Checks
- Database connectivity
- Required tables exist
- Sufficient permissions
- PHP extensions available

### Data Validation
- Foreign key constraints
- Required field validation
- Data type validation
- Business rule validation

### Error Recovery
- Transaction rollback on failure
- Detailed error logging
- User-friendly error messages
- Cleanup procedures

## Usage Instructions

### Command Line Execution
```bash
cd /path/to/project
php scripts/populate_test_data.php
```

### Web Interface Option
```
http://localhost/your-project/scripts/populate_test_data.php
```

### Expected Output
```
Starting test data population...
✓ Database connection established
✓ Existing data cleared
✓ Departments created (5)
✓ KPIs generated (18)
✓ Competencies created (14)
✓ Job templates built (10)
✓ Users generated (28)
✓ Employees created (25)
✓ Evaluation periods established (3)
✓ Evaluations populated (75)
✓ Documentation generated

Test data population completed successfully!
Check 'test_data_credentials.txt' for login information.
```

## Testing Scenarios

### Scenario 1: HR Admin Testing
- Login as admin.system
- View all employees and evaluations
- Create new evaluation periods
- Generate reports

### Scenario 2: Manager Testing
- Login as manager.smith
- View team members
- Create/edit evaluations for direct reports
- Review evaluation progress

### Scenario 3: Employee Testing
- Login as john.doe
- View personal evaluations
- Check performance history
- Update profile information

## Future Enhancements

### Possible Extensions
1. **Historical Data**: Multiple years of evaluation data
2. **Performance Trends**: Realistic improvement/decline patterns
3. **Custom Scenarios**: Specific testing scenarios (promotions, transfers)
4. **Bulk Operations**: Mass evaluation creation tools
5. **Data Export**: CSV/Excel export of generated data

### Configuration Options
1. **Data Volume**: Adjustable number of employees
2. **Complexity Level**: Simple, moderate, or complex hierarchies
3. **Time Periods**: Configurable evaluation periods
4. **Score Distributions**: Customizable performance patterns

This comprehensive plan ensures the test data population script will provide a realistic, complete dataset for thorough testing of the performance evaluation system.