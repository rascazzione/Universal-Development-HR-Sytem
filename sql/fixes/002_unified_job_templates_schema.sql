-- Unified Job Templates Schema Extension
-- Consolidates job_templates_structure.sql and add_job_template_id_to_employees.sql
-- Fixes duplicate column definitions and ensures proper execution order

USE performance_evaluation;

-- Start transaction for safety
START TRANSACTION;

-- Check if tables already exist before creating
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'job_position_templates';

-- Only create tables if they don't exist
SET @sql = IF(@table_exists = 0, 
    'CREATE TABLE job_position_templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        position_title VARCHAR(255) NOT NULL,
        department VARCHAR(100),
        description TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
        INDEX idx_position_title (position_title),
        INDEX idx_department (department),
        INDEX idx_is_active (is_active)
    )', 
    'SELECT "Table job_position_templates already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Company KPIs Directory
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'company_kpis';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE company_kpis (
        id INT PRIMARY KEY AUTO_INCREMENT,
        kpi_name VARCHAR(255) NOT NULL,
        kpi_description TEXT,
        measurement_unit VARCHAR(50),
        category VARCHAR(100),
        target_type ENUM("higher_better", "lower_better", "target_range") DEFAULT "higher_better",
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
        INDEX idx_kpi_name (kpi_name),
        INDEX idx_category (category),
        INDEX idx_is_active (is_active)
    )',
    'SELECT "Table company_kpis already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Skills and Competencies Catalog
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'competency_categories';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE competency_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(255) NOT NULL,
        description TEXT,
        parent_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (parent_id) REFERENCES competency_categories(id) ON DELETE SET NULL,
        INDEX idx_category_name (category_name),
        INDEX idx_parent_id (parent_id)
    )',
    'SELECT "Table competency_categories already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'competencies';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE competencies (
        id INT PRIMARY KEY AUTO_INCREMENT,
        competency_name VARCHAR(255) NOT NULL,
        description TEXT,
        category_id INT,
        competency_type ENUM("technical", "soft_skill", "leadership", "core") DEFAULT "technical",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (category_id) REFERENCES competency_categories(id) ON DELETE SET NULL,
        INDEX idx_competency_name (competency_name),
        INDEX idx_category_id (category_id),
        INDEX idx_competency_type (competency_type)
    )',
    'SELECT "Table competencies already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Company Values
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'company_values';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE company_values (
        id INT PRIMARY KEY AUTO_INCREMENT,
        value_name VARCHAR(255) NOT NULL,
        description TEXT,
        sort_order INT DEFAULT 0,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
        INDEX idx_value_name (value_name),
        INDEX idx_sort_order (sort_order)
    )',
    'SELECT "Table company_values already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Job Template - KPI Assignments
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'job_template_kpis';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE job_template_kpis (
        id INT PRIMARY KEY AUTO_INCREMENT,
        job_template_id INT,
        kpi_id INT,
        target_value DECIMAL(10,2),
        weight_percentage DECIMAL(5,2) DEFAULT 100.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
        FOREIGN KEY (kpi_id) REFERENCES company_kpis(id) ON DELETE CASCADE,
        INDEX idx_job_template_id (job_template_id),
        INDEX idx_kpi_id (kpi_id)
    )',
    'SELECT "Table job_template_kpis already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Job Template - Competency Assignments
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'job_template_competencies';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE job_template_competencies (
        id INT PRIMARY KEY AUTO_INCREMENT,
        job_template_id INT,
        competency_id INT,
        required_level ENUM("basic", "intermediate", "advanced", "expert") DEFAULT "intermediate",
        weight_percentage DECIMAL(5,2) DEFAULT 100.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
        FOREIGN KEY (competency_id) REFERENCES competencies(id) ON DELETE CASCADE,
        INDEX idx_job_template_id (job_template_id),
        INDEX idx_competency_id (competency_id)
    )',
    'SELECT "Table job_template_competencies already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Job Template - Key Responsibilities
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'job_template_responsibilities';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE job_template_responsibilities (
        id INT PRIMARY KEY AUTO_INCREMENT,
        job_template_id INT,
        responsibility_text TEXT NOT NULL,
        sort_order INT DEFAULT 0,
        weight_percentage DECIMAL(5,2) DEFAULT 100.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
        INDEX idx_job_template_id (job_template_id),
        INDEX idx_sort_order (sort_order)
    )',
    'SELECT "Table job_template_responsibilities already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Job Template - Company Values
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'job_template_values';

