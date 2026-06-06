<?php
include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit();
}

// Validate input
if (!isset($_POST['record_id']) || !isset($_POST['table_name'])) {
    echo '<div class="alert alert-danger">Invalid request. Missing parameters.</div>';
    exit();
}

$record_id = intval($_POST['record_id']);
$table_name = mysqli_real_escape_string($conn, $_POST['table_name']);

// Verify table exists
$table_check_sql = "SHOW TABLES LIKE '$table_name'";
$table_check_result = $conn->query($table_check_sql);

if (!$table_check_result || $table_check_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Table does not exist.</div>';
    exit();
}

// Get record details
$record_sql = "SELECT * FROM `$table_name` WHERE id = $record_id";
$record_result = $conn->query($record_sql);

if (!$record_result || $record_result->num_rows === 0) {
    echo '<div class="alert alert-warning">Record not found.</div>';
    exit();
}

$record = $record_result->fetch_assoc();

// Get table structure for better display
$columns_sql = "SHOW COLUMNS FROM `$table_name`";
$columns_result = $conn->query($columns_sql);
$columns_info = [];

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $columns_info[$column['Field']] = $column;
    }
}

?>

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <strong><i class="fa fa-info-circle"></i> Record Details</strong><br>
            <small>Table: <code><?= htmlspecialchars($table_name) ?></code> | Record ID: <strong><?= $record_id ?></strong></small>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-body">
                <?php foreach ($record as $field_name => $field_value): ?>
                    <div class="form-group row" style="margin-bottom: 15px;">
                        <div class="col-md-3">
                            <label class="control-label" style="font-weight: bold; color: #555;">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $field_name))) ?>:
                            </label>
                            <?php if (isset($columns_info[$field_name])): ?>
                                <br><small class="text-muted">
                                    Type: <?= htmlspecialchars($columns_info[$field_name]['Type']) ?>
                                    <?php if ($columns_info[$field_name]['Null'] === 'NO'): ?>
                                        <span class="label label-warning">Required</span>
                                    <?php endif; ?>
                                    <?php if ($columns_info[$field_name]['Key'] === 'PRI'): ?>
                                        <span class="label label-primary">Primary Key</span>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <div class="well well-sm" style="background: #f8f9fa; border: 1px solid #e9ecef; margin-bottom: 0;">
                                <?php if ($field_value !== null && $field_value !== ''): ?>
                                    <?php
                                    // Format display based on field type or name
                                    $display_value = htmlspecialchars($field_value);
                                    
                                    // Special formatting for common field types
                                    if (strpos(strtolower($field_name), 'date') !== false || 
                                        strpos(strtolower($field_name), 'time') !== false) {
                                        // Try to format as date/time
                                        $timestamp = strtotime($field_value);
                                        if ($timestamp !== false) {
                                            $display_value = '<span class="text-primary">' . 
                                                date('M j, Y H:i:s', $timestamp) . '</span> ' .
                                                '<small class="text-muted">(' . $display_value . ')</small>';
                                        }
                                    } elseif (strpos(strtolower($field_name), 'email') !== false) {
                                        // Format email with mailto link
                                        $display_value = '<a href="mailto:' . $display_value . '">' . $display_value . '</a>';
                                    } elseif (strpos(strtolower($field_name), 'phone') !== false || 
                                             strpos(strtolower($field_name), 'mobile') !== false) {
                                        // Format phone number
                                        $display_value = '<span class="text-info">' . $display_value . '</span>';
                                    } elseif (strpos(strtolower($field_name), 'amount') !== false || 
                                             strpos(strtolower($field_name), 'fee') !== false ||
                                             strpos(strtolower($field_name), 'balance') !== false ||
                                             strpos(strtolower($field_name), 'payment') !== false) {
                                        // Format currency
                                        if (is_numeric($field_value)) {
                                            $display_value = '<span class="text-success">₱ ' . number_format($field_value, 2) . '</span>';
                                        }
                                    } elseif (is_numeric($field_value) && strlen($field_value) > 6) {
                                        // Large numbers - add formatting
                                        $display_value = '<span class="text-primary">' . number_format($field_value) . '</span>';
                                    }
                                    
                                    echo $display_value;
                                    ?>
                                    
                                    <!-- Show character count for long text fields -->
                                    <?php if (strlen($field_value) > 50): ?>
                                        <br><small class="text-muted">
                                            <i class="fa fa-info-circle"></i> <?= strlen($field_value) ?> characters
                                        </small>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <span class="text-muted"><em>[NULL or Empty]</em></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <hr style="margin: 10px 0;">
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="text-center" style="margin-top: 20px;">
            <button onclick="editRecordFromDetails(<?= $record_id ?>, '<?= $table_name ?>')" 
                    class="btn btn-warning">
                <i class="fa fa-edit"></i> Edit This Record
            </button>
            <button onclick="deleteRecordFromDetails(<?= $record_id ?>, '<?= $table_name ?>')" 
                    class="btn btn-danger">
                <i class="fa fa-trash"></i> Delete This Record
            </button>
            <button onclick="duplicateRecord(<?= $record_id ?>, '<?= $table_name ?>')" 
                    class="btn btn-info">
                <i class="fa fa-copy"></i> Duplicate Record
            </button>
        </div>
    </div>
</div>

<script>
function editRecordFromDetails(recordId, tableName) {
    // Close current modal and open edit modal
    $('#recordDetailsModal').modal('hide');
    setTimeout(function() {
        editRecord(recordId, tableName);
    }, 500);
}

function deleteRecordFromDetails(recordId, tableName) {
    if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
        $('#recordDetailsModal').modal('hide');
        setTimeout(function() {
            deleteRecord(recordId, tableName);
        }, 500);
    }
}

function duplicateRecord(recordId, tableName) {
    if (confirm('Create a duplicate of this record?')) {
        $.ajax({
            url: 'duplicate_record.php',
            type: 'POST',
            data: {
                record_id: recordId,
                table_name: tableName
            },
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    $('#recordDetailsModal').modal('hide');
                    showAlert('success', 'Record duplicated successfully! New record ID: ' + result.new_id);
                    // Refresh the page after a delay
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert('danger', 'Error duplicating record: ' + result.message);
                }
            },
            error: function() {
                showAlert('danger', 'Error duplicating record. Please try again.');
            }
        });
    }
}
</script>