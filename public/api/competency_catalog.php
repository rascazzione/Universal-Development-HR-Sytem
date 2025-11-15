<?php
/**
 * Competency Catalog Export API
 * Provides starter templates or the current catalog in CSV/JSON formats.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Competency.php';

try {
    requireAuth();
    
    if (!hasPermission('*')) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    
    $source = strtolower($_GET['source'] ?? 'db');
    $format = strtolower($_GET['format'] ?? 'csv');
    $categoryName = trim((string)($_GET['category'] ?? ''));
    $moduleFilterParam = $_GET['module'] ?? ($_GET['type'] ?? null);
    $moduleFilter = null;
    if ($moduleFilterParam !== null && $moduleFilterParam !== '') {
        $moduleNormalized = strtolower(trim((string)$moduleFilterParam));
        if (in_array($moduleNormalized, ['soft', 'soft_skill', 'soft-skill', 'soft skills', 'behavioral', 'people'], true)) {
            $moduleFilter = 'soft_skill';
        } elseif (in_array($moduleNormalized, ['technical', 'tech'], true)) {
            $moduleFilter = 'technical';
        }
    }
    $downloadParam = $_GET['download'] ?? '0';
    $download = filter_var($downloadParam, FILTER_VALIDATE_BOOLEAN);
    
    $competencyClass = new Competency();
    $fromDb = $source !== 'starter';
    
    if ($fromDb) {
        $categoryId = null;
        if ($categoryName !== '') {
            $category = $competencyClass->getCategoryByName($categoryName);
            $categoryId = $category['id'] ?? null;
        }
        $records = $competencyClass->getCompetencies($categoryId, $moduleFilter);
    } else {
        $records = $competencyClass->getStarterCompetencyCatalog($moduleFilter);
    }
    
    $normalizedRecords = array_map(function ($record) use ($fromDb) {
        if ($fromDb) {
            return [
                'competency_name' => $record['competency_name'] ?? '',
                'description' => $record['description'] ?? '',
                'category' => $record['category_name'] ?? '',
                'category_type' => $record['category_type'] ?? 'technical',
                'module_type' => $record['category_module_type'] ?? $record['category_type'] ?? 'technical',
                'symbol' => $record['symbol'] ?? '',
                'max_level' => $record['max_level'] ?? '',
                'level_type' => $record['level_type'] ?? '',
                'competency_key' => $record['competency_key'] ?? ''
            ];
        }
        
        return [
            'competency_name' => $record['competency_name'] ?? '',
            'description' => $record['description'] ?? '',
            'category' => $record['category'] ?? '',
            'category_type' => $record['category_type'] ?? 'technical',
            'module_type' => $record['module_type'] ?? ($record['category_type'] ?? 'technical'),
            'symbol' => $record['symbol'] ?? '',
            'max_level' => $record['max_level'] ?? '',
            'level_type' => $record['level_type'] ?? '',
            'competency_key' => $record['competency_key'] ?? ''
        ];
    }, $records);
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode([
            'success' => true,
            'source' => $fromDb ? 'db' : 'starter',
            'category' => $categoryName !== '' ? $categoryName : null,
            'module_type' => $moduleFilter ?: null,
            'count' => count($normalizedRecords),
            'competencies' => $normalizedRecords
        ]);
        exit;
    }
    
    if ($format !== 'csv') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unsupported format']);
        exit;
    }
    
    header('Cache-Control: no-cache, must-revalidate');
    header('Content-Type: text/csv; charset=utf-8');
    
    if ($download) {
        $filename = $fromDb ? 'competency_catalog_current' : 'competency_catalog_starter';
        if ($categoryName !== '') {
            $safeCategory = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($categoryName));
            $filename .= '_' . trim($safeCategory, '-');
        }
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    }
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Competency Name',
        'Description',
        'Category',
        'Category Type',
        'Module Type',
        'Symbol',
        'Max Level',
        'Level Type',
        'Competency Key'
    ]);
    
    foreach ($normalizedRecords as $record) {
        fputcsv($output, [
            $record['competency_name'],
            $record['description'],
            $record['category'],
            $record['category_type'],
            $record['module_type'],
            $record['symbol'],
            $record['max_level'],
            $record['level_type'],
            $record['competency_key']
        ]);
    }
    
    fclose($output);
    exit;
} catch (Throwable $e) {
    error_log('API competency_catalog.php error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
