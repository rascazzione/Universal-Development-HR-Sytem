-- PHP Performance Evaluation System Database Setup
-- Created: 2025-06-18

-- Create database
CREATE DATABASE IF NOT EXISTS performance_evaluation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE performance_evaluation;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('hr_admin', 'manager', 'employee') NOT NULL DEFAULT 'employee',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Employees table
CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    employee_number VARCHAR(20) UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    position VARCHAR(100),
    department VARCHAR(100),
    manager_id INT,
    hire_date DATE,
    phone VARCHAR(20),
    address TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    INDEX idx_employee_number (employee_number),
    INDEX idx_manager (manager_id),
    INDEX idx_department (department),
    INDEX idx_active (active)
);

-- Evaluation periods table
CREATE TABLE evaluation_periods (
    period_id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(100) NOT NULL,
    period_type ENUM('monthly', 'quarterly', 'semi_annual', 'annual', 'custom') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_period_dates (start_date, end_date),
    INDEX idx_status (status),
    INDEX idx_period_type (period_type)
);

-- Evaluations table
CREATE TABLE evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    period_id INT NOT NULL,
    
    -- Expected Results (JSON structure for flexibility)
    expected_results JSON,
    expected_results_score DECIMAL(5,2),
    expected_results_weight DECIMAL(5,2) DEFAULT 40.00,
    
    -- Skills, Knowledge, and Competencies
    skills_competencies JSON,
    skills_competencies_score DECIMAL(5,2),
    skills_competencies_weight DECIMAL(5,2) DEFAULT 25.00,
    
    -- Key Responsibilities
    key_responsibilities JSON,
    key_responsibilities_score DECIMAL(5,2),
    key_responsibilities_weight DECIMAL(5,2) DEFAULT 25.00,
    
    -- Living Our Values
    living_values JSON,
    living_values_score DECIMAL(5,2),
    living_values_weight DECIMAL(5,2) DEFAULT 10.00,
    
    -- Overall evaluation
    overall_rating DECIMAL(3,2) CHECK (overall_rating BETWEEN 1.00 AND 5.00),
    overall_comments TEXT,
    
    -- Goals and development
    goals_next_period TEXT,
    development_areas TEXT,
    strengths TEXT,
    
    -- Status and workflow
    status ENUM('draft', 'submitted', 'reviewed', 'approved', 'rejected') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE CASCADE,
    
    INDEX idx_employee (employee_id),
    INDEX idx_evaluator (evaluator_id),
    INDEX idx_period (period_id),
    INDEX idx_status (status),
    INDEX idx_overall_rating (overall_rating),
    
    UNIQUE KEY unique_employee_period (employee_id, period_id)
);

-- Evaluation comments table (for detailed feedback)
CREATE TABLE evaluation_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    section VARCHAR(50) NOT NULL, -- 'expected_results', 'skills', 'responsibilities', 'values', 'general'
    criterion VARCHAR(100),
    comment TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_evaluation (evaluation_id),
    INDEX idx_section (section)
);

-- System settings table
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Audit log table
CREATE TABLE audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_table (table_name),
    INDEX idx_created_at (created_at)
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('system_name', 'Performance Evaluation System', 'Application name'),
('company_name', 'Your Company Name', 'Company name for reports'),
('evaluation_scale_min', '1', 'Minimum evaluation score'),
('evaluation_scale_max', '5', 'Maximum evaluation score'),
('auto_save_interval', '30', 'Auto-save interval in seconds'),
('session_timeout', '3600', 'Session timeout in seconds'),
('password_min_length', '8', 'Minimum password length'),
('require_password_complexity', '1', 'Require complex passwords (1=yes, 0=no)');

-- Create default admin user (password: admin123 - CHANGE THIS!)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr_admin');

-- Create sample employee record for admin
INSERT INTO employees (user_id, employee_number, first_name, last_name, position, department, hire_date) VALUES
(1, 'EMP001', 'System', 'Administrator', 'HR Administrator', 'Human Resources', CURDATE());

-- Create indexes for better performance
CREATE INDEX idx_evaluations_composite ON evaluations(employee_id, period_id, status);
CREATE INDEX idx_employees_manager_dept ON employees(manager_id, department);
CREATE INDEX idx_users_role_active ON users(role, is_active);

COMMIT;