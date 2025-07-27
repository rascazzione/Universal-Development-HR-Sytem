<?php
/**
 * Test Script for Test Data Population
 * Validates the populate_test_data.php script without actually running it
 */

require_once __DIR__ . '/../config/config.php';

echo "🧪 Testing Test Data Population Script\n";
echo "=====================================\n\n";

// Test 1: Check database connection
echo "1. Testing database connection...\n";
try {
    $pdo = getDbConnection();
    if ($pdo) {
        echo "   ✅ Database connection successful\n";
    } else {
        echo "   ❌ Database connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check required tables exist
echo "\n2. Checking required tables...\n";
$requiredTables = [
    'users', 'employees', 'departments', 'company_kpis', 'competencies',
    'company_values', 'job_position_templates', 'evaluation_periods', 'evaluations',
    'evaluation_kpi_results', 'evaluation_competency_results', 
    'evaluation_responsibility_results', 'evaluation_value_results'
];

$missingTables = [];
foreach ($requiredTables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() === 0) {
            $missingTables[] = $table;
            echo "   ❌ Missing table: $table\n";
        } else {
            echo "   ✅ Found table: $table\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking table $table: " . $e->getMessage() . "\n";
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "\n❌ Missing required tables. Please run database setup first.\n";
    exit(1);
}

// Test 3: Check required PHP classes
echo "\n3. Checking required PHP classes...\n";
$requiredClasses = [
    'User' => '../classes/User.php',
    'Employee' => '../classes/Employee.php',
    'Department' => '../classes/Department.php',
    'CompanyKPI' => '../classes/CompanyKPI.php',
    'Competency' => '../classes/Competency.php',
    'CompanyValues' => '../classes/CompanyValues.php',
    'JobTemplate' => '../classes/JobTemplate.php',
    'EvaluationPeriod' => '../classes/EvaluationPeriod.php',
    'Evaluation' => '../classes/Evaluation.php'
];

$missingClasses = [];
foreach ($requiredClasses as $className => $filePath) {
    $fullPath = __DIR__ . '/' . $filePath;
    if (file_exists($fullPath)) {
        require_once $fullPath;
        if (class_exists($className)) {
            echo "   ✅ Class $className loaded successfully\n";
        } else {
            echo "   ❌ Class $className not found in file\n";
            $missingClasses[] = $className;
        }
    } else {
        echo "   ❌ File not found: $filePath\n";
        $missingClasses[] = $className;
    }
}

if (!empty($missingClasses)) {
    echo "\n❌ Missing required classes. Please ensure all class files exist.\n";
    exit(1);
}

// Test 4: Check file permissions
echo "\n4. Checking file permissions...\n";
$scriptsDir = __DIR__;
if (is_writable($scriptsDir)) {
    echo "   ✅ Scripts directory is writable\n";
} else {
    echo "   ⚠️  Scripts directory is not writable - documentation files may not be created\n";
}

// Test 5: Check PHP configuration
echo "\n5. Checking PHP configuration...\n";
$memoryLimit = ini_get('memory_limit');
$executionTime = ini_get('max_execution_time');

echo "   📊 Memory limit: $memoryLimit\n";
echo "   ⏱️  Execution time limit: $executionTime seconds\n";

if (intval($memoryLimit) < 128) {
    echo "   ⚠️  Memory limit may be too low for large datasets\n";
}

if (intval($executionTime) < 300 && $executionTime != 0) {
    echo "   ⚠️  Execution time limit may be too low for complete data generation\n";
}

// Test 6: Validate script syntax
echo "\n6. Validating script syntax...\n";
$scriptPath = __DIR__ . '/populate_test_data.php';
if (file_exists($scriptPath)) {
    $output = [];
    $returnCode = 0;
    exec("php -l $scriptPath 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "   ✅ Script syntax is valid\n";
    } else {
        echo "   ❌ Script syntax errors found:\n";
        foreach ($output as $line) {
            echo "      $line\n";
        }
        exit(1);
    }
} else {
    echo "   ❌ populate_test_data.php not found\n";
    exit(1);
}

// Test 7: Check current data state
echo "\n7. Checking current data state...\n";
try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $employeeCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $evaluationCount = $pdo->query("SELECT COUNT(*) FROM evaluations")->fetchColumn();
    
    echo "   📊 Current users: $userCount\n";
    echo "   📊 Current employees: $employeeCount\n";
    echo "   📊 Current evaluations: $evaluationCount\n";
    
    if ($userCount > 0 || $employeeCount > 0 || $evaluationCount > 0) {
        echo "   ⚠️  Database contains existing data - will be cleared when script runs\n";
    } else {
        echo "   ✅ Database is empty and ready for test data\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking data state: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test Summary\n";
echo "===============\n";
echo "✅ All prerequisite checks passed!\n";
echo "🚀 The test data population script is ready to run.\n\n";

echo "To execute the script:\n";
echo "  Command line: php scripts/populate_test_data.php\n";
echo "  Web browser:  http://localhost/your-project/scripts/populate_test_data.php\n\n";

echo "⚠️  IMPORTANT: The script will completely reset all data except system settings.\n";
echo "   Make sure to backup any important data before running.\n\n";

echo "📄 After execution, check these files for credentials and summary:\n";
echo "  - scripts/test_data_credentials.txt\n";
echo "  - scripts/test_data_summary.txt\n";
?>