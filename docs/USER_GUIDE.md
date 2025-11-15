# PHP Performance Evaluation System - User Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Dashboard Navigation](#dashboard-navigation)
4. [For HR Administrators](#for-hr-administrators)
5. [For Managers](#for-managers)
6. [For Employees](#for-employees)
7. [Evidence Management](#evidence-management)
8. [Evaluation Process](#evaluation-process)
9. [Reports and Analytics](#reports-and-analytics)
10. [Common Tasks](#common-tasks)
11. [Troubleshooting](#troubleshooting)

## Introduction

This user guide provides step-by-step instructions for using the PHP Performance Evaluation System. Whether you're an HR Administrator, Manager, or Employee, this guide will help you navigate the system and make the most of its features.

The system is designed to be intuitive and user-friendly, but this guide will help you understand all the available features and how to use them effectively.

## Getting Started

### System Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Stable internet connection
- Valid user credentials provided by your HR Administrator

### Logging In
1. Open your web browser and navigate to the system URL
2. Enter your username and password
3. Click the "Login" button

**Default Login Credentials:**
- HR Admin: `admin` / `admin123`
- Manager: `manager` / `manager123`
- Employee: `employee` / `employee123`

**Note:** Change your password after first login for security.

### First Login
After logging in for the first time:
1. You will be prompted to change your password
2. Update your profile information
3. Review your dashboard and available features
4. Explore the system based on your user role

### Logging Out
To log out securely:
1. Click on your profile name in the top right corner
2. Select "Logout" from the dropdown menu
3. Confirm logout if prompted

## Dashboard Navigation

### Dashboard Overview
Upon logging in, you'll see your personalized dashboard which provides:
- Key performance metrics and insights
- Recent activity and notifications
- Quick access to common tasks
- Performance trends and analytics

### Navigation Menu
The main navigation menu is located at the top of the page and includes:

- **Dashboard**: Your personalized homepage
- **Employees**: Manage employee records (HR Admin and Managers only)
- **Evaluations**: Create and manage performance evaluations
- **Evidence**: View and manage performance evidence
- **Reports**: Access performance reports and analytics
- **Settings**: Configure system preferences (HR Admin only)

### Search Functionality
The search bar allows you to quickly find:
- Employees by name or ID
- Evaluations by employee or period
- Evidence entries by keyword or date
- Reports by title or type

## For HR Administrators

### System Configuration

#### Managing Evaluation Periods
1. Navigate to Settings → Evaluation Periods
2. Click "Add New Period" to create a new evaluation period
3. Fill in the period details:
   - Period name (e.g., "Q1 2024 Performance Review")
   - Period type (monthly, quarterly, semi-annual, annual, custom)
   - Start and end dates
   - Status (draft, active, completed, archived)
   - Description (optional)
4. Click "Save" to create the period

#### Managing Job Templates
1. Navigate to Settings → Job Templates
2. Click "Add New Template" to create a job template
3. Define the template:
   - Position title
   - Department
   - Description
   - Associated KPIs, competencies, responsibilities, and values
4. Assign weights to each evaluation dimension
5. Click "Save" to create the template

#### Managing Departments
1. Navigate to Settings → Departments
2. Click "Add Department" to create a new department
3. Enter department details:
   - Department name
   - Description
   - Department manager (optional)
4. Click "Save" to create the department

### User Management

#### Creating User Accounts
1. Navigate to Employees → Users
2. Click "Add New User"
3. Enter user information:
   - Username
   - Email address
   - Password
   - Role (HR Admin, Manager, Employee)
4. Click "Save" to create the user account

#### Creating Employee Records
1. Navigate to Employees → Employee List
2. Click "Add Employee"
3. Enter employee details:
   - Personal information (first name, last name)
   - Employee number (auto-generated if not provided)
   - Position and department
   - Manager assignment
   - Job template assignment
   - Contact information
   - Hire date
4. Click "Save" to create the employee record

#### Managing Employee Hierarchy
1. Navigate to Employees → Hierarchy
2. View the organizational chart
3. Click on an employee to edit their manager assignment
4. Update reporting relationships as needed
5. Click "Save" to update the hierarchy

### System Oversight

#### Monitoring Evaluation Progress
1. Navigate to Evaluations → Evaluation Status
2. View evaluation completion rates by department
3. Filter by evaluation period or department
4. Export status reports for management review

#### Generating Organization Reports
1. Navigate to Reports → Organization Reports
2. Select report type:
   - Performance distribution
   - Department comparison
   - Evaluation completion rates
   - Evidence collection statistics
3. Set date range and filters
4. Click "Generate Report"
5. Download or view the report

## For Managers

### Team Management

#### Viewing Your Team
1. Navigate to Employees → My Team
2. View your direct reports
3. Click on an employee to view their profile
4. Review their performance history and current status

#### Team Performance Dashboard
1. Navigate to Dashboard → Team Analytics
2. View team performance metrics:
   - Average team rating
   - Evidence collection trends
   - Evaluation completion status
   - Performance distribution
   - Coaching opportunities
3. Filter by date range or team members
4. Export team analytics for review

### Evidence Management

#### Adding Evidence for Team Members
1. Navigate to Evidence → Add Evidence
2. Select the employee from the dropdown
3. Enter evidence details:
   - Dimension (KPIs, competencies, responsibilities, values)
   - Star rating (1-5)
   - Date of observation
   - Detailed comments
4. Add attachments if needed (images, documents)
5. Click "Save" to add the evidence

#### Viewing Team Evidence
1. Navigate to Evidence → Team Evidence
2. Filter by employee, date range, or dimension
3. View evidence entries with ratings and comments
4. Export evidence reports for performance reviews

#### Managing Evidence Quality
1. Navigate to Evidence → Evidence Quality
2. Review evidence statistics:
   - Average content length
   - Evidence distribution by dimension
   - Feedback frequency trends
3. Identify team members needing more feedback
4. Set reminders for evidence collection

### Evaluation Process

#### Creating Evaluations
1. Navigate to Evaluations → Create Evaluation
2. Select the employee and evaluation period
3. Review auto-aggregated evidence
4. Adjust scores and add comments as needed
5. Set goals for the next evaluation period
6. Click "Save" to create the evaluation

#### Completing Evaluations
1. Navigate to Evaluations → My Evaluations
2. Select an evaluation in "Draft" status
3. Review all sections:
   - KPIs performance
   - Competencies assessment
   - Responsibilities fulfillment
   - Values alignment
4. Add overall comments and recommendations
5. Submit the evaluation for review

#### Reviewing Team Evaluations
1. Navigate to Evaluations → Team Evaluations
2. View all evaluations for your team members
3. Filter by status (draft, submitted, reviewed, approved)
4. Track evaluation completion progress
5. Follow up on overdue evaluations

### Coaching and Development

#### Identifying Coaching Opportunities
1. Navigate to Dashboard → Coaching Opportunities
2. Review recommendations based on evidence:
   - Performance gaps
   - Development areas
   - Strengths to leverage
3. Plan coaching conversations
4. Track improvement over time

#### Setting Development Goals
1. Navigate to Employees → select employee → Goals
2. Add new development goals:
   - Goal title and description
   - Target metrics and timeline
   - Action steps and resources
3. Monitor goal progress
4. Update goal status as needed

## For Employees

### Personal Dashboard

#### Viewing Your Performance
1. Navigate to Dashboard → My Performance
2. View your performance metrics:
   - Current rating
   - Performance trends
   - Evidence summary
   - Development recommendations
3. Filter by time period
4. Export personal performance reports

#### Managing Notifications
1. Click the notification bell icon
2. View new feedback and reminders
3. Mark notifications as read
4. Configure notification preferences

### Evidence and Feedback

#### Viewing Your Evidence
1. Navigate to Evidence → My Evidence
2. View all feedback received:
   - Star ratings and comments
   - Date of feedback
   - Manager who provided feedback
   - Associated dimension
3. Filter by date or dimension
4. Export evidence history

#### Responding to Feedback
1. Navigate to Evidence → My Evidence
2. Select an evidence entry
3. Add your comments or response
4. Provide additional context if needed
5. Save your response

#### Self-Assessment
1. Navigate to Evidence → Self-Assessment
2. Add self-evaluation entries:
   - Performance achievements
   - Challenges faced
   - Areas for improvement
   - Goals accomplished
3. Rate your own performance
4. Submit for manager review

### Evaluations and Goals

#### Viewing Your Evaluations
1. Navigate to Evaluations → My Evaluations
2. View all your evaluations:
   - Current and past evaluations
   - Overall ratings and comments
   - Dimension-specific scores
   - Development recommendations
3. Download evaluation reports
4. Track performance trends over time

#### Participating in Self-Evaluation
1. Navigate to Evaluations → Self-Evaluation
2. Select the current evaluation period
3. Complete self-assessment:
   - Rate your performance in each dimension
   - Provide examples and evidence
   - Identify strengths and areas for improvement
   - Set goals for next period
4. Submit self-evaluation

#### Managing Your Goals
1. Navigate to Goals → My Goals
2. View your active goals:
   - Goal descriptions and targets
   - Progress status
   - Timeline and milestones
3. Update progress as you achieve milestones
4. Add comments or challenges faced
5. Mark goals as completed when achieved

### Profile Management

#### Updating Personal Information
1. Navigate to Profile → Personal Information
2. Update your details:
   - Contact information
   - Job-related information
   - Preferences
3. Click "Save" to update your profile

#### Changing Password
1. Navigate to Profile → Security
2. Enter your current password
3. Enter your new password
4. Confirm your new password
5. Click "Update Password" to save changes

## Evidence Management

### Understanding Evidence Types

#### Manager Feedback
- Direct observations from your manager
- Performance on specific tasks or projects
- Behavioral feedback and observations
- Recognition of achievements

#### Self-Assessment
- Your own assessment of performance
- Documentation of achievements
- Identification of challenges
- Personal development insights

#### Peer Feedback
- Feedback from colleagues and team members
- Collaboration and teamwork assessment
- Communication effectiveness
- Interpersonal skills evaluation

### Adding Evidence

#### As a Manager
1. Navigate to Evidence → Add Evidence
2. Select the employee
3. Choose the dimension (KPIs, competencies, responsibilities, values)
4. Set the star rating (1-5)
5. Enter detailed comments with specific examples
6. Add the date of observation
7. Attach supporting documents if needed
8. Click "Save"

#### As an Employee (Self-Assessment)
1. Navigate to Evidence → Self-Assessment
2. Choose the dimension
3. Rate your performance (1-5)
4. Describe your achievements and challenges
5. Provide specific examples
6. Add supporting evidence if available
7. Click "Save"

### Managing Evidence Quality

#### Writing Effective Evidence Comments
- **Be Specific**: Provide concrete examples rather than general statements
- **Be Timely**: Record feedback as close to the event as possible
- **Be Balanced**: Include both strengths and areas for improvement
- **Be Actionable**: Provide clear guidance for improvement
- **Be Professional**: Maintain professional and constructive tone

#### Using Evidence Attachments
- **Relevant Documents**: Attach project reports, emails, or other relevant documents
- **Images**: Include screenshots or photos of work products
- **Supporting Data**: Add spreadsheets or data visualizations
- **Size Limits**: Ensure attachments are within system size limits
- **File Types**: Use supported file formats (PDF, DOC, XLS, JPG, PNG)

### Searching and Filtering Evidence

#### Advanced Search
1. Navigate to Evidence → Search Evidence
2. Use search filters:
   - Employee name or ID
   - Date range
   - Dimension
   - Star rating range
   - Keywords in comments
3. Click "Search" to view results
4. Export search results if needed

#### Filtering by Dimension
1. Navigate to Evidence → View Evidence
2. Use the dimension filter:
   - KPIs: Performance indicators and results
   - Competencies: Skills and abilities
   - Responsibilities: Job duties and accountabilities
   - Values: Cultural alignment and behaviors
3. View filtered evidence
4. Switch between dimensions as needed

## Evaluation Process

### Evaluation Lifecycle

#### 1. Preparation Phase
- HR Admin sets up evaluation periods
- Job templates are assigned to employees
- Managers and employees receive notifications
- Evidence collection begins

#### 2. Evidence Collection Phase
- Managers provide regular feedback
- Employees complete self-assessments
- Evidence is aggregated by dimension
- Performance trends are analyzed

#### 3. Evaluation Creation Phase
- Managers create evaluations based on evidence
- Scores are calculated for each dimension
- Overall ratings are determined
- Comments and recommendations are added

#### 4. Review and Approval Phase
- Evaluations are submitted for review
- HR Admin reviews evaluations for consistency
- Employees review and acknowledge evaluations
- Development goals are set for next period

#### 5. Follow-up Phase
- Progress on development goals is tracked
- Coaching conversations occur
- Continuous feedback continues
- Next evaluation cycle begins

### Creating Evaluations

#### As a Manager
1. Navigate to Evaluations → Create Evaluation
2. Select employee and evaluation period
3. Review auto-aggregated evidence:
   - Evidence count by dimension
   - Average star ratings
   - Confidence indicators
4. Adjust scores based on your judgment
5. Add detailed comments for each dimension
6. Set development goals for next period
7. Save as draft or submit for review

#### Auto-Aggregation Process
The system automatically aggregates evidence to create evaluation scores:
- Evidence entries are grouped by dimension
- Star ratings are averaged for each dimension
- Confidence factors are applied based on evidence quantity
- Final scores are weighted according to the evaluation framework

### Reviewing Evaluations

#### As an Employee
1. Navigate to Evaluations → My Evaluations
2. Select the evaluation to review
3. Read through all sections:
   - Overall rating and comments
   - Dimension-specific scores and feedback
   - Strengths and areas for improvement
   - Development goals for next period
4. Add your comments or response
5. Acknowledge receipt of evaluation

#### As an HR Admin
1. Navigate to Evaluations → Review Evaluations
2. Filter by status or department
3. Review evaluations for consistency and fairness
4. Provide feedback to managers if needed
5. Approve evaluations for finalization
6. Generate evaluation summary reports

### Setting Development Goals

#### SMART Goal Framework
When setting development goals, ensure they are:
- **Specific**: Clear and well-defined
- **Measurable**: Quantifiable progress indicators
- **Achievable**: Realistic and attainable
- **Relevant**: Aligned with job requirements and career goals
- **Time-bound**: Clear timeline for completion

#### Creating Effective Goals
1. Navigate to Evaluations → select evaluation → Goals
2. Add new goals:
   - Clear goal statement
   - Specific metrics for success
   - Timeline and milestones
   - Required resources and support
   - Action steps for achievement
3. Link goals to evaluation feedback
4. Set up progress tracking milestones
5. Save goals for next evaluation period

## Reports and Analytics

### Dashboard Analytics

#### HR Administrator Dashboard
- Organization-wide performance metrics
- Evaluation completion rates
- Department performance comparison
- System usage statistics
- Evidence collection trends

#### Manager Dashboard
- Team performance overview
- Evidence collection status
- Evaluation completion progress
- Coaching opportunities
- Team member performance trends

#### Employee Dashboard
- Personal performance metrics
- Evidence summary
- Goal progress tracking
- Development recommendations
- Performance trends over time

### Generating Reports

#### Performance Reports
1. Navigate to Reports → Performance Reports
2. Select report type:
   - Individual performance summary
   - Team performance comparison
   - Department performance overview
   - Organization performance trends
3. Set filters and date range
4. Choose report format (PDF, Excel, CSV)
5. Click "Generate Report"
6. Download or view the report

#### Evidence Reports
1. Navigate to Reports → Evidence Reports
2. Select evidence report type:
   - Evidence summary by employee
   - Evidence distribution by dimension
   - Evidence quality analysis
   - Evidence collection trends
3. Apply filters as needed
4. Generate and download the report

#### Scheduled Reports
1. Navigate to Reports → Scheduled Reports
2. Create new scheduled report:
   - Report name and type
   - Recipients (email addresses)
   - Schedule frequency (daily, weekly, monthly)
   - Report parameters and filters
3. Activate the schedule
4. Monitor report generation history

### Understanding Analytics

#### Performance Metrics
- **Overall Rating**: Average score across all dimensions
- **Dimension Scores**: Performance in each evaluation area
- **Trend Analysis**: Performance changes over time
- **Comparison Metrics**: Performance relative to peers or team

#### Evidence Analytics
- **Evidence Count**: Number of feedback entries
- **Evidence Quality**: Assessment of feedback detail and usefulness
- **Evidence Distribution**: Breakdown by dimension and source
- **Feedback Frequency**: How often feedback is provided

#### Development Analytics
- **Goal Completion**: Progress on development objectives
- **Skill Improvement**: Changes in competency ratings
- **Coaching Effectiveness**: Impact of coaching on performance
- **Career Progression**: Movement and growth over time

## Common Tasks

### Quick Reference Guide

#### For HR Administrators
- **Add New Employee**: Employees → Add Employee → Fill details → Save
- **Create Evaluation Period**: Settings → Evaluation Periods → Add New Period → Fill details → Save
- **Generate Organization Report**: Reports → Organization Reports → Select type → Generate → Download
- **Manage Users**: Employees → Users → Add/Edit users → Save changes

#### For Managers
- **Add Evidence**: Evidence → Add Evidence → Select employee → Fill details → Save
- **Create Evaluation**: Evaluations → Create Evaluation → Select employee → Complete → Submit
- **View Team Performance**: Dashboard → Team Analytics → View metrics → Filter as needed
- **Set Development Goals**: Evaluations → Select evaluation → Goals → Add goals → Save

#### For Employees
- **View Performance**: Dashboard → My Performance → View metrics → Filter by period
- **Complete Self-Assessment**: Evidence → Self-Assessment → Fill details → Submit
- **View Evaluations**: Evaluations → My Evaluations → Select evaluation → Review
- **Update Goals**: Goals → My Goals → Update progress → Save changes

### Keyboard Shortcuts

#### Global Shortcuts
- `Ctrl + /` or `Cmd + /`: Open help
- `Ctrl + K` or `Cmd + K`: Quick search
- `Esc`: Close modal or cancel action
- `Enter`: Submit form or confirm action

#### Navigation Shortcuts
- `Alt + D`: Go to Dashboard
- `Alt + E`: Go to Evaluations
- `Alt + V`: Go to Evidence
- `Alt + R`: Go to Reports
- `Alt + S`: Go to Settings (if available)

#### Form Shortcuts
- `Tab`: Navigate to next field
- `Shift + Tab`: Navigate to previous field
- `Ctrl + S` or `Cmd + S`: Save form
- `Ctrl + Enter` or `Cmd + Enter`: Submit form

### Mobile Usage

#### Responsive Design
The system is fully responsive and works on:
- Smartphones (iOS and Android)
- Tablets (iPad and Android tablets)
- Mobile browsers (Chrome, Safari, Firefox)

#### Mobile Navigation
- Use the hamburger menu (☰) to access navigation
- Swipe left/right to navigate between sections
- Tap and hold to access context menus
- Use pinch-to-zoom for better readability

#### Mobile-Specific Features
- Touch-friendly buttons and controls
- Optimized forms for mobile input
- Mobile-optimized dashboards
- Push notifications for mobile devices

## Troubleshooting

### Common Issues and Solutions

#### Login Problems
**Problem**: Cannot log in to the system
**Solutions**:
- Check username and password spelling
- Verify Caps Lock is not on
- Clear browser cache and cookies
- Try a different browser
- Contact HR Administrator if account is locked

**Problem**: Password reset not working
**Solutions**:
- Check spam/junk folder for reset email
- Verify email address is correct
- Request password reset again
- Contact HR Administrator for manual reset

#### Performance Issues
**Problem**: System is running slowly
**Solutions**:
- Check internet connection speed
- Close unnecessary browser tabs
- Clear browser cache
- Try a different browser
- Contact IT support if issue persists

**Problem**: Pages not loading correctly
**Solutions**:
- Refresh the page (F5 or Ctrl+R)
- Clear browser cache and cookies
- Check browser compatibility
- Disable browser extensions temporarily
- Try incognito/private browsing mode

#### Data Issues
**Problem**: Cannot find expected data
**Solutions**:
- Check date range filters
- Verify you have correct permissions
- Ensure data was entered correctly
- Contact data owner if data is missing
- Check with HR Administrator about data access

**Problem**: Incorrect calculations in evaluations
**Solutions**:
- Verify evidence entries are complete
- Check evaluation period settings
- Review dimension weight configurations
- Contact HR Administrator for verification
- Report calculation errors for investigation

### Getting Help

#### In-System Help
- Click the help icon (?) on any page
- Access the user guide from the dashboard
- Use the search function to find specific topics
- Check tooltips and field descriptions

#### Contacting Support
- **HR Administrator**: For user access, evaluation questions, and system configuration
- **IT Support**: For technical issues, login problems, and system errors
- **Manager**: For evaluation process questions and feedback guidance
- **Help Desk**: For general system usage questions

#### Reporting Issues
When reporting issues, please provide:
- Your username and role
- Browser and version
- Error messages (exact text)
- Steps to reproduce the issue
- Screenshots if applicable
- Time and date of issue

### Best Practices

#### For All Users
- **Regular Login**: Check the system regularly for updates and feedback
- **Complete Profile**: Keep your profile information up to date
- **Strong Passwords**: Use strong, unique passwords and change them regularly
- **Log Out**: Always log out when finished, especially on shared devices

#### For HR Administrators
- **Regular Backups**: Ensure regular system backups are performed
- **User Training**: Provide adequate training for all users
- **Period Management**: Keep evaluation periods well-organized and communicated
- **Data Quality**: Regularly review data quality and consistency

#### For Managers
- **Timely Feedback**: Provide feedback regularly, not just during evaluations
- **Specific Comments**: Be specific and detailed in evidence comments
- **Balanced Approach**: Provide both positive feedback and constructive criticism
- **Follow-up**: Schedule follow-up conversations after evaluations

#### For Employees
- **Self-Reflection**: Regularly complete self-assessments
- **Goal Tracking**: Keep your goals and progress up to date
- **Open Communication**: Communicate openly with your manager about performance
- **Continuous Improvement**: Use feedback to drive continuous improvement

---

## Additional Resources

For more detailed information on specific aspects of the system, please refer to the following documentation:

- [Application Overview](APPLICATION_OVERVIEW.md): Comprehensive system overview and features
- [Administrator Guide](ADMINISTRATOR_GUIDE.md): Detailed system administration instructions
- [Developer Documentation](DEVELOPER_GUIDE.md): Technical details and customization
- [API Documentation](API_DOCUMENTATION.md): Integration and automation
- [Deployment Guide](DEPLOYMENT_GUIDE.md): Installation and setup
- [System Architecture](SYSTEM_ARCHITECTURE.md): Technical architecture details