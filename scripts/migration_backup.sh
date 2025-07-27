#!/bin/bash
# Growth Evidence System Migration Backup Script
# Creates comprehensive backups before migration

set -e

echo "🔒 Creating comprehensive backup before Growth Evidence System migration..."
echo "Timestamp: $(date)"

# Create backup directory
BACKUP_DIR="backups/migration_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Database connection details
DB_NAME="performance_evaluation"
DB_USER="root"
DB_PASS="rootpassword"

echo "📊 Backing up critical evaluation data..."

# Backup evaluations table
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" evaluations > "$BACKUP_DIR/evaluations_backup.sql"

# Backup evaluation result tables
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    evaluation_kpi_results \
    evaluation_competency_results \
    evaluation_responsibility_results \
    evaluation_value_results \
    evaluation_section_weights > "$BACKUP_DIR/evaluation_results_backup.sql"

# Backup job template data
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    job_position_templates \
    company_kpis \
    competencies \
    company_values \
    job_template_kpis \
    job_template_competencies \
    job_template_responsibilities \
    job_template_values > "$BACKUP_DIR/job_templates_backup.sql"

# Create summary report
echo "📋 Creating migration summary..."
cat > "$BACKUP_DIR/migration_summary.txt" << EOF
Growth Evidence System Migration Backup
======================================
Date: $(date)
Backup Directory: $BACKUP_DIR

Tables Backed Up:
- evaluations (all records)
- evaluation_kpi_results
- evaluation_competency_results  
- evaluation_responsibility_results
- evaluation_value_results
- evaluation_section_weights
- All job template related tables

Record Counts:
$(mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 'evaluations' as table_name, COUNT(*) as records FROM evaluations
UNION ALL
SELECT 'evaluation_kpi_results', COUNT(*) FROM evaluation_kpi_results
UNION ALL
SELECT 'evaluation_competency_results', COUNT(*) FROM evaluation_competency_results
UNION ALL
SELECT 'evaluation_responsibility_results', COUNT(*) FROM evaluation_responsibility_results
UNION ALL
SELECT 'evaluation_value_results', COUNT(*) FROM evaluation_value_results
")

Migration Commands:
1. Run Phase 1 migration: php sql/migrations/run_phase1_migration.php
2. Run Growth Evidence migration: php sql/migrations/run_growth_evidence_migration.php
3. Verify migration: php scripts/test_growth_evidence_system.php

Rollback Commands:
1. Restore from backup: mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_DIR/evaluations_backup.sql"
2. Restore results: mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_DIR/evaluation_results_backup.sql"
EOF

echo "✅ Backup completed successfully!"
echo "📁 Backup location: $BACKUP_DIR"
echo "📊 Summary file: $BACKUP_DIR/migration_summary.txt"
