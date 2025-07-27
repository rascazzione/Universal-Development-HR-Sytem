<?php
/**
 * Complete Growth Evidence System Migration Script
 * Orchestrates the entire migration process
 */

echo "🚀 Starting complete Growth Evidence System migration...\n";
echo "==============================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Create backup
echo "🔒 Step 1: Creating backup...\n";
exec('./scripts/migration_backup.sh', $output, $returnCode);
if ($returnCode !== 0) {
    echo "❌ Backup failed. Aborting migration.\n";
    exit(1);
}
echo "✅ Backup completed successfully\n\n";

// Step 2: Run Phase 1 migration (if exists)
echo "📋 Step 2: Running Phase 1 migration...\n";
if (file_exists('sql/migrations/run_phase1_migration.php')) {
    exec('php sql/migrations/run_phase1_migration.php', $output, $returnCode);
    if ($returnCode === 0) {
        echo "✅ Phase 1 migration completed\n";
    } else {
        echo "⚠️ Phase 1 migration skipped or failed\n";
    }
} else {
    echo "⚠️ Phase 1 migration file not found, skipping\n";
}
echo "";

// Step 3: Run Growth Evidence migration
echo "🔄 Step 3: Running Growth Evidence migration...\n";
if (file_exists('sql/migrations/run_growth_evidence_migration.php')) {
    exec('php sql/migrations/run_growth_evidence_migration.php', $output, $returnCode);
    if ($returnCode === 0) {
        echo "✅ Growth Evidence migration completed\n";
    } else {
        echo "❌ Growth Evidence migration failed\n";
        exit(1);
    }
} else {
    echo "❌ Growth Evidence migration file not found\n";
    exit(1);
}
echo "";

// Step 4: Migrate existing data
echo "📊 Step 4: Migrating existing evaluation data...\n";
if (file_exists('scripts/migrate_evaluation_data.php')) {
    exec('php scripts/migrate_evaluation_data.php', $output, $returnCode);
    if ($returnCode === 0) {
        echo "✅ Data migration completed\n";
    } else {
        echo "❌ Data migration failed\n";
        exit(1);
    }
} else {
    echo "❌ Data migration file not found\n";
    exit(1);
}
echo "";

// Step 5: Verify migration
echo "✅ Step 5: Verifying migration...\n";
if (file_exists('scripts/test_growth_evidence_system.php')) {
    exec('php scripts/test_growth_evidence_system.php', $output, $returnCode);
    if ($returnCode === 0) {
        echo "✅ Migration verification passed\n";
    } else {
        echo "⚠️ Migration verification issues detected\n";
    }
} else {
    echo "⚠️ Verification script not found\n";
}
echo "";

// Step 6: Final summary
echo "🎉 Migration completed successfully!\n";
echo "==============================================\n";
echo "Next steps:\n";
echo "1. Test the system by logging in as different user types\n";
echo "2. Create a test feedback entry at /employees/give-feedback.php\n";
echo "3. View feedback history at /employees/view-feedback.php\n";
echo "4. Create a new evaluation to see evidence-based scoring\n";
echo "5. Train your team on the new workflows\n";
echo "\nMigration timestamp: " . date('Y-m-d H:i:s') . "\n";
?>
