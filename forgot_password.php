<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'dbcon.php';

require 'vendor/autoload.php'; // Include PHPMailer autoload file

require 'email_credentials.php';

// Query to fetch the lab name
$labQuery = "SELECT lab_name,url FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

$labName = "My Vivarium"; // A default value in case the query fails or returns no result
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
    $url = $row['url'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {



    $email = $_POST['email'];

    // Check if the email exists in your database
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Email exists, generate and save a reset token
        $resetToken = bin2hex(random_bytes(32));
        $expirationTimeUnix = time() + 3600; // Example Unix timestamp
        $expirationTime = date('Y-m-d H:i:s', $expirationTimeUnix);

        $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expiration = ?, login_attempts = 0, account_locked = NULL WHERE username = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("sss", $resetToken, $expirationTime, $email);
        $updateStmt->execute();

        // Send the password reset email
        $resetLink = "https://".$url."/reset_password.php?token=$resetToken";

        $to = $email;
        $subject = 'Password Reset';
        $message = "To reset your password, click the following link:\n$resetLink";
        $headers = 'From: myvivarium.online@gmail.com';

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

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Add your CSS styles here */
        .container {
            max-width: 600px;
            margin: 0 auto;
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
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Header and Footer Styling */
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

        /* Center-align the result message */
        .result-message {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #dff0d8;
            /* Green background color */
            border: 1px solid #3c763d;
            /* Green border color */
            color: #3c763d;
            /* Green text color */
            border-radius: 5px;
        }

        header {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #343a40;
            /* Dark background color for the header */
            color: white;
            padding: 1rem;
            text-align: center;
        }

        .logo-container {
            /* Light background color for logo container */
            padding: 0;
            /* No padding inside the logo container */
            margin: 0;
            /* No margin around the logo container */
        }

        header img.header-logo {
            width: 250px;
            /* Adjust size as needed */
            height: auto;
            display: block;
            /* Removes any extra space below the image */
            margin: 0;
            /* No margin around the image */
        }

        header h1 {
            margin-left: 15px;
            /* Maintain space between the logo and h1 text */
            margin-bottom: 0;
            font-size: 2.5rem;
            /* Adjust font size as needed */
        }
    </style>
</head>

<body>
    <!-- Header with Lab Name -->
    <header class="bg-dark text-white text-center py-3 d-flex justify-content-center align-items-center">
        <div class="logo-container d-flex justify-content-center align-items-center">
            <img src="images/logo.webp" alt="Logo" class="header-logo">
        </div>
        <h1 class="ml-3 mb-0"><?php echo htmlspecialchars($labName); ?></h1>
    </header>
    <br>
    <br>
    <div class="container">
        <h2>Forgot Password</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
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