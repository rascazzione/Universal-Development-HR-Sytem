# PHP Application Call Graph

This document will map the dependencies between PHP files in the application, providing a visual representation of how different components interact with each other.

## classes/CompanyKPI.php

### Dependencies
- Uses database functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Depends on database schema tables: `company_kpis`, `users`, `job_template_kpis`, `job_position_templates`, `evaluation_kpi_results`, `evaluations`
- Requires external functionality: CSV file handling (`fopen`, `fgetcsv`, `fclose`)
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `getKPIs()` - queries company_kpis table with users join
- `getKPIById()` - queries company_kpis table with users join
- `getKPICategories()` - queries company_kpis table
- `createKPI()` - inserts into company_kpis table
- `updateKPI()` - updates company_kpis table
- `deleteKPI()` - updates company_kpis table
- `getKPIUsage()` - joins job_template_kpis and job_position_templates tables
- `calculateKPIScore()` - calculation logic for KPI scoring
- `getKPIStatistics()` - queries evaluation_kpi_results and evaluations tables
- `importKPIsFromCSV()` - reads CSV files and creates KPIs
- `exportKPIsToCSV()` - exports KPI data to CSV format

### External Function Dependencies
- Database interaction functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- File I/O functions: `fopen`, `fgetcsv`, `fclose`
- Exception handling: `Exception`

### Database Schema Dependencies
- `company_kpis` table (main KPI data)
- `users` table (for created_by user info)
- `job_template_kpis` table (KPI usage in job templates)
- `job_position_templates` table (job template info)
- `evaluation_kpi_results` table (KPI performance data)
- `evaluations` table (evaluation data)

### Usage Context
- Used by admin/kpis.php (admin interface for KPI management)
- Called by DashboardAnalytics.php for KPI statistics
- Used by ReportGenerator.php for generating reports
- Accessed via API endpoints in public/api/ directory

### Notes
- This class is central to company KPI management
- Relies heavily on database interactions
- Implements both CRUD operations and business logic for KPI scoring
- Supports CSV import/export functionality

## classes/CompanyValues.php

### Dependencies
- Uses database functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Depends on database schema tables: `company_values`, `users`, `job_template_values`, `evaluation_value_results`, `evaluations`
- Requires external functionality: CSV file handling (`fopen`, `fgetcsv`, `fclose`)
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `getValues()` - queries company_values table with users join
- `getValueById()` - queries company_values table with users join
- `createValue()` - inserts into company_values table
- `updateValue()` - updates company_values table
- `deleteValue()` - updates company_values table
- `reorderValues()` - updates company_values table
- `getValueUsage()` - joins job_template_values and job_position_templates tables
- `getValueStatistics()` - queries evaluation_value_results and evaluations tables
- `getAllValuesStatistics()` - queries company_values, evaluation_value_results, and evaluations tables
- `calculateValueScore()` - calculation logic for value scoring
- `getValueBehaviors()` - returns default behaviors based on value name
- `importValuesFromCSV()` - reads CSV files and creates values
- `exportValuesToCSV()` - exports value data to CSV format

### External Function Dependencies
- Database interaction functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- File I/O functions: `fopen`, `fgetcsv`, `fclose`
- Exception handling: `Exception`

### Database Schema Dependencies
- `company_values` table (main value data)
- `users` table (for created_by user info)
- `job_template_values` table (value usage in job templates)
- `job_position_templates` table (job template info)
- `evaluation_value_results` table (value performance data)
- `evaluations` table (evaluation data)

### Usage Context
- Used by admin/values.php (admin interface for value management)
- Called by DashboardAnalytics.php for value statistics
- Accessed via API endpoints in public/api/ directory

### Notes
- This class is central to company values management
- Relies heavily on database interactions
- Implements both CRUD operations and business logic for value scoring
- Supports CSV import/export functionality

---

## classes/Competency.php

### Dependencies
- Uses database functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Depends on database schema tables: `competencies`, `competency_categories`, `job_template_competencies`, `evaluation_competency_results`
- Requires external functionality: CSV file handling (`fopen`, `fgetcsv`, `fclose`)
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `getCategories()` - queries competency_categories table with joins
- `getCategoryById()` - queries competency_categories table with joins
- `createCategory()` - inserts into competency_categories table
- `updateCategory()` - updates competency_categories table
- `deleteCategory()` - updates competency_categories table
- `getCompetencies()` - queries competencies table with joins
- `getCompetencyById()` - queries competencies table with joins
- `createCompetency()` - inserts into competencies table
- `updateCompetency()` - updates competencies table
- `deleteCompetency()` - updates competencies table
- `getCompetencyTypes()` - returns predefined competency types
- `getCompetencyLevels()` - returns predefined competency levels
- `getLevelDescription()` - returns description for a competency level
- `getCompetencyUsage()` - joins job_template_competencies and job_position_templates tables
- `calculateCompetencyScore()` - calculation logic for competency scoring
- `getCompetencyStatistics()` - queries evaluation_competency_results and evaluations tables
- `importCompetenciesFromCSV()` - reads CSV files and creates competencies
- `exportCompetenciesToCSV()` - exports competency data to CSV format

### External Function Dependencies
- Database interaction functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- File I/O functions: `fopen`, `fgetcsv`, `fclose`
- Exception handling: `Exception`

### Database Schema Dependencies
- `competencies` table (main competency data)
- `competency_categories` table (competency categories)
- `job_template_competencies` table (competency usage in job templates)
- `job_position_templates` table (job template info)
- `evaluation_competency_results` table (competency performance data)
- `evaluations` table (evaluation data)

### Usage Context
- Used by admin/competencies.php (admin interface for competency management)
- Called by DashboardAnalytics.php for competency statistics
- Accessed via API endpoints in public/api/ directory

### Notes
- This class handles skills, knowledge, and competencies catalog
- Relies heavily on database interactions
- Implements both CRUD operations and business logic for competency scoring
- Supports CSV import/export functionality

---

## classes/DashboardAnalytics.php

