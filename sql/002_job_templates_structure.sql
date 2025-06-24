-- Job Position Templates and Related Tables
-- This extends the existing database structure

-- Job Position Templates
CREATE TABLE IF NOT EXISTS job_position_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position_title VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Company KPIs Directory
CREATE TABLE IF NOT EXISTS company_kpis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kpi_name VARCHAR(255) NOT NULL,
    kpi_description TEXT,
    measurement_unit VARCHAR(50), -- %, numbers, currency, etc.
    category VARCHAR(100), -- Sales, Quality, Efficiency, etc.
    target_type ENUM('higher_better', 'lower_better', 'target_range') DEFAULT 'higher_better',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Skills and Competencies Catalog
CREATE TABLE IF NOT EXISTS competency_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT NULL, -- For subcategories
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (parent_id) REFERENCES competency_categories(id)
);

CREATE TABLE IF NOT EXISTS competencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competency_name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    competency_type ENUM('technical', 'soft_skill', 'leadership', 'core') DEFAULT 'technical',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES competency_categories(id)
);

-- Company Values
CREATE TABLE IF NOT EXISTS company_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    value_name VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Job Template - KPI Assignments
CREATE TABLE IF NOT EXISTS job_template_kpis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_template_id INT,
    kpi_id INT,
    target_value DECIMAL(10,2),
    weight_percentage DECIMAL(5,2) DEFAULT 100.00, -- Weight of this KPI in overall score
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (kpi_id) REFERENCES company_kpis(id)
);

-- Job Template - Competency Assignments
CREATE TABLE IF NOT EXISTS job_template_competencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_template_id INT,
    competency_id INT,
    required_level ENUM('basic', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(id)
);

-- Job Template - Key Responsibilities
CREATE TABLE IF NOT EXISTS job_template_responsibilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_template_id INT,
    responsibility_text TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE
);

-- Job Template - Company Values (which values apply to this position)
CREATE TABLE IF NOT EXISTS job_template_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_template_id INT,
    value_id INT,
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (value_id) REFERENCES company_values(id)
);

-- Link employees to job templates
ALTER TABLE employees ADD COLUMN job_template_id INT NULL;
ALTER TABLE employees ADD FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id);

-- Modify evaluations table to reference job template
ALTER TABLE evaluations ADD COLUMN job_template_id INT NULL;
ALTER TABLE evaluations ADD FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id);

-- Evaluation KPI Results
CREATE TABLE IF NOT EXISTS evaluation_kpi_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT,
    kpi_id INT,
    target_value DECIMAL(10,2),
    achieved_value DECIMAL(10,2),
    score DECIMAL(3,2), -- 1-5 scale
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (kpi_id) REFERENCES company_kpis(id)
);

-- Evaluation Competency Results
CREATE TABLE IF NOT EXISTS evaluation_competency_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT,
    competency_id INT,
    required_level ENUM('basic', 'intermediate', 'advanced', 'expert'),
    achieved_level ENUM('basic', 'intermediate', 'advanced', 'expert'),
    score DECIMAL(3,2), -- 1-5 scale
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(id)
);

-- Evaluation Responsibility Results
CREATE TABLE IF NOT EXISTS evaluation_responsibility_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT,
    responsibility_id INT,
    score DECIMAL(3,2), -- 1-5 scale
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (responsibility_id) REFERENCES job_template_responsibilities(id)
);

-- Evaluation Values Results
CREATE TABLE IF NOT EXISTS evaluation_value_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluation_id INT,
    value_id INT,
    score DECIMAL(3,2), -- 1-5 scale
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (value_id) REFERENCES company_values(id)
);

-- Insert some default company values
INSERT INTO company_values (value_name, description, sort_order, created_by) VALUES
('Integrity', 'Acting with honesty and strong moral principles', 1, 1),
('Excellence', 'Striving for the highest quality in everything we do', 2, 1),
('Innovation', 'Embracing creativity and new ideas to drive progress', 3, 1),
('Collaboration', 'Working together effectively to achieve common goals', 4, 1),
('Customer Focus', 'Putting our customers at the center of everything we do', 5, 1);

-- Insert some default competency categories
INSERT INTO competency_categories (category_name, description) VALUES
('Technical Skills', 'Job-specific technical competencies'),
('Communication', 'Verbal and written communication abilities'),
('Leadership', 'Leadership and management capabilities'),
('Problem Solving', 'Analytical and problem-solving skills'),
('Teamwork', 'Collaboration and team-working skills');

-- Insert some default competencies
INSERT INTO competencies (competency_name, description, category_id, competency_type) VALUES
('Project Management', 'Ability to plan, execute and deliver projects', 1, 'technical'),
('Written Communication', 'Clear and effective written communication', 2, 'soft_skill'),
('Verbal Communication', 'Clear and effective verbal communication', 2, 'soft_skill'),
('Team Leadership', 'Ability to lead and motivate teams', 3, 'leadership'),
('Strategic Thinking', 'Ability to think strategically and long-term', 3, 'leadership'),
('Analytical Thinking', 'Ability to analyze complex problems', 4, 'core'),
('Creative Problem Solving', 'Finding innovative solutions to challenges', 4, 'core'),
('Collaboration', 'Working effectively with others', 5, 'soft_skill'),
('Adaptability', 'Ability to adapt to change and new situations', 5, 'core');

-- Create evaluation section weights table for flexible weighting
CREATE TABLE IF NOT EXISTS evaluation_section_weights (
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

-- Insert default section weights for any existing evaluations
INSERT IGNORE INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT
    evaluation_id,
    'kpis' as section_type,
    40.00 as weight_percentage
FROM evaluations;

INSERT IGNORE INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT
    evaluation_id,
    'competencies' as section_type,
    25.00 as weight_percentage
FROM evaluations;

INSERT IGNORE INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT
    evaluation_id,
    'responsibilities' as section_type,
    25.00 as weight_percentage
FROM evaluations;

INSERT IGNORE INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage)
SELECT
    evaluation_id,
    'values' as section_type,
    10.00 as weight_percentage
FROM evaluations;