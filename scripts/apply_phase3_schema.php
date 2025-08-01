<?php
/**
 * Apply Phase 3 Database Schema
 * Essential tables and columns for Phase 3 functionality
 */

require_once __DIR__ . '/../config/config.php';

echo "=== APPLYING PHASE 3 DATABASE SCHEMA ===\n";

try {
    $pdo = getDbConnection();
    
    // 1. Create notifications table
    echo "1. Creating notifications table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        type ENUM('feedback_submitted', 'evidence_reminder', 'evaluation_summary', 'milestone_alert', 'system_announcement') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        data JSON NULL,
        is_read BOOLEAN DEFAULT FALSE,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_user_unread (user_id, is_read),
        INDEX idx_type (type),
        INDEX idx_priority (priority),
        INDEX idx_created_at (created_at)
    )";
    $pdo->exec($sql);
    echo "   ✓ Notifications table created\n";
    
    // 2. Create evidence_tags table
    echo "2. Creating evidence_tags table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS evidence_tags (
        tag_id INT PRIMARY KEY AUTO_INCREMENT,
        tag_name VARCHAR(50) UNIQUE NOT NULL,
        tag_color VARCHAR(7) DEFAULT '#007bff',
        description TEXT,
        created_by INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_tag_name (tag_name),
        INDEX idx_is_active (is_active)
    )";
    $pdo->exec($sql);
    echo "   ✓ Evidence tags table created\n";
    
    // 3. Create evidence_archive table (renamed from evidence_archives)
    echo "3. Creating evidence_archive table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS evidence_archive (
        archive_id INT PRIMARY KEY AUTO_INCREMENT,
        entry_id INT NOT NULL,
        archived_by INT NOT NULL,
        archive_reason ENUM('retention_policy', 'manual', 'employee_departure', 'data_cleanup') NOT NULL,
        archive_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        restore_date TIMESTAMP NULL,
        is_restored BOOLEAN DEFAULT FALSE,
        original_data JSON NOT NULL,
        
        FOREIGN KEY (entry_id) REFERENCES growth_evidence_entries(entry_id) ON DELETE CASCADE,
        FOREIGN KEY (archived_by) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_entry_id (entry_id),
        INDEX idx_archived_by (archived_by),
        INDEX idx_archive_date (archive_date)
    )";
    $pdo->exec($sql);
    echo "   ✓ Evidence archive table created\n";
    
    // 4. Add columns to growth_evidence_entries
    echo "4. Adding columns to growth_evidence_entries...\n";
    
    // Check if columns exist first
    $columns = fetchAll('DESCRIBE growth_evidence_entries');
    $existingColumns = array_column($columns, 'Field');
    
    if (!in_array('is_archived', $existingColumns)) {
        $pdo->exec("ALTER TABLE growth_evidence_entries ADD COLUMN is_archived BOOLEAN DEFAULT FALSE");
        echo "   ✓ Added is_archived column\n";
    } else {
        echo "   ✓ is_archived column already exists\n";
    }
    
    if (!in_array('archived_at', $existingColumns)) {
        $pdo->exec("ALTER TABLE growth_evidence_entries ADD COLUMN archived_at TIMESTAMP NULL");
        echo "   ✓ Added archived_at column\n";
    } else {
        echo "   ✓ archived_at column already exists\n";
    }
    
    if (!in_array('approval_status', $existingColumns)) {
        $pdo->exec("ALTER TABLE growth_evidence_entries ADD COLUMN approval_status ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none'");
        echo "   ✓ Added approval_status column\n";
    } else {
        echo "   ✓ approval_status column already exists\n";
    }
    
    if (!in_array('visibility', $existingColumns)) {
        $pdo->exec("ALTER TABLE growth_evidence_entries ADD COLUMN visibility ENUM('public', 'manager_only', 'private') DEFAULT 'public'");
        echo "   ✓ Added visibility column\n";
    } else {
        echo "   ✓ visibility column already exists\n";
    }
    
    // 5. Add indexes
    echo "5. Adding indexes...\n";
    try {
        $pdo->exec("ALTER TABLE growth_evidence_entries ADD INDEX idx_archived (is_archived)");
        echo "   ✓ Added is_archived index\n";
    } catch (Exception $e) {
        echo "   ✓ is_archived index already exists\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE growth_evidence_entries ADD INDEX idx_approval_status (approval_status)");
        echo "   ✓ Added approval_status index\n";
    } catch (Exception $e) {
        echo "   ✓ approval_status index already exists\n";
    }
    
    // 6. Insert default evidence tags
    echo "6. Inserting default evidence tags...\n";
    $defaultTags = [
        ['Leadership', '#dc3545', 'Evidence related to leadership skills and activities'],
        ['Innovation', '#28a745', 'Evidence of innovative thinking and creative solutions'],
        ['Collaboration', '#007bff', 'Evidence of teamwork and collaborative efforts'],
        ['Problem Solving', '#ffc107', 'Evidence of analytical and problem-solving skills'],
        ['Communication', '#17a2b8', 'Evidence of effective communication skills']
    ];
    
    foreach ($defaultTags as $tag) {
        $existing = fetchOne("SELECT tag_id FROM evidence_tags WHERE tag_name = ?", [$tag[0]]);
        if (!$existing) {
            insertRecord("INSERT INTO evidence_tags (tag_name, tag_color, description, created_by) VALUES (?, ?, ?, 1)", 
                        [$tag[0], $tag[1], $tag[2]]);
            echo "   ✓ Added tag: {$tag[0]}\n";
        } else {
            echo "   ✓ Tag already exists: {$tag[0]}\n";
        }
    }
    
    echo "\n=== PHASE 3 SCHEMA APPLICATION COMPLETE ===\n";
    echo "✓ All essential Phase 3 tables and columns created\n";
    echo "✓ Default data inserted\n";
    echo "✓ System ready for Phase 3 functionality\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}