### Dependencies
- Includes: `GrowthEvidenceJournal.php`, `Evaluation.php`, `Employee.php`, `EvaluationPeriod.php`
- Uses database functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Uses database connection function: `getDbConnection()`
- Depends on database schema tables: `growth_evidence_entries`, `employees`, `evaluations`, `evaluation_periods`
- Uses external classes: `GrowthEvidenceJournal`, `Evaluation`, `Employee`, `EvaluationPeriod`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `getManagerDashboardData()` - main method for manager dashboard analytics
- `getEmployeeDashboardData()` - main method for employee dashboard analytics
- `getHRAnalyticsDashboard()` - main method for HR analytics dashboard
- `getTeamEvidenceTrends()` - analyzes evidence trends for team members
- `getTeamPerformanceInsights()` - calculates team performance insights
- `getFeedbackFrequencyAnalytics()` - analyzes feedback frequency
- `getEvidenceQualityIndicators()` - evaluates evidence quality
- `getTeamComparisonAnalytics()` - compares team members across dimensions
- `getCoachingOpportunities()` - identifies coaching opportunities
- `getPersonalFeedbackSummary()` - summarizes personal feedback
- `getPersonalPerformanceTrends()` - analyzes personal performance trends
- `getDevelopmentRecommendations()` - provides development recommendations
- `getGoalProgressTracking()` - tracks goal progress
- `getPeerComparisonInsights()` - provides peer comparison insights
- `getEvidenceHistoryVisualization()` - visualizes evidence history
- `getCurrentOrFilteredPeriod()` - gets current or filtered period
- `calculatePercentile()` - helper method for percentile calculation
- `getOrganizationalEvidencePatterns()` - analyzes organizational patterns
- `getDepartmentComparisonAnalytics()` - compares departments
- `getSystemUsageAnalytics()` - analyzes system usage
- `getPerformanceDistributionAnalysis()` - analyzes performance distribution
- `getEvidenceBasedReportingInsights()` - provides reporting insights
- `getSystemAdoptionMetrics()` - measures system adoption
- Helper methods for calculations and utilities

### External Function Dependencies
- Database interaction functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Database connection function: `getDbConnection()`
- Exception handling: `Exception`

### Database Schema Dependencies
- `growth_evidence_entries` table (evidence data)
- `employees` table (employee data)
- `evaluations` table (evaluation data)
- `evaluation_periods` table (evaluation periods)

### Usage Context
- Used by dashboard pages: public/dashboard/employee.php, public/dashboard/manager.php, public/dashboard/hr.php
- Called by API endpoints in public/api/ directory
- Integrates with EvidenceManager, Evaluation, Employee, and EvaluationPeriod classes

### Notes
- This class implements Phase 2: Dashboard & Analytics Implementation
- Central hub for all dashboard analytics functionality
- Integrates with multiple other classes for comprehensive analytics
- Provides evidence-based performance insights

---

## classes/Department.php

### Dependencies
- Uses database functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Uses database connection function: `getDbConnection()`
- Depends on database schema tables: `departments`, `employees`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `getDepartments()` - queries departments table with manager joins
- `getAllDepartments()` - queries departments table with manager joins
- `getDepartmentById()` - queries departments table
- `createDepartment()` - inserts into departments table
- `updateDepartment()` - updates departments table
- `deleteDepartment()` - soft deletes from departments table
- `restoreDepartment()` - restores departments table
- `getAvailableManagers()` - queries employees table for available managers
- `getDepartmentStats()` - calculates department statistics

### External Function Dependencies
- Database interaction functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Database connection function: `getDbConnection()`
- Exception handling: `Exception`

### Database Schema Dependencies
- `departments` table (department data)
- `employees` table (manager data)

### Usage Context
- Used by admin/departments.php (admin interface for department management)
- Called by Employee.php for department data
- Accessed via API endpoints in public/api/ directory

### Notes
- This class handles department management
- Provides CRUD operations for departments
- Integrates with employee data for manager assignments
- Supports soft delete functionality

---

## classes/Employee.php

### Dependencies
- Uses database functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Uses database connection function: `getDbConnection()`
- Includes: `Department.php`
- Depends on database schema tables: `employees`, `users`, `employees` (self-join)
- Uses external class: `Department`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `createEmployee()` - inserts into employees table
- `updateEmployee()` - updates employees table
- `getEmployeeById()` - queries employees table with user and manager joins
- `getEmployeeByUserId()` - queries employees table with user joins
- `getEmployees()` - queries employees table with pagination and filtering
- `getAllEmployees()` - queries employees table with pagination and filtering
- `getTeamMembers()` - queries employees table for team members
- `getDepartments()` - calls Department class to get departments
- `getManagers()` - queries employees table for managers
- `getPotentialManagers()` - queries employees table for potential managers
- `getEmployeeHierarchy()` - builds hierarchical tree structure
- `buildHierarchyTree()` - helper method for building hierarchy
- `generateEmployeeNumber()` - generates unique employee numbers
- `employeeNumberExists()` - checks if employee number exists
- `deleteEmployee()` - soft deletes from employees table
- `getEmployeeStats()` - calculates employee statistics
- `searchEmployees()` - searches employees by query

