# Make Commands for Test Data Population

This document describes the convenient Make commands added for test data population in your Performance Evaluation System.

## 🚀 Quick Start

```bash
# Start your development environment
make up

# Populate with test data (with safety confirmation)
make test-data

# View credentials for testing
make test-data-credentials
```

## 📋 Available Commands

### Primary Commands

| Command | Description | Safety |
|---------|-------------|---------|
| `make test-data` | Populate database with comprehensive test data | ✅ Asks for confirmation |
| `make test-data-force` | Populate test data without confirmation | ⚠️ No confirmation |
| `make test-data-check` | Validate prerequisites before running | ✅ Safe (read-only) |

### Information Commands

| Command | Description |
|---------|-------------|
| `make test-data-credentials` | Show all user credentials for testing |
| `make test-data-summary` | Show summary of generated data |
| `make test-data-help` | Show detailed help for test data commands |

### Utility Commands

| Command | Description |
|---------|-------------|
| `make test-data-validate` | Alias for `test-data-check` |
| `make test-data-clean` | Remove generated documentation files |

## 🎯 Typical Workflow

### 1. Initial Setup
```bash
# Start the environment
make up

# Check if everything is ready
make test-data-check

# Populate with test data
make test-data
```

### 2. Development Testing
```bash
# Quick access to credentials
make test-data-credentials

# View data summary
make test-data-summary

# Reset and repopulate when needed
make test-data-force
```

### 3. Clean Reset
```bash
# Reset environment and repopulate
make reset
make test-data-force
```

## 📊 What Gets Created

When you run `make test-data`, you get:

### Users (28 total)
- **1 HR Administrator**: `admin.system` / `admin123`
- **6 Department Managers**: `manager.lastname` / `manager123`
- **21 Employees**: `firstname.lastname` / `employee123`

### Organizational Structure
- **6 Departments**: IT, HR, Sales, Marketing, Finance, Operations
- **8 Job Templates**: CEO, Manager, Developer, Sales Rep, etc.
- **Clear hierarchy**: Managers → Employees reporting structure

### Evaluation Framework
- **18 KPIs** across Sales, Quality, Productivity, Leadership, Innovation
- **14 Competencies** covering Technical, Communication, Leadership
- **5 Company Values**: Integrity, Excellence, Innovation, Collaboration, Customer Focus

### Evaluation Data
- **3 Evaluation Periods**: 2024 Q3 (completed), 2024 Q4 (active), 2025 Q1 (draft)
- **75+ Evaluations** with realistic scores and mixed completion states
- **Complete assessment data** for all evaluation components

## 🔧 Command Details

### `make test-data`
- **Purpose**: Main command for populating test data
- **Safety**: Asks for confirmation before proceeding
- **Output**: Creates `test_data_credentials.txt` and `test_data_summary.txt`
- **Duration**: 1-3 minutes depending on system performance

### `make test-data-force`
- **Purpose**: Same as `test-data` but without confirmation
- **Use case**: Automation, scripts, or when you're sure
- **⚠️ Warning**: Will immediately clear all data except system settings

### `make test-data-check`
- **Purpose**: Validates environment before running population
- **Checks**: Database connectivity, required tables, PHP classes, permissions
- **Safe**: Read-only operation, no data changes

### `make test-data-credentials`
- **Purpose**: Quick access to login credentials
- **Output**: Formatted list of all usernames and passwords
- **Requirement**: Must run `test-data` first to generate the file

## 🛡️ Safety Features

### Confirmation Prompt
```bash
$ make test-data
WARNING: This will clear all existing data except system settings!
Continue? (y/N): 
```

### Prerequisites Check
The commands automatically verify:
- Docker containers are running
- Database is accessible
- Required PHP classes exist
- File permissions are correct

### Data Preservation
- **Cleared**: Users, employees, evaluations, periods, job templates
- **Preserved**: System settings, database structure, Docker configuration

## 🔍 Troubleshooting

### Common Issues

**Command not found**
```bash
make: *** No rule to make target 'test-data'
```
**Solution**: Make sure you're in the project root directory

**Docker not running**
```bash
ERROR: No such service: web
```
**Solution**: Run `make up` first to start containers

**Permission denied**
```bash
Cannot write to scripts/test_data_credentials.txt
```
**Solution**: Check file permissions in scripts directory

**Database connection failed**
```bash
Database connection failed
```
**Solution**: Verify database is running with `make status`

### Debug Commands

```bash
# Check container status
make status

# View logs
make logs

# Access container shell
make shell

# Check database connectivity
make mysql
```

## 📝 Examples

### Complete Setup from Scratch
```bash
# Clone project and setup
git clone <your-repo>
cd <project-directory>

# Initial setup
make install
make up

# Populate with test data
make test-data

# Start testing
make test-data-credentials
```

### Daily Development Workflow
```bash
# Start environment
make up

# Reset data when needed
make test-data-force

# Quick credential lookup
make test-data-credentials

# Check what was created
make test-data-summary
```

### Automation/CI Usage
```bash
# For automated testing (no prompts)
make up
make test-data-force
# Run your tests here
make down
```

## 🎨 Customization

To modify the generated data, edit:
- `scripts/populate_test_data.php` - Main population logic
- Makefile test-data commands - Command behavior
- `scripts/test_populate_script.php` - Validation logic

## 📞 Support

If you encounter issues:
1. Run `make test-data-check` to validate prerequisites
2. Check `make logs` for error messages
3. Use `make shell` to debug inside the container
4. Verify database connectivity with `make mysql`

---

**Happy Testing! 🚀**

These Make commands provide a convenient, safe way to populate your Performance Evaluation System with comprehensive test data.