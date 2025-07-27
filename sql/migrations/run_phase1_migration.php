<?php
/**
 * Phase 1 Migration Runner for Continuous Performance Foundation
 * 
 * This script safely executes the Phase 1 migration with proper error handling,
 * rollback capabilities, and verification steps.
 * 
 * Usage: php run_phase1_migration.php [--dry-run] [--force]
 */

require_once __DIR__ . '/../../config/database.php';

class Phase1MigrationRunner {
    private $pdo;
    private $isDryRun = false;
    private $isForced = false;
    private $migrationFile = '2025_07_27_081800_phase1_continuous_performance_foundation.sql';
    
    public function __construct($options = []) {
        $this->isDryRun = $options['dry_run'] ?? false;
        $this->isForced = $options['force'] ?? false;
        
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function run() {
        echo "=== Phase 1 Continuous Performance Migration ===\n";
        echo "Migration: {$this->migrationFile}\n";
        echo "Mode: " . ($this->isDryRun ? "DRY RUN" : "LIVE EXECUTION") . "\n";
        echo "Force: " . ($this->isForced ? "YES" : "NO") . "\n\n";
        
        try {
            // Pre-migration checks
            $this->performPreMigrationChecks();
            
            // Execute migration
            if (!$this->isDryRun) {
                $this->executeMigration();
                $this->verifyMigration();
                $this->populateInitialData();
            } else {
                echo "DRY RUN: Migration would be executed here\n";
            }
            
            echo "\n=== Migration Completed Successfully ===\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            if (!$this->isDryRun) {
                $this->rollbackMigration();
            }
            exit(1);
        }
    }
    
    private function performPreMigrationChecks() {
        echo "Performing pre-migration checks...\n";
        
        // Check if migration already applied
        if (!$this->isForced && $this->isMigrationApplied()) {
            throw new Exception("Migration already applied. Use --force to re-run.");
        }
        
        // Check database connectivity
        $this->pdo->query("SELECT 1");
        echo "✓ Database connection verified\n";
        
        // Check required tables exist
        $requiredTables = ['users', 'employees', 'evaluations', 'competencies', 'company_kpis'];
        foreach ($requiredTables as $table) {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch()) {
                throw new Exception("Required table '{$table}' not found. Run base migrations first.");
            }
        }
        echo "✓ Required tables verified\n";
        
        // Check for existing data that might conflict
        $conflicts = $this->checkForConflicts();
        if (!empty($conflicts) && !$this->isForced) {
            throw new Exception("Data conflicts detected: " . implode(', ', $conflicts) . ". Use --force to override.");
        }
        
