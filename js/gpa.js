// Core JavaScript for Student Dropout Prediction System
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all functionality
    initializeCharts();
    initializeSearch();
    initializeFilters();
    initializeStudentModals();
    initializePagination();
    
});

// Chart Initialization
function initializeCharts() {
    // Risk Gauge Charts
    const highRisk = <?= $riskCounts['High'] ?>;
    const mediumRisk = <?= $riskCounts['Medium'] ?>;
    const lowRisk = <?= $riskCounts['Low'] ?>;
    const total = highRisk + mediumRisk + lowRisk;

    // Render gauge charts
    renderGauge("#highRiskGauge", highRisk, '#e74c3c', 'High Risk', highRisk, total);
    renderGauge("#mediumRiskGauge", mediumRisk, '#f1c40f', 'Medium Risk', mediumRisk, total);
    renderGauge("#lowRiskGauge", lowRisk, '#2ecc71', 'Low Risk', lowRisk, total);

    // Course-wise Risk Distribution Chart
    initializeCourseChart();
}

function renderGauge(element, value, color, label, count, total) {
    if (!document.querySelector(element)) return;
    
    const options = {
        chart: {
            type: 'radialBar',
            height: 180,
            offsetY: -20,
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                track: {
                    background: '#e7e7e7',
                    strokeWidth: '97%',
                },
                dataLabels: {
                    name: { 
                        show: true, 
                        offsetY: -10, 
                        fontSize: '16px',
                        fontWeight: 'bold',
                        color: '#333'
                    },
                    value: {
                        formatter: function (val) {
                            return val + "%";
                        },
                        fontSize: '22px',
                        show: true,
                    }
                }
            }
        },
        tooltip: {
            enabled: true,
            y: {
                formatter: function () {
                    return count + " student(s)";
                }
            }
        },
        colors: [color],
        labels: [label],
        series: [Math.round((value / (total || 1)) * 100)]
    };

    new ApexCharts(document.querySelector(element), options).render();
}

