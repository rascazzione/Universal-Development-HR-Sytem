<?php
/**
 * Company KPI Class
 * Handles company-wide KPIs directory
 */

class CompanyKPI {
    
    /**
     * Get all company KPIs
     * @param string $category
     * @return array
     */
    public function getKPIs($category = null) {
        $sql = "SELECT ck.*,
                       u.username as created_by_username
                FROM company_kpis ck
                LEFT JOIN users u ON ck.created_by = u.user_id
                WHERE ck.is_active = 1";
        
        $params = [];
        if ($category) {
            $sql .= " AND ck.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY ck.category, ck.kpi_name";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get KPI by ID
     * @param int $id
     * @return array|false
     */
    public function getKPIById($id) {
        $sql = "SELECT ck.*,
                       u.username as created_by_username
                FROM company_kpis ck
                LEFT JOIN users u ON ck.created_by = u.user_id
                WHERE ck.id = ? AND ck.is_active = 1";
        
        return fetchOne($sql, [$id]);
    }
    
    /**
     * Get all KPI categories
     * @return array
     */
    public function getKPICategories() {
        $sql = "SELECT DISTINCT category 
                FROM company_kpis 
                WHERE is_active = 1 AND category IS NOT NULL 
                ORDER BY category";
        
        return fetchAll($sql);
    }

    /**
     * Get all distinct measurement units
     * @return array
     */
    public function getMeasurementUnits() {
        $sql = "SELECT DISTINCT measurement_unit
                FROM company_kpis
                WHERE is_active = 1 AND measurement_unit IS NOT NULL AND measurement_unit != ''
                ORDER BY measurement_unit";
        return fetchAll($sql);
    }
    
    /**
     * Create new KPI
     * @param array $data
     * @return int
     */
    public function createKPI($data) {
        $sql = "INSERT INTO company_kpis (kpi_name, kpi_description, measurement_unit, category, target_type, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        return insertRecord($sql, [
            $data['kpi_name'],
            $data['kpi_description'],
            $data['measurement_unit'],
            $data['category'],
            $data['target_type'],
            $data['created_by']
        ]);
    }
    
    /**
     * Update KPI
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateKPI($id, $data) {
        $sql = "UPDATE company_kpis 
                SET kpi_name = ?, kpi_description = ?, measurement_unit = ?, category = ?, target_type = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['kpi_name'],
            $data['kpi_description'],
            $data['measurement_unit'],
            $data['category'],
            $data['target_type'],
            $id
        ]);
    }
    
    /**
     * Delete KPI
     * @param int $id
     * @return int
     */
    public function deleteKPI($id) {
        $sql = "UPDATE company_kpis SET is_active = 0 WHERE id = ?";
        return updateRecord($sql, [$id]);
    }
    
    /**
     * Get KPIs used in job templates
     * @param int $kpiId
     * @return array
     */
    public function getKPIUsage($kpiId) {
        $sql = "SELECT jpt.position_title, jpt.department, jtk.target_value, jtk.weight_percentage
                FROM job_template_kpis jtk
                JOIN job_position_templates jpt ON jtk.job_template_id = jpt.id
                WHERE jtk.kpi_id = ? AND jpt.is_active = 1
                ORDER BY jpt.position_title";
        
        return fetchAll($sql, [$kpiId]);
    }
    
