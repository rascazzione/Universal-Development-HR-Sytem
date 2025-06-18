<?php
/**
 * Create Evaluation Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Require manager or HR admin role
requireRole(['manager', 'hr_admin']);

$pageTitle = 'Create Evaluation';
$pageHeader = true;
$pageDescription = 'Create a new performance evaluation';

// Initialize classes
$employeeClass = new Employee();
$evaluationClass = new Evaluation();
$periodClass = new EvaluationPeriod();

// Get accessible employees and active periods
$accessibleEmployees = getAccessibleEmployees();
$activePeriods = $periodClass->getActivePeriods();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    protect_csrf();
    
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $periodId = (int)($_POST['period_id'] ?? 0);
    
    // Validation
    if (empty($employeeId)) {
        $errors[] = 'Please select an employee.';
    }
    
    if (empty($periodId)) {
        $errors[] = 'Please select an evaluation period.';
    }
    
    // Check if user can access this employee
    if ($employeeId && !canAccessEmployee($employeeId)) {
        $errors[] = 'You do not have permission to evaluate this employee.';
    }
    
    if (empty($errors)) {
        try {
            $evaluationData = [
                'employee_id' => $employeeId,
                'evaluator_id' => $_SESSION['user_id'],
                'period_id' => $periodId
            ];
            
            $evaluationId = $evaluationClass->createEvaluation($evaluationData);
            
            if ($evaluationId) {
                setFlashMessage('success', 'Evaluation created successfully.');
                redirect("/evaluation/edit.php?id=$evaluationId");
            } else {
                $errors[] = 'Failed to create evaluation. Please try again.';
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Create New Evaluation
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (empty($accessibleEmployees)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No employees available for evaluation. 
                    <?php if (isHRAdmin()): ?>
                    <a href="/employees/create.php">Add employees</a> first.
                    <?php else: ?>
                    Contact your HR administrator to assign team members.
                    <?php endif; ?>
                </div>
                <?php elseif (empty($activePeriods)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No active evaluation periods available. 
                    <?php if (isHRAdmin()): ?>
                    <a href="/admin/periods.php">Create evaluation periods</a> first.
                    <?php else: ?>
                    Contact your HR administrator to set up evaluation periods.
                    <?php endif; ?>
                </div>
                <?php else: ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">
                                <i class="fas fa-user me-1"></i>Employee *
                            </label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($accessibleEmployees as $employee): ?>
                                <option value="<?php echo $employee['employee_id']; ?>" 
                                        <?php echo (isset($_POST['employee_id']) && $_POST['employee_id'] == $employee['employee_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    <?php if ($employee['position']): ?>
                                        - <?php echo htmlspecialchars($employee['position']); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select an employee.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="period_id" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Evaluation Period *
                            </label>
                            <select class="form-select" id="period_id" name="period_id" required>
                                <option value="">Select Period</option>
                                <?php foreach ($activePeriods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>"
                                        <?php echo (isset($_POST['period_id']) && $_POST['period_id'] == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($period['period_name']); ?>
                                    (<?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select an evaluation period.
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> After creating the evaluation, you'll be redirected to the evaluation form 
                        where you can complete the performance assessment.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Evaluation
                        </button>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Help Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-question-circle me-2"></i>How to Create an Evaluation
                </h6>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li><strong>Select Employee:</strong> Choose the employee you want to evaluate from your accessible team members.</li>
                    <li><strong>Select Period:</strong> Choose the evaluation period that this assessment covers.</li>
                    <li><strong>Create:</strong> Click "Create Evaluation" to proceed to the detailed evaluation form.</li>
                    <li><strong>Complete:</strong> Fill out all sections of the evaluation form with detailed feedback.</li>
                    <li><strong>Submit:</strong> Review and submit the evaluation for approval.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>