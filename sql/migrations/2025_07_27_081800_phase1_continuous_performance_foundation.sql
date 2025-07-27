-- =====================================================
-- PHASE 1: Continuous Performance Foundation Migration
-- =====================================================
-- Creates 1:1 session infrastructure and real-time feedback capture
-- This is the core foundation for transforming from event-based to continuous performance management
-- 
-- Migration: 2025_07_27_081800_phase1_continuous_performance_foundation
-- Dependencies: 001_database_setup.sql, 002_job_templates_structure.sql
-- 
-- CRITICAL: This migration enables the "continuous feedback loop" that eliminates
-- the "surprise factor" in performance reviews by capturing all feedback in real-time
-- =====================================================

USE performance_evaluation;

-- Start transaction for atomic migration
START TRANSACTION;

-- =====================================================
-- 1. ONE-TO-ONE SESSIONS INFRASTRUCTURE
-- =====================================================
-- This table is the CORE of continuous performance management
-- Every piece of feedback, development discussion, and goal progress
-- should be linked to a 1:1 session for evidence-based reviews

CREATE TABLE one_to_one_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    manager_id INT NOT NULL,
    
    -- Scheduling and timing
    scheduled_date DATETIME NOT NULL,
    actual_date DATETIME NULL,
    duration_minutes INT NOT NULL DEFAULT 30,
    
    -- Session status tracking
    status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled', 'no_show') DEFAULT 'scheduled',
    
    -- Session content (structured for evidence capture)
    meeting_notes TEXT NULL COMMENT 'Free-form notes from the session',
    agenda_items JSON NULL COMMENT 'Structured agenda: [{"topic": "Goal Progress", "time_allocated": 10}]',
    action_items JSON NULL COMMENT 'Action items: [{"item": "Complete training", "owner": "employee", "due_date": "2025-08-15"}]',
    
    -- Follow-up tracking
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_notes TEXT NULL,
    next_session_scheduled DATETIME NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    
    -- Performance indexes for high-volume queries
    INDEX idx_employee_manager (employee_id, manager_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_actual_date (actual_date),
    INDEX idx_status_date (status, actual_date),
    INDEX idx_employee_date_range (employee_id, actual_date, status),
    
    -- Ensure data integrity
    CONSTRAINT chk_session_dates CHECK (actual_date IS NULL OR actual_date >= scheduled_date - INTERVAL 7 DAY),
    CONSTRAINT chk_duration CHECK (duration_minutes > 0 AND duration_minutes <= 480)
) ENGINE=InnoDB COMMENT='Core 1:1 session tracking for continuous performance management';

-- =====================================================
-- 2. REAL-TIME FEEDBACK CAPTURE SYSTEM
-- =====================================================
-- This table captures ALL feedback given during 1:1 sessions
-- Links directly to competencies, KPIs, responsibilities, and values
-- Enables evidence-based reviews and bias reduction

