<?php
/**
 * Advanced Evidence Search Interface
 * Phase 3: Advanced Features - Evidence Search
 * Growth Evidence System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/EvidenceManager.php';

// Require authentication
requireAuth();

$evidenceManager = new EvidenceManager();

// Get search parameters
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
    'include_archived' => isset($_GET['include_archived']),
    'content_length_min' => $_GET['content_length_min'] ?? '',
    'content_length_max' => $_GET['content_length_max'] ?? '',
    'has_attachments' => $_GET['has_attachments'] ?? '',
    'approval_status' => $_GET['approval_status'] ?? '',
    'evidence_source' => $_GET['evidence_source'] ?? ''
];

// Get pagination parameters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 20);

// Perform search if filters are applied
$searchResults = null;
$hasFilters = array_filter($filters, function($value) { return !empty($value); });

if (!empty($hasFilters)) {
    $searchResults = $evidenceManager->advancedSearch($filters, $page, $limit);
}

// Get available options for dropdowns
$availableTags = $evidenceManager->getAvailableTags();

// Get employees and managers based on user role
$userRole = $_SESSION['role'];
$employees = [];
$managers = [];

if ($userRole === 'manager') {
    $managerId = $_SESSION['employee_id'];
    $employees = fetchAll("SELECT employee_id, first_name, last_name FROM employees WHERE manager_id = ? AND active = TRUE", [$managerId]);
} else {
    $employees = fetchAll("SELECT employee_id, first_name, last_name FROM employees WHERE active = TRUE ORDER BY first_name, last_name");
}

if ($userRole === 'hr_admin') {
    $managers = fetchAll("SELECT DISTINCT e.employee_id, e.first_name, e.last_name 
                         FROM employees e 
                         JOIN growth_evidence_entries gee ON e.employee_id = gee.manager_id 
                         ORDER BY e.first_name, e.last_name");
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-search"></i> Advanced Evidence Search</h1>
                <div>
                    <a href="manage.php" class="btn btn-outline-primary">
                        <i class="fas fa-cogs"></i> Evidence Management
                    </a>
                    <button type="button" class="btn btn-primary" onclick="exportResults()">
                        <i class="fas fa-download"></i> Export Results
                    </button>
                </div>
            </div>

            <!-- Advanced Search Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Search Criteria</h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="searchForm">
                        <div class="row g-3">
                            <!-- Text Search -->
                            <div class="col-md-6">
                                <label for="search" class="form-label">Content Search</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                           placeholder="Search in evidence content...">
                                </div>
                                <div class="form-text">Use quotes for exact phrases, + for required words, - to exclude words</div>
                            </div>

                            <!-- Employee Filter -->
                            <div class="col-md-3">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select class="form-select" id="employee_id" name="employee_id">
                                    <option value="">All Employees</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['employee_id']; ?>" 
                                                <?php echo $filters['employee_id'] == $employee['employee_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Manager Filter -->
                            <?php if ($userRole === 'hr_admin'): ?>
                            <div class="col-md-3">
                                <label for="manager_id" class="form-label">Manager</label>
                                <select class="form-select" id="manager_id" name="manager_id">
                                    <option value="">All Managers</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?php echo $manager['employee_id']; ?>" 
                                                <?php echo $filters['manager_id'] == $manager['employee_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <!-- Dimension Filter -->
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

                            <!-- Rating Range -->
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

                            <!-- Date Range -->
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $filters['start_date']; ?>">
                            </div>

                            <div class="col-md-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $filters['end_date']; ?>">
                            </div>

                            <!-- Content Length -->
                            <div class="col-md-2">
                                <label for="content_length_min" class="form-label">Min Content Length</label>
                                <input type="number" class="form-control" id="content_length_min" name="content_length_min" 
                                       value="<?php echo $filters['content_length_min']; ?>" placeholder="Characters">
                            </div>

                            <div class="col-md-2">
                                <label for="content_length_max" class="form-label">Max Content Length</label>
                                <input type="number" class="form-control" id="content_length_max" name="content_length_max" 
                                       value="<?php echo $filters['content_length_max']; ?>" placeholder="Characters">
                            </div>

                            <!-- Tags -->
                            <div class="col-md-4">
                                <label for="tags" class="form-label">Tags</label>
                                <select class="form-select" id="tags" name="tags" multiple>
                                    <?php foreach ($availableTags as $tag): ?>
                                        <option value="<?php echo htmlspecialchars($tag['tag_name']); ?>" 
                                                <?php echo in_array($tag['tag_name'], $filters['tags']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple tags</div>
                            </div>

                            <!-- Additional Filters -->
                            <div class="col-md-2">
                                <label for="has_attachments" class="form-label">Attachments</label>
                                <select class="form-select" id="has_attachments" name="has_attachments">
                                    <option value="">Any</option>
                                    <option value="yes" <?php echo $filters['has_attachments'] === 'yes' ? 'selected' : ''; ?>>With Attachments</option>
                                    <option value="no" <?php echo $filters['has_attachments'] === 'no' ? 'selected' : ''; ?>>Without Attachments</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="approval_status" class="form-label">Approval Status</label>
                                <select class="form-select" id="approval_status" name="approval_status">
                                    <option value="">Any</option>
                                    <option value="none" <?php echo $filters['approval_status'] === 'none' ? 'selected' : ''; ?>>No Approval Required</option>
                                    <option value="pending" <?php echo $filters['approval_status'] === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="approved" <?php echo $filters['approval_status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $filters['approval_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="evidence_source" class="form-label">Evidence Source</label>
                                <select class="form-select" id="evidence_source" name="evidence_source">
                                    <option value="">Any Source</option>
                                    <option value="manager_feedback" <?php echo $filters['evidence_source'] === 'manager_feedback' ? 'selected' : ''; ?>>Manager Feedback</option>
                                    <option value="self_assessment" <?php echo $filters['evidence_source'] === 'self_assessment' ? 'selected' : ''; ?>>Self Assessment</option>
                                    <option value="peer_feedback" <?php echo $filters['evidence_source'] === 'peer_feedback' ? 'selected' : ''; ?>>Peer Feedback</option>
                                    <option value="customer_feedback" <?php echo $filters['evidence_source'] === 'customer_feedback' ? 'selected' : ''; ?>>Customer Feedback</option>
                                </select>
                            </div>

                            <!-- Results per page -->
                            <div class="col-md-2">
                                <label for="limit" class="form-label">Results per page</label>
                                <select class="form-select" id="limit" name="limit">
                                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>

                            <!-- Checkboxes -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="include_archived" name="include_archived" 
                                           <?php echo $filters['include_archived'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="include_archived">
                                        Include Archived Entries
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search Evidence
                                </button>
                                <a href="search.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear All
                                </a>
                                <button type="button" class="btn btn-outline-info" onclick="saveSearch()">
                                    <i class="fas fa-save"></i> Save Search
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="loadSavedSearch()">
                                    <i class="fas fa-folder-open"></i> Load Saved Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search Results -->
            <?php if ($searchResults !== null): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Search Results 
                                <span class="badge bg-primary"><?php echo number_format($searchResults['pagination']['total']); ?> found</span>
                            </h5>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleView()">
                                    <i class="fas fa-th" id="viewToggleIcon"></i> <span id="viewToggleText">Grid View</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($searchResults['results'])): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5>No evidence entries found</h5>
                                <p class="text-muted">Try adjusting your search criteria or filters.</p>
                            </div>
                        <?php else: ?>
                            <!-- List View (Default) -->
                            <div id="listView">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Employee</th>
                                                <th>Manager</th>
                                                <th>Dimension</th>
                                                <th>Rating</th>
                                                <th>Content Preview</th>
                                                <th>Tags</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($searchResults['results'] as $entry): ?>
                                                <tr class="<?php echo isset($entry['is_archived']) && $entry['is_archived'] ? 'table-secondary' : ''; ?>">
                                                    <td>
                                                        <?php echo date('M j, Y', strtotime($entry['entry_date'])); ?>
                                                        <?php if (isset($entry['is_archived']) && $entry['is_archived']): ?>
                                                            <br><small class="text-muted"><i class="fas fa-archive"></i> Archived</small>
                                                        <?php endif; ?>
                                                    </td>
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
                                                            <?php 
                                                            $content = htmlspecialchars($entry['content']);
                                                            $preview = substr($content, 0, 150);
                                                            echo $preview;
                                                            if (strlen($content) > 150): ?>...<?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($entry['attachment_count'])): ?>
                                                            <br><small class="text-info"><i class="fas fa-paperclip"></i> <?php echo $entry['attachment_count']; ?> attachment(s)</small>
                                                        <?php endif; ?>
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
                                                            <button type="button" class="btn btn-outline-primary" onclick="viewEntryDetails(<?php echo $entry['entry_id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-info" onclick="addToComparison(<?php echo $entry['entry_id']; ?>)">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Grid View -->
                            <div id="gridView" style="display: none;">
                                <div class="row">
                                    <?php foreach ($searchResults['results'] as $entry): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100 <?php echo isset($entry['is_archived']) && $entry['is_archived'] ? 'border-secondary' : ''; ?>">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-primary"><?php echo ucfirst($entry['dimension']); ?></span>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $entry['star_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <?php echo htmlspecialchars($entry['employee_first_name'] . ' ' . $entry['employee_last_name']); ?>
                                                        <small class="text-muted">by <?php echo htmlspecialchars($entry['manager_first_name'] . ' ' . $entry['manager_last_name']); ?></small>
                                                    </h6>
                                                    <p class="card-text">
                                                        <?php echo htmlspecialchars(substr($entry['content'], 0, 120)); ?>
                                                        <?php if (strlen($entry['content']) > 120): ?>...<?php endif; ?>
                                                    </p>
                                                    <?php if (!empty($entry['tags'])): ?>
                                                        <div class="mb-2">
                                                            <?php foreach (explode(',', $entry['tags']) as $tag): ?>
                                                                <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between align-items-center">
                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($entry['entry_date'])); ?></small>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary" onclick="viewEntryDetails(<?php echo $entry['entry_id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-info" onclick="addToComparison(<?php echo $entry['entry_id']; ?>)">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <?php if ($searchResults['pagination']['pages'] > 1): ?>
                                <nav aria-label="Search results pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($searchResults['pagination']['page'] > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $searchResults['pagination']['page'] - 1])); ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $searchResults['pagination']['page'] - 2); $i <= min($searchResults['pagination']['pages'], $searchResults['pagination']['page'] + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $searchResults['pagination']['page'] ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($searchResults['pagination']['page'] < $searchResults['pagination']['pages']): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $searchResults['pagination']['page'] + 1])); ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Comparison Panel -->
<div id="comparisonPanel" class="position-fixed bottom-0 end-0 bg-white border shadow-lg" style="width: 300px; max-height: 400px; display: none; z-index: 1050;">
    <div class="p-3 border-bottom">
        <h6 class="mb-0">Evidence Comparison <span id="comparisonCount" class="badge bg-primary">0</span></h6>
        <button type="button" class="btn-close float-end" onclick="clearComparison()"></button>
    </div>
    <div class="p-3" style="max-height: 300px; overflow-y: auto;">
        <div id="comparisonList"></div>
        <button type="button" class="btn btn-primary btn-sm w-100 mt-2" onclick="compareEntries()" disabled id="compareBtn">
            Compare Selected
        </button>
    </div>
</div>

<script>
// Advanced Search JavaScript
let comparisonEntries = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize multi-select for tags
    if (document.getElementById('tags')) {
        // You can integrate a multi-select library here like Select2 or Choices.js
    }
});

function toggleView() {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const toggleIcon = document.getElementById('viewToggleIcon');
    const toggleText = document.getElementById('viewToggleText');
    
    if (listView.style.display === 'none') {
        listView.style.display = 'block';
        gridView.style.display = 'none';
        toggleIcon.className = 'fas fa-th';
        toggleText.textContent = 'Grid View';
    } else {
        listView.style.display = 'none';
        gridView.style.display = 'block';
        toggleIcon.className = 'fas fa-list';
        toggleText.textContent = 'List View';
    }
}

function viewEntryDetails(entryId) {
    window.open(`/api/evidence-details.php?entry_id=${entryId}`, '_blank');
}

function addToComparison(entryId) {
    if (!comparisonEntries.includes(entryId)) {
        comparisonEntries.push(entryId);
        updateComparisonPanel();
    }
}

function removeFromComparison(entryId) {
    comparisonEntries = comparisonEntries.filter(id => id !== entryId);
    updateComparisonPanel();
}

function updateComparisonPanel() {
    const panel = document.getElementById('comparisonPanel');
    const count = document.getElementById('comparisonCount');
    const list = document.getElementById('comparisonList');
    const compareBtn = document.getElementById('compareBtn');
    
    count.textContent = comparisonEntries.length;
    
    if (comparisonEntries.length > 0) {
        panel.style.display = 'block';
        list.innerHTML = comparisonEntries.map(id => 
            `<div class="d-flex justify-content-between align-items-center mb-1">
                <small>Entry #${id}</small>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFromComparison(${id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>`
        ).join('');
        
        compareBtn.disabled = comparisonEntries.length < 2;
    } else {
        panel.style.display = 'none';
    }
}

function clearComparison() {
    comparisonEntries = [];
    updateComparisonPanel();
}

function compareEntries() {
    if (comparisonEntries.length >= 2) {
        const url = `/evidence/compare.php?entries=${comparisonEntries.join(',')}`;
        window.open(url, '_blank');
    }
}

function saveSearch() {
    const searchParams = new URLSearchParams(window.location.search);
    const searchName = prompt('Enter a name for this search:');
    
    if (searchName) {
        const savedSearches = JSON.parse(localStorage.getItem('savedEvidenceSearches') || '{}');
        savedSearches[searchName] = Object.fromEntries(searchParams);
        localStorage.setItem('savedEvidenceSearches', JSON.stringify(savedSearches));
        alert('Search saved successfully!');
    }
}

function loadSavedSearch() {
    const savedSearches = JSON.parse(localStorage.getItem
('savedEvidenceSearches') || '{}');
    
    if (Object.keys(savedSearches).length === 0) {
        alert('No saved searches found.');
        return;
    }
    
    const searchNames = Object.keys(savedSearches);
    const selectedSearch = prompt(`Select a saved search:\n${searchNames.map((name, index) => `${index + 1}. ${name}`).join('\n')}\n\nEnter the number:`);
    
    if (selectedSearch && searchNames[selectedSearch - 1]) {
        const searchParams = savedSearches[searchNames[selectedSearch - 1]];
        const url = new URL(window.location.href);
        url.search = new URLSearchParams(searchParams).toString();
        window.location.href = url.toString();
    }
}

function exportResults() {
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set('export', 'csv');
    window.open(`/api/evidence-export.php?${searchParams.toString()}`, '_blank');
}

// Quick filter buttons
function applyQuickFilter(filterType, value) {
    const form = document.getElementById('searchForm');
    const input = form.querySelector(`[name="${filterType}"]`);
    
    if (input) {
        if (input.type === 'checkbox') {
            input.checked = value;
        } else {
            input.value = value;
        }
        form.submit();
    }
}

// Add quick filter buttons
document.addEventListener('DOMContentLoaded', function() {
    const quickFilters = document.createElement('div');
    quickFilters.className = 'mb-3';
    quickFilters.innerHTML = `
        <div class="btn-toolbar" role="toolbar">
            <div class="btn-group me-2" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="applyQuickFilter('min_rating', '4')">High Rated (4-5★)</button>
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="applyQuickFilter('max_rating', '2')">Low Rated (1-2★)</button>
            </div>
            <div class="btn-group me-2" role="group">
                <button type="button" class="btn btn-outline-info btn-sm" onclick="applyQuickFilter('start_date', '${new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0]}')">Last 30 Days</button>
                <button type="button" class="btn btn-outline-info btn-sm" onclick="applyQuickFilter('start_date', '${new Date(Date.now() - 7*24*60*60*1000).toISOString().split('T')[0]}')">Last 7 Days</button>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-success btn-sm" onclick="applyQuickFilter('has_attachments', 'yes')">With Attachments</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyQuickFilter('include_archived', true)">Include Archived</button>
            </div>
        </div>
    `;
    
    const searchForm = document.querySelector('.card-body form');
    if (searchForm) {
        searchForm.parentNode.insertBefore(quickFilters, searchForm);
    }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>