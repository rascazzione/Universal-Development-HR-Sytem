# Docker Development Environment - Makefile
# Performance Evaluation System

.PHONY: help up down restart reset destroy logs shell health status clean migrate migrate-status migrate-rollback migrate-validate migrate-create test-data test-data-check test-data-validate test-data-credentials phase1-migrate phase1-test-data phase1-status phase1-help phase1-test

# Default target
.DEFAULT_GOAL := help

# Configuration
COMPOSE_FILE := docker-compose.yml
PROJECT_NAME := $(shell basename $(CURDIR))

# Colors
GREEN := \033[32m
YELLOW := \033[33m
RED := \033[31m
BLUE := \033[34m
RESET := \033[0m

help: ## Show this help message
	@echo "$(BLUE)Docker Development Environment Commands$(RESET)"
	@echo ""
	@echo "$(GREEN)Available commands:$(RESET)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(YELLOW)%-20s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""
	@echo "$(GREEN)Examples:$(RESET)"
	@echo "  make up              # Start the development environment"
	@echo "  make logs            # View application logs"
	@echo "  make shell           # Access web container shell"
	@echo "  make reset           # Reset environment with fresh data"
	@echo "  make test-data       # Populate with comprehensive test data"
	@echo "  make test-data-help  # Show detailed test data help"
	@echo "  make phase1-migrate  # Deploy Phase 1 continuous performance features"
	@echo "  make phase1-help     # Show Phase 1 continuous performance help"
	@echo ""

up: ## Start the development environment
	@echo "$(GREEN)Starting development environment...$(RESET)"
	@./docker/scripts/deploy.sh

down: ## Stop the development environment
	@echo "$(YELLOW)Stopping development environment...$(RESET)"
	@docker compose down

restart: ## Restart all services
	@echo "$(YELLOW)Restarting services...$(RESET)"
	@docker compose restart
	@echo "$(GREEN)Services restarted$(RESET)"

reset: ## Reset environment (clear data, keep images)
	@echo "$(YELLOW)Resetting environment...$(RESET)"
	@./docker/scripts/reset.sh

destroy: ## Completely destroy the environment
	@echo "$(RED)Destroying environment...$(RESET)"
	@./docker/scripts/destroy.sh

logs: ## View application logs
	@docker compose logs -f

logs-web: ## View web server logs only
	@docker compose logs -f web

logs-db: ## View database logs only
	@docker compose logs -f mysql

shell: ## Access web container shell
	@docker compose exec web bash

shell-db: ## Access database container shell
	@docker compose exec mysql bash

mysql: ## Access MySQL command line
	@docker compose exec mysql mysql -u root -p performance_evaluation

health: ## Run health checks
	@./docker/scripts/health-check.sh

status: ## Show container status
	@echo "$(BLUE)Container Status:$(RESET)"
	@docker compose ps
	@echo ""
	@echo "$(BLUE)Resource Usage:$(RESET)"
	@docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}" $$(docker compose ps -q) 2>/dev/null || echo "No containers running"

clean: ## Clean up unused Docker resources
	@echo "$(YELLOW)Cleaning up Docker resources...$(RESET)"
	@docker system prune -f
	@echo "$(GREEN)Cleanup completed$(RESET)"

build: ## Build containers without cache
	@echo "$(YELLOW)Building containers...$(RESET)"
	@docker compose build --no-cache

rebuild: ## Rebuild and restart containers
	@echo "$(YELLOW)Rebuilding containers...$(RESET)"
	@docker compose down
	@docker compose build --no-cache
	@docker compose up -d
	@echo "$(GREEN)Rebuild completed$(RESET)"

backup: ## Backup database
	@echo "$(YELLOW)Creating database backup...$(RESET)"
	@mkdir -p backups
	@docker compose exec -T mysql mysqldump -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation > backups/backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Backup created in backups/ directory$(RESET)"

restore: ## Restore database from backup (usage: make restore BACKUP=filename)
	@if [ -z "$(BACKUP)" ]; then \
		echo "$(RED)Error: Please specify backup file$(RESET)"; \
		echo "Usage: make restore BACKUP=backup_20231201_120000.sql"; \
		exit 1; \
	fi
	@if [ ! -f "backups/$(BACKUP)" ]; then \
		echo "$(RED)Error: Backup file not found$(RESET)"; \
		exit 1; \
	fi
	@echo "$(YELLOW)Restoring database from $(BACKUP)...$(RESET)"
	@docker compose exec -T mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation < backups/$(BACKUP)
	@echo "$(GREEN)Database restored$(RESET)"

update: ## Update container images
	@echo "$(YELLOW)Updating container images...$(RESET)"
	@docker compose pull
	@echo "$(GREEN)Images updated$(RESET)"

install: ## Initial setup (copy .env, create directories)
	@echo "$(GREEN)Setting up development environment...$(RESET)"
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "$(GREEN)Created .env file from .env.example$(RESET)"; \
	fi
	@mkdir -p docker/logs/{apache,php,mysql}
	@echo "$(GREEN)Created log directories$(RESET)"
	@echo "$(BLUE)Setup completed! Run 'make up' to start the environment$(RESET)"

# Database Migration Commands
migrate: ## Run pending database migrations
	@echo "$(YELLOW)Running database migrations...$(RESET)"
	@docker compose exec web php /var/www/html/sql/migration_runner.php migrate

migrate-status: ## Show migration status
	@echo "$(BLUE)Migration status:$(RESET)"
	@docker compose exec web php /var/www/html/sql/migration_runner.php status

migrate-rollback: ## Rollback specific migration
	@echo "$(RED)Rolling back migration...$(RESET)"
	@read -p "Enter migration version to rollback: " version; \
	docker compose exec web php /var/www/html/sql/migration_runner.php rollback $$version

migrate-validate: ## Validate migration files
	@echo "$(YELLOW)Validating migrations...$(RESET)"
	@docker compose exec web php /var/www/html/sql/migration_runner.php validate

migrate-create: ## Create new migration file
	@read -p "Enter migration description: " desc; \
	docker compose exec web php /var/www/html/sql/migration_runner.php create "$$desc"

# Test Data Commands
test-data: ## Populate database with comprehensive test data
	@echo "$(YELLOW)Populating database with test data...$(RESET)"
	@echo "$(RED)WARNING: This will clear all existing data except system settings!$(RESET)"
	@read -p "Continue? (y/N): " confirm; \
	if [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ]; then \
		docker compose exec web php /var/www/html/scripts/populate_test_data.php; \
		echo "$(GREEN)Test data population completed!$(RESET)"; \
		echo "$(BLUE)Check scripts/test_data_credentials.txt for login information$(RESET)"; \
	else \
		echo "$(YELLOW)Operation cancelled$(RESET)"; \
	fi

test-data-force: ## Populate test data without confirmation (use with caution!)
	@echo "$(YELLOW)Force populating database with test data...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/populate_test_data.php
	@echo "$(GREEN)Test data population completed!$(RESET)"
	@echo "$(BLUE)Check scripts/test_data_credentials.txt for login information$(RESET)"

test-data-check: ## Validate prerequisites for test data population
	@echo "$(BLUE)Checking test data prerequisites...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/test_populate_script.php

test-data-validate: ## Alias for test-data-check
	@make test-data-check

test-data-credentials: ## Show test data user credentials
	@echo "$(BLUE)Test Data User Credentials:$(RESET)"
	@if [ -f scripts/test_data_credentials.txt ]; then \
		cat scripts/test_data_credentials.txt; \
	else \
		echo "$(RED)Credentials file not found. Run 'make test-data' first.$(RESET)"; \
	fi

test-data-summary: ## Show test data summary
	@echo "$(BLUE)Test Data Summary:$(RESET)"
	@if [ -f scripts/test_data_summary.txt ]; then \
		cat scripts/test_data_summary.txt; \
	else \
		echo "$(RED)Summary file not found. Run 'make test-data' first.$(RESET)"; \
	fi

test-data-clean: ## Remove test data files
	@echo "$(YELLOW)Cleaning test data files...$(RESET)"
	@rm -f scripts/test_data_credentials.txt scripts/test_data_summary.txt
	@echo "$(GREEN)Test data files cleaned$(RESET)"

test-data-help: ## Show detailed help for test data commands
	@echo "$(BLUE)Test Data Population Commands$(RESET)"
	@echo ""
	@echo "$(GREEN)Available commands:$(RESET)"
	@echo "  $(YELLOW)test-data$(RESET)             Populate database with test data (with confirmation)"
	@echo "  $(YELLOW)test-data-force$(RESET)       Populate test data without confirmation"
	@echo "  $(YELLOW)test-data-check$(RESET)       Validate prerequisites before running"
	@echo "  $(YELLOW)test-data-credentials$(RESET) Show user credentials for testing"
	@echo "  $(YELLOW)test-data-summary$(RESET)     Show summary of generated data"
	@echo "  $(YELLOW)test-data-clean$(RESET)       Remove generated documentation files"
	@echo ""
	@echo "$(GREEN)What gets created:$(RESET)"
	@echo "  • 28 users (1 HR admin, 6 managers, 21 employees)"
	@echo "  • 6 departments with organizational hierarchy"
	@echo "  • 8 job templates with KPIs, competencies, and responsibilities"
	@echo "  • 18 KPIs across 5 categories"
	@echo "  • 14 competencies covering technical and soft skills"
	@echo "  • 5 company values"
	@echo "  • 3 evaluation periods (past, current, future)"
	@echo "  • 75+ evaluations with realistic scores and comments"
	@echo ""
	@echo "$(GREEN)Sample credentials:$(RESET)"
	@echo "  HR Admin:  admin.system / admin123"
	@echo "  Manager:   manager.smith / manager123"
	@echo "  Employee:  john.doe / employee123"
	@echo ""
	@echo "$(RED)WARNING:$(RESET) This will clear ALL existing data except system settings!"
	@echo ""

# Phase 1 Continuous Performance Commands
phase1-migrate: ## Deploy Phase 1 continuous performance foundation
	@echo "$(YELLOW)Deploying Phase 1 continuous performance foundation...$(RESET)"
	@echo "$(BLUE)This will add 1:1 sessions and real-time feedback capture$(RESET)"
	@read -p "Continue with Phase 1 migration? (y/N): " confirm; \
	if [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ]; then \
		docker compose exec web php /var/www/html/sql/migrations/run_phase1_migration.php; \
		echo "$(GREEN)Phase 1 migration completed!$(RESET)"; \
		echo "$(BLUE)Run 'make phase1-test-data' to populate with sample 1:1 data$(RESET)"; \
	else \
		echo "$(YELLOW)Migration cancelled$(RESET)"; \
	fi

phase1-migrate-force: ## Force deploy Phase 1 (overwrites existing Phase 1 tables)
	@echo "$(RED)Force deploying Phase 1 continuous performance foundation...$(RESET)"
	@echo "$(RED)WARNING: This will drop and recreate Phase 1 tables!$(RESET)"
	@read -p "Are you sure? This will destroy existing 1:1 data! (y/N): " confirm; \
	if [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ]; then \
		docker compose exec web php /var/www/html/sql/migrations/run_phase1_migration.php --force; \
		echo "$(GREEN)Phase 1 force migration completed!$(RESET)"; \
	else \
		echo "$(YELLOW)Force migration cancelled$(RESET)"; \
	fi

phase1-migrate-dry-run: ## Test Phase 1 migration without making changes
	@echo "$(BLUE)Running Phase 1 migration dry run...$(RESET)"
	@docker compose exec web php /var/www/html/sql/migrations/run_phase1_migration.php --dry-run

phase1-test-data: ## Populate Phase 1 with realistic 1:1 session and feedback data
	@echo "$(YELLOW)Populating Phase 1 with continuous performance test data...$(RESET)"
	@echo "$(BLUE)This creates 6 months of 1:1 sessions and feedback for existing employees$(RESET)"
	@read -p "Continue with Phase 1 test data population? (y/N): " confirm; \
	if [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ]; then \
		docker compose exec web php /var/www/html/scripts/populate_phase1_test_data.php; \
		echo "$(GREEN)Phase 1 test data population completed!$(RESET)"; \
		echo "$(BLUE)Run 'make phase1-status' to see the results$(RESET)"; \
	else \
		echo "$(YELLOW)Test data population cancelled$(RESET)"; \
	fi

phase1-test-data-force: ## Force populate Phase 1 test data without confirmation
	@echo "$(YELLOW)Force populating Phase 1 test data...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/populate_phase1_test_data.php
	@echo "$(GREEN)Phase 1 test data population completed!$(RESET)"

phase1-status: ## Show Phase 1 continuous performance system status
	@echo "$(BLUE)Phase 1 Continuous Performance Status$(RESET)"
	@echo ""
	@echo "$(GREEN)Database Tables:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'performance_evaluation' AND TABLE_NAME LIKE 'one_to_one%' ORDER BY TABLE_NAME;" 2>/dev/null || echo "$(RED)Phase 1 tables not found - run 'make phase1-migrate' first$(RESET)"
	@echo ""
	@echo "$(GREEN)Sample Data Summary:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT 'Sessions' as Type, COUNT(*) as Count FROM one_to_one_sessions UNION ALL SELECT 'Feedback Items', COUNT(*) FROM one_to_one_feedback UNION ALL SELECT 'Templates', COUNT(*) FROM one_to_one_templates;" 2>/dev/null || echo "$(RED)No Phase 1 data found$(RESET)"
	@echo ""
	@echo "$(GREEN)Recent Activity:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT CONCAT(e.first_name, ' ', e.last_name) as Employee, s.actual_date as 'Last 1:1', COUNT(f.feedback_id) as 'Feedback Items' FROM employees e LEFT JOIN one_to_one_sessions s ON e.employee_id = s.employee_id AND s.status = 'completed' LEFT JOIN one_to_one_feedback f ON s.session_id = f.session_id WHERE e.active = 1 GROUP BY e.employee_id, e.first_name, e.last_name, s.actual_date ORDER BY s.actual_date DESC LIMIT 5;" 2>/dev/null || echo "$(RED)No activity data available$(RESET)"

phase1-evidence-demo: ## Demonstrate evidence aggregation for a sample employee
	@echo "$(BLUE)Phase 1 Evidence Aggregation Demo$(RESET)"
	@echo ""
	@echo "$(GREEN)Testing evidence aggregation procedure...$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "CALL sp_aggregate_1to1_evidence((SELECT employee_id FROM employees WHERE active = 1 LIMIT 1), DATE_SUB(NOW(), INTERVAL 3 MONTH), NOW());" 2>/dev/null || echo "$(RED)Evidence aggregation failed - ensure Phase 1 is deployed and has test data$(RESET)"

phase1-feedback-analysis: ## Show feedback analysis across the organization
	@echo "$(BLUE)Phase 1 Feedback Analysis$(RESET)"
	@echo ""
	@echo "$(GREEN)Feedback by Type:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT feedback_type as 'Feedback Type', COUNT(*) as Count, ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM one_to_one_feedback), 1) as 'Percentage' FROM one_to_one_feedback GROUP BY feedback_type ORDER BY Count DESC;" 2>/dev/null || echo "$(RED)No feedback data available$(RESET)"
	@echo ""
	@echo "$(GREEN)Feedback Linked to Evaluation Criteria:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT 'Competencies' as 'Linked To', COUNT(*) as Count FROM one_to_one_feedback WHERE related_competency_id IS NOT NULL UNION ALL SELECT 'KPIs', COUNT(*) FROM one_to_one_feedback WHERE related_kpi_id IS NOT NULL UNION ALL SELECT 'Values', COUNT(*) FROM one_to_one_feedback WHERE related_value_id IS NOT NULL;" 2>/dev/null || echo "$(RED)No feedback data available$(RESET)"

phase1-test: ## Run comprehensive Phase 1 implementation tests
	@echo "$(YELLOW)Running Phase 1 implementation tests...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/test_phase1_implementation.php
	@echo "$(GREEN)Phase 1 testing completed$(RESET)"

phase1-test-verbose: ## Run Phase 1 tests with detailed output
	@echo "$(YELLOW)Running Phase 1 implementation tests (verbose)...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/test_phase1_implementation.php --verbose

phase1-test-performance: ## Run Phase 1 tests including performance benchmarks
	@echo "$(YELLOW)Running Phase 1 implementation tests with performance benchmarks...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/test_phase1_implementation.php --verbose --performance

phase1-help: ## Show detailed help for Phase 1 continuous performance features
	@echo "$(BLUE)Phase 1: Continuous Performance Foundation$(RESET)"
	@echo ""
	@echo "$(GREEN)What Phase 1 Provides:$(RESET)"
	@echo "  • 1:1 session tracking and management"
	@echo "  • Real-time feedback capture during sessions"
	@echo "  • Evidence aggregation for performance reviews"
	@echo "  • Structured session templates and agendas"
	@echo "  • Elimination of 'surprise factor' in reviews"
	@echo ""
	@echo "$(GREEN)Available Commands:$(RESET)"
	@echo "  $(YELLOW)phase1-migrate$(RESET)           Deploy Phase 1 database schema"
	@echo "  $(YELLOW)phase1-migrate-force$(RESET)     Force redeploy (destroys existing data)"
	@echo "  $(YELLOW)phase1-migrate-dry-run$(RESET)   Test migration without changes"
	@echo "  $(YELLOW)phase1-test-data$(RESET)         Populate with realistic 1:1 data"
	@echo "  $(YELLOW)phase1-test-data-force$(RESET)   Force populate test data"
	@echo "  $(YELLOW)phase1-status$(RESET)            Show system status and data summary"
	@echo "  $(YELLOW)phase1-evidence-demo$(RESET)     Demonstrate evidence aggregation"
	@echo "  $(YELLOW)phase1-feedback-analysis$(RESET) Show feedback patterns and trends"
	@echo "  $(YELLOW)phase1-test$(RESET)              Run comprehensive implementation tests"
	@echo "  $(YELLOW)phase1-test-verbose$(RESET)      Run tests with detailed output"
	@echo "  $(YELLOW)phase1-test-performance$(RESET)  Run tests with performance benchmarks"
	@echo ""
	@echo "$(GREEN)Implementation Workflow:$(RESET)"
	@echo "  1. $(YELLOW)make phase1-migrate$(RESET)     # Deploy the foundation"
	@echo "  2. $(YELLOW)make phase1-test-data$(RESET)   # Add sample data"
	@echo "  3. $(YELLOW)make phase1-test$(RESET)        # Run comprehensive tests"
	@echo "  4. $(YELLOW)make phase1-status$(RESET)      # Verify deployment"
	@echo "  5. $(YELLOW)make phase1-evidence-demo$(RESET) # Test evidence aggregation"
	@echo ""
	@echo "$(GREEN)Key Features Enabled:$(RESET)"
	@echo "  • Managers can schedule and track 1:1 sessions"
	@echo "  • Real-time feedback capture linked to competencies/KPIs"
	@echo "  • Automatic evidence aggregation for performance reviews"
	@echo "  • Follow-up tracking and action item management"
	@echo "  • Bias reduction through evidence-based reviews"
	@echo ""
	@echo "$(GREEN)Next Steps:$(RESET)"
	@echo "  • Phase 2: Dynamic development goal tracking"
	@echo "  • Phase 3: Calibration framework and bias detection"
	@echo "  • Phase 4: Advanced analytics and predictive insights"
	@echo ""
	@echo "$(BLUE)Documentation:$(RESET) docs/PHASE1_CONTINUOUS_PERFORMANCE_IMPLEMENTATION.md"
	@echo ""