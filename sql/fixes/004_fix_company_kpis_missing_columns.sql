-- Fix Missing Columns in company_kpis Table
-- Adds missing columns that are referenced in CompanyKPI.php but not present in the database

USE performance_evaluation;

-- Start transaction for safety
START TRANSACTION;

-- Check if company_kpis table exists
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'company_kpis';

-- Only proceed if the table exists
SET @sql = IF(@table_exists > 0, 
    'SELECT "company_kpis table found, proceeding with column additions" as status',
    'SELECT "company_kpis table not found, skipping column additions" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add missing category column (main issue causing the error)
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND column_name = 'category';

SET @sql = IF(@table_exists > 0 AND @column_exists = 0,
    'ALTER TABLE company_kpis ADD COLUMN category VARCHAR(100) AFTER kpi_description',
    'SELECT "category column already exists or table not found" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records to have a default category
SET @sql = IF(@table_exists > 0 AND @column_exists = 0,
    'UPDATE company_kpis SET category = "General" WHERE category IS NULL',
    'SELECT "Skipping category update - column already existed" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add missing measurement_unit column
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND column_name = 'measurement_unit';

SET @sql = IF(@table_exists > 0 AND @column_exists = 0,
    'ALTER TABLE company_kpis ADD COLUMN measurement_unit VARCHAR(50) AFTER category',
    'SELECT "measurement_unit column already exists or table not found" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add missing target_type column
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND column_name = 'target_type';

SET @sql = IF(@table_exists > 0 AND @column_exists = 0,
    'ALTER TABLE company_kpis ADD COLUMN target_type ENUM(''higher_better'', ''lower_better'', ''target_range'') DEFAULT ''higher_better'' AFTER measurement_unit',
    'SELECT "target_type column already exists or table not found" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add missing is_active column
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND column_name = 'is_active';

SET @sql = IF(@table_exists > 0 AND @column_exists = 0,
    'ALTER TABLE company_kpis ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER target_type',
    'SELECT "is_active column already exists or table not found" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.statistics
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND index_name = 'idx_category';

SET @sql = IF(@table_exists > 0 AND @index_exists = 0,
    'CREATE INDEX idx_category ON company_kpis (category)',
    'SELECT "idx_category already exists or table not found" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.statistics
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND index_name = 'idx_is_active';

SET @sql = IF(@table_exists > 0 AND @index_exists = 0,
    'CREATE INDEX idx_is_active ON company_kpis (is_active)',
    'SELECT "idx_is_active already exists or table not found" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the table structure after changes
SELECT 
    'Column Verification' as check_type,
    column_name,
    data_type,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND column_name IN ('category', 'measurement_unit', 'target_type', 'is_active')
ORDER BY ordinal_position;

-- Test the problematic query to ensure it now works
SET @sql = IF(@table_exists > 0,
    'SELECT * FROM company_kpis ck LEFT JOIN users u ON ck.created_by = u.user_id WHERE ck.is_active = 1 ORDER BY ck.category, ck.kpi_name LIMIT 1',
    'SELECT "Skipping query test - table not found" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Commit the transaction
COMMIT;

SELECT 'Company KPIs table column fixes completed successfully' as result;