CREATE TABLE one_to_one_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    
    -- Feedback participants
    given_by INT NOT NULL COMMENT 'user_id of feedback giver',
    receiver_id INT NOT NULL COMMENT 'employee_id of feedback receiver',
    
    -- Feedback classification
    feedback_type ENUM('positive', 'constructive', 'development', 'goal_progress', 'concern', 'recognition') NOT NULL,
    content TEXT NOT NULL,
    
    -- Link to evaluation criteria (enables evidence aggregation)
    related_competency_id INT NULL,
    related_kpi_id INT NULL,
    related_responsibility_id INT NULL,
    related_value_id INT NULL,
    
    -- Feedback metadata
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    visibility ENUM('private', 'manager_only', 'hr_visible', 'public') DEFAULT 'manager_only',
    
    -- Edit tracking (for audit trail)
    is_edited BOOLEAN DEFAULT FALSE,
    edit_history JSON NULL COMMENT 'Track changes: [{"edited_at": "2025-07-27T10:30:00", "edited_by": 123, "reason": "clarification"}]',
    
    -- Follow-up tracking
    requires_follow_up BOOLEAN DEFAULT FALSE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    follow_up_notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (session_id) REFERENCES one_to_one_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (given_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (related_competency_id) REFERENCES competencies(id) ON DELETE SET NULL,
    FOREIGN KEY (related_kpi_id) REFERENCES company_kpis(id) ON DELETE SET NULL,
    FOREIGN KEY (related_responsibility_id) REFERENCES job_template_responsibilities(id) ON DELETE SET NULL,
    FOREIGN KEY (related_value_id) REFERENCES company_values(id) ON DELETE SET NULL,
    
    -- Performance indexes for evidence aggregation
    INDEX idx_receiver_type (receiver_id, feedback_type),
    INDEX idx_session_feedback (session_id, feedback_type),
    INDEX idx_competency_feedback (related_competency_id, created_at),
    INDEX idx_kpi_feedback (related_kpi_id, created_at),
    INDEX idx_responsibility_feedback (related_responsibility_id, created_at),
    INDEX idx_value_feedback (related_value_id, created_at),
    INDEX idx_feedback_date_range (receiver_id, created_at, feedback_type),
    INDEX idx_urgency_follow_up (urgency, requires_follow_up),
    
    -- Data integrity constraints
    CONSTRAINT chk_feedback_content CHECK (CHAR_LENGTH(content) >= 10),
    CONSTRAINT chk_at_least_one_relation CHECK (
        related_competency_id IS NOT NULL OR 
        related_kpi_id IS NOT NULL OR 
        related_responsibility_id IS NOT NULL OR 
        related_value_id IS NOT NULL OR
        feedback_type IN ('recognition', 'concern')
    )
) ENGINE=InnoDB COMMENT='Real-time feedback capture linked to 1:1 sessions and evaluation criteria';

-- =====================================================
-- 3. SESSION TEMPLATES FOR CONSISTENCY
-- =====================================================
-- Provides structure for different types of 1:1 sessions
-- Ensures consistent agenda items and reduces manager prep time

CREATE TABLE one_to_one_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Template configuration
    frequency ENUM('weekly', 'biweekly', 'monthly', 'quarterly', 'ad_hoc') NOT NULL,
    default_duration_minutes INT DEFAULT 30,
    
    -- Structured agenda template
    agenda_template JSON NOT NULL COMMENT 'Template agenda: [{"section": "Goal Progress", "time_minutes": 10, "questions": ["How are you progressing on X?"]}]',
    
    -- Department/role targeting
    applicable_departments JSON NULL COMMENT 'Array of department names this template applies to',
    applicable_job_templates JSON NULL COMMENT 'Array of job_template_ids this applies to',
    
    -- Template metadata
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_frequency (frequency),
    INDEX idx_active (is_active),
    INDEX idx_usage (usage_count DESC)
) ENGINE=InnoDB COMMENT='Templates for structured 1:1 sessions';

-- =====================================================
-- 4. FEEDBACK AGGREGATION VIEWS
-- =====================================================
-- Pre-computed views for fast evidence retrieval during review prep
-- These views power the "auto-generated review draft" functionality

-- View: Recent feedback by competency for an employee
CREATE VIEW v_employee_competency_feedback AS
SELECT 
    f.receiver_id,
    f.related_competency_id,
    c.competency_name,
    c.competency_type,
    COUNT(*) as feedback_count,
    COUNT(CASE WHEN f.feedback_type = 'positive' THEN 1 END) as positive_count,
    COUNT(CASE WHEN f.feedback_type = 'constructive' THEN 1 END) as constructive_count,
    COUNT(CASE WHEN f.feedback_type = 'development' THEN 1 END) as development_count,
    MAX(f.created_at) as last_feedback_date,
    GROUP_CONCAT(
        CONCAT(f.feedback_type, ': ', LEFT(f.content, 100)) 
        ORDER BY f.created_at DESC 
        SEPARATOR ' | '
    ) as recent_feedback_summary
