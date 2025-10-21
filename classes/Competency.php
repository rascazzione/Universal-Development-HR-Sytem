<?php
/**
 * Competency Class
 * Handles skills, knowledge, and competencies catalog
 */

class Competency {
    
    private $softSkillLevelsPath;
    
    public function __construct() {
        $this->softSkillLevelsPath = __DIR__ . '/../config/soft_skill_levels.json';
    }
    
    /**
     * Get all competency categories
     * @param bool $includeSubcategories
     * @return array
     */
    public function getCategories($includeSubcategories = true, $categoryType = null) {
        // DEBUG: Log method parameters
        error_log("[DEBUG] Competency::getCategories - includeSubcategories: " . ($includeSubcategories ? 'true' : 'false') .
                  ", categoryType: " . ($categoryType ?? 'null'));
        
        $sql = "SELECT cc.*,
                       parent.category_name as parent_category_name,
                       COUNT(c.id) as competency_count
                FROM competency_categories cc
                LEFT JOIN competency_categories parent ON cc.parent_id = parent.id
                LEFT JOIN competencies c ON cc.id = c.category_id AND c.is_active = 1
                WHERE cc.is_active = 1";
        
        if (!$includeSubcategories) {
            $sql .= " AND cc.parent_id IS NULL";
        }
        
        if ($categoryType) {
            $sql .= " AND cc.category_type = ?";
            $params[] = $categoryType;
        }
        
        $sql .= " GROUP BY cc.id ORDER BY cc.category_name";
        
        $result = fetchAll($sql, $params ?? []);
        error_log("[DEBUG] Competency::getCategories - SQL: " . $sql);
        error_log("[DEBUG] Competency::getCategories - Result count: " . count($result));
        
        return $result;
    }
    
    /**
     * Get category by ID
     * @param int $id
     * @return array|false
     */
    public function getCategoryById($id) {
        $sql = "SELECT cc.*, parent.category_name as parent_category_name
                FROM competency_categories cc
                LEFT JOIN competency_categories parent ON cc.parent_id = parent.id
                WHERE cc.id = ? AND cc.is_active = 1";
        
        return fetchOne($sql, [$id]);
    }
    
    /**
     * Create new competency category
     * @param array $data
     * @return int
     */
    public function createCategory($data) {
        $sql = "INSERT INTO competency_categories (category_name, description, parent_id, category_type)
                VALUES (?, ?, ?, ?)";
        
        return insertRecord($sql, [
            $data['category_name'],
            $data['description'],
            $data['parent_id'] ?? null,
            $data['category_type'] ?? 'technical'
        ]);
    }
    
    /**
     * Update competency category
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateCategory($id, $data) {
        // DEBUG: Log incoming data
        error_log("[DEBUG] updateCategory - ID: " . $id);
        error_log("[DEBUG] updateCategory - Data: " . print_r($data, true));
        
        $sql = "UPDATE competency_categories
                SET category_name = ?, description = ?, parent_id = ?, category_type = ?
                WHERE id = ?";
        
        $params = [
            $data['category_name'],
            $data['description'],
            $data['parent_id'] ?? null,
            $data['category_type'] ?? 'technical',
            $id
        ];
        
        error_log("[DEBUG] updateCategory - SQL: " . $sql);
        error_log("[DEBUG] updateCategory - Params: " . print_r($params, true));
        
        try {
            $result = updateRecord($sql, $params);
            error_log("[DEBUG] updateCategory - Result (affected rows): " . $result);
            return $result;
        } catch (Exception $e) {
            error_log("[ERROR] updateCategory - Exception: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete competency category
     * @param int $id
     * @return int
     */
    public function deleteCategory($id) {
        $sql = "UPDATE competency_categories SET is_active = 0 WHERE id = ?";
        return updateRecord($sql, [$id]);
    }
    
    /**
     * Get all competencies
     * @param int $categoryId
     * @param string $type
     * @return array
     */
    public function getCompetencies($categoryId = null, $categoryType = null) {
        // DEBUG: Log method parameters
        error_log("[DEBUG] Competency::getCompetencies - categoryId: " . ($categoryId ?? 'null') .
                  ", categoryType: " . ($categoryType ?? 'null'));
        
        $sql = "SELECT c.*,
                       cc.category_name,
                       cc.category_type,
                       parent.category_name as parent_category_name
                FROM competencies c
                LEFT JOIN competency_categories cc ON c.category_id = cc.id
                LEFT JOIN competency_categories parent ON cc.parent_id = parent.id
                WHERE c.is_active = 1";
        
        $params = [];
        
        if ($categoryId) {
            $sql .= " AND c.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($categoryType) {
            $sql .= " AND cc.category_type = ?";
            $params[] = $categoryType;
        }
        
        $sql .= " ORDER BY cc.category_name, c.competency_name";
        
        error_log("[DEBUG] Competency::getCompetencies - SQL: " . $sql);
        error_log("[DEBUG] Competency::getCompetencies - Params: " . print_r($params, true));
        
        $result = fetchAll($sql, $params);
        error_log("[DEBUG] Competency::getCompetencies - Result count: " . count($result));
        
        return $result;
    }
    
