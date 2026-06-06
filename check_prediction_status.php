<?php
/**
 * Real-time Prediction Status Checker
 * Called by JavaScript to monitor prediction progress
 */

header('Content-Type: application/json');
include("php/dbconnect.php");

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request ID'
    ]);
    exit;
}

try {
    // Get prediction request status
    $query = "SELECT 
        id,
        request_timestamp,
        year,
        semester,
        table_name,
        records_count,
        status,
        prediction_started_at,
        prediction_completed_at,
        predictions_generated,
        error_message,
        trigger_method,
        TIMESTAMPDIFF(SECOND, prediction_started_at, prediction_completed_at) as duration_seconds
    FROM prediction_requests 
    WHERE id = ?
    LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $response = [
            'success' => true,
            'request_id' => $row['id'],
            'status' => $row['status'],
            'year' => $row['year'],
            'semester' => $row['semester'],
            'table_name' => $row['table_name'],
            'records_count' => intval($row['records_count']),
            'predictions_count' => intval($row['predictions_generated']),
            'trigger_method' => $row['trigger_method'],
            'request_timestamp' => $row['request_timestamp'],
            'started_at' => $row['prediction_started_at'],
            'completed_at' => $row['prediction_completed_at'],
            'error_message' => $row['error_message']
        ];
        
        // Calculate duration
        if ($row['status'] === 'completed' && $row['duration_seconds']) {
            $minutes = floor($row['duration_seconds'] / 60);
            $seconds = $row['duration_seconds'] % 60;
            $response['duration'] = $minutes > 0 ? 
                "{$minutes}m {$seconds}s" : 
                "{$seconds}s";
        }
        
        // Add progress percentage estimate
        if ($row['status'] === 'processing') {
            $elapsed = time() - strtotime($row['prediction_started_at']);
            // Estimate: ~0.5 seconds per student
            $estimated_total = $row['records_count'] * 0.5;
            $progress = min(95, ($elapsed / $estimated_total) * 100);
            $response['progress_percentage'] = round($progress, 1);
            $response['estimated_remaining'] = max(0, ceil($estimated_total - $elapsed));
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Request not found'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>