FROM one_to_one_feedback f
JOIN competencies c ON f.related_competency_id = c.id
WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY f.receiver_id, f.related_competency_id, c.competency_name, c.competency_type;

-- View: KPI-related feedback aggregation
CREATE VIEW v_employee_kpi_feedback AS
SELECT 
    f.receiver_id,
    f.related_kpi_id,
    k.kpi_name,
    k.category,
    COUNT(*) as feedback_count,
    AVG(CASE 
        WHEN f.feedback_type = 'positive' THEN 5
        WHEN f.feedback_type = 'constructive' THEN 3
        WHEN f.feedback_type = 'concern' THEN 2
        ELSE 4
    END) as sentiment_score,
    MAX(f.created_at) as last_feedback_date,
    GROUP_CONCAT(
        CONCAT(DATE(f.created_at), ': ', LEFT(f.content, 80))
        ORDER BY f.created_at DESC
        SEPARATOR ' | '
    ) as recent_feedback_summary
FROM one_to_one_feedback f
JOIN company_kpis k ON f.related_kpi_id = k.id
WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY f.receiver_id, f.related_kpi_id, k.kpi_name, k.category;

-- =====================================================
-- 5. ENHANCE EXISTING EVALUATIONS TABLE
-- =====================================================
-- Add columns to link evaluations with 1:1 evidence
-- This enables the "continuous feedback loop" integration

ALTER TABLE evaluations 
ADD COLUMN related_sessions JSON NULL COMMENT 'Array of session_ids that contributed evidence to this review',
ADD COLUMN evidence_summary TEXT NULL COMMENT 'Auto-generated summary from 1:1 feedback',
ADD COLUMN review_source ENUM('1to1_evidence', 'manual', 'hybrid') DEFAULT '1to1_evidence',
ADD COLUMN last_1to1_sync TIMESTAMP NULL COMMENT 'When evidence was last aggregated from 1:1s';

-- Add index for evidence-based review queries
CREATE INDEX idx_evaluations_evidence_source ON evaluations(review_source, last_1to1_sync);

-- =====================================================
-- 6. STORED PROCEDURES FOR EVIDENCE AGGREGATION
-- =====================================================
-- These procedures power the "auto-generated review draft" functionality
-- Called during review prep to aggregate 1:1 evidence

DELIMITER //

-- Procedure: Aggregate 1:1 feedback for evaluation prep
CREATE PROCEDURE sp_aggregate_1to1_evidence(
    IN p_employee_id INT,
    IN p_period_start DATE,
    IN p_period_end DATE
)
BEGIN
    DECLARE v_competency_summary TEXT DEFAULT '';
    DECLARE v_kpi_summary TEXT DEFAULT '';
    DECLARE v_session_ids JSON DEFAULT JSON_ARRAY();
    
    -- Aggregate competency feedback
    SELECT GROUP_CONCAT(
        CONCAT(competency_name, ' (', feedback_count, ' discussions): ', 
               CASE 
                   WHEN positive_count > constructive_count THEN 'Strengths demonstrated'
                   WHEN constructive_count > positive_count THEN 'Development opportunities identified'
                   ELSE 'Mixed feedback received'
               END)
        SEPARATOR '; '
    )
    INTO v_competency_summary
    FROM v_employee_competency_feedback
    WHERE receiver_id = p_employee_id
    AND last_feedback_date BETWEEN p_period_start AND p_period_end;
    
    -- Aggregate KPI feedback
    SELECT GROUP_CONCAT(
        CONCAT(kpi_name, ' - Sentiment: ', ROUND(sentiment_score, 1), '/5')
        SEPARATOR '; '
    )
    INTO v_kpi_summary
    FROM v_employee_kpi_feedback
    WHERE receiver_id = p_employee_id
    AND last_feedback_date BETWEEN p_period_start AND p_period_end;
    
    -- Get related session IDs
    SELECT JSON_ARRAYAGG(DISTINCT s.session_id)
    INTO v_session_ids
    FROM one_to_one_sessions s
    JOIN one_to_one_feedback f ON s.session_id = f.session_id
    WHERE s.employee_id = p_employee_id
    AND s.actual_date BETWEEN p_period_start AND p_period_end
    AND s.status = 'completed';
    
    -- Return aggregated evidence
    SELECT 
        v_competency_summary as competency_evidence,
        v_kpi_summary as kpi_evidence,
        v_session_ids as related_sessions,
        COUNT(DISTINCT s.session_id) as total_sessions,
        COUNT(f.feedback_id) as total_feedback_items
    FROM one_to_one_sessions s
    LEFT JOIN one_to_one_feedback f ON s.session_id = f.session_id
    WHERE s.employee_id = p_employee_id
    AND s.actual_date BETWEEN p_period_start AND p_period_end
    AND s.status = 'completed';
    
