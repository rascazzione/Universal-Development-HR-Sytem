<?php
/**
 * Employee Export API Endpoint
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
    // Get request parameters
    $exportType = $_GET['type'] ?? 'basic'; // 'basic' or 'complete'
    $filters = [];
    
    // Parse filters
    if (isset($_GET['include_inactive'])) {
        $filters['include_inactive'] = (bool)$_GET['include_inactive'];
    }
    
    if (!empty($_GET['department'])) {
        $filters['department'] = $_GET['department'];
    }
    
    // Initialize export class
    $exportClass = new EmployeeImportExport();
    
    if ($exportType === 'complete') {
        // Export complete data as ZIP
        $result = $exportClass->exportCompleteZIP($filters);
        
        if ($result['success']) {
            // Return file download information
            echo json_encode([
                'success' => true,
                'download_url' => '/api/employees/download.php?file=' . urlencode($result['filename']),
                'filename' => $result['filename'],
                'files_included' => $result['files_included']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $result['error']]);
        }
        
    } else {
        // Export basic CSV
        $result = $exportClass->exportBasicCSV($filters);
        
        if ($result['success']) {
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            
            // Output CSV data
            $output = fopen('php://output', 'w');
            foreach ($result['data'] as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['error' => $result['error']]);
        }
    }
    
} catch (Exception $e) {
    error_log("Export API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}
?>
