#!/bin/bash

# Docker Development Environment - Reset Script
# Performance Evaluation System

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
DOCKER_DIR="$PROJECT_ROOT"

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

confirm_action() {
    local message="$1"
    local default="${2:-n}"
    
    if [ "$default" = "y" ]; then
        local prompt="[Y/n]"
    else
        local prompt="[y/N]"
    fi
    
    read -p "$message $prompt: " -n 1 -r
    echo
    
    if [ "$default" = "y" ]; then
        [[ $REPLY =~ ^[Nn]$ ]] && return 1 || return 0
    else
        [[ $REPLY =~ ^[Yy]$ ]] && return 0 || return 1
    fi
}

stop_containers() {
    log_info "Stopping containers..."
    
    cd "$DOCKER_DIR"
    
    if docker compose ps -q | grep -q .; then
        docker compose down
        log_success "Containers stopped"
    else
        log_info "No running containers found"
    fi
}

reset_database() {
    log_info "Resetting database..."
    
    # Remove database volume
    if docker volume ls -q | grep -q "$(basename "$DOCKER_DIR")_mysql_data"; then
        docker volume rm "$(basename "$DOCKER_DIR")_mysql_data" 2>/dev/null || true
        log_success "Database volume removed"
    else
        log_info "Database volume not found"
    fi
}

clear_logs() {
    log_info "Clearing logs..."
    
    if [ -d "docker/logs" ]; then
        find docker/logs -type f -name "*.log" -delete 2>/dev/null || true
        log_success "Log files cleared"
    else
        log_info "Log directory not found"
    fi
}

restart_services() {
    log_info "Restarting services..."
    
    docker compose up -d
    
    # Wait for services to be ready
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f http://localhost:8080/health-check.php &> /dev/null; then
            log_success "Services restarted and ready"
            break
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            log_error "Services failed to start within timeout"
            exit 1
        fi
        
        log_info "Waiting for services... (attempt $attempt/$max_attempts)"
        sleep 2
        ((attempt++))
    done
}

display_info() {
    log_success "Environment reset completed!"
    echo ""
    echo "🌐 Application URL: http://localhost:8080"
    echo "🗄️  Database URL: localhost:3306"
    echo ""
    echo "The database has been reset to initial state with default admin user:"
    echo "  Username: admin"
    echo "  Password: admin123"
    echo ""
}

# Main execution
main() {
    log_info "Starting environment reset..."
    
    cd "$DOCKER_DIR"
    
    # Confirm action unless --force flag is used
    if [ "${1:-}" != "--force" ]; then
        echo "This will:"
        echo "  • Stop all containers"
        echo "  • Remove database data (preserving images)"
        echo "  • Clear log files"
        echo "  • Restart services with fresh database"
        echo ""
        
        if ! confirm_action "Are you sure you want to reset the environment?"; then
            log_info "Reset cancelled"
            exit 0
        fi
    fi
    
    stop_containers
    reset_database
    clear_logs
    restart_services
    display_info
}

# Handle script arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Reset the Docker development environment"
        echo ""
        echo "Options:"
        echo "  --help, -h     Show this help message"
        echo "  --force        Skip confirmation prompt"
        echo ""
        echo "This script will:"
        echo "  • Stop all containers"
        echo "  • Remove database data (preserving images)"
        echo "  • Clear log files"
        echo "  • Restart services with fresh database"
        echo ""
        exit 0
        ;;
    --force)
        log_info "Force reset mode enabled"
        ;;
esac

main "$@"