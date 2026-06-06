<?php
include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

// Validate input
if (!isset($_POST['log_id']) || !isset($_POST['table_name'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Missing parameters.']);
    exit();
}

$log_id = intval($_POST['log_id']);
$table_name = mysqli_real_escape_string($conn, $_POST['table_name']);

// Verify table exists
$table_check_sql = "SHOW TABLES LIKE '$table_name'";
$table_check_result = $conn->query($table_check_sql);

if (!$table_check_result || $table_check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Table does not exist.']);
    exit();
}

// Get table structure to understand validation rules
$columns_sql = "SHOW COLUMNS FROM `$table_name`";
$columns_result = $conn->query($columns_sql);
$columns = [];

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column;
    }
}

// Initialize validation results
$validation_errors = [];
$valid_count = 0;
$invalid_count = 0;

// Get all records to validate
$records_sql = "SELECT * FROM `$table_name`";
$records_result = $conn->query($records_sql);

if (!$records_result) {
    echo json_encode(['success' => false, 'message' => 'Error fetching records: ' . $conn->error]);
    exit();
}

// Validate each record
while ($record = $records_result->fetch_assoc()) {
    $record_id = $record['id'];
    $record_errors = [];
    
    // Check each column against its constraints
    foreach ($columns as $column_info) {
        $field_name = $column_info['Field'];
        $field_type = $column_info['Type'];
        $is_nullable = ($column_info['Null'] === 'YES');
        $field_value = $record[$field_name];
        
        // Skip validation for auto-increment and timestamp fields
        if (in_array($field_name, ['id', 'created_at', 'updated_at'])) {
            continue;
        }
        
        // Check for NULL values in non-nullable fields
        if (!$is_nullable && ($field_value === null || $field_value === '')) {
            $record_errors[] = "Field '$field_name' cannot be NULL or empty";
        }
        
        // Type-specific validations
        if ($field_value !== null && $field_value !== '') {
            // Integer validation
            if (strpos(strtolower($field_type), 'int') !== false) {
                if (!is_numeric($field_value) || !filter_var($field_value, FILTER_VALIDATE_INT)) {
                    $record_errors[] = "Field '$field_name' must be a valid integer (current: '$field_value')";
                }
            }
            
            // Decimal/Float validation
            elseif (strpos(strtolower($field_type), 'decimal') !== false || 
                    strpos(strtolower($field_type), 'float') !== false ||
                    strpos(strtolower($field_type), 'double') !== false) {
                if (!is_numeric($field_value)) {
                    $record_errors[] = "Field '$field_name' must be a valid number (current: '$field_value')";
                }
            }
            
            // Email validation (if field name suggests email)
            elseif (strpos(strtolower($field_name), 'email') !== false) {
                if (!filter_var($field_value, FILTER_VALIDATE_EMAIL)) {
                    $record_errors[] = "Field '$field_name' must be a valid email address (current: '$field_value')";
                }
            }
            
            // Date validation (if field name suggests date)
            elseif (strpos(strtolower($field_name), 'date') !== false && 
                    strpos(strtolower($field_type), 'date') !== false) {
                $date = DateTime::createFromFormat('Y-m-d', $field_value);
                if (!$date || $date->format('Y-m-d') !== $field_value) {
                    $record_errors[] = "Field '$field_name' must be a valid date in Y-m-d format (current: '$field_value')";
                }
            }
            
            // String length validation
            elseif (strpos(strtolower($field_type), 'varchar') !== false) {
                // Extract max length from VARCHAR(n)
                preg_match('/varchar\((\d+)\)/i', $field_type, $matches);
                if (isset($matches[1])) {
                    $max_length = intval($matches[1]);
                    if (strlen($field_value) > $max_length) {
                        $record_errors[] = "Field '$field_name' exceeds maximum length of $max_length characters (current: " . strlen($field_value) . ")";
                    }
                }
            }
            
            // Phone number validation (if field name suggests phone)
            elseif (strpos(strtolower($field_name), 'phone') !== false || 
                    strpos(strtolower($field_name), 'mobile') !== false ||
                    strpos(strtolower($field_name), 'contact') !== false) {
                // Basic phone validation - remove spaces, hyphens, parentheses
                $clean_phone = preg_replace('/[\s\-\(\)]/', '', $field_value);
                if (!preg_match('/^[\+]?[0-9]{7,15}$/', $clean_phone)) {
                    $record_errors[] = "Field '$field_name' appears to be invalid phone format (current: '$field_value')";
                }
            }
        }
        
        // Custom business logic validations based on common school system fields
        if ($field_value !== null && $field_value !== '') {
            // Student ID validation
            if (strtolower($field_name) === 'student_id' || strtolower($field_name) === 'studentid') {
                if (strlen($field_value) < 3) {
                    $record_errors[] = "Student ID should be at least 3 characters long";
                }
            }
            
            // Grade/Year validation
            if (strpos(strtolower($field_name), 'grade') !== false || 
                strpos(strtolower($field_name), 'year') !== false ||
                strpos(strtolower($field_name), 'level') !== false) {
                if (is_numeric($field_value)) {
                    $grade_val = intval($field_value);
                    if ($grade_val < 1 || $grade_val > 12) {
                        $record_errors[] = "Grade/Year level should be between 1 and 12 (current: $grade_val)";
                    }
                }
            }
            
            // Amount/Fee validation
            if (strpos(strtolower($field_name), 'amount') !== false || 
                strpos(strtolower($field_name), 'fee') !== false ||
                strpos(strtolower($field_name), 'payment') !== false ||
                strpos(strtolower($field_name), 'balance') !== false) {
                if (is_numeric($field_value) && floatval($field_value) < 0) {
                    $record_errors[] = "Amount/Fee should not be negative (current: $field_value)";
                }
            }
            
            // Semester validation
            if (strpos(strtolower($field_name), 'semester') !== false || 
                strtolower($field_name) === 'sem') {
                if (is_numeric($field_value)) {
                    $sem_val = intval($field_value);
                    if ($sem_val < 1 || $sem_val > 3) {
                        $record_errors[] = "Semester should be 1, 2, or 3 (current: $sem_val)";
                    }
                }
            }
        }
    }
    
    // Count validation results
    if (empty($record_errors)) {
        $valid_count++;
    } else {
        $invalid_count++;
        $validation_errors[] = "Record ID $record_id: " . implode(', ', $record_errors);
    }
    
    // Limit the number of error messages to prevent overwhelming output
    if (count($validation_errors) >= 50) {
        $validation_errors[] = "... (Additional errors truncated. Only showing first 50 validation errors.)";
        break;
    }
}

