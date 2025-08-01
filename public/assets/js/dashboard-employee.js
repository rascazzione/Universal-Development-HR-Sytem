/**
 * Employee Dashboard JavaScript
 * Interactive charts and personal analytics functionality
 */

// Employee Dashboard Object
const EmployeeDashboard = {
    charts: {},
    data: window.dashboardData || {},
    
    // Initialize dashboard
    init: function() {
        this.initCharts();
        this.initEventListeners();
        this.initFilters();
        console.log('Employee Dashboard initialized');
    },
    
    // Initialize all charts
    initCharts: function() {
        this.initPerformanceTrendsChart();
        this.initGoalProgressChart();
        this.initPeerComparisonChart();
        this.initEvidenceTimelineChart();
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
    },
    
    // Initialize filters
    initFilters: function() {
        // Set current period if available
        const currentPeriod = this.data.personal_overview?.period_info;
        if (currentPeriod) {
            const periodFilter = document.getElementById('periodFilter');
            if (periodFilter && currentPeriod.period_id) {
                periodFilter.value = currentPeriod.period_id;
            }
        }
    },
    
    // Performance Trends Chart
    initPerformanceTrendsChart: function() {
        const ctx = document.getElementById('performanceTrendsChart');
        if (!ctx) return;
        
        const trendsData = this.data.performance_trends?.monthly_data || [];
        const chartData = this.processPerformanceTrends(trendsData);
        
        this.charts.performanceTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Overall Performance',
                    data: chartData.overall,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Performance Rating'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time Period'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                return 'out of 5.0';
                            }
                        }
                    }
                }
            }
        });
    },
    
    // Goal Progress Chart
    initGoalProgressChart: function() {
        const ctx = document.getElementById('goalProgressChart');
        if (!ctx) return;
        
        const goals = this.data.goal_progress || [];
        if (goals.length === 0) return;
        
        const labels = goals.map(goal => this.formatDimensionName(goal.dimension));
        const progress = goals.map(goal => goal.progress_percentage);
        const colors = goals.map((goal, index) => {
            const hue = (index * 360 / goals.length) % 360;
            return `hsl(${hue}, 70%, 50%)`;
        });
        
        this.charts.goalProgress = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: progress,
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
                                const goal = goals[context.dataIndex];
                                return [
                                    `${context.label}: ${context.parsed}%`,
                                    `Current: ${goal.current_rating}/5.0`,
                                    `Target: ${goal.target_rating}/5.0`
                                ];
                            }
                        }
                    }
                }
            }
        });
    },
    
    // Peer Comparison Chart
    initPeerComparisonChart: function() {
        const ctx = document.getElementById('peerComparisonChart');
        if (!ctx) return;
        
        const comparison = this.data.peer_comparison;
        if (!comparison?.comparison_available) return;
        
        const comparisons = comparison.dimension_comparisons || [];
        const labels = comparisons.map(comp => this.formatDimensionName(comp.dimension));
        const employeeRatings = comparisons.map(comp => comp.employee_rating);
        const deptAverages = comparisons.map(comp => comp.department_avg);
        
        this.charts.peerComparison = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Your Performance',
                    data: employeeRatings,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#0d6efd'
                }, {
                    label: 'Department Average',
                    data: deptAverages,
                    borderColor: '#6c757d',
                    backgroundColor: 'rgba(108, 117, 125, 0.2)',
                    pointBackgroundColor: '#6c757d',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#6c757d'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 5,
                        ticks: {
                            stepSize: 1
                        }
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
    
    // Evidence Timeline Chart
    initEvidenceTimelineChart: function() {
        const ctx = document.getElementById('evidenceTimelineChart');
        if (!ctx) return;
        
        const timeline = this.data.evidence_history?.timeline || {};
        const chartData = this.processEvidenceTimeline(timeline);
        
        if (chartData.labels.length === 0) return;
        
        this.charts.evidenceTimeline = new Chart(ctx, {
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
                            text: 'Date'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
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
        if (viewType === 'trendByDimension') {
            this.updatePerformanceTrendsChart('dimension');
        } else {
            this.updatePerformanceTrendsChart('overall');
        }
    },
    
    // Update performance trends chart
    updatePerformanceTrendsChart: function(viewType) {
        if (!this.charts.performanceTrends) return;
        
        const trendsData = this.data.performance_trends?.monthly_data || [];
        
        if (viewType === 'dimension') {
            const chartData = this.processPerformanceTrendsByDimension(trendsData);
            
            this.charts.performanceTrends.data.datasets = [
                {
                    label: 'Responsibilities',
                    data: chartData.responsibilities,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'KPIs',
                    data: chartData.kpis,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Competencies',
                    data: chartData.competencies,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Values',
                    data: chartData.values,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4
                }
            ];
        } else {
            const chartData = this.processPerformanceTrends(trendsData);
            
            this.charts.performanceTrends.data.datasets = [{
                label: 'Overall Performance',
                data: chartData.overall,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }];
        }
        
        this.charts.performanceTrends.update();
    },
    
    // Process performance trends data
    processPerformanceTrends: function(trendsData) {
        const monthlyMap = {};
        
        trendsData.forEach(item => {
            const month = item.month_year;
            if (!monthlyMap[month]) {
                monthlyMap[month] = { totalRating: 0, count: 0 };
            }
            monthlyMap[month].totalRating += parseFloat(item.avg_rating) * parseInt(item.entry_count);
            monthlyMap[month].count += parseInt(item.entry_count);
        });
        
        const labels = Object.keys(monthlyMap).sort();
        const overall = labels.map(month => 
            monthlyMap[month].count > 0 ? monthlyMap[month].totalRating / monthlyMap[month].count : 0
        );
        
        return { labels, overall };
    },
    
    // Process performance trends by dimension
    processPerformanceTrendsByDimension: function(trendsData) {
        const dimensionMap = {
            responsibilities: {},
            kpis: {},
            competencies: {},
            values: {}
        };
        
        trendsData.forEach(item => {
            const month = item.month_year;
            const dimension = item.dimension;
            
            if (dimensionMap[dimension]) {
                if (!dimensionMap[dimension][month]) {
                    dimensionMap[dimension][month] = { totalRating: 0, count: 0 };
                }
                dimensionMap[dimension][month].totalRating += parseFloat(item.avg_rating) * parseInt(item.entry_count);
                dimensionMap[dimension][month].count += parseInt(item.entry_count);
            }
        });
        
        const allMonths = new Set();
        Object.values(dimensionMap).forEach(dimData => {
            Object.keys(dimData).forEach(month => allMonths.add(month));
        });
        
        const labels = Array.from(allMonths).sort();
        
        const result = {};
        Object.keys(dimensionMap).forEach(dimension => {
            result[dimension] = labels.map(month => {
                const data = dimensionMap[dimension][month];
                return data && data.count > 0 ? data.totalRating / data.count : 0;
            });
        });
        
        return result;
    },
    
    // Process evidence timeline
    processEvidenceTimeline: function(timeline) {
        const dates = Object.keys(timeline).sort().slice(-30); // Last 30 days
        const labels = dates.map(date => new Date(date).toLocaleDateString());
        const entries = dates.map(date => timeline[date].length);
        
        return { labels, entries };
    },
    
    // Format dimension name
    formatDimensionName: function(dimension) {
        return dimension.charAt(0).toUpperCase() + dimension.slice(1).replace('_', ' ');
    },
    
    // Export dashboard
    exportDashboard: function() {
        const exportData = {
            dashboard_type: 'employee',
            employee_id: window.employeeId,
            data: this.data,
            exported_at: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `employee_dashboard_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        PerfEval.showAlert('success', 'Dashboard data exported successfully');
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
        EmployeeDashboard.init();
    } else {
        console.error('Chart.js not loaded');
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    EmployeeDashboard.destroy();
});