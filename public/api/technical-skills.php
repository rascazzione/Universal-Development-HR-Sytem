<?php
/**
 * Technical Skills API
 * Manages technical competency assignments for job templates
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';
require_once __DIR__ . '/../../classes/Competency.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
    // Still ensure session is valid for downstream helpers
    if (!hasPermission('*')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $jobTemplateClass = new JobTemplate();
    $competencyClass = new Competency();

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            handleGetRequest($jobTemplateClass, $competencyClass);
            break;
        case 'POST':
            handlePostRequest($jobTemplateClass, $competencyClass);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Throwable $e) {
    error_log('Technical skills API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Handle GET request - fetch technical skills data for a template
 */
function handleGetRequest(JobTemplate $jobTemplateClass, Competency $competencyClass): void {
    $templateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
    if ($templateId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid template id']);
        return;
    }

    $assigned = $jobTemplateClass->getTemplateTechnicalSkills($templateId);
    $levels = $competencyClass->getTechnicalSkillLevels();
    $categories = $competencyClass->getCategories(true, 'technical');
    $competencies = $competencyClass->getCompetencies(null, 'technical');

    echo json_encode([
        'success' => true,
        'technical_skills' => $assigned,
        'levels' => $levels,
        'categories' => $categories,
        'competencies' => $competencies
    ]);
}

/**
 * Handle POST request - add/update/remove technical skills
 */
function handlePostRequest(JobTemplate $jobTemplateClass, Competency $competencyClass): void {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        return;
    }

    $csrfToken = $payload['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        return;
    }

    $action = $payload['action'] ?? '';
    $templateId = isset($payload['template_id']) ? (int)$payload['template_id'] : 0;
    if ($templateId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid template id']);
        return;
    }

    try {
        switch ($action) {
            case 'add':
            case 'update':
                $competencyId = (int)($payload['competency_id'] ?? 0);
                $technicalLevelId = (int)($payload['technical_level_id'] ?? 0);
                $weight = isset($payload['weight_percentage']) ? (float)$payload['weight_percentage'] : 100.0;

                if ($competencyId <= 0 || $technicalLevelId <= 0) {
                    throw new InvalidArgumentException('Missing competency or level information.');
                }

                $jobTemplateClass->addTechnicalSkillToTemplate($templateId, $competencyId, $technicalLevelId, $weight);
                $message = $action === 'add' ? 'Technical skill added successfully.' : 'Technical skill updated successfully.';
                break;

            case 'remove':
                $assignmentId = (int)($payload['assignment_id'] ?? 0);
                if ($assignmentId <= 0) {
                    throw new InvalidArgumentException('Invalid assignment id.');
                }
                $jobTemplateClass->removeSkillFromTemplate($assignmentId);
                $message = 'Technical skill removed successfully.';
                break;

            default:
                throw new InvalidArgumentException('Unsupported action.');
        }

        $response = [
            'success' => true,
            'message' => $message,
            'technical_skills' => $jobTemplateClass->getTemplateTechnicalSkills($templateId),
            'levels' => $competencyClass->getTechnicalSkillLevels()
        ];
        echo json_encode($response);
    } catch (Throwable $e) {
        error_log('Technical skills POST error: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
