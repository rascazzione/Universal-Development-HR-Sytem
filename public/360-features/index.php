<?php
/**
 * System Configuration Hub (replaces legacy 360° features page)
 */

require_once __DIR__ . '/../../includes/auth.php';

// Only HR administrators should access this area
requireRole(['hr_admin']);

$pageTitle = 'System Configuration';
$pageHeader = true;
$pageDescription = 'Manage templates, KPIs, competencies, and company values';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="h2 mb-2">
                <i class="fas fa-tools me-2 text-primary"></i>
                System Configuration
            </h1>
            <p class="text-muted mb-0">
                Centralize the setup of your performance framework. Only HR administrators have access to these options.
            </p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 config-card">
                <div class="card-body text-center">
                    <div class="config-icon bg-primary text-white mb-3">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h5>Job Templates</h5>
                    <p class="text-muted small">
                        Maintain job profiles, responsibilities, and expectations for every role.
                    </p>
                    <a href="/admin/job_templates.php" class="btn btn-primary btn-sm w-100">
                        Manage Templates
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 config-card">
                <div class="card-body text-center">
                    <div class="config-icon bg-success text-white mb-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Company KPIs</h5>
                    <p class="text-muted small">
                        Define organization-wide KPIs that can be used within evaluations and job templates.
                    </p>
                    <a href="/admin/kpis.php" class="btn btn-success btn-sm w-100">
                        Configure KPIs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 config-card">
                <div class="card-body text-center">
                    <div class="config-icon bg-info text-white mb-3">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h5>Competencies</h5>
                    <p class="text-muted small">
                        Curate behavioral and technical competencies to drive consistent evaluations.
                    </p>
                    <a href="/admin/competencies.php" class="btn btn-info btn-sm w-100 text-white">
                        Manage Competencies
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 config-card">
                <div class="card-body text-center">
                    <div class="config-icon bg-danger text-white mb-3">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h5>Company Values</h5>
                    <p class="text-muted small">
                        Keep your core values front-and-center and tie them back to employee assessments.
                    </p>
                    <a href="/admin/values.php" class="btn btn-danger btn-sm w-100">
                        Update Values
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 config-card">
                <div class="card-body text-center">
                    <div class="config-icon bg-dark text-white mb-3">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h5>Departments</h5>
                    <p class="text-muted small">
                        Maintain your org structure so reporting lines and templates stay aligned.
                    </p>
                    <a href="/admin/departments.php" class="btn btn-dark btn-sm w-100">
                        Manage Departments
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 config-card">
                <div class="card-body text-center">
                    <div class="config-icon bg-secondary text-white mb-3">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h5>Audit Log</h5>
                    <p class="text-muted small">
                        Track sensitive configuration changes. This area is still being built.
                    </p>
                    <a href="/pending.php?feature=Audit+Log" class="btn btn-secondary btn-sm w-100">
                        Coming Soon
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2 text-warning"></i>
                        Configuration Tips
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Keep job templates updated so managers reference the latest expectations.</li>
                        <li>Use KPIs and competencies consistently to ensure balanced reviews.</li>
                        <li>Company values should appear in each job template to reinforce culture.</li>
                        <li>Schedule periodic audits to confirm every employee is mapped to a template.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-link me-2 text-secondary"></i>
                        Related Administration
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Need to perform additional setup? Leverage the broader administration menu for departments,
                        users, and evaluation periods. Keeping these aligned ensures job templates and KPIs remain actionable.
                    </p>
                    <a href="/admin/" class="btn btn-outline-secondary">
                        Open Admin Console
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.config-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.config-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(0,0,0,0.1);
}

.config-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 1.5rem;
}
</style>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
