-- Skills Specification System Migration
-- Introduces dedicated technical and soft skill modules for job templates
-- Created: 2025-03-05

USE performance_evaluation;
SET NAMES utf8mb4;
SET @@foreign_key_checks=0;

-- ---------------------------------------------------------------------
-- 1) Competencies table enhancements
-- ---------------------------------------------------------------------

ALTER TABLE competencies
    ADD COLUMN competency_key VARCHAR(100) NULL AFTER description,
    ADD COLUMN symbol VARCHAR(10) DEFAULT '🧩' AFTER competency_key,
    ADD COLUMN max_level INT DEFAULT 5 AFTER symbol,
    ADD COLUMN level_type ENUM('technical_scale', 'soft_skill_scale') DEFAULT 'technical_scale' AFTER max_level,
    ADD UNIQUE KEY uq_competency_key (competency_key);

-- Ensure existing competency categories align with module expectations
ALTER TABLE competency_categories
    ADD COLUMN module_type ENUM('technical', 'soft_skill') DEFAULT 'technical' AFTER category_type,
    ADD COLUMN display_order INT DEFAULT 0 AFTER module_type;

UPDATE competency_categories
SET module_type = category_type
WHERE module_type IS NULL;

-- ---------------------------------------------------------------------
-- 2) Technical skill level reference table
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS technical_skill_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_value INT NOT NULL,
    display_level INT NOT NULL,
    level_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    symbol_pattern VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_technical_level_value (level_value),
    UNIQUE KEY uq_technical_display_level (display_level)
);

INSERT INTO technical_skill_levels (level_value, display_level, level_name, description, symbol_pattern)
VALUES
    (1, 1, 'I know it', 'Knows what it is and has a general idea of how it works.', '🧩⚪️⚪️⚪️⚪️'),
    (3, 2, 'Built in production', 'Can develop with support and detailed instructions.', '🧩🧩⚪️⚪️⚪️'),
    (5, 3, 'Advanced level', 'Self-sufficient with minimal guidance and consistent quality.', '🧩🧩🧩⚪️⚪️'),
    (7, 4, 'Expert level', 'Works with ease, can mentor or supervise others.', '🧩🧩🧩🧩⚪️'),
    (10, 5, 'Mastery', 'Absolute mastery, optimizes and acts as a reference.', '🧩🧩🧩🧩🧩')
ON DUPLICATE KEY UPDATE
    level_name = VALUES(level_name),
    description = VALUES(description),
    symbol_pattern = VALUES(symbol_pattern);

-- ---------------------------------------------------------------------
-- 3) Job template competency assignments (technical & soft skills)
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS job_template_competencies;

CREATE TABLE IF NOT EXISTS job_template_competencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_template_id INT NOT NULL,
    competency_id INT NOT NULL,
    technical_level_id INT NULL,
    soft_skill_level INT NULL,
    weight_percentage DECIMAL(5,2) DEFAULT 100.00,
    module_type ENUM('technical', 'soft_skill') NOT NULL,
    competency_key VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_template_id) REFERENCES job_position_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(id),
    FOREIGN KEY (technical_level_id) REFERENCES technical_skill_levels(id),
    CONSTRAINT chk_job_template_level_type CHECK (
        (module_type = 'technical' AND technical_level_id IS NOT NULL AND soft_skill_level IS NULL) OR
        (module_type = 'soft_skill' AND soft_skill_level IS NOT NULL AND technical_level_id IS NULL)
    )
);

CREATE INDEX idx_jtc_module_type ON job_template_competencies (module_type);
CREATE INDEX idx_jtc_competency_key ON job_template_competencies (competency_key);

-- ---------------------------------------------------------------------
-- 4) Soft skill definition metadata
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS soft_skill_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competency_key VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    definition TEXT,
    description TEXT,
    json_source_path VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_soft_skill_key (competency_key)
);

