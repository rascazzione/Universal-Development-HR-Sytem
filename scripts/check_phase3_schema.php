<?php
require_once __DIR__ . '/../config/config.php';

echo "Checking Phase 3 database schema...\n";

// Check Phase 3 tables
$tables = ['notifications', 'evidence_tags', 'evidence_archives'];
foreach ($tables as $table) {
    $exists = fetchOne("SHOW TABLES LIKE '$table'");
    echo "$table: " . (empty($exists) ? 'MISSING' : 'EXISTS') . "\n";
}

echo "\nChecking evidence table structure...\n";
$columns = fetchAll('DESCRIBE growth_evidence_entries');
$hasArchived = false;
foreach ($columns as $col) {
    if ($col['Field'] === 'is_archived') {
        $hasArchived = true;
        break;
    }
}
echo "is_archived column: " . ($hasArchived ? 'EXISTS' : 'MISSING') . "\n";