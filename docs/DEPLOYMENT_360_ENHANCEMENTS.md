# 360-Degree Enhancements Deployment Guide

## Overview
Complete step-by-step deployment guide for implementing the 360-degree performance management system enhancements. This covers production deployment checklist, database migrations, configuration management, and rollback procedures.

## Pre-Deployment Checklist

### ✅ System Requirements Verification
**Before deployment, ensure:**
- PHP 8.1+ with PDO extension
- MySQL 8.0+ with JSON support  
- Apache 2.4+ or Nginx 1.20+
- SSL certificate configured
- 2GB available memory
- 1GB storage space

### ✅ Security Prerequisites
- [ ] Database backup completed
- [ ] File system backup created
- [ ] SSL certificates valid
- [ ] Security headers configured
- [ ] Rate limiting implemented
- [ ] Environment variables secured

### ✅ Testing Environment
- [ ] Staging environment deployed
- [ ] Feature flags configured
- [ ] Smoke tests passed
- [ ] User acceptance testing complete
- [ ] Documentation reviewed
- [ ] Rollback plan tested

## Database Migration Scripts

### 1. Schema Migration Script
```sql
-- /sql/migrate_360_enhancements.sql
-- Comprehensive migration for 360-degree enhancements

START TRANSACTION;

-- Step 1: Create enhancement tables
CREATE TABLE IF NOT EXISTS `self_assessment_configs` (
    `config_id` INT AUTO_INCREMENT PRIMARY KEY,
    `competency_framework_id` INT NOT NULL,
    `period_id` INT NOT NULL,
    `config_data` JSON NOT NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (`active`),
    INDEX idx_period (`period_id`)
);

CREATE TABLE IF NOT EXISTS `self_assessment_responses` (
    `response_id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `config_id` INT NOT NULL,
    `responses` JSON NOT NULL,
    `status` ENUM('draft','submitted','reviewed','archived') DEFAULT 'draft',
    `submitted_at` TIMESTAMP NULL,
    `manager_review` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employee`(`employee_id`),
    FOREIGN KEY (`config_id`) REFERENCES `self_assessment_configs`(`config_id`),
    INDEX idx_employee_status (`employee_id`,`status`),
    INDEX idx_created (`created_at`)
);

-- Step 2: Kudos system tables
CREATE TABLE IF NOT EXISTS `kudos_categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(255) NOT NULL,
    `category_description` TEXT,
    `points_value` INT DEFAULT 10,
    `icon_url` VARCHAR(500),
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (`active`)
);

CREATE TABLE IF NOT EXISTS `kudos_transactions` (
    `kudos_id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `recipient_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `points` INT DEFAULT 10,
    `message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_public` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`sender_id`) REFERENCES `employee`(`employee_id`),
    FOREIGN KEY (`recipient_id`) REFERENCES `employee`(`employee_id`),
    FOREIGN KEY (`category_id`) REFERENCES `kudos_categories`(`category_id`),
    INDEX idx_recipient (`recipient_id`),
    INDEX idx_sender (`sender_id`),
    INDEX idx_created (`created_at`)
);

-- Step 3: OKR management tables
CREATE TABLE IF NOT EXISTS `okr_objectives` (
    `objective_id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('draft','active','completed','cancelled','archived') DEFAULT 'draft',
    `visibility` ENUM('private','team','public') DEFAULT 'team',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employee`(`employee_id`),
    INDEX idx_employee (`employee_id`),
    INDEX idx_dates (`start_date`,`end_date`),
    INDEX idx_status (`status`)
);

CREATE TABLE IF NOT EXISTS `okr_key_results` (
    `key_result_id` INT AUTO_INCREMENT PRIMARY KEY,
    `objective_id` INT NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `target_value` DECIMAL(10,2),
    `current_value` DECIMAL(10,2),
    `unit` VARCHAR(50),
    `weight` DECIMAL(3,2) DEFAULT 1.0,
    `status` ENUM('on_track','at_risk','behind','completed','not_started') DEFAULT 'not_started',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`objective_id`) REFERENCES `okr_objectives`(`objective_id`) ON DELETE CASCADE,
    INDEX idx_objective (`objective_id`),
    INDEX idx_status (`status`)
);

-- Step 4: Upward feedback system
CREATE TABLE IF NOT EXISTS `upward_feedback_sessions` (
    `session_id` INT AUTO_INCREMENT PRIMARY KEY,
    `manager_id` INT NOT NULL,
    `period_id` INT NOT NULL,
    `anonymous` BOOLEAN DEFAULT TRUE,
    `minimum_responses` INT DEFAULT 3,
    `status` ENUM('active','completed','cancelled') DEFAULT 'active',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`manager_id`) REFERENCES `employee`(`employee_id`),
    INDEX idx_manager_period (`manager_id`,`period_id`)
);

CREATE TABLE IF NOT EXISTS `upward_feedback_responses` (
    `response_id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT NOT NULL,
    `employee_role` VARCHAR(100), -- for anonymous tracking without identity
    `competency_ratings` JSON NOT NULL,
    `overall_rating` DECIMAL(3,2),
    `comments` TEXT,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `upward_feedback_sessions`(`session_id`) ON DELETE CASCADE,
    INDEX idx_session (`session_id`),
    INDEX idx_timeframe (`submitted_at`)
);

-- Step 5: IDP system tables
CREATE TABLE IF NOT EXISTS `idp_master` (
    `idp_id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `description` TEXT,
    `start_date` DATE NOT NULL,
    `target_date` DATE NOT NULL,
    `current_status` ENUM('draft','active','completed','on_hold','archived') DEFAULT 'draft',
    `achievement_level` ENUM('exceeding','meeting','developing','needs_improvement'),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employee`(`employee_id`),
    INDEX idx_employee_date (`employee_id`,`start_date`),
    INDEX idx_status (`current_status`)
);

CREATE TABLE IF NOT EXISTS `idp_development_actions` (
    `action_id` INT AUTO_INCREMENT PRIMARY KEY,
    `idp_id` INT NOT NULL,
    `action_type` ENUM('training','mentoring','experience','networking','self_study') NOT NULL,
    `description` TEXT,
    `deadline` DATE,
    `priority` ENUM('high','medium','low') DEFAULT 'medium',
    `status` ENUM('planned','in_progress','completed','cancelled') DEFAULT 'planned',
    `progress_note` TEXT,
    `evidence_url` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`idp_id`) REFERENCES `idp_master`(`idp_id`) ON DELETE CASCADE,
    INDEX idx_idp (`idp_id`),
    INDEX idx_status (`status`),
    INDEX idx_deadline (`deadline`)
);

COMMIT;
```

### 2. Configuration Data Seed Script