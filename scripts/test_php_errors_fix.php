<?php
/**
 * Test script to verify PHP errors are fixed in evaluation edit page
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Evaluation.php';

echo "=== Testing PHP Errors Fix ===\n\n";

// Test 1: Verify normalization methods work correctly
echo "Test 1: Testing Data Normalization\n";
try {
    $evaluationClass = new Evaluation();
    
    // Test the normalization methods
    $reflection = new ReflectionClass($evaluationClass);
    
    // Test KPI normalization
    $kpisMethod = $reflection->getMethod('normalizeKPIs');
    $kpisMethod->setAccessible(true);
    
    $testKPIs = [
        ['id' => 1, 'kpi_name' => 'Test KPI', 'category' => 'Test', 'target_value' => 100, 'measurement_unit' => '%']
    ];
    
    $normalizedKPIs = $kpisMethod->invoke($evaluationClass, $testKPIs);
    if (isset($normalizedKPIs[0]['kpi_id']) && $normalizedKPIs[0]['kpi_id'] === 'kpi_1') {
        echo "✓ KPI normalization works correctly\n";
    } else {
        echo "✗ KPI normalization failed\n";
    }
    
    // Test responsibility normalization
    $respMethod = $reflection->getMethod('normalizeResponsibilities');
    $respMethod->setAccessible(true);
    
    $testResponsibilities = [
        ['id' => 1, 'responsibility_text' => 'Test Responsibility', 'sort_order' => 1]
    ];
    
    $normalizedResponsibilities = $respMethod->invoke($evaluationClass, $testResponsibilities);
    if (isset($normalizedResponsibilities[0]['responsibility_id']) && $normalizedResponsibilities[0]['responsibility_id'] === 'resp_1') {
        echo "✓ Responsibility normalization works correctly\n";
    } else {
        echo "✗ Responsibility normalization failed\n";
    }
    
    // Test competency normalization
    $compMethod = $reflection->getMethod('normalizeCompetencies');
    $compMethod->setAccessible(true);
    
    $testCompetencies = [
        ['id' => 1, 'competency_name' => 'Test Competency', 'category_name' => 'Test']
    ];
    
    $normalizedCompetencies = $compMethod->invoke($evaluationClass, $testCompetencies);
    if (isset($normalizedCompetencies[0]['competency_id']) && $normalizedCompetencies[0]['competency_id'] === 'comp_1') {
        echo "✓ Competency normalization works correctly\n";
    } else {
        echo "✗ Competency normalization failed\n";
    }
    
    // Test value normalization
    $valMethod = $reflection->getMethod('normalizeValues');
    $valMethod->setAccessible(true);
    
    $testValues = [
        ['id' => 1, 'value_name' => 'Test Value', 'description' => 'Test Description']
    ];
    
    $normalizedValues = $valMethod->invoke($evaluationClass, $testValues);
    if (isset($normalizedValues[0]['value_id']) && $normalizedValues[0]['value_id'] === 'val_1') {
        echo "✓ Value normalization works correctly\n";
    } else {
        echo "✗ Value normalization failed\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 2: Testing Evaluation Data Structure\n";
try {
    $evaluationClass = new Evaluation();
    
    // Get an existing evaluation
    $evaluations = fetchAll("SELECT evaluation_id FROM evaluations LIMIT 1");
    if (!empty($evaluations)) {
        $evaluationId = $evaluations[0]['evaluation_id'];
        $evaluationData = $evaluationClass->getEvidenceEvaluation($evaluationId);
        
        if ($evaluationData) {
            // Check that all responsibility items have responsibility_id
            $responsibilityErrors = 0;
            foreach ($evaluationData['responsibility_results'] as $responsibility) {
                if (!isset($responsibility['responsibility_id']) || $responsibility['responsibility_id'] === null) {
                    $responsibilityErrors++;
                }
            }
            
            if ($responsibilityErrors === 0) {
                echo "✓ All responsibilities have responsibility_id\n";
            } else {
                echo "✗ Found $responsibilityErrors responsibilities without responsibility_id\n";
            }
            
            // Check that all competency items have competency_id
            $competencyErrors = 0;
            foreach ($evaluationData['competency_results'] as $competency) {
                if (!isset($competency['competency_id']) || $competency['competency_id'] === null) {
                    $competencyErrors++;
                }
            }
            
            if ($competencyErrors === 0) {
                echo "✓ All competencies have competency_id\n";
            } else {
                echo "✗ Found $competencyErrors competencies without competency_id\n";
            }
            
            // Check that all KPI items have kpi_id
            $kpiErrors = 0;
            foreach ($evaluationData['kpi_results'] as $kpi) {
                if (!isset($kpi['kpi_id']) || $kpi['kpi_id'] === null) {
                    $kpiErrors++;
                }
            }
            
            if ($kpiErrors === 0) {
                echo "✓ All KPIs have kpi_id\n";
            } else {
                echo "✗ Found $kpiErrors KPIs without kpi_id\n";
            }
            
            // Check that all value items have value_id
            $valueErrors = 0;
            foreach ($evaluationData['value_results'] as $value) {
                if (!isset($value['value_id']) || $value['value_id'] === null) {
                    $valueErrors++;
                }
            }
            
            if ($valueErrors === 0) {
                echo "✓ All values have value_id\n";
            } else {
                echo "✗ Found $valueErrors values without value_id\n";
            }
            
            // Check that all items have the required fields for the edit page
            $allItemsValid = true;
            
            foreach ($evaluationData['responsibility_results'] as $responsibility) {
                if (!isset($responsibility['sort_order']) || !isset($responsibility['responsibility_text'])) {
                    $allItemsValid = false;
                    break;
                }
            }
            
            if ($allItemsValid) {
                echo "✓ All responsibilities have required fields\n";
            } else {
                echo "✗ Some responsibilities missing required fields\n";
            }
        } else {
            echo "✗ Could not get evaluation data\n";
        }
    } else {
        echo "! No evaluations found for testing\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 3: PHP Syntax Check\n";
try {
    $evaluationFile = __DIR__ . '/../classes/Evaluation.php';
    $output = [];
    $returnCode = 0;
    exec("php -l $evaluationFile 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✓ Evaluation class has no syntax errors\n";
    } else {
        echo "✗ Evaluation class has syntax errors: " . implode("\n", $output) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== PHP Errors Fix Summary ===\n";
echo "✅ Fixed Issues:\n";
echo "  - Added responsibility_id field to all responsibility items\n";
echo "  - Added competency_id field to all competency items\n";
echo "  - Added kpi_id field to all KPI items\n";
echo "  - Added value_id field to all value items\n";
echo "  - All items now have proper ID fields for form processing\n";
echo "  - Normalization ensures consistent data structure\n";
echo "\n🎯 Expected Result:\n";
echo "  - No more 'Undefined array key' errors in evaluation edit page\n";
echo "  - No more 'strpos(): Passing null' deprecation warnings\n";
echo "  - All form fields will have proper names and IDs\n";
echo "  - Evaluation edit page should work without PHP errors\n";
echo "\n📋 Test URL:\n";
echo "  - Evaluation Edit: http://localhost:8080/evaluation/edit.php?id=34\n";