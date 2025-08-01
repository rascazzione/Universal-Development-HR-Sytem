# Docker Development Environment - Makefile
# Performance Evaluation System

.PHONY: help up down restart reset destroy logs shell health status clean migrate migrate-status migrate-rollback migrate-validate migrate-create test-data test-data-check test-data-validate test-data-credentials evidence-status evidence-analytics evidence-notifications evidence-test evidence-validation

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
	@echo "  make up                    # Start the development environment"
	@echo "  make logs                  # View application logs"
	@echo "  make shell                 # Access web container shell"
	@echo "  make reset                 # Reset environment with fresh data"
	@echo "  make test-data             # Populate with comprehensive test data including Growth Evidence System"
	@echo "  make test-data-help        # Show detailed test data help"
	@echo "  make evidence-status       # Show Growth Evidence System status"
	@echo "  make evidence-analytics    # Show evidence analytics and insights"
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
	@echo "  • 200+ growth evidence entries across all dimensions"
	@echo "  • Evidence tags and categorization system"
	@echo "  • Notification system with sample alerts"
	@echo "  • Phase 3 advanced features (approvals, reports)"
	@echo ""
	@echo "$(GREEN)Sample credentials:$(RESET)"
	@echo "  HR Admin:  admin.system / admin123"
	@echo "  Manager:   manager.smith / manager123"
	@echo "  Employee:  john.doe / employee123"
	@echo ""
	@echo "$(RED)WARNING:$(RESET) This will clear ALL existing data except system settings!"
	@echo ""
	@echo "$(GREEN)Growth Evidence System Features Included:$(RESET)"
	@echo "  • Evidence capture across KPIs, competencies, responsibilities, values"
	@echo "  • Evidence tagging and categorization"
	@echo "  • Real-time notifications and alerts"
	@echo "  • Evidence approval workflows"
	@echo "  • Scheduled reporting system"
	@echo "  • Evidence aggregation for evaluations"
	@echo "  • Analytics and performance insights"
	@echo ""

# Growth Evidence System Commands

evidence-status: ## Show Growth Evidence System status and data summary
	@echo "$(BLUE)Growth Evidence System Status$(RESET)"
	@echo ""
	@echo "$(GREEN)Database Tables:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'performance_evaluation' AND (TABLE_NAME LIKE 'growth_evidence%' OR TABLE_NAME LIKE 'evidence%' OR TABLE_NAME = 'notifications') ORDER BY TABLE_NAME;" 2>/dev/null || echo "$(RED)Growth Evidence tables not found$(RESET)"
	@echo ""
	@echo "$(GREEN)Evidence Data Summary:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT 'Evidence Entries' as Type, COUNT(*) as Count FROM growth_evidence_entries UNION ALL SELECT 'Notifications', COUNT(*) FROM notifications UNION ALL SELECT 'Evidence Tags', COUNT(*) FROM evidence_tags;" 2>/dev/null || echo "$(RED)No Growth Evidence data found$(RESET)"
	@echo ""
	@echo "$(GREEN)Evidence by Dimension:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT dimension as 'Dimension', COUNT(*) as 'Count', ROUND(AVG(star_rating), 2) as 'Avg Rating' FROM growth_evidence_entries GROUP BY dimension ORDER BY Count DESC;" 2>/dev/null || echo "$(RED)No evidence data available$(RESET)"

evidence-analytics: ## Show Growth Evidence analytics and insights
	@echo "$(BLUE)Growth Evidence Analytics$(RESET)"
	@echo ""
	@echo "$(GREEN)Evidence Trends by Month:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT DATE_FORMAT(entry_date, '%Y-%m') as Month, COUNT(*) as 'Evidence Count', ROUND(AVG(star_rating), 2) as 'Avg Rating' FROM growth_evidence_entries GROUP BY DATE_FORMAT(entry_date, '%Y-%m') ORDER BY Month DESC LIMIT 6;" 2>/dev/null || echo "$(RED)No evidence data available$(RESET)"
	@echo ""
	@echo "$(GREEN)Top Performing Employees (by evidence quality):$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) performance_evaluation -e "SELECT CONCAT(e.first_name, ' ', e.last_name) as Employee, COUNT(gee.entry_id) as 'Evidence Count', ROUND(AVG(gee.star_rating), 2) as 'Avg Rating' FROM employees e JOIN growth_evidence_entries gee ON e.employee_id = gee.employee_id GROUP BY e.employee_id, e.first_name, e.last_name HAVING COUNT(gee.entry_id) >= 3 ORDER BY AVG(gee.star_rating) DESC, COUNT(gee.entry_id) DESC LIMIT 5;" 2>/dev/null || echo "$(RED)No evidence data available$(RESET)"

