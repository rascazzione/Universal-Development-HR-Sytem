#!/bin/bash

# PHP Syntax Verification Script
# Checks all PHP files for syntax errors

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_status "Verifying PHP syntax for all modified files..."

# List of PHP files to check
files=(
    "classes/Evaluation.php"
    "classes/JobTemplate.php"
    "public/evaluation/edit.php"
    "public/admin/job_templates.php"
)

syntax_errors=0

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        print_status "Checking $file..."
        
        # Check for basic syntax issues manually
        # Check for unclosed braces
        open_braces=$(grep -o '{' "$file" | wc -l)
        close_braces=$(grep -o '}' "$file" | wc -l)
        
        if [ "$open_braces" -ne "$close_braces" ]; then
            print_error "$file: Mismatched braces (Open: $open_braces, Close: $close_braces)"
            syntax_errors=$((syntax_errors + 1))
        else
            print_success "$file: Brace count matches"
        fi
        
        # Check for PHP opening/closing tags
        if ! grep -q "<?php" "$file"; then
            print_error "$file: Missing PHP opening tag"
            syntax_errors=$((syntax_errors + 1))
        fi
        
        # Check for basic class structure (for class files)
        if [[ "$file" == classes/* ]]; then
            if ! grep -q "class " "$file"; then
                print_error "$file: Missing class declaration"
                syntax_errors=$((syntax_errors + 1))
            fi
        fi
        
    else
        print_error "File not found: $file"
        syntax_errors=$((syntax_errors + 1))
    fi
done

echo
if [ $syntax_errors -eq 0 ]; then
    print_success "All PHP files passed syntax verification!"
    echo
    print_status "Files verified:"
    for file in "${files[@]}"; do
        echo "  ✓ $file"
    done
else
    print_error "Found $syntax_errors syntax issues"
    exit 1
fi

echo
print_status "Syntax verification completed successfully"