### External Function Dependencies
- Database interaction functions: `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Database connection function: `getDbConnection()`
- Exception handling: `Exception`

### Database Schema Dependencies
- `employees` table (employee data)
- `users` table (user authentication data)
- Self-join on employees table (manager relationship)

### Usage Context
- Used by employee management pages: public/employees/list.php, public/employees/view.php, public/employees/create.php, public/employees/edit.php
- Called by DashboardAnalytics.php for team member data
- Called by Department.php for department data
- Accessed via API endpoints in public/api/ directory

### Notes
- This class handles employee management
- Provides comprehensive CRUD operations for employees
- Integrates with user authentication system
- Supports hierarchical organization structure
- Implements soft delete functionality


## classes/Evaluation.php

### Dependencies
- Includes: Employee.php, EvaluationPeriod.php, GrowthEvidenceJournal.php
- Uses database functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Uses external functions: `logActivity`, `error_log`
- Uses external classes: `Employee`, `EvaluationPeriod`, `GrowthEvidenceJournal`
- Depends on database schema tables: `evaluations`, `evidence_evaluation_results`, `growth_evidence_entries`, `evaluation_periods`, `employees`, `users`
- Uses session variables: `$_SESSION`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `createEvaluation()` - creates new evidence-based evaluation
- `createFromEvidenceJournal()` - creates evaluation from evidence journal
- `aggregateEvidence()` - aggregates evidence for an evaluation
- `updateEvaluation()` - updates evaluation metadata
- `getEvaluationById()` - retrieves evaluation by ID
- `getEvidenceEvaluation()` - gets evidence-based evaluation data
- `getEvidenceResults()` - gets evidence results for evaluation
- `getEvidenceEntriesByDimension()` - gets evidence entries by dimension
- `getEvaluations()` - gets evaluations with pagination and filtering
- `getEvaluatorEvaluations()` - gets evaluations where user is evaluator
- `getManagerEvaluations()` - gets evaluations for a specific manager
- `getEmployeeEvaluations()` - gets evaluations for a specific employee
- `getJobTemplateEvaluation()` - compatibility method for legacy system
- `batchAggregateEvidence()` - batch aggregates evidence for multiple evaluations
- `reAggregateEvidenceForPeriod()` - re-aggregates evidence for all evaluations in a period
- `getEvidenceAggregationStats()` - gets evidence aggregation statistics
- `validateEvidenceIntegrity()` - validates evidence data integrity

### External Function Dependencies
- Database interaction functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Logging functions: `logActivity`, `error_log`
- Exception handling: `Exception`

### Database Schema Dependencies
- `evaluations` table (main evaluation data)
- `evidence_evaluation_results` table (aggregated evidence results)
- `growth_evidence_entries` table (raw evidence entries)
- `evaluation_periods` table (evaluation period data)
- `employees` table (employee data)
- `users` table (user authentication data)

### Usage Context
- Used by evaluation management pages: public/evaluation/create.php, public/evaluation/edit.php, public/evaluation/list.php, public/evaluation/view.php
- Called by DashboardAnalytics.php for evaluation data
- Called by EvaluationPeriod.php for auto-generation of evaluations
- Accessed via API endpoints in public/api/ directory

### Notes
- This class implements evidence-based evaluation management
- Central to continuous performance system implementation
- Integrates with evidence journal and evaluation period systems
- Provides advanced evidence aggregation algorithms

---

## classes/EvaluationPeriod.php

### Dependencies
- Includes: config/config.php
- Uses database functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Uses external functions: `logActivity`, `error_log`
- Uses class instances: none directly
- Depends on database schema tables: `evaluation_periods`
- Uses session variables: `$_SESSION`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `createPeriod()` - creates new evaluation period
- `updatePeriod()` - updates evaluation period
- `getPeriodById()` - retrieves period by ID
- `getPeriods()` - gets periods with pagination and filtering
- `getActivePeriods()` - gets active periods
- `getCurrentPeriod()` - gets current active period
- `getUpcomingPeriods()` - gets upcoming periods
- `getPeriodsByYear()` - gets periods by year
- `hasOverlappingPeriod()` - checks for overlapping periods
- `deletePeriod()` - deletes period
- `activatePeriod()` - activates period
- `completePeriod()` - completes period
- `archivePeriod()` - archives period
- `getPeriodStats()` - gets period statistics with evidence metrics
- `generatePeriodsForYear()` - generates automatic periods for a year
- `getAvailableYears()` - gets available years
- `isPeriodEditable()` - checks if period is editable
- `getManagerDashboard()` - gets manager dashboard data for period
- `autoGenerateEvaluations()` - auto-generates evaluations for all employees in period

### External Function Dependencies
- Database interaction functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Logging functions: `logActivity`, `error_log`
- Exception handling: `Exception`

### Database Schema Dependencies
- `evaluation_periods` table (evaluation period data)

### Usage Context
- Used by period management pages: public/admin/periods.php
- Called by Evaluation.php for period-related operations
- Called by EvidenceManager.php for period-based operations
- Accessed via API endpoints in public/api/ directory

### Notes
- This class manages evaluation periods for the continuous performance system
- Supports quarterly, semi-annual, and annual period generation
- Provides period lifecycle management (create, activate, complete, archive)
- Integrates with evaluation system for period-based operations

---

## classes/EvidenceManager.php

### Dependencies
- Includes: GrowthEvidenceJournal.php
- Uses database functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Uses external functions: `logActivity`, `error_log`
- Uses external class: `GrowthEvidenceJournal` (extends)
- Depends on database schema tables: `growth_evidence_entries`, `evidence_archive`, `evidence_entry_tags`, `evidence_tags`, `evidence_approvals`
- Uses session variables: `$_SESSION`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `advancedSearch()` - advanced search for evidence entries
- `bulkOperation()` - bulk operations for evidence entries
- `archiveEntry()` - archives evidence entry
- `restoreEntry()` - restores archived evidence entry
- `addTagsToEntry()` - adds tags to evidence entry
- `removeTagsFromEntry()` - removes tags from evidence entry
- `getAvailableTags()` - gets available evidence tags
- `createTag()` - creates new evidence tag
- `getEvidenceStatistics()` - gets evidence statistics for dashboard
- `getEntriesRequiringApproval()` - gets evidence entries requiring approval
- `processApproval()` - approves or rejects evidence entry

### External Function Dependencies
- Database interaction functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Logging functions: `logActivity`, `error_log`
- Exception handling: `Exception`

### Database Schema Dependencies
- `growth_evidence_entries` table (raw evidence entries)
- `evidence_archive` table (archived evidence entries)
- `evidence_entry_tags` table (tags for evidence entries)
- `evidence_tags` table (available evidence tags)
- `evidence_approvals` table (approval status for evidence entries)

### Usage Context
- Used by evidence management pages: public/evidence/manage.php, public/evidence/search.php
- Called by GrowthEvidenceJournal.php for extended functionality
- Accessed via API endpoints in public/api/ directory

### Notes
- This class extends GrowthEvidenceJournal with advanced features
- Implements evidence tagging, archiving, and approval workflows
- Provides bulk operations and advanced search capabilities
- Supports evidence management for the growth evidence system

---

## classes/GrowthEvidenceJournal.php

### Dependencies
- Includes: config/config.php
- Uses database functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Uses external functions: `logActivity`, `error_log`
- Uses class instances: none directly
- Depends on database schema tables: `growth_evidence_entries`
- Uses session variables: `$_SESSION`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `createEntry()` - creates new evidence entry
- `updateEntry()` - updates evidence entry
- `getEntryById()` - retrieves entry by ID
- `getEmployeeJournal()` - gets employee's journal entries
- `getManagerEntries()` - gets entries by manager
- `getEvidenceByDimension()` - gets evidence by dimension for an employee
- `getEvidenceSummary()` - gets evidence summary statistics
- `deleteEntry()` - deletes evidence entry
- `getRecentEntries()` - gets recent entries for dashboard
- `getEntriesByDateRange()` - gets entries by date range for reporting
- `getDimensionStatistics()` - gets evidence statistics by dimension
- `getEvidenceWithRecencyWeighting()` - gets evidence with recency weighting
- `getEvidenceQualityMetrics()` - gets evidence quality metrics for validation
- `batchGetEvidenceByDimension()` - batch retrieves evidence for multiple employees
- `getEvidenceTrendAnalysis()` - gets evidence trend analysis for an employee
- `validateEvidenceConsistency()` - validates evidence data consistency

### External Function Dependencies
- Database interaction functions: `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Logging functions: `logActivity`, `error_log`
- Exception handling: `Exception`

