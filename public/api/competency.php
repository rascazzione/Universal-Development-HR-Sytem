<?php
/**
 * Competency Details API
 * Returns a single competency by ID for editing in the admin UI
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Competency.php';

// JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Require authentication
    requireAuth();

    // Restrict to users with full admin permissions (matches admin page requirement)
    if (!hasPermission('*')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // Validate and get competency ID
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid id']);
        exit;
    }

    // Load competency
    $competencyClass = new Competency();
    $competency = $competencyClass->getCompetencyById($id);

    if (!$competency) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Competency not found']);
        exit;
    }

    // Success
    echo json_encode([
        'success' => true,
        'competency' => $competency
    ]);
} catch (Throwable $e) {
    error_log('API competency.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>