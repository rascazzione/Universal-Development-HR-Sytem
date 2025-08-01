-- Phase 3: Advanced Features Database Schema
-- Growth Evidence System - Notifications, Enhanced Evidence Management, and Reporting
-- Created: 2025-07-28

USE performance_evaluation;

-- Notifications system
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('feedback_submitted', 'evidence_reminder', 'evaluation_summary', 'milestone_alert', 'system_announcement') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL, -- Additional data for the notification
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
);

-- Notification templates for consistent messaging
CREATE TABLE IF NOT EXISTS notification_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_key VARCHAR(100) UNIQUE NOT NULL,
    type ENUM('feedback_submitted', 'evidence_reminder', 'evaluation_summary', 'milestone_alert', 'system_announcement') NOT NULL,
    title_template VARCHAR(255) NOT NULL,
    message_template TEXT NOT NULL,
    variables JSON NULL, -- Available variables for the template
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_template_key (template_key),
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
);

-- Evidence tags for categorization
CREATE TABLE IF NOT EXISTS evidence_tags (
    tag_id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(50) UNIQUE NOT NULL,
    tag_color VARCHAR(7) DEFAULT '#007bff', -- Hex color code
    description TEXT,
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_tag_name (tag_name),
    INDEX idx_is_active (is_active)
);

