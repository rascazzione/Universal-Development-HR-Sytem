-- Migration: Move competency_type from competencies to competency_categories
-- This script moves the type from individual competencies to their parent categories

USE performance_evaluation;

SET @schema := DATABASE();

-- Step 1: Add category_type column to competency_categories table if missing
SET @add_category_type := (
    SELECT IF(
        EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = @schema 
              AND TABLE_NAME = 'competency_categories' 
              AND COLUMN_NAME = 'category_type'
        ),
        'SELECT 0',
        'ALTER TABLE competency_categories ADD COLUMN category_type ENUM(''technical'', ''soft_skill'') DEFAULT ''technical''' 
    )
);
PREPARE stmt FROM @add_category_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Update category types based on existing competency types (if column exists)
SET @update_category_type := (
    SELECT IF(
        EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = @schema 
              AND TABLE_NAME = 'competency_categories' 
              AND COLUMN_NAME = 'category_type'
        ),
        'UPDATE competency_categories cc SET cc.category_type = (\n            SELECT CASE \n                WHEN COUNT(*) > 0 THEN ''soft_skill''\n                ELSE ''technical''\n            END\n            FROM competencies c\n            WHERE c.category_id = cc.id \n              AND c.competency_type = ''soft_skill''\n              AND c.is_active = 1\n            LIMIT 1\n        )',
        'SELECT 0'
    )
);
PREPARE stmt FROM @update_category_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Set default technical for categories without competencies (if column exists)
SET @default_category_type := (
    SELECT IF(
        EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = @schema 
              AND TABLE_NAME = 'competency_categories' 
              AND COLUMN_NAME = 'category_type'
        ),
        'UPDATE competency_categories SET category_type = ''technical'' WHERE category_type IS NULL',
        'SELECT 0'
    )
);
PREPARE stmt FROM @default_category_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Remove competency_type column from competencies table if it exists
SET @drop_competency_type := (
    SELECT IF(
        EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = @schema 
              AND TABLE_NAME = 'competencies' 
              AND COLUMN_NAME = 'competency_type'
        ),
        'ALTER TABLE competencies DROP COLUMN competency_type',
        'SELECT 0'
    )
);
PREPARE stmt FROM @drop_competency_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Add index for better performance (if missing)
SET @create_category_index := (
    SELECT IF(
        EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = @schema 
              AND TABLE_NAME = 'competency_categories' 
              AND INDEX_NAME = 'idx_category_type'
        ),
        'SELECT 0',
        'CREATE INDEX idx_category_type ON competency_categories(category_type)'
    )
);
PREPARE stmt FROM @create_category_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Update existing categories with appropriate types (if column exists)
SET @set_soft_skill_categories := (
    SELECT IF(
        EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = @schema 
              AND TABLE_NAME = 'competency_categories' 
              AND COLUMN_NAME = 'category_type'
        ),
        'UPDATE competency_categories SET category_type = ''soft_skill'' WHERE category_name IN (''Communication'', ''Teamwork'', ''Leadership'')',
        'SELECT 0'
    )
);
PREPARE stmt FROM @set_soft_skill_categories;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @set_technical_categories := (
    SELECT IF(
        EXISTS (
            SELECT 1 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = @schema 
              AND TABLE_NAME = 'competency_categories' 
              AND COLUMN_NAME = 'category_type'
        ),
        'UPDATE competency_categories SET category_type = ''technical'' WHERE category_name IN (''Technical Skills'', ''Problem Solving'')',
        'SELECT 0'
    )
);
PREPARE stmt FROM @set_technical_categories;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
