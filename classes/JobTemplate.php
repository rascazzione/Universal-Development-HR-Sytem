<?php
/**
 * Job Template Class
 * Handles job position templates and their components
 */

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
     * Get competencies assigned to job template
     * @param int $templateId
     * @return array
     */
    public function getJobTemplateCompetencies($templateId) {
        $sql = "SELECT jtc.*, c.competency_name, c.description, c.competency_type,
                       cc.category_name
                FROM job_template_competencies jtc
                JOIN competencies c ON jtc.competency_id = c.id
                LEFT JOIN competency_categories cc ON c.category_id = cc.id
                WHERE jtc.job_template_id = ? AND c.is_active = 1
                ORDER BY cc.category_name, c.competency_name";
        
        return fetchAll($sql, [$templateId]);
    }
    
    /**
     * Add competency to job template
     * @param int $templateId
     * @param int $competencyId
     * @param string $requiredLevel
     * @param float $weight
     * @return int
     */
    public function addCompetencyToTemplate($templateId, $competencyId, $requiredLevel, $weight = 100.00) {
        $sql = "INSERT INTO job_template_competencies (job_template_id, competency_id, required_level, weight_percentage) 
                VALUES (?, ?, ?, ?)";
        
        return insertRecord($sql, [$templateId, $competencyId, $requiredLevel, $weight]);
    }
    
    /**
     * Remove competency from job template
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
        $template['competencies'] = $this->getJobTemplateCompetencies($templateId);
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
        
        // Clone competencies
        foreach ($originalTemplate['competencies'] as $competency) {
            $this->addCompetencyToTemplate($newTemplateId, $competency['competency_id'], $competency['required_level'], $competency['weight_percentage']);
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
}
?>