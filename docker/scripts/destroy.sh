#!/bin/bash

# Docker Development Environment - Destroy Script
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
PROJECT_NAME="$(basename "$DOCKER_DIR")"

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
    read -p "$message [y/N]: " -n 1 -r
    echo
    [[ $REPLY =~ ^[Yy]$ ]]
}

stop_and_remove_containers() {
    log_info "Stopping and removing containers..."
    
    cd "$DOCKER_DIR"
    
    # Stop and remove containers
    docker compose down --remove-orphans 2>/dev/null || true
    
    # Remove any remaining containers
    local containers=$(docker ps -a --filter "label=com.docker.compose.project=$PROJECT_NAME" -q)
    if [ -n "$containers" ]; then
        docker rm -f $containers 2>/dev/null || true
        log_success "Containers removed"
    else
        log_info "No containers to remove"
    fi
}

remove_images() {
    log_info "Removing Docker images..."
    
    # Remove project-specific images
    local images=$(docker images --filter "label=com.docker.compose.project=$PROJECT_NAME" -q)
    if [ -n "$images" ]; then
        docker rmi -f $images 2>/dev/null || true
    fi
    
    # Remove custom built images
    local custom_images=$(docker images "${PROJECT_NAME}_*" -q)
    if [ -n "$custom_images" ]; then
        docker rmi -f $custom_images 2>/dev/null || true
    fi
    
    log_success "Images removed"
}

remove_volumes() {
    log_info "Removing Docker volumes..."
    
    # Remove named volumes
    local volumes=$(docker volume ls --filter "label=com.docker.compose.project=$PROJECT_NAME" -q)
    if [ -n "$volumes" ]; then
        docker volume rm $volumes 2>/dev/null || true
    fi
    
    # Remove project-specific volumes
    local project_volumes=$(docker volume ls -q | grep "^${PROJECT_NAME}_")
    if [ -n "$project_volumes" ]; then
        echo "$project_volumes" | xargs docker volume rm 2>/dev/null || true
    fi
    
    log_success "Volumes removed"
}

remove_networks() {
    log_info "Removing Docker networks..."
    
    # Remove project-specific networks
    local networks=$(docker network ls --filter "label=com.docker.compose.project=$PROJECT_NAME" -q)
    if [ -n "$networks" ]; then
        docker network rm $networks 2>/dev/null || true
    fi
    
    # Remove networks by name pattern
    local project_networks=$(docker network ls -q --filter "name=${PROJECT_NAME}_")
    if [ -n "$project_networks" ]; then
        echo "$project_networks" | xargs docker network rm 2>/dev/null || true
    fi
    
    log_success "Networks removed"
}

cleanup_files() {
    log_info "Cleaning up files..."
    
    # Remove log files
    if [ -d "docker/logs" ]; then
        rm -rf docker/logs/*
        log_success "Log files removed"
    fi
    
    # Remove temporary files
    if [ -f ".env" ]; then
        if confirm_action "Remove .env file?"; then
            rm .env
            log_success ".env file removed"
        fi
    fi
}

cleanup_system() {
    log_info "Cleaning up Docker system..."
    
    # Remove unused images, containers, networks, and volumes
    docker system prune -f --volumes 2>/dev/null || true
    
    log_success "Docker system cleanup completed"
}

display_summary() {
    log_success "Environment destruction completed!"
    echo ""
    echo "The following have been removed:"
    echo "  ✓ All containers"
    echo "  ✓ All images"
    echo "  ✓ All volumes (including database data)"
    echo "  ✓ All networks"
    echo "  ✓ Log files"
    echo ""
    echo "To recreate the environment, run: ./docker/scripts/deploy.sh"
    echo ""
}

# Main execution
main() {
    log_info "Starting environment destruction..."
    
    cd "$DOCKER_DIR"
    
    # Confirm action unless --force flag is used
    if [ "${1:-}" != "--force" ]; then
        echo "⚠️  WARNING: This will completely destroy the Docker environment!"
        echo ""
        echo "This will remove:"
        echo "  • All containers"
        echo "  • All images"
        echo "  • All volumes (including database data)"
        echo "  • All networks"
        echo "  • Log files"
        echo ""
        echo "This action cannot be undone!"
        echo ""
        
        if ! confirm_action "Are you absolutely sure you want to destroy everything?"; then
            log_info "Destruction cancelled"
            exit 0
        fi
        
        echo ""
        if ! confirm_action "Last chance - this will delete ALL data. Continue?"; then
            log_info "Destruction cancelled"
            exit 0
        fi
    fi
    
    stop_and_remove_containers
    remove_images
    remove_volumes
    remove_networks
    cleanup_files
    cleanup_system
    display_summary
}

# Handle script arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Completely destroy the Docker development environment"
        echo ""
        echo "Options:"
        echo "  --help, -h     Show this help message"
        echo "  --force        Skip confirmation prompts"
        echo ""
        echo "⚠️  WARNING: This will permanently delete:"
        echo "  • All containers"
        echo "  • All images"
        echo "  • All volumes (including database data)"
        echo "  • All networks"
        echo "  • Log files"
        echo ""
        exit 0
        ;;
    --force)
        log_warning "Force destruction mode enabled - skipping confirmations"
        ;;
esac

main "$@"