    /**
     * Get competency by ID
     * @param int $id
     * @return array|false
     */
    public function getCompetencyById($id) {
        $sql = "SELECT c.*,
                       cc.category_name,
                       cc.category_type,
                       parent.category_name as parent_category_name
                FROM competencies c
                LEFT JOIN competency_categories cc ON c.category_id = cc.id
                LEFT JOIN competency_categories parent ON cc.parent_id = parent.id
                WHERE c.id = ? AND c.is_active = 1";
        
        return fetchOne($sql, [$id]);
    }
    
    /**
     * Create new competency
     * @param array $data
     * @return int
     */
    public function createCompetency($data) {
        $sql = "INSERT INTO competencies (competency_name, description, category_id)
                VALUES (?, ?, ?)";
        
        return insertRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id']
        ]);
    }
    
    /**
     * Update competency
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateCompetency($id, $data) {
        $sql = "UPDATE competencies
                SET competency_name = ?, description = ?, category_id = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id'],
            $id
        ]);
    }
    
    /**
     * Delete competency
     * @param int $id
     * @return int
     */
    public function deleteCompetency($id) {
        $sql = "UPDATE competencies SET is_active = 0 WHERE id = ?";
        return updateRecord($sql, [$id]);
    }
    
    /**
     * Get competency types
     * @return array
     */
    public function getCompetencyTypes() {
        return [
            'technical' => 'Technical Skills',
            'soft_skill' => 'Soft Skills'
        ];
    }
    
    /**
     * Get category types
     * @return array
     */
    public function getCategoryTypes() {
        return [
            'technical' => 'Technical Skills',
            'soft_skill' => 'Soft Skills'
        ];
    }
    
    /**
     * Get competency levels
     * @return array
     */
    public function getCompetencyLevels() {
        return [
            'basic' => 'Basic',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'expert' => 'Expert'
        ];
    }
    
    /**
     * Get competency level description
     * @param string $level
     * @return string
     */
    public function getLevelDescription($level) {
        $descriptions = [
            'basic' => 'Has fundamental knowledge and can perform basic tasks with guidance',
            'intermediate' => 'Can work independently and handle most situations effectively',
            'advanced' => 'Highly skilled, can handle complex situations and mentor others',
            'expert' => 'Recognized authority, can innovate and lead strategic initiatives'
        ];
        
        return $descriptions[$level] ?? '';
    }
    
    /**
     * Get competencies used in job templates
     * @param int $competencyId
     * @return array
     */
    public function getCompetencyUsage($competencyId) {
        $sql = "SELECT jpt.position_title, jpt.department, jtc.required_level, jtc.weight_percentage
                FROM job_template_competencies jtc
                JOIN job_position_templates jpt ON jtc.job_template_id = jpt.id
                WHERE jtc.competency_id = ? AND jpt.is_active = 1
                ORDER BY jpt.position_title";
        
        return fetchAll($sql, [$competencyId]);
    }
    
    /**
     * Calculate competency score based on required and achieved levels
     * @param string $requiredLevel
     * @param string $achievedLevel
     * @return float
     */
    public function calculateCompetencyScore($requiredLevel, $achievedLevel) {
        $levels = ['basic' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
        
        $requiredScore = $levels[$requiredLevel] ?? 2;
        $achievedScore = $levels[$achievedLevel] ?? 2;
        
        if ($achievedScore >= $requiredScore) {
            // Exceeds or meets requirements
            $ratio = $achievedScore / $requiredScore;
            if ($ratio >= 1.5) return 5.0; // Significantly exceeds
            if ($ratio >= 1.25) return 4.5; // Exceeds
            if ($ratio >= 1.0) return 4.0; // Meets
        } else {
            // Below requirements
            $ratio = $achievedScore / $requiredScore;
            if ($ratio >= 0.75) return 3.0; // Partially meets
            if ($ratio >= 0.5) return 2.0; // Below expectations
            return 1.0; // Significantly below
        }
        
        return 3.0; // Default
    }
    
    /**
     * Get competency statistics
     * @param int $competencyId
     * @param string $periodStart
     * @param string $periodEnd
     * @return array
     */
    public function getCompetencyStatistics($competencyId, $periodStart = null, $periodEnd = null) {
        $sql = "SELECT 
                    COUNT(*) as total_evaluations,
                    AVG(ecr.score) as average_score,
                    MIN(ecr.score) as min_score,
                    MAX(ecr.score) as max_score,
                    ecr.required_level,
                    ecr.achieved_level,
                    COUNT(*) as level_count
                FROM evaluation_competency_results ecr
                JOIN evaluations e ON ecr.evaluation_id = e.id
                WHERE ecr.competency_id = ?";
        
        $params = [$competencyId];
        
        if ($periodStart && $periodEnd) {
            $sql .= " AND e.created_at BETWEEN ? AND ?";
            $params[] = $periodStart;
            $params[] = $periodEnd;
        }
        
        $sql .= " GROUP BY ecr.required_level, ecr.achieved_level";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Import competencies from CSV
     * @param string $csvFile
     * @return array
     */
    public function importCompetenciesFromCSV($csvFile) {
        $results = ['success' => 0, 'errors' => []];
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            $header = fgetcsv($handle); // Skip header row
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    $competencyData = [
                        'competency_name' => $data[0] ?? '',
                        'description' => $data[1] ?? '',
                        'category_id' => $data[2] ?? null
                    ];
                    
                    if (empty($competencyData['competency_name'])) {
                        $results['errors'][] = "Empty competency name in row";
                        continue;
                    }
                    
                    $this->createCompetency($competencyData);
                    $results['success']++;
                    
                } catch (Exception $e) {
                    $results['errors'][] = "Error importing competency: " . $e->getMessage();
                }
            }
            fclose($handle);
        }
        
        return $results;
    }
    
    /**
     * Export competencies to CSV
     * @param int $categoryId
     * @return string
     */
    public function exportCompetenciesToCSV($categoryId = null) {
        $competencies = $this->getCompetencies($categoryId);
        
        $csv = "Competency Name,Description,Category,Category Type,Created At\n";
        
        foreach ($competencies as $competency) {
            $csv .= sprintf('"%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $competency['competency_name']),
                str_replace('"', '""', $competency['description']),
                str_replace('"', '""', $competency['category_name']),
                str_replace('"', '""', $competency['category_type']),
                str_replace('"', '""', $competency['created_at'])
            );
        }
        
        return $csv;
    }
    
    /**
     * Get soft skill level definitions from JSON file
     * @return array
     */
    public function getSoftSkillLevelDefinitions() {
        if (!file_exists($this->softSkillLevelsPath)) {
            return [];
        }
        
        $jsonContent = file_get_contents($this->softSkillLevelsPath);
        $data = json_decode($jsonContent, true);
        
        return $data['soft_skills'] ?? [];
    }
    
    /**
     * Get soft skill level definition for a specific competency
     * @param string $competencyKey
     * @return array|false
     */
    public function getSoftSkillLevels($competencyKey) {
        $definitions = $this->getSoftSkillLevelDefinitions();
        return $definitions[$competencyKey] ?? false;
    }
    
    /**
     * Save soft skill level definitions to JSON file
     * @param array $definitions
     * @return bool
     */
    public function saveSoftSkillLevelDefinitions($definitions) {
        $data = [
            'soft_skills' => $definitions,
            'level_mapping' => [
                '1' => 'basic',
                '2' => 'intermediate',
                '3' => 'advanced',
                '4' => 'expert'
            ]
        ];
        
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return file_put_contents($this->softSkillLevelsPath, $jsonContent) !== false;
    }
    
    /**
     * Save soft skill levels for a specific competency
     * @param string $competencyKey
     * @param array $levels
     * @return bool
     */
    public function saveSoftSkillLevels($competencyKey, $levels) {
        // Check if file is writable
        if (!is_writable($this->softSkillLevelsPath)) {
            throw new Exception("JSON file is not writable");
        }
        
        $definitions = $this->getSoftSkillLevelDefinitions();
        $definitions[$competencyKey] = $levels;
        
        return $this->saveSoftSkillLevelDefinitions($definitions);
    }
    
    /**
     * Convert competency name to key for JSON storage
     * @param string $competencyName
     * @return string
     */
    public function competencyNameToKey($competencyName) {
        return strtolower(str_replace([' ', '-'], '_', $competencyName));
    }
    
    /**
     * Get level mapping from 1-4 to basic/expert
     * @return array
     */
    public function getLevelMapping() {
        return [
            '1' => 'basic',
            '2' => 'intermediate',
            '3' => 'advanced',
            '4' => 'expert'
        ];
    }
    
    /**
     * Check if a competency is a soft skill based on its category
     * @param int $competencyId
     * @return bool
     */
    public function isSoftSkillCompetency($competencyId) {
        $competency = $this->getCompetencyById($competencyId);
        if (!$competency) {
            return false;
        }
        
        $sql = "SELECT category_type FROM competency_categories WHERE id = ? AND is_active = 1";
        $categoryType = fetchOne($sql, [$competency['category_id']]);
        
        return $categoryType && $categoryType['category_type'] === 'soft_skill';
    }
}
?>