END //

-- Procedure: Generate 1:1 session recommendations
CREATE PROCEDURE sp_recommend_1to1_agenda(
    IN p_employee_id INT,
    IN p_manager_id INT
)
BEGIN
    -- Recommend agenda items based on:
    -- 1. Overdue follow-ups
    -- 2. Recent feedback requiring discussion
    -- 3. Goal progress updates needed
    
    SELECT 
        'Follow-up Required' as agenda_category,
        CONCAT('Follow up on: ', f.content) as agenda_item,
        f.urgency as priority,
        f.created_at as last_discussed
    FROM one_to_one_feedback f
    JOIN one_to_one_sessions s ON f.session_id = s.session_id
    WHERE f.receiver_id = p_employee_id
    AND f.requires_follow_up = TRUE
    AND f.follow_up_completed = FALSE
    AND s.actual_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    
    UNION ALL
    
    SELECT 
        'High Priority Feedback' as agenda_category,
        CONCAT('Discuss: ', LEFT(f.content, 50), '...') as agenda_item,
        f.urgency as priority,
        f.created_at as last_discussed
    FROM one_to_one_feedback f
    WHERE f.receiver_id = p_employee_id
    AND f.urgency IN ('high', 'critical')
    AND f.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    
    ORDER BY 
        CASE priority 
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            ELSE 4
        END,
        last_discussed DESC
    LIMIT 10;
    
END //

DELIMITER ;

-- =====================================================
-- 7. INSERT DEFAULT 1:1 TEMPLATES
-- =====================================================
-- Provide starter templates for common 1:1 scenarios

