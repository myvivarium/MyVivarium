<?php

/**
 * Login Page
 * 
 * This script handles user login, displays login errors, and redirects authenticated users to their intended destination or home page. 
 * It also displays a carousel of images and highlights the features of the web application.
 * 
 * Author: [Your Name]
 * Date: [Date]
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

// Query to fetch the lab name and URL
$labQuery = "SELECT * FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

// Default value if the query fails or returns no result
$labName = "My Vivarium";
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
    $url = $row['url'];
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
        $url = urldecode($_GET['redirect']);
        header("Location: $url");
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

                    // Reset login attempts and unlock the account
                    $reset_attempts = "UPDATE users SET login_attempts = 0, account_locked = NULL WHERE username=?";
                    $reset_stmt = mysqli_prepare($con, $reset_attempts);
                    mysqli_stmt_bind_param($reset_stmt, "s", $username);
                    mysqli_stmt_execute($reset_stmt);

                    // Redirect to the specified URL or default to home.php
                    if (isset($_GET['redirect'])) {
                        $url = urldecode($_GET['redirect']);
                        header("Location: $url");
                        exit;
                    } else {
                        header("Location: home.php");
                        exit;
                    }
                } else {
                    // Handle failed login attempts
                    $new_attempts = $row['login_attempts'] + 1;
                    if ($new_attempts >= 3) {
                        $lock_time = "UPDATE users SET account_locked = DATE_ADD(NOW(), INTERVAL 10 MINUTE), login_attempts = 3 WHERE username=?";
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

    <!-- Bootstrap and jQuery JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom CSS -->
    <style>
        .carousel img {
            height: 320px;
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

        @media (max-width: 576px) {
            header h1 {
                font-size: 2.2rem;
                margin-left: 10px;
            }

            header img.header-logo {
                width: 150px;
            }
        }
    </style>
</head>

<body>

    <header class="bg-dark text-white text-center py-3 d-flex flex-wrap justify-content-center align-items-center">
        <div class="logo-container d-flex justify-content-center align-items-center">
            <img src="images/logo1.jpg" alt="Logo" class="header-logo">
        </div>
        <h1 class="ml-3 mb-0"><?php echo htmlspecialchars($labName); ?></h1>
    </header>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <!-- Slideshow Column -->
            <div class="col-md-6">
                <div id="labCarousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active"> <img class="d-block w-100" src="images/DSC_0536.JPG" alt="Image 1"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0537.JPG" alt="Image 2"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0539.JPG" alt="Image 3"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0540.JPG" alt="Image 4"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0560.JPG" alt="Image 7"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0562.JPG" alt="Image 8"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0586.JPG" alt="Image 11"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0593.JPG" alt="Image 12"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0607.JPG" alt="Image 13"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0623.JPG" alt="Image 14"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0658.JPG" alt="Image 15"> </div>
                        <div class="carousel-item"> <img class="d-block w-100" src="images/DSC_0665.JPG" alt="Image 16"> </div>
                    </div>
                </div>
            </div>

            <!-- Login Form Column -->
            <div class="col-md-6">
                <div class="login-form">
                    <h3>Login</h3>
                    <?php if (isset($error_message)) { ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
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
                        <button type="submit" class="btn btn-primary" name="login">Login</button>
                        <a href="register.php" class="btn btn-secondary">Register</a>
                        <br><br>
                        <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
                    </form>
                </div>
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

    <!-- Include the footer -->
    <?php include 'footer.php'; ?>

</body>

</html>
