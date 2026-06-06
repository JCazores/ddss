<?php
session_start();
include("php/dbconnect.php");
include("php/checklogin.php");

$success = true;
$messages = [];

try {
    // Check if upload_logs table exists, if not create it
    $table_check = $conn->query("SHOW TABLES LIKE 'upload_logs'");
    if (!$table_check || $table_check->num_rows == 0) {
        // Create the table with all columns
        $createTableSQL = "CREATE TABLE upload_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            year INT,
            semester VARCHAR(10),
            table_name VARCHAR(100),
            filename VARCHAR(255),
            records_processed INT DEFAULT 0,
            records_success INT DEFAULT 0,
            records_error INT DEFAULT 0,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20),
            error_message TEXT NULL,
            uploaded_by_user_id INT NULL,
            uploaded_by_username VARCHAR(100) NULL,
            uploaded_by_name VARCHAR(255) NULL,
            user_ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            content_warnings TEXT NULL,
            INDEX idx_uploaded_by (uploaded_by_user_id),
            INDEX idx_upload_date (upload_date)
        )";
        
        if ($conn->query($createTableSQL)) {
            $messages[] = "✓ Created upload_logs table successfully";
        } else {
            throw new Exception("Error creating upload_logs table: " . $conn->error);
        }
    } else {
        // Table exists, check and add missing columns
        $columns_to_add = [
            'uploaded_by_user_id' => 'INT NULL',
            'uploaded_by_username' => 'VARCHAR(100) NULL',
            'uploaded_by_name' => 'VARCHAR(255) NULL',
            'user_ip_address' => 'VARCHAR(45) NULL',
            'user_agent' => 'TEXT NULL',
            'content_warnings' => 'TEXT NULL'
        ];
        
        foreach ($columns_to_add as $column_name => $column_def) {
            // Check if column exists
            $column_check = $conn->query("SHOW COLUMNS FROM upload_logs LIKE '$column_name'");
            if (!$column_check || $column_check->num_rows == 0) {
                // Column doesn't exist, add it
                $add_column_sql = "ALTER TABLE upload_logs ADD COLUMN $column_name $column_def";
                if ($conn->query($add_column_sql)) {
                    $messages[] = "✓ Added column '$column_name' to upload_logs table";
                } else {
                    throw new Exception("Error adding column '$column_name': " . $conn->error);
                }
            } else {
                $messages[] = "✓ Column '$column_name' already exists";
            }
        }
        
        // Check and add indexes if they don't exist
        $indexes_to_add = [
            'idx_uploaded_by' => 'uploaded_by_user_id',
            'idx_upload_date' => 'upload_date'
        ];
        
        foreach ($indexes_to_add as $index_name => $index_column) {
            // Check if index exists
            $index_check = $conn->query("SHOW INDEX FROM upload_logs WHERE Key_name = '$index_name'");
            if (!$index_check || $index_check->num_rows == 0) {
                // Index doesn't exist, add it
                $add_index_sql = "ALTER TABLE upload_logs ADD INDEX $index_name ($index_column)";
                if ($conn->query($add_index_sql)) {
                    $messages[] = "✓ Added index '$index_name' to upload_logs table";
                } else {
                    // Index creation failure is not critical, just log it
                    $messages[] = "⚠ Could not add index '$index_name' (may already exist)";
                }
            } else {
                $messages[] = "✓ Index '$index_name' already exists";
            }
        }
    }
    
    // Display success message
    echo '<div class="alert alert-success">';
    echo '<h5><i class="fa fa-check-circle"></i> Database Schema Updated Successfully!</h5>';
    foreach ($messages as $message) {
        echo '<p style="margin-bottom: 5px;">' . htmlspecialchars($message) . '</p>';
    }
    echo '<p style="margin-top: 15px;"><strong>The page will refresh in 2 seconds...</strong></p>';
    echo '</div>';
    
} catch (Exception $e) {
    $success = false;
    echo '<div class="alert alert-danger">';
    echo '<h5><i class="fa fa-exclamation-triangle"></i> Schema Update Failed</h5>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    if (!empty($messages)) {
        echo '<p><strong>Completed steps:</strong></p>';
        foreach ($messages as $message) {
            echo '<p style="margin-bottom: 5px;">' . htmlspecialchars($message) . '</p>';
        }
    }
    echo '</div>';
}

$conn->close();
?>