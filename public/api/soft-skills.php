<?php
/**
 * Soft Skills API
 * Manages soft skill competency assignments for job templates
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
    error_log('Soft skills API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Handle GET request - fetch soft skills data for a template
 */
function handleGetRequest(JobTemplate $jobTemplateClass, Competency $competencyClass): void {
    $templateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
    if ($templateId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid template id']);
        return;
    }

    $assigned = $jobTemplateClass->getTemplateSoftSkills($templateId);
    $catalog = $competencyClass->getSoftSkillCatalog();
    $softSkillCompetencies = $competencyClass->getCompetencies(null, 'soft_skill');

    $available = [];
    foreach ($softSkillCompetencies as $competency) {
        $key = $competency['competency_key'] ?? $competencyClass->competencyNameToKey($competency['competency_name']);
        $definition = $catalog[$key] ?? [
            'name' => $competency['competency_name'],
            'definition' => $competency['description'] ?? '',
            'description' => $competency['description'] ?? '',
            'levels' => []
        ];
        $available[] = [
            'id' => $competency['id'],
            'competency_key' => $key,
            'name' => $definition['name'] ?? $competency['competency_name'],
            'definition' => $definition['definition'] ?? '',
            'description' => $definition['description'] ?? '',
            'levels' => $definition['levels'] ?? []
        ];
    }

    echo json_encode([
        'success' => true,
        'soft_skills' => $assigned,
        'available_competencies' => $available
    ]);
}

/**
 * Handle POST request - add/update/remove soft skills
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
                $softSkillLevel = (int)($payload['soft_skill_level'] ?? 0);
                $competencyKey = $payload['competency_key'] ?? null;
                $weight = isset($payload['weight_percentage']) ? (float)$payload['weight_percentage'] : 100.0;

                if ($competencyId <= 0 || $softSkillLevel <= 0) {
                    throw new InvalidArgumentException('Missing competency or level information.');
                }

                $jobTemplateClass->addSoftSkillToTemplate($templateId, $competencyId, $competencyKey, $softSkillLevel, $weight);
                $message = $action === 'add' ? 'Soft skill added successfully.' : 'Soft skill updated successfully.';
                break;

            case 'remove':
                $assignmentId = (int)($payload['assignment_id'] ?? 0);
                if ($assignmentId <= 0) {
                    throw new InvalidArgumentException('Invalid assignment id.');
                }
                $jobTemplateClass->removeSkillFromTemplate($assignmentId);
                $message = 'Soft skill removed successfully.';
                break;

            default:
                throw new InvalidArgumentException('Unsupported action.');
        }

        $response = [
            'success' => true,
            'message' => $message,
            'soft_skills' => $jobTemplateClass->getTemplateSoftSkills($templateId)
        ];
        echo json_encode($response);
    } catch (Throwable $e) {
        error_log('Soft skills POST error: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
