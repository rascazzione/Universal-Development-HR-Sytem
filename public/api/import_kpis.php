<?php
/**
 * Import KPIs API
 * Handles CSV upload and imports KPIs
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/CompanyKPI.php';

// JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        exit;
    }

    // Require authentication
    requireAuth();

    // Restrict to users with full admin permissions (matches admin page requirement)
    if (!hasPermission('*')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // CSRF protection
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    // Validate uploaded file
    if (!isset($_FILES['csvFile']) || !is_array($_FILES['csvFile'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'CSV file not provided']);
        exit;
    }

    $file = $_FILES['csvFile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }

    // Basic extension check (not strictly required, but helpful)
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        // Still allow, but warn? For safety, enforce csv
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a .csv file']);
        exit;
    }

    // Process import
    $kpiClass = new CompanyKPI();
    $result = $kpiClass->importKPIsFromCSV($file['tmp_name'], $_SESSION['user_id']);

    // Build response
    echo json_encode([
        'success' => true,
        'imported' => (int)($result['success'] ?? 0),
        'errors' => $result['errors'] ?? []
    ]);
} catch (Throwable $e) {
    error_log('API import_kpis.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
