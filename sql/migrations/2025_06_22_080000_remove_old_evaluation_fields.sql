-- Migration: Remove old evaluation fields
-- Created: 2025-06-22 08:00:00

-- Drop old JSON-based fields from evaluations table
ALTER TABLE evaluations
    DROP COLUMN expected_results,
    DROP COLUMN expected_results_score,
    DROP COLUMN expected_results_weight,
    DROP COLUMN skills_competencies,
    DROP COLUMN skills_competencies_score,
    DROP COLUMN skills_competencies_weight,
    DROP COLUMN key_responsibilities,
    DROP COLUMN key_responsibilities_score,
    DROP COLUMN key_responsibilities_weight,
    DROP COLUMN living_values,
    DROP COLUMN living_values_score,
    DROP COLUMN living_values_weight;

SELECT 'Migration remove_old_evaluation_fields completed successfully' as result;
