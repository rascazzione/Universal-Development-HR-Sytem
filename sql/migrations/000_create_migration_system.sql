-- Migration System Infrastructure
-- Creates the schema_migrations table to track migration state

USE performance_evaluation;

-- Create migrations tracking table
CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(255) UNIQUE NOT NULL,
    filename VARCHAR(255) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT,
    checksum VARCHAR(64),
    status ENUM('pending', 'running', 'completed', 'failed', 'rolled_back') DEFAULT 'pending',
    rollback_sql TEXT,
    description TEXT,
    INDEX idx_version (version),
    INDEX idx_status (status),
    INDEX idx_executed_at (executed_at)
);

-- Insert initial migration record for existing schema
INSERT IGNORE INTO schema_migrations (version, filename, status, executed_at, description) 
VALUES ('000_create_migration_system', '000_create_migration_system.sql', 'completed', NOW(), 'Migration system infrastructure');

-- Insert record for the initial database setup (retroactive)
INSERT IGNORE INTO schema_migrations (version, filename, status, executed_at, description) 
VALUES ('001_initial_schema', 'database_setup.sql', 'completed', '2025-06-18 00:00:00', 'Initial database schema');

-- Insert record for the job templates system (retroactive)
INSERT IGNORE INTO schema_migrations (version, filename, status, executed_at, description) 
VALUES ('002_job_templates_system', 'job_templates_structure.sql', 'completed', NOW(), 'Job templates and competency system');

SELECT 'Migration system created successfully' as result;
SELECT 'Existing migrations recorded' as status;

-- Show current migration status
SELECT 
    version,
    filename,
    status,
    executed_at,
    description
FROM schema_migrations 
ORDER BY version;