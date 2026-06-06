<?php
include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit();
}

// Validate input
if (!isset($_POST['log_id']) || !isset($_POST['table_name'])) {
    echo '<div class="alert alert-danger">Invalid request. Missing parameters.</div>';
    exit();
}

$log_id = intval($_POST['log_id']);
$table_name = mysqli_real_escape_string($conn, $_POST['table_name']);

// Verify the upload log exists and get file information
$log_info_sql = "SELECT * FROM upload_logs WHERE log_id = $log_id";
$log_info_result = $conn->query($log_info_sql);

if (!$log_info_result || $log_info_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Upload log not found.</div>';
    exit();
}

$log_info = $log_info_result->fetch_assoc();

// Verify table exists
$table_check_sql = "SHOW TABLES LIKE '$table_name'";
$table_check_result = $conn->query($table_check_sql);

if (!$table_check_result || $table_check_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Table does not exist.</div>';
    exit();
}

// Get table structure to display columns properly
$columns_sql = "SHOW COLUMNS FROM `$table_name`";
$columns_result = $conn->query($columns_sql);
$columns = [];

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
}

// Get records from the table (show recent records)
$records_sql = "SELECT * FROM `$table_name` ORDER BY id DESC LIMIT 100";
$records_result = $conn->query($records_sql);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM `$table_name`";
$count_result = $conn->query($count_sql);
$total_records = $count_result ? $count_result->fetch_assoc()['total'] : 0;

?>

