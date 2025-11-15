<?php
/**
 * KPI Catalog Export API
 * Provides curated starter KPIs or the current company catalog in CSV/JSON formats
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/CompanyKPI.php';

try {
    requireAuth();

    if (!hasPermission('*')) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $source = strtolower($_GET['source'] ?? 'starter');
    $format = strtolower($_GET['format'] ?? 'csv');
    $category = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;
    $downloadParam = $_GET['download'] ?? '0';
    $download = filter_var($downloadParam, FILTER_VALIDATE_BOOLEAN);

    $kpiClass = new CompanyKPI();
    $kpis = [];

    if ($source === 'db') {
        $kpis = $kpiClass->getKPIs($category);
    } else {
        $source = 'starter';
        $kpis = $kpiClass->getStarterKPICatalog($category);
    }

    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode([
            'success' => true,
            'source' => $source,
            'category' => $category,
            'count' => count($kpis),
            'kpis' => $kpis
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
        $filename = $source === 'db' ? 'kpi_catalog_current' : 'kpi_catalog_starter';
        if ($category) {
            $safeCategory = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($category));
            $filename .= '_' . trim($safeCategory, '-');
        }
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    }

    $output = fopen('php://output', 'w');
    fputcsv($output, ['KPI Name', 'Description', 'Measurement Unit', 'Category', 'Target Type']);

    foreach ($kpis as $kpi) {
        fputcsv($output, [
            $kpi['kpi_name'] ?? '',
            $kpi['kpi_description'] ?? '',
            $kpi['measurement_unit'] ?? '',
            $kpi['category'] ?? '',
            $kpi['target_type'] ?? 'higher_better'
        ]);
    }

    fclose($output);
    exit;
} catch (Throwable $e) {
    error_log('API kpi_catalog.php error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
