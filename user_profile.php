<?php
session_start();
require 'dbcon.php';
require 'config.php';
require 'vendor/autoload.php'; // Include PHPMailer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Fetch user details from the database
$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $newUsername = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_EMAIL);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);

    // Update user details in the database
    $updateQuery = "UPDATE users SET username = ?, name = ?, position = ? WHERE username = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bind_param("ssss", $newUsername, $name, $position, $username);
    $updateStmt->execute();
    $updateStmt->close();

    // Update the session username if it was changed
    if ($newUsername !== $username) {
        $_SESSION['username'] = $newUsername;
        $username = $newUsername;
    }

    // Refresh user data
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission for password reset
$resultMessage = '';
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
}
require 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Profile</title>
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

        .result-message {
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
        <h2>User Profile</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <select class="form-control" id="position" name="position">
                    <option value="" disabled>Select Position</option>
                    <option value="Principal Investigator" <?php echo ($user['position'] == 'Principal Investigator') ? 'selected' : ''; ?>>Principal Investigator</option>
                    <option value="Research Scientist" <?php echo ($user['position'] == 'Research Scientist') ? 'selected' : ''; ?>>Research Scientist</option>
                    <option value="Postdoctoral Researcher" <?php echo ($user['position'] == 'Postdoctoral Researcher') ? 'selected' : ''; ?>>Postdoctoral Researcher</option>
                    <option value="PhD Student" <?php echo ($user['position'] == 'PhD Student') ? 'selected' : ''; ?>>PhD Student</option>
                    <option value="Masters Student" <?php echo ($user['position'] == 'Masters Student') ? 'selected' : ''; ?>>Masters Student</option>
                    <option value="Undergraduate" <?php echo ($user['position'] == 'Undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                    <option value="Laboratory Technician" <?php echo ($user['position'] == 'Laboratory Technician') ? 'selected' : ''; ?>>Laboratory Technician</option>
                    <option value="Research Associate" <?php echo ($user['position'] == 'Research Associate') ? 'selected' : ''; ?>>Research Associate</option>
                    <option value="Lab Manager" <?php echo ($user['position'] == 'Lab Manager') ? 'selected' : ''; ?>>Lab Manager</option>
                    <option value="Animal Care Technician" <?php echo ($user['position'] == 'Animal Care Technician') ? 'selected' : ''; ?>>Animal Care Technician</option>
                    <option value="Interns and Volunteers" <?php echo ($user['position'] == 'Interns and Volunteers') ? 'selected' : ''; ?>>Interns and Volunteers</option>
                </select>
            </div>
            <div class="form-group">
                <label for="username">Email Address</label>
                <input type="email" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <button type="submit" class="btn1 btn-primary" name="update_profile">Update Profile</button>
        </form>
        <br>
        <br>
        <h2>Request Password Change</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            <button type="submit" class="btn1 btn-warning" name="reset">Request Password Change</button>
        </form>
        <?php if ($resultMessage) { echo "<p class='result-message'>$resultMessage</p>"; } ?>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>
<?php mysqli_close($con); ?>