### Database Schema Dependencies
- `growth_evidence_entries` table (raw evidence entries)

### Usage Context
- Used by evidence management pages: public/evidence/manage.php, public/evidence/search.php
- Called by EvidenceManager.php for core functionality
- Called by Evaluation.php for evidence aggregation
- Called by DashboardAnalytics.php for evidence data
- Accessed via API endpoints in public/api/ directory

### Notes
- This class implements the core evidence journal functionality
- Central to the growth evidence system implementation
- Provides comprehensive evidence entry management
- Supports advanced analytics and reporting features

---

## classes/MediaManager.php

### Dependencies
- Includes: `config/config.php`
- Uses database functions: `insertRecord`, `fetchAll`
- Uses external functions: `error_log`, `pathinfo`, `uniqid`, `move_uploaded_file`, `mkdir`, `is_dir`, `imagecreatefromjpeg`, `imagecreatefrompng`, `imagecreatefromgif`, `imagesx`, `imagesy`, `imagecreatetruecolor`, `imagealphablending`, `imagesavealpha`, `imagecopyresampled`, `imagejpeg`, `imagepng`, `imagegif`, `imagedestroy`, `file_exists`, `unlink`
- Uses class instances: none directly
- Depends on database schema tables: `evidence_attachments`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `uploadFile()` - handles file uploads and stores metadata
- `validateFile()` - validates uploaded files
- `generateThumbnail()` - generates thumbnails for image files
- `getSecureUrl()` - generates secure URLs for file access
- `getUploadErrorMessage()` - gets upload error messages
- `getAttachmentsForEntry()` - retrieves attachments for an evidence entry
- `deleteAttachment()` - deletes attachment files and records
- `getAllowedFileTypes()` - gets allowed file types
- `getMaxFileSize()` - gets maximum file size
- `getFormattedFileSize()` - formats file size for display

### External Function Dependencies
- Database interaction functions: `insertRecord`, `fetchAll`
- File I/O functions: `pathinfo`, `uniqid`, `move_uploaded_file`, `mkdir`, `is_dir`, `file_exists`, `unlink`
- Image processing functions: `imagecreatefromjpeg`, `imagecreatefrompng`, `imagecreatefromgif`, `imagesx`, `imagesy`, `imagecreatetruecolor`, `imagealphablending`, `imagesavealpha`, `imagecopyresampled`, `imagejpeg`, `imagepng`, `imagegif`, `imagedestroy`
- Exception handling: `Exception`

### Database Schema Dependencies
- `evidence_attachments` table (stores file metadata)

### Usage Context
- Used by evidence management pages for file uploads
- Called by EvidenceManager for attachment handling
- Accessed via API endpoints for media handling

### Notes
- This class handles file uploads, validation, and storage for evidence entries
- Implements thumbnail generation for image files
- Provides secure file access mechanisms
- Integrates with the evidence management system

---

## classes/NotificationManager.php

