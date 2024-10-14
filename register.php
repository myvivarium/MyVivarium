<?php

/**
 * User Registration Page
 * 
 * This script handles user registration for the lab management system. 
 * It collects user information, checks for spam submissions, verifies 
 * if the email already exists in the database, hashes the password, 
 * and stores the new user details in the database with a pending status.
 * 
 */

session_start();
require 'dbcon.php';  // Include database connection file
require 'config.php';  // Include configuration file
require 'vendor/autoload.php';  // Include PHPMailer autoload file

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

// Inform admins if a new user signup
function notifyAdmins($newUserDetails)
{
    global $con, $url;
    $adminQuery = "SELECT username FROM users WHERE role = 'admin'";
    $adminResult = mysqli_query($con, $adminQuery);

    if (mysqli_num_rows($adminResult) > 0) {
        $subject = 'New User Registration Notification';
        $message = "A new user has registered on the lab management system. Here are the details:\n";
        $message .= "Name: " . $newUserDetails['name'] . "\n";
        $message .= "Email: " . $newUserDetails['email'] . "\n";
        $message .= "Position: " . $newUserDetails['position'] . "\n";
        $message .= "Email Verified: " . ($newUserDetails['email_verified'] == 1 ? 'Yes' : 'No') . "\n";

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;

            // Recipients
            $mail->setFrom(SENDER_EMAIL, SENDER_NAME);

            while ($adminRow = mysqli_fetch_assoc($adminResult)) {
                $adminEmail = $adminRow['username'];
                $mail->addAddress($adminEmail);
            }

            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
        } catch (Exception $e) {
            error_log("Admin notification could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
}

// Function to verify Cloudflare Turnstile token
function verifyTurnstile($turnstileResponse)
{
    $secretKey = '0x4AAAAAAAxY9lYVO4s30kxlouhrHy5xkhg'; // Your secret key
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $secretKey,
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

    return $result['success'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check honeypot field for spam detection
    if (!empty($_POST['honeypot'])) {
        $_SESSION['resultMessage'] = "Spam detected! Please try again.";
    } else {
        // Verify the Cloudflare Turnstile token
        $turnstileResponse = $_POST['cf-turnstile-response'];
        if (!verifyTurnstile($turnstileResponse)) {
            $_SESSION['resultMessage'] = "Turnstile verification failed. Please try again.";
        } else {
            // Retrieve and sanitize user input
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $username = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);
            $role = "user";
            $status = "pending";
            $email_verified = 0; // Explicitly set to integer 0
            $email_token = bin2hex(random_bytes(16)); // Generate a random token

            // Check if the email already exists
            $checkEmailQuery = "SELECT username FROM users WHERE username = ?";
            $checkEmailStmt = $con->prepare($checkEmailQuery);
            $checkEmailStmt->bind_param("s", $username);
            $checkEmailStmt->execute();
            $checkEmailStmt->store_result();

            if ($checkEmailStmt->num_rows > 0) {
                $_SESSION['resultMessage'] = "Email address already registered. Please try logging in or use a different email.";
            } else {
                // Hash the password and insert the new user into the database
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $con->prepare("INSERT INTO users (name, username, position, role, password, status, email_verified, email_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssis", $name, $username, $position, $role, $hashedPassword, $status, $email_verified, $email_token);

                if ($stmt->execute()) {
                    sendConfirmationEmail($username, $email_token);

                    // Prepare new user details for admin notification
                    $newUserDetails = [
                        'name' => $name,
                        'email' => $username,
                        'position' => $position,
                        'email_verified' => $email_verified
                    ];
                    notifyAdmins($newUserDetails);

                    $_SESSION['resultMessage'] = "Registration successful. Please check your email to confirm your email address.";
                } else {
                    $_SESSION['resultMessage'] = "Registration failed. Please try again.";
                }
                $stmt->close();
            }
            $checkEmailStmt->close();
        }
    }
    $con->close();
    // Redirect to the same script to avoid POST resubmission issues
    header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
    exit;
}

// Retrieve and clear the session message after displaying
$resultMessage = isset($_SESSION['resultMessage']) ? $_SESSION['resultMessage'] : "";
unset($_SESSION['resultMessage']);  // Clear the message from session
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo htmlspecialchars($labName); ?></title>

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
    <div class="container content">
        <h2>User Registration</h2>
        <br>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <!-- Honeypot field for spam detection -->
            <div style="display:none;">
                <label for="honeypot">Keep this field blank</label>
                <input type="text" id="honeypot" name="honeypot">
            </div>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <select class="form-control" id="position" name="position" required>
                    <option value="" disabled selected>Select Position</option>
                    <option value="Principal Investigator">Principal Investigator</option>
                    <option value="Research Scientist">Research Scientist</option>
                    <option value="Postdoctoral Researcher">Postdoctoral Researcher</option>
                    <option value="PhD Student">PhD Student</option>
                    <option value="Masters Student">Masters Student</option>
                    <option value="Undergraduate">Undergraduate</option>
                    <option value="Laboratory Technician">Laboratory Technician</option>
                    <option value="Research Associate">Research Associate</option>
                    <option value="Lab Manager">Lab Manager</option>
                    <option value="Animal Care Technician">Animal Care Technician</option>
                    <option value="Interns and Volunteers">Interns and Volunteers</option>
                </select>
            </div>
            <div class="form-group">
                <label for="email">Email Address <span class="note">(Your email address will be your username for login)</span></label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <!-- Cloudflare Turnstile Widget -->
            <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>"></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

            <br>
            <button type="submit" class="btn btn-primary" name="signup">Register</button>
            <br>
            <a href="index.php" class="btn btn-secondary">Go Back</a>
        </form>
        <br>

        <!-- Display the result message if any -->
        <?php if (!empty($resultMessage)) {
            echo "<div class=\"alert alert-warning\" role=\"alert\">";
            echo $resultMessage;
            echo "</div>";
        } ?>
        <br>
    </div>
    <br>
    <?php include 'footer.php'; ?>
</body>

</html>