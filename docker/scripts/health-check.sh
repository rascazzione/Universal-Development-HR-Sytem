#!/bin/bash

# Docker Development Environment - Health Check Script
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

check_container_status() {
    local service="$1"
    local container_id=$(docker compose ps -q "$service" 2>/dev/null)
    
    if [ -z "$container_id" ]; then
        log_error "$service container is not running"
        return 1
    fi
    
    local status=$(docker inspect --format='{{.State.Status}}' "$container_id")
    if [ "$status" != "running" ]; then
        log_error "$service container status: $status"
        return 1
    fi
    
    log_success "$service container is running"
    return 0
}

check_container_health() {
    local service="$1"
    local container_id=$(docker compose ps -q "$service" 2>/dev/null)
    
    if [ -z "$container_id" ]; then
        return 1
    fi
    
    local health=$(docker inspect --format='{{.State.Health.Status}}' "$container_id" 2>/dev/null || echo "none")
    
    case "$health" in
        "healthy")
            log_success "$service container is healthy"
            return 0
            ;;
        "unhealthy")
            log_error "$service container is unhealthy"
            return 1
            ;;
        "starting")
            log_warning "$service container health check is starting"
            return 1
            ;;
        "none")
            log_info "$service container has no health check configured"
            return 0
            ;;
        *)
            log_warning "$service container health status: $health"
            return 1
            ;;
    esac
}

check_web_service() {
    log_info "Checking web service..."
    
    if ! check_container_status "web"; then
        return 1
    fi
    
    if ! check_container_health "web"; then
        return 1
    fi
    
    # Check if web server is responding
    if curl -f -s http://localhost:8080/health-check.php > /dev/null; then
        log_success "Web server is responding"
    else
        log_error "Web server is not responding"
        return 1
    fi
    
    return 0
}

check_database_service() {
    log_info "Checking database service..."
    
    if ! check_container_status "mysql"; then
        return 1
    fi
    
    if ! check_container_health "mysql"; then
        return 1
    fi
    
    # Check database connectivity
    if docker compose exec -T mysql mysqladmin ping -h localhost -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2)" &> /dev/null; then
        log_success "Database is accessible"
    else
        log_error "Database is not accessible"
        return 1
    fi
    
    return 0
}

check_application_health() {
    log_info "Checking application health..."
    
    local health_response=$(curl -s http://localhost:8080/health-check.php)
    local health_status=$(echo "$health_response" | grep -o '"status":"[^"]*"' | cut -d '"' -f4)
    
    if [ "$health_status" = "healthy" ]; then
        log_success "Application health check passed"
        return 0
    else
        log_error "Application health check failed: $health_status"
        echo "Response: $health_response"
        return 1
    fi
}

check_disk_space() {
    log_info "Checking disk space..."
    
    local available=$(df -h . | awk 'NR==2 {print $4}')
    local usage=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$usage" -gt 90 ]; then
        log_error "Disk usage is critical: ${usage}% (${available} available)"
        return 1
    elif [ "$usage" -gt 80 ]; then
        log_warning "Disk usage is high: ${usage}% (${available} available)"
    else
        log_success "Disk usage is normal: ${usage}% (${available} available)"
    fi
    
    return 0
}

check_docker_resources() {
    log_info "Checking Docker resources..."
    
    # Check Docker daemon
    if ! docker info &> /dev/null; then
        log_error "Docker daemon is not accessible"
        return 1
    fi
    
    # Check container resource usage
    local containers=$(docker compose ps -q)
    if [ -n "$containers" ]; then
        local stats=$(docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}" $containers)
        echo "$stats"
    fi
    
    log_success "Docker resources checked"
    return 0
}

generate_report() {
    local overall_status="$1"
    
    echo ""
    echo "=================================="
    echo "    HEALTH CHECK REPORT"
    echo "=================================="
    echo "Timestamp: $(date)"
    echo "Overall Status: $overall_status"
    echo ""
    
    if [ "$overall_status" = "HEALTHY" ]; then
        echo "✅ All systems are operational"
        echo ""
        echo "🌐 Application: http://localhost:8080"
        echo "🗄️  Database: localhost:3306"
        echo "📊 Health Check: http://localhost:8080/health-check.php"
    else
        echo "❌ Some systems are experiencing issues"
        echo ""
        echo "Check the logs above for details"
        echo "Run 'docker compose logs' for more information"
    fi
    
    echo "=================================="
}

# Main execution
main() {
    log_info "Starting comprehensive health check..."
    
    cd "$DOCKER_DIR"
    
    local overall_healthy=true
    
    # Run all health checks
    check_web_service || overall_healthy=false
    echo ""
    
    check_database_service || overall_healthy=false
    echo ""
    
    check_application_health || overall_healthy=false
    echo ""
    
    check_disk_space || overall_healthy=false
    echo ""
    
    check_docker_resources || overall_healthy=false
    echo ""
    
    # Generate report
    if [ "$overall_healthy" = true ]; then
        generate_report "HEALTHY"
        exit 0
    else
        generate_report "UNHEALTHY"
        exit 1
    fi
}

# Handle script arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Perform comprehensive health check of the Docker environment"
        echo ""
        echo "Options:"
        echo "  --help, -h     Show this help message"
        echo "  --quiet, -q    Minimal output (exit codes only)"
        echo "  --watch, -w    Continuous monitoring mode"
        echo ""
        exit 0
        ;;
    --quiet|-q)
        exec > /dev/null 2>&1
        ;;
    --watch|-w)
        while true; do
            clear
            main
            sleep 30
        done
        ;;
esac

main "$@"