    /**
     * Calculate KPI score based on target and achieved values
     * @param float $targetValue
     * @param float $achievedValue
     * @param string $targetType
     * @return float
     */
    public function calculateKPIScore($targetValue, $achievedValue, $targetType = 'higher_better') {
        if ($targetValue == 0) {
            return 0;
        }
        
        switch ($targetType) {
            case 'higher_better':
                // Higher values are better (e.g., sales, productivity)
                $percentage = ($achievedValue / $targetValue) * 100;
                if ($percentage >= 100) return 5.0;
                if ($percentage >= 90) return 4.0;
                if ($percentage >= 80) return 3.0;
                if ($percentage >= 70) return 2.0;
                return 1.0;
                
            case 'lower_better':
                // Lower values are better (e.g., defects, costs)
                if ($achievedValue <= $targetValue) {
                    $percentage = (($targetValue - $achievedValue) / $targetValue) * 100;
                    if ($percentage >= 20) return 5.0;
                    if ($percentage >= 10) return 4.0;
                    if ($percentage >= 5) return 3.0;
                    return 2.0;
                } else {
                    return 1.0;
                }
                
            case 'target_range':
                // Target range (e.g., within 5% of target)
                $variance = abs($achievedValue - $targetValue) / $targetValue * 100;
                if ($variance <= 2) return 5.0;
                if ($variance <= 5) return 4.0;
                if ($variance <= 10) return 3.0;
                if ($variance <= 15) return 2.0;
                return 1.0;
                
            default:
                return 3.0; // Default neutral score
        }
    }
    
    /**
     * Get KPI performance statistics
     * @param int $kpiId
     * @param string $periodStart
     * @param string $periodEnd
     * @return array
     */
    public function getKPIStatistics($kpiId, $periodStart = null, $periodEnd = null) {
        $sql = "SELECT 
                    COUNT(*) as total_evaluations,
                    AVG(ekr.score) as average_score,
                    MIN(ekr.score) as min_score,
                    MAX(ekr.score) as max_score,
                    AVG(ekr.achieved_value) as average_achieved,
                    MIN(ekr.achieved_value) as min_achieved,
                    MAX(ekr.achieved_value) as max_achieved
                FROM evaluation_kpi_results ekr
                JOIN evaluations e ON ekr.evaluation_id = e.id
                WHERE ekr.kpi_id = ?";
        
        $params = [$kpiId];
        
        if ($periodStart && $periodEnd) {
            $sql .= " AND e.created_at BETWEEN ? AND ?";
            $params[] = $periodStart;
            $params[] = $periodEnd;
        }
        
        return fetchOne($sql, $params);
    }
    
    /**
     * Import KPIs from CSV
     * @param string $csvFile
     * @param int $createdBy
     * @return array
     */
    public function importKPIsFromCSV($csvFile, $createdBy) {
        $results = ['success' => 0, 'errors' => []];
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            $header = fgetcsv($handle); // Skip header row
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    $kpiData = [
                        'kpi_name' => $data[0] ?? '',
                        'kpi_description' => $data[1] ?? '',
                        'measurement_unit' => $data[2] ?? '',
                        'category' => $data[3] ?? '',
                        'target_type' => $data[4] ?? 'higher_better',
                        'created_by' => $createdBy
                    ];
                    
                    if (empty($kpiData['kpi_name'])) {
                        $results['errors'][] = "Empty KPI name in row";
                        continue;
                    }
                    
                    $this->createKPI($kpiData);
                    $results['success']++;
                    
                } catch (Exception $e) {
                    $results['errors'][] = "Error importing KPI: " . $e->getMessage();
                }
            }
            fclose($handle);
        }
        
        return $results;
    }
    
    /**
     * Export KPIs to CSV
     * @param string $category
     * @return string
     */
    public function exportKPIsToCSV($category = null) {
        $kpis = $this->getKPIs($category);
        
        $csv = "KPI Name,Description,Measurement Unit,Category,Target Type,Created By,Created At\n";
        
        foreach ($kpis as $kpi) {
            $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $kpi['kpi_name']),
                str_replace('"', '""', $kpi['kpi_description']),
                str_replace('"', '""', $kpi['measurement_unit']),
                str_replace('"', '""', $kpi['category']),
                str_replace('"', '""', $kpi['target_type']),
                str_replace('"', '""', ($kpi['created_by_first_name'] ?? '') . ' ' . ($kpi['created_by_last_name'] ?? '')),
                str_replace('"', '""', $kpi['created_at'])
            );
        }
        
        return $csv;
    }
}
?>
