-- Fix: Add missing weight_percentage and updated_at columns to evaluation result tables
-- This fixes the "Legacy Evaluation Format" issue by ensuring template initialization works

-- Add missing columns to evaluation_kpi_results
ALTER TABLE evaluation_kpi_results 
ADD COLUMN weight_percentage DECIMAL(5,2) DEFAULT 100.00 AFTER comments,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER weight_percentage;

-- Add missing columns to evaluation_competency_results  
ALTER TABLE evaluation_competency_results 
ADD COLUMN weight_percentage DECIMAL(5,2) DEFAULT 100.00 AFTER comments,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER weight_percentage;

-- Add missing columns to evaluation_responsibility_results
ALTER TABLE evaluation_responsibility_results 
ADD COLUMN weight_percentage DECIMAL(5,2) DEFAULT 100.00 AFTER comments,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER weight_percentage;

-- Add missing columns to evaluation_value_results
ALTER TABLE evaluation_value_results 
ADD COLUMN weight_percentage DECIMAL(5,2) DEFAULT 100.00 AFTER comments,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER weight_percentage;

SELECT 'Missing weight_percentage and updated_at columns added successfully' as result;