        echo "✓ Pre-migration checks passed\n\n";
    }
    
    private function isMigrationApplied() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'one_to_one_sessions'");
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function checkForConflicts() {
        $conflicts = [];
        
        // Check if tables already exist
        $newTables = ['one_to_one_sessions', 'one_to_one_feedback', 'one_to_one_templates'];
        foreach ($newTables as $table) {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->fetch()) {
                $conflicts[] = "Table '{$table}' already exists";
            }
        }
        
        return $conflicts;
    }
    
    private function executeMigration() {
        echo "Executing Phase 1 migration...\n";
        
        $migrationPath = __DIR__ . '/' . $this->migrationFile;
        if (!file_exists($migrationPath)) {
            throw new Exception("Migration file not found: {$migrationPath}");
        }
        
        $sql = file_get_contents($migrationPath);
        if ($sql === false) {
            throw new Exception("Failed to read migration file");
        }
        
        // Remove comments and split by delimiter changes
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Execute the migration
        try {
            $this->pdo->beginTransaction();
            
            // Split SQL into individual statements (handle DELIMITER changes)
            $statements = $this->splitSqlStatements($sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement) || $statement === 'COMMIT;') {
                    continue;
                }
                
                echo "Executing: " . substr($statement, 0, 50) . "...\n";
                $this->pdo->exec($statement);
            }
            
            $this->pdo->commit();
            echo "✓ Migration executed successfully\n";
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Migration execution failed: " . $e->getMessage());
        }
    }
    
    private function splitSqlStatements($sql) {
        $statements = [];
        $current = '';
        $inDelimiter = false;
        $delimiter = ';';
        
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Handle DELIMITER changes
            if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
                $delimiter = trim($matches[1]);
                $inDelimiter = true;
                continue;
            }
            
            $current .= $line . "\n";
            
            // Check if statement ends with current delimiter
            if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
                $statement = trim(substr($current, 0, -strlen($delimiter)));
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $current = '';
                
                // Reset delimiter after procedure/function
                if ($inDelimiter && $delimiter !== ';') {
                    $delimiter = ';';
                    $inDelimiter = false;
                }
            }
        }
        
        // Add any remaining statement
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return $statements;
    }
    
    private function verifyMigration() {
        echo "\nVerifying migration...\n";
        
        // Verify tables were created
        $expectedTables = ['one_to_one_sessions', 'one_to_one_feedback', 'one_to_one_templates'];
        foreach ($expectedTables as $table) {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch()) {
                throw new Exception("Table '{$table}' was not created");
            }
        }
        echo "✓ Tables created successfully\n";
        
        // Verify views were created
        $expectedViews = ['v_employee_competency_feedback', 'v_employee_kpi_feedback'];
        foreach ($expectedViews as $view) {
            $stmt = $this->pdo->prepare("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_performance_evaluation = ?");
            $stmt->execute([$view]);
            if (!$stmt->fetch()) {
                throw new Exception("View '{$view}' was not created");
            }
        }
        echo "✓ Views created successfully\n";
        
        // Verify stored procedures were created
        $expectedProcedures = ['sp_aggregate_1to1_evidence', 'sp_recommend_1to1_agenda'];
        foreach ($expectedProcedures as $procedure) {
            $stmt = $this->pdo->prepare("SHOW PROCEDURE STATUS WHERE Name = ?");
            $stmt->execute([$procedure]);
            if (!$stmt->fetch()) {
                throw new Exception("Procedure '{$procedure}' was not created");
            }
        }
        echo "✓ Stored procedures created successfully\n";
        
        // Verify evaluations table was enhanced
        $stmt = $this->pdo->query("DESCRIBE evaluations");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['related_sessions', 'evidence_summary', 'review_source', 'last_1to1_sync'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                throw new Exception("Column '{$column}' was not added to evaluations table");
            }
        }
        echo "✓ Evaluations table enhanced successfully\n";
        
        echo "✓ Migration verification completed\n";
    }
    
    private function populateInitialData() {
        echo "\nPopulating initial data...\n";
        
        // Check if templates were inserted
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM one_to_one_templates");
        $templateCount = $stmt->fetchColumn();
        
        if ($templateCount > 0) {
            echo "✓ {$templateCount} 1:1 templates inserted\n";
        } else {
            echo "⚠ No 1:1 templates found - this may be expected\n";
        }
        
        // Create sample 1:1 sessions for existing manager-employee relationships
        $this->createSample1to1Sessions();
        
        echo "✓ Initial data population completed\n";
    }
    
    private function createSample1to1Sessions() {
        // Get manager-employee relationships
        $stmt = $this->pdo->query("
            SELECT e.employee_id, e.manager_id, e.first_name, e.last_name,
                   m.first_name as manager_first_name, m.last_name as manager_last_name
            FROM employees e 
            JOIN employees m ON e.manager_id = m.employee_id 
            WHERE e.manager_id IS NOT NULL 
            AND e.active = 1
            LIMIT 5
        ");
        
        $relationships = $stmt->fetchAll();
        
        if (empty($relationships)) {
            echo "⚠ No manager-employee relationships found for sample data\n";
            return;
        }
        
        $insertStmt = $this->pdo->prepare("
            INSERT INTO one_to_one_sessions 
            (employee_id, manager_id, scheduled_date, actual_date, duration_minutes, status, meeting_notes, agenda_items)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $sampleCount = 0;
        foreach ($relationships as $rel) {
            // Create a recent completed session
            $scheduledDate = date('Y-m-d H:i:s', strtotime('-1 week'));
            $actualDate = date('Y-m-d H:i:s', strtotime('-1 week +1 hour'));
            
            $agendaItems = json_encode([
                ['section' => 'Goal Progress', 'time_minutes' => 10],
                ['section' => 'Feedback Exchange', 'time_minutes' => 15],
                ['section' => 'Next Steps', 'time_minutes' => 5]
            ]);
            
            $notes = "Initial 1:1 session - discussed current projects and development goals.";
            
            $insertStmt->execute([
                $rel['employee_id'],
                $rel['manager_id'],
                $scheduledDate,
                $actualDate,
                30,
                'completed',
                $notes,
                $agendaItems
            ]);
            
            $sampleCount++;
        }
        
        echo "✓ Created {$sampleCount} sample 1:1 sessions\n";
    }
    
    private function rollbackMigration() {
        echo "\nAttempting rollback...\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Drop created tables in reverse order
            $tablesToDrop = ['one_to_one_feedback', 'one_to_one_sessions', 'one_to_one_templates'];
            foreach ($tablesToDrop as $table) {
                $this->pdo->exec("DROP TABLE IF EXISTS {$table}");
            }
            
            // Drop views
            $viewsToDrop = ['v_employee_competency_feedback', 'v_employee_kpi_feedback'];
            foreach ($viewsToDrop as $view) {
                $this->pdo->exec("DROP VIEW IF EXISTS {$view}");
            }
            
            // Drop procedures
            $proceduresToDrop = ['sp_aggregate_1to1_evidence', 'sp_recommend_1to1_agenda'];
            foreach ($proceduresToDrop as $procedure) {
                $this->pdo->exec("DROP PROCEDURE IF EXISTS {$procedure}");
            }
            
            // Remove added columns from evaluations (if they exist)
            $columnsToRemove = ['related_sessions', 'evidence_summary', 'review_source', 'last_1to1_sync'];
            foreach ($columnsToRemove as $column) {
                try {
                    $this->pdo->exec("ALTER TABLE evaluations DROP COLUMN {$column}");
                } catch (Exception $e) {
                    // Column might not exist, continue
                }
            }
            
            $this->pdo->commit();
            echo "✓ Rollback completed successfully\n";
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo "✗ Rollback failed: " . $e->getMessage() . "\n";
        }
    }
}

// Parse command line arguments
$options = [
    'dry_run' => in_array('--dry-run', $argv),
    'force' => in_array('--force', $argv)
];

// Run migration
try {
    $runner = new Phase1MigrationRunner($options);
    $runner->run();
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}