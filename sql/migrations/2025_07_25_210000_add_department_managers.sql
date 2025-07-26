-- Add Department Managers
-- Migration to add manager assignment functionality to departments

USE performance_evaluation;

-- Start transaction for safety
START TRANSACTION;

-- Add manager_id column to departments table
ALTER TABLE departments 
ADD COLUMN manager_id INT NULL AFTER description,
ADD INDEX idx_manager_id (manager_id),
ADD FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON DELETE SET NULL;

-- Commit the transaction
COMMIT;

SELECT 'Department manager column added successfully' as result;
