<?php
/**
 * Evidence Management Interface
 * Phase 3: Advanced Features - Evidence Management
 * Growth Evidence System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/EvidenceManager.php';
require_once __DIR__ . '/../../classes/NotificationManager.php';

// Require authentication
requireAuth();

// Check if user has permission to manage evidence
$userRole = $_SESSION['role'];
if (!in_array($userRole, ['hr_admin', 'manager'])) {
    header('Location: /dashboard.php');
    exit;
}

$evidenceManager = new EvidenceManager();
$notificationManager = new NotificationManager();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'bulk_operation':
                $entryIds = $_POST['entry_ids'] ?? [];
                $operation = $_POST['operation'] ?? '';
                $operationData = $_POST['operation_data'] ?? [];
                
                if (!empty($entryIds) && !empty($operation)) {
                    $results = $evidenceManager->bulkOperation($entryIds, $operation, $operationData);
                    $message = "Bulk operation completed. Success: {$results['success']}, Failed: {$results['failed']}";
                    $messageType = $results['failed'] > 0 ? 'warning' : 'success';
                    
                    if (!empty($results['errors'])) {
                        $message .= "<br>Errors: " . implode('<br>', $results['errors']);
                    }
                } else {
                    $message = "Please select entries and operation.";
                    $messageType = 'error';
                }
                break;
                
            case 'create_tag':
                $tagData = [
                    'tag_name' => $_POST['tag_name'] ?? '',
                    'tag_color' => $_POST['tag_color'] ?? '#007bff',
                    'description' => $_POST['description'] ?? '',
                    'created_by' => $_SESSION['user_id']
                ];
                
                if ($evidenceManager->createTag($tagData)) {
                    $message = "Tag created successfully.";
                    $messageType = 'success';
                } else {
                    $message = "Failed to create tag.";
                    $messageType = 'error';
                }
                break;
                
            case 'archive_entry':
                $entryId = $_POST['entry_id'] ?? 0;
                $reason = $_POST['reason'] ?? 'manual';
                
                if ($evidenceManager->archiveEntry($entryId, $reason)) {
                    $message = "Evidence entry archived successfully.";
                    $messageType = 'success';
                } else {
                    $message = "Failed to archive evidence entry.";
                    $messageType = 'error';
                }
                break;
                
            case 'restore_entry':
                $entryId = $_POST['entry_id'] ?? 0;
                
                if ($evidenceManager->restoreEntry($entryId)) {
                    $message = "Evidence entry restored successfully.";
                    $messageType = 'success';
                } else {
                    $message = "Failed to restore evidence entry.";
                    $messageType = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get filter parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'employee_id' => $_GET['employee_id'] ?? '',
    'manager_id' => $_GET['manager_id'] ?? '',
    'dimension' => $_GET['dimension'] ?? '',
    'min_rating' => $_GET['min_rating'] ?? '',
    'max_rating' => $_GET['max_rating'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'tags' => !empty($_GET['tags']) ? explode(',', $_GET['tags']) : [],
    'include_archived' => isset($_GET['include_archived'])
];

// Get pagination parameters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;

// Get evidence entries
$searchResults = $evidenceManager->advancedSearch($filters, $page, $limit);
$entries = $searchResults['results'];
$pagination = $searchResults['pagination'];

// Get available tags
$availableTags = $evidenceManager->getAvailableTags();

// Get employees for filter dropdown (if manager)
$employees = [];
if ($userRole === 'manager') {
    $managerId = $_SESSION['employee_id'];
    $employees = fetchAll("SELECT employee_id, first_name, last_name FROM employees WHERE manager_id = ? AND active = TRUE", [$managerId]);
} else {
    $employees = fetchAll("SELECT employee_id, first_name, last_name FROM employees WHERE active = TRUE ORDER BY first_name, last_name");
}

// Get managers for filter dropdown (if HR admin)
$managers = [];
if ($userRole === 'hr_admin') {
    $managers = fetchAll("SELECT DISTINCT e.employee_id, e.first_name, e.last_name 
                         FROM employees e 
                         JOIN growth_evidence_entries gee ON e.employee_id = gee.manager_id 
                         ORDER BY e.first_name, e.last_name");
}

// Get statistics
$stats = $evidenceManager->getEvidenceStatistics($filters);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-cogs"></i> Evidence Management</h1>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTagModal">
                        <i class="fas fa-tag"></i> Create Tag
                    </button>
                    <a href="search.php" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i> Advanced Search
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : ($messageType === 'warning' ? 'warning' : 'success'); ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo number_format($stats['total_entries']); ?></h4>
                                    <p class="mb-0">Total Entries</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo number_format($stats['avg_rating'], 1); ?></h4>
                                    <p class="mb-0">Average Rating</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-star fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo number_format($stats['unique_employees']); ?></h4>
                                    <p class="mb-0">Employees</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo number_format($stats['positive_entries']); ?></h4>
                                    <p class="mb-0">Positive (4-5★)</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-thumbs-up fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter"></i> Filters
                        <button class="btn btn-sm btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                            Toggle Filters
                        </button>
                    </h5>
                </div>
                <div class="collapse show" id="filtersCollapse">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Content</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search in content...">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select class="form-select" id="employee_id" name="employee_id">
                                    <option value="">All Employees</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['employee_id']; ?>" <?php echo $filters['employee_id'] == $employee['employee_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($userRole === 'hr_admin'): ?>
                            <div class="col-md-3">
                                <label for="manager_id" class="form-label">Manager</label>
                                <select class="form-select" id="manager_id" name="manager_id">
                                    <option value="">All Managers</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?php echo $manager['employee_id']; ?>" <?php echo $filters['manager_id'] == $manager['employee_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-3">
                                <label for="dimension" class="form-label">Dimension</label>
                                <select class="form-select" id="dimension" name="dimension">
                                    <option value="">All Dimensions</option>
                                    <option value="responsibilities" <?php echo $filters['dimension'] === 'responsibilities' ? 'selected' : ''; ?>>Responsibilities</option>
                                    <option value="kpis" <?php echo $filters['dimension'] === 'kpis' ? 'selected' : ''; ?>>KPIs</option>
                                    <option value="competencies" <?php echo $filters['dimension'] === 'competencies' ? 'selected' : ''; ?>>Competencies</option>
                                    <option value="values" <?php echo $filters['dimension'] === 'values' ? 'selected' : ''; ?>>Values</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="min_rating" class="form-label">Min Rating</label>
                                <select class="form-select" id="min_rating" name="min_rating">
                                    <option value="">Any</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $filters['min_rating'] == $i ? 'selected' : ''; ?>><?php echo $i; ?>★</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="max_rating" class="form-label">Max Rating</label>
                                <select class="form-select" id="max_rating" name="max_rating">
                                    <option value="">Any</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $filters['max_rating'] == $i ? 'selected' : ''; ?>><?php echo $i; ?>★</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $filters['start_date']; ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $filters['end_date']; ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="tags" class="form-label">Tags</label>
                                <select class="form-select" id="tags" name="tags" multiple>
                                    <?php foreach ($availableTags as $tag): ?>
                                        <option value="<?php echo htmlspecialchars($tag['tag_name']); ?>" <?php echo in_array($tag['tag_name'], $filters['tags']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="include_archived" name="include_archived" <?php echo $filters['include_archived'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="include_archived">
                                        Include Archived Entries
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                                <a href="manage.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bulk Operations -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Bulk Operations</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="bulkOperationForm">
                        <input type="hidden" name="action" value="bulk_operation">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="operation" class="form-label">Operation</label>
                                <select class="form-select" id="operation" name="operation" required>
                                    <option value="">Select Operation</option>
                                    <option value="archive">Archive Entries</option>
                                    <option value="delete">Delete Entries</option>
                                    <option value="update_dimension">Update Dimension</option>
                                    <option value="add_tags">Add Tags</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4" id="operationDataContainer" style="display: none;">
                                <!-- Dynamic content based on operation -->
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-warning" id="bulkOperationBtn" disabled>
                                        <i class="fas fa-play"></i> Execute Operation
                                    </button>
                                    <span class="text-muted ms-2" id="selectedCount">0 entries selected</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Evidence Entries Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Evidence Entries 
                        <span class="badge bg-secondary"><?php echo number_format($pagination['total']); ?> total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($entries)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No evidence entries found</h5>
                            <p class="text-muted">Try adjusting your filters or search criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Manager</th>
                                        <th>Dimension</th>
                                        <th>Rating</th>
                                        <th>Content</th>
                                        <th>Tags</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $entry): ?>
                                        <tr class="<?php echo isset($entry['is_archived']) && $entry['is_archived'] ? 'table-secondary' : ''; ?>">
                                            <td>
                                                <input type="checkbox" name="entry_ids[]" value="<?php echo $entry['entry_id']; ?>" class="form-check-input entry-checkbox">
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($entry['entry_date'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($entry['employee_first_name'] . ' ' . $entry['employee_last_name']); ?></strong>
                                                <?php if (!empty($entry['employee_number'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($entry['employee_number']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($entry['manager_first_name'] . ' ' . $entry['manager_last_name']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo ucfirst($entry['dimension']); ?></span>
                                            </td>
                                            <td>
                                                <div class="rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $entry['star_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="content-preview" style="max-width: 300px;">
                                                    <?php echo htmlspecialchars(substr($entry['content'], 0, 100)); ?>
                                                    <?php if (strlen($entry['content']) > 100): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($entry['tags'])): ?>
                                                    <?php foreach (explode(',', $entry['tags']) as $tag): ?>
                                                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" onclick="viewEntry(<?php echo $entry['entry_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (!isset($entry['is_archived']) || !$entry['is_archived']): ?>
                                                        <button type="button" class="btn btn-outline-warning" onclick="archiveEntry(<?php echo $entry['entry_id']; ?>)">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-success" onclick="restoreEntry(<?php echo $entry['entry_id']; ?>)">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['pages'] > 1): ?>
                            <nav aria-label="Evidence entries pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagination['page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['page'] - 1])); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['pages'], $pagination['page'] + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['page'] < $pagination['pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['page'] + 1])); ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Tag Modal -->
<div class="modal fade" id="createTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create_tag">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tag_name" class="form-label">Tag Name</label>
                        <input type="text" class="form-control" id="tag_name" name="tag_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="tag_color" class="form-label">Tag Color</label>
                        <input type="color" class="form-control form-control-color" id="tag_color" name="tag_color" value="#007bff">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Evidence Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const entryCheckboxes = document.querySelectorAll('.entry-checkbox');
    const bulkOperationBtn = document.getElementById('bulkOperationBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const operationSelect = document.getElementById('operation');
    const operationDataContainer = document.getElementById('operationDataContainer');

    selectAllCheckbox.addEventListener('change', function() {
        entryCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkOperationState();
    });

    entryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkOperationState);
    });

    operationSelect.addEventListener('change', function() {
        updateOperationDataContainer();
        updateBulkOperationState();
    });

    function updateBulkOperationState() {
        const selectedCheckboxes = document.querySelectorAll('.entry-checkbox:checked');
        const count = selectedCheckboxes.length;
        
        selectedCountSpan.textContent = `${count} entries selected`;
        bulkOperationBtn.disabled = count === 0 || !operationSelect.value;
        
        // Update select all checkbox state
        if (count === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (count === entryCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    function updateOperationDataContainer() {
        const operation = operationSelect.value;
        let html = '';

        switch (operation) {
            case 'update_dimension':
                html = `
                    <label for="operation_dimension" class="form-label">New Dimension</label>
                    <select class="form-select" name="operation_data[dimension]" id="operation_dimension" required>
                        <option value="responsibilities">Responsibilities</option>
                        <option value="kpis">KPIs</option>
                        <option value="competencies">Competencies</option>
                        <option value="values">Values</option>
                    </select>
                `;
                break;
            case 'add_tags':
                html = `
                    <label for="operation_tags" class="form-label">Tags to Add</label>
                    <select class="form-select" name="operation_data[tags][]" id="operation_tags" multiple required>
                        <?php foreach ($availableTags as $tag): ?>
                            <option value="<?php echo $tag['tag_id']; ?>"><?php echo htmlspecialchars($tag['tag_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                `;
                break;
            case 'archive':
                html = `
                    <label for="operation_reason" class="form-label">Archive Reason</label>
                    <select class="form-select" name="operation_data[reason]" id="operation_reason" required>
                        <option value="manual">Manual Archive</option>
                        <option value="retention_policy">Retention Policy</option>
                        <option value="data_cleanup">Data Cleanup</option>
                    </select>
                `;
                break;
        }

        operationDataContainer.innerHTML = html;
        operationDataContainer.style.display = html ? 'block' : 'none';
    }

    // Confirm bulk operations
    document.getElementById('bulkOperationForm').addEventListener('submit', function(e) {
        const operation = operationSelect.value;
        const selectedCount = document.querySelectorAll('.entry-checkbox:checked').length;
        
        let confirmMessage = `Are you sure you want to ${operation} ${selectedCount} entries?`;
        
        if (operation === 'delete') {
            confirmMessage += ' This action cannot be undone.';
        }
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        }
    });
});

// Individual entry actions
function viewEntry(entryId) {
    // Open entry details in modal or new page
    window.open(`/api/evidence-details.php
                html = `
                    <label for="operation_dimension" class="form-label">New Dimension</label>
                    <select class="form-select" name="operation_data[dimension]" id="operation_dimension" required>
                        <option value="responsibilities">Responsibilities</option>
                        <option value="kpis">KPIs</option>
                        <option value="competencies">Competencies</option>
                        <option value="values">Values</option>
                    </select>
                `;
                break;
            case 'add_tags':
                html = `
                    <label for="operation_tags" class="form-label">Tags to Add</label>
                    <select class="form-select" name="operation_data[tags][]" id="operation_tags" multiple required>
                        <?php foreach ($availableTags as $tag): ?>
                            <option value="<?php echo $tag['tag_id']; ?>"><?php echo htmlspecialchars($tag['tag_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                `;
                break;
            case 'archive':
                html = `
                    <label for="operation_reason" class="form-label">Archive Reason</label>
                    <select class="form-select" name="operation_data[reason]" id="operation_reason" required>
                        <option value="manual">Manual Archive</option>
                        <option value="retention_policy">Retention Policy</option>
                        <option value="data_cleanup">Data Cleanup</option>
                    </select>
                `;
                break;
        }

        operationDataContainer.innerHTML = html;
        operationDataContainer.style.display = html ? 'block' : 'none';
    }

    // Confirm bulk operations
    document.getElementById('bulkOperationForm').addEventListener('submit', function(e) {
        const operation = operationSelect.value;
        const selectedCount = document.querySelectorAll('.entry-checkbox:checked').length;
        
        let confirmMessage = `Are you sure you want to ${operation} ${selectedCount} entries?`;
        
        if (operation === 'delete') {
            confirmMessage += ' This action cannot be undone.';
        }
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        }
    });
});

// Individual entry actions
function viewEntry(entryId) {
    // Open entry details in modal or new page
    window.open(`/api/evidence-details.php?entry_id=${entryId}`, '_blank');
}

function archiveEntry(entryId) {
    if (confirm('Are you sure you want to archive this evidence entry?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="archive_entry">
            <input type="hidden" name="entry_id" value="${entryId}">
            <input type="hidden" name="reason" value="manual">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function restoreEntry(entryId) {
    if (confirm('Are you sure you want to restore this evidence entry?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="restore_entry">
            <input type="hidden" name="entry_id" value="${entryId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>