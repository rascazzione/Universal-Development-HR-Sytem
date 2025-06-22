<?php
/**
 * Company Values Class
 * Handles company values management
 */

class CompanyValues {
    
    /**
     * Get all company values
     * @return array
     */
    public function getValues() {
        $sql = "SELECT cv.*,
                       u.username as created_by_username
                FROM company_values cv
                LEFT JOIN users u ON cv.created_by = u.user_id
                WHERE cv.is_active = 1
                ORDER BY cv.sort_order, cv.value_name";
        
        return fetchAll($sql);
    }
    
    /**
     * Get company value by ID
     * @param int $id
     * @return array|false
     */
    public function getValueById($id) {
        $sql = "SELECT cv.*,
                       u.username as created_by_username
                FROM company_values cv
                LEFT JOIN users u ON cv.created_by = u.user_id
                WHERE cv.id = ? AND cv.is_active = 1";
        
        return fetchOne($sql, [$id]);
    }
    
    /**
     * Create new company value
     * @param array $data
     * @return int
     */
    public function createValue($data) {
        $sql = "INSERT INTO company_values (value_name, description, sort_order, created_by) 
                VALUES (?, ?, ?, ?)";
        
        return insertRecord($sql, [
            $data['value_name'],
            $data['description'],
            $data['sort_order'] ?? 0,
            $data['created_by']
        ]);
    }
    
    /**
     * Update company value
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateValue($id, $data) {
        $sql = "UPDATE company_values 
                SET value_name = ?, description = ?, sort_order = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['value_name'],
            $data['description'],
            $data['sort_order'] ?? 0,
            $id
        ]);
    }
    
    /**
     * Delete company value
     * @param int $id
     * @return int
     */
    public function deleteValue($id) {
        $sql = "UPDATE company_values SET is_active = 0 WHERE id = ?";
        return updateRecord($sql, [$id]);
    }
    
