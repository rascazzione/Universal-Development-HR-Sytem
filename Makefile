# Docker Development Environment - Makefile
# Performance Evaluation System

.PHONY: help up down restart reset destroy logs shell health status clean

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
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(YELLOW)%-15s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""
	@echo "$(GREEN)Examples:$(RESET)"
	@echo "  make up          # Start the development environment"
	@echo "  make logs        # View application logs"
	@echo "  make shell       # Access web container shell"
	@echo "  make reset       # Reset environment with fresh data"
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