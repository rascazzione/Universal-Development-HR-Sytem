#!/bin/bash

# Performance Evaluation System Migration Script
# Executes the complete migration from old JSON-based system to job templates

set -e  # Exit on any error

# Configuration
DB_HOST="localhost"
DB_NAME="performance_evaluation"
DB_USER="root"
DB_PASS=""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to execute SQL file
execute_sql_file() {
    local file=$1
    local description=$2
    
    print_status "Executing: $description"
    
    if [ ! -f "$file" ]; then
        print_error "SQL file not found: $file"
        exit 1
    fi
    
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$file"; then
        print_success "$description completed successfully"
    else
        print_error "Failed to execute: $description"
        exit 1
    fi
}

# Function to check database connection
check_database_connection() {
    print_status "Checking database connection..."
    
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
        print_success "Database connection successful"
    else
        print_error "Cannot connect to database. Please check your credentials."
        exit 1
    fi
}

# Function to backup database
backup_database() {
    local backup_file="backup_$(date +%Y%m%d_%H%M%S).sql"
    
    print_status "Creating database backup: $backup_file"
    
    if mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$backup_file"; then
        print_success "Database backup created: $backup_file"
    else
        print_error "Failed to create database backup"
        exit 1
    fi
}

# Function to verify migration
verify_migration() {
    print_status "Verifying migration..."
    
    # Check if new tables exist
    local tables=("job_position_templates" "company_kpis" "competencies" "company_values" 
                  "evaluation_kpi_results" "evaluation_competency_results" 
                  "evaluation_responsibility_results" "evaluation_value_results")
    
    for table in "${tables[@]}"; do
        if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE $table;" >/dev/null 2>&1; then
            print_success "Table $table exists"
        else
            print_error "Table $table does not exist"
            exit 1
        fi
    done
    
    # Check if old JSON fields are removed
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE evaluations;" | grep -q "expected_results"; then
        print_error "Old JSON fields still exist in evaluations table"
        exit 1
    else
        print_success "Old JSON fields successfully removed"
    fi
    
    print_success "Migration verification completed successfully"
}

# Main execution
main() {
    echo "=================================================="
    echo "Performance Evaluation System Migration"
    echo "From JSON-based to Job Templates System"
    echo "=================================================="
    echo
    
    # Check if we're in the right directory
    if [ ! -f "sql/migrations/2025_06_22_080000_remove_old_evaluation_fields.sql" ]; then
        print_error "Please run this script from the project root directory"
        exit 1
    fi
    
    # Step 1: Check database connection
    check_database_connection
    
    # Step 2: Create backup
    print_warning "This migration will permanently modify your database structure."
    read -p "Do you want to create a backup before proceeding? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        backup_database
    fi
    
    # Step 3: Confirm migration
    echo
    print_warning "This will execute the following changes:"
    echo "  1. Apply job templates schema (if not already applied)"
    echo "  2. Remove old JSON-based evaluation fields"
    echo "  3. Create new job template-based evaluation system"
    echo
    read -p "Are you sure you want to proceed? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Migration cancelled by user"
        exit 0
    fi
    
    echo
    print_status "Starting migration process..."
    
    # Step 4: Apply job templates schema (if needed)
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE job_position_templates;" >/dev/null 2>&1; then
        print_success "Job templates schema already exists"
    else
        execute_sql_file "sql/fixes/002_unified_job_templates_schema.sql" "Job Templates Schema Setup"
    fi
    
    # Step 5: Remove old evaluation fields
    execute_sql_file "sql/migrations/2025_06_22_080000_remove_old_evaluation_fields.sql" "Remove Old Evaluation Fields"
    
    # Step 6: Create new evaluation system
    execute_sql_file "sql/migrations/2025_06_22_081000_create_new_evaluation_system.sql" "Create New Evaluation System"
    
    # Step 7: Verify migration
    verify_migration
    
    echo
    echo "=================================================="
    print_success "MIGRATION COMPLETED SUCCESSFULLY!"
    echo "=================================================="
    echo
    print_status "Next steps:"
    echo "  1. Test the new evaluation system"
    echo "  2. Create job templates in the admin interface"
    echo "  3. Assign job templates to employees"
    echo "  4. Start creating template-based evaluations"
    echo
    print_status "Access the system at: http://localhost:8080"
    print_status "Admin interface: http://localhost:8080/admin/job_templates.php"
    echo
}

# Execute main function
main "$@"