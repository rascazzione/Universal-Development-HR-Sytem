<?php
/**
 * Evaluation Period Management Class for Continuous Performance System
 * Enhanced for evidence-based evaluations
 */

require_once __DIR__ . '/../config/config.php';

class EvaluationPeriod {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create new evaluation period
     * @param array $periodData
     * @return int|false
     */
    public function createPeriod($periodData) {
        try {
            // Validate required fields
            $required = ['period_name', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (empty($periodData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Validate dates
            $startDate = new DateTime($periodData['start_date']);
            $endDate = new DateTime($periodData['end_date']);
            
            if ($startDate >= $endDate) {
                throw new Exception("End date must be after start date");
            }
            
            // Check for overlapping periods
            if ($this->hasOverlappingPeriod($periodData['start_date'], $periodData['end_date'])) {
                throw new Exception("Period overlaps with existing evaluation period");
            }
            
            // Insert period
            $sql = "INSERT INTO evaluation_periods (period_name, period_type, start_date, end_date, status, description, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $periodId = insertRecord($sql, [
                $periodData['period_name'],
                $periodData['period_type'] ?? 'custom',
                $periodData['start_date'],
                $periodData['end_date'],
                $periodData['status'] ?? 'draft',
                $periodData['description'] ?? null,
                $_SESSION['user_id'] ?? null
            ]);
            
            // Log period creation
            logActivity($_SESSION['user_id'] ?? null, 'period_created', 'evaluation_periods', $periodId, null, $periodData);
            
            return $periodId;
        } catch (Exception $e) {
            error_log("Create period error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update evaluation period
     * @param int $periodId
     * @param array $periodData
     * @return bool
     */
    public function updatePeriod($periodId, $periodData) {
        try {
            // Get current period data for logging
            $currentPeriod = $this->getPeriodById($periodId);
            if (!$currentPeriod) {
                throw new Exception("Period not found");
            }
            
            $updateFields = [];
            $params = [];
            
            // Build dynamic update query
            $allowedFields = [
                'period_name', 'period_type', 'start_date', 'end_date', 
                'status', 'description'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $periodData)) {
                    $updateFields[] = "$field = ?";
                    $params[] = $periodData[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }
            
            // Validate dates if being updated
            if (isset($periodData['start_date']) || isset($periodData['end_date'])) {
                $startDate = $periodData['start_date'] ?? $currentPeriod['start_date'];
                $endDate = $periodData['end_date'] ?? $currentPeriod['end_date'];
                
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                
                if ($start >= $end) {
                    throw new Exception("End date must be after start date");
                }
                
                // Check for overlapping periods (excluding current period)
                if ($this->hasOverlappingPeriod($startDate, $endDate, $periodId)) {
                    throw new Exception("Period overlaps with existing evaluation period");
                }
            }
            
            $params[] = $periodId;
            $sql = "UPDATE evaluation_periods SET " . implode(', ', $updateFields) . " WHERE period_id = ?";
            
            $affected = updateRecord($sql, $params);
            
            if ($affected > 0) {
                // Log period update
                logActivity($_SESSION['user_id'] ?? null, 'period_updated', 'evaluation_periods', $periodId, $currentPeriod, $periodData);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update period error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get period by ID
     * @param int $periodId
     * @return array|false
     */
    public function getPeriodById($periodId) {
        $sql = "SELECT p.*, u.username as created_by_username, e.first_name as created_by_first_name, e.last_name as created_by_last_name
                FROM evaluation_periods p
                LEFT JOIN users u ON p.created_by = u.user_id
                LEFT JOIN employees e ON u.user_id = e.user_id
                WHERE p.period_id = ?";
        
        return fetchOne($sql, [$periodId]);
    }
    
    /**
     * Get all periods with pagination and filtering
     * @param int $page
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getPeriods($page = 1, $limit = RECORDS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period_type'])) {
            $whereClause .= " AND p.period_type = ?";
            $params[] = $filters['period_type'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (p.period_name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['year'])) {
            $whereClause .= " AND YEAR(p.start_date) = ?";
            $params[] = $filters['year'];
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM evaluation_periods p $whereClause";
        $totalResult = fetchOne($countSql, $params);
        $total = $totalResult['total'];
        
        // Get periods
        $sql = "SELECT p.*, u.username as created_by_username, e.first_name as created_by_first_name, e.last_name as created_by_last_name,
                       (SELECT COUNT(*) FROM evaluations ev WHERE ev.period_id = p.period_id) as evaluation_count
                FROM evaluation_periods p
                LEFT JOIN users u ON p.created_by = u.user_id
                LEFT JOIN employees e ON u.user_id = e.user_id
                $whereClause 
                ORDER BY p.start_date DESC 
                LIMIT $limit OFFSET $offset";
        
        $periods = fetchAll($sql, $params);
        
        return [
            'periods' => $periods,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * Get active periods
     * @return array
     */
    public function getActivePeriods() {
        $sql = "SELECT * FROM evaluation_periods WHERE status = 'active' ORDER BY start_date DESC";
        return fetchAll($sql);
    }
    
    /**
     * Get current active period
     * @return array|false
     */
    public function getCurrentPeriod() {
        $sql = "SELECT * FROM evaluation_periods 
                WHERE status = 'active' 
                AND start_date <= CURDATE() 
                AND end_date >= CURDATE() 
                ORDER BY start_date DESC 
                LIMIT 1";
        
        return fetchOne($sql);
    }
    
    /**
     * Get upcoming periods
     * @param int $limit
     * @return array
     */
    public function getUpcomingPeriods($limit = 5) {
        $sql = "SELECT * FROM evaluation_periods 
                WHERE start_date > CURDATE() 
                ORDER BY start_date ASC 
                LIMIT ?";
        
        return fetchAll($sql, [$limit]);
    }
    
    /**
     * Get periods by year
     * @param int $year
     * @return array
     */
    public function getPeriodsByYear($year) {
        $sql = "SELECT * FROM evaluation_periods 
                WHERE YEAR(start_date) = ? OR YEAR(end_date) = ?
                ORDER BY start_date ASC";
        
        return fetchAll($sql, [$year, $year]);
    }
    
    /**
     * Check for overlapping periods
     * @param string $startDate
     * @param string $endDate
     * @param int $excludePeriodId
     * @return bool
     */
    private function hasOverlappingPeriod($startDate, $endDate, $excludePeriodId = null) {
        $sql = "SELECT COUNT(*) as count FROM evaluation_periods 
                WHERE (
                    (start_date <= ? AND end_date >= ?) OR
                    (start_date <= ? AND end_date >= ?) OR
                    (start_date >= ? AND end_date <= ?)
                )";
        
        $params = [$startDate, $startDate, $endDate, $endDate, $startDate, $endDate];
        
        if ($excludePeriodId) {
            $sql .= " AND period_id != ?";
            $params[] = $excludePeriodId;
        }
        
        $result = fetchOne($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Delete period
     * @param int $periodId
     * @return bool
     */
    public function deletePeriod($periodId) {
        try {
            // Get current period data for logging
            $period = $this->getPeriodById($periodId);
            if (!$period) {
                throw new Exception("Period not found");
            }
            
            // Check if period has evaluations
            $sql = "SELECT COUNT(*) as count FROM evaluations WHERE period_id = ?";
            $result = fetchOne($sql, [$periodId]);
            
            if ($result['count'] > 0) {
                throw new Exception("Cannot delete period with existing evaluations");
            }
            
            // Delete period
            $sql = "DELETE FROM evaluation_periods WHERE period_id = ?";
            $affected = updateRecord($sql, [$periodId]);
            
            if ($affected > 0) {
                // Log period deletion
                logActivity($_SESSION['user_id'] ?? null, 'period_deleted', 'evaluation_periods', $periodId, $period, null);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Delete period error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Activate period
     * @param int $periodId
     * @return bool
     */
    public function activatePeriod($periodId) {
        try {
            return $this->updatePeriod($periodId, ['status' => 'active']);
        } catch (Exception $e) {
            error_log("Activate period error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Complete period
     * @param int $periodId
     * @return bool
     */
    public function completePeriod($periodId) {
        try {
            return $this->updatePeriod($periodId, ['status' => 'completed']);
        } catch (Exception $e) {
            error_log("Complete period error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Archive period
     * @param int $periodId
     * @return bool
     */
    public function archivePeriod($periodId) {
        try {
            return $this->updatePeriod($periodId, ['status' => 'archived']);
        } catch (Exception $e) {
            error_log("Archive period error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get period statistics with evidence metrics
     * @param int $periodId
     * @return array
     */
    public function getPeriodStats($periodId) {
        $stats = [];
        
        // Total evaluations in period
        $sql = "SELECT COUNT(*) as count FROM evaluations WHERE period_id = ?";
        $result = fetchOne($sql, [$periodId]);
        $stats['total_evaluations'] = $result['count'];
        
        // Evaluations by status
        $sql = "SELECT status, COUNT(*) as count FROM evaluations WHERE period_id = ? GROUP BY status";
        $result = fetchAll($sql, [$periodId]);
        $stats['by_status'] = [];
        foreach ($result as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // Completion percentage
        $completedCount = $stats['by_status']['approved'] ?? 0;
        $stats['completion_percentage'] = $stats['total_evaluations'] > 0 
            ? round(($completedCount / $stats['total_evaluations']) * 100, 1) 
            : 0;
        
        // Average evidence rating
        $sql = "SELECT AVG(evidence_rating) as avg_rating FROM evaluations WHERE period_id = ? AND evidence_rating IS NOT NULL";
        $result = fetchOne($sql, [$periodId]);
        $stats['average_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 2) : 0;
        
        // Evaluations by department
        $sql = "SELECT e.department, COUNT(*) as count 
                FROM evaluations ev 
                JOIN employees e ON ev.employee_id = e.employee_id 
                WHERE ev.period_id = ? AND e.department IS NOT NULL
                GROUP BY e.department 
                ORDER BY count DESC";
        $result = fetchAll($sql, [$periodId]);
        $stats['by_department'] = $result;
        
        // Evidence metrics
        $sql = "SELECT 
                    COUNT(DISTINCT gee.employee_id) as employees_with_evidence,
                    COUNT(gee.entry_id) as total_evidence_entries,
                    AVG(gee.star_rating) as avg_evidence_rating
                FROM growth_evidence_entries gee
                JOIN evaluations ev ON gee.employee_id = ev.employee_id
                WHERE ev.period_id = ?";
        $result = fetchOne($sql, [$periodId]);
        $stats['evidence_metrics'] = $result;
        
        return $stats;
    }
    
    /**
     * Generate automatic periods for a year
     * @param int $year
     * @param string $type (quarterly, semi_annual, annual)
     * @return array
     */
    public function generatePeriodsForYear($year, $type = 'quarterly') {
        $periods = [];
        
        try {
            switch ($type) {
                case 'quarterly':
                    $quarters = [
                        ['Q1', '01-01', '03-31'],
                        ['Q2', '04-01', '06-30'],
                        ['Q3', '07-01', '09-30'],
                        ['Q4', '10-01', '12-31']
                    ];
                    
                    foreach ($quarters as $quarter) {
                        $periodData = [
                            'period_name' => "$year {$quarter[0]} Evaluation",
                            'period_type' => 'quarterly',
                            'start_date' => "$year-{$quarter[1]}",
                            'end_date' => "$year-{$quarter[2]}",
                            'status' => 'draft',
                            'description' => "Quarterly evaluation period for {$quarter[0]} $year"
                        ];
                        
                        $periodId = $this->createPeriod($periodData);
                        $periods[] = $periodId;
                    }
                    break;
                    
                case 'semi_annual':
                    $halves = [
                        ['H1', '01-01', '06-30'],
                        ['H2', '07-01', '12-31']
                    ];
                    
                    foreach ($halves as $half) {
                        $periodData = [
                            'period_name' => "$year {$half[0]} Evaluation",
                            'period_type' => 'semi_annual',
                            'start_date' => "$year-{$half[1]}",
                            'end_date' => "$year-{$half[2]}",
                            'status' => 'draft',
                            'description' => "Semi-annual evaluation period for {$half[0]} $year"
                        ];
                        
                        $periodId = $this->createPeriod($periodData);
                        $periods[] = $periodId;
                    }
                    break;
                    
                case 'annual':
                    $periodData = [
                        'period_name' => "$year Annual Evaluation",
                        'period_type' => 'annual',
                        'start_date' => "$year-01-01",
                        'end_date' => "$year-12-31",
                        'status' => 'draft',
                        'description' => "Annual evaluation period for $year"
                    ];
                    
                    $periodId = $this->createPeriod($periodData);
                    $periods[] = $periodId;
                    break;
                    
                default:
                    throw new Exception("Invalid period type: $type");
            }
            
            return $periods;
        } catch (Exception $e) {
            error_log("Generate periods error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get available years
     * @return array
     */
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT YEAR(start_date) as year FROM evaluation_periods ORDER BY year DESC";
        $result = fetchAll($sql);
        return array_column($result, 'year');
    }
    
    /**
     * Check if period is editable
     * @param int $periodId
     * @return bool
     */
    public function isPeriodEditable($periodId) {
        $period = $this->getPeriodById($periodId);
        if (!$period) {
            return false;
        }
        
        // Draft periods are always editable
        if ($period['status'] === 'draft') {
            return true;
        }
        
        // Active periods can be edited if they haven't started yet
        if ($period['status'] === 'active') {
            return strtotime($period['start_date']) > time();
        }
        
        // Completed and archived periods are not editable
        return false;
    }
    
    /**
     * Get manager dashboard data for period
     * @param int $managerId
     * @param int $periodId
     * @return array
     */
    public function getManagerDashboard($managerId, $periodId) {
        $dashboard = [];
        
        // Get period details
        $dashboard['period'] = $this->getPeriodById($periodId);
        
        // Get manager's team evaluations for this period
        $sql = "SELECT e.*, 
                       emp.first_name, emp.last_name, emp.employee_number,
                       e.evidence_rating as rating
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                WHERE e.manager_id = ? AND e.period_id = ?
                ORDER BY e.created_at DESC";
        
        $dashboard['team_evaluations'] = fetchAll($sql, [$managerId, $periodId]);
        
        // Get evidence summary for manager's team
        $sql = "SELECT 
                    COUNT(DISTINCT gee.employee_id) as team_members_with_evidence,
                    COUNT(gee.entry_id) as total_team_evidence_entries,
                    AVG(gee.star_rating) as avg_team_evidence_rating
                FROM growth_evidence_entries gee
                JOIN employees emp ON gee.employee_id = emp.employee_id
                WHERE emp.manager_id = ? 
                AND gee.entry_date BETWEEN ? AND ?";
        
        $period = $this->getPeriodById($periodId);
        $dashboard['team_evidence_summary'] = fetchOne($sql, [
            $managerId, 
            $period['start_date'], 
            $period['end_date']
        ]);
        
        return $dashboard;
    }
    
    /**
     * Auto-generate evaluations for all employees in period
     * @param int $periodId
     * @param int $evaluatorId (typically HR or admin)
     * @return array
     */
    public function autoGenerateEvaluations($periodId, $evaluatorId) {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        try {
            // Get all employees
            $sql = "SELECT employee_id FROM employees WHERE active = 1";
            $employees = fetchAll($sql);
            
            $evaluationClass = new Evaluation();
            
            foreach ($employees as $employee) {
                try {
                    // Check if evaluation already exists for this employee and period
                    $sql = "SELECT COUNT(*) as count FROM evaluations WHERE employee_id = ? AND period_id = ?";
                    $result = fetchOne($sql, [$employee['employee_id'], $periodId]);
                    
                    if ($result['count'] == 0) {
                        // Create new evaluation
                        $evaluationClass->createFromEvidenceJournal(
                            $employee['employee_id'], 
                            $periodId, 
                            $evaluatorId
                        );
                        $results['created']++;
                    } else {
                        $results['skipped']++;
                    }
                } catch (Exception $e) {
                    $results['errors'][] = "Employee ID {$employee['employee_id']}: " . $e->getMessage();
                    error_log("Auto-generate evaluation error for employee {$employee['employee_id']}: " . $e->getMessage());
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Auto-generate evaluations error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get active period for employee
     * @param int $employeeId
     * @return array|false
     */
    public function getActivePeriodForEmployee($employeeId) {
        // For now, return the current active period (same for all employees)
        // In the future, this could be enhanced to check employee-specific periods
        return $this->getCurrentPeriod();
    }
}
?>
