<?php
/**
 * Evidence Details API Endpoint
 * Returns detailed evidence entries for a specific dimension
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Evaluation.php';

// Require authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Only GET requests are allowed');
    }
    
    // Get parameters
    $evaluationId = $_GET['evaluation_id'] ?? null;
    $dimension = $_GET['dimension'] ?? null;
    
    if (!$evaluationId || !$dimension) {
        throw new Exception('Missing required parameters: evaluation_id and dimension');
    }
    
    // Initialize evaluation class
    $evaluationClass = new Evaluation();
    
    // Check if user has permission to view this evaluation
    $evaluation = $evaluationClass->getEvaluationById($evaluationId);
    if (!$evaluation) {
        throw new Exception('Evaluation not found');
    }
    
    // Check permissions (same as edit permissions)
    if (!canEditEvaluation($evaluation)) {
        throw new Exception('You do not have permission to view this evaluation');
    }
    
    // Get evidence entries for the dimension
    $evidenceEntries = $evaluationClass->getEvidenceEntriesByDimension($evaluationId, $dimension);
    
    // Format the response
    $response = [
        'success' => true,
        'dimension' => $dimension,
        'entries' => $evidenceEntries,
        'summary' => [
            'total_entries' => count($evidenceEntries),
            'positive_entries' => count(array_filter($evidenceEntries, fn($e) => $e['star_rating'] >= 4)),
            'neutral_entries' => count(array_filter($evidenceEntries, fn($e) => $e['star_rating'] == 3)),
            'negative_entries' => count(array_filter($evidenceEntries, fn($e) => $e['star_rating'] <= 2)),
            'average_rating' => count($evidenceEntries) > 0 ? 
                array_sum(array_column($evidenceEntries, 'star_rating')) / count($evidenceEntries) : 0
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}