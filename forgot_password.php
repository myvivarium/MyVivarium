<?php

/**
 *  Forgot Password Email Script
 *
 * This script handles the password reset functionality for users.
 * It verifies if the provided email exists in the database, generates a reset token,
 * saves it along with an expiration time, and sends an email with the reset link.
 * The script also fetches lab information to customize the page.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'dbcon.php';  // Include database connection file
require 'config.php';  // Include configuration file
require 'vendor/autoload.php';  // Include PHPMailer autoload file

// Query to fetch the lab name, URL, and Turnstile keys from the settings table
$labQuery = "SELECT name, value FROM settings WHERE name IN ('lab_name', 'url', 'cf-turnstile-sitekey', 'cf-turnstile-secretKey')";
$labResult = mysqli_query($con, $labQuery);

// Default values if the query fails or returns no result
$labName = "My Vivarium";
$url = "";
$turnstileSiteKey = "";
$turnstileSecretKey = "";

while ($row = mysqli_fetch_assoc($labResult)) {
    if ($row['name'] === 'lab_name') {
        $labName = $row['value'];
    } elseif ($row['name'] === 'url') {
        $url = $row['value'];
    } elseif ($row['name'] === 'cf-turnstile-sitekey') {
        $turnstileSiteKey = $row['value'];
    } elseif ($row['name'] === 'cf-turnstile-secretKey') {
        $turnstileSecretKey = $row['value'];
    }
}

// Handle form submission for password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $email = $_POST['email'];

    // Proceed with Turnstile verification only if both sitekey and secret key are set
    if (!empty($turnstileSiteKey) && !empty($turnstileSecretKey)) {
        // Check Turnstile token
        $turnstileResponse = $_POST['cf-turnstile-response'];

        // Verify the Turnstile token with Cloudflare's API
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret' => $turnstileSecretKey,
            'response' => $turnstileResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        // Send the verification request
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        // Check if Turnstile verification was successful
        if (!$result['success']) {
            $resultMessage = "Cloudflare Turnstile verification failed. Please try again.";
        } else {
            // Continue with password reset process if Turnstile verification is successful
            handlePasswordReset($con, $email, $url);
        }
    } else {
        // Skip Turnstile verification if keys are not set and proceed with password reset
        handlePasswordReset($con, $email, $url);
    }
}

function handlePasswordReset($con, $email, $url) {
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

    // Display the result message after form submission
    echo "<p class='result-message'>$resultMessage</p>";
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

        .form-label {
            font-weight: bold;
        }

        .required-asterisk {
            color: red;
        }

        .warning-text {
            color: #dc3545;
            font-size: 14px;
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
    <?php if ($demo === "yes") include('demo/demo-banner.php'); ?>
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
    <div class="container content">
        <h2>Forgot Password</h2>
        <br>
        <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>
        <br>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address <span class="required-asterisk">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <!-- Conditionally include the Cloudflare Turnstile Widget -->
            <?php if (!empty($turnstileSiteKey)) { ?>
                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <?php } ?>

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
    <?php include 'footer.php'; ?>

</body>

</html>
