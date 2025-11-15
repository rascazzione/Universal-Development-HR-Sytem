<?php
/**
 * Company KPI Class
 * Handles company-wide KPIs directory
 */

class CompanyKPI {
    
    /**
     * Allowed target types recognized by the evaluation engine.
     * @var array
     */
    private static $allowedTargetTypes = ['higher_better', 'lower_better', 'target_range'];
    
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
        $results = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        if (!is_readable($csvFile)) {
            $results['errors'][] = 'CSV file could not be read.';
            return $results;
        }
        
        if (($handle = fopen($csvFile, "r")) === false) {
            $results['errors'][] = 'Unable to open CSV file.';
            return $results;
        }
        
        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            $results['errors'][] = 'CSV file does not contain a header row.';
            return $results;
        }
        
        $headerMap = $this->buildHeaderMap($headerRow);
        $columnIndexes = $this->mapImportColumns($headerMap);
        
        $missingColumns = [];
        foreach (['kpi_name' => 'KPI Name', 'category' => 'Category'] as $field => $label) {
            if (!isset($columnIndexes[$field])) {
                $missingColumns[] = $label;
            }
        }
        
        if (!empty($missingColumns)) {
            fclose($handle);
            $results['errors'][] = 'Missing required columns: ' . implode(', ', $missingColumns);
            return $results;
        }
        
        $rowNumber = 1; // Header already read
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            if ($this->isRowEmpty($row)) {
                $results['skipped']++;
                continue;
            }
            
            try {
                $kpiName = $this->extractColumnValue($row, $columnIndexes, 'kpi_name');
                $category = $this->extractColumnValue($row, $columnIndexes, 'category');
                
                if ($kpiName === '' || $category === '') {
                    $results['errors'][] = "Row {$rowNumber}: KPI Name and Category are required.";
                    $results['skipped']++;
                    continue;
                }
                
                $kpiData = [
                    'kpi_name' => $kpiName,
                    'kpi_description' => $this->extractColumnValue($row, $columnIndexes, 'kpi_description'),
                    'measurement_unit' => $this->extractColumnValue($row, $columnIndexes, 'measurement_unit') ?: 'count',
                    'category' => $category,
                    'target_type' => $this->normalizeTargetType($this->extractColumnValue($row, $columnIndexes, 'target_type')),
                    'created_by' => $createdBy
                ];
                
                $existingId = $this->findKPIIdByNameAndCategory($kpiData['kpi_name'], $kpiData['category']);
                
                if ($existingId) {
                    $this->updateKPI($existingId, $kpiData);
                    $results['updated']++;
                } else {
                    $this->createKPI($kpiData);
                    $results['imported']++;
                }
            } catch (Exception $e) {
                $results['errors'][] = "Row {$rowNumber}: " . $e->getMessage();
                $results['skipped']++;
            }
        }
        
        fclose($handle);
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
    
    /**
     * Return curated starter KPIs defined in the catalog config.
     * @param string|null $category
     * @return array
     */
    public function getStarterKPICatalog($category = null) {
        static $catalog = null;
        
        if ($catalog === null) {
            $catalogFile = __DIR__ . '/../config/kpi_catalog.php';
            if (file_exists($catalogFile)) {
                $data = include $catalogFile;
                $catalog = is_array($data) ? $data : [];
            } else {
                $catalog = [];
            }
        }
        
        if ($category) {
            return array_values(array_filter($catalog, function ($item) use ($category) {
                return isset($item['category']) && strcasecmp($item['category'], $category) === 0;
            }));
        }
        
        return $catalog;
    }
    
    /**
     * Build a normalized map of header labels to their column indexes.
     * @param array $headerRow
     * @return array
     */
    private function buildHeaderMap($headerRow) {
        $map = [];
        foreach ($headerRow as $index => $label) {
            $normalized = strtolower(trim((string)$label));
            if ($normalized === '') {
                continue;
            }
            if (!array_key_exists($normalized, $map)) {
                $map[$normalized] = $index;
            }
        }
        return $map;
    }
    
    /**
     * Map CSV columns to internal fields using supported aliases.
     * @param array $headerMap
     * @return array
     */
    private function mapImportColumns(array $headerMap) {
        $aliases = [
            'kpi_name' => ['kpi name', 'kpi_name', 'name', 'kpi'],
            'kpi_description' => ['description', 'kpi description', 'kpi_description', 'details'],
            'measurement_unit' => ['measurement unit', 'measurement_unit', 'unit', 'uom'],
            'category' => ['category', 'kpi category', 'kpi_category'],
            'target_type' => ['target type', 'target_type', 'target', 'direction']
        ];
        
        $columns = [];
        foreach ($aliases as $field => $fieldAliases) {
            foreach ($fieldAliases as $alias) {
                if (array_key_exists($alias, $headerMap)) {
                    $columns[$field] = $headerMap[$alias];
                    break;
                }
            }
        }
        
        return $columns;
    }
    
    /**
     * Extract and trim a value from the CSV row for a given field.
     * @param array $row
     * @param array $columnIndexes
     * @param string $field
     * @return string
     */
    private function extractColumnValue(array $row, array $columnIndexes, $field) {
        if (!isset($columnIndexes[$field])) {
            return '';
        }
        
        $index = $columnIndexes[$field];
        return isset($row[$index]) ? trim((string)$row[$index]) : '';
    }
    
    /**
     * Determine if an entire CSV row is empty/blank.
     * @param array $row
     * @return bool
     */
    private function isRowEmpty(array $row) {
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Normalize target type aliases into supported values.
     * @param string $targetType
     * @return string
     */
    private function normalizeTargetType($targetType) {
        $value = strtolower(trim((string)$targetType));
        
        if ($value === '') {
            return 'higher_better';
        }
        
        $aliasMap = [
            'higher' => 'higher_better',
            'increase' => 'higher_better',
            'growth' => 'higher_better',
            'lower' => 'lower_better',
            'decrease' => 'lower_better',
            'reduction' => 'lower_better',
            'range' => 'target_range',
            'balanced' => 'target_range'
        ];
        
        if (isset($aliasMap[$value])) {
            $value = $aliasMap[$value];
        }
        
        if (in_array($value, self::$allowedTargetTypes, true)) {
            return $value;
        }
        
        return 'higher_better';
    }
    
    /**
     * Locate an existing KPI by name and category to avoid duplicates.
     * @param string $kpiName
     * @param string $category
     * @return int|null
     */
    private function findKPIIdByNameAndCategory($kpiName, $category) {
        $sql = "SELECT id 
                FROM company_kpis 
                WHERE LOWER(kpi_name) = LOWER(?) 
                  AND LOWER(COALESCE(category, '')) = LOWER(?) 
                  AND is_active = 1
                LIMIT 1";
        
        $existing = fetchOne($sql, [$kpiName, $category ?? '']);
        return $existing['id'] ?? null;
    }
}
?>
