/**
 * HR Analytics Dashboard JavaScript
 * Interactive charts and organizational analytics functionality
 */

// HR Dashboard Object
const HRDashboard = {
    charts: {},
    data: window.dashboardData || {},
    
    // Initialize dashboard
    init: function() {
        this.initCharts();
        this.initEventListeners();
        this.initFilters();
        console.log('HR Analytics Dashboard initialized');
    },
    
    // Initialize all charts
    initCharts: function() {
        this.initDepartmentComparisonChart();
        this.initPerformanceDistributionChart();
        this.initUsageAnalyticsChart();
        this.initAdoptionMetricsChart();
        this.initOrganizationalTrendsChart();
    },
    
    // Initialize event listeners
    initEventListeners: function() {
        // Department filter change
        const departmentFilter = document.getElementById('departmentFilter');
        if (departmentFilter) {
            departmentFilter.addEventListener('change', (e) => {
                this.handleDepartmentChange(e.target.value);
            });
        }
        
        // Period filter change
        const periodFilter = document.getElementById('periodFilter');
        if (periodFilter) {
            periodFilter.addEventListener('change', (e) => {
                this.handlePeriodChange(e.target.value);
            });
        }
        
        // Department view toggle
        const deptViewRadios = document.querySelectorAll('input[name="deptView"]');
        deptViewRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.handleDepartmentViewChange(e.target.id);
            });
        });
        
        // Export and other actions
        window.exportDashboard = () => this.exportDashboard();
        window.refreshDashboard = () => this.refreshDashboard();
        window.generateReport = () => this.generateReport();
    },
    
    // Initialize filters
    initFilters: function() {
        // Set current filters if available
        const urlParams = new URLSearchParams(window.location.search);
        const department = urlParams.get('department');
        const periodId = urlParams.get('period_id');
        
        if (department) {
            const departmentFilter = document.getElementById('departmentFilter');
            if (departmentFilter) departmentFilter.value = department;
        }
        
        if (periodId) {
            const periodFilter = document.getElementById('periodFilter');
            if (periodFilter) periodFilter.value = periodId;
        }
    },
    
    // Department Comparison Chart
    initDepartmentComparisonChart: function() {
        const ctx = document.getElementById('departmentComparisonChart');
        if (!ctx) return;
        
        const departments = this.data.department_comparison || [];
        const chartData = this.processDepartmentData(departments);
        
        this.charts.departmentComparison = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Average Rating',
                    data: chartData.avgRatings,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Evidence Entries',
                    data: chartData.totalEntries,
                    backgroundColor: 'rgba(25, 135, 84, 0.8)',
                    borderColor: '#198754',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Average Rating'
                        },
                        min: 0,
                        max: 5
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Evidence Entries'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const dept = departments[context.dataIndex];
                                if (context.datasetIndex === 0) {
                                    return `Active Employees: ${dept?.active_employees || 0}`;
                                } else {
                                    return `Dimensions Covered: ${dept?.dimensions_covered || 0}`;
                                }
                            }
                        }
                    }
                }
            }
        });
    },
    
    // Performance Distribution Chart
    initPerformanceDistributionChart: function() {
        const ctx = document.getElementById('performanceDistributionChart');
        if (!ctx) return;
        
        const distribution = this.data.performance_distribution?.distribution || [];
        
        const labels = distribution.map(item => item.performance_category);
        const data = distribution.map(item => item.entry_count);
        const colors = [
            '#198754', // Excellent - Green
            '#20c997', // Good - Teal
            '#ffc107', // Satisfactory - Yellow
            '#fd7e14', // Needs Improvement - Orange
            '#dc3545'  // Unsatisfactory - Red
        ];
        
        this.charts.performanceDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    },
    
    // Usage Analytics Chart
    initUsageAnalyticsChart: function() {
        const ctx = document.getElementById('usageAnalyticsChart');
        if (!ctx) return;
        
        const usageData = this.data.usage_analytics?.daily_usage || [];
        const chartData = this.processUsageData(usageData);
        
        this.charts.usageAnalytics = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Daily Entries',
                    data: chartData.entries,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Active Users',
                    data: chartData.users,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Daily Entries'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Active Users'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    },
    
    // Adoption Metrics Chart
    initAdoptionMetricsChart: function() {
        const ctx = document.getElementById('adoptionMetricsChart');
        if (!ctx) return;
        
        const adoption = this.data.adoption_metrics || {};
        const adoptionRate = adoption.adoption_rate || 0;
        
        this.charts.adoptionMetrics = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active Users', 'Inactive Users'],
                datasets: [{
                    data: [adoptionRate, 100 - adoptionRate],
                    backgroundColor: [
                        adoptionRate >= 80 ? '#198754' : adoptionRate >= 60 ? '#20c997' : adoptionRate >= 40 ? '#ffc107' : '#dc3545',
                        '#e9ecef'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed.toFixed(1)}%`;
                            }
                        }
                    }
                }
            }
        });
    },
    
    // Organizational Trends Chart
    initOrganizationalTrendsChart: function() {
        const ctx = document.getElementById('organizationalTrendsChart');
        if (!ctx) return;
        
        const patterns = this.data.organizational_patterns || {};
        const insights = this.data.reporting_insights?.dimension_insights || [];
        
        const labels = insights.map(item => this.formatDimensionName(item.dimension));
        const ratings = insights.map(item => item.avg_rating);
        const entries = insights.map(item => item.entry_count);
        
        this.charts.organizationalTrends = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Rating',
                    data: ratings,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Evidence Volume',
                    data: entries,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: '#ffc107',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Average Rating'
                        },
                        min: 0,
                        max: 5
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Evidence Volume'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    },
    
    // Handle department filter change
    handleDepartmentChange: function(department) {
        const url = new URL(window.location);
        if (department) {
            url.searchParams.set('department', department);
        } else {
            url.searchParams.delete('department');
        }
        window.location.href = url.toString();
    },
    
    // Handle period filter change
    handlePeriodChange: function(periodId) {
        const url = new URL(window.location);
        if (periodId) {
            url.searchParams.set('period_id', periodId);
        } else {
            url.searchParams.delete('period_id');
        }
        window.location.href = url.toString();
    },
    
    // Handle department view change
    handleDepartmentViewChange: function(viewType) {
        if (!this.charts.departmentComparison) return;
        
        const departments = this.data.department_comparison || [];
        const chartData = this.processDepartmentData(departments);
        
        let datasets = [];
        
        switch (viewType) {
            case 'deptRating':
                datasets = [{
                    label: 'Average Rating',
                    data: chartData.avgRatings,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: '#0d6efd',
                    borderWidth: 1
                }];
                this.charts.departmentComparison.options.scales.y.max = 5;
                this.charts.departmentComparison.options.scales.y1.display = false;
                break;
                
            case 'deptEntries':
                datasets = [{
                    label: 'Evidence Entries',
                    data: chartData.totalEntries,
                    backgroundColor: 'rgba(25, 135, 84, 0.8)',
                    borderColor: '#198754',
                    borderWidth: 1
                }];
                this.charts.departmentComparison.options.scales.y.max = undefined;
                this.charts.departmentComparison.options.scales.y1.display = false;
                break;
                
            case 'deptEmployees':
                datasets = [{
                    label: 'Active Employees',
                    data: chartData.activeEmployees,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }];
                this.charts.departmentComparison.options.scales.y.max = undefined;
                this.charts.departmentComparison.options.scales.y1.display = false;
                break;
        }
        
        this.charts.departmentComparison.data.datasets = datasets;
        this.charts.departmentComparison.update();
    },
    
    // Process department data
    processDepartmentData: function(departments) {
        const labels = departments.map(dept => dept.department || 'Unknown');
        const avgRatings = departments.map(dept => parseFloat(dept.avg_rating) || 0);
        const totalEntries = departments.map(dept => parseInt(dept.total_entries) || 0);
        const activeEmployees = departments.map(dept => parseInt(dept.active_employees) || 0);
        
        return { labels, avgRatings, totalEntries, activeEmployees };
    },
    
    // Process usage data
    processUsageData: function(usageData) {
        const last30Days = usageData.slice(-30);
        const labels = last30Days.map(item => new Date(item.usage_date).toLocaleDateString());
        const entries = last30Days.map(item => parseInt(item.daily_entries) || 0);
        const users = last30Days.map(item => parseInt(item.active_users) || 0);
        
        return { labels, entries, users };
    },
    
    // Format dimension name
    formatDimensionName: function(dimension) {
        return dimension.charAt(0).toUpperCase() + dimension.slice(1).replace('_', ' ');
    },
    
    // Export dashboard
    exportDashboard: function() {
        const exportData = {
            dashboard_type: 'hr_analytics',
            data: this.data,
            exported_at: new Date().toISOString(),
            filters: {
                department: new URLSearchParams(window.location.search).get('department'),
                period_id: new URLSearchParams(window.location.search).get('period_id')
            }
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `hr_analytics_dashboard_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        PerfEval.showAlert('success', 'HR Analytics data exported successfully');
    },
    
    // Generate comprehensive report
    generateReport: function() {
        const reportData = {
            report_type: 'organizational_performance',
            generated_at: new Date().toISOString(),
            summary: {
                total_employees: this.data.organizational_overview?.total_employees || 0,
                total_evidence_entries: this.data.organizational_overview?.total_evidence_entries || 0,
                avg_organizational_rating: this.data.organizational_overview?.avg_organizational_rating || 0,
                system_adoption_rate: this.data.organizational_overview?.system_adoption_rate || 0
            },
            department_analysis: this.data.department_comparison || [],
            performance_distribution: this.data.performance_distribution || {},
            usage_analytics: this.data.usage_analytics || {},
            adoption_metrics: this.data.adoption_metrics || {},
            recommendations: this.data.reporting_insights?.reporting_recommendations || []
        };
        
        // Create CSV format for easier analysis
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // Summary section
        csvContent += "ORGANIZATIONAL PERFORMANCE SUMMARY\n";
        csvContent += `Generated At,${reportData.generated_at}\n`;
        csvContent += `Total Employees,${reportData.summary.total_employees}\n`;
        csvContent += `Total Evidence Entries,${reportData.summary.total_evidence_entries}\n`;
        csvContent += `Average Rating,${reportData.summary.avg_organizational_rating}\n`;
        csvContent += `System Adoption Rate,${reportData.summary.system_adoption_rate}%\n\n`;
        
        // Department analysis
        csvContent += "DEPARTMENT ANALYSIS\n";
        csvContent += "Department,Average Rating,Total Entries,Active Employees,Dimensions Covered\n";
        reportData.department_analysis.forEach(dept => {
            csvContent += `${dept.department},${dept.avg_rating},${dept.total_entries},${dept.active_employees},${dept.dimensions_covered}\n`;
        });
        
        // Download CSV
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `organizational_performance_report_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        PerfEval.showAlert('success', 'Organizational performance report generated successfully');
    },
    
    // Refresh dashboard
    refreshDashboard: function() {
        window.location.reload();
    },
    
    // Destroy charts
    destroy: function() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        HRDashboard.init();
    } else {
        console.error('Chart.js not loaded');
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    HRDashboard.destroy();
});