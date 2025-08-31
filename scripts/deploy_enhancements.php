<?php
<?php
/**
 * Comprehensive Migration and Deployment Script
 * Safely deploys comprehensive performance enhancements with rollback capability
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class ComprehensiveDeploymentManager {
    private $db;
    private $testResults = [];
    private $backupPath;
    private $deploymentLog = [];
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->backupPath = __DIR__ . '/../backups/';
        
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        
        $this->runCompleteDeployment();
    }
    
    public function runCompleteDeployment() {
        echo "\n=== Comprehensive Performance Enhancement Deployment ===\n\n";
        
        $this->performPreDeploymentChecks();
        $this->createSystemBackup();
        $this->deployDatabaseSchema();
        $this->validateSchemaIntegrity();
        $this->deployApplicationFiles();
        $this->updateConfiguration();
        $this->runDataValidation();
        $this->testAllIntegrations();
        $this->performFinalVerification();
        $this->generateDeploymentReport();
        
        echo "\n=== Deployment Complete ===\n";
        $this->printSummary();
    }
    
    private function performPreDeploymentChecks() {
        echo "\n1. Pre-Deployment System Checks...\n";
        
        // Check PHP version compatibility
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $this->recordSuccess('Pre-Check', 'PHP version '.PHP_VERSION.' compatible');
        } else {
            $this->recordFailure('Pre-Check', 'PHP '.PHP_VERSION.' not supported. Requires 7.4+');
        }
        
        // Check MySQL version and features
        $stmt = $this->db->query("SELECT VERSION()");
        $mysqlVersion = $stmt->fetchColumn();
        if (version_compare($mysqlVersion, '8.0.0', '>=')) {
            $this->recordSuccess('Pre-Check', 'MySQL version '.explode('-', $mysqlVersion)[0].' compatible');
        } else {
            $this->recordWarning('Pre-Check', 'Older MySQL version detected. Some features may be limited');
        }
        
        // Check required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->recordSuccess('Pre-Check', "Extension $ext loaded");
            } else {
                $this->recordFailure('Pre-Check', "Required extension $ext missing");
            }
        }
        
        // Check disk space (minimum 500MB)
        $freeSpace = disk_free_space(__DIR__);
        if ($freeSpace > 500 * 1024 * 1024) {
            $this->recordSuccess('Pre-Check', 'Sufficient disk space available');
        } else {
            $this->recordFailure('Pre-Check', 'Insufficient disk space for deployment');
        }
    }
    
    private function createSystemBackup() {
        echo "\n2. Creating System Backup...\n";
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupDir = $this->backupPath . "deployment_$timestamp/";
        mkdir($backupDir, 0755, true);
        
        // Backup database
        $this->backupDatabase($backupDir . 'database_backup.sql');
        
        // Backup configuration
        $this->backupFiles($backupDir);
        
        // Create rollback script
        $this->createRollbackScript($backupDir);
        
        $this->recordSuccess('Backup', 'Complete system backup created at: ' . $backupDir);
    }
    
    private function backupDatabase($backupFile) {
        try {
            // Create comprehensive database backup
            $tables = [
                'employees', 'evaluation_periods', 'enhanced_self_assessments',
                'enhanced_achievements', 'enhanced_okrs', 'enhanced_idps',
                'kudos_points', 'upward_feedback', 'enhanced_evaluations'
            ];
            
            file_put_contents($backupFile, "-- Comprehensive Database Backup\n-- Created: " . date('Y-m-d H:i:s') . "\n\n");
            
            foreach ($tables as $table) {
                $stmt = $this->db->prepare("SHOW CREATE TABLE $table");
                $stmt->execute();
                $createResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                file_put_contents($backupFile, "\n\n-- Table: $table\n", FILE_APPEND);
                file_put_contents($backupFile, $createResult['Create Table'] . ";\n\n", FILE_APPEND);
                
                // Data backup
                $stmt = $this->db->prepare("SELECT * FROM $table");
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($data as $row) {
                    $columns = array_keys($row);
                    $placeholders = array_fill(0, count($columns), '?');
                    $values = array_values($row);
                    
                    $insertSQL = "INSERT INTO $table (" . implode(',', $columns) . ") 
                                  VALUES (" . implode(',', $placeholders) . ")";
                    
                    file_put_contents($backupFile, preg_replace('/\?/', "'%s'", $insertSQL) . ";\n", FILE_APPEND);
                }
            }
            
            $this->recordSuccess('Database Backup', 'Comprehensive database backup completed');
            
        } catch (Exception $e) {
            $this->recordFailure('Database Backup', 'Backup failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function backupFiles($backupDir) {
        // Backup critical files
        $filesToBackup = [
            'config/config.php' => 'config_backup.php',
            'config/database.php' => 'database_backup.php',
            'sql/004_comprehensive_enhancements.sql' => 'schema_backup.sql',
            'classes/*.php' => 'classes_backup.tar.gz'
        ];
        
        foreach ($filesToBackup as $source => $backupName) {
            $sourcePath = __