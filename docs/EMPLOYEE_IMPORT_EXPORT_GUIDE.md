# Employee Import/Export System Guide

## Overview

The Employee Import/Export system provides comprehensive functionality to export all employee data and import employee information from CSV files. This system is designed for HR administrators to manage employee data efficiently and supports both basic employee information and complete performance data.

## Features

### Export Functionality

#### Basic Export (CSV)
- Exports core employee information in CSV format
- Includes: employee_number, name, email, position, department, manager, etc.
- Direct download as CSV file
- Supports filtering by active/inactive employees

#### Complete Export (ZIP)
- Exports all employee-related data across the entire system
- Includes multiple CSV files in a ZIP archive:
  - `employees.csv` - Core employee data
  - `evaluations.csv` - Performance evaluations
  - `evaluation_kpi_results.csv` - KPI evaluation details
  - `evaluation_competency_results.csv` - Competency evaluation details
  - `evaluation_responsibility_results.csv` - Responsibility evaluation details
  - `evaluation_value_results.csv` - Company values evaluation details
  - `achievements.csv` - Achievement journal entries
  - `kudos.csv` - KUDOS recognitions given and received
  - `okrs.csv` - OKRs and performance goals
  - `idps.csv` - Individual Development Plans
  - `idp_activities.csv` - Development activities
  - `growth_evidence.csv` - Growth evidence entries
  - `manager_feedback_summary.csv` - Aggregated upward feedback (anonymized)
  - `self_assessments.csv` - Self-assessment data

### Import Functionality

#### CSV Import with Validation
- Upload CSV files with employee data
- Three-step process: Upload → Validate → Import
- Comprehensive validation before import
- Support for both creating new employees and updating existing ones
- Default password assignment with forced change on first login

#### Validation Features
- Required field validation
- Email format validation
- Duplicate detection (within import file and against existing data)
- Manager reference validation
- Job template validation
- Date format validation
- Role validation

## Access Requirements

- **HR Admin role required** for all import/export operations
- Managers can view the employee list but cannot access import/export functions
- All operations are logged in the audit trail

## How to Use

### Exporting Employee Data

1. **Navigate to Employee List**
   - Go to `/employees/list.php`
   - Only HR Admins will see the export/import buttons

2. **Choose Export Type**
   - Click the "Export" dropdown button
   - Select "Basic (CSV)" for core employee data only
   - Select "Complete (ZIP)" for all employee-related data
   - Select "Download Template" to get an import template

3. **Export Options**
   - Toggle "Show inactive employees" to include inactive employees in export
   - Basic export downloads immediately
   - Complete export prepares a ZIP file and provides download link

### Importing Employee Data

1. **Prepare CSV File**
   - Download the import template from the Export dropdown
   - Fill in employee data following the template format
   - Required fields: employee_number, first_name, last_name, email

2. **Upload and Validate**
   - Click the "Import" button to open the import modal
   - Select your CSV file (max 10MB)
   - Click "Validate" to check the data

3. **Review Validation Results**
   - Review the validation summary
   - Check any errors that need to be fixed
   - Valid rows will be highlighted for import

4. **Perform Import**
   - Click "Import" to process valid employees
   - Monitor the import progress
   - Review the import results

## CSV Format Specification

### Required Fields
- `employee_number` - Unique identifier for the employee
- `first_name` - Employee's first name
- `last_name` - Employee's last name
- `email` - Valid email address (must be unique)

### Optional Fields
- `username` - Login username (auto-generated from email if not provided)
- `role` - User role (hr_admin, manager, employee) - defaults to 'employee'
- `position` - Job title/position
- `department` - Department name
- `manager_employee_number` - Employee number of the manager
- `hire_date` - Hire date in YYYY-MM-DD format
- `phone` - Phone number
- `address` - Address
- `active` - Active status (1 for active, 0 for inactive) - defaults to 1
- `job_template_title` - Job template title (must exist in system)

