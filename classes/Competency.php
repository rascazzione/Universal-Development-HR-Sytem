<?php
/**
 * Competency Class
 * Handles skills, knowledge, and competencies catalog
 */

class Competency {
    
    private $softSkillLevelsPath;
    private $technicalLevelsCache = null;
    private $softSkillCatalogCache = null;
    
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
        $category = isset($data['category_id']) ? $this->getCategoryById($data['category_id']) : null;
        $moduleType = $data['module_type'] ?? ($category['module_type'] ?? $category['category_type'] ?? 'technical');
        $competencyType = $data['competency_type'] ?? ($moduleType === 'soft_skill' ? 'soft_skill' : 'technical');
        
        $baseKey = $data['competency_key'] ?? null;
        if ($moduleType === 'soft_skill' && empty($baseKey)) {
            $baseKey = $this->competencyNameToKey($data['competency_name']);
        }
        $competencyKey = $moduleType === 'soft_skill' && $baseKey
            ? $this->resolveCompetencyKey($baseKey)
            : null;
        
        $symbol = $data['symbol'] ?? ($moduleType === 'soft_skill' ? '🧠' : '🧩');
        $maxLevel = $data['max_level'] ?? ($moduleType === 'soft_skill' ? 4 : 5);
        $levelType = $data['level_type'] ?? ($moduleType === 'soft_skill' ? 'soft_skill_scale' : 'technical_scale');
        
