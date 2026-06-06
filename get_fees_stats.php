<?php
include("php/dbconnect.php");

// Initialize response array
$response = array(
    'total_fees' => 0,
    'paid_fees' => 0,
    'pending_fees' => 0,
    'chart_data' => array(
        'fees_trends' => array(
            'labels' => array(),
            'data' => array()
        ),
        'payment_status' => array()
    )
);

// Get total fees and balances
$sql = "SELECT SUM(fees) as total_fees, SUM(fees - balance) as paid_fees, SUM(balance) as pending_fees FROM student";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response['total_fees'] = intval($row['total_fees']);
    $response['paid_fees'] = intval($row['paid_fees']);
    $response['pending_fees'] = intval($row['pending_fees']);
}

// Get payment trends for last 6 months
$months = array();
$data = array();

// Get current month and year
$currentMonth = date('n');
$currentYear = date('Y');

// Generate last 6 months
for ($i = 5; $i >= 0; $i--) {
    $month = ($currentMonth - $i) > 0 ? ($currentMonth - $i) : (12 + ($currentMonth - $i));
    $year = ($currentMonth - $i) > 0 ? $currentYear : ($currentYear - 1);
    
    $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
    $months[] = $monthName;
    
    // Format month and year for SQL query
    $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
    $yearMonthFormatted = "$year-$monthFormatted";
    
    // Query for monthly payments
    $sql = "SELECT SUM(paid) as monthly_paid FROM fees_transaction 
            WHERE submitdate LIKE '$yearMonthFormatted%'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $data[] = intval($row['monthly_paid']) ?: 0; // Use 0 if null
    } else {
        $data[] = 0;
    }
}

// Add to response
$response['chart_data']['fees_trends']['labels'] = $months;
$response['chart_data']['fees_trends']['data'] = $data;

// Calculate payment status percentages for pie chart
if ($response['total_fees'] > 0) {
    $paidPercentage = ($response['paid_fees'] / $response['total_fees']) * 100;
    $pendingPercentage = ($response['pending_fees'] / $response['total_fees']) * 100;
    
    $response['chart_data']['payment_status'] = array(
        round($paidPercentage), // Paid
        round($pendingPercentage)  // Pending
    );
} else {
    $response['chart_data']['payment_status'] = array(0, 0);
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>