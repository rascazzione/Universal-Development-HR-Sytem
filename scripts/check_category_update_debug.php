<?php
/**
 * Debug script to check category update functionality
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Category Update Debug Script ===\n\n";

// Check if category_type column exists
echo "1. Checking if category_type column exists in competency_categories table...\n";
$sql = "DESCRIBE competency_categories";
$result = fetchAll($sql);

$categoryTypeExists = false;
foreach ($result as $column) {
    if ($column['Field'] === 'category_type') {
        $categoryTypeExists = true;
        echo "   ✓ category_type column found\n";
        echo "   Type: " . $column['Type'] . "\n";
        echo "   Default: " . $column['Default'] . "\n";
        break;
    }
}

if (!$categoryTypeExists) {
    echo "   ✗ category_type column NOT found\n";
    echo "   This is the problem - the migration hasn't been applied\n";
    echo "\n   To fix this, run:\n";
    echo "   mysql -u root -p performance_evaluation < sql/migrate_category_type.sql\n";
    exit(1);
}

// Check current categories
echo "\n2. Checking current categories in database...\n";
$sql = "SELECT id, category_name, category_type FROM competency_categories WHERE is_active = 1";
$categories = fetchAll($sql);

foreach ($categories as $category) {
    echo "   ID: {$category['id']}, Name: {$category['category_name']}, Type: {$category['category_type']}\n";
}

// Test update functionality
echo "\n3. Testing update functionality...\n";
if (!empty($categories)) {
    $testCategory = $categories[0];
    $originalType = $testCategory['category_type'];
    $newType = $originalType === 'technical' ? 'soft_skill' : 'technical';
    
    echo "   Testing update on category ID: {$testCategory['id']}\n";
    echo "   Original type: $originalType\n";
    echo "   New type: $newType\n";
    
    try {
        $sql = "UPDATE competency_categories SET category_type = ? WHERE id = ?";
        $result = updateRecord($sql, [$newType, $testCategory['id']]);
        echo "   Update result: $result rows affected\n";
        
        // Verify the change
        $sql = "SELECT category_type FROM competency_categories WHERE id = ?";
        $updated = fetchOne($sql, [$testCategory['id']]);
        echo "   Verified new type: " . $updated['category_type'] . "\n";
        
        // Revert the change
        $sql = "UPDATE competency_categories SET category_type = ? WHERE id = ?";
        updateRecord($sql, [$originalType, $testCategory['id']]);
        echo "   Reverted to original type\n";
        
    } catch (Exception $e) {
        echo "   ✗ Update failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   No categories found to test\n";
}

echo "\n=== Debug Complete ===\n";