-- Schema Validation Script
-- Checks for common issues and reports schema health

USE performance_evaluation;

-- Check for missing foreign key constraints
SELECT 
    'Missing Foreign Keys' as check_type,
    table_name,
    column_name,
    'Missing FK constraint' as issue
FROM information_schema.columns c
WHERE table_schema = 'performance_evaluation'
AND column_name LIKE '%_id'
AND column_name NOT IN ('user_id', 'employee_id', 'evaluation_id', 'period_id', 'comment_id', 'setting_id', 'log_id')
AND NOT EXISTS (
    SELECT 1 FROM information_schema.key_column_usage k
    WHERE k.table_schema = c.table_schema
    AND k.table_name = c.table_name
    AND k.column_name = c.column_name
    AND k.referenced_table_name IS NOT NULL
);

-- Check for orphaned records in employees table
SELECT 
    'Orphaned Records' as check_type,
    'employees' as table_name,
    COUNT(*) as count,
    'Employees without valid user_id' as issue
FROM employees e
LEFT JOIN users u ON e.user_id = u.user_id
WHERE e.user_id IS NOT NULL AND u.user_id IS NULL;

-- Check for foreign key reference consistency
SELECT 
    'Foreign Key Consistency' as check_type,
    kcu.table_name,
    kcu.column_name,
    kcu.referenced_table_name,
    kcu.referenced_column_name,
    CASE 
        WHEN kcu.referenced_table_name = 'users' AND kcu.referenced_column_name != 'user_id' 
        THEN 'Incorrect reference - should be user_id'
        ELSE 'OK'
    END as status
FROM information_schema.key_column_usage kcu
WHERE kcu.table_schema = 'performance_evaluation'
AND kcu.referenced_table_name IS NOT NULL
ORDER BY kcu.table_name, kcu.column_name;

-- Check for duplicate column definitions across tables
SELECT 
    'Duplicate Columns Check' as check_type,
    'employees' as table_name,
    column_name,
    COUNT(*) as occurrences,
    CASE 
        WHEN COUNT(*) > 1 THEN 'Duplicate column definition'
        ELSE 'OK'
    END as status
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'employees'
AND column_name = 'job_template_id'
GROUP BY column_name;

-- Check table existence for job templates system
SELECT 
    'Table Existence Check' as check_type,
    expected_table,
    CASE 
        WHEN actual_table IS NOT NULL THEN 'EXISTS'
        ELSE 'MISSING'
    END as status
FROM (
    SELECT 'job_position_templates' as expected_table
    UNION SELECT 'company_kpis'
    UNION SELECT 'competency_categories'
    UNION SELECT 'competencies'
    UNION SELECT 'company_values'
    UNION SELECT 'job_template_kpis'
    UNION SELECT 'job_template_competencies'
    UNION SELECT 'job_template_responsibilities'
    UNION SELECT 'job_template_values'
) expected
LEFT JOIN (
    SELECT table_name as actual_table
    FROM information_schema.tables
    WHERE table_schema = 'performance_evaluation'
) actual ON expected.expected_table = actual.actual_table;

-- Check for proper indexes on foreign key columns
SELECT 
    'Index Check' as check_type,
    kcu.table_name,
    kcu.column_name,
    CASE 
        WHEN s.index_name IS NOT NULL THEN 'INDEXED'
        ELSE 'MISSING INDEX'
    END as status
FROM information_schema.key_column_usage kcu
LEFT JOIN information_schema.statistics s 
    ON kcu.table_schema = s.table_schema 
    AND kcu.table_name = s.table_name 
    AND kcu.column_name = s.column_name
    AND s.index_name != 'PRIMARY'
WHERE kcu.table_schema = 'performance_evaluation'
AND kcu.referenced_table_name IS NOT NULL
ORDER BY kcu.table_name, kcu.column_name;

-- Check data integrity for default values
SELECT 
    'Data Integrity Check' as check_type,
    'company_values' as table_name,
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) >= 5 THEN 'Default values present'
        WHEN COUNT(*) > 0 THEN 'Partial default values'
        ELSE 'No default values'
    END as status
FROM company_values
WHERE is_active = TRUE;

SELECT 
    'Data Integrity Check' as check_type,
    'competency_categories' as table_name,
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) >= 5 THEN 'Default categories present'
        WHEN COUNT(*) > 0 THEN 'Partial default categories'
        ELSE 'No default categories'
    END as status
FROM competency_categories
WHERE is_active = TRUE;

SELECT 
    'Data Integrity Check' as check_type,
    'competencies' as table_name,
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) >= 9 THEN 'Default competencies present'
        WHEN COUNT(*) > 0 THEN 'Partial default competencies'
        ELSE 'No default competencies'
    END as status
FROM competencies
WHERE is_active = TRUE;

-- Summary report
SELECT 
    'Schema Health Summary' as check_type,
    'Overall Status' as component,
    CASE 
        WHEN (
            SELECT COUNT(*) FROM information_schema.tables 
            WHERE table_schema = 'performance_evaluation' 
            AND table_name IN ('job_position_templates', 'company_kpis', 'competencies')
        ) = 3 THEN 'HEALTHY'
        ELSE 'NEEDS ATTENTION'
    END as status;