### Dependencies
- Includes: `config/config.php`
- Uses database functions: `getDbConnection()`, `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Uses external functions: `error_log`, `json_encode`, `json_decode`, `date`, `strtotime`, `count`, `implode`
- Uses class instances: none directly
- Depends on database schema tables: `notifications`, `notification_templates`, `employees`, `users`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `createNotification()` - creates new notifications
- `createFromTemplate()` - creates notifications from templates
- `getTemplate()` - retrieves notification templates
- `replaceVariables()` - replaces variables in templates
- `getUserNotifications()` - retrieves user notifications
- `markAsRead()` - marks notifications as read
- `markAllAsRead()` - marks all notifications as read
- `deleteNotification()` - deletes notifications
- `getUnreadCount()` - gets unread notification count
- `sendFeedbackNotification()` - sends feedback submission notifications
- `sendEvidenceReminder()` - sends evidence reminders to managers
- `sendEvaluationSummary()` - sends evaluation period summaries
- `sendMilestoneAlert()` - sends milestone alerts
- `sendSystemAnnouncement()` - sends system announcements
- `cleanupExpiredNotifications()` - cleans up expired notifications
- `getNotificationStatistics()` - gets notification statistics

### External Function Dependencies
- Database interaction functions: `getDbConnection()`, `insertRecord`, `fetchAll`, `fetchOne`, `updateRecord`
- Data serialization functions: `json_encode`, `json_decode`
- Date/time functions: `date`, `strtotime`
- Array functions: `count`, `implode`
- Exception handling: `Exception`

### Database Schema Dependencies
- `notifications` table (stores notifications)
- `notification_templates` table (stores notification templates)
- `employees` table (for employee data)
- `users` table (for user data)

### Usage Context
- Used by API endpoints for notification handling
- Called by other classes for sending notifications
- Integrated with user management system

### Notes
- This class implements the notification system for the growth evidence system
- Supports multiple notification types and priorities
- Provides template-based notification creation
- Integrates with user management and evaluation systems

---

## classes/ReportGenerator.php

### Dependencies
- Includes: `config/config.php`, `EvidenceManager.php`
- Uses database functions: `getDbConnection()`, `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Uses external functions: `error_log`, `date`, `file_put_contents`, `json_encode`, `json_decode`, `mkdir`, `is_dir`, `ksort`
- Uses class instances: `EvidenceManager`
- Depends on database schema tables: `growth_evidence_entries`, `evidence_attachments`, `employees`, `notifications`, `scheduled_reports`, `report_history`, `evaluation_periods`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `generateEvidenceSummaryReport()` - generates evidence summary reports
- `generatePerformanceTrendsReport()` - generates performance trends reports
- `generateManagerOverviewReport()` - generates manager overview reports
- `generateCustomReport()` - generates custom reports
- `exportToPDF()` - exports reports to PDF format
- `exportToExcel()` - exports reports to Excel format
- `scheduleReport()` - schedules report generation
- `processScheduledReports()` - processes scheduled reports
- `prepareDimensionChart()` - prepares dimension chart data
- `prepareRatingChart()` - prepares rating chart data
- `prepareTimelineChart()` - prepares timeline chart data
- `processTrendData()` - processes trend data
- `analyzePerformanceTrends()` - analyzes performance trends
- `generateRecommendations()` - generates recommendations
- `generateManagerInsights()` - generates manager insights
- `buildCustomQuery()` - builds custom SQL queries
- `applyAggregations()` - applies data aggregations
- `groupData()` - groups data
- `generateReportHTML()` - generates HTML report
- `generateReportCSV()` - generates CSV report
- `calculateNextRunTime()` - calculates next run time for scheduled reports
- `generateReportByType()` - generates reports by type
- `saveReportHistory()` - saves report history

