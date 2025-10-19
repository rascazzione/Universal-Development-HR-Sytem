<?php
/**
 * Migration Script: Move competency_type from competencies to competency_categories
 * This script executes the SQL migration to move types from individual competencies to their parent categories
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "Starting migration: Moving competency types to categories...\n\n";

try {
    // Step 1: Add category_type column to competency_categories table
    echo "Step 1: Adding category_type column to competency_categories table...\n";
    
    // Check if column already exists
    $checkSql = "SELECT COUNT(*) as column_exists
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                 AND table_name = 'competency_categories'
                 AND column_name = 'category_type'";
    
    $result = fetchOne($checkSql);
    
    if ($result['column_exists'] == 0) {
        $sql = "ALTER TABLE competency_categories
                ADD COLUMN category_type ENUM('technical', 'soft_skill') DEFAULT 'technical'";
        $result = executeQuery($sql);
        echo "✓ Column added successfully\n";
    } else {
        echo "✓ Column already exists, skipping...\n";
    }

    // Step 2: Update category types based on existing competency types
    echo "\nStep 2: Updating category types based on existing competencies...\n";
    
    // First, get all categories and their competencies
    $categoriesSql = "SELECT cc.id, cc.category_name, 
                      GROUP_CONCAT(c.competency_type) as competency_types
                      FROM competency_categories cc
                      LEFT JOIN competencies c ON cc.id = c.category_id AND c.is_active = 1
                      WHERE cc.is_active = 1
                      GROUP BY cc.id";
    
    $categories = fetchAll($categoriesSql);
    
    foreach ($categories as $category) {
        $types = explode(',', $category['competency_types']);
        $types = array_filter($types); // Remove empty values
        
        // If any competency is soft_skill, make the category soft_skill
        if (in_array('soft_skill', $types)) {
            $categoryType = 'soft_skill';
        } else {
            $categoryType = 'technical';
        }
        
        $updateSql = "UPDATE competency_categories 
                      SET category_type = ? 
                      WHERE id = ?";
        updateRecord($updateSql, [$categoryType, $category['id']]);
        
        echo "  - Category '{$category['category_name']}' set to {$categoryType}\n";
    }
    echo "✓ Category types updated\n";

    // Step 3: Remove competency_type column from competencies table
    echo "\nStep 3: Removing competency_type column from competencies table...\n";
    
    // Check if column exists before trying to drop it
    $checkSql = "SELECT COUNT(*) as column_exists
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                 AND table_name = 'competencies'
                 AND column_name = 'competency_type'";
    
    $result = fetchOne($checkSql);
    
    if ($result['column_exists'] > 0) {
        $sql = "ALTER TABLE competencies DROP COLUMN competency_type";
        $result = executeQuery($sql);
        echo "✓ Column removed successfully\n";
    } else {
        echo "✓ Column already removed, skipping...\n";
    }

    // Step 4: Add index for better performance
    echo "\nStep 4: Adding index for better performance...\n";
    
    // Check if index already exists
    $checkIndexSql = "SELECT COUNT(*) as index_exists
                      FROM information_schema.statistics
                      WHERE table_schema = DATABASE()
                      AND table_name = 'competency_categories'
                      AND index_name = 'idx_category_type'";
    
    $result = fetchOne($checkIndexSql);
    
    if ($result['index_exists'] == 0) {
        $sql = "CREATE INDEX idx_category_type ON competency_categories(category_type)";
        $result = executeQuery($sql);
        echo "✓ Index created successfully\n";
    } else {
        echo "✓ Index already exists, skipping...\n";
    }

    echo "\n🎉 Migration completed successfully!\n";
    echo "\nSummary:\n";
    echo "- Added category_type column to competency_categories table\n";
    echo "- Updated all categories with appropriate types based on their competencies\n";
    echo "- Removed competency_type column from competencies table\n";
    echo "- Created index for better query performance\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Please check the error and try again.\n";
    exit(1);
}
?>