<?php
/**
 * Import Competencies API
 * Handles CSV upload for competency catalog management.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Competency.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        exit;
    }
    
    requireAuth();
    
    if (!hasPermission('*')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    
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
    
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a .csv file']);
        exit;
    }
    
    $competencyClass = new Competency();
    $result = $competencyClass->importCompetenciesFromCSV($file['tmp_name']);
    
    echo json_encode([
        'success' => true,
        'imported' => (int)($result['imported'] ?? 0),
        'updated' => (int)($result['updated'] ?? 0),
        'skipped' => (int)($result['skipped'] ?? 0),
        'errors' => $result['errors'] ?? []
    ]);
} catch (Throwable $e) {
    error_log('API import_competencies.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