        $sql = "INSERT INTO competencies (competency_name, description, category_id, competency_type, competency_key, symbol, max_level, level_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        return insertRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id'],
            $competencyType,
            $competencyKey,
            $symbol,
            $maxLevel,
            $levelType
        ]);
    }
    
    /**
     * Update competency
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateCompetency($id, $data) {
        $existing = $this->getCompetencyById($id);
        if (!$existing) {
            throw new Exception("Competency not found for update.");
        }
        
        $category = isset($data['category_id']) ? $this->getCategoryById($data['category_id']) : null;
        $moduleType = $data['module_type']
            ?? ($category['module_type'] ?? $category['category_type'] ?? $existing['level_type'] === 'soft_skill_scale' ? 'soft_skill' : 'technical');
        $competencyType = $data['competency_type'] ?? $existing['competency_type'] ?? ($moduleType === 'soft_skill' ? 'soft_skill' : 'technical');
        
        $baseKey = array_key_exists('competency_key', $data)
            ? $data['competency_key']
            : ($existing['competency_key'] ?? null);
        if ($moduleType === 'soft_skill') {
            if (empty($baseKey)) {
                $baseKey = $this->competencyNameToKey($data['competency_name']);
            }
            $competencyKey = $this->resolveCompetencyKey($baseKey, $id);
        } else {
            $competencyKey = null;
        }
        
        $symbol = $data['symbol'] ?? ($existing['symbol'] ?? ($moduleType === 'soft_skill' ? '🧠' : '🧩'));
        $maxLevel = $data['max_level'] ?? ($existing['max_level'] ?? ($moduleType === 'soft_skill' ? 4 : 5));
        $levelType = $data['level_type'] ?? ($existing['level_type'] ?? ($moduleType === 'soft_skill' ? 'soft_skill_scale' : 'technical_scale'));
        
        $sql = "UPDATE competencies
                SET competency_name = ?, description = ?, category_id = ?, competency_type = ?, competency_key = ?, symbol = ?, max_level = ?, level_type = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id'],
            $competencyType,
            $competencyKey,
            $symbol,
            $maxLevel,
            $levelType,
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
        $levels = $this->getTechnicalSkillLevels();
        $mapping = [];
        foreach ($levels as $level) {
            $mapping[$level['id']] = $level['level_name'];
        }
        return $mapping;
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
        $sql = "SELECT 
                    jpt.position_title,
                    jpt.department,
                    jtc.module_type,
                    jtc.weight_percentage,
                    tsl.level_name AS technical_level_name,
                    tsl.display_level AS technical_display_level,
                    tsl.symbol_pattern AS technical_symbol_pattern,
                    jtc.soft_skill_level,
                    ssld.level_title AS soft_skill_level_title,
                    ssld.symbol_pattern AS soft_skill_symbol_pattern,
                    ssld.meaning AS soft_skill_meaning
                FROM job_template_competencies jtc
                JOIN job_position_templates jpt ON jtc.job_template_id = jpt.id
                LEFT JOIN technical_skill_levels tsl ON jtc.technical_level_id = tsl.id
                LEFT JOIN soft_skill_definitions ssd ON jtc.competency_key = ssd.competency_key
                LEFT JOIN soft_skill_level_details ssld ON ssd.id = ssld.soft_skill_id AND jtc.soft_skill_level = ssld.level_number
                WHERE jtc.competency_id = ? AND jpt.is_active = 1
                ORDER BY jpt.position_title";
        
        return fetchAll($sql, [$competencyId]);
    }
    
    /**
     * Calculate competency score based on required and achieved levels
     * Supports technical (5-point visual scale) and soft skills (4-level scale)
     * @param mixed $requiredLevel
     * @param mixed $achievedLevel
     * @param string $moduleType
     * @return float
     */
    public function calculateCompetencyScore($requiredLevel, $achievedLevel, string $moduleType = 'technical') {
        // Allow arrays with richer data
        if (is_array($requiredLevel) && isset($requiredLevel['level'])) {
            $requiredLevel = $requiredLevel['level'];
        }
        if (is_array($achievedLevel) && isset($achievedLevel['level'])) {
            $achievedLevel = $achievedLevel['level'];
        }
        
        if ($moduleType === 'soft_skill') {
            $requiredScore = max(1, (int)$requiredLevel);
            $achievedScore = max(1, (int)$achievedLevel);
            $maxScore = 4;
        } else {
            // Technical scale defaults
            if (is_string($requiredLevel) && !is_numeric($requiredLevel)) {
                $map = ['basic' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 5];
                $requiredLevel = $map[strtolower($requiredLevel)] ?? 3;
            }
            if (is_string($achievedLevel) && !is_numeric($achievedLevel)) {
                $map = ['basic' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 5];
                $achievedLevel = $map[strtolower($achievedLevel)] ?? 3;
            }
            $requiredScore = max(1, (int)$requiredLevel);
            $achievedScore = max(1, (int)$achievedLevel);
            $maxScore = 5;
        }
        
        $ratio = $requiredScore > 0 ? $achievedScore / $requiredScore : 1;
        
        if ($ratio >= 1.5) {
            return 5.0;
        }
        if ($ratio >= 1.25) {
            return 4.5;
        }
        if ($ratio >= 1.0) {
            return 4.0;
        }
        if ($ratio >= 0.75) {
            return 3.0;
        }
        if ($ratio >= 0.5) {
            return 2.0;
        }
        
        return 1.0;
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
                    ecr.module_type,
                    COUNT(*) as total_evaluations,
                    AVG(ecr.score) as average_score,
                    MIN(ecr.score) as min_score,
                    MAX(ecr.score) as max_score,
                    ecr.required_technical_level_id,
                    ecr.achieved_technical_level_id,
                    rtl.display_level AS required_technical_display,
                    atl.display_level AS achieved_technical_display,
                    rtl.level_name AS required_technical_level_name,
                    atl.level_name AS achieved_technical_level_name,
                    ecr.required_soft_skill_level,
                    ecr.achieved_soft_skill_level,
                    rsld.level_title AS required_soft_skill_title,
                    asld.level_title AS achieved_soft_skill_title
                FROM evaluation_competency_results ecr
                JOIN evaluations e ON ecr.evaluation_id = e.evaluation_id
                LEFT JOIN technical_skill_levels rtl ON ecr.required_technical_level_id = rtl.id
                LEFT JOIN technical_skill_levels atl ON ecr.achieved_technical_level_id = atl.id
                LEFT JOIN competencies c ON ecr.competency_id = c.id
                LEFT JOIN soft_skill_definitions ssd ON c.competency_key = ssd.competency_key
                LEFT JOIN soft_skill_level_details rsld ON ssd.id = rsld.soft_skill_id AND ecr.required_soft_skill_level = rsld.level_number
                LEFT JOIN soft_skill_level_details asld ON ssd.id = asld.soft_skill_id AND ecr.achieved_soft_skill_level = asld.level_number
                WHERE ecr.competency_id = ?";
        
        $params = [$competencyId];
        
        if ($periodStart && $periodEnd) {
            $sql .= " AND e.created_at BETWEEN ? AND ?";
            $params[] = $periodStart;
            $params[] = $periodEnd;
        }
        
        $sql .= " GROUP BY 
                    ecr.module_type,
                    ecr.required_technical_level_id,
                    ecr.achieved_technical_level_id,
                    ecr.required_soft_skill_level,
                    ecr.achieved_soft_skill_level,
                    rtl.display_level,
                    atl.display_level,
                    rtl.level_name,
                    atl.level_name,
                    rsld.level_title,
                    asld.level_title";
        
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
        
        $saved = file_put_contents($this->softSkillLevelsPath, $jsonContent) !== false;
        if ($saved) {
            $this->softSkillCatalogCache = null;
        }
        return $saved;
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
        
        $sql = "SELECT category_type, module_type FROM competency_categories WHERE id = ? AND is_active = 1";
        $categoryType = fetchOne($sql, [$competency['category_id']]);
        
        if (!$categoryType) {
            return false;
        }
        
        if (($categoryType['module_type'] ?? null) === 'soft_skill') {
            return true;
        }
        
        if (($categoryType['category_type'] ?? null) === 'soft_skill') {
            return true;
        }
        
        return ($competency['level_type'] ?? '') === 'soft_skill_scale' || ($competency['competency_type'] ?? '') === 'soft_skill';
    }
    
    /**
     * Retrieve technical skill levels (cached)
     * @return array
     */
    public function getTechnicalSkillLevels(): array {
        if ($this->technicalLevelsCache !== null) {
            return $this->technicalLevelsCache;
        }
        
        $sql = "SELECT * FROM technical_skill_levels ORDER BY display_level";
        $levels = fetchAll($sql);
        
        $this->technicalLevelsCache = $levels;
        return $levels;
    }
    
    /**
     * Get technical levels mapped by ID for quick lookup
     * @return array
     */
    public function getTechnicalSkillLevelsById(): array {
        $levels = $this->getTechnicalSkillLevels();
        $indexed = [];
        foreach ($levels as $level) {
            $indexed[$level['id']] = $level;
        }
        return $indexed;
    }
    
    /**
     * Ensure soft skill definitions table mirrors JSON definitions
     * @return array Synchronized catalog
     */
    public function getSoftSkillCatalog(): array {
        if ($this->softSkillCatalogCache !== null) {
            return $this->softSkillCatalogCache;
        }
        
        $definitions = $this->getSoftSkillLevelDefinitions();
        $this->syncSoftSkillDefinitions($definitions);
        $this->softSkillCatalogCache = $definitions;
        return $definitions;
    }
    
    /**
     * Synchronize JSON definitions into relational tables
     * @param array $definitions
     * @return void
     */
    private function syncSoftSkillDefinitions(array $definitions): void {
        foreach ($definitions as $competencyKey => $definition) {
            if (empty($competencyKey)) {
                continue;
            }
            
            $definitionId = insertRecord(
                "INSERT INTO soft_skill_definitions (competency_key, name, definition, description, json_source_path)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), definition = VALUES(definition), description = VALUES(description), json_source_path = VALUES(json_source_path), updated_at = CURRENT_TIMESTAMP",
                [
                    $competencyKey,
                    $definition['name'] ?? ucwords(str_replace('_', ' ', $competencyKey)),
                    $definition['definition'] ?? '',
                    $definition['description'] ?? '',
                    realpath($this->softSkillLevelsPath) ?: $this->softSkillLevelsPath
                ]
            );
            
            // ON DUPLICATE KEY UPDATE returns the existing id as 0, fetch to ensure we have correct id
            $record = fetchOne("SELECT id FROM soft_skill_definitions WHERE competency_key = ?", [$competencyKey]);
            $definitionId = $record['id'] ?? $definitionId;
            
            if (empty($definitionId)) {
                continue;
            }
            
            $levels = $definition['levels'] ?? [];
            foreach ($levels as $levelNumber => $levelData) {
                insertRecord(
                    "INSERT INTO soft_skill_level_details (soft_skill_id, level_number, level_title, behaviors, symbol_pattern, meaning)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE level_title = VALUES(level_title), behaviors = VALUES(behaviors), symbol_pattern = VALUES(symbol_pattern), meaning = VALUES(meaning)",
                    [
                        $definitionId,
                        (int)$levelNumber,
                        $levelData['title'] ?? '',
                        json_encode($levelData['behaviors'] ?? [], JSON_UNESCAPED_UNICODE),
                        $this->buildSoftSkillSymbolPattern((int)$levelNumber),
                        $this->softSkillMeaning((int)$levelNumber)
                    ]
                );
            }
        }
    }
    
    /**
     * Ensure technical display uses consistent icon pattern
     * @param int $displayLevel
     * @return string
     */
    public function buildTechnicalSymbolPattern(int $displayLevel): string {
        $filled = str_repeat('🧩', max(0, min($displayLevel, 5)));
        $empty = str_repeat('⚪️', max(0, 5 - $displayLevel));
        return $filled . $empty;
    }
    
    /**
     * Build soft skill symbol pattern based on 1-4 scale
     * @param int $level
     * @return string
     */
    public function buildSoftSkillSymbolPattern(int $level): string {
        $filled = str_repeat('🧠', max(0, min($level, 4)));
        $empty = str_repeat('⚪️', max(0, 4 - $level));
        return $filled . $empty;
    }
    
    /**
     * Map soft skill level to meaning label
     * @param int $level
     * @return string
     */
    private function softSkillMeaning(int $level): string {
        switch ($level) {
            case 1: return 'Basic';
            case 2: return 'Intermediate';
            case 3: return 'Advanced';
            case 4: return 'Expert';
            default: return 'Basic';
        }
    }
    
    /**
     * Check if a competency key already exists
     * @param string|null $key
     * @param int|null $excludeId
     * @return bool
     */
    private function competencyKeyExists(?string $key, ?int $excludeId = null): bool {
        if (!$key) {
            return false;
        }
        
        $sql = "SELECT id FROM competencies WHERE competency_key = ?";
        $params = [$key];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = fetchOne($sql, $params);
        return !empty($result);
    }
    
    /**
     * Resolve competency key to avoid duplicates
     * @param string $baseKey
     * @param int|null $excludeId
     * @return string
     */
    private function resolveCompetencyKey(string $baseKey, ?int $excludeId = null): string {
        $key = $baseKey;
        $suffix = 1;
        while ($this->competencyKeyExists($key, $excludeId)) {
            $key = $baseKey . '_' . $suffix;
            $suffix++;
        }
        return $key;
    }
}
?>
