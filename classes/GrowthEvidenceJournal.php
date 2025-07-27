<?php
/**
 * Growth Evidence Journal Management Class
 * Continuous Performance System
 */

require_once __DIR__ . '/../config/config.php';

class GrowthEvidenceJournal {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create new evidence entry
     * @param array $entryData
     * @return int|false
     */
    public function createEntry($entryData) {
        try {
            // Validate required fields
            $required = ['employee_id', 'manager_id', 'content', 'star_rating', 'dimension', 'entry_date'];
            foreach ($required as $field) {
                if (empty($entryData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Validate star rating
            if ($entryData['star_rating'] < 1 || $entryData['star_rating'] > 5) {
                throw new Exception("Star rating must be between 1 and 5");
            }
            
            // Validate dimension
            $validDimensions = ['responsibilities', 'kpis', 'competencies', 'values'];
            if (!in_array($entryData['dimension'], $validDimensions)) {
                throw new Exception("Invalid dimension. Must be one of: " . implode(', ', $validDimensions));
            }
            
            // Insert evidence entry
            $sql = "INSERT INTO growth_evidence_entries (employee_id, manager_id, content, star_rating, dimension, entry_date) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $entryId = insertRecord($sql, [
                $entryData['employee_id'],
                $entryData['manager_id'],
                $entryData['content'],
                $entryData['star_rating'],
                $entryData['dimension'],
                $entryData['entry_date']
            ]);
            
            // Log entry creation
            logActivity($_SESSION['user_id'] ?? null, 'evidence_entry_created', 'growth_evidence_entries', $entryId, null, $entryData);
            
            return $entryId;
        } catch (Exception $e) {
            error_log("Create evidence entry error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update evidence entry
     * @param int $entryId
     * @param array $entryData
     * @return bool
     */
    public function updateEntry($entryId, $entryData) {
        try {
            // Get current entry data for logging
            $currentEntry = $this->getEntryById($entryId);
            if (!$currentEntry) {
                throw new Exception("Entry not found");
            }
            
            $updateFields = [];
            $params = [];
            
            // Build dynamic update query
            $allowedFields = ['content', 'star_rating', 'dimension', 'entry_date'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $entryData)) {
                    $updateFields[] = "$field = ?";
                    $params[] = $entryData[$field];
                }
            }
            
            if (empty($updateFields)) {
                return true; // Nothing to update
            }
            
            // Validate star rating if being updated
            if (isset($entryData['star_rating'])) {
                if ($entryData['star_rating'] < 1 || $entryData['star_rating'] > 5) {
                    throw new Exception("Star rating must be between 1 and 5");
                }
            }
            
            // Validate dimension if being updated
            if (isset($entryData['dimension'])) {
                $validDimensions = ['responsibilities', 'kpis', 'competencies', 'values'];
                if (!in_array($entryData['dimension'], $validDimensions)) {
                    throw new Exception("Invalid dimension. Must be one of: " . implode(', ', $validDimensions));
                }
            }
            
            $params[] = $entryId;
            $sql = "UPDATE growth_evidence_entries SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE entry_id = ?";
            
            $affected = updateRecord($sql, $params);
            
            if ($affected > 0) {
                // Log entry update
                logActivity($_SESSION['user_id'] ?? null, 'evidence_entry_updated', 'growth_evidence_entries', $entryId, $currentEntry, $entryData);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update evidence entry error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get entry by ID
     * @param int $entryId
     * @return array|false
     */
    public function getEntryById($entryId) {
        $sql = "SELECT gee.*, 
                       e.first_name as employee_first_name, e.last_name as employee_last_name,
                       m.first_name as manager_first_name, m.last_name as manager_last_name
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                JOIN employees m ON gee.manager_id = m.employee_id
                WHERE gee.entry_id = ?";
        
        return fetchOne($sql, [$entryId]);
    }
    
    /**
     * Get employee's journal entries
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @return array
     */
    public function getEmployeeJournal($employeeId, $startDate = null, $endDate = null, $filters = []) {
        $whereClause = "WHERE gee.employee_id = ?";
        $params = [$employeeId];
        
        // Apply date filters
        if ($startDate) {
            $whereClause .= " AND gee.entry_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $whereClause .= " AND gee.entry_date <= ?";
            $params[] = $endDate;
        }
        
        // Apply dimension filter
        if (!empty($filters['dimension'])) {
            $whereClause .= " AND gee.dimension = ?";
            $params[] = $filters['dimension'];
        }
        
        // Apply star rating filter
        if (!empty($filters['min_rating'])) {
            $whereClause .= " AND gee.star_rating >= ?";
            $params[] = $filters['min_rating'];
        }
        
        if (!empty($filters['max_rating'])) {
            $whereClause .= " AND gee.star_rating <= ?";
            $params[] = $filters['max_rating'];
        }
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $whereClause .= " AND (gee.content LIKE ?)";
            $params[] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT gee.*, 
                       e.first_name as employee_first_name, e.last_name as employee_last_name,
                       m.first_name as manager_first_name, m.last_name as manager_last_name,
                       (SELECT COUNT(*) FROM evidence_attachments ea WHERE ea.entry_id = gee.entry_id) as attachment_count
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                JOIN employees m ON gee.manager_id = m.employee_id
                $whereClause
                ORDER BY gee.entry_date DESC, gee.created_at DESC";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get entries by manager
     * @param int $managerId
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @return array
     */
    public function getManagerEntries($managerId, $startDate = null, $endDate = null, $filters = []) {
        $whereClause = "WHERE gee.manager_id = ?";
        $params = [$managerId];
        
        // Apply date filters
        if ($startDate) {
            $whereClause .= " AND gee.entry_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $whereClause .= " AND gee.entry_date <= ?";
            $params[] = $endDate;
        }
        
        // Apply employee filter
        if (!empty($filters['employee_id'])) {
            $whereClause .= " AND gee.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        // Apply dimension filter
        if (!empty($filters['dimension'])) {
            $whereClause .= " AND gee.dimension = ?";
            $params[] = $filters['dimension'];
        }
        
        $sql = "SELECT gee.*, 
                       e.first_name as employee_first_name, e.last_name as employee_last_name,
                       e.employee_number,
                       m.first_name as manager_first_name, m.last_name as manager_last_name,
                       (SELECT COUNT(*) FROM evidence_attachments ea WHERE ea.entry_id = gee.entry_id) as attachment_count
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                JOIN employees m ON gee.manager_id = m.employee_id
                $whereClause
                ORDER BY gee.entry_date DESC, gee.created_at DESC";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get evidence by dimension for an employee
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getEvidenceByDimension($employeeId, $startDate = null, $endDate = null) {
        $whereClause = "WHERE gee.employee_id = ?";
        $params = [$employeeId];
        
        if ($startDate) {
            $whereClause .= " AND gee.entry_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $whereClause .= " AND gee.entry_date <= ?";
            $params[] = $endDate;
        }
        
        $sql = "SELECT gee.dimension,
                       COUNT(*) as entry_count,
                       AVG(gee.star_rating) as avg_rating,
                       SUM(CASE WHEN gee.star_rating >= 4 THEN 1 ELSE 0 END) as positive_count,
                       SUM(CASE WHEN gee.star_rating <= 2 THEN 1 ELSE 0 END) as negative_count
                FROM growth_evidence_entries gee
                $whereClause
                GROUP BY gee.dimension
                ORDER BY avg_rating DESC";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get evidence summary statistics
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getEvidenceSummary($employeeId, $startDate = null, $endDate = null) {
        $whereClause = "WHERE gee.employee_id = ?";
        $params = [$employeeId];
        
        if ($startDate) {
            $whereClause .= " AND gee.entry_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $whereClause .= " AND gee.entry_date <= ?";
            $params[] = $endDate;
        }
        
        $sql = "SELECT COUNT(*) as total_entries,
                       AVG(gee.star_rating) as overall_avg_rating,
                       SUM(CASE WHEN gee.star_rating >= 4 THEN 1 ELSE 0 END) as positive_entries,
                       SUM(CASE WHEN gee.star_rating <= 2 THEN 1 ELSE 0 END) as negative_entries,
                       MIN(gee.entry_date) as first_entry_date,
                       MAX(gee.entry_date) as last_entry_date
                FROM growth_evidence_entries gee
                $whereClause";
        
        return fetchOne($sql, $params);
    }
    
    /**
     * Delete evidence entry
     * @param int $entryId
     * @return bool
     */
    public function deleteEntry($entryId) {
        try {
            // Get current entry data for logging
            $entry = $this->getEntryById($entryId);
            if (!$entry) {
                throw new Exception("Entry not found");
            }
            
            // Delete entry (attachments will be deleted via CASCADE)
            $sql = "DELETE FROM growth_evidence_entries WHERE entry_id = ?";
            $affected = updateRecord($sql, [$entryId]);
            
            if ($affected > 0) {
                // Log entry deletion
                logActivity($_SESSION['user_id'] ?? null, 'evidence_entry_deleted', 'growth_evidence_entries', $entryId, $entry, null);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Delete evidence entry error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get recent entries for dashboard
     * @param int $limit
     * @return array
     */
    public function getRecentEntries($limit = 10) {
        $sql = "SELECT gee.*, 
                       e.first_name as employee_first_name, e.last_name as employee_last_name,
                       m.first_name as manager_first_name, m.last_name as manager_last_name,
                       (SELECT COUNT(*) FROM evidence_attachments ea WHERE ea.entry_id = gee.entry_id) as attachment_count
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                JOIN employees m ON gee.manager_id = m.employee_id
                ORDER BY gee.created_at DESC
                LIMIT ?";
        
        return fetchAll($sql, [$limit]);
    }
    
    /**
     * Get entries by date range for reporting
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @return array
     */
    public function getEntriesByDateRange($startDate, $endDate, $filters = []) {
        $whereClause = "WHERE gee.entry_date BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        
        // Apply employee filter
        if (!empty($filters['employee_id'])) {
            $whereClause .= " AND gee.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        // Apply manager filter
        if (!empty($filters['manager_id'])) {
            $whereClause .= " AND gee.manager_id = ?";
            $params[] = $filters['manager_id'];
        }
        
        // Apply dimension filter
        if (!empty($filters['dimension'])) {
            $whereClause .= " AND gee.dimension = ?";
            $params[] = $filters['dimension'];
        }
        
        $sql = "SELECT gee.*, 
                       e.first_name as employee_first_name, e.last_name as employee_last_name,
                       e.employee_number,
                       m.first_name as manager_first_name, m.last_name as manager_last_name,
                       (SELECT COUNT(*) FROM evidence_attachments ea WHERE ea.entry_id = gee.entry_id) as attachment_count
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                JOIN employees m ON gee.manager_id = m.employee_id
                $whereClause
                ORDER BY gee.entry_date DESC, gee.created_at DESC";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get evidence statistics by dimension
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDimensionStatistics($startDate = null, $endDate = null) {
        $whereClause = "";
        $params = [];
        
        if ($startDate && $endDate) {
            $whereClause = "WHERE gee.entry_date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }
        
        $sql = "SELECT gee.dimension,
                       COUNT(*) as entry_count,
                       AVG(gee.star_rating) as avg_rating,
                       SUM(CASE WHEN gee.star_rating >= 4 THEN 1 ELSE 0 END) as positive_count,
                       SUM(CASE WHEN gee.star_rating <= 2 THEN 1 ELSE 0 END) as negative_count
                FROM growth_evidence_entries gee
                $whereClause
                GROUP BY gee.dimension
                ORDER BY entry_count DESC";
        
        return fetchAll($sql, $params);
    }
}
?>
