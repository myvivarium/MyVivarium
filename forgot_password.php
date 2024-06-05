<?php

/**
 *  Forgot Password Email Script
 *
 * This script handles the password reset functionality for users.
 * It verifies if the provided email exists in the database, generates a reset token,
 * saves it along with an expiration time, and sends an email with the reset link.
 * The script also fetches lab information to customize the page.
 *
 * Author: [Your Name]
 * Date: [Date]
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'dbcon.php';  // Include database connection file
require 'config.php';  // Include configuration file
require 'vendor/autoload.php';  // Include PHPMailer autoload file

// Query to fetch the lab name and URL
$labQuery = "SELECT lab_name, url FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

// Default value if the query fails or returns no result
$labName = "My Vivarium";
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
    $url = $row['url'];
}

// Handle form submission for password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Email exists, generate and save a reset token
        $resetToken = bin2hex(random_bytes(32));
        $expirationTimeUnix = time() + 3600; // 1 hour expiration time
        $expirationTime = date('Y-m-d H:i:s', $expirationTimeUnix);

        $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expiration = ?, login_attempts = 0, account_locked = NULL WHERE username = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("sss", $resetToken, $expirationTime, $email);
        $updateStmt->execute();

        // Send the password reset email
        $resetLink = "https://" . $url . "/reset_password.php?token=$resetToken";

        $to = $email;
        $subject = 'Password Reset';
        $message = "To reset your password, click the following link:\n$resetLink";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;

            $mail->setFrom(SENDER_EMAIL, SENDER_NAME);
            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            $resultMessage = "Password reset instructions have been sent to your email address.";
        } catch (Exception $e) {
            $resultMessage = "Email could not be sent. Error: " . $mail->ErrorInfo;
        }
    } else {
        $resultMessage = "Email address not found in our records. Please try again.";
    }

    $stmt->close();
    if (isset($updateStmt)) {
        $updateStmt->close();
    }
    $con->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo htmlspecialchars($labName); ?></title>

    <!-- Favicon and icons for different devices -->
    <link rel="icon" href="/icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="icon" sizes="192x192" href="/icons/android-chrome-192x192.png">
    <link rel="icon" sizes="512x512" href="/icons/android-chrome-512x512.png">
    <link rel="manifest" href="/icons/site.webmanifest">

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <style>
        .container {
            max-width: 600px;
            margin-top: 300px;
            margin-bottom: 300px;
            padding: 50px;
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

        .header-footer {
            background-color: #343a40;
            padding: 20px 0;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .header-footer h2,
        .header-footer p {
            margin: 0;
            color: #333;
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

        header {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            background-color: #343a40;
            color: white;
            padding: 1rem;
            text-align: center;
        }

        .logo-container {
            padding: 0;
            margin: 0;
        }

        header img.header-logo {
            width: 300px;
            height: auto;
            display: block;
            margin: 0;
        }

        header h1 {
            margin-left: 15px;
            margin-bottom: 0;
            margin-top: 12px;
            font-size: 3.5rem;
            white-space: nowrap;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        .form-label {
            font-weight: bold;
        }

        .required-asterisk {
            color: red;
        }

        .warning-text {
            color: #dc3545;
            /* Subtle red color */
            font-size: 14px;
        }

        @media (max-width: 576px) {
            header h1 {
                font-size: 2.2rem;
                margin-left: 10px;
            }

            header img.header-logo {
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
    <!-- Header with Lab Name -->
    <header class="bg-dark text-white text-center py-3 d-flex flex-wrap justify-content-center align-items-center">
        <div class="logo-container d-flex justify-content-center align-items-center">
            <img src="images/logo1.jpg" alt="Logo" class="header-logo">
        </div>
        <h1 class="ml-3 mb-0"><?php echo htmlspecialchars($labName); ?></h1>
    </header>

    <br>
    <br>
    <div class="container">
        <h2>Forgot Password</h2>
        <br>
        <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>
        <br>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address <span class="required-asterisk">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary" name="reset">Reset Password</button>
        </form>
        <?php if (isset($resultMessage)) {
            echo "<p class='result-message'>$resultMessage</p>";
        } ?>
        <br>
        <a href="index.php" class="btn btn-secondary">Go Back</a>
    </div>
    <br>

    <!-- Footer Section -->
    <br>
    <?php include 'footer.php'; ?>

</body>

</html>