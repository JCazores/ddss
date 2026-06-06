<?php
// create_migration.php - Run this script once to fix all existing tables

include("php/dbconnect.php");

echo "<h2>Database Migration: Adding Semester Column</h2>";
echo "<div class='container' style='margin: 20px; padding: 20px; border: 1px solid #ddd;'>";

try {
    // Get all tables in the database
    $result = $conn->query("SHOW TABLES");
    
    if (!$result) {
        throw new Exception("Error getting tables: " . $conn->error);
    }
    
    $tablesUpdated = 0;
    $tablesSkipped = 0;
    $errors = [];
    
    echo "<h3>Scanning Database Tables...</h3>";
    echo "<ul>";
    
    while ($row = $result->fetch_array()) {
        $tableName = $row[0];
        
        // Only process student tables and other relevant tables
        if (strpos($tableName, 'student') !== false || in_array($tableName, ['students', 'student_data'])) {
            
            echo "<li><strong>Processing table: $tableName</strong>";
            
            // Check if semester column exists
            $checkColumn = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE 'semester'");
            
            if (!$checkColumn) {
                $errors[] = "Error checking columns in $tableName: " . $conn->error;
                echo " - <span style='color: red;'>Error checking columns</span></li>";
                continue;
            }
            
            if ($checkColumn->num_rows == 0) {
                // Add semester column
                $alterSQL = "ALTER TABLE `$tableName` 
                            ADD COLUMN semester VARCHAR(10) DEFAULT NULL AFTER year,
                            ADD INDEX idx_year_semester (year, semester)";
                
                if ($conn->query($alterSQL)) {
                    echo " - <span style='color: green;'>Added semester column</span>";
                    $tablesUpdated++;
                    
                    // Try to populate semester based on table name pattern
                    if (preg_match('/student_(\d+)_sem_(\d+)/', $tableName, $matches)) {
                        $year = $matches[1];
                        $semester = $matches[2];
                        
                        $updateSQL = "UPDATE `$tableName` 
                                     SET semester = '$semester', year = '$year' 
                                     WHERE semester IS NULL OR year IS NULL";
                        
                        if ($conn->query($updateSQL)) {
                            $affected = $conn->affected_rows;
                            echo " - <span style='color: blue;'>Updated $affected rows with year=$year, semester=$semester</span>";
                        }
                    } else {
                        // For tables without clear naming pattern, set default values
                        $updateSQL = "UPDATE `$tableName` 
                                     SET semester = '1' 
                                     WHERE semester IS NULL";
                        
                        if ($conn->query($updateSQL)) {
                            $affected = $conn->affected_rows;
                            echo " - <span style='color: orange;'>Set default semester=1 for $affected rows</span>";
                        }
                    }
                    
                } else {
                    $errors[] = "Failed to add semester column to $tableName: " . $conn->error;
                    echo " - <span style='color: red;'>Failed to add column</span>";
                }
            } else {
                echo " - <span style='color: gray;'>Semester column already exists</span>";
                $tablesSkipped++;
            }
            
            echo "</li>";
        }
    }
    
    echo "</ul>";
    
    echo "<h3>Migration Summary</h3>";
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo "<p><strong>Tables Updated:</strong> $tablesUpdated</p>";
    echo "<p><strong>Tables Skipped:</strong> $tablesSkipped</p>";
    
    if (!empty($errors)) {
        echo "<p><strong>Errors:</strong></p>";
        echo "<ul style='color: red;'>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'><strong>Migration completed successfully!</strong></p>";
    }
    echo "</div>";
    
    // Test query to verify the fix
    echo "<h3>Testing Query...</h3>";
    $testResult = $conn->query("SHOW TABLES LIKE 'student_%'");
    
    if ($testResult && $testResult->num_rows > 0) {
        echo "<p>Testing semester column access on available student tables:</p>";
        echo "<ul>";
        
        while ($tableRow = $testResult->fetch_array()) {
            $testTable = $tableRow[0];
            
            $testQuery = "SELECT StudentID, sname, year, course, semester, Attendance, GPA, balance 
                         FROM `$testTable` 
                         LIMIT 1";
            
            $testQueryResult = $conn->query($testQuery);
            
            if ($testQueryResult) {
                echo "<li style='color: green;'>✓ Table $testTable - Query successful</li>";
            } else {
                echo "<li style='color: red;'>✗ Table $testTable - Query failed: " . $conn->error . "</li>";
            }
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background: #ffe6e6; border-radius: 5px;'>";
    echo "<strong>Migration Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div>";

// Close connection
if (isset($conn)) {
    $conn->close();
}

echo "<p><em>Migration completed. You can now run your main application.</em></p>";
?>