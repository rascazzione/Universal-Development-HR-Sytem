<?php
/**
 * Employee Import Template Download Endpoint
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

try {
    // Initialize import/export class
    $importExportClass = new EmployeeImportExport();
    
    // Generate template
    $template = $importExportClass->generateImportTemplate();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $template['filename'] . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Output CSV template
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $template['headers']);
    
    // Write example row
    fputcsv($output, $template['example']);
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Template API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Template generation failed: ' . $e->getMessage()]);
}
?>
