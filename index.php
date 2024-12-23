<?php

/**
 * Login Page
 * 
 * This script handles user login, displays login errors, and redirects authenticated users to their intended destination or home page. 
 * It also displays a carousel of images and highlights the features of the web application.
 * 
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';
require 'config.php'; // Include configuration file for SMTP details
require 'vendor/autoload.php'; // Include PHPMailer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Query to fetch the lab name, URL, and Turnstile keys from the settings table
$labQuery = "SELECT name, value FROM settings WHERE name IN ('lab_name', 'url', 'cf-turnstile-secretKey', 'cf-turnstile-sitekey')";
$labResult = mysqli_query($con, $labQuery);

// Default values if the query fails or returns no result
$labName = "My Vivarium";
$url = "";
$turnstileSecretKey = "";
$turnstileSiteKey = "";

while ($row = mysqli_fetch_assoc($labResult)) {
    if ($row['name'] === 'lab_name') {
        $labName = $row['value'];
    } elseif ($row['name'] === 'url') {
        $url = $row['value'];
    } elseif ($row['name'] === 'cf-turnstile-secretKey') {
        $turnstileSecretKey = $row['value'];
    } elseif ($row['name'] === 'cf-turnstile-sitekey') {
        $turnstileSiteKey = $row['value'];
    }
}

// Function to send confirmation email
function sendConfirmationEmail($to, $token)
{
    global $url;
    $confirmLink = "https://" . $url . "/confirm_email.php?token=$token";
    $subject = 'Email Confirmation';
    $message = "Please click the link below to confirm your email address:\n$confirmLink";

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;

        //Recipients
        $mail->setFrom(SENDER_EMAIL, SENDER_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

// Check if the user is already logged in
if (isset($_SESSION['name'])) {
    // Redirect to the specified URL or default to home.php
    if (isset($_GET['redirect'])) {
        $rurl = urldecode($_GET['redirect']);
        header("Location: $rurl");
        exit;
    } else {
        header("Location: home.php");
        exit;
    }
}

// Handle login form submission
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Proceed with Turnstile verification only if Turnstile keys are set
    if (!empty($turnstileSiteKey) && !empty($turnstileSecretKey)) {
        $turnstileResponse = $_POST['cf-turnstile-response'];
        
        // Verify Turnstile token
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret' => $turnstileSecretKey,
            'response' => $turnstileResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        // Send request to verify Turnstile response
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);

        // Check Turnstile response success
        if (!$result['success']) {
            // Store error message in the session to display to the user
            $_SESSION['error_message'] = "Cloudflare Turnstile verification failed. Please try again.";
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
            exit;
        }
    }

    // Proceed with login validation if Turnstile passed or not required
    $query = "SELECT * FROM users WHERE username=?";
    $statement = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($statement, "s", $username);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    if ($row = mysqli_fetch_assoc($result)) {
        // Check if the email is verified
        if ($row['email_verified'] == 0) {
            // Check if email_token is empty
            if (empty($row['email_token'])) {
                // Generate a new token
                $new_token = bin2hex(random_bytes(16));

                // Update the database with the new token
                $update_token_query = "UPDATE users SET email_token = ? WHERE username = ?";
                $update_token_stmt = mysqli_prepare($con, $update_token_query);
                mysqli_stmt_bind_param($update_token_stmt, "ss", $new_token, $username);
                mysqli_stmt_execute($update_token_stmt);
                mysqli_stmt_close($update_token_stmt);

                // Use the new token for sending the confirmation email
                $token = $new_token;
            } else {
                // Use the existing token
                $token = $row['email_token'];
            }

            // Send the confirmation email
            sendConfirmationEmail($username, $token);

            // Set error message for the user
            $error_message = "Your email is not verified. A new verification email has been sent. Please check your email to verify your account.";
        } else {
            // Check if the account status is approved
            if ($row['status'] != 'approved') {
                $error_message = "Your account is pending admin approval.";
            } else {
                // Check if the account is locked
                if (!is_null($row['account_locked']) && new DateTime() < new DateTime($row['account_locked'])) {
                    $error_message = "Account is temporarily locked. Please try again later.";
                } else {
                    // Verify password
                    if (password_verify($password, $row['password'])) {
                        // Set session variables
                        $_SESSION['name'] = $row['name'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['role'] = $row['role'];
                        $_SESSION['position'] = $row['position'];
                        $_SESSION['user_id'] = $row['id'];

                        // Regenerate session ID to prevent session fixation    
                        session_regenerate_id(true);

                        // Reset login attempts and unlock the account
                        $reset_attempts = "UPDATE users SET login_attempts = 0, account_locked = NULL WHERE username=?";
                        $reset_stmt = mysqli_prepare($con, $reset_attempts);
                        mysqli_stmt_bind_param($reset_stmt, "s", $username);
                        mysqli_stmt_execute($reset_stmt);

                        // Redirect to the specified URL or default to home.php
                        if (isset($_GET['redirect'])) {
                            $rurl = urldecode($_GET['redirect']);
                            header("Location: $rurl");
                            exit;
                        } else {
                            header("Location: home.php");
                            exit;
                        }
                    } else {
                        // Handle failed login attempts
                        $new_attempts = $row['login_attempts'] + 1;
                        if ($new_attempts >= 3) {
                            $lock_time = "UPDATE users SET account_locked = DATE_ADD(NOW(), INTERVAL 15 MINUTE), login_attempts = 3 WHERE username=?";
                            $lock_stmt = mysqli_prepare($con, $lock_time);
                            mysqli_stmt_bind_param($lock_stmt, "s", $username);
                            mysqli_stmt_execute($lock_stmt);
                            $error_message = "Account is temporarily locked for 10 mins due to too many failed login attempts.";
                        } else {
                            $update_attempts = "UPDATE users SET login_attempts = ? WHERE username=?";
                            $update_stmt = mysqli_prepare($con, $update_attempts);
                            mysqli_stmt_bind_param($update_stmt, "is", $new_attempts, $username);
                            mysqli_stmt_execute($update_stmt);
                            $error_message = "Invalid password. Please try again.";
                        }
                    }
                }
            }
        }
    } else {
        $error_message = "No user found with that username.";
    }
    mysqli_stmt_close($statement);
}
mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($labName); ?></title>

    <!-- Favicon and Icons -->
    <link rel="icon" href="icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/favicon-16x16.png">
    <link rel="icon" sizes="192x192" href="icons/android-chrome-192x192.png">
    <link rel="icon" sizes="512x512" href="icons/android-chrome-512x512.png">
    <link rel="manifest" href="manifest.json" crossorigin="use-credentials">

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <!-- Bootstrap and jQuery JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom CSS -->
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }

        .carousel img {
            height: 390px;
            object-fit: cover;
            width: 100%;
        }

        .login-form {
            padding: 10px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
        }

        .feature-box {
            transition: transform .2s, box-shadow .2s;
            border-radius: 10px;
            padding: 30px;
            background-color: white;
            box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: 50px 0;
        }

        .feature-box h3 {
            margin-top: 0;
            color: #007bff;
        }

        .feature-box p {
            margin-bottom: 0;
        }

        .forgot-password-link {
            text-align: left;
            margin-top: 50px;
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

        /* Responsive styling for smaller screens */
        @media (max-width: 576px) {
            .header h2 {
                font-size: 1.8rem;
                margin-bottom: 5px;
            }

            .header img.header-logo {
                width: 150px;
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

    <div class="content">
        <!-- Main Content -->
        <div class="container mt-4">
            <div class="row">
                <!-- Slideshow Column -->
                <div class="col-md-6">
                    <div id="labCarousel" class="carousel slide" data-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active"> <img class="d-block w-100" src="images/DSC_0536.webp" alt="Image 1"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0537.webp" alt="Image 2"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0539.webp" alt="Image 3"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0540.webp" alt="Image 4"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0560.webp" alt="Image 7"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0562.webp" alt="Image 8"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0586.webp" alt="Image 11"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0593.webp" alt="Image 12"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0607.webp" alt="Image 13"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0623.webp" alt="Image 14"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0658.webp" alt="Image 15"> </div>
                            <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0665.webp" alt="Image 16"> </div>
                        </div>
                    </div>
                    <?php if ($demo === "yes") include('demo/demo-disclaimer.php'); ?>
                </div>

                <!-- Login Form Column -->
                <div class="col-md-6">
                    <div class="login-form">
                        <h3>Login</h3>
                        <?php if (isset($_SESSION['error_message'])) { ?>
                            <div class="alert alert-danger">
                                <?php 
                                    echo $_SESSION['error_message']; 
                                    unset($_SESSION['error_message']); // Clear the error message
                                ?>
                            </div>
                        <?php } ?>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Email Address</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <!-- Conditionally include Cloudflare Turnstile Widget -->
                            <?php if (!empty($turnstileSiteKey)) { ?>
                                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>"></div>
                                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                            <?php } ?>

                            <button type="submit" class="btn btn-primary" name="login">Login</button>
                            <a href="register.php" class="btn btn-secondary">Register</a>
                            <br><br>
                            <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
                        </form>
                    </div>
                    <?php if ($demo === "yes") include('demo/demo-credentials.php'); ?>
                </div>
            </div>

            <!-- New Row for Unique Features -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h2 class="text-center">Welcome to the <?php echo htmlspecialchars($labName); ?></h2>
                    <p class="text-center italic">Elevate Your Research with IoT-Enhanced Colony Management</p>

                    <!-- Feature Box 1 -->
                    <div class="col-md-6 mb-6 mx-auto feature-box text-center">
                        <h3>Real-Time Environmental Monitoring</h3>
                        <p>Gain unparalleled insights into the conditions of your vivarium. Our IoT sensors continuously track temperature and humidity levels, ensuring a stable and controlled environment for your research animals.</p>
                    </div>

                    <!-- Feature Box 2 -->
                    <div class="col-md-6 mb-6 mx-auto feature-box text-center">
                        <h3>Effortless Cage and Mouse Tracking</h3>
                        <p>Seamlessly monitor every cage and mouse in your facility. No more manual record-keeping or confusion.</p>
                    </div>

                    <!-- Feature Box 3 -->
                    <div class="col-md-6 mb-6 mx-auto feature-box text-center">
                        <h3>Security and Compliance</h3>
                        <p>Rest easy knowing your data is secure and compliant with industry regulations. We prioritize data integrity and confidentiality.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the footer -->
    <?php include 'footer.php'; ?>
    <script>
        function adjustFooter() {
            const footer = document.getElementById('footer');
            const container = document.querySelector('.top-container');

            if (footer && container) {
                // Remove inline styles to calculate natural height
                footer.style.position = 'relative';
                footer.style.bottom = 'auto';

                const containerHeight = container.offsetHeight;
                const windowHeight = window.innerHeight;

                // If content is shorter than viewport, fix the footer at the bottom
                if (containerHeight < windowHeight) {
                    footer.style.position = 'absolute';
                    footer.style.bottom = '0';
                } else {
                    footer.style.position = 'relative';
                    footer.style.bottom = 'auto';
                }
            }
        }

        window.addEventListener('load', adjustFooter);
        window.addEventListener('resize', adjustFooter);
    </script>
</body>

</html>
