<?php
/**
 * Give Feedback Page
 * Growth Evidence System - Continuous Performance Management
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/GrowthEvidenceJournal.php';
require_once __DIR__ . '/../../classes/MediaManager.php';

// Require authentication
requireAuth();

// Only managers and HR admins can give feedback
if (!in_array($_SESSION['user_role'], ['manager', 'hr_admin'])) {
    setFlashMessage('You do not have permission to give feedback.', 'error');
    redirect('/dashboard.php');
}

$pageTitle = 'Give Feedback';
$pageHeader = true;
$pageDescription = 'Provide feedback to your team members through the Growth Evidence System';

// Initialize classes
$employeeClass = new Employee();
$journalClass = new GrowthEvidenceJournal();
$mediaManager = new MediaManager();

// Get employee ID from URL
$employeeId = $_GET['employee_id'] ?? null;
if (!$employeeId) {
    setFlashMessage('Employee ID is required.', 'error');
    redirect('/employees/list.php');
}

// Get employee details
$employee = $employeeClass->getEmployeeById($employeeId);
if (!$employee) {
    setFlashMessage('Employee not found.', 'error');
    redirect('/employees/list.php');
}

// Check if current user can give feedback to this employee
$currentUserEmployeeId = $_SESSION['employee_id'] ?? null;
if ($_SESSION['user_role'] === 'manager' && $employee['manager_id'] != $currentUserEmployeeId) {
    setFlashMessage('You can only give feedback to your direct reports.', 'error');
    redirect('/employees/list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['content', 'star_rating', 'dimension', 'entry_date'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Prepare entry data
        $entryData = [
            'employee_id' => $employeeId,
            'manager_id' => $currentUserEmployeeId,
            'content' => $_POST['content'],
            'star_rating' => (int)$_POST['star_rating'],
            'dimension' => $_POST['dimension'],
            'entry_date' => $_POST['entry_date']
        ];
        
        // Create evidence entry
        $entryId = $journalClass->createEntry($entryData);
        
        // Handle file uploads if any
        if (!empty($_FILES['attachments']['name'][0])) {
            $mediaManager = new MediaManager();
            foreach ($_FILES['attachments']['error'] as $key => $error) {
                if ($error === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['attachments']['name'][$key],
                        'type' => $_FILES['attachments']['type'][$key],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                        'error' => $_FILES['attachments']['error'][$key],
                        'size' => $_FILES['attachments']['size'][$key]
                    ];
                    
                    try {
                        $mediaManager->uploadFile($file, $entryId);
                    } catch (Exception $e) {
                        error_log("File upload error: " . $e->getMessage());
                        // Continue with other uploads even if one fails
                    }
                }
            }
        }
        
        setFlashMessage('Feedback submitted successfully!', 'success');
        redirect("/employees/view.php?id=$employeeId");
        
    } catch (Exception $e) {
        error_log("Create feedback error: " . $e->getMessage());
        setFlashMessage('Error submitting feedback: ' . $e->getMessage(), 'error');
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Give Feedback to <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="content" class="form-label">Feedback Content *</label>
                                <textarea class="form-control" id="content" name="content" rows="6" required 
                                          placeholder="Describe the specific behavior, achievement, or area for improvement you're providing feedback on. Include concrete examples when possible."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                <div class="form-text">Be specific and provide examples. This feedback will be used in performance evaluations.</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dimension" class="form-label">Dimension *</label>
                                        <select class="form-select" id="dimension" name="dimension" required>
                                            <option value="">Select a dimension</option>
                                            <option value="responsibilities" <?php echo (($_POST['dimension'] ?? '') === 'responsibilities') ? 'selected' : ''; ?>>Key Responsibilities</option>
                                            <option value="kpis" <?php echo (($_POST['dimension'] ?? '') === 'kpis') ? 'selected' : ''; ?>>KPIs</option>
                                            <option value="competencies" <?php echo (($_POST['dimension'] ?? '') === 'competencies') ? 'selected' : ''; ?>>Competencies</option>
                                            <option value="values" <?php echo (($_POST['dimension'] ?? '') === 'values') ? 'selected' : ''; ?>>Company Values</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="star_rating" class="form-label">Rating (1-5 stars) *</label>
                                        <select class="form-select" id="star_rating" name="star_rating" required>
                                            <option value="">Select rating</option>
                                            <option value="1" <?php echo (($_POST['star_rating'] ?? '') === '1') ? 'selected' : ''; ?>>★☆☆☆☆ - Needs Improvement</option>
                                            <option value="2" <?php echo (($_POST['star_rating'] ?? '') === '2') ? 'selected' : ''; ?>>★★☆☆☆ - Below Average</option>
                                            <option value="3" <?php echo (($_POST['star_rating'] ?? '') === '3') ? 'selected' : ''; ?>>★★★☆☆ - Meets Expectations</option>
                                            <option value="4" <?php echo (($_POST['star_rating'] ?? '') === '4') ? 'selected' : ''; ?>>★★★★☆ - Exceeds Expectations</option>
                                            <option value="5" <?php echo (($_POST['star_rating'] ?? '') === '5') ? 'selected' : ''; ?>>★★★★★ - Outstanding</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="entry_date" class="form-label">Feedback Date *</label>
                                        <input type="date" class="form-control" id="entry_date" name="entry_date" 
                                               value="<?php echo htmlspecialchars($_POST['entry_date'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Attachments</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple 
                                       accept="image/*,video/*,.pdf,.doc,.docx">
                                <div class="form-text">You can attach images, videos, or documents to support your feedback.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Feedback Guidelines</h6>
                                    <ul class="small">
                                        <li><strong>Be Specific:</strong> Describe concrete behaviors or outcomes</li>
                                        <li><strong>Be Timely:</strong> Provide feedback close to when the event occurred</li>
                                        <li><strong>Be Balanced:</strong> Include both positive recognition and constructive feedback</li>
                                        <li><strong>Be Actionable:</strong> Suggest clear next steps when appropriate</li>
                                        <li><strong>Be Respectful:</strong> Focus on behaviors and outcomes, not personality traits</li>
                                    </ul>
                                    
                                    <h6 class="card-title mt-3">Rating Scale</h6>
                                    <ul class="small">
                                        <li><strong>5 Stars:</strong> Exceptional performance, consistently exceeds expectations</li>
                                        <li><strong>4 Stars:</strong> Strong performance, exceeds expectations</li>
                                        <li><strong>3 Stars:</strong> Meets expectations consistently</li>
                                        <li><strong>2 Stars:</strong> Below expectations, needs improvement</li>
                                        <li><strong>1 Star:</strong> Significantly below expectations, requires immediate attention</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/employees/view.php?id=<?php echo $employeeId; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Employee
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
