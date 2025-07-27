-- Migration: Create Growth Evidence Journal System
-- This replaces the complex job template system with a continuous feedback approach

-- First, drop the old evaluation result tables that are no longer needed
DROP TABLE IF EXISTS evaluation_kpi_results;
DROP TABLE IF EXISTS evaluation_competency_results;
DROP TABLE IF EXISTS evaluation_responsibility_results;
DROP TABLE IF EXISTS evaluation_value_results;
DROP TABLE IF EXISTS evaluation_section_weights;

-- Create the growth evidence entries table
CREATE TABLE growth_evidence_entries (
    entry_id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    manager_id INT NOT NULL,
    content TEXT NOT NULL,
    star_rating TINYINT(1) CHECK (star_rating BETWEEN 1 AND 5),
    dimension ENUM('responsibilities', 'kpis', 'competencies', 'values') NOT NULL,
    entry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
    FOREIGN KEY (manager_id) REFERENCES employees(employee_id),
    INDEX idx_employee_period (employee_id, entry_date),
    INDEX idx_dimension (dimension),
    INDEX idx_manager_date (manager_id, entry_date)
);

-- Create the media attachments table
CREATE TABLE evidence_attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    entry_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video', 'document') NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (entry_id) REFERENCES growth_evidence_entries(entry_id) ON DELETE CASCADE
);

-- Create simplified evaluation results table for evidence-based evaluations
CREATE TABLE evidence_evaluation_results (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    dimension ENUM('responsibilities', 'kpis', 'competencies', 'values') NOT NULL,
    evidence_count INT NOT NULL,
    avg_star_rating DECIMAL(3,2) NOT NULL,
    total_positive_entries INT NOT NULL,
    total_negative_entries INT NOT NULL,
    calculated_score DECIMAL(4,2) NOT NULL,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    UNIQUE KEY unique_eval_dimension (evaluation_id, dimension)
);

-- Simplify the evaluations table by removing complex scoring fields
-- We'll keep the basic structure but remove the manual scoring fields
ALTER TABLE evaluations 
DROP COLUMN IF EXISTS expected_results,
DROP COLUMN IF EXISTS skills_competencies,
DROP COLUMN IF EXISTS key_responsibilities,
DROP COLUMN IF EXISTS living_values;

-- Add fields for evidence-based evaluations
ALTER TABLE evaluations
ADD COLUMN evidence_summary TEXT NULL,
ADD COLUMN evidence_rating DECIMAL(4,2) NULL;

-- Create a table for storing evidence aggregation statistics
CREATE TABLE evidence_aggregations (
    aggregation_id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    period_id INT NOT NULL,
    total_entries INT NOT NULL DEFAULT 0,
    avg_star_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    positive_entries INT NOT NULL DEFAULT 0,
    negative_entries INT NOT NULL DEFAULT 0,
    last_entry_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
    FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id),
    UNIQUE KEY unique_employee_period (employee_id, period_id)
);
