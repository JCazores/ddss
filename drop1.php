<?php $page='predict';
include("php/dbconnect.php");

// ✅ Run Python prediction script
$python = "C:\\Users\\jeycee\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
$script = "C:\\Users\\jeycee\\PycharmProjects\\PythonProject\\predict.py";
$cmd = "\"$python\" \"$script\" 2>&1";
exec($cmd, $output, $return_var);

if ($return_var !== 0) die("Prediction Script Error! Return Code: $return_var");

// ✅ Handle JSON output
$json_output = implode("", $output);
$data = json_decode($json_output, true);
if (json_last_error() !== JSON_ERROR_NONE) die("Failed to parse JSON: " . json_last_error_msg());
if (!$data || !isset($data['results'])) die("No results found in prediction data.");

$results = $data['results'];

// ✅ Search Logic
$searchResult = [];
if (isset($_POST['search'])) {
    $searchId = $conn->real_escape_string($_POST['studentId']);
    foreach ($results as $student) {
        if ($student['StudentID'] === $searchId) {
            $searchResult[] = $student;
        }
    }
} else {
    $searchResult = $results;
}

// ✅ Risk count for Chart.js
$riskCounts = ['High' => 0, 'Medium' => 0, 'Low' => 0];
foreach ($results as $student) {
    if ($student['final_risk_level'] == "High Risk") $riskCounts['High']++;
    elseif ($student['final_risk_level'] == "Medium Risk") $riskCounts['Medium']++;
    else $riskCounts['Low']++;
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>School Fees Management System</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
       <!--CUSTOM BASIC STYLES-->
    <link href="css/style1.css" rel="stylesheet" />
    <!--CUSTOM MAIN STYLES-->
    <link href="css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
	
	<link href="css/ui.css" rel="stylesheet" />
	<link href="css/datepicker.css" rel="stylesheet" />	
	
    <script src="js/jquery-1.10.2.js"></script>
	
    <script type='text/javascript' src='js/jquery/jquery-ui-1.10.1.custom.min.js'></script>
   <!-- Add this style block inside <head> -->
    <!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body {
        background-color: #f4f6f9;
        font-family: 'Open Sans', sans-serif;
    }

    h2 {
        margin-bottom: 20px;
        color: #333;
    }

    section, .card {
        background-color: #fff;
        padding: 20px;
        margin-bottom: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    form input[type="text"], form select, form input[type="file"] {
        padding: 8px;
        width: 300px;
        margin-right: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    form button {
        padding: 8px 15px;
        border: none;
        background-color: #3498db;
        color: #fff;
        border-radius: 5px;
        cursor: pointer;
    }

    form button:hover {
        background-color: #2980b9;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        border-radius: 10px;
        overflow: hidden;
    }

    table th, table td {
        padding: 15px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }

    table th {
        background-color: #2c3e50;
        color: #ecf0f1;
    }

    .high-risk {
        background-color: #f8d7da;
        color: #721c24;
        font-weight: bold;
    }

    .medium-risk {
        background-color: #fff3cd;
        color: #856404;
        font-weight: bold;
    }

    .low-risk {
        background-color: #d4edda;
        color: #155724;
        font-weight: bold;
    }

    .chart-container {
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>

	
</head>
<body>
<?php
include("php/header.php");
?>
<div id="page-wrapper">
            <div id="page-inner">
            <h2>Student Dropout Prediction Results</h2>

<section>
    <h2>Upload CSV File</h2>
    <form action="upload_csv.php" method="post" enctype="multipart/form-data">
        <label>Select Academic Year:</label>
        <select name="year" required>
            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--) { ?>
                <option value="<?php echo htmlspecialchars($y); ?>"><?php echo htmlspecialchars($y); ?></option>
            <?php } ?>
        </select>
        <input type="file" name="csvFile" accept=".csv" required>
        <button type="submit">Upload</button>
    </form>
</section>

<section>
    <h2>Search Student Record</h2>
    <form method="POST">
        <input type="text" name="studentId" placeholder="Enter Student ID" required>
        <button type="submit" name="search">Search</button>
    </form>
</section>

<section>
    <h2>Dropout Risk Analysis</h2>
    <div style="width: 700px; height: 400px;">
        <canvas id="riskChart"></canvas>
    </div>
</section>

<table>
    <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Attendance</th>
        <th>GPA</th>
        <th>Balance</th>
        <th>Final Risk Level</th>
        <th>Model Risk</th>
        <th>Dropout %</th>
        <th>Reasons</th>
        <th>Solutions</th>
        <th>Admin Action</th>
    </tr>

    <?php
    foreach ($searchResult as $student) {
        $StudentID = $conn->real_escape_string($student['StudentID']);
        $table_name = $conn->real_escape_string($student['table']);
        $sql = "SELECT StudentID, sname, Attendance, GPA, balance FROM `$table_name` WHERE StudentID = '$StudentID'";
        $res = $conn->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $risk_class = ($student['final_risk_level'] === "High Risk") ? "high-risk" : (($student['final_risk_level'] === "Medium Risk") ? "medium-risk" : "low-risk");
            echo "<tr class='$risk_class'>";
            echo "<td>" . htmlspecialchars($row['StudentID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['sname']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Attendance']) . "</td>";
            echo "<td>" . htmlspecialchars($row['GPA']) . "</td>";
            echo "<td>₱" . number_format($row['balance'], 2) . "</td>";
            echo "<td><b>" . htmlspecialchars($student['final_risk_level']) . "</b></td>";
            echo "<td>" . htmlspecialchars($student['model_predicted_risk']) . "</td>";
            echo "<td>" . htmlspecialchars($student['dropout_percentage']) . "%</td>";
            echo "<td>" . implode("<br>", $student['reasons']) . "</td>";
            echo "<td>" . implode("<br>", $student['recommended_solutions']) . "</td>";
            echo "<td>" . implode("<br>", $student['admin_action']) . "</td>";
            echo "</tr>";
        }
    }
    ?>
</table>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('riskChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['High Risk', 'Medium Risk', 'Low Risk'],
            datasets: [{
                label: 'Number of Students',
                data: [
                    <?php echo $riskCounts['High']; ?>,
                    <?php echo $riskCounts['Medium']; ?>,
                    <?php echo $riskCounts['Low']; ?>
                ],
                backgroundColor: [
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(46, 204, 113, 0.7)'
                ],
                borderColor: [
                    '#e74c3c',
                    '#f1c40f',
                    '#2ecc71'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <!-- /. WRAPPER  -->

</body>
</html>