INSERT INTO one_to_one_templates (template_name, description, frequency, default_duration_minutes, agenda_template, created_by) VALUES
(
    'Weekly Check-in',
    'Standard weekly 1:1 for ongoing performance discussions',
    'weekly',
    30,
    JSON_ARRAY(
        JSON_OBJECT('section', 'Goal Progress', 'time_minutes', 10, 'questions', JSON_ARRAY('How are you progressing on your current goals?', 'Any blockers I can help remove?')),
        JSON_OBJECT('section', 'Feedback Exchange', 'time_minutes', 10, 'questions', JSON_ARRAY('What feedback do you have for me?', 'Any feedback on recent work?')),
        JSON_OBJECT('section', 'Development', 'time_minutes', 5, 'questions', JSON_ARRAY('What are you learning?', 'Any skill gaps to address?')),
        JSON_OBJECT('section', 'Next Steps', 'time_minutes', 5, 'questions', JSON_ARRAY('What are your priorities for next week?', 'How can I support you?'))
    ),
    1
),
(
    'Monthly Development Focus',
    'Monthly 1:1 focused on career development and growth',
    'monthly',
    45,
    JSON_ARRAY(
        JSON_OBJECT('section', 'Development Goals Review', 'time_minutes', 15, 'questions', JSON_ARRAY('Progress on development goals?', 'What have you learned this month?')),
        JSON_OBJECT('section', 'Competency Discussion', 'time_minutes', 15, 'questions', JSON_ARRAY('Which competencies are you strengthening?', 'Where do you want to grow next?')),
        JSON_OBJECT('section', 'Career Aspirations', 'time_minutes', 10, 'questions', JSON_ARRAY('How does current work align with career goals?', 'What opportunities interest you?')),
        JSON_OBJECT('section', 'Action Planning', 'time_minutes', 5, 'questions', JSON_ARRAY('What development actions for next month?', 'Resources or support needed?'))
    ),
    1
),
(
    'Quarterly Performance Review Prep',
    'Quarterly session to prepare for formal performance reviews',
    'quarterly',
    60,
    JSON_ARRAY(
        JSON_OBJECT('section', 'Performance Reflection', 'time_minutes', 20, 'questions', JSON_ARRAY('What are your biggest accomplishments this quarter?', 'What challenges did you overcome?')),
        JSON_OBJECT('section', 'Goal Achievement Review', 'time_minutes', 15, 'questions', JSON_ARRAY('How did you perform against your goals?', 'What factors contributed to success/challenges?')),
        JSON_OBJECT('section', 'Competency Self-Assessment', 'time_minutes', 15, 'questions', JSON_ARRAY('How would you rate your competency development?', 'Which areas need more focus?')),
        JSON_OBJECT('section', 'Future Planning', 'time_minutes', 10, 'questions', JSON_ARRAY('What goals for next quarter?', 'What support do you need to succeed?'))
    ),
    1
);

-- =====================================================
-- 8. CREATE MIGRATION TRACKING
-- =====================================================
-- Track this migration for rollback and audit purposes

INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address, user_agent) VALUES
(1, 'MIGRATION_PHASE_1', 'schema_migration', 1, 
 JSON_OBJECT(
     'migration_name', '2025_07_27_081800_phase1_continuous_performance_foundation',
     'tables_created', JSON_ARRAY('one_to_one_sessions', 'one_to_one_feedback', 'one_to_one_templates'),
     'views_created', JSON_ARRAY('v_employee_competency_feedback', 'v_employee_kpi_feedback'),
     'procedures_created', JSON_ARRAY('sp_aggregate_1to1_evidence', 'sp_recommend_1to1_agenda'),
     'evaluations_enhanced', true,
     'phase', 1,
     'status', 'completed'
 ), 
 '127.0.0.1', 'Migration Script');

-- Commit the transaction
COMMIT;

-- =====================================================
-- 9. POST-MIGRATION VERIFICATION QUERIES
-- =====================================================
-- Run these queries to verify the migration was successful

-- Verify table creation
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'performance_evaluation' 
AND TABLE_NAME IN ('one_to_one_sessions', 'one_to_one_feedback', 'one_to_one_templates');

-- Verify foreign key constraints
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'performance_evaluation' 
AND TABLE_NAME IN ('one_to_one_sessions', 'one_to_one_feedback')
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Verify indexes were created
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'performance_evaluation' 
AND TABLE_NAME IN ('one_to_one_sessions', 'one_to_one_feedback')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- Verify views were created
SELECT 
    TABLE_NAME as VIEW_NAME,
    VIEW_DEFINITION
FROM INFORMATION_SCHEMA.VIEWS 
WHERE TABLE_SCHEMA = 'performance_evaluation' 
AND TABLE_NAME LIKE 'v_employee_%';

-- Verify stored procedures were created
SELECT 
    ROUTINE_NAME,
    ROUTINE_TYPE,
    CREATED,
    LAST_ALTERED
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'performance_evaluation' 
AND ROUTINE_NAME LIKE 'sp_%';

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
-- Phase 1 foundation is now ready for continuous performance management
-- Next steps:
-- 1. Train managers on new 1:1 workflow
-- 2. Begin capturing feedback in structured format
-- 3. Test evidence aggregation procedures
-- 4. Prepare for Phase 2: Development Goal Tracking
-- =====================================================