# 360-Degree System Administrator Setup Guide

## System Requirements Checklist

Before beginning the 360-degree enhancements installation, ensure your system meets these requirements:

### Infrastructure Requirements
- **PHP Version**: 8.1 or higher with PDO extension enabled
- **MySQL Version**: 8.0+ with JSON data type support
- **Web Server**: Apache 2.4+ (with mod_rewrite) or Nginx 1.20+
- **SSL Certificate**: HTTPS required for all endpoints
- **Memory**: Minimum 512MB RAM for PHP, 2GB recommended for larger teams
- **Storage**: 1GB additional space for system logs and file uploads

### Required PHP Extensions
```bash
php --version  # Should show 8.1+
php -m | grep -E "(pdo|openssl|json|curl|gd|mbstring|fileinfo)"  # All should appear
```

## Pre-Installation Safety Steps

### 1. Full System Backup
```bash
# Create backup directory
mkdir -p /var/backups/360-system
cd /var/backups/360-system

# Database backup
mysqldump -u [username] -p [database_name] > db_backup_$(date +%Y%m%d_%H%M%S).sql

# File system backup
tar -czf system_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/your/system
```

### 2. Staging Environment Setup
```bash
# Create staging copy
cp -r production/ staging/
sed -i 's/production-db/staging-db/g' staging/config/config.php
```

## Database Schema Deployment

### Automated Deployment Script

**Location:** `/scripts/deploy_enhancements.php`

**Step 1: Database Schema Installation**
```bash
cd /path/to/360-system
php scripts/deploy_enhancements.php --mode=database --env=staging
```

**Step 2: Validate Installation**
```bash
php scripts/check_phase3_schema.php --verify --verbose
```

### Manual Database Setup

If you prefer manual installation, execute these SQL files in order:

```bash
mysql -u [username] -p [database_name]

-- Step 1: Core enhancement tables
SOURCE sql/004_comprehensive_enhancements.sql

-- Step 2: Sample data setup
SOURCE sql/seed_sample_data.sql

-- Step 3: Performance indexes
SOURCE sql/create_enhancement_indexes.sql
```

### Table Creation Verification

**Test Query to Verify Installation:**
```sql
SELECT 
    table_name,
    table_rows,
    table_comment
FROM information_schema.tables 
WHERE table_schema = 'your_database' 
    AND table_name LIKE '%360%'
ORDER BY table_name;
```

**Expected Output:**
```
+-----------------------------------+------------+-------------------------+
| table_name                        | table_rows | table_comment          |
+-----------------------------------+------------+-------------------------+
| self_assessment_configs          | 5          | Assessment configurations|
| self_assessment_responses        | 250        | Employee responses       |
| kudos_categories               | 8          | Recognition types        |
| kudos_transactions             | 1200       | Peer recognition history |
| upward_feedback_sessions      | 45         | Feedback cycle tracking |
| okr_objectives              | 180        | Individual OKRs         |
| okr_key_results             | 540        | Key result tracking      |
| idp_master                  | 75         | Development plans       |
| idp_development_actions    | 180        | Development actions     |
+-----------------------------------+------------+-------------------------+
```

## Configuration Management

### Environment Configuration

**File Location:** `/config/360_enhancements.php`