-- Evidence entry tags (many-to-many relationship)
CREATE TABLE IF NOT EXISTS evidence_entry_tags (
    entry_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (entry_id, tag_id),
    FOREIGN KEY (entry_id) REFERENCES growth_evidence_entries(entry_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES evidence_tags(tag_id) ON DELETE CASCADE
);

-- Evidence approval workflow
CREATE TABLE IF NOT EXISTS evidence_approvals (
    approval_id INT PRIMARY KEY AUTO_INCREMENT,
    entry_id INT NOT NULL,
    approver_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'needs_revision') DEFAULT 'pending',
    comments TEXT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (entry_id) REFERENCES growth_evidence_entries(entry_id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_entry_status (entry_id, status),
    INDEX idx_approver (approver_id),
    INDEX idx_status (status)
);

-- Scheduled reports
CREATE TABLE IF NOT EXISTS scheduled_reports (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('evidence_summary', 'performance_trends', 'manager_overview', 'custom') NOT NULL,
    parameters JSON NOT NULL, -- Report parameters and filters
    recipients JSON NOT NULL, -- Array of user IDs or email addresses
    schedule_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    schedule_day_of_week TINYINT NULL, -- 0=Sunday, 1=Monday, etc.
    schedule_day_of_month TINYINT NULL, -- 1-31
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_next_run (next_run_at, is_active),
    INDEX idx_report_type (report_type),
    INDEX idx_created_by (created_by)
);

-- Report generation history
CREATE TABLE IF NOT EXISTS report_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NULL, -- NULL for manual reports
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('evidence_summary', 'performance_trends', 'manager_overview', 'custom') NOT NULL,
    parameters JSON NOT NULL,
    file_path VARCHAR(500) NULL, -- Path to generated file
    file_format ENUM('pdf', 'excel', 'csv') NOT NULL,
    file_size INT NULL,
    generation_time_ms INT NULL,
    status ENUM('generating', 'completed', 'failed') DEFAULT 'generating',
    error_message TEXT NULL,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (schedule_id) REFERENCES scheduled_reports(schedule_id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_generated_by (generated_by),
    INDEX idx_generated_at (generated_at),
    INDEX idx_status (status),
    INDEX idx_report_type (report_type)
);

-- Performance goals and milestones
CREATE TABLE IF NOT EXISTS performance_goals (
    goal_id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    manager_id INT NOT NULL,
    period_id INT NOT NULL,
    goal_title VARCHAR(255) NOT NULL,
    goal_description TEXT NOT NULL,
    target_value DECIMAL(10,2) NULL,
    current_value DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(50) NULL, -- e.g., 'percentage', 'count', 'currency'
    target_date DATE NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed', 'overdue', 'cancelled') DEFAULT 'not_started',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE CASCADE,
    INDEX idx_employee_period (employee_id, period_id),
    INDEX idx_manager (manager_id),
    INDEX idx_status (status),
    INDEX idx_target_date (target_date)
);

-- Goal milestones
CREATE TABLE IF NOT EXISTS goal_milestones (
    milestone_id INT PRIMARY KEY AUTO_INCREMENT,
    goal_id INT NOT NULL,
    milestone_title VARCHAR(255) NOT NULL,
    milestone_description TEXT,
    target_date DATE NOT NULL,
    completion_date DATE NULL,
    status ENUM('pending', 'completed', 'overdue') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (goal_id) REFERENCES performance_goals(goal_id) ON DELETE CASCADE,
    INDEX idx_goal_id (goal_id),
    INDEX idx_target_date (target_date),
    INDEX idx_status (status)
);

-- Evidence archival and retention
CREATE TABLE IF NOT EXISTS evidence_archive (
    archive_id INT PRIMARY KEY AUTO_INCREMENT,
    entry_id INT NOT NULL,
    archived_by INT NOT NULL,
    archive_reason ENUM('retention_policy', 'manual', 'employee_departure', 'data_cleanup') NOT NULL,
    archive_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    restore_date TIMESTAMP NULL,
    is_restored BOOLEAN DEFAULT FALSE,
    original_data JSON NOT NULL, -- Backup of original evidence entry
    
    FOREIGN KEY (entry_id) REFERENCES growth_evidence_entries(entry_id) ON DELETE CASCADE,
    FOREIGN KEY (archived_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_entry_id (entry_id),
    INDEX idx_archived_by (archived_by),
    INDEX idx_archive_date (archive_date),
    INDEX idx_is_restored (is_restored)
);

-- Insert default notification templates
INSERT INTO notification_templates (template_key, type, title_template, message_template, variables) VALUES
('feedback_submitted', 'feedback_submitted', 'New Feedback Received', 'You have received new feedback from {{manager_name}} for {{dimension}}. Rating: {{rating}}/5', '["manager_name", "dimension", "rating", "content"]'),
('evidence_reminder', 'evidence_reminder', 'Evidence Entry Reminder', 'Don\'t forget to provide feedback for your team members. You have {{pending_count}} pending feedback entries.', '["pending_count", "employee_names"]'),
('evaluation_summary', 'evaluation_summary', 'Evaluation Period Summary', 'Your evaluation period "{{period_name}}" is ending on {{end_date}}. You have {{evidence_count}} evidence entries.', '["period_name", "end_date", "evidence_count", "avg_rating"]'),
('milestone_alert', 'milestone_alert', 'Performance Milestone Alert', 'Milestone "{{milestone_title}}" for goal "{{goal_title}}" is due on {{due_date}}.', '["milestone_title", "goal_title", "due_date", "status"]'),
('system_announcement', 'system_announcement', 'System Announcement', '{{announcement_title}}', '["announcement_title", "announcement_content", "priority"]');

-- Insert default evidence tags
INSERT INTO evidence_tags (tag_name, tag_color, description, created_by) VALUES
('Leadership', '#dc3545', 'Evidence related to leadership skills and activities', 1),
('Innovation', '#28a745', 'Evidence of innovative thinking and creative solutions', 1),
('Collaboration', '#007bff', 'Evidence of teamwork and collaborative efforts', 1),
('Problem Solving', '#ffc107', 'Evidence of analytical and problem-solving skills', 1),
('Communication', '#17a2b8', 'Evidence of effective communication skills', 1),
('Customer Focus', '#6f42c1', 'Evidence of customer-oriented behavior and results', 1),
('Quality', '#fd7e14', 'Evidence of high-quality work and attention to detail', 1),
('Mentoring', '#20c997', 'Evidence of mentoring and developing others', 1);

-- Add columns to growth_evidence_entries for enhanced features
ALTER TABLE growth_evidence_entries 
ADD COLUMN IF NOT EXISTS is_archived BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS approval_status ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none',
ADD COLUMN IF NOT EXISTS visibility ENUM('public', 'manager_only', 'private') DEFAULT 'public',
ADD COLUMN IF NOT EXISTS evidence_source ENUM('manager_feedback', 'self_assessment', 'peer_feedback', 'customer_feedback') DEFAULT 'manager_feedback';

-- Add indexes for new columns
ALTER TABLE growth_evidence_entries 
ADD INDEX IF NOT EXISTS idx_archived (is_archived),
ADD INDEX IF NOT EXISTS idx_approval_status (approval_status),
ADD INDEX IF NOT EXISTS idx_visibility (visibility),
ADD INDEX IF NOT EXISTS idx_evidence_source (evidence_source);

-- Create view for active evidence (non-archived)
CREATE OR REPLACE VIEW active_evidence_entries AS
SELECT gee.*, 
       e.first_name as employee_first_name, 
       e.last_name as employee_last_name,
       e.employee_number,
       m.first_name as manager_first_name, 
       m.last_name as manager_last_name,
       (SELECT COUNT(*) FROM evidence_attachments ea WHERE ea.entry_id = gee.entry_id) as attachment_count,
       (SELECT GROUP_CONCAT(et.tag_name) FROM evidence_entry_tags eet 
        JOIN evidence_tags et ON eet.tag_id = et.tag_id 
        WHERE eet.entry_id = gee.entry_id) as tags
FROM growth_evidence_entries gee
JOIN employees e ON gee.employee_id = e.employee_id
JOIN employees m ON gee.manager_id = m.employee_id
WHERE gee.is_archived = FALSE;

COMMIT;