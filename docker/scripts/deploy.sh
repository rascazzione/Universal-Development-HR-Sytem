#!/bin/bash

# Docker Development Environment - Deploy Script
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

check_requirements() {
    log_info "Checking requirements..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed or not in PATH"
        exit 1
    fi
    
    if ! docker compose version &> /dev/null && ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not available"
        exit 1
    fi
    
    log_success "Requirements check passed"
}

setup_environment() {
    log_info "Setting up environment..."
    
    cd "$DOCKER_DIR"
    
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            log_success "Created .env file from .env.example"
        else
            log_error ".env.example file not found"
            exit 1
        fi
    fi
    
    # Create log directories
    mkdir -p docker/logs/{apache,php,mysql}
    log_success "Created log directories"
}

build_containers() {
    log_info "Building Docker containers..."
    
    docker compose build --no-cache
    log_success "Containers built successfully"
}

start_services() {
    log_info "Starting services..."
    
    docker compose up -d
    log_success "Services started"
}

wait_for_services() {
    log_info "Waiting for services to be ready..."
    
    # Wait for database
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if docker compose exec -T mysql mysqladmin ping -h localhost -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2)" &> /dev/null; then
            log_success "Database is ready"
            break
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            log_error "Database failed to start within timeout"
            exit 1
        fi
        
        log_info "Waiting for database... (attempt $attempt/$max_attempts)"
        sleep 2
        ((attempt++))
    done
    
    # Wait for web server
    attempt=1
    while [ $attempt -le $max_attempts ]; do
        if curl -f http://localhost:8080/health-check.php &> /dev/null; then
            log_success "Web server is ready"
            break
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            log_error "Web server failed to start within timeout"
            exit 1
        fi
        
        log_info "Waiting for web server... (attempt $attempt/$max_attempts)"
        sleep 2
        ((attempt++))
    done
}

run_health_checks() {
    log_info "Running health checks..."
    
    # Check web container health
    if [ "$(docker compose ps -q web | xargs docker inspect --format='{{.State.Health.Status}}')" = "healthy" ]; then
        log_success "Web container is healthy"
    else
        log_warning "Web container health check failed"
    fi
    
    # Check database container health
    if [ "$(docker compose ps -q mysql | xargs docker inspect --format='{{.State.Health.Status}}')" = "healthy" ]; then
        log_success "Database container is healthy"
    else
        log_warning "Database container health check failed"
    fi
}

display_info() {
    log_success "Deployment completed successfully!"
    echo ""
    echo "🌐 Application URL: http://localhost:8080"
    echo "🗄️  Database URL: localhost:3306"
    echo "📊 Health Check: http://localhost:8080/health-check.php"
    echo ""
    echo "Default Login Credentials:"
    echo "  Username: admin"
    echo "  Password: admin123"
    echo ""
    echo "Useful Commands:"
    echo "  View logs: docker compose logs -f"
    echo "  Stop services: docker compose down"
    echo "  Reset environment: ./docker/scripts/reset.sh"
    echo "  Destroy environment: ./docker/scripts/destroy.sh"
    echo ""
}

# Main execution
main() {
    log_info "Starting Docker development environment deployment..."
    
    check_requirements
    setup_environment
    build_containers
    start_services
    wait_for_services
    run_health_checks
    display_info
}

# Handle script arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Deploy the Docker development environment"
        echo ""
        echo "Options:"
        echo "  --help, -h     Show this help message"
        echo "  --rebuild      Force rebuild of containers"
        echo "  --no-cache     Build without using cache"
        echo ""
        exit 0
        ;;
    --rebuild)
        log_info "Forcing container rebuild..."
        docker compose down
        docker compose build --no-cache
        ;;
    --no-cache)
        log_info "Building without cache..."
        ;;
esac

main "$@"