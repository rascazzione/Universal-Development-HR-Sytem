# Self-Assessment System Guide

## Overview
The self-assessment system empowers employees to regularly evaluate their performance, identify strengths and areas for improvement, and track progress against company competencies and goals.

## Quick Start Guide

### For Employees

**📱 Access Path:**
1. **Dashboard Navigation**: My Performance → Self-Assessment
2. **Direct URL**: `/public/self-assessment/create`
3. **Mobile Shortcut**: Mobile app → Performance → Assessments

**⏱️ Quick Steps:**
1. **Choose Assessment Type**: Quarterly, Project-based, or Annual Review
2. **Complete Competencies**: Rate yourself 1-5 on company competencies
3. **Provide Evidence**: Upload documents, links, or examples
4. **Set Goals**: Define 3-5 SMART goals for next period
5. **Review & Submit**: Final review before submission

### For Managers

**Access Path:**
1. **Team Navigation**: Team Management → Performance Reviews
2. **Monitoring Tool**: Dashboard → Performance → Assessment Overview

**Key Actions:**
- Review and calibrate self-assessments
- Provide constructive feedback
- Track completion rates by team members

## Detailed Feature Documentation

### Assessment Types

#### 1. Quarterly Self-Assessment
**Purpose:** Regular performance check and goal setting
**Timeline:** Every 3 months
**Focus Areas:** Company competencies, goal achievement, career development

#### 2. Project-Based Assessment
**Purpose:** Evaluate performance on specific projects
**Timeline:** End of major projects
**Focus Areas:** Project-specific skills, collaboration, outcomes

#### 3. Annual Performance Review
**Purpose:** Comprehensive yearly evaluation
**Timeline:** Once per year
**Focus Areas:** Full competency review, annual goals, career planning

### Competency Framework

#### Communication Skills
- **Level 1**: Shares information clearly
- **Level 2**: Adapts communication style to audience
- **Level 3:** Presents complex ideas effectively
- **Level 4:** Influences others through communication
- **Level 5:** Inspires and mentors others in communication

#### Team Collaboration
- **Evidence Examples:** Lead cross-functional meetings, resolved conflicts, mentored team members

#### Technical Expertise
- **Evidence Collection:** Certifications completed, projects delivered, problems solved

#### Innovation
- **Evidence Examples:** Implemented new processes, created tools, improved workflows

### Creating Your Assessment

#### Step-by-Step Process

**1. Review Competency Framework**
```
📋 Your Assessment Dashboard
├─ Current Period: Q4 2024
├─ Deadline: December 15, 2024
├─ Progress: 65% Complete
└─ Competencies to Rate (10 total)
```

**2. Rate Each Competency**
For each competency, provide:
- **Self-rating (1-5 scale)**
- **Evidence (2-3 specific examples)**
- **Areas for improvement (be honest)**
- **Development plan (next steps)**

**3. Collect Evidence**
- **Documents:** Project reports, certifications, presentations
- **Links:** GitHub repositories, project demos, client feedback
- **Examples:** Specific situations and outcomes
- **Metrics:** Measurable achievements

**4. Write Reflective Comments**
Focus on:
- **Strengths celebrated**
- **Challenges faced and overcome**
- **Lessons learned**
- **Future development goals**

### Evidence Upload Guidelines

#### Supported File Types
- **Documents:** PDF, DOC, DOCX, TXT
- **Presentations:** PPTX, Keynote, Google Slides
- **Images:** JPG, PNG screenshots of work
- **Links:** GitHub, demo URLs, portfolio items
- **Max size:** 10MB per file

#### Evidence Examples
**For Communication Skill:**
```
Evidence Upload:
├─ File: Q4_Team_Presentation.pdf
├─ Type: Presentation slide deck
├─ Description: Led Q4 strategy presentation to 50+ team members
└─ Feedback: 'Exceptional clarity and engagement' - Manager Review
```

### Assessment Analytics

#### Real-Time Metrics
- **Completion percentage** by competency
- **Evidence quality score** (auto-calculated)
- **Comparison with previous assessments**
- **Goal achievement tracking**