### Example CSV Format
```csv
employee_number,first_name,last_name,email,username,role,position,department,manager_employee_number,hire_date,phone,address,active,job_template_title
EMP001,John,Doe,john.doe@company.com,jdoe,employee,Software Engineer,IT,EMP100,2024-01-15,555-0100,123 Main St,1,Software Developer
EMP002,Jane,Smith,jane.smith@company.com,jsmith,manager,Team Lead,IT,,2024-01-10,555-0200,456 Oak Ave,1,Team Leader
```

## Import Behavior

### New Employees
- Creates new employee record
- Creates linked user account
- Sets default password "Welcome2024!"
- Forces password change on first login
- Generates username from email if not provided

### Existing Employees
- Matches by employee_number
- Updates existing employee information
- Updates linked user account if needed
- Preserves existing password

### Validation Rules
- Employee numbers must be unique
- Email addresses must be unique
- Manager employee numbers must exist and be active
- Job template titles must exist and be active
- Hire dates must be in YYYY-MM-DD format
- Roles must be: hr_admin, manager, or employee

## Error Handling

### Import Errors
- Individual row errors don't stop the entire import
- Detailed error reporting for each failed row
- Transaction rollback if more than 50% of rows fail
- Comprehensive error messages for troubleshooting

### Common Issues
1. **Duplicate employee_number**: Employee number already exists
2. **Invalid email format**: Email doesn't match valid format
3. **Invalid manager reference**: Manager employee number doesn't exist
4. **Invalid job template**: Job template title doesn't exist
5. **Invalid date format**: Date not in YYYY-MM-DD format

## Security Features

- HR Admin only access
- CSRF protection on all forms
- File upload validation (size, type, extension)
- SQL injection prevention
- Password hashing (never export plain text passwords)
- Audit logging of all operations
- Secure file handling and cleanup

## Performance Considerations

- 10MB file size limit for imports
- Batch processing for large imports
- Memory-efficient streaming for exports
- Temporary file cleanup after operations
- Transaction support for data integrity

## Privacy and Data Protection

### Export Privacy
- Upward feedback exports only aggregated summaries
- Individual anonymous responses are never exported
- Password hashes are never exported
- Sensitive data is properly handled

### Import Privacy
- Default passwords are set for new users
- Password change required on first login
- User account creation follows security best practices

## Troubleshooting

### Export Issues
- **File not found**: Temporary files are cleaned up after download
- **ZIP creation failed**: Check server permissions and disk space
- **Large exports timeout**: Consider filtering data or increasing server limits

### Import Issues
- **File upload failed**: Check file size (max 10MB) and format (CSV only)
- **Validation errors**: Review CSV format and fix data issues
- **Import timeout**: Break large files into smaller batches

### Common Solutions
1. **Download template** to ensure correct format
2. **Check required fields** are properly filled
3. **Verify manager references** exist in the system
4. **Ensure unique values** for employee_number and email
5. **Use correct date format** (YYYY-MM-DD)

## API Endpoints

### Export Endpoints
- `GET /api/employees/export.php?type=basic` - Basic CSV export
- `GET /api/employees/export.php?type=complete` - Complete ZIP export
- `GET /api/employees/template.php` - Download import template
- `GET /api/employees/download.php?file=filename` - Download generated files

### Import Endpoints
- `POST /api/employees/import.php` - Import CSV file
  - `action=validate` - Validate CSV data
  - `action=import` - Perform actual import

## Integration Notes

- Compatible with existing employee management system
- Maintains referential integrity with all related tables
- Supports job template assignments
- Integrates with user management system
- Preserves audit trail and logging

## Best Practices

1. **Always download template** before creating import files
2. **Validate data** before importing large batches
3. **Backup data** before major imports
4. **Test with small files** first
5. **Review validation results** carefully
6. **Monitor import results** for errors
7. **Keep CSV files** for reference after import
8. **Use meaningful employee numbers** that won't conflict
9. **Verify manager relationships** before import
10. **Coordinate with IT** for large data migrations
