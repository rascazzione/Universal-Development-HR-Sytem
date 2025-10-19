<?php
/**
 * API Endpoint for Soft Skill Level Definitions
 * Manages the JSON file with 4-level competency descriptions
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Competency.php';

// Require admin authentication
requireAuth();
if (!hasPermission('*')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Initialize classes
$competencyClass = new Competency();

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

/**
 * Handle GET requests - retrieve soft skill level definitions
 */
function handleGetRequest() {
    global $competencyClass;
    
    try {
        $competencyKey = $_GET['competency_key'] ?? null;
        $competencyId = $_GET['competency_id'] ?? null;
        
        if ($competencyId) {
            // Get competency by ID and check if it's a soft skill
            $competency = $competencyClass->getCompetencyById($competencyId);
            if (!$competency) {
                throw new Exception('Competency not found');
            }
            
            // Check if it's a soft skill
            if (!$competencyClass->isSoftSkillCompetency($competencyId)) {
                throw new Exception('This competency is not a soft skill');
            }
            
            $competencyKey = $competencyClass->competencyNameToKey($competency['competency_name']);
        }
        
        if ($competencyKey) {
            // Get specific competency levels
            $levels = $competencyClass->getSoftSkillLevels($competencyKey);
            if (!$levels) {
                // Return empty template for new competency
                $levels = [
                    'name' => ucwords(str_replace('_', ' ', $competencyKey)),
                    'definition' => '',
                    'description' => '',
                    'levels' => [
                        '1' => ['title' => '', 'behaviors' => ['', '', '', '']],
                        '2' => ['title' => '', 'behaviors' => ['', '', '', '']],
                        '3' => ['title' => '', 'behaviors' => ['', '', '', '']],
                        '4' => ['title' => '', 'behaviors' => ['', '', '', '']]
                    ]
                ];
            }
            
            echo json_encode([
                'success' => true,
                'competency_key' => $competencyKey,
                'levels' => $levels
            ]);
        } else {
            // Get all soft skill definitions
            $definitions = $competencyClass->getSoftSkillLevelDefinitions();
            echo json_encode([
                'success' => true,
                'definitions' => $definitions
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Handle POST requests - save soft skill level definitions
 */
function handlePostRequest() {
    global $competencyClass;
    
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $competencyKey = $_POST['competency_key'] ?? null;
        $levelsJson = $_POST['levels'] ?? null;
        
        if (!$competencyKey || !$levelsJson) {
            throw new Exception('Missing required parameters');
        }
        
        // Decode JSON levels data
        $levels = json_decode($levelsJson, true);
        if (!$levels || !is_array($levels)) {
            throw new Exception('Invalid levels JSON format');
        }
        
        // Validate required fields
        $requiredFields = ['name', 'definition', 'description', 'levels'];
        foreach ($requiredFields as $field) {
            if (!isset($levels[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate all 4 levels are present
        for ($i = 1; $i <= 4; $i++) {
            if (!isset($levels['levels'][$i])) {
                throw new Exception("Missing level $i");
            }
            
            $level = $levels['levels'][$i];
            if (!isset($level['title']) || !isset($level['behaviors'])) {
                throw new Exception("Invalid structure for level $i");
            }
            
            if (!is_array($level['behaviors']) || count($level['behaviors']) !== 4) {
                throw new Exception("Level $i must have exactly 4 behaviors");
            }
        }
        
        // Save the levels
        $success = $competencyClass->saveSoftSkillLevels($competencyKey, $levels);
        
        if (!$success) {
            throw new Exception('Failed to save soft skill levels');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Soft skill levels saved successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>