evidence-notifications: ## Show notification system status
	@echo "$(BLUE)Notification System Status$(RESET)"
	@echo ""
	@echo "$(GREEN)Notification Summary:$(RESET)"
	@docker compose exec mysql mysql -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) web_object_classification -e "SELECT type as 'Type', COUNT(*) as 'Total', SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as 'Unread', priority as 'Priority' FROM notifications GROUP BY type, priority ORDER BY COUNT(*) DESC;" 2>/dev/null || echo "$(RED)No notification data available$(RESET)"

evidence-test: ## Run comprehensive Growth Evidence System tests
	@echo "$(YELLOW)Running Growth Evidence System tests...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/test_evidence_aggregation.php
	@echo "$(GREEN)Growth Evidence testing completed$(RESET)"

evidence-validation: ## Run complete system validation
	@echo "$(YELLOW)Running comprehensive Growth Evidence System validation...$(RESET)"
	@docker compose exec web php /var/www/html/scripts/comprehensive_final_system_validation.php
	@echo "$(GREEN)System validation completed$(RESET)"

evidence-help: ## Show detailed help for Growth Evidence System features
	@echo "$(BLUE)Growth Evidence System: Evidence-Based Performance Management$(RESET)"
	@echo ""
	@echo "$(GREEN)What the Growth Evidence System Provides:$(RESET)"
	@echo "  • Continuous evidence capture across 4 dimensions (KPIs, competencies, responsibilities, values)"
	@echo "  • Real-time notifications and milestone alerts"
	@echo "  • Evidence tagging and categorization system"
	@echo "  • Advanced analytics and reporting capabilities"
	@echo "  • Evidence-based evaluation aggregation"
	@echo "  • Phase 3 advanced features (approvals, scheduled reports)"
	@echo ""
	@echo "$(GREEN)Available Commands:$(RESET)"
	@echo "  $(YELLOW)test-data$(RESET)                Create complete test environment with Growth Evidence System"
	@echo "  $(YELLOW)evidence-status$(RESET)          Show system status and data summary"
	@echo "  $(YELLOW)evidence-analytics$(RESET)       Show evidence analytics and insights"
	@echo "  $(YELLOW)evidence-notifications$(RESET)   Show notification system status"
	@echo "  $(YELLOW)evidence-test$(RESET)            Run comprehensive system tests"
	@echo "  $(YELLOW)evidence-validation$(RESET)      Run complete system validation"
	@echo ""
	@echo "$(GREEN)Unified Workflow:$(RESET)"
	@echo "  1. $(YELLOW)make test-data$(RESET)           # Create complete test environment (includes all Growth Evidence features)"
	@echo "  2. $(YELLOW)make evidence-test$(RESET)       # Run system tests"
	@echo "  3. $(YELLOW)make evidence-status$(RESET)     # Verify deployment"
	@echo "  4. $(YELLOW)make evidence-analytics$(RESET)  # View evidence insights"
	@echo ""
	@echo "$(GREEN)Key Features Enabled:$(RESET)"
	@echo "  • Managers can capture evidence across all performance dimensions"
	@echo "  • Real-time evidence aggregation for evaluations"
	@echo "  • Notification system for milestones and reminders"
	@echo "  • Evidence tagging and advanced search capabilities"
	@echo "  • Comprehensive analytics and reporting"
	@echo "  • Evidence approval workflows (Phase 3)"
	@echo ""
	@echo "$(GREEN)Evidence Dimensions:$(RESET)"
	@echo "  • $(YELLOW)KPIs$(RESET): Key Performance Indicators and measurable outcomes"
	@echo "  • $(YELLOW)Competencies$(RESET): Skills, knowledge, and behavioral competencies"
	@echo "  • $(YELLOW)Responsibilities$(RESET): Job-specific duties and accountabilities"
	@echo "  • $(YELLOW)Values$(RESET): Company values and cultural alignment"
	@echo ""
	@echo "$(BLUE)Documentation:$(RESET) docs/GROWTH_EVIDENCE_SYSTEM_IMPLEMENTATION.md"
	@echo ""