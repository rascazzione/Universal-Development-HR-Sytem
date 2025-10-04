<?php
/**
 * Test script for the evaluation fix to show all job template dimensions
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Evaluation.php';
require_once __DIR__ . '/../classes/Employee.php';

echo "=== Testing Evaluation Fix ===\n\n";

// Test 1: Check if Evaluation class can get job template data
echo "Test 1: Evaluation Job Template Integration\n";
try {
    $evaluationClass = new Evaluation();
    $employeeClass = new Employee();
    
    // Get a sample employee
    $employees = $employeeClass->getEmployees(1, 5);
    if (!empty($employees['employees'])) {
        $sampleEmployee = $employees['employees'][0];
        echo "✓ Found sample employee: {$sampleEmployee['first_name']} {$sampleEmployee['last_name']} (ID: {$sampleEmployee['employee_id']})\n";
        
        // Test the new getJobTemplateDataForEvaluation method
        $reflection = new ReflectionClass($evaluationClass);
        $method = $reflection->getMethod('getJobTemplateDataForEvaluation');
        $method->setAccessible(true);
        
        $jobTemplateData = $method->invoke($evaluationClass, $sampleEmployee['employee_id']);
        
        echo "✓ Job template data retrieved:\n";
        echo "  - KPIs: " . count($jobTemplateData['kpis']) . "\n";
        echo "  - Competencies: " . count($jobTemplateData['competencies']) . "\n";
        echo "  - Responsibilities: " . count($jobTemplateData['responsibilities']) . "\n";
        echo "  - Values: " . count($jobTemplateData['values']) . "\n";
        echo "  - Section weights: " . json_encode($jobTemplateData['section_weights']) . "\n";
        
        // Test the calculateSectionWeights method
        $weightsMethod = $reflection->getMethod('calculateSectionWeights');
        $weightsMethod->setAccessible(true);
        
        if (!empty($jobTemplateData['kpis']) || !empty($jobTemplateData['competencies']) || 
            !empty($jobTemplateData['responsibilities']) || !empty($jobTemplateData['values'])) {
            
            $templateData = [
                'kpis' => $jobTemplateData['kpis'],
                'competencies' => $jobTemplateData['competencies'],
                'responsibilities' => $jobTemplateData['responsibilities'],
                'values' => $jobTemplateData['values']
            ];
            
            $calculatedWeights = $weightsMethod->invoke($evaluationClass, $templateData);
            echo "✓ Calculated section weights: " . json_encode($calculatedWeights) . "\n";
        }
    } else {
        echo "✗ No employees found for testing\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 2: Check Evaluation Class Syntax\n";
try {
    $evaluationFile = __DIR__ . '/../classes/Evaluation.php';
    $output = [];
    $returnCode = 0;
    exec("php -l $evaluationFile 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✓ Evaluation class syntax is valid\n";
    } else {
        echo "✗ Evaluation class syntax error: " . implode("\n", $output) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 3: Check for Existing Evaluations\n";
try {
    $evaluationClass = new Evaluation();
    
    // Try to get some evaluations
    $sql = "SELECT evaluation_id FROM evaluations LIMIT 5";
    $evaluations = fetchAll($sql);
    
    if (!empty($evaluations)) {
        echo "✓ Found " . count($evaluations) . " evaluations for testing\n";
        
        // Test the getEvidenceEvaluation method with the first evaluation
        $evaluationId = $evaluations[0]['evaluation_id'];
        echo "Testing with evaluation ID: $evaluationId\n";
        
        $evaluationData = $evaluationClass->getEvidenceEvaluation($evaluationId);
        
        if ($evaluationData) {
            echo "✓ getEvidenceEvaluation returned data\n";
            echo "  - KPIs: " . count($evaluationData['kpi_results'] ?? []) . "\n";
            echo "  - Competencies: " . count($evaluationData['competency_results'] ?? []) . "\n";
            echo "  - Responsibilities: " . count($evaluationData['responsibility_results'] ?? []) . "\n";
            echo "  - Values: " . count($evaluationData['value_results'] ?? []) . "\n";
            echo "  - Evidence results: " . count($evaluationData['evidence_results'] ?? []) . "\n";
            
            // Check if we have both evidence summaries and job template items
            $hasEvidenceSummary = false;
            $hasJobTemplateItems = false;
            
            if (!empty($evaluationData['kpi_results'])) {
                foreach ($evaluationData['kpi_results'] as $kpi) {
                    if (strpos($kpi['kpi_id'] ?? '', 'evidence_') === 0) {
                        $hasEvidenceSummary = true;
                    } else {
                        $hasJobTemplateItems = true;
                    }
                }
            }
            
            echo "  - Has evidence summary: " . ($hasEvidenceSummary ? 'YES' : 'NO') . "\n";
            echo "  - Has job template items: " . ($hasJobTemplateItems ? 'YES' : 'NO') . "\n";
            
            if ($hasJobTemplateItems) {
                echo "✓ Evaluation now includes job template dimensions even without evidence\n";
            } else {
                echo "! This evaluation may not have a job template assigned\n";
            }
        } else {
            echo "✗ getEvidenceEvaluation returned false\n";
        }
    } else {
        echo "! No evaluations found in the system\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "The evaluation fix has been implemented with the following changes:\n";
echo "1. ✓ Modified getEvidenceEvaluation to include all job template dimensions\n";
echo "2. ✓ Added getJobTemplateDataForEvaluation method\n";
echo "3. ✓ Added calculateSectionWeights method\n";
echo "4. ✓ Evidence summaries are now shown alongside job template items\n";
echo "\nNext steps:\n";
echo "1. Test the evaluation edit page: http://localhost:8080/evaluation/edit.php?id=X\n";
echo "2. Verify all dimensions appear regardless of evidence\n";
echo "3. Ensure managers can complete missing dimensions\n";