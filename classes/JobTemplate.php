<?php
/**
 * Job Template Class
 * Handles job position templates and their components
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Competency.php';

class JobTemplate {
    
    /**
     * Get all job templates
     * @return array
     */
    public function getJobTemplates() {
        $sql = "SELECT jpt.*,
                       u.username as created_by_username,
                       0 as employee_count
                FROM job_position_templates jpt
                LEFT JOIN users u ON jpt.created_by = u.user_id
                WHERE jpt.is_active = 1
                ORDER BY jpt.position_title";
        
        return fetchAll($sql);
    }
    
    /**
     * Get job template by ID
     * @param int $id
     * @return array|false
     */
    public function getJobTemplateById($id) {
        $sql = "SELECT jpt.*,
                       u.username as created_by_username
                FROM job_position_templates jpt
                LEFT JOIN users u ON jpt.created_by = u.user_id
                WHERE jpt.id = ? AND jpt.is_active = 1";
        
        return fetchOne($sql, [$id]);
    }

    /**
     * Get a lightweight summary of a job template with component counts
     * @param int $id
     * @return array|false
     */
    public function getJobTemplateSummary($id) {
        $sql = "SELECT jpt.*,
                       (SELECT COUNT(*) FROM job_template_kpis WHERE job_template_id = ?) AS kpi_count,
                       (SELECT COUNT(*) FROM job_template_competencies WHERE job_template_id = ?) AS competency_count,
                       (SELECT COUNT(*) FROM job_template_competencies WHERE job_template_id = ? AND module_type = 'technical') AS technical_skill_count,
                       (SELECT COUNT(*) FROM job_template_competencies WHERE job_template_id = ? AND module_type = 'soft_skill') AS soft_skill_count,
                       (SELECT COUNT(*) FROM job_template_responsibilities WHERE job_template_id = ?) AS responsibility_count,
                       (SELECT COUNT(*) FROM job_template_values WHERE job_template_id = ?) AS value_count
                FROM job_position_templates jpt
                WHERE jpt.id = ? AND jpt.is_active = 1";
        
        return fetchOne($sql, [$id, $id, $id, $id, $id, $id, $id]);
    }
    
    /**
     * Create new job template
     * @param array $data
     * @return int
     */
    public function createJobTemplate($data) {
        $sql = "INSERT INTO job_position_templates (position_title, department, description, created_by) 
                VALUES (?, ?, ?, ?)";
        
        return insertRecord($sql, [
            $data['position_title'],
            $data['department'],
            $data['description'],
            $data['created_by']
        ]);
    }
    
    /**
     * Update job template
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateJobTemplate($id, $data) {
        $sql = "UPDATE job_position_templates 
                SET position_title = ?, department = ?, description = ?
                WHERE id = ?";
        
        return updateRecord($sql, [
            $data['position_title'],
            $data['department'],
            $data['description'],
            $id
        ]);
    }
    
    /**
     * Delete job template
     * @param int $id
     * @return int
     */
    public function deleteJobTemplate($id) {
        $sql = "UPDATE job_position_templates SET is_active = 0 WHERE id = ?";
        return updateRecord($sql, [$id]);
    }
    
    /**
     * Get KPIs assigned to job template
     * @param int $templateId
     * @return array
     */
    public function getJobTemplateKPIs($templateId) {
        $sql = "SELECT jtk.*, ck.kpi_name, ck.kpi_description, ck.measurement_unit, ck.category
                FROM job_template_kpis jtk
                JOIN company_kpis ck ON jtk.kpi_id = ck.id
                WHERE jtk.job_template_id = ? AND ck.is_active = 1
                ORDER BY ck.category, ck.kpi_name";
        
        return fetchAll($sql, [$templateId]);
    }
    
    /**
     * Add KPI to job template
     * @param int $templateId
     * @param int $kpiId
     * @param float $targetValue
     * @param float $weight
     * @return int
     */
    public function addKPIToTemplate($templateId, $kpiId, $targetValue, $weight = 100.00) {
        $sql = "INSERT INTO job_template_kpis (job_template_id, kpi_id, target_value, weight_percentage) 
                VALUES (?, ?, ?, ?)";
        
        return insertRecord($sql, [$templateId, $kpiId, $targetValue, $weight]);
    }
    
    /**
     * Remove KPI from job template
     * @param int $templateId
     * @param int $kpiId
     * @return int
     */
    public function removeKPIFromTemplate($templateId, $kpiId) {
        $sql = "DELETE FROM job_template_kpis WHERE job_template_id = ? AND kpi_id = ?";
        return updateRecord($sql, [$templateId, $kpiId]);
    }
    
    /**
     * Retrieve all skills (technical + soft) assigned to a template
     * @param int $templateId
     * @return array{technical: array, soft_skill: array, all: array}
     */
    public function getTemplateSkills($templateId) {
        if (!$templateId) {
            return ['technical' => [], 'soft_skill' => [], 'all' => []];
        }
        
        // Ensure soft skill definitions are synchronized for joins
        $competencyClass = new Competency();
        $competencyClass->getSoftSkillCatalog();
        
        $sql = "SELECT * FROM view_job_template_competencies 
                WHERE job_template_id = ?
                ORDER BY module_type, category_name, competency_name";
        $rows = fetchAll($sql, [$templateId]);
        
        $result = ['technical' => [], 'soft_skill' => [], 'all' => []];
        foreach ($rows as $row) {
            $normalized = $this->normalizeSkillRow($row);
            $result['all'][] = $normalized;
            if ($normalized['module_type'] === 'technical') {
                $result['technical'][] = $normalized;
            } else {
                $result['soft_skill'][] = $normalized;
            }
        }
        
        return $result;
    }
    
    /**
     * Legacy compatibility: returns a flat list of skills
     * @param int $templateId
     * @return array
     */
    public function getJobTemplateCompetencies($templateId) {
        $skills = $this->getTemplateSkills($templateId);
        return $skills['all'];
    }
    
    /**
     * Retrieve only technical skills for a template
     * @param int $templateId
     * @return array
     */
    public function getTemplateTechnicalSkills($templateId) {
        $skills = $this->getTemplateSkills($templateId);
        return $skills['technical'];
    }
    
    /**
     * Retrieve only soft skills for a template
     * @param int $templateId
     * @return array
     */
    public function getTemplateSoftSkills($templateId) {
        $skills = $this->getTemplateSkills($templateId);
        return $skills['soft_skill'];
    }
    
    /**
     * Add or update a technical skill assignment
     * @param int $templateId
     * @param int $competencyId
     * @param int $technicalLevelId
     * @param float $weight
     * @return int
     */
    public function addTechnicalSkillToTemplate($templateId, $competencyId, $technicalLevelId, $weight = 100.00) {
        if (!$technicalLevelId) {
            throw new InvalidArgumentException('Technical level is required.');
        }
        
        $existing = fetchOne(
            "SELECT id FROM job_template_competencies WHERE job_template_id = ? AND competency_id = ? AND module_type = 'technical'",
            [$templateId, $competencyId]
        );
        
        if ($existing) {
            $sql = "UPDATE job_template_competencies
                    SET technical_level_id = ?, weight_percentage = ?
                    WHERE id = ?";
            return updateRecord($sql, [$technicalLevelId, $weight, $existing['id']]);
        }
        
        $sql = "INSERT INTO job_template_competencies (job_template_id, competency_id, technical_level_id, weight_percentage, module_type)
                VALUES (?, ?, ?, ?, 'technical')";
        return insertRecord($sql, [$templateId, $competencyId, $technicalLevelId, $weight]);
    }
    
    /**
     * Add or update a soft skill assignment
     * @param int $templateId
     * @param int $competencyId
     * @param string $competencyKey
     * @param int $softSkillLevel
     * @param float $weight
     * @return int
     */
    public function addSoftSkillToTemplate($templateId, $competencyId, $competencyKey, $softSkillLevel, $weight = 100.00) {
        if (!$competencyKey) {
            $competency = (new Competency())->getCompetencyById($competencyId);
            $competencyKey = $competency['competency_key'] ?? null;
        }
        
        if (!$competencyKey) {
            throw new InvalidArgumentException('Soft skill competency key is required.');
        }
        
        $existing = fetchOne(
            "SELECT id FROM job_template_competencies WHERE job_template_id = ? AND competency_id = ? AND module_type = 'soft_skill'",
            [$templateId, $competencyId]
        );
        
        if ($existing) {
            $sql = "UPDATE job_template_competencies
                    SET soft_skill_level = ?, competency_key = ?, weight_percentage = ?
                    WHERE id = ?";
            return updateRecord($sql, [$softSkillLevel, $competencyKey, $weight, $existing['id']]);
        }
        
        $sql = "INSERT INTO job_template_competencies (job_template_id, competency_id, soft_skill_level, competency_key, weight_percentage, module_type)
                VALUES (?, ?, ?, ?, ?, 'soft_skill')";
        return insertRecord($sql, [$templateId, $competencyId, $softSkillLevel, $competencyKey, $weight]);
    }
    
    /**
     * Backwards-compatible method for adding competencies
     * Accepts either legacy string level or new configuration array
     * @param int $templateId
     * @param int $competencyId
     * @param mixed $config
     * @param float $weight
     * @return int
     */
    public function addCompetencyToTemplate($templateId, $competencyId, $config, $weight = 100.00) {
        if (!is_array($config)) {
            $technicalLevelId = $this->mapLegacyRequiredLevel($config);
            return $this->addTechnicalSkillToTemplate($templateId, $competencyId, $technicalLevelId, $weight);
        }
        
        $moduleType = $config['module_type'] ?? 'technical';
        if ($moduleType === 'soft_skill') {
            $level = $config['soft_skill_level'] ?? null;
            $competencyKey = $config['competency_key'] ?? null;
            $weightValue = $config['weight_percentage'] ?? $weight;
            if ($level === null) {
                throw new InvalidArgumentException('Soft skill level is required.');
            }
            return $this->addSoftSkillToTemplate($templateId, $competencyId, $competencyKey, (int)$level, $weightValue);
        }
        
        $technicalLevelId = $config['technical_level_id'] ?? null;
        if (!$technicalLevelId && isset($config['display_level'])) {
            $technicalLevelId = $this->getTechnicalLevelIdByDisplay((int)$config['display_level']);
        }
        if (!$technicalLevelId) {
            throw new InvalidArgumentException('Technical level ID is required for technical skills.');
        }
        $weightValue = $config['weight_percentage'] ?? $weight;
        return $this->addTechnicalSkillToTemplate($templateId, $competencyId, $technicalLevelId, $weightValue);
    }
    
    /**
     * Remove a specific skill assignment by row ID
     * @param int $assignmentId
     * @return int
     */
    public function removeSkillFromTemplate($assignmentId) {
        $sql = "DELETE FROM job_template_competencies WHERE id = ?";
        return updateRecord($sql, [$assignmentId]);
    }
    
    /**
     * Legacy removal method by competency reference
     * @param int $templateId
     * @param int $competencyId
     * @return int
     */
    public function removeCompetencyFromTemplate($templateId, $competencyId) {
        $sql = "DELETE FROM job_template_competencies WHERE job_template_id = ? AND competency_id = ?";
        return updateRecord($sql, [$templateId, $competencyId]);
    }
    
    /**
     * Get responsibilities assigned to job template
     * @param int $templateId
     * @return array
     */
    public function getJobTemplateResponsibilities($templateId) {
        $sql = "SELECT * FROM job_template_responsibilities 
                WHERE job_template_id = ? 
                ORDER BY sort_order, id";
        
        return fetchAll($sql, [$templateId]);
    }
    
    /**
     * Add responsibility to job template
     * @param int $templateId
     * @param string $responsibilityText
     * @param int $sortOrder
     * @param float $weight
     * @return int
     */
    public function addResponsibilityToTemplate($templateId, $responsibilityText, $sortOrder = 0, $weight = 100.00) {
        $sql = "INSERT INTO job_template_responsibilities (job_template_id, responsibility_text, sort_order, weight_percentage) 
                VALUES (?, ?, ?, ?)";
        
        return insertRecord($sql, [$templateId, $responsibilityText, $sortOrder, $weight]);
    }
    
    /**
     * Update responsibility
     * @param int $responsibilityId
     * @param string $responsibilityText
     * @param int $sortOrder
     * @param float $weight
     * @return int
     */
    public function updateResponsibility($responsibilityId, $responsibilityText, $sortOrder = 0, $weight = 100.00) {
        $sql = "UPDATE job_template_responsibilities 
                SET responsibility_text = ?, sort_order = ?, weight_percentage = ?
                WHERE id = ?";
        
        return updateRecord($sql, [$responsibilityText, $sortOrder, $weight, $responsibilityId]);
    }
    
    /**
     * Remove responsibility from job template
     * @param int $responsibilityId
     * @return int
     */
    public function removeResponsibility($responsibilityId) {
        $sql = "DELETE FROM job_template_responsibilities WHERE id = ?";
        return updateRecord($sql, [$responsibilityId]);
    }
    
    /**
     * Get company values assigned to job template
     * @param int $templateId
     * @return array
     */
    public function getJobTemplateValues($templateId) {
        $sql = "SELECT jtv.*, cv.value_name, cv.description
                FROM job_template_values jtv
                JOIN company_values cv ON jtv.value_id = cv.id
                WHERE jtv.job_template_id = ? AND cv.is_active = 1
                ORDER BY cv.sort_order, cv.value_name";
        
        return fetchAll($sql, [$templateId]);
    }
    
    /**
     * Add company value to job template
     * @param int $templateId
     * @param int $valueId
     * @param float $weight
     * @return int
     */
    public function addValueToTemplate($templateId, $valueId, $weight = 100.00) {
        $sql = "INSERT INTO job_template_values (job_template_id, value_id, weight_percentage) 
                VALUES (?, ?, ?)";
        
        return insertRecord($sql, [$templateId, $valueId, $weight]);
    }
    
    /**
     * Remove company value from job template
     * @param int $templateId
     * @param int $valueId
     * @return int
     */
    public function removeValueFromTemplate($templateId, $valueId) {
        $sql = "DELETE FROM job_template_values WHERE job_template_id = ? AND value_id = ?";
        return updateRecord($sql, [$templateId, $valueId]);
    }
    
    /**
     * Get template KPIs
     * @param int $templateId
     * @return array
     */
    public function getTemplateKPIs($templateId) {
        $sql = "SELECT tk.*, k.kpi_name, k.kpi_description, k.measurement_unit, k.category
                FROM job_template_kpis tk
                JOIN company_kpis k ON tk.kpi_id = k.id
                WHERE tk.job_template_id = ? AND k.is_active = 1
                ORDER BY k.category, k.kpi_name";
        return fetchAll($sql, [$templateId]);
    }

    /**
     * Get template competencies
     * @param int $templateId
     * @return array
     */
    public function getTemplateCompetencies($templateId) {
        $sql = "SELECT tc.*, c.competency_name, c.description, c.competency_type,
                       cc.category_name
                FROM job_template_competencies tc
                JOIN competencies c ON tc.competency_id = c.id
                LEFT JOIN competency_categories cc ON c.category_id = cc.id
                WHERE tc.job_template_id = ? AND c.is_active = 1
                ORDER BY cc.category_name, c.competency_name";
        return fetchAll($sql, [$templateId]);
    }

    /**
     * Get template responsibilities
     * @param int $templateId
     * @return array
     */
    public function getTemplateResponsibilities($templateId) {
        $sql = "SELECT * FROM job_template_responsibilities
                WHERE job_template_id = ?
                ORDER BY sort_order, id";
        return fetchAll($sql, [$templateId]);
    }

    /**
     * Get template values
     * @param int $templateId
     * @return array
     */
    public function getTemplateValues($templateId) {
        $sql = "SELECT tv.*, v.value_name, v.description
                FROM job_template_values tv
                JOIN company_values v ON tv.value_id = v.id
                WHERE tv.job_template_id = ? AND v.is_active = 1
                ORDER BY v.sort_order, v.value_name";
        return fetchAll($sql, [$templateId]);
    }

    /**
     * Get complete job template with all components
     * @param int $templateId
     * @return array
     */
    public function getCompleteJobTemplate($templateId) {
        $template = $this->getJobTemplateById($templateId);
        if (!$template) {
            return false;
        }
        
        $template['kpis'] = $this->getJobTemplateKPIs($templateId);
        $skills = $this->getTemplateSkills($templateId);
        $template['technical_skills'] = $skills['technical'];
        $template['soft_skills'] = $skills['soft_skill'];
        $template['competencies'] = $skills['all'];
        $template['responsibilities'] = $this->getJobTemplateResponsibilities($templateId);
        $template['values'] = $this->getJobTemplateValues($templateId);
        
        return $template;
    }
    
    /**
     * Clone job template
     * @param int $templateId
     * @param string $newTitle
     * @param int $createdBy
     * @return int
     */
    public function cloneJobTemplate($templateId, $newTitle, $createdBy) {
        $originalTemplate = $this->getCompleteJobTemplate($templateId);
        if (!$originalTemplate) {
            return false;
        }
        
        // Create new template
        $newTemplateId = $this->createJobTemplate([
            'position_title' => $newTitle,
            'department' => $originalTemplate['department'],
            'description' => $originalTemplate['description'],
            'created_by' => $createdBy
        ]);
        
        // Clone KPIs
        foreach ($originalTemplate['kpis'] as $kpi) {
            $this->addKPIToTemplate($newTemplateId, $kpi['kpi_id'], $kpi['target_value'], $kpi['weight_percentage']);
        }
        
        // Clone technical skills
        foreach ($originalTemplate['technical_skills'] as $technicalSkill) {
            $this->addTechnicalSkillToTemplate(
                $newTemplateId,
                $technicalSkill['competency_id'],
                $technicalSkill['technical_level_id'],
                $technicalSkill['weight_percentage']
            );
        }
        
        // Clone soft skills
        foreach ($originalTemplate['soft_skills'] as $softSkill) {
            $this->addSoftSkillToTemplate(
                $newTemplateId,
                $softSkill['competency_id'],
                $softSkill['competency_key'],
                $softSkill['soft_skill_level'],
                $softSkill['weight_percentage']
            );
        }
        
        // Clone responsibilities
        foreach ($originalTemplate['responsibilities'] as $responsibility) {
            $this->addResponsibilityToTemplate($newTemplateId, $responsibility['responsibility_text'], $responsibility['sort_order'], $responsibility['weight_percentage']);
        }
        
        // Clone values
        foreach ($originalTemplate['values'] as $value) {
            $this->addValueToTemplate($newTemplateId, $value['value_id'], $value['weight_percentage']);
        }
        
        return $newTemplateId;
    }
    
    /**
     * Normalize skill row from view into consistent structure
     * @param array $row
     * @return array
     */
    private function normalizeSkillRow(array $row): array {
        $moduleType = $row['module_type'] ?? 'technical';
        $competencyName = $row['competency_name'] ?? '';
        if ($moduleType === 'soft_skill' && !empty($row['soft_skill_name'])) {
            $competencyName = $row['soft_skill_name'];
        }
        
        $competencyKey = $row['competency_key'] ?? $row['competency_catalog_key'] ?? null;
        
        $normalized = [
            'id' => isset($row['id']) ? (int)$row['id'] : null,
            'job_template_id' => isset($row['job_template_id']) ? (int)$row['job_template_id'] : null,
            'competency_id' => isset($row['competency_id']) ? (int)$row['competency_id'] : null,
            'competency_key' => $competencyKey,
            'competency_name' => $competencyName,
            'category_name' => $row['category_name'] ?? 'Uncategorized',
            'module_type' => $moduleType,
            'competency_type' => $moduleType === 'soft_skill' ? 'soft_skill' : 'technical',
            'weight_percentage' => isset($row['weight_percentage']) ? (float)$row['weight_percentage'] : 0.0
        ];
        
        if ($moduleType === 'technical') {
            $displayLevel = isset($row['technical_display_level']) ? (int)$row['technical_display_level'] : 0;
            $normalized = array_merge($normalized, [
                'technical_level_id' => isset($row['technical_level_id']) ? (int)$row['technical_level_id'] : null,
                'technical_level_value' => $row['technical_level_value'] ?? null,
                'technical_display_level' => $displayLevel ?: null,
                'technical_level_name' => $row['technical_level_name'] ?? null,
                'technical_level_description' => $row['technical_level_description'] ?? null,
                'technical_symbol_pattern' => $row['technical_symbol_pattern'] ?? $this->buildTechnicalSymbolPattern($displayLevel),
                'required_level' => $row['technical_level_name'] ?? ($displayLevel ? 'Level ' . $displayLevel : null)
            ]);
        } else {
            $level = isset($row['soft_skill_level']) ? (int)$row['soft_skill_level'] : 0;
            $behaviors = [];
            if (!empty($row['soft_skill_behaviors'])) {
                $decoded = json_decode($row['soft_skill_behaviors'], true);
                if (is_array($decoded)) {
                    $behaviors = $decoded;
                }
            }
            
            $normalized = array_merge($normalized, [
                'soft_skill_level' => $level ?: null,
                'soft_skill_level_title' => $row['soft_skill_level_title'] ?? null,
                'soft_skill_symbol_pattern' => $row['soft_skill_symbol_pattern'] ?? $this->buildSoftSkillSymbolPattern($level),
                'soft_skill_meaning' => $row['soft_skill_meaning'] ?? null,
                'soft_skill_behaviors' => $behaviors,
                'soft_skill_definition' => $row['soft_skill_definition'] ?? null,
                'soft_skill_description' => $row['soft_skill_description'] ?? null,
                'required_level' => $row['soft_skill_level_title'] ?? ($level ? 'Level ' . $level : null)
            ]);
        }
        
        return $normalized;
    }
    
    /**
     * Build symbol pattern for technical skill levels
     * @param int $displayLevel
     * @return string
     */
    private function buildTechnicalSymbolPattern(int $displayLevel): string {
        $filled = max(0, min($displayLevel, 5));
        return str_repeat('🧩', $filled) . str_repeat('⚪️', max(0, 5 - $filled));
    }
    
    /**
     * Build symbol pattern for soft skill levels
     * @param int $level
     * @return string
     */
    private function buildSoftSkillSymbolPattern(int $level): string {
        $filled = max(0, min($level, 4));
        return str_repeat('🧠', $filled) . str_repeat('⚪️', max(0, 4 - $filled));
    }
    
    /**
     * Get technical level ID by display position
     * @param int $displayLevel
     * @return int|null
     */
    private function getTechnicalLevelIdByDisplay(int $displayLevel): ?int {
        $record = fetchOne("SELECT id FROM technical_skill_levels WHERE display_level = ? LIMIT 1", [$displayLevel]);
        if ($record) {
            return (int)$record['id'];
        }
        
        $fallback = fetchOne("SELECT id FROM technical_skill_levels ORDER BY display_level LIMIT 1");
        return $fallback ? (int)$fallback['id'] : null;
    }
    
    /**
     * Map legacy required level string to technical level ID
     * @param string|null $legacyLevel
     * @return int|null
     */
    private function mapLegacyRequiredLevel($legacyLevel): ?int {
        if (!$legacyLevel) {
            return $this->getTechnicalLevelIdByDisplay(1);
        }
        
        $legacyLevel = strtolower($legacyLevel);
        $displayMap = [
            'basic' => 1,
            'intermediate' => 2,
            'advanced' => 3,
            'expert' => 5
        ];
        
        $displayLevel = $displayMap[$legacyLevel] ?? 3;
        return $this->getTechnicalLevelIdByDisplay($displayLevel);
    }
}
?>
