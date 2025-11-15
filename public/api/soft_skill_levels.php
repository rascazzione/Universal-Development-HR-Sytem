<?php
/**
 * API Endpoint for Soft Skill Level Definitions
 * Manages the JSON file with 4-level competency descriptions
 */

// Start output buffering to prevent any unwanted output before JSON
ob_start();

// Disable error display to prevent HTML errors in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Competency.php';

// Check authentication for API calls
// For API endpoints, we need to allow session-based auth without redirect
if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

if (!hasPermission('*')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Initialize classes
$competencyClass = new Competency();

// Test basic functionality first
error_log("[DEBUG] soft_skill_levels.php - Starting API, method: " . $_SERVER['REQUEST_METHOD']);
error_log("[DEBUG] soft_skill_levels.php - GET params: " . print_r($_GET, true));

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    case 'DELETE':
        handleDeleteRequest();
        break;
    default:
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

/**
 * Handle GET requests - retrieve soft skill level definitions
 */
function handleGetRequest() {
    global $competencyClass;
    
    try {
        if (isset($_GET['overview'])) {
            try {
                error_log("[DEBUG] soft_skill_levels.php - Getting soft skill definition status");
                $status = $competencyClass->getSoftSkillDefinitionStatus();
                error_log("[DEBUG] soft_skill_levels.php - Status retrieved: " . print_r($status, true));
                
                // Ensure we always have a valid response structure
                if (!is_array($status)) {
                    error_log("[ERROR] soft_skill_levels.php - Status is not an array, using fallback");
                    $status = [
                        'files' => [],
                        'unassigned_definitions' => [],
                        'missing_definitions' => [],
                        'error' => 'Invalid status format'
                    ];
                }
                
                $response = array_merge(['success' => true], $status);
                error_log("[DEBUG] soft_skill_levels.php - Final response: " . print_r($response, true));
                
                // Clear any previous output that might interfere with JSON
                if (ob_get_length()) {
                    ob_clean();
                }
                
                header('Content-Type: application/json');
                $jsonOutput = json_encode($response);
                
                // Ensure JSON encoding succeeded
                if ($jsonOutput === false) {
                    error_log("[ERROR] soft_skill_levels.php - JSON encoding failed: " . json_last_error_msg());
                    $jsonOutput = json_encode([
                        'success' => false,
                        'error' => 'JSON encoding failed: ' . json_last_error_msg()
                    ]);
                }
                
                error_log("[DEBUG] soft_skill_levels.php - JSON output length: " . strlen($jsonOutput));
                error_log("[DEBUG] soft_skill_levels.php - JSON output first 100 chars: " . substr($jsonOutput, 0, 100));
                echo $jsonOutput;
            } catch (Exception $e) {
                error_log("[ERROR] soft_skill_levels.php - Exception in overview: " . $e->getMessage());
                error_log("[ERROR] soft_skill_levels.php - Stack trace: " . $e->getTraceAsString());
                
                // Clear any previous output that might interfere with JSON
                if (ob_get_length()) {
                    ob_clean();
                }
                
                http_response_code(500);
                header('Content-Type: application/json');
                $errorResponse = json_encode(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()]);
                
                // Ensure error response is valid JSON
                if ($errorResponse === false) {
                    $errorResponse = json_encode(['success' => false, 'error' => 'Critical error occurred']);
                }
                
                echo $errorResponse;
            }
            return;
        }

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
            
            $competencyKey = $competency['competency_key'] ?? $competencyClass->competencyNameToKey($competency['competency_name']);
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

            $rawJson = json_encode($levels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $filePath = $competencyClass->getSoftSkillDefinitionFilePath($competencyKey);
            $fileExists = $competencyClass->softSkillDefinitionExists($competencyKey);
            $fileInfo = $fileExists ? realpath($filePath) : $filePath;
            $lastUpdated = $fileExists ? date('c', filemtime($filePath)) : null;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'competency_key' => $competencyKey,
                'levels' => $levels,
                'raw_json' => $rawJson,
                'file_path' => $fileInfo,
                'file_exists' => $fileExists,
                'last_modified' => $lastUpdated
            ]);
        } else {
            // Get all soft skill definitions
            $definitions = $competencyClass->getSoftSkillLevelDefinitions();
            header('Content-Type: application/json');
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
        // Log incoming request for debugging
        error_log("[DEBUG] soft_skill_levels POST - competency_key: " . ($_POST['competency_key'] ?? 'null'));
        error_log("[DEBUG] soft_skill_levels POST - raw_json length: " . strlen($_POST['raw_json'] ?? ''));
        
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            error_log("[ERROR] soft_skill_levels POST - CSRF token validation failed");
            throw new Exception('Invalid security token');
        }

        $competencyKey = $_POST['competency_key'] ?? null;
        $rawJsonPayload = $_POST['raw_json'] ?? null;
        $levelsJson = $_POST['levels'] ?? null;
        
        if (!$competencyKey || (!$rawJsonPayload && !$levelsJson)) {
            error_log("[ERROR] soft_skill_levels POST - Missing parameters");
            throw new Exception('Missing required parameters');
        }
        
        // Decode JSON levels data
        $payload = $rawJsonPayload ?? $levelsJson;

        $levels = json_decode($payload, true);
        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE) {
            error_log("[ERROR] soft_skill_levels POST - JSON decode error: " . json_last_error_msg());
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }

        if (!$levels || !is_array($levels)) {
            error_log("[ERROR] soft_skill_levels POST - Invalid levels format");
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
        error_log("[DEBUG] soft_skill_levels POST - About to save for key: " . $competencyKey);
        
        try {
            $success = $competencyClass->saveSoftSkillLevels($competencyKey, $levels);
        } catch (Exception $saveException) {
            error_log("[ERROR] soft_skill_levels POST - Save exception: " . $saveException->getMessage());
            error_log("[ERROR] soft_skill_levels POST - Stack trace: " . $saveException->getTraceAsString());
            throw $saveException;
        }
        
        if (!$success) {
            error_log("[ERROR] soft_skill_levels POST - Save returned false");
            throw new Exception('Failed to save soft skill levels');
        }
        
        $filePath = $competencyClass->getSoftSkillDefinitionFilePath($competencyKey);
        error_log("[DEBUG] soft_skill_levels POST - Successfully saved to: " . $filePath);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Soft skill levels saved successfully',
            'file_path' => realpath($filePath) ?: $filePath
        ]);
        
    } catch (Exception $e) {
        error_log("[ERROR] soft_skill_levels POST - Final exception: " . $e->getMessage());
        error_log("[ERROR] soft_skill_levels POST - Stack trace: " . $e->getTraceAsString());
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Handle DELETE requests - delete orphaned JSON files
 */
function handleDeleteRequest() {
    global $competencyClass;

    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }

        $competencyKey = $input['competency_key'] ?? null;

        if (!$competencyKey) {
            throw new Exception('Missing competency_key parameter');
        }

        // Verify CSRF token
        if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        // Check if this is actually an orphaned file (not linked to active competency)
        $competencies = fetchAll(
            "SELECT c.id
             FROM competencies c
             JOIN competency_categories cc ON c.category_id = cc.id
             WHERE c.competency_key = ?
               AND c.is_active = 1
               AND (cc.category_type = 'soft_skill' OR cc.module_type = 'soft_skill' OR c.level_type = 'soft_skill_scale')",
            [$competencyKey]
        );

        if (!empty($competencies)) {
            throw new Exception('Cannot delete JSON file for active competency');
        }

        // Delete the JSON file
        $filePath = $competencyClass->getSoftSkillDefinitionFilePath($competencyKey);
        if (!file_exists($filePath)) {
            throw new Exception('JSON file does not exist');
        }

        if (!unlink($filePath)) {
            throw new Exception('Failed to delete JSON file');
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Orphaned JSON file deleted successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Clean output buffer and send final response
// Flush any buffered output without discarding the JSON response
if (ob_get_level() > 0) {
    $buffer = ob_get_contents();

    if ($buffer !== false) {
        $hasJsonHeader = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type: application/json') === 0) {
                $hasJsonHeader = true;
                break;
            }
        }

        // Only log truly unexpected output (e.g., warnings before headers are set)
        if (!$hasJsonHeader && trim($buffer) !== '') {
            $preview = substr($buffer, 0, 200);
            error_log("[DEBUG] Unexpected buffered output detected before shutdown: " . $preview);
        }
    }

    ob_end_flush();
}

?>