CREATE TABLE IF NOT EXISTS soft_skill_level_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soft_skill_id INT NOT NULL,
    level_number INT NOT NULL,
    level_title VARCHAR(100) NOT NULL,
    behaviors JSON,
    symbol_pattern VARCHAR(64) NOT NULL,
    meaning ENUM('Basic', 'Intermediate', 'Advanced', 'Expert') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soft_skill_id) REFERENCES soft_skill_definitions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_soft_skill_level (soft_skill_id, level_number)
);

-- ---------------------------------------------------------------------
-- 5) Evaluation competency results restructure
-- ---------------------------------------------------------------------

ALTER TABLE evaluation_competency_results
    DROP COLUMN required_level,
    DROP COLUMN achieved_level,
    ADD COLUMN required_technical_level_id INT NULL AFTER competency_id,
    ADD COLUMN achieved_technical_level_id INT NULL AFTER required_technical_level_id,
    ADD COLUMN required_soft_skill_level INT NULL AFTER achieved_technical_level_id,
    ADD COLUMN achieved_soft_skill_level INT NULL AFTER required_soft_skill_level,
    ADD COLUMN module_type ENUM('technical', 'soft_skill') NOT NULL DEFAULT 'technical' AFTER achieved_soft_skill_level,
    ADD FOREIGN KEY (required_technical_level_id) REFERENCES technical_skill_levels(id),
    ADD FOREIGN KEY (achieved_technical_level_id) REFERENCES technical_skill_levels(id),
    ADD CONSTRAINT chk_evaluation_level_type CHECK (
        (module_type = 'technical' AND required_technical_level_id IS NOT NULL AND achieved_technical_level_id IS NOT NULL AND required_soft_skill_level IS NULL AND achieved_soft_skill_level IS NULL) OR
        (module_type = 'soft_skill' AND required_soft_skill_level IS NOT NULL AND achieved_soft_skill_level IS NOT NULL AND required_technical_level_id IS NULL AND achieved_technical_level_id IS NULL)
    );

CREATE INDEX idx_ecr_module_type ON evaluation_competency_results (module_type);

-- ---------------------------------------------------------------------
-- 6) View for unified template competency display
-- ---------------------------------------------------------------------

DROP VIEW IF EXISTS view_job_template_competencies;

CREATE VIEW view_job_template_competencies AS
SELECT
    jtc.id,
    jtc.job_template_id,
    jtc.competency_id,
    jtc.module_type,
    jtc.weight_percentage,
    jtc.soft_skill_level,
    jtc.technical_level_id,
    jtc.competency_key,
    c.competency_name,
    c.competency_key AS competency_catalog_key,
    c.symbol,
    c.max_level,
    c.level_type,
    cc.category_name,
    cc.module_type AS category_module_type,
    tsl.level_value AS technical_level_value,
    tsl.display_level AS technical_display_level,
    tsl.level_name AS technical_level_name,
    tsl.description AS technical_level_description,
    tsl.symbol_pattern AS technical_symbol_pattern,
    ss.name AS soft_skill_name,
    ss.definition AS soft_skill_definition,
    ss.description AS soft_skill_description,
    ssd.level_title AS soft_skill_level_title,
    ssd.behaviors AS soft_skill_behaviors,
    ssd.symbol_pattern AS soft_skill_symbol_pattern,
    ssd.meaning AS soft_skill_meaning
FROM job_template_competencies jtc
LEFT JOIN competencies c ON jtc.competency_id = c.id
LEFT JOIN competency_categories cc ON c.category_id = cc.id
LEFT JOIN technical_skill_levels tsl ON jtc.technical_level_id = tsl.id
LEFT JOIN soft_skill_definitions ss ON jtc.competency_key = ss.competency_key
LEFT JOIN soft_skill_level_details ssd
       ON ss.id = ssd.soft_skill_id AND jtc.soft_skill_level = ssd.level_number;

SET @@foreign_key_checks=1;
