#!/bin/bash

# Docker Development Environment - Setup Verification Script
# Performance Evaluation System

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

check_file() {
    local file="$1"
    local description="$2"
    
    if [ -f "$file" ]; then
        log_success "$description exists: $file"
        return 0
    else
        log_error "$description missing: $file"
        return 1
    fi
}

check_directory() {
    local dir="$1"
    local description="$2"
    
    if [ -d "$dir" ]; then
        log_success "$description exists: $dir"
        return 0
    else
        log_error "$description missing: $dir"
        return 1
    fi
}

check_executable() {
    local file="$1"
    local description="$2"
    
    if [ -x "$file" ]; then
        log_success "$description is executable: $file"
        return 0
    else
        log_error "$description is not executable: $file"
        return 1
    fi
}

main() {
    log_info "Verifying Docker development environment setup..."
    echo ""
    
    local all_good=true
    
    # Check core Docker files
    log_info "Checking Docker configuration files..."
    check_file "docker-compose.yml" "Docker Compose configuration" || all_good=false
    check_file "docker-compose.override.yml" "Docker Compose override" || all_good=false
    check_file ".env.example" "Environment template" || all_good=false
    check_file "Makefile" "Makefile" || all_good=false
    echo ""
    
    # Check Docker directory structure
    log_info "Checking Docker directory structure..."
    check_directory "docker" "Docker directory" || all_good=false
    check_directory "docker/web" "Web container directory" || all_good=false
    check_directory "docker/scripts" "Scripts directory" || all_good=false
    check_directory "docker/logs" "Logs directory" || all_good=false
    check_directory "docker/logs/apache" "Apache logs directory" || all_good=false
    check_directory "docker/logs/php" "PHP logs directory" || all_good=false
    check_directory "docker/logs/mysql" "MySQL logs directory" || all_good=false
    echo ""
    
    # Check web container files
    log_info "Checking web container configuration..."
    check_file "docker/web/Dockerfile" "Web Dockerfile" || all_good=false
    check_file "docker/web/apache.conf" "Apache configuration" || all_good=false
    check_file "docker/web/php.ini" "PHP configuration" || all_good=false
    check_file "docker/web/health-check.php" "Health check endpoint" || all_good=false
    echo ""
    
    # Check automation scripts
    log_info "Checking automation scripts..."
    check_executable "docker/scripts/deploy.sh" "Deploy script" || all_good=false
    check_executable "docker/scripts/reset.sh" "Reset script" || all_good=false
    check_executable "docker/scripts/destroy.sh" "Destroy script" || all_good=false
    check_executable "docker/scripts/health-check.sh" "Health check script" || all_good=false
    echo ""
    
    # Check documentation
    log_info "Checking documentation..."
    check_file "README_DOCKER.md" "Docker README" || all_good=false
    check_file "DOCKER_SETUP_COMPLETE.md" "Setup completion guide" || all_good=false
    check_file "docs/DOCKER_DEVELOPMENT_PLAN.md" "Development plan" || all_good=false
    echo ""
    
    # Check environment setup
    log_info "Checking environment setup..."
    if [ -f ".env" ]; then
        log_success "Environment file exists: .env"
    else
        log_warning "Environment file not found (run 'make install' first)"
    fi
    echo ""
    
    # Check prerequisites
    log_info "Checking prerequisites..."
    if command -v docker &> /dev/null; then
        local docker_version=$(docker --version)
        log_success "Docker is installed: $docker_version"
    else
        log_error "Docker is not installed"
        all_good=false
    fi
    
    if docker compose version &> /dev/null; then
        local compose_version=$(docker compose version)
        log_success "Docker Compose is available: $compose_version"
    elif command -v docker-compose &> /dev/null; then
        local compose_version=$(docker-compose --version)
        log_success "Docker Compose is installed: $compose_version"
    else
        log_error "Docker Compose is not available"
        all_good=false
    fi
    
    if command -v make &> /dev/null; then
        local make_version=$(make --version | head -n1)
        log_success "Make is installed: $make_version"
    else
        log_error "Make is not installed"
        all_good=false
    fi
    echo ""
    
    # Final result
    if [ "$all_good" = true ]; then
        log_success "✅ All checks passed! Docker environment is ready."
        echo ""
        echo "🚀 Next steps:"
        echo "  1. Run 'make up' to start the environment"
        echo "  2. Access http://localhost:8080 in your browser"
        echo "  3. Login with admin/admin123"
        echo ""
        exit 0
    else
        log_error "❌ Some checks failed. Please review the errors above."
        echo ""
        echo "🔧 Troubleshooting:"
        echo "  1. Ensure all files are properly created"
        echo "  2. Check file permissions (scripts should be executable)"
        echo "  3. Install missing prerequisites"
        echo ""
        exit 1
    fi
}

main "$@"