// Additional table-level validations
$table_level_errors = [];

// Check for duplicate primary keys (shouldn't happen but good to verify)
$duplicate_id_sql = "SELECT id, COUNT(*) as count FROM `$table_name` GROUP BY id HAVING COUNT(*) > 1";
$duplicate_id_result = $conn->query($duplicate_id_sql);
if ($duplicate_id_result && $duplicate_id_result->num_rows > 0) {
    while ($duplicate = $duplicate_id_result->fetch_assoc()) {
        $table_level_errors[] = "Duplicate ID found: " . $duplicate['id'] . " (appears " . $duplicate['count'] . " times)";
    }
}

// Check for completely empty records
$empty_records_sql = "SELECT id FROM `$table_name` WHERE ";
$empty_conditions = [];
foreach ($columns as $column_info) {
    $field_name = $column_info['Field'];
    if ($field_name !== 'id' && !in_array($field_name, ['created_at', 'updated_at'])) {
        $empty_conditions[] = "(`$field_name` IS NULL OR `$field_name` = '')";
    }
}

if (!empty($empty_conditions)) {
    $empty_records_sql .= implode(' AND ', $empty_conditions);
    $empty_records_result = $conn->query($empty_records_sql);
    if ($empty_records_result && $empty_records_result->num_rows > 0) {
        $empty_count = $empty_records_result->num_rows;
        $table_level_errors[] = "Found $empty_count completely empty records (all fields are NULL or empty)";
    }
}

// Combine all errors
$all_errors = array_merge($validation_errors, $table_level_errors);

// Return validation results
echo json_encode([
    'success' => true,
    'valid_count' => $valid_count,
    'invalid_count' => $invalid_count,
    'total_records' => $valid_count + $invalid_count,
    'errors' => $all_errors,
    'has_errors' => !empty($all_errors)
]);
?>