function initializeCourseChart() {
    const courseCtx = document.getElementById('courseChart');
    if (!courseCtx) return;
    
    new Chart(courseCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($courseStats as $course => $stats) {
                    echo "'" . addslashes($course) . "', ";
                }
                ?>
            ],
            datasets: [
                {
                    label: 'High Risk',
                    data: [
                        <?php 
                        foreach ($courseStats as $stats) {
                            echo $stats['High'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(231, 76, 60, 0.8)'
                },
                {
                    label: 'Medium Risk',
                    data: [
                        <?php 
                        foreach ($courseStats as $stats) {
                            echo $stats['Medium'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(241, 196, 15, 0.8)'
                },
                {
                    label: 'Low Risk',
                    data: [
                        <?php 
                        foreach ($courseStats as $stats) {
                            echo $stats['Low'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(46, 204, 113, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            aspectRatio: 2,
            plugins: {
                title: {
                    display: true,
                    text: 'Dropout Risk by Course<?= $hasValidFilter ? " (Filtered)" : "" ?>'
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            }
        }
    });
}

// Search Functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchTerm');
    const searchForm = document.querySelector('form[method="POST"]');
    
    if (!searchInput || !searchForm) return;
    
    // Handle search input
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Real-time validation
    searchInput.addEventListener('input', function() {
        const value = this.value.trim();
        updateSearchButton(value.length > 0);
    });
    
    // Handle search form submission
    searchForm.addEventListener('submit', function(e) {
        if (e.submitter && e.submitter.name === 'search') {
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm.length === 0) {
                e.preventDefault();
                showAlert('Please enter a search term', 'warning');
                searchInput.focus();
                return false;
            }
            
            if (searchTerm.length < 2) {
                e.preventDefault();
                showAlert('Search term must be at least 2 characters long', 'warning');
                searchInput.focus();
                return false;
            }
            
            setLoadingState(true);
        }
    });
    
    // Highlight search terms if present
    const searchTerm = '<?= htmlspecialchars($searchTerm ?? '') ?>';
    if (searchTerm && searchTerm.length > 0) {
        highlightSearchTerms(searchTerm);
    }
}

function performSearch() {
    const searchInput = document.getElementById('searchTerm');
    const searchTerm = searchInput.value.trim();
    
    if (searchTerm.length === 0) {
        showAlert('Please enter a search term', 'warning');
        searchInput.focus();
        return false;
    }
    
    if (searchTerm.length < 2) {
        showAlert('Search term must be at least 2 characters long', 'warning');
        searchInput.focus();
        return false;
    }
    
    setLoadingState(true);
    document.querySelector('button[name="search"]').click();
}

function updateSearchButton(hasContent) {
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        if (hasContent) {
            searchBtn.classList.add('active');
        } else {
            searchBtn.classList.remove('active');
        }
    }
}

function setLoadingState(loading) {
    const searchBtn = document.querySelector('.search-btn');
    const searchInput = document.getElementById('searchTerm');
    
    if (loading && searchBtn && searchInput) {
        searchBtn.classList.add('btn-loading');
        searchBtn.disabled = true;
        searchInput.disabled = true;
        
        // Re-enable after timeout as backup
        setTimeout(() => {
            searchBtn.classList.remove('btn-loading');
            searchBtn.disabled = false;
            searchInput.disabled = false;
        }, 10000);
    }
}

function highlightSearchTerms(term) {
    if (!term || term.length < 2) return;
    
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, index) => {
            // Only highlight Student ID (0) and Name (1) columns
            if (index === 0 || index === 1) {
                highlightInElement(cell, term);
            }
        });
    });
}

function highlightInElement(element, term) {
    const text = element.textContent;
    const regex = new RegExp(`(${escapeRegExp(term)})`, 'gi');
    
    if (regex.test(text)) {
        const html = text.replace(regex, '<mark>$1</mark>');
        element.innerHTML = html;
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Filter Functionality
function initializeFilters() {
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;
    
    // Handle filter form submission with loading state
    filterForm.addEventListener('submit', function() {
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Filtering...';
        }
    });
}

// Apply filters function - called by button
function applyFilters() {
    const yearValue = document.getElementById('filterYear').value;
    const semesterValue = document.getElementById('filterSemester').value;
    const courseValue = document.getElementById('filterCourse').value;
    const riskValue = document.getElementById('filterRiskLevel').value;
    
    // Build URL with filter parameters
    let url = window.location.pathname + '?';
    let params = [];
    
    if (yearValue) params.push('filterYear=' + encodeURIComponent(yearValue));
    if (semesterValue) params.push('filterSemester=' + encodeURIComponent(semesterValue));
    if (courseValue) params.push('filterCourse=' + encodeURIComponent(courseValue));
    if (riskValue) params.push('filterRiskLevel=' + encodeURIComponent(riskValue));
    
    // Preserve other parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('entries')) params.push('entries=' + urlParams.get('entries'));
    if (urlParams.get('debug')) params.push('debug=' + urlParams.get('debug'));
    
    if (params.length > 0) {
        url += params.join('&');
    } else {
        // No filters, go to clean URL
        url = window.location.pathname;
        if (urlParams.get('entries')) url += '?entries=' + urlParams.get('entries');
    }
    
    window.location.href = url;
}

// Student Modal Charts
function initializeStudentModals() {
    <?php foreach ($paginatedStudents as $index => $student): ?>
        initializeStudentModal(<?= $index ?>, <?= json_encode($student) ?>);
    <?php endforeach; ?>
}

function initializeStudentModal(index, studentData) {
    // Wait for modal to be shown before initializing charts
    const modal = document.getElementById('statsModal' + index);
    if (!modal) return;
    
    $(modal).on('shown.bs.modal', function () {
        initializeStudentCharts(index, studentData);
    });
}

function initializeStudentCharts(index, student) {
    // GPA Gauge
    const gpaElement = document.querySelector("#gpaGauge" + index);
    if (gpaElement) {
        new ApexCharts(gpaElement, {
            chart: { type: 'radialBar', height: 120, sparkline: { enabled: true } },
            plotOptions: {
                radialBar: {
                    startAngle: -90, endAngle: 90,
                    track: { background: '#e7e7e7', strokeWidth: '97%' },
                    hollow: { size: '35%' },
                    dataLabels: { show: false }
                }
            },
            colors: [parseFloat(student.GPA) < 2.0 ? '#e74c3c' : (parseFloat(student.GPA) < 2.5 ? '#f1c40f' : '#2ecc71')],
            series: [Math.min(100, parseFloat(student.GPA) / 4.0 * 100)]
        }).render();
    }
    
    // Attendance Gauge
    const attendanceElement = document.querySelector("#attendanceGauge" + index);
    if (attendanceElement) {
        new ApexCharts(attendanceElement, {
            chart: { type: 'radialBar', height: 120, sparkline: { enabled: true } },
            plotOptions: {
                radialBar: {
                    startAngle: -90, endAngle: 90,
                    track: { background: '#e7e7e7', strokeWidth: '97%' },
                    hollow: { size: '35%' },
                    dataLabels: { show: false }
                }
            },
            colors: [parseFloat(student.Attendance) < 75 ? '#e74c3c' : (parseFloat(student.Attendance) < 85 ? '#f1c40f' : '#2ecc71')],
            series: [Math.min(100, parseFloat(student.Attendance))]
        }).render();
    }
    
    // Risk Gauge
    const riskElement = document.querySelector("#riskGauge" + index);
    if (riskElement) {
        new ApexCharts(riskElement, {
            chart: { type: 'radialBar', height: 120, sparkline: { enabled: true } },
            plotOptions: {
                radialBar: {
                    startAngle: -90, endAngle: 90,
                    track: { background: '#e7e7e7', strokeWidth: '97%' },
                    hollow: { size: '35%' },
                    dataLabels: { show: false }
                }
            },
            colors: [parseFloat(student.dropout_percentage) > 66 ? '#e74c3c' : (parseFloat(student.dropout_percentage) > 33 ? '#f1c40f' : '#2ecc71')],
            series: [Math.min(100, parseFloat(student.dropout_percentage))]
        }).render();
    }
    
    // Risk Factors Radar Chart
    const riskFactorsElement = document.getElementById('riskFactorsChart' + index);
    if (riskFactorsElement) {
        new Chart(riskFactorsElement.getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['GPA', 'Attendance', 'Financial', 'Engagement', 'Other Factors'],
                datasets: [{
                    label: 'Risk Factors',
                    data: [
                        parseFloat(student.GPA) < 2.0 ? 80 : (parseFloat(student.GPA) < 2.5 ? 50 : 20),
                        parseFloat(student.Attendance) < 75 ? 80 : (parseFloat(student.Attendance) < 85 ? 50 : 20),
                        parseFloat(student.balance) > 10000 ? 80 : (parseFloat(student.balance) > 5000 ? 50 : 20),
                        parseFloat(student.dropout_percentage) > 66 ? 80 : (parseFloat(student.dropout_percentage) > 33 ? 50 : 20),
                        student.final_risk_level === 'High Risk' ? 80 : (student.final_risk_level === 'Medium Risk' ? 50 : 20)
                    ],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(255, 99, 132)',
                    pointBackgroundColor: 'rgb(255, 99, 132)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(255, 99, 132)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 1.8,
                plugins: {
                    title: { display: true, text: 'Risk Factor Analysis' }
                },
                scales: {
                    r: {
                        angleLines: { display: true },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });
    }
}

// Pagination
function initializePagination() {
    // Add click handlers for pagination buttons
    const paginationBtns = document.querySelectorAll('.pagination-btn:not(.disabled)');
    paginationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Add loading state
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';
        });
    });
}

// Print Report Function
function printStatsReport(studentId, studentName) {
    const printWindow = window.open('', '_blank');
    const currentDate = new Date().toLocaleDateString();
    
    // Find student data
    const studentRow = Array.from(document.querySelectorAll('table tbody tr')).find(row => {
        const idCell = row.querySelector('td:first-child');
        return idCell && idCell.textContent.includes(studentId);
    });
    
    if (!studentRow) {
        showAlert('Student data not found for printing', 'error');
        return;
    }
    
    const cells = studentRow.querySelectorAll('td');
    const riskLevel = cells[5].textContent.trim();
    const dropoutPercentage = cells[6].textContent.trim();
    const gpa = cells[3] ? cells[3].textContent.trim() : 'N/A';
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Student Performance Report - ${studentName}</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                h1, h2, h3 { color: #006633; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #006633; padding-bottom: 10px; }
                .section { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .risk-high { color: #e74c3c; font-weight: bold; }
                .risk-medium { color: #f1c40f; font-weight: bold; }
                .risk-low { color: #2ecc71; font-weight: bold; }
                .footer { margin-top: 50px; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Student Performance Report</h1>
                <p><strong>Student ID:</strong> ${studentId} | <strong>Name:</strong> ${studentName}</p>
                <p><strong>Generated on:</strong> ${currentDate}</p>
            </div>
            
            <div class="section">
                <h2>Risk Assessment Summary</h2>
                <table>
                    <tr><th>Risk Level</th><td>${riskLevel}</td></tr>
                    <tr><th>Dropout Percentage</th><td>${dropoutPercentage}</td></tr>
                    <tr><th>Current GPA</th><td>${gpa}</td></tr>
                </table>
            </div>
            
            <div class="footer">
                <p>This report is generated by the Student Dropout Prediction System.</p>
                <p>Confidential: For administrative and advisory use only.</p>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    setTimeout(() => printWindow.print(), 500);
}

// Utility Functions
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.temp-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} temp-alert`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    alertDiv.innerHTML = `
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
        ${message}
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 5000);
}

// Debug toggle function
function toggleDebug() {
    const currentUrl = new URL(window.location);
    const debug = currentUrl.searchParams.get('debug');
    
    if (debug === '1') {
        currentUrl.searchParams.delete('debug');
    } else {
        currentUrl.searchParams.set('debug', '1');
    }
    
    window.location.href = currentUrl.toString();
}

// CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .search-btn.active {
        background: rgba(1, 129, 55, 1) !important;
        transform: scale(1.05);
        transition: all 0.3s ease;
    }
    
    .btn-loading {
        position: relative;
        color: transparent !important;
    }
    
    .btn-loading::after {
        content: "";
        position: absolute;
        width: 16px; height: 16px;
        top: 50%; left: 50%;
        margin-left: -8px; margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    mark {
        background-color: #fff3cd;
        color: #856404;
        padding: 1px 2px;
        border-radius: 2px;
    }
`;
document.head.appendChild(style);

// Make functions available globally
window.applyFilters = applyFilters;
window.printStatsReport = printStatsReport;
window.toggleDebug = toggleDebug;