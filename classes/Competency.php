<?php
/**
 * Competency Class
 * Handles skills, knowledge, and competencies catalog
 */

class Competency {
    
    private $softSkillLevelsPath;
    private $softSkillLevelsDir;
    private $maintainLegacyAggregateFile = false;
    private $technicalLevelsCache = null;
    private $softSkillCatalogCache = null;
    
    public function __construct() {
        $projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        $configDir = $projectRoot . '/config';
        $configSoftSkillsDir = $configDir . '/soft_skills';
        
        $dataBase = '/var/www/data';
        $tempBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        
        $storageOptions = [
            ['base' => $configDir, 'dir' => $configSoftSkillsDir],
            ['base' => $dataBase, 'dir' => $dataBase . '/soft_skills'],
            ['base' => $tempBase, 'dir' => $tempBase . '/soft_skills']
        ];
        
        $selected = $storageOptions[count($storageOptions) - 1];
        foreach ($storageOptions as $option) {
            $this->ensureDirectoryExists($option['base']);
            $this->ensureDirectoryExists($option['dir']);
            
            if ($this->isDirectoryWritable($option['base']) && $this->isDirectoryWritable($option['dir'])) {
                $selected = $option;
                break;
            }
        }
        
        $this->softSkillLevelsDir = $selected['dir'];
        $this->softSkillLevelsPath = rtrim($selected['base'], DIRECTORY_SEPARATOR) . '/soft_skill_levels.json';
        $this->maintainLegacyAggregateFile = filter_var(
            getenv('SOFT_SKILL_LEGACY_FILE') ?: '0',
            FILTER_VALIDATE_BOOLEAN
        );
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
     * Get category by name (case-insensitive)
     * @param string $name
     * @return array|false
     */
    public function getCategoryByName($name) {
        if ($name === null) {
            return false;
        }
        
        $trimmed = trim((string)$name);
        if ($trimmed === '') {
            return false;
        }
        
        $sql = "SELECT * FROM competency_categories
                WHERE LOWER(category_name) = LOWER(?)
                  AND is_active = 1
                LIMIT 1";
        
        return fetchOne($sql, [$trimmed]);
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
     * Locate a category id by name or create it on the fly.
     * @param string $categoryName
     * @param string $categoryType
     * @return int|null
     */
    private function findOrCreateCategoryId($categoryName, $categoryType = 'technical') {
        $normalizedName = trim((string)$categoryName);
        if ($normalizedName === '') {
            return null;
        }
        
        $existing = $this->getCategoryByName($normalizedName);
        if ($existing) {
            return (int)$existing['id'];
        }
        
        return $this->createCategory([
            'category_name' => $normalizedName,
            'description' => '',
            'parent_id' => null,
            'category_type' => $categoryType ?: 'technical'
        ]);
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
                       cc.module_type AS category_module_type,
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
                       cc.module_type AS category_module_type,
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

        // Use the new schema without competency_type column
        $sql = "INSERT INTO competencies (competency_name, description, category_id, competency_key, symbol, max_level, level_type)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        return insertRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id'],
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
        
        // Use the new schema without competency_type column
        $sql = "UPDATE competencies
                SET competency_name = ?, description = ?, category_id = ?, competency_key = ?, symbol = ?, max_level = ?, level_type = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['competency_name'],
            $data['description'],
            $data['category_id'],
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
     * @param array $options
     * @return array
     */
    public function importCompetenciesFromCSV($csvFile, array $options = []) {
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
        
        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
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
        $columnIndexes = $this->mapCompetencyImportColumns($headerMap);
        
        $missingColumns = [];
        foreach (['competency_name' => 'Competency Name', 'category' => 'Category'] as $field => $label) {
            if (!isset($columnIndexes[$field])) {
                $missingColumns[] = $label;
            }
        }
        
        if (!empty($missingColumns)) {
            fclose($handle);
            $results['errors'][] = 'Missing required columns: ' . implode(', ', $missingColumns);
            return $results;
        }
        
        $rowNumber = 1; // header already consumed
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            if ($this->isRowEmpty($row)) {
                $results['skipped']++;
                continue;
            }
            
            try {
                $competencyName = $this->extractColumnValue($row, $columnIndexes, 'competency_name');
                $categoryName = $this->extractColumnValue($row, $columnIndexes, 'category');
                
                if ($competencyName === '' || $categoryName === '') {
                    $results['errors'][] = "Row {$rowNumber}: Competency Name and Category are required.";
                    $results['skipped']++;
                    continue;
                }
                
                $categoryType = $this->normalizeCategoryType(
                    $this->extractColumnValue($row, $columnIndexes, 'category_type')
                );
                $moduleType = $this->normalizeModuleType(
                    $this->extractColumnValue($row, $columnIndexes, 'module_type')
                ) ?: $categoryType;
                
                if (!$categoryType) {
                    $categoryType = $moduleType ?: 'technical';
                }
                if (!$moduleType) {
                    $moduleType = $categoryType ?: 'technical';
                }
                
                $categoryId = $this->findOrCreateCategoryId($categoryName, $categoryType);
                if (!$categoryId) {
                    $results['errors'][] = "Row {$rowNumber}: Unable to locate or create category '{$categoryName}'.";
                    $results['skipped']++;
                    continue;
                }
                
                $levelType = $this->normalizeLevelType(
                    $this->extractColumnValue($row, $columnIndexes, 'level_type'),
                    $moduleType
                );
                $maxLevel = $this->extractNumericValue($row, $columnIndexes, 'max_level');
                $symbol = $this->extractColumnValue($row, $columnIndexes, 'symbol');
                $competencyKey = $this->extractColumnValue($row, $columnIndexes, 'competency_key');
                
                $competencyData = [
                    'competency_name' => $competencyName,
                    'description' => $this->extractColumnValue($row, $columnIndexes, 'description'),
                    'category_id' => $categoryId,
                    'module_type' => $moduleType,
                    'competency_type' => $moduleType === 'soft_skill' ? 'soft_skill' : 'technical',
                    'competency_key' => $competencyKey !== '' ? $competencyKey : null,
                    'symbol' => $symbol !== '' ? $symbol : ($moduleType === 'soft_skill' ? '🧠' : '🧩'),
                    'max_level' => $maxLevel ?? ($moduleType === 'soft_skill' ? 4 : 5),
                    'level_type' => $levelType
                ];
                
                $existingId = $this->findCompetencyIdByNameAndCategory($competencyName, $categoryId);
                
                if ($existingId) {
                    $this->updateCompetency($existingId, $competencyData);
                    $results['updated']++;
                } else {
                    $this->createCompetency($competencyData);
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
     * Export competencies to CSV
     * @param int $categoryId
     * @return string
     */
    public function exportCompetenciesToCSV($categoryId = null) {
        $competencies = $this->getCompetencies($categoryId);
        
        $csv = "Competency Name,Description,Category,Category Type,Module Type,Symbol,Max Level,Level Type,Competency Key,Created At\n";
        
        foreach ($competencies as $competency) {
            $moduleType = $competency['category_module_type'] ?? $competency['category_type'] ?? 'technical';
            $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $competency['competency_name']),
                str_replace('"', '""', $competency['description']),
                str_replace('"', '""', $competency['category_name']),
                str_replace('"', '""', $competency['category_type']),
                str_replace('"', '""', $moduleType),
                str_replace('"', '""', $competency['symbol'] ?? ''),
                str_replace('"', '""', (string)($competency['max_level'] ?? '')),
                str_replace('"', '""', $competency['level_type'] ?? ''),
                str_replace('"', '""', $competency['competency_key'] ?? ''),
                str_replace('"', '""', $competency['created_at'] ?? '')
            );
        }
        
        return $csv;
    }
    
    /**
     * Return curated starter competencies used as import templates.
     * @param string|null $moduleType
     * @return array
     */
    public function getStarterCompetencyCatalog($moduleType = null) {
        static $catalog = null;
        
        if ($catalog === null) {
            $catalogFile = __DIR__ . '/../config/competency_catalog.php';
            if (file_exists($catalogFile)) {
                $data = include $catalogFile;
                $catalog = is_array($data) ? $data : [];
            } else {
                $catalog = [];
            }
        }
        
        $records = $catalog;
        if ($moduleType) {
            $normalizedType = $this->normalizeModuleType($moduleType);
            $records = array_values(array_filter($records, function ($item) use ($normalizedType) {
                $itemType = $item['module_type'] ?? $item['category_type'] ?? 'technical';
                return $this->normalizeModuleType($itemType) === $normalizedType;
            }));
        }
        
        return $records;
    }
    
    /**
     * Find competency id by name + category pairing.
     * @param string $competencyName
     * @param int $categoryId
     * @return int|null
     */
    private function findCompetencyIdByNameAndCategory($competencyName, $categoryId) {
        $sql = "SELECT id FROM competencies
                WHERE LOWER(competency_name) = LOWER(?)
                  AND category_id = ?
                  AND is_active = 1
                LIMIT 1";
        
        $existing = fetchOne($sql, [$competencyName, $categoryId]);
        return $existing['id'] ?? null;
    }
    
    /**
     * Get soft skill level definitions from JSON file
     * @return array
     */
    public function getSoftSkillLevelDefinitions() {
        $definitions = $this->loadSoftSkillDefinitionsFromDirectory();
        if (!empty($definitions)) {
            return $definitions;
        }
        
        // Legacy fallback: convert single JSON file into per-skill files on first access
        if (file_exists($this->softSkillLevelsPath)) {
            $legacy = $this->loadLegacySoftSkillDefinitions();
            if (!empty($legacy)) {
                $this->migrateLegacySoftSkillCatalog($legacy);
                $definitions = $this->loadSoftSkillDefinitionsFromDirectory();
                if (!empty($definitions)) {
                    return $definitions;
                }
                return $legacy;
            }
        }
        
        return [];
    }
    
    /**
     * Get soft skill level definition for a specific competency
     * @param string $competencyKey
     * @return array|false
     */
    public function getSoftSkillLevels($competencyKey) {
        $key = $this->normalizeCompetencyKey($competencyKey);
        $path = $this->getSoftSkillDefinitionPath($key);

        if (is_readable($path)) {
            $json = file_get_contents($path);
            $data = json_decode($this->sanitizeJsonPayload($json), true);
            $jsonError = json_last_error();

            if ($jsonError !== JSON_ERROR_NONE) {
                return false;
            }

            if (is_array($data)) {
                return $data;
            }
        }

        $definitions = $this->getSoftSkillLevelDefinitions();
        return $definitions[$key] ?? false;
    }
    
    public function getSoftSkillDefinitionFilePath(string $competencyKey): string {
        return $this->getSoftSkillDefinitionPath($competencyKey);
    }
    
    public function softSkillDefinitionExists(string $competencyKey): bool {
        return is_file($this->getSoftSkillDefinitionPath($competencyKey));
    }
    
    /**
     * Save soft skill level definitions to JSON file
     * @param array $definitions
     * @return bool
     */
    public function saveSoftSkillLevelDefinitions($definitions) {
        if (!is_array($definitions)) {
            return false;
        }
        
        $this->ensureSoftSkillDirectory();
        $saved = true;
        
        foreach ($definitions as $key => $definition) {
            if (!$this->writeSoftSkillDefinitionFile($key, $definition)) {
                $saved = false;
            }
        }
        
        if ($this->maintainLegacyAggregateFile) {
            $this->refreshSoftSkillAggregateFile();
        }
        
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
        $key = $this->normalizeCompetencyKey($competencyKey);

        $this->ensureSoftSkillDirectory(true);

        if (!$this->writeSoftSkillDefinitionFile($key, $levels)) {
            throw new Exception("Unable to persist soft skill definition for {$key}");
        }

        if ($this->maintainLegacyAggregateFile) {
            $this->refreshSoftSkillAggregateFile();
        }

        $this->softSkillCatalogCache = null;
        return true;
    }
    
    /**
     * Provide health summary for soft skill definition files.
     * @return array
     */
    public function getSoftSkillDefinitionStatus(): array {
        error_log("[DEBUG] getSoftSkillDefinitionStatus - Starting method");
        
        try {
            $definitions = $this->getSoftSkillLevelDefinitions();
            error_log("[DEBUG] getSoftSkillDefinitionStatus - Definitions retrieved: " . print_r($definitions, true));
            
            $files = [];
            $unassigned = [];

        // Only get competencies that are actually soft skills
        $competencies = fetchAll(
            "SELECT c.id, c.competency_name, c.competency_key
             FROM competencies c
             JOIN competency_categories cc ON c.category_id = cc.id
             WHERE c.competency_key IS NOT NULL
               AND c.competency_key != ''
               AND c.is_active = 1
               AND (cc.category_type = 'soft_skill' OR cc.module_type = 'soft_skill' OR c.level_type = 'soft_skill_scale')"
        );

        $competencyMap = [];
        foreach ($competencies as $competency) {
            $key = $this->normalizeCompetencyKey($competency['competency_key']);
            $competencyMap[$key][] = [
                'id' => (int)$competency['id'],
                'name' => $competency['competency_name']
            ];
        }
        
        foreach ($definitions as $key => $definition) {
            $path = $this->getSoftSkillDefinitionPath($key);
            $fileExists = file_exists($path);
            $files[] = [
                'competency_key' => $key,
                'name' => $definition['name'] ?? ucwords(str_replace('_', ' ', $key)),
                'file_path' => $fileExists ? realpath($path) : $path,
                'file_exists' => $fileExists,
                'last_modified' => $fileExists ? date('c', filemtime($path)) : null,
                'assignments' => $competencyMap[$key] ?? []
            ];
            
            if (empty($competencyMap[$key])) {
                $unassigned[] = $key;
            }
        }
        
        // Also check for orphaned JSON files that exist but aren't in active competencies
        $allJsonFiles = glob($this->softSkillLevelsDir . '/*.json');
        $orphanedFiles = [];
        foreach ($allJsonFiles as $file) {
            $key = basename($file, '.json');
            $normalizedKey = $this->normalizeCompetencyKey($key);
            
            if (!array_key_exists($normalizedKey, $definitions) && file_exists($file)) {
                $orphanedFiles[] = [
                    'competency_key' => $key,
                    'name' => ucwords(str_replace('_', ' ', $key)),
                    'file_path' => realpath($file),
                    'file_exists' => true,
                    'last_modified' => date('c', filemtime($file)),
                    'assignments' => [],
                    'orphaned' => true
                ];
            }
        }
        
        // Merge orphaned files into the main files array for display
        $files = array_merge($files, $orphanedFiles);
        
        $missingDefinitions = [];
        foreach ($competencyMap as $key => $list) {
            if (!array_key_exists($key, $definitions)) {
                $missingDefinitions[] = [
                    'competency_key' => $key,
                    'assignments' => $list
                ];
            }
        }
        
            $result = [
                'files' => $files,
                'unassigned_definitions' => $unassigned,
                'missing_definitions' => $missingDefinitions
            ];
            
            error_log("[DEBUG] getSoftSkillDefinitionStatus - Final result: " . print_r($result, true));
            return $result;
            
        } catch (Exception $e) {
            error_log("[ERROR] getSoftSkillDefinitionStatus - Exception: " . $e->getMessage());
            error_log("[ERROR] getSoftSkillDefinitionStatus - Stack trace: " . $e->getTraceAsString());
            
            return [
                'files' => [],
                'unassigned_definitions' => [],
                'missing_definitions' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Import soft skill competencies from JSON files stored on disk.
     *
     * @param int $categoryId  The soft skill category that will own the imported competencies
     * @param array $options   Supported keys:
     *                         - keys (array): optional list of competency keys to limit the import to.
     * @return array           Summary of the import work (imported, skipped, errors, created)
     * @throws Exception
     */
    public function importSoftSkillsFromJson(int $categoryId, array $options = []): array {
        $category = $this->getCategoryById($categoryId);
        if (!$category) {
            throw new Exception('Selected category not found');
        }

        $categoryType = $category['category_type'] ?? '';
        $moduleType = $category['module_type'] ?? '';
        $isSoftSkillCategory = $this->normalizeCategoryType($categoryType) === 'soft_skill'
            || $this->normalizeModuleType($moduleType) === 'soft_skill';

        if (!$isSoftSkillCategory) {
            throw new Exception('Selected category is not configured for soft skills');
        }

        if (!is_dir($this->softSkillLevelsDir)) {
            throw new Exception('Soft skill JSON directory not found');
        }

        $files = glob($this->softSkillLevelsDir . '/*.json');
        if (empty($files)) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['No soft skill JSON files were found in the catalog directory.'],
                'created' => []
            ];
        }

        $limitKeys = array_key_exists('keys', $options) && is_array($options['keys'])
            ? array_map(function ($key) {
                return $this->normalizeCompetencyKey((string)$key);
            }, $options['keys'])
            : null;

        // Build a lookup of currently active soft skill competency keys
        $existingKeys = [];
        $existingRecords = fetchAll(
            "SELECT c.competency_key
             FROM competencies c
             JOIN competency_categories cc ON c.category_id = cc.id
             WHERE c.is_active = 1
               AND c.competency_key IS NOT NULL
               AND c.competency_key != ''
               AND (cc.category_type = 'soft_skill' OR cc.module_type = 'soft_skill' OR c.level_type = 'soft_skill_scale')"
        );
        foreach ($existingRecords as $record) {
            $normalized = $this->normalizeCompetencyKey($record['competency_key']);
            if ($normalized !== '') {
                $existingKeys[$normalized] = true;
            }
        }

        $summary = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'created' => []
        ];

        foreach ($files as $file) {
            $key = basename($file, '.json');
            $normalizedKey = $this->normalizeCompetencyKey($key);
            if ($normalizedKey === '') {
                $summary['errors'][] = "Skipping file {$file}: cannot determine competency key.";
                $summary['skipped']++;
                continue;
            }

            if ($limitKeys !== null && !in_array($normalizedKey, $limitKeys, true)) {
                continue;
            }

            if (isset($existingKeys[$normalizedKey])) {
                $summary['skipped']++;
                continue;
            }

            $json = file_get_contents($file);
            $data = json_decode($this->sanitizeJsonPayload($json), true);
            if (!is_array($data)) {
                $summary['errors'][] = "Invalid JSON structure in {$file}.";
                $summary['skipped']++;
                continue;
            }

            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                $name = ucwords(str_replace('_', ' ', $normalizedKey));
            }

            $description = trim((string)($data['definition'] ?? ''));
            if ($description === '') {
                $description = trim((string)($data['description'] ?? ''));
            }
            if ($description === '') {
                $description = 'Imported from soft skill JSON catalog.';
            }

            try {
                $newId = $this->createCompetency([
                    'competency_name' => $name,
                    'description' => $description,
                    'category_id' => $categoryId,
                    'module_type' => 'soft_skill',
                    'competency_key' => $normalizedKey,
                    'symbol' => '🧠',
                    'max_level' => 4,
                    'level_type' => 'soft_skill_scale'
                ]);

                $summary['imported']++;
                $summary['created'][] = [
                    'id' => (int)$newId,
                    'competency_key' => $normalizedKey,
                    'name' => $name
                ];
                $existingKeys[$normalizedKey] = true;
            } catch (Exception $e) {
                $summary['errors'][] = "Failed to import {$normalizedKey}: " . $e->getMessage();
                $summary['skipped']++;
            }
        }

        if ($summary['imported'] > 0) {
            // Refresh caches and ensure relational soft skill tables are synchronized
            $this->softSkillCatalogCache = null;
            $this->getSoftSkillCatalog();
        }

        return $summary;
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
     * Expose the directory storing soft skill JSON files for UI/debugging purposes.
     * @return string
     */
    public function getSoftSkillDefinitionsRoot(): string {
        return $this->softSkillLevelsDir;
    }
    
    /**
     * Normalize competency key using the internal strategy so lookups stay consistent.
     * @param string|null $competencyKey
     * @return string
     */
    public function normalizeCompetencyKeyValue($competencyKey) {
        return $this->normalizeCompetencyKey($competencyKey);
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
            
            $definitionPath = $this->getSoftSkillDefinitionPath($competencyKey);
            $jsonSourcePath = file_exists($definitionPath)
                ? (realpath($definitionPath) ?: $definitionPath)
                : (($this->maintainLegacyAggregateFile && file_exists($this->softSkillLevelsPath))
                    ? (realpath($this->softSkillLevelsPath) ?: $this->softSkillLevelsPath)
                    : null);
            
            $definitionId = insertRecord(
                "INSERT INTO soft_skill_definitions (competency_key, name, definition, description, json_source_path)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), definition = VALUES(definition), description = VALUES(description), json_source_path = VALUES(json_source_path), updated_at = CURRENT_TIMESTAMP",
                [
                    $competencyKey,
                    $definition['name'] ?? ucwords(str_replace('_', ' ', $competencyKey)),
                    $definition['definition'] ?? '',
                    $definition['description'] ?? '',
                    $jsonSourcePath
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

    private function loadSoftSkillDefinitionsFromDirectory(): array {
        if (!is_dir($this->softSkillLevelsDir)) {
            return [];
        }
        
        // First get only active soft skill competencies
        $activeSoftSkills = fetchAll(
            "SELECT c.competency_key
             FROM competencies c
             JOIN competency_categories cc ON c.category_id = cc.id
             WHERE c.competency_key IS NOT NULL
               AND c.competency_key != ''
               AND c.is_active = 1
               AND (cc.category_type = 'soft_skill' OR cc.module_type = 'soft_skill' OR c.level_type = 'soft_skill_scale')"
        );
        
        $activeKeys = [];
        foreach ($activeSoftSkills as $skill) {
            $activeKeys[] = $this->normalizeCompetencyKey($skill['competency_key']);
        }
        
        $files = glob($this->softSkillLevelsDir . '/*.json');
        if (empty($files)) {
            return [];
        }
        
        natcasesort($files);
        $definitions = [];
        
        foreach ($files as $file) {
            $key = basename($file, '.json');
            $normalizedKey = $this->normalizeCompetencyKey($key);
            
            // Only load JSON files for active soft skill competencies
            if (!in_array($normalizedKey, $activeKeys)) {
                continue;
            }
            
            $json = file_get_contents($file);
            $data = json_decode($this->sanitizeJsonPayload($json), true);
            if (!is_array($data)) {
                continue;
            }
            
            $definitions[$key] = $data;
        }
        
        return $definitions;
    }
    
    private function loadLegacySoftSkillDefinitions(): array {
        if (!file_exists($this->softSkillLevelsPath)) {
            return [];
        }
        
        $jsonContent = file_get_contents($this->softSkillLevelsPath);
        $data = json_decode($this->sanitizeJsonPayload($jsonContent), true);
        
        if (isset($data['soft_skills']) && is_array($data['soft_skills'])) {
            return $data['soft_skills'];
        }
        
        return is_array($data) ? $data : [];
    }
    
    private function migrateLegacySoftSkillCatalog(array $legacy): void {
        $this->ensureSoftSkillDirectory(true);
        
        // Preserve legacy file but mark as migrated to avoid repeated work
        if (file_exists($this->softSkillLevelsPath)) {
            $backup = $this->softSkillLevelsPath . '.legacy';
            if (!file_exists($backup)) {
                @rename($this->softSkillLevelsPath, $backup);
            }
        }
        
        foreach ($legacy as $key => $definition) {
            $this->writeSoftSkillDefinitionFile($key, $definition);
        }
        
        if ($this->maintainLegacyAggregateFile) {
            $this->refreshSoftSkillAggregateFile();
        }
    }
    
    private function ensureSoftSkillDirectory(bool $create = false): void {
        if (is_dir($this->softSkillLevelsDir)) {
            return;
        }
        
        if ($create) {
            $this->ensureDirectoryExists($this->softSkillLevelsDir);
        }
    }
    
    private function getSoftSkillDefinitionPath(string $competencyKey): string {
        $key = $this->normalizeCompetencyKey($competencyKey);
        return $this->softSkillLevelsDir . '/' . $key . '.json';
    }
    
    private function writeSoftSkillDefinitionFile(string $competencyKey, array $definition): bool {
        $this->ensureSoftSkillDirectory(true);
        $key = $this->normalizeCompetencyKey($competencyKey);
        $path = $this->getSoftSkillDefinitionPath($key);

        $payload = [
            'name' => $definition['name'] ?? ucwords(str_replace('_', ' ', $key)),
            'definition' => $definition['definition'] ?? '',
            'description' => $definition['description'] ?? '',
            'levels' => $definition['levels'] ?? [
                '1' => ['title' => '', 'behaviors' => ['', '', '', '']],
                '2' => ['title' => '', 'behaviors' => ['', '', '', '']],
                '3' => ['title' => '', 'behaviors' => ['', '', '', '']],
                '4' => ['title' => '', 'behaviors' => ['', '', '', '']]
            ]
        ];

        // Ensure behaviors arrays contain exactly 4 entries for compatibility
        foreach ($payload['levels'] as $levelNumber => &$levelData) {
            if (!isset($levelData['behaviors']) || !is_array($levelData['behaviors'])) {
                $levelData['behaviors'] = ['', '', '', ''];
            } else {
                $levelData['behaviors'] = array_pad(array_slice($levelData['behaviors'], 0, 4), 4, '');
            }
        }
        unset($levelData);

        $jsonContent = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE) {
            return false;
        }

        return file_put_contents($path, $jsonContent) !== false;
    }
    
    private function refreshSoftSkillAggregateFile(): void {
        if (!$this->maintainLegacyAggregateFile) {
            return;
        }
        
        $definitions = $this->loadSoftSkillDefinitionsFromDirectory();
        if (empty($definitions)) {
            return;
        }
        
        $data = [
            'soft_skills' => $definitions,
            'level_mapping' => $this->getLevelMapping()
        ];
        
        $this->ensureDirectoryExists(dirname($this->softSkillLevelsPath));
        
        file_put_contents(
            $this->softSkillLevelsPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    private function normalizeCompetencyKey(?string $competencyKey): string {
        $key = $competencyKey ?? '';
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9]+/i', '_', $key);
        $key = trim($key, '_');
        if ($key === '') {
            $key = 'soft_skill_' . uniqid();
        }
        return $key;
    }
    
    private function sanitizeJsonPayload($payload): string {
        if (!is_string($payload)) {
            $payload = (string)$payload;
        }
        
        if ($payload === '') {
            return '';
        }
        
        if (substr($payload, 0, 3) === "\xEF\xBB\xBF") {
            $payload = substr($payload, 3);
        }
        
        $cleaned = preg_replace('/^[\x00-\x1F\x7F]+/u', '', $payload);
        if (is_string($cleaned)) {
            $payload = $cleaned;
        }
        
        return ltrim($payload);
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
    
    /**
     * Build normalized header map for CSV imports.
     * @param array $headerRow
     * @return array
     */
    private function buildHeaderMap(array $headerRow): array {
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
     * Map CSV columns to internal competency fields.
     * @param array $headerMap
     * @return array
     */
    private function mapCompetencyImportColumns(array $headerMap): array {
        $aliases = [
            'competency_name' => ['competency name', 'competency', 'name'],
            'description' => ['description', 'details', 'competency description'],
            'category' => ['category', 'category name', 'competency category'],
            'category_type' => ['category type', 'type', 'category_type'],
            'module_type' => ['module', 'module type', 'module_type'],
            'symbol' => ['symbol', 'icon'],
            'max_level' => ['max level', 'max_level', 'levels'],
            'level_type' => ['level type', 'level_type', 'scale'],
            'competency_key' => ['competency key', 'catalog key', 'competency_key']
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
     * Extract trimmed value from CSV field.
     */
    private function extractColumnValue(array $row, array $columnIndexes, string $field): string {
        if (!isset($columnIndexes[$field])) {
            return '';
        }
        
        $index = $columnIndexes[$field];
        return isset($row[$index]) ? trim((string)$row[$index]) : '';
    }
    
    /**
     * Extract integer value from CSV field.
     */
    private function extractNumericValue(array $row, array $columnIndexes, string $field): ?int {
        $value = $this->extractColumnValue($row, $columnIndexes, $field);
        if ($value === '') {
            return null;
        }
        
        $number = filter_var($value, FILTER_VALIDATE_INT);
        return $number !== false ? (int)$number : null;
    }
    
    /**
     * Determine if CSV row is empty.
     */
    private function isRowEmpty(array $row): bool {
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Normalize category type values.
     */
    private function normalizeCategoryType(?string $value): string {
        $normalized = strtolower(trim((string)$value));
        $softValues = [
            'soft',
            'soft skill',
            'soft skills',
            'soft_skill',
            'soft-skill',
            'softskill',
            'behavioral',
            'people',
            'culture'
        ];
        
        if (in_array($normalized, $softValues, true)) {
            return 'soft_skill';
        }
        
        return 'technical';
    }
    
    /**
     * Normalize module type values.
     */
    private function normalizeModuleType(?string $value): string {
        $normalized = strtolower(trim((string)$value));
        $softValues = [
            'soft',
            'soft skill',
            'soft skills',
            'soft_skill',
            'soft-skill',
            'softskill',
            'behavioral',
            'people',
            'culture',
            'leadership'
        ];
        
        if (in_array($normalized, $softValues, true)) {
            return 'soft_skill';
        }
        
        if (in_array($normalized, ['technical', 'tech'], true)) {
            return 'technical';
        }
        
        return 'technical';
    }
    
    /**
     * Normalize level type based on module.
     */
    private function normalizeLevelType(?string $value, string $moduleType = 'technical'): string {
        $normalized = strtolower(trim((string)$value));
        if (in_array($normalized, ['soft', 'soft skill', 'soft skills', 'soft_skill', 'soft_skill_scale', 'soft skill scale'], true)) {
            return 'soft_skill_scale';
        }
        if (in_array($normalized, ['technical', 'tech', 'technical_scale'], true)) {
            return 'technical_scale';
        }
        return $moduleType === 'soft_skill' ? 'soft_skill_scale' : 'technical_scale';
    }
    
    private function ensureDirectoryExists(string $directory): void {
        if ($directory === '' || is_dir($directory)) {
            return;
        }
        
        @mkdir($directory, 0775, true);
    }
    
    private function isDirectoryWritable(string $directory): bool {
        if ($directory === '') {
            return false;
        }
        
        if (is_dir($directory)) {
            return is_writable($directory);
        }
        
        $parent = dirname($directory);
        if (!$parent || $parent === $directory) {
            return false;
        }
        
        return is_dir($parent) && is_writable($parent);
    }
}
?>