```php
<?php
return [
    // Feature Toggles
    'enhancements' => [
        'self_assessment' => [
            'enabled' => true,
            'default_frequency' => 'quarterly',
            'notification_reminders' => [3, 7, 14], // days before deadline
            'approval_required' => false,
        ],
        
        'kudos_system' => [
            'enabled' => true,
            'categories' => [
                'team_player' => ['points' => 10, 'icon' => '👥'],
                'innovation' => ['points' => 15, 'icon' => '💡'],
                'leadership' => ['points' => 20, 'icon' => '🏆'],
                'above_beyond' => ['points' => 25, 'icon' => '⭐'],
                'customer_focus' => ['points' => 15, 'icon' => '🎯'],
            ],
            'monthly_allowance' => 500, // points per employee
            'yearly_reset' => true,
        ],
        
        'okr_management' => [
            'enabled' => true,
            'default_cycle_length' => 90, // days
            'max_objectives' => 5,
            'max_key_results' => 5,
            'visibility_levels' => ['private', 'team', 'public'],
        ],
        
        'upward_feedback' => [
            'enabled' => true,
            'cycle_frequency' => 180, // days
            'anonymous_default' => true,
            'min_responses_for_report' => 3,
        ],
        
        'idp_system' => [
            'enabled' => true,
            'default_timeline_days' => 180,
            'milestone_checkpoints' => [30, 60, 90, 180],
            'require_approval' => true,
        ],
    ],
    
    // Security Settings
    'security' => [
        'rate_limit' => [
            'self_assessment_submissions' => 5, // per hour
            'kudos_giving' => 20, // per hour
            'upward_feedback' => 1, // per cycle
        ],
        
        'data_retention' => [
            'self_assessment_history' => '5 years',
            'kudos_history' => 'indefinite',
            'upward_feedback_history' => '3 years',
            'okr_history' => '5 years',
            'idp_history' => 'indefinite',
        ],
        
        'encryption' => [
            'algorithm' => 'AES-256-CBC',
            'key_length' => 32,
            'rotation_period' => 90, // days
        ],
    ],
    
    // Notification Settings
    'notifications' => [
        'sender_email' => 'noreply@company.com',
        'templates' => [
            'self_assessment_reminder' => 'emails/self-assessment-reminder',
            'kudos_received' => 'emails/kudos-received',
            'upward_feedback_request' => 'emails/upward-feedback-request',
            'okr_milestone' => 'emails/okr-milestone',
            'idp_checkin_reminder' => 'emails/idp-checkin',
        ],
    ],
    
    // Performance Settings
    'performance' => [
        'cache_ttl' => 3600, // seconds
        'query_timeout' => 30, // seconds
        'max_file_upload_size' => 10 * 1024 * 1024, // 10MB
        'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx'],
    ],
    
    // API Settings
    'api' => [
        'pagination' => [
            'default_page_size' => 20,
            'max_page_size' => 100,
        ],
        
        'authentication' => [
            'token_expiry' => 3600, // seconds
            'refresh_expiry' => 86400, // seconds
            'algorithm' => 'HS256',
        ],
    ],
];
```

### Environment Variables

**File Location:** `/.env.360`

```bash
# Database Configuration
DB_360_HOST=localhost
DB_360_USERNAME=enhancements_user
DB_360_PASSWORD=secure_password_here
DB_360_NAME=enhancements_db

# Redis Configuration (Optional)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_DB_ENHANCEMENTS=2

# Email Configuration
SMTP_HOST=smtp.company.com
SMTP_PORT=587
SMTP_USERNAME=noreply@company.com
SMTP_PASSWORD=email_password_here

# Security Keys
JWT_SECRET_KEY=your_jwt_secret_here
ENCRYPTION_KEY_BASE64=base64_encoded_key_here
```

## Feature-Specific Setup Guides

### 1. Self-Assessment Configuration

#### Create Assessment Frameworks

**1. Competency Framework Setup:**
```sql
INSERT INTO self_assessment_configs (
    competency_framework_id, 
    period_id, 
    config_data,
    active
) VALUES (
    1,
    1,
    JSON_OBJECT(
        'competencies', JSON_ARRAY(
            JSON_OBJECT(
                'id', 1,
                'name', 'Communication',
                'description', 'Ability to convey ideas clearly',
                'weight', 0.2,
                'options', JSON_ARRAY('Beginner', 'Developing', 'Proficient', 'Advanced', 'Expert')
            ),
            JSON_OBJECT(
                'id', 2,
                'name', 'Teamwork',
                'description', 'Effectiveness in collaborative settings',
                'weight', 0.2,
                'options', JSON_ARRAY('Beginner', 'Developing', 'Proficient', 'Advanced', 'Expert')
            ),
            JSON_OBJECT(
                'id', 3,
                'name', 'Problem Solving',
                'description', 'Critical thinking and solution development',
                'weight', 0.3,
                'options', JSON_ARRAY('Beginner', 'Developing', 'Proficient', 'Advanced', 'Expert')
            ),
            JSON_OBJECT(
                'id', 4,
                'name', 'Leadership',
                'description', 'Guiding and inspiring others',
                'weight', 0.3,
                'options', JSON_ARRAY('Beginner', 'Developing', 'Proficient', 'Advanced', 'Expert')
            )
        ),
        'scoring', JSON_OBJECT(
            'type', 'weighted',
            'scale', JSON_OBJECT(
                'min', 1,
                'max', 5,
                'increment', 0.5
            )
        ),
        'workflow', JSON_OBJECT(
            'auto_save', true,
            'approval_required', false,
            'notifications', JSON_OBJECT(
                'due_date_reminder', 7,
                'overdue_warning', 3
            )
        )
    ),
    TRUE
);
```

