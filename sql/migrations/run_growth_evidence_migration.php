<?php
/**
 * Run Growth Evidence System Migration
 * This script applies the database changes for the new evidence-based evaluation system
 */

require_once __DIR__ . '/../../config/database.php';

// Get database connection
$pdo = getDbConnection();

// Read the migration SQL file
$migrationFile = __DIR__ . '/2025_07_27_090000_create_growth_evidence_system.sql';
if (!file_exists($migrationFile)) {
    die("Migration file not found: $migrationFile\n");
}

$sql = file_get_contents($migrationFile);

// Split SQL into statements (handling delimiters properly)
$statements = preg_split('/;(?=\s*(?:--|$))/m', $sql);

$transactionActive = false;

foreach ($statements as $statement) {
    $statement = trim($statement);
    // Skip empty statements and comments
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    // Skip comments within statements
    $lines = explode("\n", $statement);
    $cleanStatement = "";
    foreach ($lines as $line) {
        if (strpos(trim($line), '--') !== 0) {
            $cleanStatement .= $line . "\n";
        }
    }
    $cleanStatement = trim($cleanStatement);
    
    if (!empty($cleanStatement)) {
        try {
            echo "Executing: " . substr($cleanStatement, 0, 50) . "...\n";
            $pdo->exec($cleanStatement);
        } catch (Exception $e) {
            echo "Warning: " . $e->getMessage() . " (This may be expected for DROP statements)\n";
            // Continue with other statements even if one fails
        }
    }
}

echo "Migration completed!\n";
?>