<div class="panel panel-info">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-database"></i> 
            Records from: <?= htmlspecialchars($log_info['filename']) ?>
            <small class="pull-right">
                <span class="badge"><?= $total_records ?> total records</span>
                <?php if ($total_records > 100): ?>
                    <span class="text-warning">(Showing first 100)</span>
                <?php endif; ?>
            </small>
        </h4>
    </div>
    <div class="panel-body">
        
        <!-- File Information Summary -->
        <div class="alert alert-info" style="margin-bottom: 20px;">
            <div class="row">
                <div class="col-md-4">
                    <strong>Table:</strong> <code><?= htmlspecialchars($table_name) ?></code><br>
                    <strong>Status:</strong> 
                    <span class="label label-<?= $log_info['status'] == 'success' ? 'success' : 'danger' ?>">
                        <?= htmlspecialchars(strtoupper($log_info['status'])) ?>
                    </span>
                </div>
                <div class="col-md-4">
                    <strong>Upload Date:</strong> <?= date('M j, Y H:i', strtotime($log_info['upload_date'])) ?><br>
                    <strong>Year/Semester:</strong> <?= htmlspecialchars($log_info['year']) ?> - Sem <?= htmlspecialchars($log_info['semester']) ?>
                </div>
                <div class="col-md-4">
                    <strong>Success/Errors:</strong> 
                    <span class="text-success"><?= $log_info['records_success'] ?></span> / 
                    <span class="text-danger"><?= $log_info['records_error'] ?></span><br>
                    <strong>Total Processed:</strong> <?= $log_info['records_processed'] ?>
                </div>
            </div>
        </div>

        <?php if ($records_result && $records_result->num_rows > 0): ?>
            <!-- Column Headers Info -->
            <div class="well well-sm">
                <strong>Available Columns:</strong> 
                <?php foreach ($columns as $index => $column): ?>
                    <code><?= htmlspecialchars($column) ?></code><?= $index < count($columns) - 1 ? ', ' : '' ?>
                <?php endforeach; ?>
            </div>

            <!-- Records Display -->
            <div style="max-height: 600px; overflow-y: auto;">
                <?php $record_count = 0; ?>
                <?php while ($record = $records_result->fetch_assoc()): ?>
                    <?php $record_count++; ?>
                    <div class="record-item" id="record-row-<?= $record['id'] ?>" style="border-left: 4px solid rgba(1, 129, 55, 0.6);">
                        <div class="row">
                            <div class="col-md-1">
                                <strong>Row #<?= $record_count ?></strong>
                                <br><small class="text-muted">ID: <?= $record['id'] ?></small>
                            </div>
                            <div class="col-md-11">
                                <div class="row">
                                    <?php 
                                    $displayed_columns = 0;
                                    foreach ($record as $field => $value): 
                                        // Skip technical fields
                                        if (in_array($field, ['id', 'created_at', 'updated_at'])) continue;
                                        $displayed_columns++;
                                        
                                        // Limit columns per row for better display
                                        if ($displayed_columns > 0 && ($displayed_columns - 1) % 3 === 0 && $displayed_columns > 1): 
                                    ?>
                                </div>
                                <div class="row" style="margin-top: 10px;">
                                    <?php endif; ?>
                                    
                                    <div class="col-md-4">
                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label class="text-muted" style="font-size: 0.85em; margin-bottom: 2px;">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $field))) ?>:
                                            </label>
                                            <div data-field="<?= htmlspecialchars($field) ?>" style="background: #f8f9fa; padding: 5px 8px; border-radius: 3px; min-height: 24px; font-size: 0.9em;">
                                                <?= $value !== null ? htmlspecialchars($value) : '<span class="text-muted">[NULL]</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Record Actions -->
                        
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_records > 100): ?>
                <div class="alert alert-warning" style="margin-top: 15px;">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Note:</strong> Only the first 100 records are displayed for performance reasons. 
                    Total records in this upload: <strong><?= $total_records ?></strong>
                </div>
            <?php endif; ?>

            <!-- Bulk Actions -->
            <!--<div class="well" style="margin-top: 20px;">
                <h5><i class="fa fa-cogs"></i> Bulk Actions</h5>
                <div class="btn-group">
                    <button onclick="exportRecords(<?= $log_id ?>, '<?= $table_name ?>')" 
                            class="btn btn-sm btn-default">
                        <i class="fa fa-download"></i> Export Records (CSV)
                    </button>
                    <button onclick="showRecordStats(<?= $log_id ?>, '<?= $table_name ?>')" 
                            class="btn btn-sm btn-info">
                        <i class="fa fa-bar-chart"></i> Show Statistics
                    </button>
                    <button onclick="validateRecords(<?= $log_id ?>, '<?= $table_name ?>')" 
                            class="btn btn-sm btn-primary">
                        <i class="fa fa-check-circle"></i> Validate Data
                    </button>
                </div>
            </div>-->

        <?php else: ?>
                <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> 
                <strong>No records found</strong> in this table. 
                This could mean:
                <ul style="margin-bottom: 0; margin-top: 10px;">
                    <li>The table is empty</li>
                    <li>The data was already deleted</li>
                    <li>The upload failed completely</li>
                    <li>The records were moved to a different table</li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Technical Information -->
        <!--<div class="panel panel-default" style="margin-top: 20px;">
            <div class="panel-heading">
                <h6 class="panel-title">
                    <a data-toggle="collapse" href="#technical-info-<?= $log_id ?>">
                        <i class="fa fa-cog"></i> Technical Information
                        <i class="fa fa-chevron-down pull-right"></i>
                    </a>
                </h6>
            </div>
            <div id="technical-info-<?= $log_id ?>" class="panel-collapse collapse">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Upload Batch ID:</strong> <?= $log_id ?><br>
                            <strong>File Size:</strong> <?= isset($log_info['file_size']) && $log_info['file_size'] ? number_format($log_info['file_size']) . ' bytes' : 'Unknown' ?><br>
                            <strong>File Type:</strong> <?= htmlspecialchars($log_info['file_type'] ?? 'Unknown') ?><br>
                            <strong>Processing Time:</strong> <?= isset($log_info['processing_time']) && $log_info['processing_time'] ? $log_info['processing_time'] . ' seconds' : 'Unknown' ?>
                        </div>
                        <div class="col-md-6">
                            <strong>User IP:</strong> <?= htmlspecialchars($log_info['user_ip_address'] ?? 'Unknown') ?><br>
                            <strong>User Agent:</strong> <small><?= htmlspecialchars(substr($log_info['user_agent'] ?? 'Unknown', 0, 50)) ?></small><br>
                            <strong>File Path:</strong> <small><code><?= htmlspecialchars($log_info['file_path'] ?? 'Not specified') ?></code></small>
                        </div>
                    </div>
                    <?php if (isset($log_info['error_message']) && $log_info['error_message']): ?>
                        <hr>
                        <strong>Error Message:</strong>
                        <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.9em;"><?= htmlspecialchars($log_info['error_message']) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>-->
    </div>
