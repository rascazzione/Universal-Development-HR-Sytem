<?php
/**
 * Database Migration Runner
 * Handles execution and tracking of database migrations
 */

require_once __DIR__ . '/../config/config.php';

class MigrationRunner {
    private $pdo;
    private $migrationsPath;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        $this->migrationsPath = __DIR__ . '/migrations/';
    }
    
    /**
     * Run all pending migrations
     */
    public function runPendingMigrations() {
        $this->ensureMigrationTable();
        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            echo "No pending migrations found.\n";
            return 0;
        }
        
        foreach ($pendingMigrations as $migration) {
            $this->executeMigration($migration);
        }
        
        return count($pendingMigrations);
    }
    
    /**
     * Get migration status
     */
    public function getMigrationStatus() {
        $this->ensureMigrationTable();
        
        $sql = "SELECT version, filename, status, executed_at, execution_time_ms, description 
                FROM schema_migrations 
                ORDER BY version";
        return fetchAll($sql);
    }
    
    /**
     * Rollback specific migration
     */
    public function rollbackMigration($version) {
        $sql = "SELECT rollback_sql FROM schema_migrations WHERE version = ? AND status = 'completed'";
        $migration = fetchOne($sql, [$version]);
        
        if (!$migration || !$migration['rollback_sql']) {
            throw new Exception("Cannot rollback migration $version - no rollback script available");
        }
        
        $startTime = microtime(true);
        
        try {
            $this->pdo->beginTransaction();
            
            // Execute rollback
            $this->pdo->exec($migration['rollback_sql']);
            
            // Update migration status
            $updateSql = "UPDATE schema_migrations SET status = 'rolled_back' WHERE version = ?";
            executeQuery($updateSql, [$version]);
            
            $this->pdo->commit();
            
            $executionTime = round((microtime(true) - $startTime) * 1000);
            echo "✅ Rolled back migration: $version ({$executionTime}ms)\n";
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception("Rollback failed for $version: " . $e->getMessage());
        }
    }
    
    /**
     * Validate all migrations
     */
    public function validateMigrations() {
        $issues = [];
        $migrations = glob($this->migrationsPath . '*.sql');
        
        foreach ($migrations as $file) {
            $version = $this->extractVersionFromFilename(basename($file));
            
            // Check filename format
            if (!preg_match('/^\d{3}_[a-z_]+\.sql$/', basename($file))) {
                $issues[] = "Invalid filename format: " . basename($file);
            }
            
            // Check file readability
            if (!is_readable($file)) {
                $issues[] = "Cannot read migration file: " . basename($file);
            }
            
            // Check for SQL syntax (basic)
            $content = file_get_contents($file);
            if (empty(trim($content))) {
                $issues[] = "Empty migration file: " . basename($file);
            }
            
            // Check for dangerous operations
            if (preg_match('/DROP\s+DATABASE/i', $content)) {
                $issues[] = "Dangerous operation (DROP DATABASE) in: " . basename($file);
            }
        }
        
        return $issues;
    }
    
    /**
     * Create a new migration file
     */
    public function createMigration($description) {
        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . strtolower(str_replace(' ', '_', $description)) . '.sql';
        $filepath = $this->migrationsPath . $filename;
        
        $template = "-- Migration: $description\n";
        $template .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        $template .= "USE performance_evaluation;\n\n";
        $template .= "-- Start transaction for safety\n";
        $template .= "START TRANSACTION;\n\n";
        $template .= "-- Add your migration SQL here\n\n";
        $template .= "-- Commit the transaction\n";
        $template .= "COMMIT;\n\n";
        $template .= "SELECT 'Migration $description completed successfully' as result;\n";
        
        if (file_put_contents($filepath, $template)) {
            echo "✅ Created migration: $filename\n";
            return $filename;
        } else {
            throw new Exception("Failed to create migration file: $filename");
        }
    }
    
    private function ensureMigrationTable() {
        $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
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
            INDEX idx_status (status)
        )";
        $this->pdo->exec($sql);
    }
    
    private function getPendingMigrations() {
        $files = glob($this->migrationsPath . '*.sql');
        $executed = $this->getExecutedMigrations();
        $pending = [];
        
        foreach ($files as $file) {
            $version = $this->extractVersionFromFilename(basename($file));
            if (!in_array($version, $executed)) {
                $pending[] = [
                    'version' => $version,
                    'filename' => basename($file),
                    'path' => $file
                ];
            }
        }
        
        // Sort by version
        usort($pending, function($a, $b) {
            return strcmp($a['version'], $b['version']);
        });
        
        return $pending;
    }
    
    private function getExecutedMigrations() {
        $sql = "SELECT version FROM schema_migrations WHERE status = 'completed'";
        $result = fetchAll($sql);
        return array_column($result, 'version');
    }
    
    private function executeMigration($migration) {
        $startTime = microtime(true);
        
        try {
            echo "🔄 Starting migration: {$migration['version']}\n";
            
            // Mark as running
            $this->recordMigrationStart($migration);
            echo "📝 Recorded migration start\n";
            
            echo "🔒 Beginning transaction\n";
            $this->pdo->beginTransaction();
            
            // Read and execute migration
            $sql = file_get_contents($migration['path']);
            $checksum = md5($sql);
            
            echo "📄 Executing migration SQL\n";
            // Execute the migration SQL
            $this->pdo->exec($sql);
            
            echo "✏️ Recording migration completion\n";
            // Mark as completed
            $executionTime = round((microtime(true) - $startTime) * 1000);
            $this->recordMigrationComplete($migration, $executionTime, $checksum);
            
            echo "✅ Committing transaction\n";
            $this->pdo->commit();
            
            echo "✅ Executed migration: {$migration['version']} ({$executionTime}ms)\n";
            
        } catch (Exception $e) {
            echo "❌ Error occurred: " . $e->getMessage() . "\n";
            echo "🔄 Attempting rollback\n";
            try {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollback();
                    echo "✅ Rollback successful\n";
                } else {
                    echo "⚠️ No active transaction to rollback\n";
                }
            } catch (Exception $rollbackError) {
                echo "❌ Rollback failed: " . $rollbackError->getMessage() . "\n";
            }
            $this->recordMigrationFailed($migration, $e->getMessage());
            throw new Exception("Migration {$migration['version']} failed: " . $e->getMessage());
        }
    }
    
    private function extractVersionFromFilename($filename) {
        return preg_replace('/\.sql$/', '', $filename);
    }
    
    private function recordMigrationStart($migration) {
        $sql = "INSERT INTO schema_migrations (version, filename, status)
                VALUES (?, ?, 'running')
                ON DUPLICATE KEY UPDATE status = 'running'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$migration['version'], $migration['filename']]);
    }
    
    private function recordMigrationComplete($migration, $executionTime, $checksum) {
        $sql = "UPDATE schema_migrations
                SET status = 'completed', execution_time_ms = ?, checksum = ?, executed_at = NOW()
                WHERE version = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$executionTime, $checksum, $migration['version']]);
    }
    
    private function recordMigrationFailed($migration, $error) {
        $sql = "UPDATE schema_migrations
                SET status = 'failed'
                WHERE version = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$migration['version']]);
        
        error_log("Migration failed: {$migration['version']} - $error");
    }
}

