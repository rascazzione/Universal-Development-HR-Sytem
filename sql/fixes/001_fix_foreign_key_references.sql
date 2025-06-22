-- Fix Foreign Key References
-- Addresses conflicts between core schema (users.user_id) and extended schema (users.id)
-- This script corrects all foreign key references to use the correct column name

USE performance_evaluation;

-- Start transaction for safety
START TRANSACTION;

-- Check if job_position_templates table exists before attempting fixes
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'job_position_templates';

-- Only proceed if the table exists
SET @sql = IF(@table_exists > 0, 
    'SELECT "job_position_templates table found, proceeding with fixes" as status',
    'SELECT "job_position_templates table not found, skipping fixes" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix job_position_templates foreign key if table exists
-- First check if foreign key exists, then drop it
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.key_column_usage
WHERE table_schema = 'performance_evaluation'
AND table_name = 'job_position_templates'
AND constraint_name = 'fk_job_templates_created_by';

SET @sql = IF(@table_exists > 0 AND @fk_exists > 0,
    'ALTER TABLE job_position_templates DROP FOREIGN KEY fk_job_templates_created_by',
    'SELECT "No existing FK to drop for job_position_templates" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@table_exists > 0,
    'ALTER TABLE job_position_templates
     ADD CONSTRAINT fk_job_templates_created_by
     FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL',
    'SELECT "Skipping job_position_templates FK creation" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and fix company_kpis table
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'company_kpis';

-- Check if foreign key exists for company_kpis
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.key_column_usage
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_kpis'
AND constraint_name = 'fk_company_kpis_created_by';

SET @sql = IF(@table_exists > 0 AND @fk_exists > 0,
    'ALTER TABLE company_kpis DROP FOREIGN KEY fk_company_kpis_created_by',
    'SELECT "No existing FK to drop for company_kpis" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@table_exists > 0,
    'ALTER TABLE company_kpis
     ADD CONSTRAINT fk_company_kpis_created_by
     FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL',
    'SELECT "Skipping company_kpis FK creation" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and fix company_values table
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'company_values';

-- Check if foreign key exists for company_values
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.key_column_usage
WHERE table_schema = 'performance_evaluation'
AND table_name = 'company_values'
AND constraint_name = 'fk_company_values_created_by';

SET @sql = IF(@table_exists > 0 AND @fk_exists > 0,
    'ALTER TABLE company_values DROP FOREIGN KEY fk_company_values_created_by',
    'SELECT "No existing FK to drop for company_values" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@table_exists > 0,
    'ALTER TABLE company_values
     ADD CONSTRAINT fk_company_values_created_by
     FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL',
    'SELECT "Skipping company_values FK creation" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify foreign key constraints are properly set
SELECT 
    'Foreign Key Verification' as check_type,
    table_name,
    column_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = 'performance_evaluation'
AND referenced_table_name = 'users'
AND column_name = 'created_by';

-- Commit the transaction
COMMIT;

SELECT 'Foreign key reference fixes completed successfully' as result;