#!/bin/bash
# Database Backup Script
# Creates timestamped backup of the performance evaluation database

set -e

# Configuration
BACKUP_DIR="backups/$(date +%Y%m%d)"
DB_NAME="performance_evaluation"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🔄 Starting database backup...${NC}"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Check if Docker is running
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

# Check if MySQL container is running
MYSQL_CONTAINER=$(docker ps --format "{{.Names}}" | grep mysql)
if [ -z "$MYSQL_CONTAINER" ]; then
    echo -e "${RED}❌ MySQL container is not running. Please start the environment first.${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Using MySQL container: $MYSQL_CONTAINER${NC}"

# Get database credentials from environment
if [ -f .env ]; then
    source .env
else
    echo -e "${YELLOW}⚠️  .env file not found, using default credentials${NC}"
    DB_ROOT_PASSWORD="root_dev_password"
fi

echo -e "${YELLOW}📊 Creating schema-only backup...${NC}"
# Create schema-only backup
docker exec "$MYSQL_CONTAINER" mysqladmin ping -h localhost -u root -p"$DB_ROOT_PASSWORD" --silent
docker exec "$MYSQL_CONTAINER" mysqldump -u root -p"$DB_ROOT_PASSWORD" --no-data --routines --triggers "$DB_NAME" > "$BACKUP_DIR/schema_only_$TIMESTAMP.sql"

echo -e "${YELLOW}💾 Creating full data backup...${NC}"
# Create full backup with data
docker exec "$MYSQL_CONTAINER" mysqldump -u root -p"$DB_ROOT_PASSWORD" --routines --triggers --single-transaction "$DB_NAME" > "$BACKUP_DIR/full_backup_$TIMESTAMP.sql"

# Create backup info file
cat > "$BACKUP_DIR/backup_info.txt" << EOF
Backup Information
==================
Date: $(date)
Database: $DB_NAME
Schema File: schema_only_$TIMESTAMP.sql
Full Backup: full_backup_$TIMESTAMP.sql
Docker Container: $MYSQL_CONTAINER

Restore Instructions:
====================
1. Schema only: docker exec -i $MYSQL_CONTAINER mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_NAME" < "$BACKUP_DIR/schema_only_$TIMESTAMP.sql"
2. Full restore: docker exec -i $MYSQL_CONTAINER mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_NAME" < "$BACKUP_DIR/full_backup_$TIMESTAMP.sql"
EOF

# Get file sizes
SCHEMA_SIZE=$(du -h "$BACKUP_DIR/schema_only_$TIMESTAMP.sql" | cut -f1)
FULL_SIZE=$(du -h "$BACKUP_DIR/full_backup_$TIMESTAMP.sql" | cut -f1)

echo -e "${GREEN}✅ Backup completed successfully!${NC}"
echo -e "${GREEN}📁 Backup location: $BACKUP_DIR${NC}"
echo -e "${GREEN}📊 Schema backup: $SCHEMA_SIZE${NC}"
echo -e "${GREEN}💾 Full backup: $FULL_SIZE${NC}"
echo -e "${YELLOW}📋 Backup info saved to: $BACKUP_DIR/backup_info.txt${NC}"