#### Set Assessment Schedules

**Quarterly Schedule Example:**
```sql
-- January - March cycle
INSERT INTO assessment_cycles (
    cycle_name,
    start_date,
    due_date,
    late_date,
    configuration_id
) VALUES (
    'Q1 2024 Self-Assessment',
    '2024-01-01',
    '2024-03-31',
    '2024-04-07',
    1
);
```

### 2. Kudos System Configuration

#### Setup Categories

**Default Kudos Categories:**
```sql
INSERT INTO kudos_categories (
    category_name,
    category_description,
    points_value,
    icon_url,
    active
) VALUES 
    ('Team Player', 'Exceptional collaboration and support', 10, '🤝', TRUE),
    ('Innovation', 'Creative problem-solving and new ideas', 15, '💡', TRUE),
    ('Leadership', 'Effective guidance and mentoring', 20, '👑', TRUE),
    ('Above & Beyond', 'Going extra mile for success', 25, '⭐', TRUE),
    ('Customer Focus', 'Exceptional client service', 15, '💼', TRUE),
    ('Technical Excellence', 'Outstanding technical contribution', 15, '🔧', TRUE),
    ('Communication Excellence', 'Clear and effective information sharing', 10, '📢', TRUE);
```

#### Configure Point System

**Monthly Allowances:**
```sql
INSERT INTO kudos_configuration (
    setting_name,
    value_json,
    effective_date
) VALUES (
    'monthly_point_allowances',
    JSON_OBJECT('default', 500, 'manager', 750, 'hr_admin', 1000, 'senior_manager', 1000),
    CURDATE()
);
```

### 3. OKR Framework Setup

#### Create OKR Templates

**Department-specific OKRs:**
```sql
INSERT INTO okr_templates (
    template_name,
    department,
    objective_type,
    key_result_templates,
    default_timeline_days
) VALUES (
    'Engineering Performance OKR',
    'Engineering',
    'performance_improvement',
    JSON_ARRAY(
        JSON_OBJECT(
            'title', 'Reduce system errors by 25%',
            'target_value', 25,
            'unit', 'percentage',
            'metric_type', 'system_error_rate'
        ),
        JSON_OBJECT(
            'title', 'Improve code coverage to 85%',
            'target_value', 85,
            'unit', 'percentage', 
            'metric_type', 'test_coverage'
        )
    ),
    90
);
```

#### Configure Automatic Reminders

**Reminder Schedule:**
```sql
INSERT INTO scheduled_notifications (
    notification_type,
    subject,
    template,
    trigger_event,
    days_before,
    is_active
) VALUES 
    ('okr_update_reminder', 'OKR Update Due', 'templates/okr_reminder', 'weekly_checkin', 0, TRUE),
    ('okr_milestone_alert', 'OKR Milestone Ahead', 'templates/milestone_warning', 'milestone_approach', 7, TRUE);
```

### 4. Upward Feedback Setup

#### Configure Feedback Cycles

**Bi-annual upward feedback:**
```sql
INSERT INTO upward_feedback_cycles (
    cycle_name,
    start_date,
    end_date,
    anonymous_by_default,
    competency_framework_id,
    notification_schedule
) VALUES (
    'Q1-Q2 2024 Manager Feedback',
    '2024-01-01',
    '2024-06-30',
    true,
    2,
    JSON_OBJECT(
        'initial_invite', 14,
        'reminder_1', 7,
        'reminder_2', 3,
        'final_notice', 1
    )
);
```

#### Setup Manager Profiles

**Manager competencies to evaluate:**
```sql
INSERT INTO manager_competencies (
    framework_id,
    competency_name,
    description,
    evaluation_criteria
) VALUES (
    2,
    'Leadership Style',
    'Effectiveness in guiding team',
    'Provides clear direction, inspires others, delegates effectively'
);
```

### 5. IDP System Configuration

#### Setup Development Frameworks

