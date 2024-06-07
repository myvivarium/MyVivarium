<?php

/**
 * Manage Lab Page
 * 
 * This script allows logged-in users to view and update lab details, including the lab name, URL, and IoT sensor links for two rooms.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit; // Ensure no further code is executed
}

// Fetch lab details from the database
$query = "SELECT * FROM data LIMIT 1";
$result = mysqli_query($con, $query);
$labData = mysqli_fetch_assoc($result);

// Provide default values if no data is found
if (!$labData) {
    $labData = [
        'lab_name' => '',
        'url' => '',
        'r1_temp' => '',
        'r1_humi' => '',
        'r1_illu' => '',
        'r1_pres' => '',
        'r2_temp' => '',
        'r2_humi' => '',
        'r2_illu' => '',
        'r2_pres' => ''
    ];
}

$updateMessage = '';

// Handle form submission for lab data update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_lab'])) {
    // Sanitize and fetch form inputs
    $labName = filter_input(INPUT_POST, 'lab_name', FILTER_SANITIZE_STRING);
    $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
    $r1_temp = filter_input(INPUT_POST, 'r1_temp', FILTER_SANITIZE_STRING);
    $r1_humi = filter_input(INPUT_POST, 'r1_humi', FILTER_SANITIZE_STRING);
    $r1_illu = filter_input(INPUT_POST, 'r1_illu', FILTER_SANITIZE_STRING);
    $r1_pres = filter_input(INPUT_POST, 'r1_pres', FILTER_SANITIZE_STRING);
    $r2_temp = filter_input(INPUT_POST, 'r2_temp', FILTER_SANITIZE_STRING);
    $r2_humi = filter_input(INPUT_POST, 'r2_humi', FILTER_SANITIZE_STRING);
    $r2_illu = filter_input(INPUT_POST, 'r2_illu', FILTER_SANITIZE_STRING);
    $r2_pres = filter_input(INPUT_POST, 'r2_pres', FILTER_SANITIZE_STRING);

    // Check if data already exists
    $checkQuery = "SELECT COUNT(*) as count FROM data";
    $checkResult = mysqli_query($con, $checkQuery);
    $rowCount = mysqli_fetch_assoc($checkResult)['count'];

    if ($rowCount > 0) {
        // Update existing data
        $updateQuery = "UPDATE data SET lab_name = ?, url = ?, r1_temp = ?, r1_humi = ?, r1_illu = ?, r1_pres = ?, r2_temp = ?, r2_humi = ?, r2_illu = ?, r2_pres = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("ssssssssss", $labName, $url, $r1_temp, $r1_humi, $r1_illu, $r1_pres, $r2_temp, $r2_humi, $r2_illu, $r2_pres);
    } else {
        // Insert new data
        $insertQuery = "INSERT INTO data (lab_name, url, r1_temp, r1_humi, r1_illu, r1_pres, r2_temp, r2_humi, r2_illu, r2_pres) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $updateStmt = $con->prepare($insertQuery);
        $updateStmt->bind_param("ssssssssss", $labName, $url, $r1_temp, $r1_humi, $r1_illu, $r1_pres, $r2_temp, $r2_humi, $r2_illu, $r2_pres);
    }

    $updateStmt->execute();
    $updateStmt->close();

    // Refresh lab data
    $result = mysqli_query($con, $query);
    $labData = mysqli_fetch_assoc($result);

    if (!$labData) {
        $labData = [
            'lab_name' => '',
            'url' => '',
            'r1_temp' => '',
            'r1_humi' => '',
            'r1_illu' => '',
            'r1_pres' => '',
            'r2_temp' => '',
            'r2_humi' => '',
            'r2_illu' => '',
            'r2_pres' => ''
        ];
    }

    $updateMessage = "Lab information updated successfully.";
}

// Include the header file
require 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta tags for character encoding and responsive design -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Lab</title>

    <!-- Inline CSS for styling -->
    <style>
        .container {
            max-width: 800px;
            margin-top: 50px;
            margin-bottom: 50px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .btn1 {
            display: block;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn1:hover {
            background-color: #0056b3;
        }

        .update-message {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #dff0d8;
            border: 1px solid #3c763d;
            color: #3c763d;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Manage Lab</h2>
        <!-- Form for updating lab information -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="lab_name">Lab Name</label>
                <textarea class="form-control" id="lab_name" name="lab_name" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['lab_name']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="url">URL</label>
                <textarea class="form-control" id="url" name="url" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['url']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r1_temp">Room 1 Temperature Sensor</label>
                <textarea class="form-control" id="r1_temp" name="r1_temp" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r1_temp']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r1_humi">Room 1 Humidity Sensor</label>
                <textarea class="form-control" id="r1_humi" name="r1_humi" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r1_humi']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r1_illu">Room 1 Illumination Sensor</label>
                <textarea class="form-control" id="r1_illu" name="r1_illu" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r1_illu']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r1_pres">Room 1 Pressure Sensor</label>
                <textarea class="form-control" id="r1_pres" name="r1_pres" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r1_pres']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r2_temp">Room 2 Temperature Sensor</label>
                <textarea class="form-control" id="r2_temp" name="r2_temp" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r2_temp']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r2_humi">Room 2 Humidity Sensor</label>
                <textarea class="form-control" id="r2_humi" name="r2_humi" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r2_humi']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r2_illu">Room 2 Illumination Sensor</label>
                <textarea class="form-control" id="r2_illu" name="r2_illu" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r2_illu']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="r2_pres">Room 2 Pressure Sensor</label>
                <textarea class="form-control" id="r2_pres" name="r2_pres" oninput="adjustTextareaHeight(this)"><?php echo htmlspecialchars($labData['r2_pres']); ?></textarea>
            </div>
            <button type="submit" class="btn1 btn-primary" name="update_lab">Update Lab Information</button>
        </form>
        <?php if ($updateMessage) {
            echo "<p class='update-message'>$updateMessage</p>";
        } ?>
    </div>

    <!-- Include the footer -->
    <?php include 'footer.php'; ?>

    <!-- JavaScript for adjusting textarea height dynamically -->
    <script>
        function adjustTextareaHeight(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('textarea').forEach(function(textarea) {
                adjustTextareaHeight(textarea);
            });
        });
    </script>
</body>

</html>

<?php mysqli_close($con); ?>
