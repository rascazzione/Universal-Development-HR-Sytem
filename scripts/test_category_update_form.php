<?php
/**
 * Test script to simulate category update form submission
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Competency.php';

echo "=== Testing Category Update Form Submission ===\n\n";

// Get a test category
$sql = "SELECT id, category_name, category_type, description FROM competency_categories WHERE is_active = 1 LIMIT 1";
$category = fetchOne($sql);

if (!$category) {
    echo "No categories found to test with\n";
    exit(1);
}

echo "Original category:\n";
echo "  ID: {$category['id']}\n";
echo "  Name: {$category['category_name']}\n";
echo "  Type: {$category['category_type']}\n";
echo "  Description: {$category['description']}\n\n";

// Simulate form data
$_POST = [
    'csrf_token' => 'test_token',
    'action' => 'update_category',
    'category_id' => $category['id'],
    'category_name' => $category['category_name'] . ' (TEST)',
    'category_type' => $category['category_type'] === 'technical' ? 'soft_skill' : 'technical',
    'description' => $category['description'] . ' (UPDATED)'
];

echo "Simulated POST data:\n";
print_r($_POST);

// Initialize competency class
$competencyClass = new Competency();

try {
    // Test the update
    echo "\nAttempting update...\n";
    
    $categoryId = (int)$_POST['category_id'];
    $categoryData = [
        'category_name' => sanitizeInput($_POST['category_name']),
        'description' => sanitizeInput($_POST['description']),
        'parent_id' => null,
        'category_type' => sanitizeInput($_POST['category_type'])
    ];
    
    echo "Sanitized data:\n";
    print_r($categoryData);
    
    $result = $competencyClass->updateCategory($categoryId, $categoryData);
    echo "Update result: $result rows affected\n";
    
    // Verify the update
    $sql = "SELECT * FROM competency_categories WHERE id = ?";
    $updated = fetchOne($sql, [$categoryId]);
    
    echo "\nUpdated category:\n";
    echo "  ID: {$updated['id']}\n";
    echo "  Name: {$updated['category_name']}\n";
    echo "  Type: {$updated['category_type']}\n";
    echo "  Description: {$updated['description']}\n";
    
    // Revert changes
    echo "\nReverting changes...\n";
    $revertData = [
        'category_name' => $category['category_name'],
        'description' => $category['description'],
        'parent_id' => null,
        'category_type' => $category['category_type']
    ];
    
    $competencyClass->updateCategory($categoryId, $revertData);
    echo "Changes reverted\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";