// CLI interface
if (php_sapi_name() === 'cli') {
    $runner = new MigrationRunner();
    $command = $argv[1] ?? 'status';
    
    try {
        switch ($command) {
            case 'up':
            case 'migrate':
                $count = $runner->runPendingMigrations();
                echo "Executed $count migrations\n";
                break;
                
            case 'status':
                $status = $runner->getMigrationStatus();
                echo "Migration Status:\n";
                echo str_pad("Version", 30) . str_pad("Status", 15) . str_pad("Executed At", 20) . "Description\n";
                echo str_repeat("-", 80) . "\n";
                foreach ($status as $migration) {
                    echo str_pad($migration['version'], 30) . 
                         str_pad($migration['status'], 15) . 
                         str_pad($migration['executed_at'] ?? 'not executed', 20) . 
                         ($migration['description'] ?? '') . "\n";
                }
                break;
                
            case 'rollback':
                $version = $argv[2] ?? null;
                if (!$version) {
                    echo "Usage: php migration_runner.php rollback <version>\n";
                    exit(1);
                }
                $runner->rollbackMigration($version);
                break;
                
            case 'validate':
                $issues = $runner->validateMigrations();
                if (empty($issues)) {
                    echo "✅ All migrations are valid\n";
                } else {
                    echo "❌ Migration issues found:\n";
                    foreach ($issues as $issue) {
                        echo "  - $issue\n";
                    }
                    exit(1);
                }
                break;
                
            case 'create':
                $description = $argv[2] ?? null;
                if (!$description) {
                    echo "Usage: php migration_runner.php create \"Description of migration\"\n";
                    exit(1);
                }
                $filename = $runner->createMigration($description);
                echo "Edit the migration file: sql/migrations/$filename\n";
                break;
                
            default:
                echo "Usage: php migration_runner.php [up|status|rollback|validate|create]\n";
                echo "Commands:\n";
                echo "  up/migrate           Run pending migrations\n";
                echo "  status               Show migration status\n";
                echo "  rollback <version>   Rollback specific migration\n";
                echo "  validate             Validate migration files\n";
                echo "  create \"description\"  Create new migration file\n";
                exit(1);
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>