-- Migration: Move competency_type from competencies to competency_categories
-- This script moves the type from individual competencies to their parent categories

-- Step 1: Add category_type column to competency_categories table
ALTER TABLE competency_categories 
ADD COLUMN category_type ENUM('technical', 'soft_skill') DEFAULT 'technical';

-- Step 2: Update category types based on existing competency types
UPDATE competency_categories cc
SET cc.category_type = (
    SELECT CASE 
        WHEN COUNT(*) > 0 THEN 'soft_skill'
        ELSE 'technical'
    END
    FROM competencies c
    WHERE c.category_id = cc.id 
    AND c.competency_type = 'soft_skill'
    AND c.is_active = 1
    LIMIT 1
);

-- Set default technical for categories without competencies
UPDATE competency_categories 
SET category_type = 'technical' 
WHERE category_type IS NULL;

-- Step 3: Remove competency_type column from competencies table
ALTER TABLE competencies 
DROP COLUMN competency_type;

-- Step 4: Add index for better performance
CREATE INDEX idx_category_type ON competency_categories(category_type);

-- Step 5: Update existing categories with appropriate types
UPDATE competency_categories 
SET category_type = 'soft_skill' 
WHERE category_name IN ('Communication', 'Teamwork', 'Leadership');

UPDATE competency_categories 
SET category_type = 'technical' 
WHERE category_name IN ('Technical Skills', 'Problem Solving');

COMMIT;