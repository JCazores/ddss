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

// Verify table exists
$table_check_sql = "SHOW TABLES LIKE '$table_name'";
$table_check_result = $conn->query($table_check_sql);

if (!$table_check_result || $table_check_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Table does not exist.</div>';
    exit();
}

// Get basic statistics
$total_sql = "SELECT COUNT(*) as total FROM `$table_name`";
$total_result = $conn->query($total_sql);
$total_records = $total_result ? $total_result->fetch_assoc()['total'] : 0;

// Get table structure
$columns_sql = "SHOW COLUMNS FROM `$table_name`";
$columns_result = $conn->query($columns_sql);
$columns = [];
$numeric_columns = [];
$text_columns = [];

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column;
        $field_name = $column['Field'];
        $field_type = strtolower($column['Type']);
        
        if (strpos($field_type, 'int') !== false || 
            strpos($field_type, 'decimal') !== false || 
            strpos($field_type, 'float') !== false ||
            strpos($field_type, 'double') !== false) {
            $numeric_columns[] = $field_name;
        } else {
            $text_columns[] = $field_name;
        }
    }
}

// Get upload log info
$log_info_sql = "SELECT * FROM upload_logs WHERE log_id = $log_id";
$log_info_result = $conn->query($log_info_sql);
$log_info = $log_info_result ? $log_info_result->fetch_assoc() : null;

?>

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h5><i class="fa fa-info-circle"></i> Basic Statistics</h5>
            </div>
            <div class="panel-body">
                <table class="table table-striped">
                    <tr>
                        <td><strong>Total Records:</strong></td>
                        <td><?= number_format($total_records) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Columns:</strong></td>
                        <td><?= count($columns) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Numeric Columns:</strong></td>
                        <td><?= count($numeric_columns) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Text Columns:</strong></td>
                        <td><?= count($text_columns) ?></td>
                    </tr>
                    <?php if ($log_info): ?>
                    <tr>
                        <td><strong>Upload Date:</strong></td>
                        <td><?= date('M j, Y H:i', strtotime($log_info['upload_date'])) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Records Processed:</strong></td>
                        <td><?= number_format($log_info['records_processed']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Success Rate:</strong></td>
                        <td>
                            <?php 
                            $success_rate = $log_info['records_processed'] > 0 ? 
                                ($log_info['records_success'] / $log_info['records_processed']) * 100 : 0;
                            echo number_format($success_rate, 1) . '%';
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h5><i class="fa fa-columns"></i> Column Information</h5>
            </div>
            <div class="panel-body" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-condensed table-striped">
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($columns as $column): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($column['Field']) ?></code></td>
                            <td><small><?= htmlspecialchars($column['Type']) ?></small></td>
                            <td>
                                <span class="label label-<?= $column['Null'] === 'YES' ? 'warning' : 'success' ?>">
                                    <?= $column['Null'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($column['Key']): ?>
                                    <span class="label label-primary"><?= htmlspecialchars($column['Key']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($numeric_columns)): ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h5><i class="fa fa-calculator"></i> Numeric Column Statistics</h5>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Count</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Average</th>
                                <th>Sum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($numeric_columns as $column): ?>
                                <?php
                                // Skip id and timestamp columns for statistics
                                if (in_array($column, ['id', 'created_at', 'updated_at'])) continue;
                                
                                $stats_sql = "SELECT 
                                    COUNT(`$column`) as count_val,
                                    MIN(`$column`) as min_val, 
                                    MAX(`$column`) as max_val, 
                                    AVG(`$column`) as avg_val, 
                                    SUM(`$column`) as sum_val 
                                    FROM `$table_name` 
                                    WHERE `$column` IS NOT NULL";
                                $stats_result = $conn->query($stats_sql);
                                $stats = $stats_result ? $stats_result->fetch_assoc() : null;
                                ?>
                                <?php if ($stats): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($column) ?></code></td>
                                    <td><?= number_format($stats['count_val']) ?></td>
                                    <td><?= $stats['min_val'] !== null ? number_format($stats['min_val'], 2) : 'N/A' ?></td>
                                    <td><?= $stats['max_val'] !== null ? number_format($stats['max_val'], 2) : 'N/A' ?></td>
                                    <td><?= $stats['avg_val'] !== null ? number_format($stats['avg_val'], 2) : 'N/A' ?></td>
                                    <td><?= $stats['sum_val'] !== null ? number_format($stats['sum_val'], 2) : 'N/A' ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($text_columns)): ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h5><i class="fa fa-font"></i> Text Column Analysis</h5>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Non-Empty Count</th>
                                <th>Empty/NULL Count</th>
                                <th>Unique Values</th>
                                <th>Avg Length</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($text_columns as $column): ?>
                                <?php
                                // Skip technical columns
                                if (in_array($column, ['id', 'created_at', 'updated_at'])) continue;
                                
                                $text_stats_sql = "SELECT 
                                    COUNT(CASE WHEN `$column` IS NOT NULL AND `$column` != '' THEN 1 END) as non_empty_count,
                                    COUNT(CASE WHEN `$column` IS NULL OR `$column` = '' THEN 1 END) as empty_count,
                                    COUNT(DISTINCT `$column`) as unique_count,
                                    AVG(CHAR_LENGTH(`$column`)) as avg_length
                                    FROM `$table_name`";
                                $text_stats_result = $conn->query($text_stats_sql);
                                $text_stats = $text_stats_result ? $text_stats_result->fetch_assoc() : null;
                                ?>
                                <?php if ($text_stats): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($column) ?></code></td>
                                    <td><?= number_format($text_stats['non_empty_count']) ?></td>
                                    <td><?= number_format($text_stats['empty_count']) ?></td>
                                    <td><?= number_format($text_stats['unique_count']) ?></td>
                                    <td><?= $text_stats['avg_length'] !== null ? number_format($text_stats['avg_length'], 1) : 'N/A' ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>