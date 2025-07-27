<?php
/**
 * Data Migration Script: Convert Old Evaluation Data to Growth Evidence System
 * This script safely migrates existing evaluation data to the new evidence-based format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Evaluation.php';

echo "🔄 Starting data migration from old evaluation system to Growth Evidence System...\n";

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Step 1: Check if old tables exist
    $tables = ['evaluation_kpi_results', 'evaluation_competency_results', 
               'evaluation_responsibility_results', 'evaluation_value_results'];
    
    $existingTables = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $existingTables[] = $table;
        }
    }
    
    if (empty($existingTables)) {
        echo "✅ No old evaluation data found. Migration not needed.\n";
        $pdo->commit();
        exit(0);
    }
    
    echo "📊 Found existing evaluation data in: " . implode(', ', $existingTables) . "\n";
    
    // Step 2: Get all evaluations with their managers
    $evaluationsStmt = $pdo->query("
        SELECT e.evaluation_id, e.employee_id, e.manager_id, e.created_at, e.overall_rating
        FROM evaluations e
        WHERE e.manager_id IS NOT NULL
    ");
    
    $evaluations = $evaluationsStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalEvaluations = count($evaluations);
    echo "📋 Processing $totalEvaluations evaluations...\n";
    
    $migratedEntries = 0;
    
    // Step 3: Migrate KPI results to evidence entries
    if (in_array('evaluation_kpi_results', $existingTables)) {
        $kpiStmt = $pdo->query("
            SELECT 
                e.evaluation_id,
                e.employee_id,
                e.manager_id,
                k.kpi_name,
                r.score,
                r.comments,
                r.achieved_value,
                r.target_value,
                e.created_at
            FROM evaluations e
            JOIN evaluation_kpi_results r ON e.evaluation_id = r.evaluation_id
            JOIN company_kpis k ON r.kpi_id = k.id
            WHERE e.manager_id IS NOT NULL
        ");
        
        $kpiResults = $kpiStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($kpiResults as $result) {
            $content = "KPI: {$result['kpi_name']}\n";
            $content .= "Target: {$result['target_value']}, Achieved: {$result['achieved_value']}\n";
            $content .= "Comments: {$result['comments']}";
            
            $starRating = max(1, min(5, round($result['score'])));
            
            $insertStmt = $pdo->prepare("
                INSERT INTO growth_evidence_entries 
                (employee_id, manager_id, content, star_rating, dimension, entry_date, created_at)
                VALUES (?, ?, ?, ?, 'kpis', ?, ?)
            ");
            
            $insertStmt->execute([
                $result['employee_id'],
                $result['manager_id'],
                $content,
                $starRating,
                date('Y-m-d', strtotime($result['created_at'])),
                $result['created_at']
            ]);
            
            $migratedEntries++;
        }
    }
    
    // Step 4: Migrate competency results
    if (in_array('evaluation_competency_results', $existingTables)) {
        $competencyStmt = $pdo->query("
            SELECT 
                e.evaluation_id,
                e.employee_id,
                e.manager_id,
                c.competency_name,
                r.score,
                r.comments,
                r.required_level,
                r.achieved_level,
                e.created_at
            FROM evaluations e
            JOIN evaluation_competency_results r ON e.evaluation_id = r.evaluation_id
            JOIN competencies c ON r.competency_id = c.id
            WHERE e.manager_id IS NOT NULL
        ");
        
        $competencyResults = $competencyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($competencyResults as $result) {
            $content = "Competency: {$result['competency_name']}\n";
            $content .= "Required: {$result['required_level']}, Achieved: {$result['achieved_level']}\n";
            $content .= "Comments: {$result['comments']}";
            
            $starRating = max(1, min(5, round($result['score'])));
            
            $insertStmt = $pdo->prepare("
                INSERT INTO growth_evidence_entries 
                (employee_id, manager_id, content, star_rating, dimension, entry_date, created_at)
                VALUES (?, ?, ?, ?, 'competencies', ?, ?)
            ");
            
            $insertStmt->execute([
                $result['employee_id'],
                $result['manager_id'],
                $content,
                $starRating,
                date('Y-m-d', strtotime($result['created_at'])),
                $result['created_at']
            ]);
            
            $migratedEntries++;
        }
    }
    
    // Step 5: Migrate responsibility results
    if (in_array('evaluation_responsibility_results', $existingTables)) {
        $responsibilityStmt = $pdo->query("
            SELECT 
                e.evaluation_id,
                e.employee_id,
                e.manager_id,
                r.responsibility_text,
                r.score,
                r.comments,
                e.created_at
            FROM evaluations e
            JOIN evaluation_responsibility_results r ON e.evaluation_id = r.evaluation_id
            JOIN job_template_responsibilities jtr ON r.responsibility_id = jtr.id
            WHERE e.manager_id IS NOT NULL
        ");
        
        $responsibilityResults = $responsibilityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($responsibilityResults as $result) {
            $content = "Responsibility: {$result['responsibility_text']}\n";
            $content .= "Comments: {$result['comments']}";
            
            $starRating = max(1, min(5, round($result['score'])));
            
            $insertStmt = $pdo->prepare("
                INSERT INTO growth_evidence_entries 
                (employee_id, manager_id, content, star_rating, dimension, entry_date, created_at)
                VALUES (?, ?, ?, ?, 'responsibilities', ?, ?)
            ");
            
            $insertStmt->execute([
                $result['employee_id'],
                $result['manager_id'],
                $content,
                $starRating,
                date('Y-m-d', strtotime($result['created_at'])),
                $result['created_at']
            ]);
            
            $migratedEntries++;
        }
    }
    
    // Step 6: Migrate value results
    if (in_array('evaluation_value_results', $existingTables)) {
        $valueStmt = $pdo->query("
            SELECT 
                e.evaluation_id,
                e.employee_id,
                e.manager_id,
                v.value_name,
                r.score,
                r.comments,
                e.created_at
            FROM evaluations e
            JOIN evaluation_value_results r ON e.evaluation_id = r.evaluation_id
            JOIN company_values v ON r.value_id = v.id
            WHERE e.manager_id IS NOT NULL
        ");
        
        $valueResults = $valueStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($valueResults as $result) {
            $content = "Company Value: {$result['value_name']}\n";
            $content .= "Comments: {$result['comments']}";
            
            $starRating = max(1, min(5, round($result['score'])));
            
            $insertStmt = $pdo->prepare("
                INSERT INTO growth_evidence_entries 
                (employee_id, manager_id, content, star_rating, dimension, entry_date, created_at)
                VALUES (?, ?, ?, ?, 'values', ?, ?)
            ");
            
            $insertStmt->execute([
                $result['employee_id'],
                $result['manager_id'],
                $content,
                $starRating,
                date('Y-m-d', strtotime($result['created_at'])),
                $result['created_at']
            ]);
            
            $migratedEntries++;
        }
    }
    
    // Step 7: Update evaluations with evidence-based fields
    $updateStmt = $pdo->prepare("
        UPDATE evaluations 
        SET evidence_summary = 'Migrated from historical evaluation data',
            evidence_rating = ?
        WHERE evaluation_id = ?
    ");
    
    foreach ($evaluations as $evaluation) {
        $updateStmt->execute([
            $evaluation['overall_rating'],
            $evaluation['evaluation_id']
        ]);
    }
    
    $pdo->commit();
    
    echo "✅ Migration completed successfully!\n";
    echo "📊 Migrated $migratedEntries evidence entries from historical data\n";
    echo "🎯 Updated $totalEvaluations evaluations with evidence-based fields\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
