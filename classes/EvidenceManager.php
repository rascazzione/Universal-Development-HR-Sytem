<?php
/**
 * Enhanced Evidence Manager Class
 * Phase 3: Advanced Features - Evidence Management
 * Growth Evidence System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/GrowthEvidenceJournal.php';

class EvidenceManager extends GrowthEvidenceJournal {
    
    /**
     * Advanced search for evidence entries
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function advancedSearch($filters = [], $page = 1, $limit = 20) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // Text search in content
            if (!empty($filters['search'])) {
                $whereClause .= " AND gee.content LIKE ?";
                $params[] = "%{$filters['search']}%";
            }
            
            // Employee filter
            if (!empty($filters['employee_id'])) {
                $whereClause .= " AND gee.employee_id = ?";
                $params[] = $filters['employee_id'];
            }
            
            // Manager filter
            if (!empty($filters['manager_id'])) {
                $whereClause .= " AND gee.manager_id = ?";
                $params[] = $filters['manager_id'];
            }
            
            // Dimension filter
            if (!empty($filters['dimension'])) {
                $whereClause .= " AND gee.dimension = ?";
                $params[] = $filters['dimension'];
            }
            
            // Rating range filter
            if (!empty($filters['min_rating'])) {
                $whereClause .= " AND gee.star_rating >= ?";
                $params[] = $filters['min_rating'];
            }
            
            if (!empty($filters['max_rating'])) {
                $whereClause .= " AND gee.star_rating <= ?";
                $params[] = $filters['max_rating'];
            }
            
            // Date range filter
            if (!empty($filters['start_date'])) {
                $whereClause .= " AND gee.entry_date >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereClause .= " AND gee.entry_date <= ?";
                $params[] = $filters['end_date'];
            }
            
            // Tag filter (if tags are implemented)
            if (!empty($filters['tags'])) {
                $tagPlaceholders = str_repeat('?,', count($filters['tags']) - 1) . '?';
                $whereClause .= " AND gee.entry_id IN (
                    SELECT eet.entry_id FROM evidence_entry_tags eet 
                    JOIN evidence_tags et ON eet.tag_id = et.tag_id 
                    WHERE et.tag_name IN ($tagPlaceholders)
                )";
                $params = array_merge($params, $filters['tags']);
            }
            
            // Archived filter
            $whereClause .= " AND gee.is_archived = ?";
            $params[] = isset($filters['include_archived']) && $filters['include_archived'] ? 1 : 0;
            
            // Calculate offset
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                        FROM growth_evidence_entries gee
                        JOIN employees e ON gee.employee_id = e.employee_id
                        JOIN employees m ON gee.manager_id = m.employee_id
                        $whereClause";
            
            $totalResult = fetchOne($countSql, $params);
            $total = $totalResult['total'];
            
            // Get results
            $sql = "SELECT gee.*, 
                           e.first_name as employee_first_name, 
                           e.last_name as employee_last_name,
                           e.employee_number,
                           m.first_name as manager_first_name, 
                           m.last_name as manager_last_name,
                           (SELECT COUNT(*) FROM evidence_attachments ea WHERE ea.entry_id = gee.entry_id) as attachment_count,
                           (SELECT GROUP_CONCAT(et.tag_name) FROM evidence_entry_tags eet 
                            JOIN evidence_tags et ON eet.tag_id = et.tag_id 
                            WHERE eet.entry_id = gee.entry_id) as tags
                    FROM growth_evidence_entries gee
                    JOIN employees e ON gee.employee_id = e.employee_id
                    JOIN employees m ON gee.manager_id = m.employee_id
                    $whereClause
                    ORDER BY gee.entry_date DESC, gee.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $results = fetchAll($sql, $params);
            
            return [
                'results' => $results,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Advanced search error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Bulk operations for evidence entries
     * @param array $entryIds
     * @param string $operation
     * @param array $data
     * @return array
     */
    public function bulkOperation($entryIds, $operation, $data = []) {
        try {
            if (empty($entryIds)) {
                throw new Exception("No entries selected for bulk operation");
            }
            
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            foreach ($entryIds as $entryId) {
                try {
                    switch ($operation) {
                        case 'delete':
                            if ($this->deleteEntry($entryId)) {
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Failed to delete entry $entryId";
                            }
                            break;
                            
                        case 'archive':
                            if ($this->archiveEntry($entryId, $data['reason'] ?? 'manual')) {
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Failed to archive entry $entryId";
                            }
                            break;
                            
                        case 'update_dimension':
                            if ($this->updateEntry($entryId, ['dimension' => $data['dimension']])) {
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Failed to update dimension for entry $entryId";
                            }
                            break;
                            
                        case 'add_tags':
                            if ($this->addTagsToEntry($entryId, $data['tags'])) {
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Failed to add tags to entry $entryId";
                            }
                            break;
                            
                        default:
                            $results['failed']++;
                            $results['errors'][] = "Unknown operation: $operation";
                    }
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Entry $entryId: " . $e->getMessage();
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Bulk operation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Archive evidence entry
     * @param int $entryId
     * @param string $reason
     * @return bool
     */
    public function archiveEntry($entryId, $reason = 'manual') {
        try {
            // Get current entry data
            $entry = $this->getEntryById($entryId);
            if (!$entry) {
                throw new Exception("Entry not found");
            }
            
            // Update entry as archived
            $sql = "UPDATE growth_evidence_entries 
                    SET is_archived = TRUE, archived_at = NOW() 
                    WHERE entry_id = ?";
            
            $affected = updateRecord($sql, [$entryId]);
            
            if ($affected > 0) {
                // Create archive record
                $archiveSql = "INSERT INTO evidence_archive (entry_id, archived_by, archive_reason, original_data) 
                              VALUES (?, ?, ?, ?)";
                
                insertRecord($archiveSql, [
                    $entryId,
                    $_SESSION['user_id'] ?? null,
                    $reason,
                    json_encode($entry)
                ]);
                
                // Log activity
                logActivity($_SESSION['user_id'] ?? null, 'evidence_entry_archived', 'growth_evidence_entries', $entryId, null, ['reason' => $reason]);
            }
            
            return $affected > 0;
            
        } catch (Exception $e) {
            error_log("Archive entry error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Restore archived evidence entry
     * @param int $entryId
     * @return bool
     */
    public function restoreEntry($entryId) {
        try {
            // Update entry as not archived
            $sql = "UPDATE growth_evidence_entries 
                    SET is_archived = FALSE, archived_at = NULL 
                    WHERE entry_id = ?";
            
            $affected = updateRecord($sql, [$entryId]);
            
            if ($affected > 0) {
                // Update archive record
                $archiveSql = "UPDATE evidence_archive 
                              SET restore_date = NOW(), is_restored = TRUE 
                              WHERE entry_id = ? AND is_restored = FALSE";
                
                updateRecord($archiveSql, [$entryId]);
                
                // Log activity
                logActivity($_SESSION['user_id'] ?? null, 'evidence_entry_restored', 'growth_evidence_entries', $entryId, null, null);
            }
            
            return $affected > 0;
            
        } catch (Exception $e) {
            error_log("Restore entry error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add tags to evidence entry
     * @param int $entryId
     * @param array $tagIds
     * @return bool
     */
    public function addTagsToEntry($entryId, $tagIds) {
        try {
            if (empty($tagIds)) {
                return true;
            }
            
            foreach ($tagIds as $tagId) {
                $sql = "INSERT IGNORE INTO evidence_entry_tags (entry_id, tag_id) VALUES (?, ?)";
                insertRecord($sql, [$entryId, $tagId]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Add tags error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Remove tags from evidence entry
     * @param int $entryId
     * @param array $tagIds
     * @return bool
     */
    public function removeTagsFromEntry($entryId, $tagIds = []) {
        try {
            if (empty($tagIds)) {
                // Remove all tags
                $sql = "DELETE FROM evidence_entry_tags WHERE entry_id = ?";
                updateRecord($sql, [$entryId]);
            } else {
                // Remove specific tags
                $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
                $sql = "DELETE FROM evidence_entry_tags WHERE entry_id = ? AND tag_id IN ($placeholders)";
                $params = array_merge([$entryId], $tagIds);
                updateRecord($sql, $params);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Remove tags error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get available evidence tags
     * @return array
     */
    public function getAvailableTags() {
        try {
            $sql = "SELECT * FROM evidence_tags WHERE is_active = TRUE ORDER BY tag_name";
            return fetchAll($sql);
        } catch (Exception $e) {
            error_log("Get tags error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create new evidence tag
     * @param array $tagData
     * @return int|false
     */
    public function createTag($tagData) {
        try {
            $required = ['tag_name', 'created_by'];
            foreach ($required as $field) {
                if (empty($tagData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "INSERT INTO evidence_tags (tag_name, tag_color, description, created_by) 
                    VALUES (?, ?, ?, ?)";
            
            return insertRecord($sql, [
                $tagData['tag_name'],
                $tagData['tag_color'] ?? '#007bff',
                $tagData['description'] ?? '',
                $tagData['created_by']
            ]);
            
        } catch (Exception $e) {
            error_log("Create tag error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get evidence statistics for dashboard
     * @param array $filters
     * @return array
     */
    public function getEvidenceStatistics($filters = []) {
        try {
            $whereClause = "WHERE gee.is_archived = FALSE";
            $params = [];
            
            // Apply filters
            if (!empty($filters['manager_id'])) {
                $whereClause .= " AND gee.manager_id = ?";
                $params[] = $filters['manager_id'];
            }
            
            if (!empty($filters['employee_id'])) {
                $whereClause .= " AND gee.employee_id = ?";
                $params[] = $filters['employee_id'];
            }
            
            if (!empty($filters['start_date'])) {
                $whereClause .= " AND gee.entry_date >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereClause .= " AND gee.entry_date <= ?";
                $params[] = $filters['end_date'];
            }
            
            $sql = "SELECT 
                        COUNT(*) as total_entries,
                        AVG(gee.star_rating) as avg_rating,
                        SUM(CASE WHEN gee.star_rating >= 4 THEN 1 ELSE 0 END) as positive_entries,
                        SUM(CASE WHEN gee.star_rating = 3 THEN 1 ELSE 0 END) as neutral_entries,
                        SUM(CASE WHEN gee.star_rating <= 2 THEN 1 ELSE 0 END) as negative_entries,
                        COUNT(DISTINCT gee.employee_id) as unique_employees,
                        COUNT(DISTINCT gee.manager_id) as unique_managers,
                        COUNT(DISTINCT gee.dimension) as dimensions_covered
                    FROM growth_evidence_entries gee
                    $whereClause";
            
            return fetchOne($sql, $params);
            
        } catch (Exception $e) {
            error_log("Get statistics error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get evidence entries requiring approval
     * @param int $approverId
     * @return array
     */
    public function getEntriesRequiringApproval($approverId) {
        try {
            $sql = "SELECT gee.*, 
                           e.first_name as employee_first_name, 
                           e.last_name as employee_last_name,
                           m.first_name as manager_first_name, 
                           m.last_name as manager_last_name,
                           ea.status as approval_status,
                           ea.comments as approval_comments
                    FROM growth_evidence_entries gee
                    JOIN employees e ON gee.employee_id = e.employee_id
                    JOIN employees m ON gee.manager_id = m.employee_id
                    LEFT JOIN evidence_approvals ea ON gee.entry_id = ea.entry_id
                    WHERE gee.approval_status = 'pending' 
                    AND (ea.approver_id = ? OR ea.approver_id IS NULL)
                    AND gee.is_archived = FALSE
                    ORDER BY gee.created_at DESC";
            
            return fetchAll($sql, [$approverId]);
            
        } catch (Exception $e) {
            error_log("Get approval entries error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Approve or reject evidence entry
     * @param int $entryId
     * @param int $approverId
     * @param string $status
     * @param string $comments
     * @return bool
     */
    public function processApproval($entryId, $approverId, $status, $comments = '') {
        try {
            $validStatuses = ['approved', 'rejected', 'needs_revision'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid approval status");
            }
            
            // Update or insert approval record
            $sql = "INSERT INTO evidence_approvals (entry_id, approver_id, status, comments, approved_at) 
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    comments = VALUES(comments), 
                    approved_at = NOW()";
            
            insertRecord($sql, [$entryId, $approverId, $status, $comments]);
            
            // Update evidence entry approval status
            $updateSql = "UPDATE growth_evidence_entries SET approval_status = ? WHERE entry_id = ?";
            updateRecord($updateSql, [$status, $entryId]);
            
            // Log activity
            logActivity($approverId, 'evidence_approval_processed', 'evidence_approvals', $entryId, null, [
                'status' => $status,
                'comments' => $comments
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Process approval error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>