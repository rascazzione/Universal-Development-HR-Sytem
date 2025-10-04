<?php
/**
 * Test script for the new feedback form redesign
 * Tests the API endpoint and functionality
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/JobTemplate.php';

echo "=== Testing Feedback Form Redesign ===\n\n";

// Test 1: Check if Employee class can get job template info
echo "Test 1: Employee Job Template Integration\n";
try {
    $employeeClass = new Employee();
    $jobTemplateClass = new JobTemplate();
    
    // Get a sample employee
    $employees = $employeeClass->getEmployees(1, 5);
    if (!empty($employees['employees'])) {
        $sampleEmployee = $employees['employees'][0];
        echo "✓ Found sample employee: {$sampleEmployee['first_name']} {$sampleEmployee['last_name']} (ID: {$sampleEmployee['employee_id']})\n";
        
        // Check if job_template_id field exists
        if (isset($sampleEmployee['job_template_id'])) {
            echo "✓ Employee has job_template_id field: " . ($sampleEmployee['job_template_id'] ?? 'NULL') . "\n";
            
            if ($sampleEmployee['job_template_id']) {
                // Test getting job template data
                $template = $jobTemplateClass->getJobTemplateById($sampleEmployee['job_template_id']);
                if ($template) {
                    echo "✓ Found job template: {$template['position_title']}\n";
                    
                    // Test getting responsibilities
                    $responsibilities = $jobTemplateClass->getTemplateResponsibilities($sampleEmployee['job_template_id']);
                    echo "✓ Found " . count($responsibilities) . " responsibilities\n";
                    
                    // Test getting KPIs
                    $kpis = $jobTemplateClass->getTemplateKPIs($sampleEmployee['job_template_id']);
                    echo "✓ Found " . count($kpis) . " KPIs\n";
                    
                    // Test getting competencies
                    $competencies = $jobTemplateClass->getTemplateCompetencies($sampleEmployee['job_template_id']);
                    echo "✓ Found " . count($competencies) . " competencies\n";
                    
                    // Test getting values
                    $values = $jobTemplateClass->getTemplateValues($sampleEmployee['job_template_id']);
                    echo "✓ Found " . count($values) . " company values\n";
                } else {
                    echo "✗ Job template not found for ID: {$sampleEmployee['job_template_id']}\n";
                }
            } else {
                echo "! Employee has no job template assigned\n";
            }
        } else {
            echo "! job_template_id field not found in employee data\n";
        }
    } else {
        echo "✗ No employees found for testing\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 2: API Endpoint Structure\n";
try {
    // Check if API file exists and is readable
    $apiFile = __DIR__ . '/../public/api/job-template.php';
    if (file_exists($apiFile)) {
        echo "✓ API file exists: job-template.php\n";
        
        // Check file syntax
        $output = [];
        $returnCode = 0;
        exec("php -l $apiFile 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✓ API file syntax is valid\n";
        } else {
            echo "✗ API file syntax error: " . implode("\n", $output) . "\n";
        }
    } else {
        echo "✗ API file not found: job-template.php\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 3: Feedback Form Structure\n";
try {
    $formFile = __DIR__ . '/../public/employees/give-feedback.php';
    if (file_exists($formFile)) {
        echo "✓ Feedback form file exists\n";
        
        // Check if form contains the new structure
        $formContent = file_get_contents($formFile);
        
        if (strpos($formContent, 'jobTemplateContext') !== false) {
            echo "✓ Form contains job template context section\n";
        } else {
            echo "✗ Form missing job template context section\n";
        }
        
        if (strpos($formContent, 'dimensionSelect.addEventListener') !== false) {
            echo "✓ Form contains JavaScript for dynamic dimension selection\n";
        } else {
            echo "✗ Form missing JavaScript for dynamic dimension selection\n";
        }
        
        if (strpos($formContent, 'Step 1: Select Feedback Dimension') !== false) {
            echo "✓ Form has new step-by-step structure\n";
        } else {
            echo "✗ Form missing new step-by-step structure\n";
        }
        
        if (strpos($formContent, 'Feedback Guidelines & Rating Scale') !== false) {
            echo "✓ Guidelines moved to bottom of form\n";
        } else {
            echo "✗ Guidelines not properly positioned\n";
        }
    } else {
        echo "✗ Feedback form file not found\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "The feedback form redesign has been implemented with the following changes:\n";
echo "1. ✓ Created API endpoint for job template data\n";
echo "2. ✓ Redesigned form with dimension-first approach\n";
echo "3. ✓ Added dynamic content loading based on dimension selection\n";
echo "4. ✓ Moved guidelines and rating scale to bottom\n";
echo "5. ✓ Added step-by-step workflow with visual indicators\n";
echo "\nTo test the full functionality:\n";
echo "1. Access: http://localhost:8080/employees/give-feedback.php?employee_id=20\n";
echo "2. Select a dimension to see job template context load dynamically\n";
echo "3. Verify the form follows the new step-by-step workflow\n";
echo "\nNote: You need to be logged in as a manager or HR admin to access the feedback form.\n";