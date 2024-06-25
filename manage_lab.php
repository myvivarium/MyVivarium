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

// Check if the user is logged in and has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to the index page
    header("Location: index.php");
    exit; // Ensure no further code is executed
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch lab details from the database
$query = "SELECT * FROM settings";
$result = mysqli_query($con, $query);
$labData = [];
while ($row = mysqli_fetch_assoc($result)) {
    $labData[$row['name']] = $row['value'];
}

// Provide default values if no data is found
$defaultLabData = [
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

$labData = array_merge($defaultLabData, $labData);

$updateMessage = '';

// Handle form submission for lab data update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_lab'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Sanitize and fetch form inputs
    $inputFields = ['lab_name', 'url', 'r1_temp', 'r1_humi', 'r1_illu', 'r1_pres', 'r2_temp', 'r2_humi', 'r2_illu', 'r2_pres'];
    $inputData = [];
    foreach ($inputFields as $field) {
        $inputData[$field] = filter_input(INPUT_POST, $field, FILTER_SANITIZE_STRING);
    }

    // Update or insert new data
    foreach ($inputData as $name => $value) {
        $checkQuery = "SELECT COUNT(*) as count FROM settings WHERE name = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            // Update existing setting
            $updateQuery = "UPDATE settings SET value = ? WHERE name = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->bind_param("ss", $value, $name);
        } else {
            // Insert new setting
            $insertQuery = "INSERT INTO settings (name, value) VALUES (?, ?)";
            $updateStmt = $con->prepare($insertQuery);
            $updateStmt->bind_param("ss", $name, $value);
        }
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Refresh lab data
    $result = mysqli_query($con, $query);
    $labData = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $labData[$row['name']] = $row['value'];
    }

    $labData = array_merge($defaultLabData, $labData);

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
    <div class="container content">
        <h2>Manage Lab</h2>
        <!-- Form for updating lab information -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
