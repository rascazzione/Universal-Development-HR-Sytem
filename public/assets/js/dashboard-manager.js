/**
 * Manager Dashboard JavaScript
 * Interactive charts and dashboard functionality
 */

// Dashboard Manager Object
const ManagerDashboard = {
    charts: {},
    data: window.dashboardData || {},
    
    // Initialize dashboard
    init: function() {
        this.initCharts();
        this.initEventListeners();
        this.initFilters();
        console.log('Manager Dashboard initialized');
    },
    
    // Initialize all charts
    initCharts: function() {
        this.initEvidenceTrendsChart();
        this.initPerformanceDistributionChart();
        this.initTeamComparisonChart();
        this.initFeedbackFrequencyChart();
    },
    
    // Initialize event listeners
    initEventListeners: function() {
        // Period filter change
        const periodFilter = document.getElementById('periodFilter');
        if (periodFilter) {
            periodFilter.addEventListener('change', (e) => {
                this.handlePeriodChange(e.target.value);
            });
        }
        
        // Trend view toggle
        const trendViewRadios = document.querySelectorAll('input[name="trendView"]');
        trendViewRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.handleTrendViewChange(e.target.id);
            });
        });
        
        // Export button
        window.exportDashboard = () => this.exportDashboard();
        window.refreshDashboard = () => this.refreshDashboard();
    },
    
    // Initialize filters
    initFilters: function() {
        // Set current period if available
        const currentPeriod = this.data.team_overview?.period_info;
        if (currentPeriod) {
            const periodFilter = document.getElementById('periodFilter');
            if (periodFilter && currentPeriod.period_id) {
                periodFilter.value = currentPeriod.period_id;
            }
        }
    },
    
    // Evidence Trends Chart
    initEvidenceTrendsChart: function() {
        const ctx = document.getElementById('evidenceTrendsChart');
        if (!ctx) return;
        
        const trendsData = this.data.evidence_trends?.trends || [];
        const dimensionData = this.data.evidence_trends?.dimension_breakdown || [];
        
        // Process monthly trends data
        const monthlyData = this.processMonthlyTrends(trendsData);
        
        this.charts.evidenceTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Evidence Entries',
                    data: monthlyData.entries,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Average Rating',
                    data: monthlyData.ratings,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Evidence Entries'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Average Rating'
                        },
                        min: 0,
                        max: 5,
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
                                if (context.datasetIndex === 1) {
                                    return 'out of 5.0';
                                }
                                return '';
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
        
        const distribution = this.data.performance_insights?.performance_distribution || {};
        
        const labels = Object.keys(distribution);
        const data = Object.values(distribution);
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
    
    // Team Comparison Chart
    initTeamComparisonChart: function() {
        const ctx = document.getElementById('teamComparisonChart');
        if (!ctx) return;
        
        const comparison = this.data.team_comparison?.dimension_comparison || {};
        
        const dimensions = Object.keys(comparison);
        const avgRatings = dimensions.map(dim => comparison[dim]?.avg_rating || 0);
        const employeeCounts = dimensions.map(dim => comparison[dim]?.employee_count || 0);
        
        this.charts.teamComparison = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dimensions.map(dim => this.formatDimensionName(dim)),
                datasets: [{
                    label: 'Average Rating',
                    data: avgRatings,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Employee Count',
                    data: employeeCounts,
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
                            text: 'Employee Count'
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
    
    // Feedback Frequency Chart
    initFeedbackFrequencyChart: function() {
        const ctx = document.getElementById('feedbackFrequencyChart');
        if (!ctx) return;
        
        const frequencyData = this.data.feedback_analytics?.frequency_distribution || [];
        
        // Process frequency data for chart
        const chartData = this.processFrequencyData(frequencyData);
        
        this.charts.feedbackFrequency = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Evidence Entries',
                    data: chartData.entries,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: '#0d6efd',
                    borderWidth: 1
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
                            text: 'Number of Entries'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Team Members'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return `Employee ${context[0].label}`;
                            },
                            label: function(context) {
                                const employee = frequencyData[context.dataIndex];
                                return [
                                    `Entries: ${context.parsed.y}`,
                                    `Active Days: ${employee?.active_days || 0}`,
                                    `Period Days: ${employee?.period_days || 0}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    },
    
    // Handle period change
    handlePeriodChange: function(periodId) {
        const url = new URL(window.location);
        if (periodId) {
            url.searchParams.set('period_id', periodId);
        } else {
            url.searchParams.delete('period_id');
        }
        window.location.href = url.toString();
    },
    
    // Handle trend view change
    handleTrendViewChange: function(viewType) {
        if (viewType === 'trendDimension') {
            this.updateEvidenceTrendsChart('dimension');
        } else {
            this.updateEvidenceTrendsChart('monthly');
        }
    },
    
    // Update evidence trends chart
    updateEvidenceTrendsChart: function(viewType) {
        if (!this.charts.evidenceTrends) return;
        
        if (viewType === 'dimension') {
            const dimensionData = this.data.evidence_trends?.dimension_breakdown || [];
            const chartData = this.processDimensionData(dimensionData);
            
            this.charts.evidenceTrends.data.labels = chartData.labels;
            this.charts.evidenceTrends.data.datasets = [{
                label: 'Average Rating',
                data: chartData.ratings,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    '#0d6efd',
                    '#198754',
                    '#ffc107',
                    '#dc3545'
                ],
                borderWidth: 1
            }];
            
            this.charts.evidenceTrends.config.type = 'bar';
            this.charts.evidenceTrends.options.scales.y1.display = false;
        } else {
            // Reset to monthly view
            const trendsData = this.data.evidence_trends?.trends || [];
            const monthlyData = this.processMonthlyTrends(trendsData);
            
            this.charts.evidenceTrends.data.labels = monthlyData.labels;
            this.charts.evidenceTrends.data.datasets = [{
                label: 'Evidence Entries',
                data: monthlyData.entries,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Average Rating',
                data: monthlyData.ratings,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }];
            
            this.charts.evidenceTrends.config.type = 'line';
            this.charts.evidenceTrends.options.scales.y1.display = true;
        }
        
        this.charts.evidenceTrends.update();
    },
    
    // Process monthly trends data
    processMonthlyTrends: function(trendsData) {
        const monthlyMap = {};
        
        trendsData.forEach(item => {
            const month = item.month_year;
            if (!monthlyMap[month]) {
                monthlyMap[month] = { entries: 0, totalRating: 0, count: 0 };
            }
            monthlyMap[month].entries += parseInt(item.entry_count);
            monthlyMap[month].totalRating += parseFloat(item.avg_rating) * parseInt(item.entry_count);
            monthlyMap[month].count += parseInt(item.entry_count);
        });
        
        const labels = Object.keys(monthlyMap).sort();
        const entries = labels.map(month => monthlyMap[month].entries);
        const ratings = labels.map(month => 
            monthlyMap[month].count > 0 ? monthlyMap[month].totalRating / monthlyMap[month].count : 0
        );
        
        return { labels, entries, ratings };
    },
    
    // Process dimension data
    processDimensionData: function(dimensionData) {
        const labels = dimensionData.map(item => this.formatDimensionName(item.dimension));
        const ratings = dimensionData.map(item => parseFloat(item.avg_rating));
        
        return { labels, ratings };
    },
    
    // Process frequency data
    processFrequencyData: function(frequencyData) {
        const labels = frequencyData.map((item, index) => `Emp ${index + 1}`);
        const entries = frequencyData.map(item => parseInt(item.entry_count));
        
        return { labels, entries };
    },
    
    // Format dimension name
    formatDimensionName: function(dimension) {
        return dimension.charAt(0).toUpperCase() + dimension.slice(1).replace('_', ' ');
    },
    
    // Export dashboard
    exportDashboard: function() {
        const exportData = {
            dashboard_type: 'manager',
            manager_id: window.managerId,
            data: this.data,
            exported_at: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `manager_dashboard_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        PerfEval.showAlert('success', 'Dashboard data exported successfully');
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
        ManagerDashboard.init();
    } else {
        console.error('Chart.js not loaded');
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    ManagerDashboard.destroy();
});