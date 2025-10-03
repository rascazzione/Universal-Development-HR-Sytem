<?php
/**
 * Employee Import API Endpoint
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../classes/EmployeeImportExport.php';

// Require authentication and HR Admin access
requireAuth();
if (!isHRAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. HR Admin role required.']);
    exit;
}

// Set headers for JSON response
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. Use POST.']);
        exit;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded or upload error occurred.']);
        exit;
    }
    
    $uploadedFile = $_FILES['csv_file'];
    
    // Validate file type
    $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
    $fileType = $uploadedFile['type'];
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes) && $fileExtension !== 'csv') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Please upload a CSV file.']);
        exit;
    }
    
    // Validate file size (10MB max)
    if ($uploadedFile['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Maximum size is 10MB.']);
        exit;
    }
    
    // Move uploaded file to temporary location
    $tempFilePath = sys_get_temp_dir() . '/employee_import_' . uniqid() . '.csv';
    if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFilePath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process uploaded file.']);
        exit;
    }
    
    // Initialize import class
    $importClass = new EmployeeImportExport();
    
    // Get action parameter
    $action = $_POST['action'] ?? 'validate';
    
    if ($action === 'validate') {
        // Validate the CSV data
        $csvData = $importClass->parseCSV($tempFilePath);
        $result = $importClass->validateImportData($csvData);
        
        // Clean up temp file
        unlink($tempFilePath);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'validation_result' => $result,
                'message' => "Validation complete. {$result['valid_count']} valid rows, {$result['error_count']} errors."
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
        
    } elseif ($action === 'import') {
        // Perform the actual import
        $options = [];
        
        $result = $importClass->importEmployeesCSV($tempFilePath, $options);
        
        // Clean up temp file
        unlink($tempFilePath);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'import_result' => $result,
                'message' => "Import complete. Created: {$result['created']}, Updated: {$result['updated']}, Errors: " . count($result['errors'])
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use "validate" or "import".']);
    }
    
} catch (Exception $e) {
    // Clean up temp file if it exists
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    
    error_log("Import API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
}
?>
