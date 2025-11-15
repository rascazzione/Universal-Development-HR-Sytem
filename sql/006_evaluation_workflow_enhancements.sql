-- Evaluation Workflow Enhancements
-- Adds support for structured evaluation process: Self -> Manager -> Final

-- Add new fields to evaluations table for workflow management
ALTER TABLE evaluations 
ADD COLUMN evaluation_type ENUM('self', 'manager', 'final') DEFAULT 'manager',
ADD COLUMN workflow_state ENUM('pending_self', 'self_submitted', 'pending_manager', 'manager_submitted', 'final_delivered') DEFAULT 'pending_self',
ADD COLUMN self_evaluation_id INT NULL,
ADD COLUMN self_submitted_at TIMESTAMP NULL,
ADD COLUMN manager_submitted_at TIMESTAMP NULL,
ADD COLUMN final_delivered_at TIMESTAMP NULL,
ADD FOREIGN KEY (self_evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE SET NULL,
ADD INDEX idx_evaluation_type (evaluation_type),
ADD INDEX idx_workflow_state (workflow_state);

-- Drop the old unique constraint and add new one that allows multiple evaluation types per period
ALTER TABLE evaluations DROP INDEX unique_employee_period;
ALTER TABLE evaluations ADD UNIQUE KEY unique_employee_period_type (employee_id, period_id, evaluation_type);

-- Insert default system settings for evaluation workflow
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('evaluation_workflow_enabled', 'true', 'Enable structured evaluation workflow'),
('self_evaluation_required', 'true', 'Require self-evaluation before manager evaluation'),
('manager_review_required', 'true', 'Require manager review for final evaluation'),
('evaluation_reminder_days', '7', 'Days before deadline to send reminder');

-- Create workflow audit table to track state changes
CREATE TABLE evaluation_workflow_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    from_state VARCHAR(50),
    to_state VARCHAR(50),
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_evaluation (evaluation_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_changed_at (changed_at)
);

-- Create evaluation notifications table
CREATE TABLE evaluation_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    user_id INT NOT NULL,
    notification_type ENUM('period_started', 'self_due', 'self_submitted', 'manager_due', 'manager_submitted', 'final_delivered') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_evaluation_user (evaluation_id, user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read)
);