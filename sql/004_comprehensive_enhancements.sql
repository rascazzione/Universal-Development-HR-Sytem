-- Comprehensive Enhancements Database Schema
-- Implements Phase 1 enhancements: Self-assessments, KUDOS, Manager evaluations, OKRs, IDPs
-- Created: 2025-08-24

USE performance_evaluation;

-- NOTE: Some MySQL installations may enforce strict FK checks during DDL; disable temporarily for safe schema changes
SET @@foreign_key_checks=0;

-- ---------------------------------------------------------------------
-- 1) Employee Self-Assessment & Achievement Journal
-- ---------------------------------------------------------------------

-- Employee self-assessments by dimension (stored per employee, per period)
CREATE TABLE IF NOT EXISTS employee_self_assessments (
    self_assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_id INT NULL,
    assessor_user_id INT NULL, -- normally the employee's user_id for self-assessments
    dimension VARCHAR(100) NOT NULL, -- e.g. 'kpis','competencies','values','responsibilities'
    responses JSON NOT NULL, -- structured answers per criterion: { "criterion_id": {"score":X,"comment":".."} }
    overall_score DECIMAL(5,2) NULL,
    status ENUM('draft','submitted','approved','archived') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_esa_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_esa_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE SET NULL,
    CONSTRAINT fk_esa_assessor FOREIGN KEY (assessor_user_id) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_self_employee_period (employee_id, period_id),
    INDEX idx_self_dimension (dimension),
    INDEX idx_self_status (status)
);

-- Achievement journal entries for employees (impact + evidence)
CREATE TABLE IF NOT EXISTS achievement_journal (
    journal_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    impact TEXT, -- qualitative impact description
    measurable_outcome JSON NULL, -- e.g., {"metric":"sales","before":100,"after":150}
    evidence_entries JSON NULL, -- references to growth_evidence_entries or file attachment ids
    visibility ENUM('public','manager_only','private') DEFAULT 'manager_only',
    date_of_achievement DATE NOT NULL,
    created_by INT NOT NULL, -- user_id who logged it (should map to employees.user_id in normal workflows)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_aj_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_aj_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,

    INDEX idx_journal_employee_date (employee_id, date_of_achievement),
    INDEX idx_journal_visibility (visibility)
);

-- ---------------------------------------------------------------------
-- 2) KUDOS Recognition System
-- ---------------------------------------------------------------------

-- Categories for KUDOS recognitions
CREATE TABLE IF NOT EXISTS kudos_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_kc_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_kudos_category_name (name),
    INDEX idx_kudos_category_active (is_active)
);

-- Templates for quick recognition messages
CREATE TABLE IF NOT EXISTS kudos_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    category_id INT NULL,
    created_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_kt_category FOREIGN KEY (category_id) REFERENCES kudos_categories(category_id) ON DELETE SET NULL,
    CONSTRAINT fk_kt_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_kudos_template_active (is_active),
    INDEX idx_kudos_template_category (category_id)
);