SET @sql = IF(@table_exists = 0,
    'CREATE TABLE job_template_values (
        id INT PRIMARY KEY AUTO_INCREMENT,
        job_template_id INT,
        value_id INT,
        weight_percentage DECIMAL(5,2) DEFAULT 100.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
        FOREIGN KEY (value_id) REFERENCES company_values(id) ON DELETE CASCADE,
        INDEX idx_job_template_id (job_template_id),
        INDEX idx_value_id (value_id)
    )',
    'SELECT "Table job_template_values already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add job_template_id to employees table if not exists
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'performance_evaluation' 
AND table_name = 'employees' 
AND column_name = 'job_template_id';

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE employees ADD COLUMN job_template_id INT NULL',
    'SELECT "Column job_template_id already exists in employees table" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint for job_template_id if column was added
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.key_column_usage
WHERE table_schema = 'performance_evaluation'
AND table_name = 'employees'
AND column_name = 'job_template_id'
AND referenced_table_name = 'job_position_templates';

SET @sql = IF(@fk_exists = 0 AND @column_exists >= 0,
    'ALTER TABLE employees 
     ADD CONSTRAINT fk_employees_job_template 
     FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE SET NULL',
    'SELECT "Foreign key constraint already exists for job_template_id" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for job_template_id if not exists
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.statistics
WHERE table_schema = 'performance_evaluation'
AND table_name = 'employees'
AND index_name = 'idx_job_template';

SET @sql = IF(@index_exists = 0,
    'ALTER TABLE employees ADD INDEX idx_job_template (job_template_id)',
    'SELECT "Index idx_job_template already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default company values if table is empty
SET @values_count = 0;
SELECT COUNT(*) INTO @values_count FROM company_values;

SET @sql = IF(@values_count = 0,
    'INSERT INTO company_values (value_name, description, sort_order, created_by) VALUES
     ("Integrity", "Acting with honesty and strong moral principles", 1, 1),
     ("Excellence", "Striving for the highest quality in everything we do", 2, 1),
     ("Innovation", "Embracing creativity and new ideas to drive progress", 3, 1),
     ("Collaboration", "Working together effectively to achieve common goals", 4, 1),
     ("Customer Focus", "Putting our customers at the center of everything we do", 5, 1)',
    'SELECT "Company values already exist" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default competency categories if table is empty
SET @categories_count = 0;
SELECT COUNT(*) INTO @categories_count FROM competency_categories;

SET @sql = IF(@categories_count = 0,
    'INSERT INTO competency_categories (category_name, description) VALUES
     ("Technical Skills", "Job-specific technical competencies"),
     ("Communication", "Verbal and written communication abilities"),
     ("Leadership", "Leadership and management capabilities"),
     ("Problem Solving", "Analytical and problem-solving skills"),
     ("Teamwork", "Collaboration and team-working skills")',
    'SELECT "Competency categories already exist" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default competencies if table is empty
SET @competencies_count = 0;
SELECT COUNT(*) INTO @competencies_count FROM competencies;

SET @sql = IF(@competencies_count = 0,
    'INSERT INTO competencies (competency_name, description, category_id, competency_type) VALUES
     ("Project Management", "Ability to plan, execute and deliver projects", 1, "technical"),
     ("Written Communication", "Clear and effective written communication", 2, "soft_skill"),
     ("Verbal Communication", "Clear and effective verbal communication", 2, "soft_skill"),
     ("Team Leadership", "Ability to lead and motivate teams", 3, "leadership"),
     ("Strategic Thinking", "Ability to think strategically and long-term", 3, "leadership"),
     ("Analytical Thinking", "Ability to analyze complex problems", 4, "core"),
     ("Creative Problem Solving", "Finding innovative solutions to challenges", 4, "core"),
     ("Collaboration", "Working effectively with others", 5, "soft_skill"),
     ("Adaptability", "Ability to adapt to change and new situations", 5, "core")',
    'SELECT "Competencies already exist" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the schema extension
SELECT 
    'Schema Extension Verification' as check_type,
    table_name,
    'Created successfully' as status
FROM information_schema.tables
WHERE table_schema = 'performance_evaluation'
AND table_name IN (
    'job_position_templates', 'company_kpis', 'competency_categories', 
    'competencies', 'company_values', 'job_template_kpis',
    'job_template_competencies', 'job_template_responsibilities', 
    'job_template_values'
)
ORDER BY table_name;

-- Verify job_template_id column in employees table
SELECT 
    'Column Verification' as check_type,
    'employees' as table_name,
    'job_template_id' as column_name,
    IF(COUNT(*) > 0, 'Column exists', 'Column missing') as status
FROM information_schema.columns
WHERE table_schema = 'performance_evaluation'
AND table_name = 'employees'
AND column_name = 'job_template_id';

-- Commit the transaction
COMMIT;

SELECT 'Unified job templates schema extension completed successfully' as result;