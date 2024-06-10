<?php

/**
 * Password Reset Page
 * 
 * This script handles the password reset functionality for users. 
 * Users receive a token to reset their password. The script verifies 
 * the token, allows the user to enter a new password, and updates 
 * the database with the new password if the token is valid.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

// Include the database connection file
require 'dbcon.php';

// Query to fetch the lab name
$labQuery = "SELECT lab_name FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

$labName = "My Vivarium"; // A default value in case the query fails or returns no result
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
}

$resultMessage = "";
$updateStmt = null; // Initialize $updateStmt for later use

// Check if the form is submitted via POST and the reset button was clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if new password and confirm password are the same
    if ($newPassword === $confirmPassword) {
        // Prepare SQL to check if the token exists and is valid
        $query = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiration >= NOW()";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the token is valid
        if ($result->num_rows == 1) {
            // Fetch user data
            $row = $result->fetch_assoc();
            $username = $row['username'];

            // Prepare SQL to update the user's password
            $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiration = NULL WHERE username = ?";
            $updateStmt = $con->prepare($updateQuery);
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateStmt->bind_param("ss", $hashedPassword, $username);

            // Execute the update and set a success or failure message
            if ($updateStmt->execute()) {
                $resultMessage = "Password reset successfully. You can now <a href='index.php'>login</a> with your new password.";
            } else {
                $resultMessage = "Password reset failed. Please try again.";
            }
        } else {
            $resultMessage = "Invalid or expired token. Please request a new password reset.";
        }

        // Close the statement
        $stmt->close();
        if (isset($updateStmt)) {
            $updateStmt->close();
        }
    } else {
        $resultMessage = "New Password and Confirm Password do not match.";
    }

    // Close the database connection
    $con->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <!-- Page Metadata -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?php echo htmlspecialchars($labName); ?></title>

    <!-- Favicon and icons for different devices -->
    <link rel="icon" href="/icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="icon" sizes="192x192" href="/icons/android-chrome-192x192.png">
    <link rel="icon" sizes="512x512" href="/icons/android-chrome-512x512.png">
    <link rel="manifest" href="manifest.json" crossorigin="use-credentials">

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <style>
        .container {
            max-width: 600px;
            margin-top: 300px;
            margin-bottom: 300px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .result-message {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #dff0d8;
            border: 1px solid #3c763d;
            color: #3c763d;
            border-radius: 5px;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            background-color: #343a40;
            color: white;
            padding: 1rem;
            text-align: center;
            margin: 0;
        }

        .header .logo-container {
            padding: 0;
            margin: 0;
        }

        .header img.header-logo {
            width: 300px;
            height: auto;
            display: block;
            margin: 0;
        }

        .header h2 {
            margin-left: 15px;
            margin-bottom: 0;
            margin-top: 12px;
            font-size: 3.5rem;
            white-space: nowrap;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        @media (max-width: 576px) {
            .header h2 {
                font-size: 1.8rem;
            }

            .header img.header-logo {
                width: 150px;
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <?php if ($demo === "yes") include('demo/demo.php'); ?>
    <div class="header">
        <div class="logo-container">
            <a href="home.php">
                <img src="images/logo1.jpg" alt="Logo" class="header-logo">
            </a>
        </div>
        <h2><?php echo htmlspecialchars($labName); ?></h2>
    </div>
    <br>
    <br>
    <div class="container">
        <h2>Reset Password</h2>
        <br>
        <form method="POST" action="">
            <!-- Hidden field for the token -->
            <input type="hidden" id="token" name="token" value="<?= htmlspecialchars($_GET['token']); ?>">

            <!-- New Password Field -->
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary" name="reset">Reset Password</button>
        </form>

        <!-- Display Result Message -->
        <?php if (!empty($resultMessage)) {
            echo "<p class='result-message'>$resultMessage</p>";
        } ?>
        <br>

        <a href="index.php" class="btn btn-secondary">Go Back</a>
    </div>

    <!-- Footer Section -->
    <br>
    <?php include 'footer.php'; ?>

</body>

</html>