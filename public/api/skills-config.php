<?php
/**
 * Skills Configuration API
 * Provides shared configuration data for technical and soft skill modules
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Competency.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    requireAuth();
    if (!hasPermission('*')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $competencyClass = new Competency();

    $technicalLevels = $competencyClass->getTechnicalSkillLevels();
    $softSkillLevels = [];
    for ($i = 1; $i <= 4; $i++) {
        switch ($i) {
            case 1:
                $meaning = 'Basic';
                break;
            case 2:
                $meaning = 'Intermediate';
                break;
            case 3:
                $meaning = 'Advanced';
                break;
            case 4:
                $meaning = 'Expert';
                break;
            default:
                $meaning = 'Basic';
        }

        $softSkillLevels[] = [
            'level' => $i,
            'symbol_pattern' => $competencyClass->buildSoftSkillSymbolPattern($i),
            'meaning' => $meaning
        ];
    }

    echo json_encode([
        'success' => true,
        'symbols' => [
            'technical' => '🧩',
            'soft_skill' => '🧠'
        ],
        'technical_levels' => $technicalLevels,
        'soft_skill_levels' => $softSkillLevels
    ]);
} catch (Throwable $e) {
    error_log('Skills config API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
