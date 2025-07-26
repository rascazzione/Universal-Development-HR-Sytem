-- Create Departments Table
-- Migration for simplified department management system

USE performance_evaluation;

-- Start transaction for safety
START TRANSACTION;

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_department_name (department_name),
    INDEX idx_is_active (is_active)
);

-- Insert some default departments
INSERT IGNORE INTO departments (department_name, description, created_by) VALUES
('Human Resources', 'Human Resources and Personnel Management', 1),
('Information Technology', 'IT Systems and Infrastructure', 1),
('Finance', 'Financial Management and Accounting', 1),
('Operations', 'Day-to-day Business Operations', 1),
('Marketing', 'Marketing and Brand Management', 1),
('Sales', 'Sales and Customer Relations', 1);

-- Commit the transaction
COMMIT;

SELECT 'Departments table created successfully' as result;
