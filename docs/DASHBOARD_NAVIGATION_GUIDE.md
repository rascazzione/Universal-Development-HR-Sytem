# Dashboard Navigation Integration Guide

## Overview
This guide provides complete instructions for integrating the 360-degree feedback system features into existing dashboard interfaces, ensuring seamless navigation and user experience across all feature enhancements.

## Navigation Architecture

### Primary Dashboard Integration Points

#### Employee Dashboard (public/dashboard/employee.php)

**Add to Main Navigation Menu:**
```html
<!-- Enhanced Employee Navigation -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="enhancementNav">
        360-Degree Feedback
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/public/self-assessment/create">Self-Assessment</a></li>
        <li><a class="dropdown-item" href="/public/okr/dashboard">My OKRs</a></li>
        <li><a class="dropdown-item" href="/public/idp/dashboard">Development Plans</a></li>
        <li><a class="dropdown-item" href="/public/kudos/give">Give Recognition</a></li>
        <li><a class="dropdown-item" href="/public/upward-feedback/anonymous">Manager Feedback</a></li>
    </ul>
</li>
```

**Enhancement Cards for Dashboard:**
```html
<!-- 360-Degree System Cards -->
<div class="row">
    <!-- Self-Assessment Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card" style="min-height: 180px;">
            <div class="card-body">
                <h5 class="card-title">Self-Assessment</h5>
                <p class="card-text">Due: <span class="text-danger">Dec 15, 2024</span></p>
                <div class="progress mb-2">
                    <div class="progress-bar" style="width: 65%">65%</div>
                </div>
                <a href="/public/self-assessment/create" class="btn btn-primary btn-sm">Continue</a>
            </div>
        </div>
    </div>

    <!-- OKR Dashboard Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card" style="min-height: 180px;">
            <div class="card-body">
                <h5 class="card-title">My OKRs</h5>
                <p class="card-text">Active: <span class="text-success">3 objectives</span></p>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" style="width: 78%">78%</div>
                </div>
                <a href="/public/okr/dashboard" class="btn btn-success btn-sm">View Progress</a>
            </div>
        </div>
    </div>

    <!-- Kudos Feed Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card" style="min-height: 180px;">
            <div class="card-body">
                <h5 class="card-title">Recognition Given</h5>
                <p class="card-text">Given: <span class="text-info">12 kudos</span></p>
                <p class="text-muted">Received: 8 this week</p>
                <a href="/public/kudos/feed" class="btn btn-info btn-sm">Give More</a>
            </div>
        </div>
    </div>

    <!-- Development Plans Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card" style="min-height: 180px;">
            <div class="card-body">
                <h5 class="card-title">Development Plans</h5>
                <p class="card-text">Active: <span class="text-warning">2 plans</span></p>
                <div class="progress mb-2">
                    <div class="progress-bar bg-warning" style="width: 45%">45%</div>
                </div>
                <a href="/public/idp/dashboard" class="btn btn-warning btn-sm">View Plans</a>
            </div>
        </div>
    </div>
</div>
```

#### Manager Dashboard (public/dashboard/manager.php)

**Manager-Specific Navigation:**
```html
<!-- Manager Control Panel Enhancement -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="collapse navbar-collapse" id="managerNav">
        <ul class="navbar-nav">
            <!-- Performance Management Section -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="teamEnhancementNav">
                    <i class="fas fa-chart-line"></i> 360-Degree Management
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="/public/self-assessment/manager-review">Review Assessments</a>
                    <a class="dropdown-item" href="/public/okr/team-dashboard">Team OKRs</a>
                    <a class="dropdown-item" href="/public/upward-feedback/my-summary">Leadership Feedback</a>
                    <a class="dropdown-item" href="/public/idp/employee-plans">Team Development</a>
                </div>
            </li>
        </ul>
    </div>
</nav>
```

#### HR Dashboard (public/dashboard/hr.php)

**Admin Navigation Menu:**
```html
<!-- HR Administrator Panel -->
<div class="sidebar">
    <h5>360-Degree System</h5>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="/public/admin/assessment-configs">
                <i class="fas fa-cog"></i> Assessment Configurations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/public/admin/kudos-categories">
                <i class="fas fa-heart"></i> Kudos Management
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/public/admin/okr-settings">
                <i class="fas fa-target"></i> OKR Settings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/public/admin/enhancement-analytics">
                <i class="fas fa-chart-bar"></i> 360 Analytics
            </a>
        </li>
    </ul>
</div>
```

## Navigation Implementation Code

