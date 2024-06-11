<?php

/**
 * Email Confirmation Page
 * 
 * This script handles email confirmation for the lab management system. 
 * It verifies the email token from the URL, updates the user's email 
 * verification status, and displays an appropriate message.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

require 'dbcon.php'; // Include your database connection file

// Query to fetch the lab name and URL
$labQuery = "SELECT * FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

// Default value if the query fails or returns no result
$labName = "My Vivarium";
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Confirmation | <?php echo htmlspecialchars($labName); ?></title>

    <!-- Favicon and icons for different devices -->
    <link rel="icon" href="/icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="icon" sizes="192x192" href="/icons/android-chrome-192x192.png">
    <link rel="icon" sizes="512x512" href="/icons/android-chrome-512x512.png">
    <link rel="manifest" href="manifest.json" crossorigin="use-credentials">

    <!-- Bootstrap and Google Font -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 600px;
            margin-top: 200px;
            margin-bottom: 200px;
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

        .note {
            color: #888;
            font-size: 12px;
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
                margin-bottom: 5px;
            }

            .header img.header-logo {
                width: 150px;
            }

            .container {
                max-width: 350px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
                background-color: #f9f9f9;
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
    <div class="container content">
        <h2>Email Confirmation</h2>
        <br>

        <?php
        if (isset($_GET['token'])) {
            $token = $_GET['token'];
            $stmt = $con->prepare("SELECT username FROM users WHERE email_token = ? AND email_verified = 0");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($username);
                $stmt->fetch();

                $updateStmt = $con->prepare("UPDATE users SET email_verified = 1, email_token = NULL WHERE username = ?");
                $updateStmt->bind_param("s", $username);
                if ($updateStmt->execute()) {
                    echo "Email successfully confirmed. You can now <a href='index.php'>login</a>.";
                } else {
                    echo "Error confirming email. Please try again.";
                }
                $updateStmt->close();
            } else {
                echo "Invalid or expired token.";
            }
            $stmt->close();
        } else {
            echo "No token provided.";
        }
        $con->close();
        ?>
        <br>
    </div>
    <br>
    <?php include 'footer.php'; ?>
</body>

</html>