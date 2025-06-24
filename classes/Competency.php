<?php
/**
 * Competency Class
 * Handles skills, knowledge, and competencies catalog
 */

class Competency {
    
    /**
     * Get all competency categories
     * @param bool $includeSubcategories
     * @return array
     */
    public function getCategories($includeSubcategories = true) {
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
        
        $sql .= " GROUP BY cc.id ORDER BY cc.category_name";
        
        return fetchAll($sql);
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
        $sql = "INSERT INTO competency_categories (category_name, description, parent_id) 
                VALUES (?, ?, ?)";
        
        return insertRecord($sql, [
            $data['category_name'],
            $data['description'],
            $data['parent_id'] ?? null
        ]);
    }
    
    /**
     * Update competency category
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateCategory($id, $data) {
        $sql = "UPDATE competency_categories 
                SET category_name = ?, description = ?, parent_id = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['category_name'],
            $data['description'],
            $data['parent_id'] ?? null,
            $id
        ]);
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
    public function getCompetencies($categoryId = null, $type = null) {
        $sql = "SELECT c.*, 
                       cc.category_name,
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
        
        if ($type) {
            $sql .= " AND c.competency_type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY cc.category_name, c.competency_name";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get competency by ID
     * @param int $id
     * @return array|false
     */
    public function getCompetencyById($id) {
        $sql = "SELECT c.*, 
                       cc.category_name,
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
        $sql = "INSERT INTO competencies (competency_name, description, category_id, competency_type) 
                VALUES (?, ?, ?, ?)";
        
        return insertRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id'],
            $data['competency_type']
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
                SET competency_name = ?, description = ?, category_id = ?, competency_type = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id'],
            $data['competency_type'],
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
            'soft_skill' => 'Soft Skills',
            'leadership' => 'Leadership',
            'core' => 'Core Competencies'
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
                        'category_id' => $data[2] ?? null,
                        'competency_type' => $data[3] ?? 'technical'
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
        
        $csv = "Competency Name,Description,Category,Type,Created At\n";
        
        foreach ($competencies as $competency) {
            $csv .= sprintf('"%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $competency['competency_name']),
                str_replace('"', '""', $competency['description']),
                str_replace('"', '""', $competency['category_name']),
                str_replace('"', '""', $competency['competency_type']),
                str_replace('"', '""', $competency['created_at'])
            );
        }
        
        return $csv;
    }
}
?>