### JavaScript Navigation Manager
```javascript
// 360-Degree Navigation Manager
class Navigation360 {
    constructor() {
        this.userRole = null;
        this.activeFeatures = null;
    }

    async initialize(userId) {
        // Fetch user role and available features
        const response = await fetch('/api/360/user/nav-permissions');
        const data = await response.json();
        
        this.userRole = data.user.role;
        this.activeFeatures = data.features;
        
        this.renderNavigation();
    }

    renderNavigation() {
        // Generate navigation based on role and features
        const navContainer = document.getElementById('enhancementNav');
        
        switch(this.userRole) {
            case 'employee':
                this.renderEmployeeNav();
                break;
            case 'manager':
                this.renderManagerNav();
                break;
            case 'admin':
                this.renderAdminNav();
                break;
        }
    }

    renderEmployeeNav() {
        const navHTML = `
            <div class="enhancement-cards grid-4">
                ${this.activeFeatures.self_assessment ? this.assessmentCard() : ''}
                ${this.activeFeatures.kudos ? this.kudosCard() : ''}
                ${this.activeFeatures.okr ? this.okrCard() : ''}
                ${this.activeFeatures.idp ? this.idpCard() : ''}
            </div>
        `;
        
        navContainer.innerHTML = navHTML;
    }

    assessmentCard() {
        return `
            <div class="card enhancement-card" onclick="navigate('/public/self-assessment/create')">
                <div class="card-icon">📈</div>
                <h4>Self-Assessment</h4>
                <p>Create your performance review</p>
                <span class="completion-badge">65%</span>
            </div>
        `;
    }

    kudosCard() {
        return `
            <div class="card enhancement-card" onclick="navigate('/public/kudos/give')">
                <div class="card-icon">❤️</div>
                <h4>Give Kudos</h4>
                <p>Recognize team achievements</p>
                <span class="badge">12 given</span>
            </div>
        `;
    }
}

// Usage
const nav360 = new Navigation360();
nav360.initialize(currentUserId);
```

### CSS Styling for Navigation
```css
/* Dashboard Enhancement Cards */
.enhancement-cards {
    display: grid;
    gap: 1.5rem;
    margin: 1rem 0;
}

.grid-4 {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.enhancement-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.enhancement-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.card-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.completion-badge {
    background: #10b981;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

/* Mobile-Responsive Navigation */
@media (max-width: 768px) {
    .grid-4 {
        grid-template-columns: 1fr;
    }
    
    .enhancement-card {
        padding: 1rem;
    }
}
```

## Role-Based Navigation Quick Reference

### Employee Dashboard Access
| Feature | URL Path | Menu Location | Description |
|---------|----------|---------------|-------------|
| Self-Assessment | `/public/self-assessment/create` | Quick Actions → "Create Assessment" | Personal evaluation creation |
| My OKRs | `/public/okr/dashboard` | My Goals → OKR Dashboard | Goal setting and tracking |
| Development Plans | `/public/idp/dashboard` | My Growth → Development Plans | Career development pathway |
| Give Kudos | `/public/kudos/give` | Recognition → Give Recognition | Peer recognition system |
| Upward Feedback | `/public/upward-feedback/anonymous` | Feedback → Manager Feedback | Anonymous manager evaluation |

### Manager Dashboard Access
| Feature | URL Path | Menu Location | Description |
|---------|----------|---------------|-------------|
| Review Assessments | `/public/manager/assessment-review` | Team Management → Reviews | Review employee self-assessments |
| Team OKRs | `/public/manager/team-okrs` | Team Management → OKRs | Monitor team goal progress |
| Leadership Feedback | Upward Feedback 2FA: `/public/upward-feedback/my-summary` | Management → Leadership Feedback | View anonymous team feedback |
| Employee Development | `/public/manager/employee-plans` | Team Management → Development | Review team development plans |

### HR Admin Dashboard Access
| Feature | URL Path | Menu Location | Description |
|---------|----------|---------------|-------------|
| Assessment Configurations | `/public/admin/assessment-configs` | System → 360 Settings → Assessment Config | Configure assessment frameworks |
| Kudos Management | `/public/admin/kudos-categories` | System → 360 Settings → Recognition | Manage recognition system |
| OKR Settings | `/public/admin/okr-settings` | System → 360 Settings → OKR System | Configure OKR features |
| Analytics Dashboard | `/public/admin/enhancement-analytics` | Analytics → 360 Insights | System-wide metrics and reports |

## URL Patterns Reference

### Standard Navigation URLs
```
Base Pattern: /public/[feature-type]/[action]

Individual Actions:
├─ /public/self-assessment/create          → New assessment
├─ /public/self-assessment/view/{id}       → View assessment
├─ /public/self-assessment/edit/{id}       → Edit assessment
├─ /public/okr/create                      → Create OKR
├─ /public/okr/dashboard                   → OKR overview
├─ /public/kudos/feed                      → Recognition feed
├─ /public/kudos/give                      → Give recognition
├─ /public/idp/create                      → Create development plan
├─ /public/upward-feedback/anonymous       → Anonymous feedback
└─ /public/upward-feedback/summary/{id}    → Feedback summary
```

### API Endpoint URLs
```
API Pattern: /api/360/[feature]/[action]

Examples:
├─ /api/360/self-assessment/create         → API creation
├─ /api/360/self-assessment/update         → API update
├─ /api/360/okr/list                       → Get OKR