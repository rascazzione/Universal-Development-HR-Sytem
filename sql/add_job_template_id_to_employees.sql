-- Add job_template_id column to employees table
-- This script should be executed to enable proper employee counting for job templates

USE performance_evaluation;

-- Add the job_template_id column to employees table
ALTER TABLE employees ADD COLUMN job_template_id INT NULL;

-- Add foreign key constraint
ALTER TABLE employees ADD FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id);

-- Add index for better performance
ALTER TABLE employees ADD INDEX idx_job_template (job_template_id);

-- Verify the column was added
DESCRIBE employees;