**Professional development pathways:**
```sql
INSERT INTO development_frameworks (
    framework_name,
    description,
    skill_categories,
    progression_levels
) VALUES (
    'Leadership Development',
    'Progressive leadership skill building',
    JSON_ARRAY(
        JSON_OBJECT('category', 'Communication', 'description', 'Public speaking and writing'),
        JSON_OBJECT('category', 'Delegation', 'description', 'Task assignment and oversight'),
        JSON_OBJECT('category', 'Strategy', 'description', 'Planning and execution')
    ),
    JSON_ARRAY('Beginner', 'Developing', 'Proficient', 'Expert', 'Master')
);
```

#### Resource Library Setup

**Training resource types:**
```sql
INSERT INTO development_resources (
    resource_name,
    resource_type,
    cost_estimate,
    duration_hours,
    format,
    provider
) VALUES 
    ('Leadership Excellence Workshop', 'course', '$1200', '16', 'classroom', 'Corporate Training Solutions'),
    ('Advanced Communication Training', 'course', '$800', '12', 'online', 'Learning Platform'),
    ('Executive Mentorship Program', 'program', '$0', '50', 'mentorship', 'Internal');
```

## Security Configuration

### 1. Encryption Setup

**Generate secure keys:**
```bash
# Generate encryption keys
php -r "echo base64_encode(random_bytes(32));" > encryption_key.txt

# Setup key rotation
crontab -e
# Add: 0 0 */90 * * /path/to/rotate_keys.php
```

### 2. API Rate Limiting

**Configure rate limiting in .htaccess:**
```apache
<IfModule mod_ratelimit.c>
    SetOutputFilter RATE_LIMIT
    SetEnv rate-limit 100
    SetEnv rate-limit-360-features 20
</IfModule>
```

### 3. Security Headers

**Add security headers to Apache:**
```apache
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline';"
</IfModule>
```

## Testing and Validation

### 1. Post-Installation Verification

**Run system validation:**
```bash
php scripts/validate_complete_system.php --mode=enhancement-test
```

**Check database integrity:**
```sql
-- Verify all tables were created
SELECT table_name, table_rows FROM information_schema.tables 
WHERE table_schema = 'your_database' AND table_name LIKE '%360%';

-- Test data relationships
SELECT 
    'Self-Assessment Test' as test_name,
    COUNT(*) as record_count,
    MIN(created_at) as earliest_date
FROM self_assessment_responses;

-- Verify foreign key relationships
SELECT 
    table_name,
    column_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage
WHERE referenced_table_name IS NOT NULL
    AND table_schema = 'your_database'
    AND table_name LIKE '%360%';
```

### 2. Load Testing

**Basic performance test:**
```bash
# Install Apache Bench if not available
ab -n 1000 -c 10 http://localhost/public/api/360/system-health

# Database query performance
mysql -e "SELECT table_name, rows, avg_row_length, data_length FROM information_schema.tables WHERE table_schema='your_database' AND table_name LIKE '%360%';"
```

### 3. User Acceptance Testing

**Manual testing checklist:**

| Test Category | Test Case | Expected Result |
|---------------|-----------|-----------------|
| **Accessibility** | Can login with 2FA | ✅ Success |
| **Self-Assessment** | Create complete assessment | ✅ Saves successfully |
| **Kudos** | Send kudos to colleague | ✅ Recipient receives notification |
| **OKRs** | Create new objective | ✅ Appears in dashboard |
| **Upward Feedback** | Submit anonymous feedback | ✅ Submits without exposing identity |
| **IDP** | Create development plan | ✅ Manager sees pending approval |

## Deployment Scripts

### Automated Deployment Script

**Location:** `scripts/deploy_enhancements.sh`