</div>

<script>
function viewRecordDetails(recordId, tableName) {
    // Create a modal to show record details
    $.ajax({
        url: 'get_record_details.php',
        type: 'POST',
        data: {
            record_id: recordId,
            table_name: tableName
        },
        success: function(response) {
            // Create modal HTML
            var modalHtml = '<div class="modal fade" id="recordDetailsModal" tabindex="-1">' +
                '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h4 class="modal-title"><i class="fa fa-info-circle"></i> Record Details (ID: ' + recordId + ')</h4>' +
                '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                '</div>' +
                '<div class="modal-body">' + response + '</div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                '</div>' +
                '</div></div></div>';
            
            // Remove existing modal if any
            $('#recordDetailsModal').remove();
            
            // Add modal to body and show
            $('body').append(modalHtml);
            $('#recordDetailsModal').modal('show');
        },
        error: function() {
            alert('Error loading record details. Please try again.');
        }
    });
}

function editRecord(recordId, tableName) {
    // Load edit form in modal
    $.ajax({
        url: 'edit_record.php',
        type: 'POST',
        data: {
            record_id: recordId,
            table_name: tableName
        },
        success: function(response) {
            // Create modal HTML
            var modalHtml = '<div class="modal fade" id="editRecordModal" tabindex="-1">' +
                '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h4 class="modal-title"><i class="fa fa-edit"></i> Edit Record (ID: ' + recordId + ')</h4>' +
                '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                '</div>' +
                '<div class="modal-body">' + response + '</div>' +
                '</div></div></div>';
            
            // Remove existing modal if any
            $('#editRecordModal').remove();
            
            // Add modal to body and show
            $('body').append(modalHtml);
            $('#editRecordModal').modal('show');
        },
        error: function() {
            alert('Error loading edit form. Please try again.');
        }
    });
}

function deleteRecord(recordId, tableName) {
    if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
        $.ajax({
            url: 'delete_record.php',
            type: 'POST',
            data: {
                record_id: recordId,
                table_name: tableName
            },
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    // Remove the record row from display
                    $('#record-row-' + recordId).fadeOut(500, function() {
                        $(this).remove();
                    });
                    
                    // Show success message
                    showAlert('success', 'Record deleted successfully!');
                } else {
                    showAlert('danger', 'Error deleting record: ' + result.message);
                }
            },
            error: function() {
                showAlert('danger', 'Error deleting record. Please try again.');
            }
        });
    }
}

function exportRecords(logId, tableName) {
    // Show loading state
    var exportBtn = $('button[onclick*="exportRecords"]');
    var originalHtml = exportBtn.html();
    exportBtn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
    
    // Create form and submit to trigger download
    var form = $('<form method="post" action="export_records.php" target="_blank">');
    form.append('<input type="hidden" name="log_id" value="' + logId + '">');
    form.append('<input type="hidden" name="table_name" value="' + tableName + '">');
    form.append('<input type="hidden" name="format" value="csv">');
    
    $('body').append(form);
    form.submit();
    form.remove();
    
    // Reset button after delay
    setTimeout(function() {
        exportBtn.html(originalHtml).prop('disabled', false);
    }, 2000);
}

