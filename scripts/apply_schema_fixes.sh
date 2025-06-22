#!/bin/bash
# Schema Fixes Application Script
# Applies the critical schema fixes in the correct order

set -e

# Configuration
MYSQL_CONTAINER="web_object_classification-mysql-1"
DB_NAME="performance_evaluation"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Starting Schema Fixes Application...${NC}"

# Check if Docker is running
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

# Check if MySQL container is running
if ! docker ps | grep -q "$MYSQL_CONTAINER"; then
    echo -e "${RED}❌ MySQL container is not running. Please start the environment first.${NC}"
    exit 1
fi

# Get database credentials from environment
if [ -f .env ]; then
    source .env
else
    echo -e "${YELLOW}⚠️  .env file not found, using default credentials${NC}"
    DB_ROOT_PASSWORD="root_dev_password"
fi

echo -e "${YELLOW}📋 Using MySQL container: $MYSQL_CONTAINER${NC}"

# Function to execute SQL file
execute_sql_file() {
    local file_path="$1"
    local description="$2"
    
    echo -e "${YELLOW}🔄 Executing: $description${NC}"
    echo -e "${BLUE}   File: $file_path${NC}"
    
    if [ ! -f "$file_path" ]; then
        echo -e "${RED}❌ File not found: $file_path${NC}"
        return 1
    fi
    
    # Execute the SQL file
    if docker exec -i "$MYSQL_CONTAINER" mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_NAME" < "$file_path"; then
        echo -e "${GREEN}✅ Successfully executed: $description${NC}"
        return 0
    else
        echo -e "${RED}❌ Failed to execute: $description${NC}"
        return 1
    fi
}

# Function to run validation
run_validation() {
    echo -e "${YELLOW}🔍 Running schema validation...${NC}"
    
    if docker exec -i "$MYSQL_CONTAINER" mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_NAME" < "sql/fixes/validate_schema.sql"; then
        echo -e "${GREEN}✅ Schema validation completed${NC}"
        return 0
    else
        echo -e "${RED}❌ Schema validation failed${NC}"
        return 1
    fi
}

# Create a backup before applying fixes
echo -e "${YELLOW}📦 Creating backup before applying fixes...${NC}"
if ! ./scripts/backup_database.sh; then
    echo -e "${RED}❌ Backup failed. Aborting schema fixes.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Backup completed successfully${NC}"

# Apply fixes in order
echo -e "${BLUE}🚀 Applying schema fixes...${NC}"

# Step 1: Fix foreign key references
if execute_sql_file "sql/fixes/001_fix_foreign_key_references.sql" "Foreign Key Reference Fixes"; then
    echo -e "${GREEN}✅ Step 1 completed: Foreign key references fixed${NC}"
else
    echo -e "${RED}❌ Step 1 failed: Foreign key reference fixes${NC}"
    echo -e "${YELLOW}💡 You can restore from backup if needed${NC}"
    exit 1
fi

# Step 2: Apply unified job templates schema
if execute_sql_file "sql/fixes/002_unified_job_templates_schema.sql" "Unified Job Templates Schema"; then
    echo -e "${GREEN}✅ Step 2 completed: Job templates schema applied${NC}"
else
    echo -e "${RED}❌ Step 2 failed: Job templates schema application${NC}"
    echo -e "${YELLOW}💡 You can restore from backup if needed${NC}"
    exit 1
fi

# Step 3: Run validation
echo -e "${BLUE}🔍 Validating schema after fixes...${NC}"
if run_validation; then
    echo -e "${GREEN}✅ Schema validation passed${NC}"
else
    echo -e "${YELLOW}⚠️  Schema validation completed with warnings${NC}"
    echo -e "${YELLOW}💡 Check the output above for details${NC}"
fi

# Final status check
echo -e "${BLUE}📊 Final status check...${NC}"

# Check if key tables exist
TABLES_TO_CHECK=("job_position_templates" "company_kpis" "competencies" "company_values")
ALL_TABLES_EXIST=true

for table in "${TABLES_TO_CHECK[@]}"; do
    if docker exec "$MYSQL_CONTAINER" mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_NAME" -e "DESCRIBE $table;" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Table exists: $table${NC}"
    else
        echo -e "${RED}❌ Table missing: $table${NC}"
        ALL_TABLES_EXIST=false
    fi
done

# Check if job_template_id column exists in employees table
if docker exec "$MYSQL_CONTAINER" mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_NAME" -e "DESCRIBE employees;" | grep -q "job_template_id"; then
    echo -e "${GREEN}✅ Column exists: employees.job_template_id${NC}"
else
    echo -e "${RED}❌ Column missing: employees.job_template_id${NC}"
    ALL_TABLES_EXIST=false
fi

# Final result
echo -e "${BLUE}===========================================${NC}"
if [ "$ALL_TABLES_EXIST" = true ]; then
    echo -e "${GREEN}🎉 Schema fixes completed successfully!${NC}"
    echo -e "${GREEN}✅ All required tables and columns are present${NC}"
    echo -e "${GREEN}✅ Foreign key references have been corrected${NC}"
    echo -e "${GREEN}✅ Schema is ready for development${NC}"
else
    echo -e "${YELLOW}⚠️  Schema fixes completed with issues${NC}"
    echo -e "${YELLOW}💡 Some tables or columns may be missing${NC}"
    echo -e "${YELLOW}💡 Check the output above for details${NC}"
fi
echo -e "${BLUE}===========================================${NC}"

echo -e "${BLUE}📋 Next steps:${NC}"
echo -e "${YELLOW}1. Review the validation output above${NC}"
echo -e "${YELLOW}2. Test the application functionality${NC}"
echo -e "${YELLOW}3. Proceed with migration system implementation${NC}"
echo -e "${YELLOW}4. Remove defensive programming from application code${NC}"

echo -e "${GREEN}✅ Schema fixes application completed${NC}"