```bash
#!/bin/bash
set -e

# Configuration
STAGING_URL="https://staging.yourcompany.com"
PRODUCTION_URL="https://performance.yourcompany.com"
BACKUP_DIR="/var/backups/360-system"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Pre-deployment checks
check_requirements() {
    log "Checking system requirements..."
    
    # PHP version
    if ! command -v php &> /dev/null; then
        error "PHP is not installed"
        exit 1
    fi
    
    REQUIRED_VERSION="8.1"
    PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}' | cut -d'.' -f1-2)
    if (( $(echo "$PHP_VERSION < $REQUIRED_VERSION" | bc -l) )); then
        error "PHP version $PHP_VERSION is below required $REQUIRED_VERSION"
        exit 1
    fi
    
    # MySQL connection
    if ! mysql -u$DB_USERNAME -p$DB_PASSWORD -h$DB_HOST -e "SELECT 1;" &> /dev/null; then
        error "Database connection failed"
        exit 1
    fi
    
    log "All requirements satisfied ✓"
}

# Run deployment
deploy_enhancements() {
    check_requirements
    
    log "Starting 360-degree enhancement deployment..."
    
    # Backup current system
    log "Creating backup..."
    create_backup
    
    # Deploy database schema
    log "Deploying database schema..."
    deploy_database
    
    # Deploy application files
    log "Deploying application files..."
    deploy_application
    
    # Run post-deployment tests
    log "Running post-deployment tests..."
    run_tests
    
    # Restart services
    log "Restarting services..."
    restart_services
    
    log "Deployment completed successfully!"
}

# Helper functions
create_backup() {
    mkdir -p $BACKUP_DIR
    mysqldump -u$DB_USERNAME -p$DB_PASSWORD $DB_NAME > "$BACKUP_DIR/enhancement_backup_$(date +%Y%m%d_%H%M%S).sql"
}

deploy_database() {
    mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_NAME < sql/004_comprehensive_enhancements.sql
}

deploy_application() {
    rsync -av --exclude-from='.rsyncignore' ./ remote_user@$DEPLOY_HOST:/var/www/360-enhancements/
}

run_tests() {
    php scripts/validate_complete_system.php --mode=production-test
    echo "All tests passed ✓"
}

restart_services() {
    sudo systemctl reload apache2 || sudo systemctl reload nginx
    sudo systemctl reload php-fpm
}

# Main execution
case "$1" in
    staging)
        DEPLOY_HOST=$STAGING_URL
        deploy_enhancements
        ;;
    production)
        DEPLOY_HOST=$PRODUCTION_URL
        deploy_enhancements
        ;;
    *)
        echo "Usage: $0 {staging|production}"
        exit 1
        ;;
esac
```

## Monitoring and Maintenance

### 1. Health Check Setup

**Location:** `/public/api/360/health-check`

```php
<?php
// Health check endpoint for 360-enhancements
header('Content-Type: application/json');

$health = [
    'database' => check_database_connection(),
    'key_tables' => check_table_integrity(),
    'api_endpoints' => check_api_availability(),
    'file_permissions' => check_file_permissions(),
    'server_load' => check_server_health(),
];

echo json_encode($health, JSON_PRETTY_PRINT);

function check_database_connection() {
    // Implementation for database connectivity check
    return ['status' => 'ok', 'connections' => 3];
}
?>
```

### 2. Monitoring Dashboard

**Prometheus metrics example:**
```yaml
# In the application configuration
monitoring:
  metrics:
    - name: self_assessment_submissions_total
      type: counter
      description: Total number of self-assessment submissions
    
    - name: kudos_transactions_total
      type: counter
      description: Total kudos sent
    
    - name: api_requests_duration
      type: histogram
      description: API request duration in seconds
    
    - name: active_users_gauge
      type: gauge
      description: Number of active users in the system
```

### 3. Automated Maintenance

**Weekly maintenance script:** `scripts/weekly_maintenance.sh`

```bash
#!/bin/bash
# Weekly 360-system maintenance

# Clean up old data based on retention policy
DELETE_OLD_DATA_QUERY="
DELETE FROM kudos_transactions WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 YEAR);
DELETE FROM self_assessment_responses WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 YEAR);
DELETE FROM upward_feedback_responses WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 YEAR);
"
mysql -u$DB_USERNAME -p$DB_PASSWORD -e "$DELETE_OLD_DATA_QUERY"

# Optimize tables
mysqlcheck -u$DB_USERNAME -p$DB_PASSWORD --optimize $DB_NAME

# Clean cache and temporary files
find /var/cache/360-enhancements -type f -mtime +7 -delete
find /tmp -name "360_*" -type f -mtime +1 -delete
```

## Rollback Procedures

### Instant Rollback Script

**Location:** `scripts/rollback_enhancements.sh`

```bash
#!/bin/bash
set -e

if [ "$1" == "emergency" ]; then
    echo "Emergency rollback initiated..."
    
    # Rollback database
    mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_NAME < latest_backup.sql
    
    # Restore previous application files
    rsync -av backup/enhancement-backup/ ./enhan