<?php
/**
 * Employee Hierarchy/Organization Chart Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';

// Require authentication
requireAuth();

$pageTitle = 'Organization Chart';
$pageHeader = true;
$pageDescription = 'View the organizational hierarchy and reporting structure';

// Initialize Employee class
$employeeClass = new Employee();

// Get employee hierarchy
$hierarchy = $employeeClass->getEmployeeHierarchy();

// Get all employees for search functionality
$allEmployeesData = $employeeClass->getEmployees(1, 1000);
$allEmployees = $allEmployeesData['employees'];

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Organization Chart</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="expandAll()">
                        <i class="fas fa-expand-alt me-1"></i>Expand All
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="collapseAll()">
                        <i class="fas fa-compress-alt me-1"></i>Collapse All
                    </button>
                    <?php if (isHRAdmin()): ?>
                    <a href="/employees/create.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Add Employee
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Box -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="employeeSearch" placeholder="Search employees...">
                        </div>
                    </div>
                </div>

                <!-- Organization Chart -->
                <div id="orgChart" class="org-chart">
                    <?php if (empty($hierarchy)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No organizational structure found.</p>
                        <?php if (isHRAdmin()): ?>
                        <a href="/employees/create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add First Employee
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <?php echo renderHierarchy($hierarchy); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Details Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="employeeModalBody">
                <!-- Employee details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="viewEmployeeBtn" class="btn btn-primary">View Full Profile</a>
            </div>
        </div>
    </div>
</div>

<style>
.org-chart {
    font-family: Arial, sans-serif;
}

.org-node {
    display: inline-block;
    vertical-align: top;
    margin: 10px;
    text-align: center;
}

.org-card {
    background: #fff;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    min-width: 200px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.org-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.org-card.manager {
    border-color: #28a745;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.org-card.hr-admin {
    border-color: #dc3545;
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
}

.org-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    margin: 0 auto 10px;
}

.org-name {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
}

.org-position {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.org-department {
    color: #888;
    font-size: 12px;
}

.org-children {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #ddd;
    position: relative;
}

.org-children::before {
    content: '';
    position: absolute;
    top: -2px;
    left: 50%;
    width: 2px;
    height: 20px;
    background: #ddd;
    transform: translateX(-50%);
}

.collapsible {
    cursor: pointer;
}

.collapsed .org-children {
    display: none;
}

.expand-btn {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    cursor: pointer;
}
</style>

<script>
// Search functionality
document.getElementById('employeeSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const orgCards = document.querySelectorAll('.org-card');
    
    orgCards.forEach(card => {
        const name = card.querySelector('.org-name').textContent.toLowerCase();
        const position = card.querySelector('.org-position').textContent.toLowerCase();
        const department = card.querySelector('.org-department').textContent.toLowerCase();
        
        if (name.includes(searchTerm) || position.includes(searchTerm) || department.includes(searchTerm)) {
            card.style.display = 'block';
            card.style.backgroundColor = searchTerm ? '#fff3cd' : '';
        } else {
            card.style.display = searchTerm ? 'none' : 'block';
            card.style.backgroundColor = '';
        }
    });
});

// Expand/Collapse functionality
function expandAll() {
    document.querySelectorAll('.org-node').forEach(node => {
        node.classList.remove('collapsed');
    });
}

function collapseAll() {
    document.querySelectorAll('.org-node').forEach(node => {
        if (node.querySelector('.org-children')) {
            node.classList.add('collapsed');
        }
    });
}

// Employee card click handler
document.addEventListener('click', function(e) {
    if (e.target.closest('.org-card')) {
        const card = e.target.closest('.org-card');
        const employeeId = card.dataset.employeeId;
        showEmployeeDetails(employeeId);
    }
});

function showEmployeeDetails(employeeId) {
    // This would typically make an AJAX call to get employee details
    // For now, we'll redirect to the employee view page
    window.location.href = '/employees/view.php?id=' + employeeId;
}

// Toggle children visibility
function toggleChildren(button) {
    const node = button.closest('.org-node');
    node.classList.toggle('collapsed');
    button.innerHTML = node.classList.contains('collapsed') ? '+' : '-';
}
</script>

<?php
/**
 * Render hierarchy tree recursively
 */
function renderHierarchy($employees, $level = 0) {
    if (empty($employees)) {
        return '';
    }
    
    $html = '<div class="org-level">';
    
    foreach ($employees as $employee) {
        $roleClass = '';
        switch ($employee['role'] ?? 'employee') {
            case 'hr_admin':
                $roleClass = 'hr-admin';
                break;
            case 'manager':
                $roleClass = 'manager';
                break;
        }
        
        $hasChildren = !empty($employee['children']);
        
        $html .= '<div class="org-node' . ($hasChildren ? ' collapsible' : '') . '">';
        $html .= '<div class="org-card ' . $roleClass . '" data-employee-id="' . $employee['employee_id'] . '">';
        
        // Avatar
        $initials = strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1));
        $html .= '<div class="org-avatar">' . $initials . '</div>';
        
        // Name
        $html .= '<div class="org-name">' . htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) . '</div>';
        
        // Position
        $html .= '<div class="org-position">' . htmlspecialchars($employee['position'] ?? 'N/A') . '</div>';
        
        // Department
        $html .= '<div class="org-department">' . htmlspecialchars($employee['department'] ?? 'N/A') . '</div>';
        
        $html .= '</div>';
        
        // Children
        if ($hasChildren) {
            $html .= '<button class="expand-btn" onclick="toggleChildren(this)">-</button>';
            $html .= '<div class="org-children">';
            $html .= renderHierarchy($employee['children'], $level + 1);
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>

<?php include __DIR__ . '/../../templates/footer.php'; ?>