    /**
     * Reorder company values
     * @param array $valueIds Array of value IDs in new order
     * @return bool
     */
    public function reorderValues($valueIds) {
        try {
            $sortOrder = 1;
            foreach ($valueIds as $valueId) {
                $sql = "UPDATE company_values SET sort_order = ? WHERE id = ?";
                updateRecord($sql, [$sortOrder, $valueId]);
                $sortOrder++;
            }
            return true;
        } catch (Exception $e) {
            error_log("Error reordering values: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get company values used in job templates
     * @param int $valueId
     * @return array
     */
    public function getValueUsage($valueId) {
        $sql = "SELECT jpt.position_title, jpt.department, jtv.weight_percentage
                FROM job_template_values jtv
                JOIN job_position_templates jpt ON jtv.job_template_id = jpt.id
                WHERE jtv.value_id = ? AND jpt.is_active = 1
                ORDER BY jpt.position_title";
        
        return fetchAll($sql, [$valueId]);
    }
    
    /**
     * Get value statistics from evaluations
     * @param int $valueId
     * @param string $periodStart
     * @param string $periodEnd
     * @return array
     */
    public function getValueStatistics($valueId, $periodStart = null, $periodEnd = null) {
        $sql = "SELECT 
                    COUNT(*) as total_evaluations,
                    AVG(evr.score) as average_score,
                    MIN(evr.score) as min_score,
                    MAX(evr.score) as max_score,
                    COUNT(CASE WHEN evr.score >= 4.0 THEN 1 END) as high_performers,
                    COUNT(CASE WHEN evr.score < 3.0 THEN 1 END) as low_performers
                FROM evaluation_value_results evr
                JOIN evaluations e ON evr.evaluation_id = e.id
                WHERE evr.value_id = ?";
        
        $params = [$valueId];
        
        if ($periodStart && $periodEnd) {
            $sql .= " AND e.created_at BETWEEN ? AND ?";
            $params[] = $periodStart;
            $params[] = $periodEnd;
        }
        
        return fetchOne($sql, $params);
    }
    
    /**
     * Get all values statistics summary
     * @param string $periodStart
     * @param string $periodEnd
     * @return array
     */
    public function getAllValuesStatistics($periodStart = null, $periodEnd = null) {
        $sql = "SELECT 
                    cv.value_name,
                    cv.description,
                    COUNT(evr.id) as total_evaluations,
                    AVG(evr.score) as average_score,
                    MIN(evr.score) as min_score,
                    MAX(evr.score) as max_score
                FROM company_values cv
                LEFT JOIN evaluation_value_results evr ON cv.id = evr.value_id
                LEFT JOIN evaluations e ON evr.evaluation_id = e.id
                WHERE cv.is_active = 1";
        
        $params = [];
        
        if ($periodStart && $periodEnd) {
            $sql .= " AND (e.created_at BETWEEN ? AND ? OR e.created_at IS NULL)";
            $params[] = $periodStart;
            $params[] = $periodEnd;
        }
        
        $sql .= " GROUP BY cv.id ORDER BY cv.sort_order, cv.value_name";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Calculate value score based on behaviors and examples
     * @param array $behaviors Array of behavior ratings
     * @return float
     */
    public function calculateValueScore($behaviors) {
        if (empty($behaviors)) {
            return 3.0; // Default neutral score
        }
        
        $totalScore = 0;
        $count = 0;
        
        foreach ($behaviors as $behavior) {
            if (is_numeric($behavior) && $behavior >= 1 && $behavior <= 5) {
                $totalScore += $behavior;
                $count++;
            }
        }
        
        return $count > 0 ? round($totalScore / $count, 1) : 3.0;
    }
    
    /**
     * Get value behavioral indicators
     * @param int $valueId
     * @return array
     */
    public function getValueBehaviors($valueId) {
        // This could be extended to store behavioral indicators in the database
        // For now, return default behaviors based on common company values
        $defaultBehaviors = [
            'Integrity' => [
                'Acts with honesty and transparency in all interactions',
                'Takes responsibility for mistakes and learns from them',
                'Maintains confidentiality when required',
                'Follows through on commitments and promises'
            ],
            'Excellence' => [
                'Consistently delivers high-quality work',
                'Seeks continuous improvement in processes and outcomes',
                'Pays attention to detail and accuracy',
                'Goes above and beyond expectations'
            ],
            'Innovation' => [
                'Proposes creative solutions to challenges',
                'Embraces new technologies and methodologies',
                'Encourages experimentation and calculated risk-taking',
                'Shares knowledge and best practices with others'
            ],
            'Collaboration' => [
                'Works effectively with diverse teams',
                'Actively listens and considers different perspectives',
                'Supports colleagues and contributes to team success',
                'Communicates clearly and respectfully'
            ],
            'Customer Focus' => [
                'Understands and anticipates customer needs',
                'Responds promptly to customer inquiries and concerns',
                'Seeks feedback to improve customer experience',
                'Makes decisions with customer impact in mind'
            ]
        ];
        
        $value = $this->getValueById($valueId);
        if ($value && isset($defaultBehaviors[$value['value_name']])) {
            return $defaultBehaviors[$value['value_name']];
        }
        
        return [
            'Demonstrates this value through daily actions',
            'Serves as a role model for others',
            'Consistently applies this value in decision-making',
            'Promotes this value within the team and organization'
        ];
    }
    
    /**
     * Import company values from CSV
     * @param string $csvFile
     * @param int $createdBy
     * @return array
     */
    public function importValuesFromCSV($csvFile, $createdBy) {
        $results = ['success' => 0, 'errors' => []];
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            $header = fgetcsv($handle); // Skip header row
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    $valueData = [
                        'value_name' => $data[0] ?? '',
                        'description' => $data[1] ?? '',
                        'sort_order' => $data[2] ?? 0,
                        'created_by' => $createdBy
                    ];
                    
                    if (empty($valueData['value_name'])) {
                        $results['errors'][] = "Empty value name in row";
                        continue;
                    }
                    
                    $this->createValue($valueData);
                    $results['success']++;
                    
                } catch (Exception $e) {
                    $results['errors'][] = "Error importing value: " . $e->getMessage();
                }
            }
            fclose($handle);
        }
        
        return $results;
    }
    
    /**
     * Export company values to CSV
     * @return string
     */
    public function exportValuesToCSV() {
        $values = $this->getValues();
        
        $csv = "Value Name,Description,Sort Order,Created By,Created At\n";
        
        foreach ($values as $value) {
            $csv .= sprintf('"%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $value['value_name']),
                str_replace('"', '""', $value['description']),
                str_replace('"', '""', $value['sort_order']),
                str_replace('"', '""', ($value['created_by_first_name'] ?? '') . ' ' . ($value['created_by_last_name'] ?? '')),
                str_replace('"', '""', $value['created_at'])
            );
        }
        
        return $csv;
    }
}
?>