-- Migration: Add performance tracking tables
-- Created: 2025-06-22 07:49:41

USE performance_evaluation;

-- Start transaction for safety
START TRANSACTION;

-- Add your migration SQL here

-- Commit the transaction
COMMIT;

SELECT 'Migration Add performance tracking tables completed successfully' as result;