#### Visual Progress Tracking
```
📊 Assessment Progress
├─ Competency Completion: 8/10 (80%)
├─ Evidence Quality: 4.2/5.0
├─ Previous vs Current: +0.3 improvement
└─ Deadline: 12 days remaining
```

### Manager Review Process

#### Calibration Guidelines

**Rating Discrepancy Analysis:**
- **Self-Rating < Manager Rating:** Discuss under-confidence
- **Self-Rating > Manager Rating:** Explore blind spots
- **Alignment:** Reaffirm strengths and collaboration

**Feedback Framework:**
```
Manager Comments:
├─ Strength Recognition: "Consistently demonstrates excellent project management"
├─ Development Area: "Focus on delegation and team empowerment"
├─ Next Steps: "Project leadership opportunity in Q1"
└─ Support Offered: "Executive coaching sessions available"
```

### Best Practices

#### For Employees
1. **Be Honest:** Rate yourself realistically
2. **Be Specific:** Provide concrete evidence
3. **Be Action-Oriented:** Include development plans
4. **Be Reflective:** Show growth mindset

#### For Managers
1. **Timely Reviews:** Complete within 7 days
2. **Constructive Focus:** Balance praise and improvement
3. **Calibration Consistency:** Apply same standards across team
4. **Follow-up:** Schedule post-review conversations

### Integration with Other Features

#### Self-Assessment → IDP Link
After assessment completion:
- **Auto-generate** development goals based on lowest competencies
- **Suggest training** based on improvement areas
- **Propose mentorship** for skill development

#### Self-Assessment → OKR Connection
- **Create OKRs** from assessment goals
- **Track progress** of development objectives
- **Measure improvement** over assessment cycles

### Technical Specifications

#### Database Schema
```sql
-- Key tables for self-assessment system
CREATE TABLE self_assessment_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    config_id INT NOT NULL,
    responses JSON NOT NULL,
    status ENUM('draft','submitted','reviewed','archived'),
    submitted_at TIMESTAMP,
    INDEX idx_employee_period (employee_id, config_id),
    FOREIGN KEY (employee_id) REFERENCES employee(id)
);
```

#### API Endpoints
```
Self-Assessment APIs:
├─ POST /api/360/self-assessment/create
├─ GET  /api/360/self-assessment/{response_id}
├─ PUT  /api/360/self-assessment/update
└─ POST /api/360/self-assessment/analytics
```

#### Troubleshooting Common Issues

| Issue | Solution | Example |
|-------|----------|---------|
| **Assessment Not Saving** | Check connection + browser cache | Clear browser, save again |
| **Evidence Upload Fail** | Verify file size/type meets requirements | Reduce pdf size <10MB |
| **Rating Unavailable** | Ensure assessment period is active | Wait for period activation |
| **Manager Review Delay** | Auto-escalation after 7 days | HR intervention triggered |

### Success Metrics

#### Employee Success Indicators:
- **Completion Rate:** >90% within deadline
- **Evidence Quality:** Average score >4.0
- **Goal Setting:** 100% have future development plans
- **Follow-up Actions:** >80% implemented IDP milestones

#### Manager Success Indicators:
- **Review Timeliness:** 100% within 7 days
- **Calibration Accuracy:** <10% rating variance across team
- **Development Action:** 100% employees have follow-up plans
- **Engagement:** >85% team participation rate

### Resource Library

#### Training Materials
**Interactive Guides:**
- Video: "Completing Your Self-Assessment" (5 min)
- Interactive: Competency rating walkthrough
- Checklist: Evidence collection checklist

#### Templates
**Assessment Templates:**
- Quarterly review template
- Project-based template  
- Career development template
- Goal-setting framework

#### Best Practice Examples
**High-Quality Response Example:**
```
Competency: Communication Rating: 4/5
Evidence: 
- Led Q4 product launch presentation to 50+ stakeholders including VPs
- Created comprehensive project documentation using Notion
- Improved team communication by implementing weekly standups
- Resolved 3 client escalations through clear email communication
Improvement Areas: Public speaking confidence, executive presentation skills