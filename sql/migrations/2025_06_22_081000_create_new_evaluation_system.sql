-- Migration: Create New Job Template-Based Evaluation System
-- Created: 2025-06-22 08:10:00
-- Description: Creates the new evaluation system based on job templates

-- Add job template reference to evaluations table
ALTER TABLE evaluations 
ADD COLUMN job_template_id INT NULL,
ADD CONSTRAINT fk_evaluations_job_template 
FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE SET NULL;

-- Create evaluation KPI results table
CREATE TABLE evaluation_kpi_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    kpi_id INT NOT NULL,
    target_value DECIMAL(10,2),
    achieved_value DECIMAL(10,2),
    score DECIMAL(3,2) CHECK (score BETWEEN 1.00 AND 5.00),
    comments TEXT,
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (kpi_id) REFERENCES company_kpis(id) ON DELETE CASCADE,
    
    INDEX idx_evaluation_kpi (evaluation_id, kpi_id),
    INDEX idx_kpi_score (kpi_id, score),
    UNIQUE KEY unique_evaluation_kpi (evaluation_id, kpi_id)
);

-- Create evaluation competency results table
CREATE TABLE evaluation_competency_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    competency_id INT NOT NULL,
    required_level ENUM('basic', 'intermediate', 'advanced', 'expert') NOT NULL,
    achieved_level ENUM('basic', 'intermediate', 'advanced', 'expert'),
    score DECIMAL(3,2) CHECK (score BETWEEN 1.00 AND 5.00),
    comments TEXT,
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(id) ON DELETE CASCADE,
    
    INDEX idx_evaluation_competency (evaluation_id, competency_id),
    INDEX idx_competency_score (competency_id, score),
    INDEX idx_competency_level (competency_id, achieved_level),
    UNIQUE KEY unique_evaluation_competency (evaluation_id, competency_id)
);

-- Create evaluation responsibility results table
CREATE TABLE evaluation_responsibility_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    responsibility_id INT NOT NULL,
    score DECIMAL(3,2) CHECK (score BETWEEN 1.00 AND 5.00),
    comments TEXT,
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (responsibility_id) REFERENCES job_template_responsibilities(id) ON DELETE CASCADE,
    
    INDEX idx_evaluation_responsibility (evaluation_id, responsibility_id),
    INDEX idx_responsibility_score (responsibility_id, score),
    UNIQUE KEY unique_evaluation_responsibility (evaluation_id, responsibility_id)
);

-- Create evaluation value results table
CREATE TABLE evaluation_value_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    value_id INT NOT NULL,
    score DECIMAL(3,2) CHECK (score BETWEEN 1.00 AND 5.00),
    comments TEXT,
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (value_id) REFERENCES company_values(id) ON DELETE CASCADE,
    
    INDEX idx_evaluation_value (evaluation_id, value_id),
    INDEX idx_value_score (value_id, score),
    UNIQUE KEY unique_evaluation_value (evaluation_id, value_id)
);

-- Create evaluation section weights table for flexible weighting
CREATE TABLE evaluation_section_weights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    section_type ENUM('kpis', 'competencies', 'responsibilities', 'values') NOT NULL,
    weight_percentage DECIMAL(5,2) NOT NULL DEFAULT 25.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    
    INDEX idx_evaluation_section (evaluation_id, section_type),
    UNIQUE KEY unique_evaluation_section (evaluation_id, section_type)
);

-- Add indexes for better performance
CREATE INDEX idx_evaluations_job_template ON evaluations(job_template_id);
CREATE INDEX idx_evaluations_status_template ON evaluations(status, job_template_id);

-- Insert default section weights for existing evaluations (if any)
INSERT INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT 
    evaluation_id,
    'kpis' as section_type,
    40.00 as weight_percentage
FROM evaluations
WHERE evaluation_id NOT IN (
    SELECT evaluation_id FROM evaluation_section_weights WHERE section_type = 'kpis'
);

INSERT INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT 
    evaluation_id,
    'competencies' as section_type,
    25.00 as weight_percentage
FROM evaluations
WHERE evaluation_id NOT IN (
    SELECT evaluation_id FROM evaluation_section_weights WHERE section_type = 'competencies'
);

INSERT INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT 
    evaluation_id,
    'responsibilities' as section_type,
    25.00 as weight_percentage
FROM evaluations
WHERE evaluation_id NOT IN (
    SELECT evaluation_id FROM evaluation_section_weights WHERE section_type = 'responsibilities'
);

INSERT INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT 
    evaluation_id,
    'values' as section_type,
    10.00 as weight_percentage
FROM evaluations
WHERE evaluation_id NOT IN (
    SELECT evaluation_id FROM evaluation_section_weights WHERE section_type = 'values'
);

SELECT 'Migration create_new_evaluation_system completed successfully' as result;