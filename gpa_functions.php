<?php
/**
 * Business Logic Functions for GPA System
 * Separated for better performance and maintainability
 */

// Get available years, semesters, courses from database
function getAvailableFilters($conn) {
    static $cached = null;
    
    // Return cached result if available
    if ($cached !== null) {
        return $cached;
    }
    
    $availableYears = [];
    $availableSemesters = [];
    $availableCourses = [];
    $availableRiskLevels = ['High Risk', 'Medium Risk', 'Low Risk'];
    
    // Single optimized query to get all table info
    $tables_query = "SHOW TABLES LIKE 'student_%_sem_%'";
    $tables_result = $conn->query($tables_query);
    
    if ($tables_result) {
        while ($table_row = $tables_result->fetch_array()) {
            $table_name = $table_row[0];
            
            if (preg_match('/student_(\d{4})_sem_(\d)/', $table_name, $matches)) {
                $table_year = intval($matches[1]);
                $table_semester = $matches[2];
                
                if ($table_year >= 2020 && $table_year <= 2030) {
                    // Get years using optimized query
                    $year_query = "SELECT DISTINCT SUBSTRING(StudentID, 5, 4) as student_year 
                                  FROM `$table_name` 
                                  WHERE StudentID REGEXP '^OLFU[0-9]{4}'
                                  LIMIT 100"; // Limit for performance
                    
                    $year_result = $conn->query($year_query);
                    if ($year_result) {
                        while ($year_row = $year_result->fetch_assoc()) {
                            $student_year = intval($year_row['student_year']);
                            if ($student_year >= 2020 && $student_year <= 2030) {
                                $availableYears[$student_year] = true;
                            }
                        }
                    }
                    
                    // Get courses (optimized)
                    $course_query = "SELECT DISTINCT course FROM `$table_name` 
                                    WHERE course IS NOT NULL AND course != '' 
                                    LIMIT 50"; // Limit for performance
                    $course_result = $conn->query($course_query);
                    if ($course_result) {
                        while ($course_row = $course_result->fetch_assoc()) {
                            $course = trim($course_row['course']);
                            if ($course) {
                                $availableCourses[$course] = true;
                            }
                        }
                    }
                    
                    // Check if table has data
                    $count_query = "SELECT COUNT(*) as count FROM `$table_name` LIMIT 1";
                    $count_result = $conn->query($count_query);
                    if ($count_result && $count_result->fetch_assoc()['count'] > 0) {
                        $availableSemesters[$table_semester] = true;
                    }
                }
            }
        }
    }
    
    // Convert to arrays and sort
    $availableYears = array_keys($availableYears);
    $availableSemesters = array_keys($availableSemesters);
    $availableCourses = array_keys($availableCourses);
    
    sort($availableYears);
    sort($availableSemesters);
    sort($availableCourses);
    
    $cached = compact('availableYears', 'availableSemesters', 'availableCourses', 'availableRiskLevels');
    return $cached;
}

// Get relevant tables based on filters (optimized)
function getRelevantTables($conn, $filterYear = null, $filterSemester = null) {
    $relevant_tables = [];
    
    // Build WHERE clause for better performance
    $whereConditions = [];
    if ($filterYear !== null) {
        $whereConditions[] = "table_name LIKE 'student_%_sem_%'";
    }
    
    $tables_query = "SHOW TABLES LIKE 'student_%_sem_%'";
    $tables_result = $conn->query($tables_query);
    
    if ($tables_result) {
        while ($table_row = $tables_result->fetch_array()) {
            $table_name = $table_row[0];
            
            if (preg_match('/student_(\d{4})_sem_(\d)/', $table_name, $matches)) {
                $table_year = intval($matches[1]);
                $table_semester = intval($matches[2]);
                
                // Filter by semester at table level
                if ($filterSemester !== null && intval($filterSemester) !== $table_semester) {
                    continue;
                }
                
                // Quick count check with limit
                $count_query = "SELECT 1 FROM `$table_name`";
                if ($filterYear !== null) {
                    $count_query .= " WHERE StudentID LIKE 'OLFU" . intval($filterYear) . "%'";
                }
                $count_query .= " LIMIT 1";
                
                $count_result = $conn->query($count_query);
                if ($count_result && $count_result->num_rows > 0) {
                    $relevant_tables[] = [
                        'table_name' => $table_name,
                        'year' => $table_year,
                        'semester' => $table_semester
                    ];
                }
            }
        }
    }
    
    return $relevant_tables;
}

