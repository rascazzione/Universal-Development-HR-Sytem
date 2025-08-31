<?php
/**
 * AchievementJournal Class
 * Achievement Journal Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/GrowthEvidenceJournal.php';
require_once __DIR__ . '/NotificationManager.php';
require_once __DIR__ . '/Employee.php';

class AchievementJournal {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Create achievement entry
     * @param int $employeeId
     * @param array $achievementData
     * @return int
     * @throws Exception
     */
    public function createEntry($employeeId, $achievementData) {
        try {
            if (!is_numeric($employeeId) || $employeeId <= 0) {
                throw new Exception("Invalid employeeId");
            }
            if (empty($achievementData['title']) || empty($achievementData['description'])) {
                throw new Exception("title and description are required");
            }

            $employeeClass = new Employee();
            $employee = $employeeClass->getEmployeeById($employeeId);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $title = sanitizeInput($achievementData['title']);
            $description = sanitizeInput($achievementData['description']);
            $impact = sanitizeInput($achievementData['impact'] ?? null);
            $measurable = isset($achievementData['measurable_outcome']) ? json_encode($achievementData['measurable_outcome']) : null;
            $evidence = isset($achievementData['evidence_entries']) ? json_encode($achievementData['evidence_entries']) : null;
            $visibility = in_array($achievementData['visibility'] ?? 'manager_only', ['public','manager_only','private']) ? $achievementData['visibility'] : 'manager_only';
            $date = $achievementData['date_of_achievement'] ?? date('Y-m-d');
            $createdBy = $_SESSION['user_id'] ?? null;

            $sql = "INSERT INTO achievement_journal (employee_id, title, description, impact, measurable_outcome, evidence_entries, visibility, date_of_achievement, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $journalId = insertRecord($sql, [$employeeId, $title, $description, $impact, $measurable, $evidence, $visibility, $date, $createdBy]);

            // If evidence entries provided and they are new entries, optionally create via GrowthEvidenceJournal
            if (!empty($achievementData['new_evidence_entries']) && is_array($achievementData['new_evidence_entries'])) {
                $journal = new GrowthEvidenceJournal();
                $createdEvidenceIds = [];
                foreach ($achievementData['new_evidence_entries'] as $ev) {
                    // required fields: manager_id, content, star_rating, dimension, entry_date
                    $ev['employee_id'] = $employeeId;
                    $created = $journal->createEntry($ev);
                    if ($created) $createdEvidenceIds[] = $created;
                }

                if (!empty($createdEvidenceIds)) {
                    // update evidence_entries column to include created ids
                    $existing = $evidence ? json_decode($evidence, true) : [];
                    $merged = array_values(array_unique(array_merge($existing, $createdEvidenceIds)));
                    updateRecord("UPDATE achievement_journal SET evidence_entries = ? WHERE journal_id = ?", [json_encode($merged), $journalId]);
                }
            }

            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'achievement_created', 'achievement_journal', $journalId, null, $achievementData);

            // Notify manager if visibility requires it
            if ($visibility !== 'private' && !empty($employee['manager_id'])) {
                $notif = new NotificationManager();
                $manager = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$employee['manager_id']]);
                $managerUserId = $manager['user_id'] ?? null;
                $notif->createFromTemplate('achievement_logged', $managerUserId ?? $employee['manager_id'], [
                    'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                    'title' => $title,
                    'date' => $date
                ]);
            }

            return $journalId;
        } catch (Exception $e) {
            error_log("Create achievement entry error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update journal entry
     * @param int $journalId
     * @param array $achievementData
     * @return bool
     * @throws Exception
     */
    public function updateEntry($journalId, $achievementData) {
        try {
            if (!is_numeric($journalId) || $journalId <= 0) {
                throw new Exception("Invalid journalId");
            }

            $current = $this->getEntryById($journalId);
            if (!$current) {
                throw new Exception("Journal entry not found");
            }

            // Authorization: only creator or HR can update
            $currentUserId = $_SESSION['user_id'] ?? null;
            if ($currentUserId !== intval($current['created_by']) && !hasPermission('hr_admin')) {
                throw new Exception("Unauthorized to update this entry");
            }

            $updateFields = [];
            $params = [];

            $allowed = ['title','description','impact','measurable_outcome','evidence_entries','visibility','date_of_achievement'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $achievementData)) {
                    if ($field === 'measurable_outcome' || $field === 'evidence_entries') {
                        $updateFields[] = "$field = ?";
                        $params[] = json_encode($achievementData[$field]);
                    } else {
                        $updateFields[] = "$field = ?";
                        $params[] = ($field === 'title' || $field === 'description' || $field === 'impact') ? sanitizeInput($achievementData[$field]) : $achievementData[$field];
                    }
                }
            }

            if (empty($updateFields)) {
                return true;
            }

            $params[] = $journalId;
            $sql = "UPDATE achievement_journal SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE journal_id = ?";
            $affected = updateRecord($sql, $params);

            if ($affected > 0) {
                logActivity($_SESSION['user_id'] ?? null, 'achievement_updated', 'achievement_journal', $journalId, $current, $achievementData);
            }

            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update achievement entry error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get employee achievements
     * @param int $employeeId
     * @param array $filters
     * @return array
     */
    public function getEmployeeAchievements($employeeId, $filters = []) {
        $where = "WHERE employee_id = ?";
        $params = [$employeeId];

        if (!empty($filters['start_date'])) {
            $where .= " AND date_of_achievement >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where .= " AND date_of_achievement <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['visibility'])) {
            $where .= " AND visibility = ?";
            $params[] = $filters['visibility'];
        } else {
            // enforce visibility: employees see public and manager_only for their manager
            if (!hasPermission('hr_admin')) {
                $currentEmployeeId = $_SESSION['employee_id'] ?? null;
                $where .= " AND (visibility = 'public' OR (visibility = 'manager_only' AND ? = ? ) OR created_by = ?)";
                // pass placeholders - manager check handled by SQL placeholders
                $params[] = $currentEmployeeId;
                $params[] = $employeeId;
                $params[] = $_SESSION['user_id'] ?? null;
            }
        }

        $sql = "SELECT * FROM achievement_journal $where ORDER BY date_of_achievement DESC, created_at DESC";
        return fetchAll($sql, $params);
    }

    /**
     * Link journal entry to evidence ids
     * @param int $journalId
     * @param array $evidenceIds
     * @return bool
     * @throws Exception
     */
    public function linkToEvidence($journalId, $evidenceIds) {
        try {
            if (!is_numeric($journalId) || $journalId <= 0) {
                throw new Exception("Invalid journalId");
            }
            if (empty($evidenceIds) || !is_array($evidenceIds)) {
                throw new Exception("evidenceIds must be a non-empty array");
            }

            $entry = $this->getEntryById($journalId);
            if (!$entry) {
                throw new Exception("Journal entry not found");
            }

            $existing = $entry['evidence_entries'] ? json_decode($entry['evidence_entries'], true) : [];
            $merged = array_values(array_unique(array_merge($existing, $evidenceIds)));

            $sql = "UPDATE achievement_journal SET evidence_entries = ?, updated_at = NOW() WHERE journal_id = ?";
            $affected = updateRecord($sql, [json_encode($merged), $journalId]);

            if ($affected > 0) {
                logActivity($_SESSION['user_id'] ?? null, 'achievement_linked_evidence', 'achievement_journal', $journalId, $entry, ['linked' => $evidenceIds]);
            }

            return $affected > 0;
        } catch (Exception $e) {
            error_log("Link to evidence error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate achievement report for employee within a period
     * @param int $employeeId
     * @param mixed $period Can be an array with start_date/end_date or a period_id
     * @return array
     */
    public function generateAchievementReport($employeeId, $period) {
        try {
            if (!is_numeric($employeeId) || $employeeId <= 0) {
                throw new Exception("Invalid employeeId");
            }

            if (is_array($period) && isset($period['start_date']) && isset($period['end_date'])) {
                $start = $period['start_date'];
                $end = $period['end_date'];
            } else {
                // assume period is period_id
                $periodRow = fetchOne("SELECT start_date, end_date, period_name FROM evaluation_periods WHERE period_id = ?", [$period]);
                if (!$periodRow) {
                    throw new Exception("Period not found");
                }
                $start = $periodRow['start_date'];
                $end = $periodRow['end_date'];
            }

            $sql = "SELECT journal_id, title, description, impact, measurable_outcome, evidence_entries, visibility, date_of_achievement, created_at
                    FROM achievement_journal
                    WHERE employee_id = ? AND date_of_achievement BETWEEN ? AND ?
                    ORDER BY date_of_achievement ASC";

            $entries = fetchAll($sql, [$employeeId, $start, $end]);

            // Enrich with evidence details
            $journal = new GrowthEvidenceJournal();
            foreach ($entries as &$e) {
                $e['measurable_outcome'] = $e['measurable_outcome'] ? json_decode($e['measurable_outcome'], true) : null;
                $e['evidence_entries'] = $e['evidence_entries'] ? json_decode($e['evidence_entries'], true) : [];
                $e['evidence_details'] = [];
                if (!empty($e['evidence_entries'])) {
                    foreach ($e['evidence_entries'] as $evId) {
                        $detail = $journal->getEntryById($evId);
                        if ($detail) $e['evidence_details'][] = $detail;
                    }
                }
            }

            // Summary metrics
            $summary = [
                'employee_id' => $employeeId,
                'period' => is_array($period) ? "{$start} to {$end}" : ($periodRow['period_name'] ?? $period),
                'total_achievements' => count($entries),
                'achievements' => $entries
            ];

            return $summary;
        } catch (Exception $e) {
            error_log("Generate achievement report error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper: get entry by id
     * @param int $journalId
     * @return array|false
     */
    public function getEntryById($journalId) {
        return fetchOne("SELECT * FROM achievement_journal WHERE journal_id = ?", [$journalId]);
    }
}

?>