### External Function Dependencies
- Database interaction functions: `getDbConnection()`, `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- File I/O functions: `file_put_contents`, `mkdir`, `is_dir`
- Data serialization functions: `json_encode`, `json_decode`
- Date/time functions: `date`
- Array functions: `ksort`
- Exception handling: `Exception`

### Database Schema Dependencies
- `growth_evidence_entries` table (evidence data)
- `evidence_attachments` table (attachment data)
- `employees` table (employee data)
- `notifications` table (notification data)
- `scheduled_reports` table (scheduled reports)
- `report_history` table (report history)
- `evaluation_periods` table (period data)

### Usage Context
- Used by API endpoints for report generation
- Called by scheduled report processing
- Integrated with evidence management system

### Notes
- This class implements the reporting system for the growth evidence system
- Supports multiple report types (summary, performance trends, manager overview)
- Provides export functionality to PDF and Excel formats
- Implements scheduled report generation capabilities

---

## classes/User.php

### Dependencies
- Includes: `config/config.php`
- Uses database functions: `getDbConnection()`, `fetchAll`, `fetchOne`, `insertRecord`, `updateRecord`
- Uses external functions: `error_log`, `session_status()`, `session_start()`, `session_destroy()`, `session_regenerate_id()`, `time()`, `hash_password()`, `verifyPassword()`, `logActivity()`, `getFullName()`, `isValidEmail()`, `setFlashMessage()`, `getFlashMessages()`, `redirect()`, `filter_var()`, `htmlspecialchars()`, `trim()`, `random_bytes()`, `hash_equals()`, `date()`, `strtotime()`, `json_encode()`, `json_decode()`, `header()`, `exit()`
- Uses class instances: none directly
- Depends on database schema tables: `users`, `employees`, `audit_log`, `system_settings`
- Uses exception handling (`Exception`)

### Key Methods and Relationships
- `login()` - authenticates user login
- `logout()` - logs out user
- `isLoggedIn()` - checks if user is logged in
- `isSessionValid()` - checks if session is valid
- `setUserSession()` - sets user session variables
- `getCurrentUser()` - gets current user information
- `createUser()` - creates new user
- `updateUser()` - updates user information
- `getUserById()` - gets user by ID
- `getUsers()` - gets all users with pagination
- `userExists()` - checks if user exists
- `getUserByUsername()` - gets user by username
- `getUserByEmail()` - gets user by email
- `recordFailedAttempt()` - records failed login attempt
- `clearFailedAttempts()` - clears failed login attempts
- `isAccountLocked()` - checks if account is locked
- `updateLastLogin()` - updates last login timestamp
- `changePassword()` - changes user password

### External Function Dependencies
- Session management functions: `session_status()`, `session_start()`, `session_destroy()`, `session_regenerate_id()`
- Authentication functions: `hash_password()`, `verifyPassword()`
- Logging functions: `logActivity()`, `error_log`
- Input validation functions: `isValidEmail()`, `filter_var()`, `htmlspecialchars()`, `trim()`
- Security functions: `random_bytes()`, `hash_equals()`
- Date/time functions: `time()`, `date()`, `strtotime()`
- Data serialization functions: `json_encode()`, `json_decode()`
- Utility functions: `setFlashMessage()`, `getFlashMessages()`, `redirect()`, `header()`, `exit()`
- Exception handling: `Exception`

### Database Schema Dependencies
- `users` table (user authentication data)
- `employees` table (employee data)
- `audit_log` table (activity logs)
- `system_settings` table (application settings)

### Usage Context
- Used for authentication and user management
- Called by login/logout functionality
- Integrated with session management
- Used in authorization checks

### Notes
- This class handles user authentication and management
- Implements session management and security features
- Provides user creation, update, and deletion functionality
- Integrates with employee data and activity logging

---

## config/config.php

### Dependencies
- Includes: `database.php`
- Defines configuration constants: `APP_NAME`, `APP_VERSION`, `APP_URL`, `SESSION_TIMEOUT`, `PASSWORD_MIN_LENGTH`, `MAX_LOGIN_ATTEMPTS`, `LOGIN_LOCKOUT_TIME`, `MAX_FILE_SIZE`, `ALLOWED_FILE_TYPES`, `RECORDS_PER_PAGE`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `FROM_EMAIL`, `FROM_NAME`, `date_default_timezone_set()`
- Defines utility functions: `getAppSetting()`, `setAppSetting()`, `generateCSRFToken()`, `verifyCSRFToken()`, `sanitizeInput()`, `isValidEmail()`, `hashPassword()`, `verifyPassword()`, `logActivity()`, `formatDate()`, `getFullName()`, `hasPermission()`, `redirect()`, `setFlashMessage()`, `getFlashMessages()`
- Uses external functions: `session_start()`, `session_status()`, `session_regenerate_id()`, `session_destroy()`, `error_log()`, `password_hash()`, `password_verify()`, `filter_var()`, `htmlspecialchars()`, `trim()`, `random_bytes()`, `hash_equals()`, `date()`, `strtotime()`, `json_encode()`, `json_decode()`, `header()`, `exit()`
- Uses database functions: `fetchAll()`, `fetchOne()`, `executeQuery()`
- Depends on database schema tables: `system_settings`, `audit_log`

### Key Methods and Relationships
- Configuration constants for application settings
- Utility functions for application configuration
- Security functions for CSRF protection and input sanitization
- Authentication functions for password handling
- Logging functions for activity tracking
- Permission checking functions
- Session management functions
- Flash messaging functions
- Email validation functions
- Date formatting functions
- User name formatting functions
- Redirect functions

### External Function Dependencies
- Session management functions: `session_start()`, `session_status()`, `session_regenerate_id()`, `session_destroy()`
- Security functions: `password_hash()`, `password_verify()`, `random_bytes()`, `hash_equals()`
- Validation functions: `filter_var()`, `htmlspecialchars()`, `trim()`
- Date/time functions: `date()`, `strtotime()`
- Data serialization functions: `json_encode()`, `json_decode()`
- HTTP functions: `header()`, `exit()`
- Logging functions: `error_log()`
- Database interaction functions: `fetchAll()`, `fetchOne()`, `executeQuery()`

### Database Schema Dependencies
- `system_settings` table (application settings)
- `audit_log` table (activity logs)

### Usage Context
- Included by all PHP files that need configuration
- Provides global application settings
- Contains utility functions used throughout the application
- Central configuration point for the entire application

### Notes
- This file serves as the central configuration point for the application
- Defines all application-wide constants and utility functions
- Manages session and security configurations
- Provides database interaction utilities
</content>
## public/index.php

### Dependencies
- Includes: `includes/auth.php`
- Calls: `isAuthenticated()` function
- Redirects to: `/dashboard.php` or `/login.php`

### Key Methods and Relationships
- Redirects based on authentication status
- Uses `isAuthenticated()` function from auth.php
- Uses `redirect()` function from auth.php

### Usage Context
- Entry point for the application
- Redirects authenticated users to dashboard
- Redirects unauthenticated users to login

## public/login.php

### Dependencies
- Includes: `config/config.php`, `classes/User.php`, `includes/auth.php`
- Instantiates: `User` class
- Calls: `isAuthenticated()` function
- Uses: `protect_csrf()`, `sanitizeInput()`, `csrf_token()`, `getAppSetting()`, `redirect()`
- Uses: `is_array()`, `isset()`, `unset()`, `$_SESSION`, `$_POST`

### Key Methods and Relationships
- Handles user login form submission
- Creates new `User` object
- Calls `User::login()` method
- Uses CSRF protection
- Redirects on successful login

### Usage Context
- Login page for the application
- Processes login credentials
- Redirects to dashboard or displays errors

## public/logout.php

### Dependencies
- Includes: `includes/auth.php`
- Calls: `logout()` function
- Calls: `redirect()` function

### Key Methods and Relationships
- Calls `logout()` function to destroy session
- Redirects to login page

### Usage Context
- Logout functionality
- Destroys user session
- Redirects to login page

## public/test_logging.php

### Dependencies
- Includes: `includes/auth.php`
- Calls: `requireAuth()`, `isHRAdmin()`
- Uses: `file()`, `strpos()`, `htmlspecialchars()`, `ini_get()`, `error_log()`

### Key Methods and Relationships
- Requires HR admin authentication
- Reads and displays PHP error logs
- Displays recent log entries
- Writes test log entries

### Usage Context
- Debugging tool for viewing error logs
- Used by HR admins for system diagnostics

## public/admin/competencies.php

### Dependencies
- Includes: `includes/auth.php`, `classes/Competency.php`
- Instantiates: `Competency` class
- Calls: `requireAuth()`, `hasPermission()`, `setFlashMessage()`, `redirect()`
- Uses: `verifyCSRFToken()`, `sanitizeInput()`, `$_POST`, `$_GET`
- Uses: `array_slice()`, `explode()`, `count()`, `is_numeric()`, `is_array()`, `isset()`

### Key Methods and Relationships
- Manages competency categories and competencies
- Handles form submissions for CRUD operations
- Gets categories and competencies from Competency class
- Uses CSRF protection
- Implements filtering and pagination

### Usage Context
- Admin interface for managing competencies
- CRUD operations for competency categories and competencies
## public/admin/departments.php

### Dependencies
- Includes: `includes/auth.php`, `classes/Department.php`
- Instantiates: `Department` class
- Calls: `requireAuth()`, `hasPermission()`, `setFlashMessage()`, `redirect()`
- Uses: `verifyCSRFToken()`, `sanitizeInput()`, `formatDate()`, `generateCSRFToken()`
- Uses: `$_POST`, `$_SESSION`, `$_GET`
- Includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- Manages company departments
- Handles form submissions for CRUD operations (create, update, delete, restore)
- Gets departments and available managers from Department class
- Uses CSRF protection
- Implements soft delete functionality for departments

### Usage Context
- Admin interface for managing departments
- CRUD operations for company departments
- Used by HR administrators

## public/admin/job_templates.php

### Dependencies
- Includes: `includes/auth.php`, `classes/JobTemplate.php`, `classes/CompanyKPI.php`, `classes/Competency.php`, `classes/CompanyValues.php`, `classes/Department.php`
- Instantiates: `JobTemplate`, `CompanyKPI`, `Competency`, `CompanyValues`, `Department` classes
- Calls: `requireAuth()`, `hasPermission()`, `setFlashMessage()`, `redirect()`
- Uses: `verifyCSRFToken()`, `sanitizeInput()`, `formatDate()`, `generateCSRFToken()`
- Uses: `$_POST`, `$_SESSION`, `$_GET`
- Includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- Manages job position templates
- Handles form submissions for CRUD operations (create, update, delete)
- Manages KPIs, competencies, responsibilities, and values associated with job templates
- Gets available options for dropdowns from respective classes
- Uses AJAX for dynamic content updates
- Implements filtering and pagination

### Usage Context
- Admin interface for managing job templates
- CRUD operations for job position templates and their evaluation criteria
- Used by HR administrators

## public/admin/kpis.php

### Dependencies
- Includes: `includes/auth.php`, `classes/CompanyKPI.php`
- Instantiates: `CompanyKPI` class
- Calls: `requireAuth()`, `hasPermission()`, `setFlashMessage()`, `redirect()`
- Uses: `verifyCSRFToken()`, `sanitizeInput()`, `formatDate()`, `generateCSRFToken()`
- Uses: `$_POST`, `$_SESSION`, `$_GET`
- Includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- Manages company-wide Key Performance Indicators
- Handles form submissions for CRUD operations (create, update, delete)
- Gets KPIs and categories from CompanyKPI class
- Uses CSRF protection
- Implements filtering by category
- Supports CSV import/export functionality

### Usage Context
- Admin interface for managing KPIs
- CRUD operations for company KPIs
- Used by HR administrators

## public/admin/periods.php

### Dependencies
- Includes: `includes/auth.php`, `classes/EvaluationPeriod.php`
- Instantiates: `EvaluationPeriod` class
- Calls: `requireAuth()`, `isHRAdmin()`, `redirectTo()`, `setFlashMessage()`, `redirect()`
- Uses: `formatDate()`, `generateCSRFToken()`
- Uses: `$_POST`, `$_SESSION`, `$_GET`
- Includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- Manages evaluation periods
- Handles form submissions for CRUD operations (create, update status)
- Gets periods from EvaluationPeriod class
- Implements period lifecycle management (create, activate, complete, archive)
- Supports period filtering and display

### Usage Context
- Admin interface for managing evaluation periods
- CRUD operations for evaluation periods
- Used by HR administrators

## public/admin/values.php

### Dependencies
- Includes: `includes/auth.php`, `classes/CompanyValues.php`
- Instantiates: `CompanyValues` class
- Calls: `requireAuth()`, `hasPermission()`, `setFlashMessage()`, `redirect()`
- Uses: `verifyCSRFToken()`, `sanitizeInput()`, `formatDate()`, `generateCSRFToken()`
- Uses: `$_POST`, `$_SESSION`, `$_GET`
- Includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- Manages company values and cultural principles
- Handles form submissions for CRUD operations (create, update, delete, reorder)
- Gets values from CompanyValues class
- Uses CSRF protection
- Implements drag-and-drop reordering
- Supports CSV import/export functionality
- Provides value usage statistics

### Usage Context
- Admin interface for managing company values
- CRUD operations for company values
- Used by HR administrators
## public/api/dashboard-data.php

### Dependencies
- Includes: ../../includes/auth.php, ../../classes/DashboardAnalytics.php
- Uses: requireAuth(), getCurrentUser(), canAccessEmployee(), logActivity(), error_log(), http_response_code(), json_encode(), date()
- Instantiates: DashboardAnalytics class
- Calls: getChartData() function
- Uses: $_SESSION, $_GET, $_POST, $_SERVER
- Depends on: DashboardAnalytics class methods

### Key Methods and Relationships
- Main API endpoint for dashboard data
- Routes to different dashboard types (manager, employee, hr)
- Uses getChartData() function for chart data requests
- Implements role-based access control
- Logs API access for monitoring

### Usage Context
- Called by dashboard JavaScript for real-time data
- Used by dashboard pages for data visualization
- Accessed via API endpoints in public/api/ directory

## public/api/evidence-details.php

### Dependencies
- Includes: ../../includes/auth.php, ../../classes/Evaluation.php
- Uses: requireAuth(), canEditEvaluation(), error_log(), http_response_code(), json_encode(), array_filter(), array_column()
- Instantiates: Evaluation class
- Uses: $_SESSION, $_GET, $_SERVER
- Depends on: Evaluation class methods

### Key Methods and Relationships
- Returns detailed evidence entries for a specific dimension
- Validates request method and parameters
- Checks user permissions for evaluation access
- Formats response with summary statistics

### Usage Context
- Called by evidence detail views
- Used by dashboard components for detailed evidence data
- Accessed via API endpoints in public/api/ directory

## public/api/notifications.php

### Dependencies
- Includes: ../../includes/auth.php, ../../classes/NotificationManager.php
- Uses: requireAuth(), error_log(), http_response_code(), json_encode(), fetchAll(), fetchOne(), updateRecord(), json_decode(), file_get_contents()
- Instantiates: NotificationManager class
- Uses: $_SESSION, $_GET, $_POST, $_SERVER
- Depends on: NotificationManager class methods and database functions

### Key Methods and Relationships
- Handles GET, POST, PUT, DELETE requests for notifications
- Manages user notifications, unread counts, and statistics
- Supports creating notifications from templates and system announcements
- Implements batch operations and notification preferences
- Provides notification cleanup functionality

### Usage Context
- Called by notification system components
- Used by dashboard for notification display
- Accessed via API endpoints in public/api/ directory

## public/api/reports.php

### Dependencies
- Includes: ../../includes/auth.php, ../../classes/ReportGenerator.php
- Uses: requireAuth(), error_log(), http_response_code(), json_encode(), fetchAll(), fetchOne(), updateRecord(), json_decode(), file_get_contents()
- Instantiates: ReportGenerator class
- Uses: $_SESSION, $_GET, $_POST, $_SERVER
- Depends on: ReportGenerator class methods and database functions

### Key Methods and Relationships
- Handles GET, POST, PUT, DELETE requests for reports
- Generates various report types (evidence summary, performance trends, manager overview)
- Supports report scheduling and export functionality
- Manages scheduled reports and report history
- Provides report statistics and export formats

### Usage Context
- Called by report generation components
- Used by dashboard for report data
- Accessed via API endpoints in public/api/ directory

## public/dashboard/employee.php

### Dependencies
- Includes: ../../includes/auth.php, ../../classes/DashboardAnalytics.php, ../../classes/Employee.php, ../../classes/EvaluationPeriod.php
- Uses: requireAuth(), error_log(), json_encode(), htmlspecialchars(), date(), number_format(), ucfirst(), array_slice(), array_key_exists(), is_array(), is_numeric(), isset(), empty()
- Instantiates: DashboardAnalytics, Employee, EvaluationPeriod classes
- Uses: $_SESSION, $_GET
- Depends on: DashboardAnalytics, Employee, EvaluationPeriod class methods

### Key Methods and Relationships
- Displays employee performance dashboard
- Retrieves employee-specific dashboard data
- Shows personal performance metrics, feedback summary, development recommendations
- Displays goal progress tracking and peer comparison
- Provides evidence history timeline

### Usage Context
- Main employee dashboard page
- Called by employee navigation
- Integrates with dashboard analytics for data visualization
- Used by employee users for performance tracking
- Used by HR administrators
## public/employees/hierarchy.php

### Dependencies
- Includes: `includes/auth.php`, `classes/Employee.php`
- Uses: `requireAuth()`, `getEmployeeHierarchy()`, `getEmployees()`
- Calls: `renderHierarchy()` function
- Template includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- `requireAuth()` - ensures user authentication
- `getEmployeeHierarchy()` - retrieves employee organizational structure
- `getEmployees()` - gets all employees for search functionality
- `renderHierarchy()` - renders the organizational chart recursively

### Usage Context
- Displays organizational hierarchy chart
- Used by employees for viewing company structure
- Accessible to all authenticated users

## public/employees/list.php

### Dependencies
- Includes: `includes/auth.php`, `classes/Employee.php`
- Uses: `requireAuth()`, `isHRAdmin()`, `isManager()`, `redirect()`, `getEmployees()`, `getAllEmployees()`
- Template includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- `requireAuth()` - ensures user authentication
- `isHRAdmin()` - checks HR admin role
- `isManager()` - checks manager role
- `getEmployees()` - retrieves paginated employee list
- `getAllEmployees()` - retrieves all employees including inactive ones

### Usage Context
- Lists all employees in the system
- Used by HR admins and managers for employee management
- Accessible to HR admins and managers

## public/employees/view-feedback.php

### Dependencies
- Includes: `includes/auth.php`, `classes/Employee.php`, `classes/GrowthEvidenceJournal.php`, `classes/MediaManager.php`
- Uses: `requireAuth()`, `getEmployeeById()`, `getEmployeeJournal()`, `canAccessEmployee()`
- Template includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- `requireAuth()` - ensures user authentication
- `getEmployeeById()` - retrieves employee details
- `getEmployeeJournal()` - retrieves feedback entries for employee
- `canAccessEmployee()` - checks permission to view employee feedback

### Usage Context
- Displays feedback history for a specific employee
- Used by employees, managers, and HR admins
- Accessible based on user role and employee relationship

## public/employees/view.php

### Dependencies
- Includes: `includes/auth.php`, `classes/Employee.php`, `classes/Evaluation.php`, `classes/JobTemplate.php`
- Uses: `requireAuth()`, `getEmployeeById()`, `getEvaluations()`, `getJobTemplateById()`, `canAccessEmployee()`
- Template includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- `requireAuth()` - ensures user authentication
- `getEmployeeById()` - retrieves employee details
- `getEvaluations()` - retrieves employee evaluation history
- `getJobTemplateById()` - retrieves job template information
- `canAccessEmployee()` - checks permission to view employee details

### Usage Context
- Displays detailed employee information and evaluation history
- Used by employees, managers, and HR admins
- Accessible based on user role and employee relationship

## public/evaluation/create.php

### Dependencies
- Includes: `includes/auth.php`, `classes/Employee.php`, `classes/Evaluation.php`, `classes/EvaluationPeriod.php`, `classes/JobTemplate.php`
- Uses: `requireRole()`, `getAccessibleEmployees()`, `getActivePeriods()`, `createEvaluation()`, `canAccessEmployee()`
- Template includes: `templates/header.php`, `templates/footer.php`

### Key Methods and Relationships
- `requireRole()` - ensures manager or HR admin role
- `getAccessibleEmployees()` - retrieves employees accessible to current user
- `getActivePeriods()` - retrieves currently active evaluation periods
- `createEvaluation()` - creates new evaluation record
- `canAccessEmployee()` - checks permission to evaluate employee

### Usage Context
- Creates new performance evaluations
- Used by managers and HR admins for employee evaluation
- Requires manager or HR admin role
