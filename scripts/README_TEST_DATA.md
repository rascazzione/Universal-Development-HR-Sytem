# Test Data Population Script

This script populates your Performance Evaluation System with comprehensive test data for development and testing purposes.

## 🎯 What It Creates

### Users & Authentication (28 total users)
- **1 HR Administrator**: `admin.system` / `admin123`
- **6 Department Managers**: `manager.lastname` / `manager123`
- **21 Employees**: `firstname.lastname` / `employee123`

### Organizational Structure
- **6 Departments**: IT, HR, Sales, Marketing, Finance, Operations
- **Clear hierarchy**: Managers → Employees reporting structure
- **8 Job Templates**: CEO, Manager, Developer, Sales Rep, HR Specialist, etc.

### Evaluation Framework
- **18 KPIs** across Sales, Quality, Productivity, Leadership, Innovation
- **14 Competencies** covering Technical, Communication, Leadership, Problem Solving
- **5 Company Values**: Integrity, Excellence, Innovation, Collaboration, Customer Focus

### Evaluation Data
- **3 Evaluation Periods**: 2024 Q3 (completed), 2024 Q4 (active), 2025 Q1 (draft)
- **75+ Evaluations** with realistic scores, comments, and mixed completion states
- **Complete KPI results** with target vs achieved values
- **Detailed competency assessments** with skill level evaluations
- **Responsibility ratings** and company values scores

## 🚀 How to Run

### Command Line (Recommended)
```bash
cd /path/to/your/project
php scripts/populate_test_data.php
```

### Web Browser
Navigate to: `http://localhost/your-project/scripts/populate_test_data.php`

## ⚠️ Important Notes

### Before Running
- **Backup your database** if you have important data
- Ensure your database connection is working
- The script will **completely reset** all data except system settings

### What Gets Reset
✅ **Cleared**: Users, employees, evaluations, periods, job templates, KPIs, competencies, values  
❌ **Preserved**: System settings, database structure

### Expected Runtime
- **Small dataset**: 30-60 seconds
- **Complete execution**: 1-3 minutes depending on server performance

## 📊 Generated Data Overview

### Sample User Credentials
```
HR Admin:
- admin.system / admin123

Managers:
- manager.smith / manager123 (IT Department)
- manager.johnson / manager123 (HR Department)
- manager.williams / manager123 (Sales Department)
- manager.brown / manager123 (Marketing Department)
- manager.jones / manager123 (Finance Department)
- manager.garcia / manager123 (Operations Department)

Employees:
- john.doe / employee123
- jane.smith / employee123
- [... and many more]
```

### Organizational Hierarchy Example
```
IT Department (manager.smith)
├── Senior Developer
├── Software Developer  
├── Junior Developer
└── DevOps Engineer

Sales Department (manager.williams)
├── Senior Sales Representative
├── Sales Representative
├── Sales Coordinator
└── Sales Associate
```

### Evaluation Scenarios
- **2024 Q3**: Mostly completed evaluations (historical data)
- **2024 Q4**: Mixed status - approved, reviewed, submitted, draft
- **2025 Q1**: Mostly draft evaluations (future planning)

## 📄 Output Files

After successful execution, you'll find:

### `test_data_credentials.txt`
Complete list of all usernames and passwords organized by role

### `test_data_summary.txt`
Detailed summary of all created data including counts and testing scenarios

## 🧪 Testing Scenarios

### 1. HR Administrator Testing
```
Login: admin.system / admin123
Test: Full system access, user management, reports
```

### 2. Manager Testing
```
Login: manager.smith / manager123
Test: Team management, evaluation creation, department oversight
```

### 3. Employee Testing
```
Login: john.doe / employee123
Test: View personal evaluations, performance history
```

## 🔧 Troubleshooting

### Common Issues

**Database Connection Error**
```
Error: Database connection failed
Solution: Check config/database.php settings
```

**Permission Denied**
```
Error: Cannot write to file
Solution: Ensure web server has write permissions to scripts/ directory
```

**Memory Limit Exceeded**
```
Error: Fatal error: Allowed memory size exhausted
Solution: Increase PHP memory_limit in php.ini
```

**Execution Timeout**
```
Error: Maximum execution time exceeded
Solution: Increase max_execution_time or run via command line
```

### Debug Mode
To enable detailed logging, edit the script and uncomment debug lines:
```php
// Uncomment for detailed logging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
```

## 🔄 Re-running the Script

The script can be run multiple times safely:
- Each run completely resets the test data
- System settings are preserved
- New random data is generated each time
- User credentials remain consistent

## 📋 Data Validation

After running, verify the data:

1. **Login Test**: Try logging in with different user types
2. **Navigation Test**: Check that all pages load correctly
3. **Evaluation Test**: Create a new evaluation to test workflow
4. **Reports Test**: Generate reports to verify data integrity

## 🎨 Customization

To modify the generated data, edit these sections in the script:

- **User names**: `$firstNames` and `$lastNames` arrays
- **Departments**: `$departments` array
- **KPIs**: `createCompanyKPIs()` method
- **Competencies**: `createCompetencies()` method
- **Job templates**: `createJobTemplates()` method

## 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review the generated log output
3. Verify database connectivity and permissions
4. Ensure all required PHP classes are available

---

**Happy Testing! 🚀**

This script provides a complete, realistic dataset for thorough testing of your Performance Evaluation System.