function showRecordStats(logId, tableName) {
    // Load statistics via AJAX
    $.ajax({
        url: 'get_record_stats.php',
        type: 'POST',
        data: {
            log_id: logId,
            table_name: tableName
        },
        beforeSend: function() {
            var statsBtn = $('button[onclick*="showRecordStats"]');
            statsBtn.html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        },
        success: function(response) {
            // Create modal HTML for statistics
            var modalHtml = '<div class="modal fade" id="recordStatsModal" tabindex="-1">' +
                '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h4 class="modal-title"><i class="fa fa-bar-chart"></i> Record Statistics</h4>' +
                '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                '</div>' +
                '<div class="modal-body">' + response + '</div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                '</div>' +
                '</div></div></div>';
            
            // Remove existing modal if any
            $('#recordStatsModal').remove();
            
            // Add modal to body and show
            $('body').append(modalHtml);
            $('#recordStatsModal').modal('show');
        },
        error: function() {
            showAlert('danger', 'Error loading statistics. Please try again.');
        },
        complete: function() {
            var statsBtn = $('button[onclick*="showRecordStats"]');
            statsBtn.html('<i class="fa fa-bar-chart"></i> Show Statistics').prop('disabled', false);
        }
    });
}

function validateRecords(logId, tableName) {
    if (confirm('Run data validation checks on all records from this upload?')) {
        $.ajax({
            url: 'validate_records.php',
            type: 'POST',
            data: {
                log_id: logId,
                table_name: tableName
            },
            beforeSend: function() {
                var validateBtn = $('button[onclick*="validateRecords"]');
                validateBtn.html('<i class="fa fa-spinner fa-spin"></i> Validating...').prop('disabled', true);
            },
            success: function(response) {
                var result = JSON.parse(response);
                
                // Create modal to show validation results
                var modalHtml = '<div class="modal fade" id="validationResultsModal" tabindex="-1">' +
                    '<div class="modal-dialog modal-lg">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header">' +
                    '<h4 class="modal-title"><i class="fa fa-check-circle"></i> Validation Results</h4>' +
                    '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                    '</div>' +
                    '<div class="modal-body">';
                
                if (result.success) {
                    modalHtml += '<div class="alert alert-success">' +
                        '<h5><i class="fa fa-check"></i> Validation Complete</h5>' +
                        '<p><strong>Valid Records:</strong> ' + result.valid_count + '</p>' +
                        '<p><strong>Invalid Records:</strong> ' + result.invalid_count + '</p>' +
                        '</div>';
                    
                    if (result.errors && result.errors.length > 0) {
                        modalHtml += '<div class="alert alert-warning">' +
                            '<h6>Validation Errors Found:</h6><ul>';
                        result.errors.forEach(function(error) {
                            modalHtml += '<li>' + error + '</li>';
                        });
                        modalHtml += '</ul></div>';
                    }
                } else {
                    modalHtml += '<div class="alert alert-danger">' +
                        '<h5><i class="fa fa-exclamation-triangle"></i> Validation Failed</h5>' +
                        '<p>' + result.message + '</p></div>';
                }
                
                modalHtml += '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                    '</div>' +
                    '</div></div></div>';
                
                // Remove existing modal if any
                $('#validationResultsModal').remove();
                
                // Add modal to body and show
                $('body').append(modalHtml);
                $('#validationResultsModal').modal('show');
            },
            error: function() {
                showAlert('danger', 'Error running validation. Please try again.');
            },
            complete: function() {
                var validateBtn = $('button[onclick*="validateRecords"]');
                validateBtn.html('<i class="fa fa-check-circle"></i> Validate Data').prop('disabled', false);
            }
        });
    }
}

// Utility function to show alerts
function showAlert(type, message) {
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        message +
        '</div>';
    
    $('body').append(alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Auto-scroll to top of records when loaded (only if element exists)
var recordsElement = document.getElementById('records-content-<?= $log_id ?>');
if (recordsElement) {
    recordsElement.scrollTop = 0;
}
</script>