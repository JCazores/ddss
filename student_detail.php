<?php
// Add this CSS to your main stylesheet or in a <style> tag
?>
<style>
/* Enhanced Modal Styles */
.modal-xl {
    max-width: 95%;
    margin: 1rem auto;
}

.modal-content {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.modal-header {
    background: linear-gradient(135deg, #018137 0%, #02a644 100%);
    color: white;
    padding: 25px 30px;
    border-bottom: none;
    position: relative;
}

.modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.modal-header .close {
    color: white;
    opacity: 0.8;
    font-size: 28px;
    position: relative;
    z-index: 1;
}

.modal-header .close:hover {
    opacity: 1;
}

.modal-title {
    font-size: 24px;
    font-weight: bold;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.player-avatar-small {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255,255,255,0.3);
}

.player-avatar-small i {
    font-size: 20px;
    color: rgba(255,255,255,0.9);
}

.modal-body {
    padding: 0;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-section {
    padding: 25px 30px;
    border-bottom: 1px solid #eee;
}

.modal-section:last-child {
    border-bottom: none;
}

.section-title {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #018137;
    display: inline-block;
}

.stat-grid-modal {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-item-modal {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-item-modal::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #018137, #02a644);
}

.stat-item-modal:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    border-color: #018137;
}

.stat-value-modal {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 8px;
    color: #2c3e50;
}

.stat-label-modal {
    font-size: 12px;
    color: #7f8c8d;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-grade {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    color: white;
}

.grade-a-plus, .grade-a { background: #27ae60; }
.grade-b-plus, .grade-b { background: #3498db; }
.grade-c-plus, .grade-c { background: #f39c12; }
.grade-f { background: #e74c3c; }

.risk-display {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 20px;
}

.risk-badge-modal {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 20px;
    font-size: 16px;
    font-weight: bold;
    color: white;
    margin-bottom: 10px;
}

.risk-badge-modal.high { background: #e74c3c; }
.risk-badge-modal.medium { background: #f39c12; }
.risk-badge-modal.low { background: #27ae60; }

.progress-ring-small {
    width: 80px;
    height: 80px;
    margin: 10px auto;
    position: relative;
}

.progress-ring-small svg {
    width: 100%;
    height: 100%;
}

.progress-ring-small .progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 14px;
    font-weight: bold;
    color: #2c3e50;
}

.recommendation-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.recommendation-card-modal {
    background: white;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #018137;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.recommendation-card-modal h5 {
    color: #018137;
    margin-bottom: 15px;
    font-weight: bold;
    font-size: 14px;
}

.recommendation-list-modal {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recommendation-list-modal li {
    padding: 6px 0 6px 20px;
    border-bottom: 1px solid #f0f0f0;
    position: relative;
    font-size: 13px;
    line-height: 1.4;
}

.recommendation-list-modal li:last-child {
    border-bottom: none;
}

.recommendation-list-modal li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: #018137;
    font-weight: bold;
    font-size: 16px;
}

.chart-container-modal {
    height: 250px;
    margin: 20px 0;
}

.email-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .modal-xl { max-width: 98%; margin: 0.5rem auto; }
    .stat-grid-modal { grid-template-columns: 1fr; }
    .recommendation-grid { grid-template-columns: 1fr; }
}
</style>

<!-- Enhanced Student Details Modal -->
<?php foreach ($paginatedStudents as $index => $student): ?>
    <div class="modal fade" id="detailsModal<?= $index ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $index ?>" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <div class="modal-title" id="modalLabel<?= $index ?>">
                        <div class="player-avatar-small">
                            <i class="fa fa-user"></i>
                        </div>
                        <div>
                            <div><?= htmlspecialchars($student['sname']) ?></div>
                            <small style="font-size: 14px; opacity: 0.9;">ID: <?= htmlspecialchars($student['StudentID']) ?></small>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Risk Overview Section -->
                    <div class="modal-section">
                        <div class="row">
                            <div class="col-md-8">
                                <h3 class="section-title">Risk Assessment Overview</h3>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="risk-display">
                                            <?php 
                                            $riskClass = 'low';
                                            if ($student['final_risk_level'] == 'High Risk') $riskClass = 'high';
                                            elseif ($student['final_risk_level'] == 'Medium Risk') $riskClass = 'medium';
                                            ?>
                                            <div class="risk-badge-modal <?= $riskClass ?>">
                                                <?= htmlspecialchars($student['final_risk_level']) ?>
                                            </div>
                                            <div style="font-size: 12px; color: #666;">Overall Assessment</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="risk-display">
                                            <div class="progress-ring-small">
                                                <svg>
                                                    <circle cx="40" cy="40" r="35" fill="transparent" stroke="#f1f1f1" stroke-width="6"></circle>
                                                    <circle cx="40" cy="40" r="35" fill="transparent" 
                                                            stroke="<?= floatval($student['dropout_percentage']) > 70 ? '#e74c3c' : (floatval($student['dropout_percentage']) > 40 ? '#f39c12' : '#27ae60') ?>" 
                                                            stroke-width="6" stroke-linecap="round"
                                                            stroke-dasharray="220" 
                                                            stroke-dashoffset="<?= 220 - (220 * floatval($student['dropout_percentage']) / 100) ?>"
                                                            transform="rotate(-90 40 40)"></circle>
                                                </svg>
                                                <div class="progress-text"><?= htmlspecialchars($student['dropout_percentage']) ?>%</div>
                                            </div>
                                            <div style="font-size: 12px; color: #666;">Dropout Risk</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="risk-display">
                                            <div style="font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 5px;">
                                                <?= htmlspecialchars($student['model_predicted_risk']) ?>
                                            </div>
                                            <div style="font-size: 12px; color: #666;">Model Prediction</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h3 class="section-title">Quick Stats</h3>
                                <div class="stat-grid-modal" style="grid-template-columns: 1fr;">
                                    <div class="stat-item-modal">
                                        <div class="stat-value-modal"><?= htmlspecialchars($student['course']) ?></div>
                                        <div class="stat-label-modal">Course</div>
                                    </div>
                                    <div class="stat-item-modal">
                                        <div class="stat-value-modal"><?= htmlspecialchars($student['year']) ?></div>
                                        <div class="stat-label-modal">Year Level</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Performance Section -->
                    <div class="modal-section">
                        <h3 class="section-title">Academic Performance</h3>
                        <div class="stat-grid-modal">
                            <div class="stat-item-modal">
                                <div class="stat-value-modal"><?= htmlspecialchars($student['Attendance']) ?>%</div>
                                <div class="stat-label-modal">Attendance Rate</div>
                                <?php
                                $attendance = floatval($student['Attendance']);
                                $attendanceGrade = 'f';
                                if ($attendance >= 95) $attendanceGrade = 'a-plus';
                                elseif ($attendance >= 90) $attendanceGrade = 'a';
                                elseif ($attendance >= 85) $attendanceGrade = 'b-plus';
                                elseif ($attendance >= 80) $attendanceGrade = 'b';
                                elseif ($attendance >= 75) $attendanceGrade = 'c-plus';
                                elseif ($attendance >= 70) $attendanceGrade = 'c';
                                ?>
                                <div class="stat-grade grade-<?= $attendanceGrade ?>">
                                    <?= strtoupper(str_replace('-plus', '+', $attendanceGrade)) ?>
                                </div>
                            </div>
                            
                            <div class="stat-item-modal">
                                <div class="stat-value-modal"><?= htmlspecialchars($student['GPA']) ?></div>
                                <div class="stat-label-modal">Grade Point Average</div>
                                <?php
                                $gpa = floatval($student['GPA']);
                                $gpaGrade = 'f';
                                if ($gpa >= 3.5) $gpaGrade = 'a-plus';
                                elseif ($gpa >= 3.0) $gpaGrade = 'a';
                                elseif ($gpa >= 2.5) $gpaGrade = 'b-plus';
                                elseif ($gpa >= 2.0) $gpaGrade = 'b';
                                elseif ($gpa >= 1.5) $gpaGrade = 'c-plus';
                                elseif ($gpa >= 1.0) $gpaGrade = 'c';
                                ?>
                                <div class="stat-grade grade-<?= $gpaGrade ?>">
                                    <?= strtoupper(str_replace('-plus', '+', $gpaGrade)) ?>
                                </div>
                            </div>
                            
                            <div class="stat-item-modal">
                                <div class="stat-value-modal">₱<?= number_format($student['balance'], 2) ?></div>
                                <div class="stat-label-modal">Outstanding Balance</div>
                                <?php
                                $balance = floatval($student['balance']);
                                $financialGrade = 'f';
                                if ($balance <= 1000) $financialGrade = 'a-plus';
                                elseif ($balance <= 5000) $financialGrade = 'a';
                                elseif ($balance <= 10000) $financialGrade = 'b-plus';
                                elseif ($balance <= 20000) $financialGrade = 'b';
                                elseif ($balance <= 30000) $financialGrade = 'c-plus';
                                elseif ($balance <= 50000) $financialGrade = 'c';
                                ?>
                                <div class="stat-grade grade-<?= $financialGrade ?>">
                                    <?= strtoupper(str_replace('-plus', '+', $financialGrade)) ?>
                                </div>
                            </div>
                            
                            <?php if (isset($student['semester'])): ?>
                            <div class="stat-item-modal">
                                <div class="stat-value-modal">
                                    <?= $student['semester'] == '1' ? '1st' : ($student['semester'] == '2' ? '2nd' : $student['semester']) ?>
                                </div>
                                <div class="stat-label-modal">Semester</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Risk Analysis Charts Section -->
                    <div class="modal-section">
                        <h3 class="section-title">Risk Analysis Breakdown</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container-modal">
                                    <canvas id="riskChart<?= $index ?>" width="300" height="200"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container-modal">
                                    <canvas id="trendChart<?= $index ?>" width="300" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations Section -->
                    <div class="modal-section">
                        <h3 class="section-title">Analysis and Recommendations</h3>
                        <div class="recommendation-grid">
                            <div class="recommendation-card-modal">
                                <h5><i class="fa fa-warning"></i> Risk Factors Identified</h5>
                                <ul class="recommendation-list-modal">
                                    <?php foreach ($student['reasons'] as $reason): ?>
                                        <li><?= htmlspecialchars($reason) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="recommendation-card-modal">
                                <h5><i class="fa fa-lightbulb-o"></i> Recommended Solutions</h5>
                                <ul class="recommendation-list-modal">
                                    <?php foreach ($student['recommended_solutions'] as $solution): ?>
                                        <li><?= htmlspecialchars($solution) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="recommendation-card-modal">
                                <h5><i class="fa fa-tasks"></i> Administrative Actions</h5>
                                <ul class="recommendation-list-modal">
                                    <?php foreach ($student['admin_action'] as $action): ?>
                                        <li><?= htmlspecialchars($action) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Email Notification Section -->
                    <div class="modal-section">
                        <h3 class="section-title">Send Notification Email</h3>
                        <div class="email-section">
                            <form method="POST">
                                <input type="hidden" name="studentId" value="<?= htmlspecialchars($student['StudentID']) ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email<?= $index ?>">Email Recipients:</label>
                                            <input type="text" name="email" id="email<?= $index ?>" class="form-control" placeholder="example1@gmail.com, example2@gmail.com" required>
                                            <small class="text-muted">Separate multiple email addresses with commas</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="message<?= $index ?>">Message:</label>
                                            <textarea name="message" id="message<?= $index ?>" class="form-control" rows="4" required>Dear <?= htmlspecialchars($student['sname']) ?>,

We would like to inform you about your current academic status. Our system has identified that you may be at risk of dropping out. We encourage you to schedule a meeting with your academic advisor to discuss strategies for improvement.

Best regards,
Student Support Services</textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="send_email" class="btn btn-success">
                                    <i class="fa fa-envelope"></i> Send Email
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <a href="view_stats.php?id=<?= urlencode($student['StudentID']) ?>" class="btn btn-primary">
                        <i class="fa fa-bar-chart"></i> View Detailed Statistics
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
$(document).ready(function() {
    // Initialize charts when modals are shown
    $('.modal').on('shown.bs.modal', function() {
        var modalIndex = $(this).attr('id').replace('detailsModal', '');
        
        // Get student data (you'll need to pass this data to JavaScript)
        <?php foreach ($paginatedStudents as $index => $student): ?>
        if (modalIndex == '<?= $index ?>') {
            initializeCharts<?= $index ?>();
        }
        <?php endforeach; ?>
    });

    <?php foreach ($paginatedStudents as $index => $student): ?>
    function initializeCharts<?= $index ?>() {
        // Risk Breakdown Chart
        var riskCtx = document.getElementById('riskChart<?= $index ?>');
        if (riskCtx && !riskCtx.chartInitialized) {
            var attendanceRisk = <?= (floatval($student['Attendance']) < 75) ? 'Math.max(20, 100 - ' . floatval($student['Attendance']) . ')' : '10' ?>;
            var academicRisk = <?= (floatval($student['GPA']) < 2.5) ? 'Math.max(15, (3.0 - ' . floatval($student['GPA']) . ') * 25)' : '8' ?>;
            var financialRisk = <?= (floatval($student['balance']) > 5000) ? 'Math.min(30, ' . floatval($student['balance']) . ' / 1000)' : '5' ?>;
            var otherRisk = Math.max(0, <?= floatval($student['dropout_percentage']) ?> - attendanceRisk - academicRisk - financialRisk);

            new Chart(riskCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Attendance', 'Academic', 'Financial', 'Other'],
                    datasets: [{
                        data: [attendanceRisk, academicRisk, financialRisk, otherRisk],
                        backgroundColor: [
                            'rgba(231, 76, 60, 0.8)',
                            'rgba(243, 156, 18, 0.8)',
                            'rgba(155, 89, 182, 0.8)',
                            'rgba(52, 152, 219, 0.8)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 11 } }
                        }
                    },
                    cutout: '60%'
                }
            });
            riskCtx.chartInitialized = true;
        }

        // Performance Trend Chart
        var trendCtx = document.getElementById('trendChart<?= $index ?>');
        if (trendCtx && !trendCtx.chartInitialized) {
            var currentGPA = <?= floatval($student['GPA']) ?>;
            var currentAttendance = <?= floatval($student['Attendance']) ?>;
            
            // Generate mock historical data
            var labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
            var gpaData = [];
            var attendanceData = [];
            
            for (var i = 0; i < 7; i++) {
                gpaData.push(Math.max(0, Math.min(4, currentGPA + (Math.random() - 0.5) * 0.5)));
                attendanceData.push(Math.max(0, Math.min(100, currentAttendance + (Math.random() - 0.5) * 10)));
            }

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'GPA',
                        data: gpaData,
                        borderColor: 'rgba(39, 174, 96, 1)',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        yAxisID: 'y'
                    }, {
                        label: 'Attendance %',
                        data: attendanceData,
                        borderColor: 'rgba(52, 152, 219, 1)',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { font: { size: 11 } }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            min: 0,
                            max: 4,
                            title: { display: true, text: 'GPA', font: { size: 11 } }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            min: 0,
                            max: 100,
                            title: { display: true, text: 'Attendance %', font: { size: 11 } },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
            trendCtx.chartInitialized = true;
        }
    }
    <?php endforeach; ?>
});
</script>