-- Main KUDOS recognition entries
CREATE TABLE IF NOT EXISTS kudos_recognitions (
    kudos_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_employee_id INT NOT NULL,
    recipient_employee_id INT NOT NULL,
    category_id INT NULL,
    template_id INT NULL,
    message TEXT NOT NULL,
    points_awarded INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    is_public BOOLEAN DEFAULT TRUE,

    CONSTRAINT fk_kudos_sender FOREIGN KEY (sender_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_kudos_recipient FOREIGN KEY (recipient_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_kudos_category FOREIGN KEY (category_id) REFERENCES kudos_categories(category_id) ON DELETE SET NULL,
    CONSTRAINT fk_kudos_template FOREIGN KEY (template_id) REFERENCES kudos_templates(template_id) ON DELETE SET NULL,

    INDEX idx_kudos_sender (sender_employee_id),
    INDEX idx_kudos_recipient (recipient_employee_id),
    INDEX idx_kudos_category (category_id),
    INDEX idx_kudos_created_at (created_at)
);

-- Reactions to KUDOS (simple social reactions)
CREATE TABLE IF NOT EXISTS kudos_reactions (
    reaction_id INT AUTO_INCREMENT PRIMARY KEY,
    kudos_id INT NOT NULL,
    reacting_employee_id INT NOT NULL,
    reaction_type ENUM('like','celebrate','insightful','support','love') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_kr_kudos FOREIGN KEY (kudos_id) REFERENCES kudos_recognitions(kudos_id) ON DELETE CASCADE,
    CONSTRAINT fk_kr_employee FOREIGN KEY (reacting_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,

    INDEX idx_kudos_reaction_kudos (kudos_id),
    INDEX idx_kudos_reaction_employee (reacting_employee_id)
);

-- Employee KUDOS points summary (gamification)
CREATE TABLE IF NOT EXISTS employee_kudos_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL UNIQUE,
    total_points INT DEFAULT 0,
    monthly_points INT DEFAULT 0,
    last_reset_month DATE NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_ekp_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,

    INDEX idx_kudos_points_total (total_points),
    INDEX idx_kudos_points_monthly (monthly_points)
);

-- ---------------------------------------------------------------------
-- 3) Manager Evaluation & Upward Feedback
-- ---------------------------------------------------------------------

-- Manager evaluation aggregated records
CREATE TABLE IF NOT EXISTS manager_evaluations (
    manager_eval_id INT AUTO_INCREMENT PRIMARY KEY,
    manager_employee_id INT NOT NULL,
    period_id INT NOT NULL,
    evaluator_user_id INT NULL, -- user performing / owning the aggregated evaluation (HR/system)
    summary JSON NULL, -- Aggregated structured feedback and scores
    overall_score DECIMAL(5,2) NULL,
    status ENUM('draft','finalized','archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_me_manager FOREIGN KEY (manager_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_me_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE CASCADE,
    CONSTRAINT fk_me_evaluator FOREIGN KEY (evaluator_user_id) REFERENCES users(user_id) ON DELETE SET NULL,

    UNIQUE KEY unique_manager_period (manager_employee_id, period_id),
    INDEX idx_manager_eval_period (manager_employee_id, period_id),
    INDEX idx_manager_eval_status (status)
);

-- Upward feedback responses (anonymous capable)
CREATE TABLE IF NOT EXISTS upward_feedback_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    manager_employee_id INT NOT NULL,
    period_id INT NULL,
    respondent_hash CHAR(64) NOT NULL, -- salted hash to preserve anonymity and prevent duplicates
    responses JSON NOT NULL, -- structured Q/A responses
    anonymity_level ENUM('anonymous','identified_if_allowed') DEFAULT 'anonymous',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_uf_manager FOREIGN KEY (manager_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_uf_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE SET NULL,

    INDEX idx_upward_manager (manager_employee_id),
    INDEX idx_upward_period (period_id),
    INDEX idx_upward_hash (respondent_hash)
);

-- Anonymous response tracking (secure tokens, limited auditing)
CREATE TABLE IF NOT EXISTS anonymous_response_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    recipient_user_id INT NULL, -- user id that received / processed the anonymous batch (auditing only)
    sent_token CHAR(128) NOT NULL, -- hashed one-time token for moderation access
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,

    CONSTRAINT fk_art_response FOREIGN KEY (response_id) REFERENCES upward_feedback_responses(response_id) ON DELETE CASCADE,
    CONSTRAINT fk_art_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_anonymous_expires (expires_at),
    INDEX idx_anonymous_token (sent_token)
);

-- Manager development action items created as a result of feedback
CREATE TABLE IF NOT EXISTS manager_development_actions (
    action_id INT AUTO_INCREMENT PRIMARY KEY,
    manager_employee_id INT NOT NULL,
    created_by_user_id INT NULL,
    action_text TEXT NOT NULL,
    due_date DATE NULL,
    status ENUM('open','in_progress','completed','cancelled') DEFAULT 'open',
    related_manager_eval_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_mda_manager FOREIGN KEY (manager_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_mda_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_mda_related_eval FOREIGN KEY (related_manager_eval_id) REFERENCES manager_evaluations(manager_eval_id) ON DELETE SET NULL,

    INDEX idx_manager_actions_manager (manager_employee_id),
    INDEX idx_manager_actions_status (status),
    INDEX idx_manager_actions_due (due_date)
);

-- Aggregated manager feedback summary (cached metrics for reports)
CREATE TABLE IF NOT EXISTS manager_feedback_summary (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    manager_employee_id INT NOT NULL,
    period_id INT NULL,
    total_responses INT DEFAULT 0,
    avg_score DECIMAL(5,2) DEFAULT 0.00,
    top_themes JSON NULL,
    last_aggregated_at TIMESTAMP NULL,

    CONSTRAINT fk_mfs_manager FOREIGN KEY (manager_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_mfs_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE SET NULL,

    UNIQUE KEY unique_manager_feedback_period (manager_employee_id, period_id),
    INDEX idx_manager_feedback_avg (avg_score),
    INDEX idx_manager_feedback_responses (total_responses)
);

-- ---------------------------------------------------------------------
-- 4) Enhanced OKR System (enhance performance_goals + new OKR tables)
-- ---------------------------------------------------------------------

-- Add OKR columns to existing performance_goals table (safe ALTERs)
ALTER TABLE performance_goals
    ADD COLUMN okr_objective BOOLEAN DEFAULT FALSE,
    ADD COLUMN okr_key_results JSON NULL,
    ADD COLUMN okr_owner INT NULL,
    ADD COLUMN okr_progress DECIMAL(5,2) DEFAULT 0.00,
    ADD COLUMN okr_confidence ENUM('low','medium','high') DEFAULT 'medium',
    ADD COLUMN okr_cycle ENUM('monthly','quarterly','annual') DEFAULT 'quarterly';

-- Index to speed queries on OKR flags and progress
CREATE INDEX idx_performance_goals_okr ON performance_goals (okr_objective, okr_progress);

-- OKR progress updates history
CREATE TABLE IF NOT EXISTS okr_progress_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    updated_by_user_id INT NULL,
    progress DECIMAL(5,2) NOT NULL, -- 0-100
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_opu_goal FOREIGN KEY (goal_id) REFERENCES performance_goals(goal_id) ON DELETE CASCADE,
    CONSTRAINT fk_opu_user FOREIGN KEY (updated_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_okr_progress_goal (goal_id),
    INDEX idx_okr_progress_user (updated_by_user_id)
);

-- OKR alignments between objectives and contributing goals
CREATE TABLE IF NOT EXISTS okr_alignments (
    alignment_id INT AUTO_INCREMENT PRIMARY KEY,
    objective_goal_id INT NOT NULL, -- goal_id that is the objective
    aligned_goal_id INT NOT NULL,   -- goal_id that aligns / contributes
    alignment_type ENUM('supports','depends_on','blocks','related') DEFAULT 'supports',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_oa_objective FOREIGN KEY (objective_goal_id) REFERENCES performance_goals(goal_id) ON DELETE CASCADE,
    CONSTRAINT fk_oa_aligned FOREIGN KEY (aligned_goal_id) REFERENCES performance_goals(goal_id) ON DELETE CASCADE,

    UNIQUE KEY unique_alignment (objective_goal_id, aligned_goal_id),
    INDEX idx_alignment_objective (objective_goal_id),
    INDEX idx_alignment_aligned (aligned_goal_id)
);

-- Regular OKR check-ins (meetings and notes)
CREATE TABLE IF NOT EXISTS okr_checkins (
    checkin_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    conducted_by_user_id INT NULL,
    checkin_date DATE NOT NULL,
    note TEXT,
    progress DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_oc_goal FOREIGN KEY (goal_id) REFERENCES performance_goals(goal_id) ON DELETE CASCADE,
    CONSTRAINT fk_oc_user FOREIGN KEY (conducted_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_okr_checkin_goal (goal_id, checkin_date),
    INDEX idx_okr_checkin_user (conducted_by_user_id)
);

-- OKR templates for re-usable objective/key result structures
CREATE TABLE IF NOT EXISTS okr_templates (
    okr_template_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    template JSON NOT NULL, -- structure for objective and key results
    created_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_okr_template_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_okr_template_active (is_active)
);

-- OKR scores and final grading
CREATE TABLE IF NOT EXISTS okr_scores (
    okr_score_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    period_id INT NULL,
    final_score DECIMAL(5,2) NOT NULL, -- 0-100 or scaled
    grade ENUM('A','B','C','D','F') NULL,
    graded_by INT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_okr_score_goal FOREIGN KEY (goal_id) REFERENCES performance_goals(goal_id) ON DELETE CASCADE,
    CONSTRAINT fk_okr_score_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE SET NULL,
    CONSTRAINT fk_okr_score_graded_by FOREIGN KEY (graded_by) REFERENCES users(user_id) ON DELETE SET NULL,

    UNIQUE KEY unique_okr_score_goal_period (goal_id, period_id),
    INDEX idx_okr_score_final (final_score)
);

-- ---------------------------------------------------------------------
-- 5) Individual Development Plans (IDPs)
-- ---------------------------------------------------------------------

-- Main IDP records
CREATE TABLE IF NOT EXISTS individual_development_plans (
    idp_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    manager_id INT NULL,
    period_id INT NULL,
    career_goal TEXT NOT NULL,
    target_date DATE NULL,
    status ENUM('draft','active','on_hold','completed','cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_idp_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_idp_manager FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_idp_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE SET NULL,

    INDEX idx_idp_employee (employee_id),
    INDEX idx_idp_status (status)
);

-- Activities under IDPs (training, projects, certifications)
CREATE TABLE IF NOT EXISTS development_activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    idp_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    activity_type ENUM('training','course','project','mentoring','reading','certification','other') DEFAULT 'training',
    provider VARCHAR(255) NULL,
    cost DECIMAL(10,2) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    expected_outcome TEXT NULL,
    related_kpi_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_da_idp FOREIGN KEY (idp_id) REFERENCES individual_development_plans(idp_id) ON DELETE CASCADE,
    CONSTRAINT fk_da_related_kpi FOREIGN KEY (related_kpi_id) REFERENCES company_kpis(id) ON DELETE SET NULL,

    INDEX idx_activity_idp (idp_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_activity_dates (start_date, end_date)
);

-- Progress tracking for development activities
CREATE TABLE IF NOT EXISTS development_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    updated_by_user_id INT NULL,
    progress_percent DECIMAL(5,2) DEFAULT 0.00,
    note TEXT,
    evidence JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_dp_activity FOREIGN KEY (activity_id) REFERENCES development_activities(activity_id) ON DELETE CASCADE,
    CONSTRAINT fk_dp_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_development_progress_activity (activity_id),
    INDEX idx_development_progress_user (updated_by_user_id)
);

-- Mentoring relationships / assignments under development activities
CREATE TABLE IF NOT EXISTS development_mentoring (
    mentoring_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    mentor_employee_id INT NOT NULL,
    mentee_employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    frequency ENUM('weekly','biweekly','monthly','ad_hoc') DEFAULT 'monthly',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_dm_activity FOREIGN KEY (activity_id) REFERENCES development_activities(activity_id) ON DELETE CASCADE,
    CONSTRAINT fk_dm_mentor FOREIGN KEY (mentor_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_dm_mentee FOREIGN KEY (mentee_employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,

    INDEX idx_mentoring_mentor (mentor_employee_id),
    INDEX idx_mentoring_mentee (mentee_employee_id),
    INDEX idx_mentoring_activity (activity_id)
);

-- Development plan templates (career paths)
CREATE TABLE IF NOT EXISTS development_templates (
    dev_template_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    template JSON NOT NULL,
    created_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_dev_template_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_dev_template_active (is_active)
);

-- ROI tracking for development activities to measure business impact
CREATE TABLE IF NOT EXISTS development_roi_tracking (
    roi_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    investment_cost DECIMAL(12,2) DEFAULT 0.00,
    measured_benefit JSON NULL, -- e.g., {"type":"revenue","value":1000,"period":"Q2"}
    calculated_roi DECIMAL(12,4) NULL,
    measurement_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_roi_activity FOREIGN KEY (activity_id) REFERENCES development_activities(activity_id) ON DELETE CASCADE,

    INDEX idx_roi_activity (activity_id),
    INDEX idx_roi_measurement_date (measurement_date)
);

-- ---------------------------------------------------------------------
-- Sample data: categories, templates, defaults
-- ---------------------------------------------------------------------

-- KUDOS default categories
INSERT INTO kudos_categories (name, description, created_by) VALUES
('Team Player', 'Recognize employees who collaborate effectively', 1),
('Customer Champion', 'Recognition for outstanding customer service', 1),
('Innovation', 'Recognize creative solutions and improvements', 1)
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- KUDOS quick templates
INSERT INTO kudos_templates (title, message, category_id, created_by) VALUES
('Great support on the project', 'Thanks for your outstanding help on the X project — your collaboration made a real difference!', (SELECT category_id FROM kudos_categories WHERE name='Team Player' LIMIT 1), 1),
('Excellent customer handling', 'Your handling of customer Y was exemplary — excellent empathy and problem solving.', (SELECT category_id FROM kudos_categories WHERE name='Customer Champion' LIMIT 1), 1),
('Creative solution', 'Appreciate your creative approach to solving Z — inspired the team!', (SELECT category_id FROM kudos_categories WHERE name='Innovation' LIMIT 1), 1)
ON DUPLICATE KEY UPDATE message=VALUES(message);

-- Basic OKR template
INSERT INTO okr_templates (name, description, template, created_by) VALUES
('Default Quarterly OKR', 'Template for quarterly objectives and key results', JSON_ARRAY(JSON_OBJECT('objective','Increase customer satisfaction','key_results', JSON_ARRAY(JSON_OBJECT('kr','Improve NPS','target',10),JSON_OBJECT('kr','Reduce response time','target',20)))), 1)
ON DUPLICATE KEY UPDATE template=VALUES(template);

-- Development templates (career path)
INSERT INTO development_templates (name, description, template, created_by) VALUES
('Manager Career Path', 'Standard development plan for people managers', JSON_OBJECT('steps', JSON_ARRAY(JSON_OBJECT('title','Leadership training','type','course'),JSON_OBJECT('title','Mentoring','type','mentoring'))), 1)
ON DUPLICATE KEY UPDATE template=VALUES(template);

-- Initialize employee_kudos_points rows for existing employees (if not present)
INSERT INTO employee_kudos_points (employee_id, total_points, monthly_points, last_reset_month)
SELECT e.employee_id, 0, 0, NULL FROM employees e
LEFT JOIN employee_kudos_points k ON k.employee_id = e.employee_id
WHERE k.employee_id IS NULL;

-- Ensure foreign key checks are turned back on
SET @@foreign_key_checks=1;

COMMIT;