<?php
/**
 * Employee Export File Download Endpoint
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../../includes/auth.php';

// Require authentication and HR Admin access
requireAuth();
if (!isHRAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. HR Admin role required.']);
    exit;
}

try {
    $filename = $_GET['file'] ?? '';
    
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'No file specified']);
        exit;
    }
    
    // Validate filename to prevent directory traversal
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid filename']);
        exit;
    }
    
    $filePath = sys_get_temp_dir() . '/' . $filename;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        exit;
    }
    
    // Determine content type
    $contentType = 'application/octet-stream';
    if (pathinfo($filename, PATHINFO_EXTENSION) === 'zip') {
        $contentType = 'application/zip';
    } elseif (pathinfo($filename, PATHINFO_EXTENSION) === 'csv') {
        $contentType = 'text/csv';
    }
    
    // Set headers for file download
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Output file
    readfile($filePath);
    
    // Clean up - delete the temporary file after download
    unlink($filePath);
    
} catch (Exception $e) {
    error_log("Download API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Download failed: ' . $e->getMessage()]);
}
?>
