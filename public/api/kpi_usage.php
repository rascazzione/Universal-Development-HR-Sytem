<?php
/**
 * KPI Usage API
 * Returns an HTML snippet of where a KPI is used (job templates)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/CompanyKPI.php';

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

    // Validate and get KPI ID
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid id']);
        exit;
    }

    // Load usage
    $kpiClass = new CompanyKPI();
    $usage = $kpiClass->getKPIUsage($id);

    // Build HTML snippet expected by the UI
    if (!$usage || count($usage) === 0) {
        $html = "<p class=\"text-muted mb-0\">This KPI is not currently used in any job templates.</p>";
    } else {
        ob_start();
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Position Title</th>
                        <th>Department</th>
                        <th class="text-end">Target Value</th>
                        <th class="text-end">Weight (%)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usage as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['position_title'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['department'] ?? ''); ?></td>
                        <td class="text-end">
                            <?php
                                $tv = $row['target_value'] ?? null;
                                echo $tv === null ? '' : htmlspecialchars((string)$tv);
                            ?>
                        </td>
                        <td class="text-end">
                            <?php
                                $wp = $row['weight_percentage'] ?? null;
                                echo $wp === null ? '' : htmlspecialchars((string)$wp);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $html = (string)ob_get_clean();
    }

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} catch (Throwable $e) {
    error_log('API kpi_usage.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
