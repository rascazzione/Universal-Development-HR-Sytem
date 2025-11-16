<?php
/**
 * Employee Import/Export Management Class
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Department.php';

class EmployeeImportExport {
    private $pdo;
    private $departmentClass;
    private $departmentCache = [];
    private $autoCreatedDepartments = [];
    private $passwordChangeColumnChecked = false;
    private $passwordChangeColumnExists = false;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        $this->departmentClass = new Department();
    }
    
    /**
     * Export basic employee data to CSV
     * @param array $filters
     * @return array
     */
    public function exportBasicCSV($filters = []) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // Apply filters
            if (!empty($filters['include_inactive'])) {
                // Include all employees
            } else {
                $whereClause .= " AND e.active = 1";
            }
            
            if (!empty($filters['department'])) {
                $whereClause .= " AND e.department = ?";
                $params[] = $filters['department'];
            }
            
            $sql = "SELECT 
                        e.employee_number,
                        e.first_name,
                        e.last_name,
                        u.email,
                        u.username,
                        u.role,
                        e.position,
                        e.department,
                        m.employee_number as manager_employee_number,
                        e.hire_date,
                        e.phone,
                        e.address,
                        e.active,
                        jt.position_title as job_template_title
                    FROM employees e
                    LEFT JOIN users u ON e.user_id = u.user_id
                    LEFT JOIN employees m ON e.manager_id = m.employee_id
                    LEFT JOIN job_position_templates jt ON e.job_template_id = jt.id
                    $whereClause
                    ORDER BY e.employee_number";
            
            $employees = fetchAll($sql, $params);
            
            // Generate CSV content
            $csvData = [];
            $csvData[] = [
                'employee_number', 'first_name', 'last_name', 'email', 'username', 'role',
                'position', 'department', 'manager_employee_number', 'hire_date', 'phone', 
                'address', 'active', 'job_template_title'
            ];
            
            foreach ($employees as $employee) {
                $csvData[] = [
                    $employee['employee_number'] ?? '',
                    $employee['first_name'] ?? '',
                    $employee['last_name'] ?? '',
                    $employee['email'] ?? '',
                    $employee['username'] ?? '',
                    $employee['role'] ?? 'employee',
                    $employee['position'] ?? '',
                    $employee['department'] ?? '',
                    $employee['manager_employee_number'] ?? '',
                    $employee['hire_date'] ?? '',
                    $employee['phone'] ?? '',
                    $employee['address'] ?? '',
                    $employee['active'] ? '1' : '0',
                    $employee['job_template_title'] ?? ''
                ];
            }
            
            return [
                'success' => true,
                'data' => $csvData,
                'count' => count($employees),
                'filename' => 'employees_basic_' . date('Y-m-d_H-i-s') . '.csv'
            ];
            
        } catch (Exception $e) {
            error_log("Export basic CSV error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to export employee data: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Export complete employee data as ZIP with multiple CSV files
     * @param array $filters
     * @return array
     */
    public function exportCompleteZIP($filters = []) {
        try {
            $tempDir = sys_get_temp_dir() . '/employee_export_' . uniqid();
            if (!mkdir($tempDir, 0755, true)) {
                throw new Exception("Failed to create temporary directory");
            }
            
            $files = [];
            
            // 1. Basic employee data
            $basicResult = $this->exportBasicCSV($filters);
            if (!$basicResult['success']) {
                throw new Exception($basicResult['error']);
            }
            $files['employees.csv'] = $this->arrayToCSV($basicResult['data']);
            
            // 2. Evaluations
            $files['evaluations.csv'] = $this->exportEvaluations($filters);
            
            // 3. Evaluation details
            $files['evaluation_kpi_results.csv'] = $this->exportEvaluationKPIResults($filters);
            $files['evaluation_competency_results.csv'] = $this->exportEvaluationCompetencyResults($filters);
            $files['evaluation_responsibility_results.csv'] = $this->exportEvaluationResponsibilityResults($filters);
            $files['evaluation_value_results.csv'] = $this->exportEvaluationValueResults($filters);
            
            // 4. Achievement journal
            $files['achievements.csv'] = $this->exportAchievements($filters);
            
            // 5. KUDOS recognitions
            $files['kudos.csv'] = $this->exportKudos($filters);
            
            // 6. OKRs and performance goals
            $files['okrs.csv'] = $this->exportOKRs($filters);
            
            // 7. Individual Development Plans
            $files['idps.csv'] = $this->exportIDPs($filters);
            $files['idp_activities.csv'] = $this->exportIDPActivities($filters);
            
            // 8. Growth evidence
            $files['growth_evidence.csv'] = $this->exportGrowthEvidence($filters);
            
            // 9. Manager feedback summary (aggregated only)
            $files['manager_feedback_summary.csv'] = $this->exportManagerFeedbackSummary($filters);
            
            // 10. Self-assessments
            $files['self_assessments.csv'] = $this->exportSelfAssessments($filters);
            
            // Write all CSV files to temp directory
            foreach ($files as $filename => $csvContent) {
                if ($csvContent) {
                    file_put_contents($tempDir . '/' . $filename, $csvContent);
                }
            }
            
            // Create ZIP file
            $zipFilename = 'employees_complete_' . date('Y-m-d_H-i-s') . '.zip';
            $zipPath = sys_get_temp_dir() . '/' . $zipFilename;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception("Failed to create ZIP file");
            }
            
            foreach ($files as $filename => $csvContent) {
                if ($csvContent && file_exists($tempDir . '/' . $filename)) {
                    $zip->addFile($tempDir . '/' . $filename, $filename);
                }
            }
            
            $zip->close();
            
            // Clean up temp directory
            $this->deleteDirectory($tempDir);
            
            return [
                'success' => true,
                'zip_path' => $zipPath,
                'filename' => $zipFilename,
                'files_included' => array_keys(array_filter($files))
            ];
            
        } catch (Exception $e) {
            error_log("Export complete ZIP error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to export complete data: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Import employees from CSV
     * @param string $csvFilePath
     * @param array $options
     * @return array
     */
    public function importEmployeesCSV($csvFilePath, $options = []) {
        try {
            // Validate file
            if (!file_exists($csvFilePath)) {
                throw new Exception("CSV file not found");
            }
            
            $fileSize = filesize($csvFilePath);
            if ($fileSize > 10 * 1024 * 1024) { // 10MB limit
                throw new Exception("File too large. Maximum size is 10MB");
            }
            
            // Parse CSV
            $csvData = $this->parseCSV($csvFilePath);
            if (empty($csvData)) {
                throw new Exception("CSV file is empty or invalid");
            }
            
            // Validate data
            $validationResult = $this->validateImportData($csvData);
            if (!$validationResult['success']) {
                return $validationResult;
            }
            
            // Process import
            return $this->processImport($validationResult['valid_rows'], $options);
            
        } catch (Exception $e) {
            error_log("Import CSV error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate import data
     * @param array $csvData
     * @return array
     */
    public function validateImportData($csvData) {
        try {
            $headers = array_shift($csvData);
            $requiredFields = ['employee_number', 'first_name', 'last_name', 'email'];
            $validRows = [];
            $errors = [];
            $warnings = [];
            $departmentsToCreate = [];
            $invalidEmployeeNumbers = [];
            
            // Check required headers
            foreach ($requiredFields as $field) {
                if (!in_array($field, $headers)) {
                    return [
                        'success' => false,
                        'error' => "Missing required column: $field"
                    ];
                }
            }
            
            // Get existing data for validation
            $existingEmployees = $this->getExistingEmployeeNumbers();
            $existingEmails = $this->getExistingEmails();
            $existingUsernames = $this->getExistingUsernames();
            $validManagers = $this->getValidManagerNumbers();
            $validJobTemplates = $this->getValidJobTemplates();
            $existingDepartments = $this->getExistingDepartmentNames();
            
            // Pre-map CSV employee numbers to their row number for dependency diagnostics
            $csvEmployeeMap = [];
            foreach ($csvData as $idx => $row) {
                if (count($row) !== count($headers)) {
                    continue;
                }
                $rowAssoc = array_combine($headers, $row);
                if ($rowAssoc === false) {
                    continue;
                }
                $empNumberInMap = trim($rowAssoc['employee_number'] ?? '');
                if ($empNumberInMap !== '') {
                    $csvEmployeeMap[$empNumberInMap] = $idx + 2;
                }
            }
            
            // Debug: Log available managers and job templates
            error_log("=== Validation Debug ===");
            error_log("Existing employees: " . implode(', ', array_slice($existingEmployees, 0, 10)) . (count($existingEmployees) > 10 ? '...' : ''));
            error_log("Valid managers: " . implode(', ', $validManagers));
            error_log("Valid job templates: " . implode(', ', array_keys($validJobTemplates)));
            
            foreach ($csvData as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // +2 because we removed headers and CSV rows start at 1
                $rowErrors = [];
                
                if (count($row) !== count($headers)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'data' => [],
                        'errors' => ["Column count mismatch. Expected " . count($headers) . " columns but found " . count($row)]
                    ];
                    continue;
                }
                
                $rowData = array_combine($headers, $row);
                if ($rowData === false) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'data' => [],
                        'errors' => ["Unable to parse row data. Please verify the CSV structure."]
                    ];
                    continue;
                }
                
                $employeeNumber = trim($rowData['employee_number'] ?? '');
                
                // Skip completely empty rows
                if ($employeeNumber === '' && empty(array_filter($rowData))) {
                    continue;
                }
                
                // Validate required fields
                foreach ($requiredFields as $field) {
                    if (empty($rowData[$field])) {
                        $rowErrors[] = "Missing required field: $field";
                    }
                }
                
                // Validate email format
                if (!empty($rowData['email']) && !filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Invalid email format";
                }
                
                // Check for duplicate employee_number in this import
                if ($employeeNumber !== '') {
                    $duplicateInImport = false;
                    foreach ($validRows as $validRow) {
                        if ($validRow['employee_number'] === $employeeNumber) {
                            $duplicateInImport = true;
                            break;
                        }
                    }
                    if ($duplicateInImport) {
                        $rowErrors[] = "Duplicate employee_number in import file";
                    }
                }
                
                // Check for duplicate email in this import
                $email = $rowData['email'] ?? '';
                if (!empty($email)) {
                    $duplicateEmailInImport = false;
                    foreach ($validRows as $validRow) {
                        if ($validRow['email'] === $email) {
                            $duplicateEmailInImport = true;
                            break;
                        }
                    }
                    if ($duplicateEmailInImport) {
                        $rowErrors[] = "Duplicate email in import file";
                    }
                }
                
                // Track departments that will need to be created
                $departmentName = trim($rowData['department'] ?? '');
                if ($departmentName !== '') {
                    $deptKey = strtolower($departmentName);
                    if (!isset($existingDepartments[$deptKey])) {
                        $departmentsToCreate[$deptKey] = $departmentName;
                    }
                }
                
                // Validate manager reference
                $managerNumber = trim($rowData['manager_employee_number'] ?? '');
                if ($managerNumber !== '') {
                    // Check if manager exists in existing employees or is being imported in this batch
                    $managerExists = in_array($managerNumber, $validManagers);
                    
                    // Also check if manager is in this import batch (but not yet in database)
                    if (!$managerExists) {
                        foreach ($validRows as $existingRow) {
                            if ($existingRow['employee_number'] === $managerNumber) {
                                $managerExists = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$managerExists) {
                        if (isset($invalidEmployeeNumbers[$managerNumber])) {
                            $rowErrors[] = "Manager '{$managerNumber}' has validation errors and must be fixed before assigning direct reports.";
                        } elseif (isset($csvEmployeeMap[$managerNumber]) && $csvEmployeeMap[$managerNumber] > $rowNumber) {
                            $rowErrors[] = "Manager '{$managerNumber}' is listed later in the file on row {$csvEmployeeMap[$managerNumber]}. Move the manager row above its direct reports or import it first.";
                        } else {
                            $rowErrors[] = "Manager '{$managerNumber}' does not exist in the system or in this CSV file.";
                        }
                    }
                }
                
                // Validate job template
                if (!empty($rowData['job_template_title'])) {
                    if (!array_key_exists($rowData['job_template_title'], $validJobTemplates)) {
                        $rowErrors[] = "Job template '" . $rowData['job_template_title'] . "' not found. Create it or remove the value before importing.";
                    }
                }
                
                // Validate date format
                if (!empty($rowData['hire_date'])) {
                    $date = DateTime::createFromFormat('Y-m-d', $rowData['hire_date']);
                    if (!$date || $date->format('Y-m-d') !== $rowData['hire_date']) {
                        $rowErrors[] = "Invalid hire_date format for value '" . $rowData['hire_date'] . "' (use YYYY-MM-DD)";
                    }
                }
                
                // Validate role
                if (!empty($rowData['role'])) {
                    $validRoles = ['hr_admin', 'manager', 'employee'];
                    if (!in_array($rowData['role'], $validRoles)) {
                        $rowErrors[] = "Invalid role (must be: " . implode(', ', $validRoles) . ")";
                    }
                }
                
                // Determine if this is an update or create
                $isUpdate = in_array($employeeNumber, $existingEmployees);
                $rowData['_is_update'] = $isUpdate;
                
                if ($isUpdate) {
                    $rowData['_action'] = 'UPDATE';
                } else {
                    $rowData['_action'] = 'CREATE';
                    
                    // For new employees, check email/username uniqueness
                    if (in_array($email, $existingEmails)) {
                        $rowErrors[] = "Email already exists in system";
                    }
                    
                    $username = $rowData['username'] ?? $this->generateUsername($rowData['email']);
                    if (in_array($username, $existingUsernames)) {
                        $rowErrors[] = "Username already exists in system";
                    }
                    $rowData['username'] = $username;
                }
                
                if (empty($rowErrors)) {
                    $validRows[] = $rowData;
                } else {
                    if ($employeeNumber !== '') {
                        $invalidEmployeeNumbers[$employeeNumber] = true;
                    }
                    $errors[] = [
                        'row' => $rowNumber,
                        'data' => $rowData,
                        'errors' => $rowErrors
                    ];
                }
            }
            
            return [
                'success' => true,
                'valid_rows' => $validRows,
                'errors' => $errors,
                'total_rows' => count($csvData),
                'valid_count' => count($validRows),
                'error_count' => count($errors),
                'warnings' => $warnings,
                'departments_to_create' => array_values($departmentsToCreate)
            ];
            
        } catch (Exception $e) {
            error_log("Validation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate CSV import template
     * @return array
     */
    public function generateImportTemplate() {
        $headers = [
            'employee_number', 'first_name', 'last_name', 'email', 'username', 'role',
            'position', 'department', 'manager_employee_number', 'hire_date', 'phone', 
            'address', 'active', 'job_template_title'
        ];
        
        $exampleData = [
            'EMP001', 'John', 'Doe', 'john.doe@company.com', 'jdoe', 'employee',
            'Software Engineer', 'IT', 'EMP100', '2024-01-15', '555-0100', 
            '123 Main St', '1', 'Software Developer'
        ];
        
        return [
            'headers' => $headers,
            'example' => $exampleData,
            'filename' => 'employee_import_template.csv'
        ];
    }
    
    // Helper methods for exporting specific data types
    
    private function exportEvaluations($filters) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND e.active = 1";
        }
        
        $sql = "SELECT 
                    ev.evaluation_id,
                    emp.employee_number,
                    emp.first_name,
                    emp.last_name,
                    ev.overall_rating,
                    ev.status,
                    ev.submitted_at,
                    ev.reviewed_at,
                    ev.approved_at,
                    ep.period_name,
                    evaluator.username as evaluator_username
                FROM evaluations ev
                JOIN employees emp ON ev.employee_id = emp.employee_id
                LEFT JOIN evaluation_periods ep ON ev.period_id = ep.period_id
                LEFT JOIN users evaluator ON ev.evaluator_id = evaluator.user_id
                LEFT JOIN employees e ON emp.employee_id = e.employee_id
                $whereClause
                ORDER BY ev.evaluation_id";
        
        $evaluations = fetchAll($sql, $params);
        
        if (empty($evaluations)) {
            return null;
        }
        
        $csvData = [];
        $csvData[] = [
            'evaluation_id', 'employee_number', 'employee_name', 'overall_rating', 
            'status', 'submitted_at', 'reviewed_at', 'approved_at', 'period_name', 'evaluator'
        ];
        
        foreach ($evaluations as $eval) {
            $csvData[] = [
                $eval['evaluation_id'],
                $eval['employee_number'],
                $eval['first_name'] . ' ' . $eval['last_name'],
                $eval['overall_rating'] ?? '',
                $eval['status'],
                $eval['submitted_at'] ?? '',
                $eval['reviewed_at'] ?? '',
                $eval['approved_at'] ?? '',
                $eval['period_name'] ?? '',
                $eval['evaluator_username'] ?? ''
            ];
        }
        
        return $this->arrayToCSV($csvData);
    }
    
    private function exportManagerFeedbackSummary($filters) {
        // Check if table exists
        if (!$this->tableExists('manager_feedback_summary')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND emp.active = 1";
        }
        
        try {
            $sql = "SELECT 
                        mfs.summary_id,
                        emp.employee_number,
                        emp.first_name,
                        emp.last_name,
                        ep.period_name,
                        mfs.total_responses,
                        mfs.avg_score,
                        mfs.last_aggregated_at
                    FROM manager_feedback_summary mfs
                    JOIN employees emp ON mfs.manager_employee_id = emp.employee_id
                    LEFT JOIN evaluation_periods ep ON mfs.period_id = ep.period_id
                    $whereClause
                    ORDER BY mfs.last_aggregated_at DESC";
            
            $summaries = fetchAll($sql, $params);
            
            if (empty($summaries)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'summary_id', 'manager_employee_number', 'manager_name', 'period_name',
                'total_responses', 'avg_score', 'last_aggregated_at'
            ];
            
            foreach ($summaries as $summary) {
                $csvData[] = [
                    $summary['summary_id'],
                    $summary['employee_number'],
                    $summary['first_name'] . ' ' . $summary['last_name'],
                    $summary['period_name'] ?? '',
                    $summary['total_responses'],
                    $summary['avg_score'],
                    $summary['last_aggregated_at'] ?? ''
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export manager feedback summary error: " . $e->getMessage());
            return null;
        }
    }
    
    private function exportSelfAssessments($filters) {
        // Check if table exists
        if (!$this->tableExists('employee_self_assessments')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND emp.active = 1";
        }
        
        try {
            $sql = "SELECT 
                        esa.self_assessment_id,
                        emp.employee_number,
                        emp.first_name,
                        emp.last_name,
                        ep.period_name,
                        esa.dimension,
                        esa.overall_score,
                        esa.status,
                        esa.submitted_at,
                        esa.created_at
                    FROM employee_self_assessments esa
                    JOIN employees emp ON esa.employee_id = emp.employee_id
                    LEFT JOIN evaluation_periods ep ON esa.period_id = ep.period_id
                    $whereClause
                    ORDER BY esa.created_at DESC";
            
            $assessments = fetchAll($sql, $params);
            
            if (empty($assessments)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'self_assessment_id', 'employee_number', 'employee_name', 'period_name',
                'dimension', 'overall_score', 'status', 'submitted_at', 'created_at'
            ];
            
            foreach ($assessments as $assessment) {
                $csvData[] = [
                    $assessment['self_assessment_id'],
                    $assessment['employee_number'],
                    $assessment['first_name'] . ' ' . $assessment['last_name'],
                    $assessment['period_name'] ?? '',
                    $assessment['dimension'],
                    $assessment['overall_score'] ?? '',
                    $assessment['status'],
                    $assessment['submitted_at'] ?? '',
                    $assessment['created_at']
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export self assessments error: " . $e->getMessage());
            return null;
        }
    }
    
    private function exportEvaluationKPIResults($filters) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND emp.active = 1";
        }
        
        $sql = "SELECT 
                    ekr.id,
                    ev.evaluation_id,
                    emp.employee_number,
                    ck.kpi_name,
                    ekr.target_value,
                    ekr.achieved_value,
                    ekr.score,
                    ekr.comments,
                    ekr.weight_percentage
                FROM evaluation_kpi_results ekr
                JOIN evaluations ev ON ekr.evaluation_id = ev.evaluation_id
                JOIN employees emp ON ev.employee_id = emp.employee_id
                JOIN company_kpis ck ON ekr.kpi_id = ck.id
                $whereClause
                ORDER BY ev.evaluation_id, ck.kpi_name";
        
        $results = fetchAll($sql, $params);
        
        if (empty($results)) {
            return null;
        }
        
        $csvData = [];
        $csvData[] = [
            'result_id', 'evaluation_id', 'employee_number', 'kpi_name', 'target_value',
            'achieved_value', 'score', 'comments', 'weight_percentage'
        ];
        
        foreach ($results as $result) {
            $csvData[] = [
                $result['id'],
                $result['evaluation_id'],
                $result['employee_number'],
                $result['kpi_name'],
                $result['target_value'] ?? '',
                $result['achieved_value'] ?? '',
                $result['score'] ?? '',
                $result['comments'] ?? '',
                $result['weight_percentage'] ?? ''
            ];
        }
        
        return $this->arrayToCSV($csvData);
    }
    
    private function exportEvaluationCompetencyResults($filters) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND emp.active = 1";
        }
        
        $sql = "SELECT 
                    ecr.id,
                    ev.evaluation_id,
                    emp.employee_number,
                    c.competency_name,
                    ecr.module_type,
                    ecr.required_technical_level_id,
                    rtl.level_name AS required_technical_level_name,
                    rtl.display_level AS required_technical_display,
                    ecr.achieved_technical_level_id,
                    atl.level_name AS achieved_technical_level_name,
                    atl.display_level AS achieved_technical_display,
                    ecr.required_soft_skill_level,
                    rsld.level_title AS required_soft_skill_title,
                    ecr.achieved_soft_skill_level,
                    asld.level_title AS achieved_soft_skill_title,
                    ecr.score,
                    ecr.comments,
                    ecr.weight_percentage
                FROM evaluation_competency_results ecr
                JOIN evaluations ev ON ecr.evaluation_id = ev.evaluation_id
                JOIN employees emp ON ev.employee_id = emp.employee_id
                JOIN competencies c ON ecr.competency_id = c.id
                LEFT JOIN technical_skill_levels rtl ON ecr.required_technical_level_id = rtl.id
                LEFT JOIN technical_skill_levels atl ON ecr.achieved_technical_level_id = atl.id
                LEFT JOIN soft_skill_definitions ssd ON c.competency_key = ssd.competency_key
                LEFT JOIN soft_skill_level_details rsld ON ssd.id = rsld.soft_skill_id AND ecr.required_soft_skill_level = rsld.level_number
                LEFT JOIN soft_skill_level_details asld ON ssd.id = asld.soft_skill_id AND ecr.achieved_soft_skill_level = asld.level_number
                $whereClause
                ORDER BY ev.evaluation_id, c.competency_name";
        
        $results = fetchAll($sql, $params);
        
        if (empty($results)) {
            return null;
        }
        
        $csvData = [];
        $csvData[] = [
            'result_id',
            'evaluation_id',
            'employee_number',
            'competency_name',
            'module_type',
            'required_level',
            'required_label',
            'achieved_level',
            'achieved_label',
            'score',
            'comments',
            'weight_percentage'
        ];
        
        foreach ($results as $result) {
            if (($result['module_type'] ?? '') === 'soft_skill') {
                $requiredLevel = $result['required_soft_skill_level'] ?? '';
                $requiredLabel = $result['required_soft_skill_title'] ?? '';
                $achievedLevel = $result['achieved_soft_skill_level'] ?? '';
                $achievedLabel = $result['achieved_soft_skill_title'] ?? '';
            } else {
                $requiredLevel = $result['required_technical_display'] ?? '';
                $requiredLabel = $result['required_technical_level_name'] ?? '';
                $achievedLevel = $result['achieved_technical_display'] ?? '';
                $achievedLabel = $result['achieved_technical_level_name'] ?? '';
            }
            
            $csvData[] = [
                $result['id'],
                $result['evaluation_id'],
                $result['employee_number'],
                $result['competency_name'],
                $result['module_type'] ?? '',
                $requiredLevel,
                $requiredLabel,
                $achievedLevel,
                $achievedLabel,
                $result['score'] ?? '',
                $result['comments'] ?? '',
                $result['weight_percentage'] ?? ''
            ];
        }
        
        return $this->arrayToCSV($csvData);
    }
    
    private function exportEvaluationResponsibilityResults($filters) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND emp.active = 1";
        }
        
        $sql = "SELECT 
                    err.id,
                    ev.evaluation_id,
                    emp.employee_number,
                    jtr.responsibility_text,
                    err.score,
                    err.comments,
                    err.weight_percentage
                FROM evaluation_responsibility_results err
                JOIN evaluations ev ON err.evaluation_id = ev.evaluation_id
                JOIN employees emp ON ev.employee_id = emp.employee_id
                JOIN job_template_responsibilities jtr ON err.responsibility_id = jtr.id
                $whereClause
                ORDER BY ev.evaluation_id, jtr.sort_order";
        
        $results = fetchAll($sql, $params);
        
        if (empty($results)) {
            return null;
        }
        
        $csvData = [];
        $csvData[] = [
            'result_id', 'evaluation_id', 'employee_number', 'responsibility_text',
            'score', 'comments', 'weight_percentage'
        ];
        
        foreach ($results as $result) {
            $csvData[] = [
                $result['id'],
                $result['evaluation_id'],
                $result['employee_number'],
                $result['responsibility_text'],
                $result['score'] ?? '',
                $result['comments'] ?? '',
                $result['weight_percentage'] ?? ''
            ];
        }
        
        return $this->arrayToCSV($csvData);
    }
    
    private function exportEvaluationValueResults($filters) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND emp.active = 1";
        }
        
        $sql = "SELECT 
                    evr.id,
                    ev.evaluation_id,
                    emp.employee_number,
                    cv.value_name,
                    evr.score,
                    evr.comments,
                    evr.weight_percentage
                FROM evaluation_value_results evr
                JOIN evaluations ev ON evr.evaluation_id = ev.evaluation_id
                JOIN employees emp ON ev.employee_id = emp.employee_id
                JOIN company_values cv ON evr.value_id = cv.id
                $whereClause
                ORDER BY ev.evaluation_id, cv.sort_order";
        
        $results = fetchAll($sql, $params);
        
        if (empty($results)) {
            return null;
        }
        
        $csvData = [];
        $csvData[] = [
            'result_id', 'evaluation_id', 'employee_number', 'value_name',
            'score', 'comments', 'weight_percentage'
        ];
        
        foreach ($results as $result) {
            $csvData[] = [
                $result['id'],
                $result['evaluation_id'],
                $result['employee_number'],
                $result['value_name'],
                $result['score'] ?? '',
                $result['comments'] ?? '',
                $result['weight_percentage'] ?? ''
            ];
        }
        
        return $this->arrayToCSV($csvData);
    }
    
    // Helper methods for import processing
    
    private function processImport($validRows, $options = []) {
        try {
            $this->pdo->beginTransaction();
            $this->autoCreatedDepartments = [];
            
            $created = 0;
            $updated = 0;
            $errors = [];
            
            foreach ($validRows as $rowData) {
                try {
                    if ($rowData['_action'] === 'CREATE') {
                        $this->createEmployee($rowData);
                        $created++;
                    } else {
                        $this->updateEmployee($rowData);
                        $updated++;
                    }
                } catch (Exception $e) {
                    $errors[] = [
                        'employee_number' => $rowData['employee_number'],
                        'error' => $e->getMessage()
                    ];
                    error_log("Import error for employee {$rowData['employee_number']}: " . $e->getMessage());
                    error_log("Employee data: " . print_r($rowData, true));
                }
            }
            
            if (!empty($errors)) {
                error_log("=== Import Error Summary ===");
                error_log("Total valid rows: " . count($validRows));
                error_log("Total errors: " . count($errors));
                error_log("Error percentage: " . (count($errors) / count($validRows) * 100) . "%");
                error_log("Errors: " . print_r($errors, true));
                
                // For debugging, let's be more permissive: only rollback if 90% of rows failed
                if (count($errors) > count($validRows) * 0.9) {
                    // If more than 90% of rows failed, rollback
                    error_log("Rolling back transaction - too many errors (over 90%)");
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'error' => 'Too many errors during import. Transaction rolled back.',
                        'errors' => $errors
                    ];
                } else {
                    error_log("Proceeding with import despite " . count($errors) . " errors");
                }
            }
            
            $this->pdo->commit();
            
            // Log the import operation
            logActivity($_SESSION['user_id'] ?? null, 'employee_import', 'employees', null, null, [
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors)
            ]);
            
            return [
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'total_processed' => $created + $updated,
                'auto_created_departments' => array_values($this->autoCreatedDepartments)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Import processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function createEmployee($rowData) {
        error_log("=== Creating Employee: " . $rowData['employee_number'] . " ===");
        
        // Create user account first
        $userClass = new User();
        $userData = [
            'username' => $rowData['username'],
            'email' => $rowData['email'],
            'password' => 'Welcome2024!', // Default password
            'role' => $rowData['role'] ?? 'employee'
        ];
        
        error_log("User data: " . print_r($userData, true));
        
        $userId = $userClass->createUser($userData);
        error_log("User created with ID: " . $userId);
        
        if (!$userId) {
            throw new Exception("Failed to create user account");
        }
        
        // Mark user to change password on first login
        $this->markUserPasswordChangeRequired($userId);
        
        // Ensure supporting data exists
        if (!empty($rowData['department'])) {
            $this->ensureDepartmentExists($rowData['department']);
        }
        
        // Create employee record
        $employeeClass = new Employee();
        $employeeData = [
            'user_id' => $userId,
            'employee_number' => $rowData['employee_number'],
            'first_name' => $rowData['first_name'],
            'last_name' => $rowData['last_name'],
            'position' => $rowData['position'] ?? null,
            'department' => $rowData['department'] ?? null,
            'hire_date' => $rowData['hire_date'] ?? null,
            'phone' => $rowData['phone'] ?? null,
            'address' => $rowData['address'] ?? null,
            'active' => isset($rowData['active']) ? (bool)$rowData['active'] : true
        ];
        
        // Resolve manager_id from manager_employee_number
        if (!empty($rowData['manager_employee_number'])) {
            $managerId = $this->getEmployeeIdByNumber($rowData['manager_employee_number']);
            error_log("Manager ID for " . $rowData['manager_employee_number'] . ": " . ($managerId ?? 'null'));
            if ($managerId) {
                $employeeData['manager_id'] = $managerId;
            }
        }
        
        // Resolve job_template_id from job_template_title
        if (!empty($rowData['job_template_title'])) {
            $jobTemplateId = $this->getJobTemplateIdByTitle($rowData['job_template_title']);
            error_log("Job template ID for '" . $rowData['job_template_title'] . "': " . ($jobTemplateId ?? 'null'));
            if ($jobTemplateId) {
                $employeeData['job_template_id'] = $jobTemplateId;
            }
        }
        
        $employeeId = $employeeClass->createEmployee($employeeData);
        error_log("Employee created with ID: " . $employeeId);
        
        if (!$employeeId) {
            throw new Exception("Failed to create employee record");
        }
        
        return $employeeId;
    }
    
    private function updateEmployee($rowData) {
        $employeeClass = new Employee();
        
        // Get existing employee
        $existingEmployee = $this->getEmployeeByNumber($rowData['employee_number']);
        if (!$existingEmployee) {
            throw new Exception("Employee not found for update");
        }
        
        // Update user account if needed
        if ($existingEmployee['user_id']) {
            $userClass = new User();
            $userData = [];
            
            if (!empty($rowData['email']) && $rowData['email'] !== $existingEmployee['email']) {
                $userData['email'] = $rowData['email'];
            }
            
            if (!empty($rowData['username']) && $rowData['username'] !== $existingEmployee['username']) {
                $userData['username'] = $rowData['username'];
            }
            
            if (!empty($rowData['role']) && $rowData['role'] !== $existingEmployee['role']) {
                $userData['role'] = $rowData['role'];
            }
            
            if (!empty($userData)) {
                $userClass->updateUser($existingEmployee['user_id'], $userData);
            }
        }
        
        // Update employee record
        $employeeData = [];
        $fields = ['first_name', 'last_name', 'position', 'department', 'hire_date', 'phone', 'address'];
        
        foreach ($fields as $field) {
            if (array_key_exists($field, $rowData)) {
                $employeeData[$field] = $rowData[$field];
            }
        }
        
        if (array_key_exists('department', $rowData) && !empty($rowData['department'])) {
            $this->ensureDepartmentExists($rowData['department']);
        }
        
        if (array_key_exists('active', $rowData)) {
            $employeeData['active'] = (bool)$rowData['active'];
        }
        
        // Resolve manager_id from manager_employee_number
        if (array_key_exists('manager_employee_number', $rowData)) {
            if (!empty($rowData['manager_employee_number'])) {
                $managerId = $this->getEmployeeIdByNumber($rowData['manager_employee_number']);
                if ($managerId) {
                    $employeeData['manager_id'] = $managerId;
                }
            } else {
                $employeeData['manager_id'] = null;
            }
        }
        
        // Resolve job_template_id from job_template_title
        if (array_key_exists('job_template_title', $rowData)) {
            if (!empty($rowData['job_template_title'])) {
                $jobTemplateId = $this->getJobTemplateIdByTitle($rowData['job_template_title']);
                if ($jobTemplateId) {
                    $employeeData['job_template_id'] = $jobTemplateId;
                }
            } else {
                $employeeData['job_template_id'] = null;
            }
        }
        
        if (!empty($employeeData)) {
            $employeeClass->updateEmployee($existingEmployee['employee_id'], $employeeData);
        }
        
        return $existingEmployee['employee_id'];
    }
    
    // Utility methods
    
    public function parseCSV($filePath) {
        $csvData = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            // Read the first line manually so we can handle BOM + delimiter detection
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                return [];
            }
            
            // Check for UTF-8 BOM
            if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
                $firstLine = substr($firstLine, 3);
            }
            
            $delimiter = $this->detectDelimiter($firstLine);
            
            // Parse first line manually
            $firstData = str_getcsv($firstLine, $delimiter);
            $csvData[] = $firstData;
            
            // Process remaining lines
            while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $csvData[] = $data;
            }
            
            fclose($handle);
        }
        return $csvData;
    }
    
    private function arrayToCSV($data) {
        $output = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }
    
    private function generateUsername($email) {
        $username = strtolower(substr($email, 0, strpos($email, '@')));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
        return $username;
    }

    private function detectDelimiter($sampleLine) {
        $delimiters = [',', ';', "\t", '|'];
        $maxCount = 0;
        $selectedDelimiter = ',';
        
        foreach ($delimiters as $delimiter) {
            $count = substr_count($sampleLine, $delimiter);
            if ($count > $maxCount) {
                $maxCount = $count;
                $selectedDelimiter = $delimiter;
            }
        }
        
        return $selectedDelimiter;
    }
    
    private function getExistingEmployeeNumbers() {
        $sql = "SELECT employee_number FROM employees";
        $result = fetchAll($sql);
        return array_column($result, 'employee_number');
    }
    
    private function getExistingEmails() {
        $sql = "SELECT email FROM users";
        $result = fetchAll($sql);
        return array_column($result, 'email');
    }
    
    private function getExistingUsernames() {
        $sql = "SELECT username FROM users";
        $result = fetchAll($sql);
        return array_column($result, 'username');
    }

    private function getExistingDepartmentNames() {
        $this->loadDepartmentCache();
        $departments = [];
        foreach ($this->departmentCache as $key => $data) {
            $departments[$key] = $data['name'];
        }
        return $departments;
    }

    private function loadDepartmentCache($forceReload = false) {
        if (!$forceReload && !empty($this->departmentCache)) {
            return;
        }

        $this->departmentCache = [];
        try {
            $sql = "SELECT id, department_name, is_active FROM departments";
            $result = fetchAll($sql);
            foreach ($result as $row) {
                $key = strtolower($row['department_name']);
                $this->departmentCache[$key] = [
                    'id' => $row['id'],
                    'name' => $row['department_name'],
                    'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true
                ];
            }
        } catch (Exception $e) {
            error_log("Failed to load department cache: " . $e->getMessage());
        }
    }
    
    private function getValidManagerNumbers() {
        $sql = "SELECT e.employee_number 
                FROM employees e 
                JOIN users u ON e.user_id = u.user_id 
                WHERE e.active = 1 AND (u.role = 'manager' OR u.role = 'hr_admin')";
        $result = fetchAll($sql);
        return array_column($result, 'employee_number');
    }
    
    private function getValidJobTemplates() {
        $sql = "SELECT id, position_title FROM job_position_templates WHERE is_active = 1";
        $result = fetchAll($sql);
        $templates = [];
        foreach ($result as $row) {
            $templates[$row['position_title']] = $row['id'];
        }
        return $templates;
    }
    
    private function getEmployeeIdByNumber($employeeNumber) {
        $sql = "SELECT employee_id FROM employees WHERE employee_number = ?";
        $result = fetchOne($sql, [$employeeNumber]);
        return $result ? $result['employee_id'] : null;
    }
    
    private function getJobTemplateIdByTitle($title) {
        $sql = "SELECT id FROM job_position_templates WHERE position_title = ? AND is_active = 1";
        $result = fetchOne($sql, [$title]);
        return $result ? $result['id'] : null;
    }

    private function markUserPasswordChangeRequired($userId) {
        if (!$this->passwordChangeColumnChecked) {
            $this->passwordChangeColumnExists = $this->columnExists('users', 'password_change_required');
            $this->passwordChangeColumnChecked = true;
        }

        if (!$this->passwordChangeColumnExists || !$userId) {
            return;
        }

        try {
            $sql = "UPDATE users SET password_change_required = 1 WHERE user_id = ?";
            updateRecord($sql, [$userId]);
        } catch (Exception $e) {
            error_log("Failed to flag password change for user {$userId}: " . $e->getMessage());
        }
    }

    private function columnExists($table, $column) {
        try {
            $sql = "SELECT COUNT(*) as cnt 
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = ? 
                      AND COLUMN_NAME = ?";
            $result = fetchOne($sql, [$table, $column]);
            return !empty($result) && (int)$result['cnt'] > 0;
        } catch (Exception $e) {
            error_log("Column existence check failed for {$table}.{$column}: " . $e->getMessage());
            return false;
        }
    }

    private function ensureDepartmentExists($departmentName) {
        $departmentName = trim($departmentName ?? '');
        if ($departmentName === '') {
            return null;
        }

        $this->loadDepartmentCache();
        $key = strtolower($departmentName);
        $existing = $this->departmentCache[$key] ?? null;

        if ($existing) {
            if (isset($existing['is_active']) && !$existing['is_active']) {
                try {
                    $this->departmentClass->restoreDepartment($existing['id']);
                    $this->departmentCache[$key]['is_active'] = true;
                } catch (Exception $e) {
                    error_log("Failed to reactivate department {$departmentName}: " . $e->getMessage());
                }
            }
            return $existing['id'];
        }

        try {
            $departmentId = $this->departmentClass->createDepartment([
                'department_name' => $departmentName,
                'description' => 'Created from employee import on ' . date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user_id'] ?? null
            ]);

            if ($departmentId) {
                $this->departmentCache[$key] = [
                    'id' => $departmentId,
                    'name' => $departmentName,
                    'is_active' => true
                ];
                $this->autoCreatedDepartments[$departmentName] = $departmentName;
                return $departmentId;
            }
        } catch (Exception $e) {
            error_log("Failed to auto-create department {$departmentName}: " . $e->getMessage());
        }

        return null;
    }
    
    private function getEmployeeByNumber($employeeNumber) {
        $sql = "SELECT e.*, u.username, u.email, u.role 
                FROM employees e 
                LEFT JOIN users u ON e.user_id = u.user_id 
                WHERE e.employee_number = ?";
        return fetchOne($sql, [$employeeNumber]);
    }
    
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    private function tableExists($tableName) {
        try {
            $sql = "SHOW TABLES LIKE ?";
            $result = fetchOne($sql, [$tableName]);
            return !empty($result);
        } catch (Exception $e) {
            error_log("Table existence check error for $tableName: " . $e->getMessage());
            return false;
        }
    }
    
    private function exportAchievements($filters) {
        // Check if table exists
        if (!$this->tableExists('achievement_journal')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND e.active = 1";
        }
        
        $sql = "SELECT 
                    aj.journal_id,
                    emp.employee_number,
                    emp.first_name,
                    emp.last_name,
                    aj.title,
                    aj.description,
                    aj.impact,
                    aj.date_of_achievement,
                    aj.visibility,
                    aj.created_at
                FROM achievement_journal aj
                JOIN employees emp ON aj.employee_id = emp.employee_id
                LEFT JOIN employees e ON emp.employee_id = e.employee_id
                $whereClause
                ORDER BY aj.date_of_achievement DESC";
        
        try {
            $achievements = fetchAll($sql, $params);
            
            if (empty($achievements)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'journal_id', 'employee_number', 'employee_name', 'title', 'description', 
                'impact', 'date_of_achievement', 'visibility', 'created_at'
            ];
            
            foreach ($achievements as $achievement) {
                $csvData[] = [
                    $achievement['journal_id'],
                    $achievement['employee_number'],
                    $achievement['first_name'] . ' ' . $achievement['last_name'],
                    $achievement['title'],
                    $achievement['description'],
                    $achievement['impact'] ?? '',
                    $achievement['date_of_achievement'],
                    $achievement['visibility'],
                    $achievement['created_at']
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export achievements error: " . $e->getMessage());
            return null;
        }
    }
    
    private function exportKudos($filters) {
        // Check if table exists
        if (!$this->tableExists('kudos_recognitions')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND sender.active = 1 AND recipient.active = 1";
        }
        
        try {
            $sql = "SELECT 
                        kr.kudos_id,
                        sender.employee_number as sender_employee_number,
                        sender.first_name as sender_first_name,
                        sender.last_name as sender_last_name,
                        recipient.employee_number as recipient_employee_number,
                        recipient.first_name as recipient_first_name,
                        recipient.last_name as recipient_last_name,
                        kc.name as category_name,
                        kr.message,
                        kr.points_awarded,
                        kr.created_at,
                        kr.acknowledged_at,
                        kr.is_public
                    FROM kudos_recognitions kr
                    JOIN employees sender ON kr.sender_employee_id = sender.employee_id
                    JOIN employees recipient ON kr.recipient_employee_id = recipient.employee_id
                    LEFT JOIN kudos_categories kc ON kr.category_id = kc.category_id
                    $whereClause
                    ORDER BY kr.created_at DESC";
            
            $kudos = fetchAll($sql, $params);
            
            if (empty($kudos)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'kudos_id', 'sender_employee_number', 'sender_name', 'recipient_employee_number', 
                'recipient_name', 'category', 'message', 'points_awarded', 'created_at', 
                'acknowledged_at', 'is_public'
            ];
            
            foreach ($kudos as $kudo) {
                $csvData[] = [
                    $kudo['kudos_id'],
                    $kudo['sender_employee_number'],
                    $kudo['sender_first_name'] . ' ' . $kudo['sender_last_name'],
                    $kudo['recipient_employee_number'],
                    $kudo['recipient_first_name'] . ' ' . $kudo['recipient_last_name'],
                    $kudo['category_name'] ?? '',
                    $kudo['message'],
                    $kudo['points_awarded'],
                    $kudo['created_at'],
                    $kudo['acknowledged_at'] ?? '',
                    $kudo['is_public'] ? '1' : '0'
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export kudos error: " . $e->getMessage());
            return null;
        }
    }
    
    private function exportOKRs($filters) {
        // Check if table exists
        if (!$this->tableExists('performance_goals')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND e.active = 1";
        }
        
        try {
            $sql = "SELECT 
                        pg.goal_id,
                        emp.employee_number,
                        emp.first_name,
                        emp.last_name,
                        pg.goal_title,
                        pg.goal_description,
                        pg.target_value,
                        pg.current_value,
                        pg.measurement_unit,
                        pg.due_date,
                        pg.status,
                        pg.okr_objective,
                        pg.okr_progress,
                        pg.okr_confidence,
                        pg.okr_cycle,
                        pg.created_at
                    FROM performance_goals pg
                    JOIN employees emp ON pg.employee_id = emp.employee_id
                    LEFT JOIN employees e ON emp.employee_id = e.employee_id
                    $whereClause
                    ORDER BY pg.created_at DESC";
            
            $okrs = fetchAll($sql, $params);
            
            if (empty($okrs)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'goal_id', 'employee_number', 'employee_name', 'goal_title', 'goal_description',
                'target_value', 'current_value', 'measurement_unit', 'due_date', 'status',
                'okr_objective', 'okr_progress', 'okr_confidence', 'okr_cycle', 'created_at'
            ];
            
            foreach ($okrs as $okr) {
                $csvData[] = [
                    $okr['goal_id'],
                    $okr['employee_number'],
                    $okr['first_name'] . ' ' . $okr['last_name'],
                    $okr['goal_title'],
                    $okr['goal_description'] ?? '',
                    $okr['target_value'] ?? '',
                    $okr['current_value'] ?? '',
                    $okr['measurement_unit'] ?? '',
                    $okr['due_date'] ?? '',
                    $okr['status'],
                    $okr['okr_objective'] ? '1' : '0',
                    $okr['okr_progress'] ?? '',
                    $okr['okr_confidence'] ?? '',
                    $okr['okr_cycle'] ?? '',
                    $okr['created_at']
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export OKRs error: " . $e->getMessage());
            return null;
        }
    }
    
    private function exportIDPs($filters) {
        // Check if table exists
        if (!$this->tableExists('individual_development_plans')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND e.active = 1";
        }
        
        try {
            $sql = "SELECT 
                        idp.idp_id,
                        emp.employee_number,
                        emp.first_name,
                        emp.last_name,
                        mgr.employee_number as manager_employee_number,
                        idp.career_goal,
                        idp.target_date,
                        idp.status,
                        ep.period_name,
                        idp.created_at,
                        idp.updated_at
                    FROM individual_development_plans idp
                    JOIN employees emp ON idp.employee_id = emp.employee_id
                    LEFT JOIN employees mgr ON idp.manager_id = mgr.employee_id
                    LEFT JOIN evaluation_periods ep ON idp.period_id = ep.period_id
                    LEFT JOIN employees e ON emp.employee_id = e.employee_id
                    $whereClause
                    ORDER BY idp.created_at DESC";
            
            $idps = fetchAll($sql, $params);
            
            if (empty($idps)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'idp_id', 'employee_number', 'employee_name', 'manager_employee_number',
                'career_goal', 'target_date', 'status', 'period_name', 'created_at', 'updated_at'
            ];
            
            foreach ($idps as $idp) {
                $csvData[] = [
                    $idp['idp_id'],
                    $idp['employee_number'],
                    $idp['first_name'] . ' ' . $idp['last_name'],
                    $idp['manager_employee_number'] ?? '',
                    $idp['career_goal'],
                    $idp['target_date'] ?? '',
                    $idp['status'],
                    $idp['period_name'] ?? '',
                    $idp['created_at'],
                    $idp['updated_at']
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export IDPs error: " . $e->getMessage());
            return null;
        }
    }
    
    private function exportIDPActivities($filters) {
        // Check if table exists
        if (!$this->tableExists('development_activities')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND e.active = 1";
        }
        
        try {
            $sql = "SELECT 
                        da.activity_id,
                        da.idp_id,
                        emp.employee_number,
                        emp.first_name,
                        emp.last_name,
                        da.title,
                        da.activity_type,
                        da.provider,
                        da.cost,
                        da.start_date,
                        da.end_date,
                        da.expected_outcome,
                        da.created_at
                    FROM development_activities da
                    JOIN individual_development_plans idp ON da.idp_id = idp.idp_id
                    JOIN employees emp ON idp.employee_id = emp.employee_id
                    LEFT JOIN employees e ON emp.employee_id = e.employee_id
                    $whereClause
                    ORDER BY da.start_date DESC";
            
            $activities = fetchAll($sql, $params);
            
            if (empty($activities)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'activity_id', 'idp_id', 'employee_number', 'employee_name', 'title',
                'activity_type', 'provider', 'cost', 'start_date', 'end_date', 
                'expected_outcome', 'created_at'
            ];
            
            foreach ($activities as $activity) {
                $csvData[] = [
                    $activity['activity_id'],
                    $activity['idp_id'],
                    $activity['employee_number'],
                    $activity['first_name'] . ' ' . $activity['last_name'],
                    $activity['title'],
                    $activity['activity_type'],
                    $activity['provider'] ?? '',
                    $activity['cost'] ?? '',
                    $activity['start_date'] ?? '',
                    $activity['end_date'] ?? '',
                    $activity['expected_outcome'] ?? '',
                    $activity['created_at']
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export IDP activities error: " . $e->getMessage());
            return null;
        }
    }
    
    private function exportGrowthEvidence($filters) {
        // Check if table exists
        if (!$this->tableExists('growth_evidence_entries')) {
            return null;
        }
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (empty($filters['include_inactive'])) {
            $whereClause .= " AND emp.active = 1 AND mgr.active = 1";
        }
        
        try {
            $sql = "SELECT 
                        gee.entry_id,
                        emp.employee_number,
                        emp.first_name,
                        emp.last_name,
                        mgr.employee_number as manager_employee_number,
                        gee.content,
                        gee.star_rating,
                        gee.dimension,
                        gee.entry_date,
                        gee.created_at
                    FROM growth_evidence_entries gee
                    JOIN employees emp ON gee.employee_id = emp.employee_id
                    JOIN employees mgr ON gee.manager_id = mgr.employee_id
                    $whereClause
                    ORDER BY gee.entry_date DESC";
            
            $evidence = fetchAll($sql, $params);
            
            if (empty($evidence)) {
                return null;
            }
            
            $csvData = [];
            $csvData[] = [
                'entry_id', 'employee_number', 'employee_name', 'manager_employee_number',
                'content', 'star_rating', 'dimension', 'entry_date', 'created_at'
            ];
            
            foreach ($evidence as $entry) {
                $csvData[] = [
                    $entry['entry_id'],
                    $entry['employee_number'],
                    $entry['first_name'] . ' ' . $entry['last_name'],
                    $entry['manager_employee_number'],
                    $entry['content'],
                    $entry['star_rating'],
                    $entry['dimension'],
                    $entry['entry_date'],
                    $entry['created_at']
                ];
            }
            
            return $this->arrayToCSV($csvData);
        } catch (Exception $e) {
            error_log("Export growth evidence error: " . $e->getMessage());
            return null;
        }
    }
}
?>