// Process and filter results (optimized with early returns)
function processResults($conn, $results, $filters, $searchTerm = '') {
    if (empty($results)) {
        return [];
    }
    
    $searchResult = [];
    $isSearching = !empty($searchTerm);
    $searchLower = $isSearching ? strtolower($searchTerm) : '';
    
    // Prepare filter values
    $filterYear = $filters['year'] ?? null;
    $filterSemester = $filters['semester'] ?? null;
    $filterCourse = $filters['course'] ?? null;
    $filterRiskLevel = $filters['riskLevel'] ?? null;
    
    foreach ($results as $student) {
        if (!isset($student['StudentID'], $student['table'])) {
            continue;
        }
        
        $StudentID = $conn->real_escape_string($student['StudentID']);
        $table_name = $conn->real_escape_string($student['table']);
        
        // Get student data
        $res = $conn->query("SELECT StudentID, sname, year, course, semester, Attendance, GPA, balance 
                            FROM `$table_name` 
                            WHERE StudentID = '$StudentID' 
                            LIMIT 1");
        
        if (!$res || !($row = $res->fetch_assoc())) {
            continue;
        }
        
        $completeStudent = array_merge($student, $row);
        
        // Apply filters with early exit
        if ($filterYear !== null) {
            $studentIdYear = null;
            if (preg_match('/^OLFU(\d{4})/', $completeStudent['StudentID'], $matches)) {
                $studentIdYear = intval($matches[1]);
            }
            if ($studentIdYear === null || $studentIdYear !== intval($filterYear)) {
                continue;
            }
        }
        
        if ($filterSemester !== null && intval($completeStudent['semester']) !== intval($filterSemester)) {
            continue;
        }
        
        if ($filterCourse !== null && trim($completeStudent['course']) !== $filterCourse) {
            continue;
        }
        
        if ($filterRiskLevel !== null && trim($completeStudent['final_risk_level']) !== $filterRiskLevel) {
            continue;
        }
        
        // Apply search filter
        if ($isSearching) {
            $studentIdLower = strtolower($completeStudent['StudentID']);
            $snameLower = strtolower($completeStudent['sname']);
            
            if (strpos($studentIdLower, $searchLower) === false && 
                strpos($snameLower, $searchLower) === false) {
                continue;
            }
        }
        
        $searchResult[] = $completeStudent;
    }
    
    return $searchResult;
}

// Calculate statistics (optimized)
function calculateStatistics($searchResult) {
    $stats = [
        'riskCounts' => ['High' => 0, 'Medium' => 0, 'Low' => 0],
        'semesterStats' => [],
        'courseStats' => []
    ];
    
    if (empty($searchResult)) {
        return $stats;
    }
    
    foreach ($searchResult as $student) {
        // Risk counts
        $level = $student['final_risk_level'] ?? 'Low Risk';
        if ($level == "High Risk") {
            $stats['riskCounts']['High']++;
        } elseif ($level == "Medium Risk") {
            $stats['riskCounts']['Medium']++;
        } else {
            $stats['riskCounts']['Low']++;
        }
        
        // Semester stats
        if (isset($student['semester'])) {
            $semester = $student['semester'];
            if (!isset($stats['semesterStats'][$semester])) {
                $stats['semesterStats'][$semester] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
            }
            
            if ($level == "High Risk") {
                $stats['semesterStats'][$semester]['High']++;
            } elseif ($level == "Medium Risk") {
                $stats['semesterStats'][$semester]['Medium']++;
            } else {
                $stats['semesterStats'][$semester]['Low']++;
            }
            $stats['semesterStats'][$semester]['Total']++;
        }
        
        // Course stats
        if (isset($student['course'])) {
            $course = $student['course'];
            if (!isset($stats['courseStats'][$course])) {
                $stats['courseStats'][$course] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
            }
            
            if ($level == "High Risk") {
                $stats['courseStats'][$course]['High']++;
            } elseif ($level == "Medium Risk") {
                $stats['courseStats'][$course]['Medium']++;
            } else {
                $stats['courseStats'][$course]['Low']++;
            }
            $stats['courseStats'][$course]['Total']++;
        }
    }
    
    return $stats;
}

// Run Python prediction with caching
function getPredictionResults($cache, $conn, $filterYear = null, $filterSemester = null) {
    // Generate cache key
    $cacheKey = $cache->generateKey('prediction_results', [
        'year' => $filterYear,
        'semester' => $filterSemester,
        'timestamp' => floor(time() / 300) // 5-minute intervals
    ]);
    
    // Try cache first
    if ($cache->exists($cacheKey, 300)) {
        $cachedData = $cache->get($cacheKey);
        if ($cachedData && isset($cachedData['results'])) {
            return [
                'success' => true,
                'data' => $cachedData,
                'results' => $cachedData['results'],
                'cached' => true
            ];
        }
    }
    
    // Run Python script
    $python = "C:\\Users\\Christian Azores\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
    $script = "C:\\Users\\Christian Azores\\PycharmProjects\\PythonProject\\predict.py";
    
    $cmd = "\"$python\" \"$script\"";
    if ($filterYear !== null || $filterSemester !== null) {
        if ($filterYear !== null) {
            $cmd .= " --year " . intval($filterYear);
        }
        if ($filterSemester !== null) {
            $cmd .= " --semester " . intval($filterSemester);
        }
    }
    $cmd .= " 2>&1";
    
    $startTime = microtime(true);
    exec($cmd, $output, $return_var);
    $executionTime = microtime(true) - $startTime;
    
    if ($return_var === 0 && !empty($output)) {
        $json_output = implode("", $output);
        $data = json_decode($json_output, true);
        
        if ($data && isset($data['results'])) {
            $results = $data['results'];
            
            // Cache the results
            $cacheData = $data;
            $cacheData['cache_metadata'] = [
                'generated_at' => date('Y-m-d H:i:s'),
                'execution_time' => $executionTime,
                'filter_year' => $filterYear,
                'filter_semester' => $filterSemester,
                'total_results' => count($results)
            ];
            
            $cache->set($cacheKey, $cacheData);
            
            return [
                'success' => true,
                'data' => $cacheData,
                'results' => $results,
                'cached' => false,
                'execution_time' => $executionTime
            ];
        }
    }
    
    return [
        'success' => false,
        'results' => [],
        'error' => "Return Code: $return_var",
        'output' => $output
    ];
}

// Handle search and redirect
function handleSearch($searchTerm) {
    if (empty($searchTerm)) {
        return;
    }
    
    // Build redirect URL
    $params = ['search' => $searchTerm];
    
    // Preserve filters
    foreach (['filterYear', 'filterSemester', 'filterCourse', 'filterRiskLevel', 'entries', 'debug'] as $param) {
        if (isset($_GET[$param]) && !empty($_GET[$param])) {
            $params[$param] = $_GET[$param];
        }
    }
    
    $redirectUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
    header("Location: " . $redirectUrl);
    exit();
}

// Validate and sanitize filters
function validateFilters($availableData) {
    $filters = [];
    
    // Year filter
    if (isset($_GET['filterYear']) && !empty($_GET['filterYear'])) {
        $year = intval($_GET['filterYear']);
        if ($year >= 2020 && $year <= 2030 && in_array($year, $availableData['availableYears'])) {
            $filters['year'] = $year;
        }
    }
    
    // Semester filter
    if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester'])) {
        $semester = intval($_GET['filterSemester']);
        if (in_array($semester, [1, 2]) && in_array(strval($semester), $availableData['availableSemesters'])) {
            $filters['semester'] = $semester;
        }
    }
    
    // Course filter
    if (isset($_GET['filterCourse']) && !empty($_GET['filterCourse'])) {
        $course = trim($_GET['filterCourse']);
        if (in_array($course, $availableData['availableCourses'])) {
            $filters['course'] = $course;
        }
    }
    
    // Risk level filter
    if (isset($_GET['filterRiskLevel']) && !empty($_GET['filterRiskLevel'])) {
        $riskLevel = trim($_GET['filterRiskLevel']);
        if (in_array($riskLevel, $availableData['availableRiskLevels'])) {
            $filters['riskLevel'] = $riskLevel;
        }
    }
    
    return $filters;
}