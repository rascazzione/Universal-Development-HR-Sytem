-- Enhanced Schema Validation Script
-- Validates all key tables and columns exist with correct structure

USE performance_evaluation;

-- Start transaction for safety
START TRANSACTION;

-- Validate company_kpis table structure
SELECT 
    'company_kpis validation' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    column_type
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND column_name IN ('id', 'kpi_name', 'kpi_description', 'category', 'measurement_unit', 'target_type', 'created_by', 'is_active')
ORDER BY ordinal_position;

-- Check for any NULL categories and update them
UPDATE company_kpis 
SET category = 'General' 
WHERE category IS NULL OR category = '';

-- Validate that the problematic query now works
SELECT 
    'Query test result' as test_name,
    COUNT(*) as record_count
FROM company_kpis ck
LEFT JOIN users u ON ck.created_by = u.user_id
WHERE ck.is_active = 1
ORDER BY ck.category, ck.kpi_name;

-- Check if there are any records with missing required fields
SELECT 
    'Data validation' as check_type,
    COUNT(*) as records_with_missing_names
FROM company_kpis 
WHERE kpi_name IS NULL OR kpi_name = '';

-- Validate job_position_templates table
SELECT 
    'job_position_templates validation' as table_name,
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'job_position_templates'
AND column_name IN ('id', 'position_title', 'department', 'created_by', 'is_active')
ORDER BY ordinal_position;

-- Validate competencies table
SELECT 
    'competencies validation' as table_name,
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'competencies'
AND column_name IN ('id', 'competency_name', 'description', 'category_id', 'is_active')
ORDER BY ordinal_position;

-- Check foreign key constraints
SELECT 
    'Foreign key validation' as check_type,
    table_name,
    column_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = 'performance_evaluation'
AND referenced_table_name IN ('users', 'company_kpis', 'job_position_templates', 'competencies')
ORDER BY table_name, column_name;

-- Verify all expected tables exist
SELECT 
    'Table existence validation' as check_type,
    table_name
FROM information_schema.tables
WHERE table_schema = 'performance_evaluation'
AND table_name IN ('users', 'employees', 'company_kpis', 'job_position_templates', 'competencies', 'company_values', 'evaluation_periods', 'evaluations')
ORDER BY table_name;

-- Commit the transaction
COMMIT;

SELECT 'Schema validation completed successfully' as result;
