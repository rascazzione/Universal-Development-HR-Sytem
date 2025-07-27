<?php
/**
 * Fix Growth Evidence System Tables
 * This script creates the missing Growth Evidence System tables
 */

require_once __DIR__ . '/../config/database.php';

echo "🔧 Fixing Growth Evidence System tables...\n";

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Check if tables already exist
    $tablesToCheck = ['growth_evidence_entries', 'evidence_attachments', 'evidence_evaluation_results', 'evidence_aggregations'];
    $existingTables = [];
    
    foreach ($tablesToCheck as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $existingTables[] = $table;
        }
    }
    
    if (!empty($existingTables)) {
        echo "⚠️  Some Growth Evidence tables already exist: " . implode(', ', $existingTables) . "\n";
        echo "⏭️  Skipping table creation\n";
    } else {
        // Create the growth evidence entries table
        $sql = "
        CREATE TABLE growth_evidence_entries (
            entry_id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            manager_id INT NOT NULL,
            content TEXT NOT NULL,
            star_rating TINYINT(1) CHECK (star_rating BETWEEN 1 AND 5),
            dimension ENUM('responsibilities', 'kpis', 'competencies', 'values') NOT NULL,
            entry_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
            FOREIGN KEY (manager_id) REFERENCES employees(employee_id),
            INDEX idx_employee_period (employee_id, entry_date),
            INDEX idx_dimension (dimension),
            INDEX idx_manager_date (manager_id, entry_date)
        )";
        
        $pdo->exec($sql);
        echo "✅ Created growth_evidence_entries table\n";
        
        // Create the media attachments table
        $sql = "
        CREATE TABLE evidence_attachments (
            attachment_id INT PRIMARY KEY AUTO_INCREMENT,
            entry_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_type ENUM('image', 'video', 'document') NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            storage_path VARCHAR(500) NOT NULL,
            thumbnail_path VARCHAR(500),
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (entry_id) REFERENCES growth_evidence_entries(entry_id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        echo "✅ Created evidence_attachments table\n";
        
        // Create simplified evaluation results table for evidence-based evaluations
        $sql = "
        CREATE TABLE evidence_evaluation_results (
            result_id INT PRIMARY KEY AUTO_INCREMENT,
            evaluation_id INT NOT NULL,
            dimension ENUM('responsibilities', 'kpis', 'competencies', 'values') NOT NULL,
            evidence_count INT NOT NULL,
            avg_star_rating DECIMAL(3,2) NOT NULL,
            total_positive_entries INT NOT NULL,
            total_negative_entries INT NOT NULL,
            calculated_score DECIMAL(4,2) NOT NULL,
            
            FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
            UNIQUE KEY unique_eval_dimension (evaluation_id, dimension)
        )";
        
        $pdo->exec($sql);
        echo "✅ Created evidence_evaluation_results table\n";
        
        // Create a table for storing evidence aggregation statistics
        $sql = "
        CREATE TABLE evidence_aggregations (
            aggregation_id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            period_id INT NOT NULL,
            total_entries INT NOT NULL DEFAULT 0,
            avg_star_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            positive_entries INT NOT NULL DEFAULT 0,
            negative_entries INT NOT NULL DEFAULT 0,
            last_entry_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
            FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id),
            UNIQUE KEY unique_employee_period (employee_id, period_id)
        )";
        
        $pdo->exec($sql);
        echo "✅ Created evidence_aggregations table\n";
    }
    
    // Fix the column names in the ALTER TABLE statements
    $dropColumns = [
        "DROP COLUMN skills_competencies",
        "DROP COLUMN key_responsibilities", 
        "DROP COLUMN living_values"
    ];
    
    foreach ($dropColumns as $column) {
        try {
            $pdo->exec("ALTER TABLE evaluations $column");
            echo "✅ Dropped column: $column\n";
        } catch (Exception $e) {
            echo "⚠️  Column $column not found or already dropped\n";
        }
    }
    
    $pdo->commit();
    echo "✅ Growth Evidence System tables fixed successfully!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Failed to fix Growth Evidence System tables: " . $e->getMessage() . "\n";
    exit(1);
}
?>
