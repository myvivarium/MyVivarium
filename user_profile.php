<?php

/**
 * User Profile Management Page
 *
 * This script allows logged-in users to update their profile information, including their name, position,
 * and email address. It also provides an option to request a password change. The page fetches user details
 * from the database, displays them in a form, and handles form submissions to update the profile or request a
 * password reset.
 * 
 */

session_start();
require 'dbcon.php'; // Database connection
require 'config.php'; // Configuration file for email settings
require 'header.php'; // Include the header file
require 'vendor/autoload.php'; // Include PHPMailer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

$updateMessage = ''; // Initialize message for profile update

// Function to generate initials from the user's name
function generateInitials($name)
{
    $parts = explode(" ", $name);
    $initials = "";

    foreach ($parts as $part) {
        if (!empty($part) && ctype_alpha($part[0])) {
            $initials .= strtoupper($part[0]);
        }
    }

    return substr($initials, 0, 3); // Return up to 3 characters
}

// Function to ensure unique initials
function ensureUniqueInitials($con, $initials, $currentUsername)
{
    $uniqueInitials = substr($initials, 0, 3); // Limit initials to a maximum of 3 characters
    $suffix = 1;
    $maxLength = 10; // Define the maximum length for initials including suffix

    $checkQuery = "SELECT initials FROM users WHERE initials = ? AND username != ?";
    $stmt = $con->prepare($checkQuery);

    if (!$stmt) {
        error_log("Failed to prepare statement: " . $con->error);
        return $initials; // Return the original initials if statement preparation fails
    }

    do {
        $stmt->bind_param("ss", $uniqueInitials, $currentUsername);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $uniqueInitials = substr($initials, 0, 3) . $suffix; // Ensure initials part is still limited to 3 characters
            $suffix++;
        } else {
            break;
        }

        $stmt->free_result(); // Clear the result set for the next iteration

    } while (strlen($uniqueInitials) <= $maxLength);

    $stmt->close();

    if (strlen($uniqueInitials) > $maxLength) {
        $uniqueInitials = substr($uniqueInitials, 0, $maxLength);
    }

    return $uniqueInitials;
}

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $newUsername = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_EMAIL);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $initials = filter_input(INPUT_POST, 'initials', FILTER_SANITIZE_STRING);
    $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);

    // Check if the email address (username) has changed
    $emailChanged = ($newUsername !== $username);

    // Ensure initials are unique
    $uniqueInitials = ensureUniqueInitials($con, $initials, $username);

    if ($uniqueInitials !== $initials) {
        $updateMessage = "The initials '$initials' are already in use by another user. Please choose different initials.";
    } else {
        // Update user details in the database
        $updateQuery = "UPDATE users SET username = ?, name = ?, position = ?, initials = ?";
        if ($emailChanged) {
            $updateQuery .= ", email_verified = 0";
        }
        $updateQuery .= " WHERE username = ?";
        $updateStmt = $con->prepare($updateQuery);

        if ($emailChanged) {
            $updateStmt->bind_param("sssss", $newUsername, $name, $position, $uniqueInitials, $username);
        } else {
            $updateStmt->bind_param("sssss", $newUsername, $name, $position, $uniqueInitials, $username);
        }

        if ($updateStmt->execute()) {
            // Update the session username if it was changed
            if ($emailChanged) {
                $_SESSION['username'] = $newUsername;
                $username = $newUsername;
                $updateMessage = "Profile information updated successfully. Please log out and log back in to reflect the changes everywhere.";
            } else {
                $updateMessage = "Profile information updated successfully.";
            }
        } else {
            $updateMessage = "An error occurred while updating the profile. Please try again.";
        }

        $updateStmt->close();

        // Refresh user data
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
}

// Handle form submission for password reset
$resultMessage = ''; // Initialize message for password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $email = $username;

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

        .btn1:hover {
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

        .update-message {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #dff0d8;
            border: 1px solid #3c763d;
            color: #3c763d;
            border-radius: 5px;
        }

        .note {
            font-size: 0.9em;
            color: #555;
            text-align: center;
            margin-top: 10px;
        }

        .note1 {
            color: #888;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container content">
        <br>
        <h2>User Profile</h2>
        <br>
        <br>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <br>
            <div class="form-group">
                <label for="initials">Initials <span class="note1">(Your Initials will be displayed in Cage Card)</span></label>
                <input type="text" class="form-control" id="initials" name="initials" value="<?php echo htmlspecialchars($user['initials']); ?>" maxlength="3" required>
            </div>
            <br>
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
            <br>
            <?php if ($demo !== "yes") : ?>
                <div class="form-group">
                    <label for="username">Email Address</label>
                    <input type="email" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
            <?php endif; ?>
            <br>
            <button type="submit" class="btn1 btn-primary" name="update_profile">Update Profile</button>
        </form>
        <br>
        <?php if ($updateMessage) {
            echo "<p class='update-message'>$updateMessage</p>";
        } ?>
        <br>
        <br>
        <br>
        <br>
        <h2>Request Password Change</h2>
        <br>
        <br>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" class="btn1 btn-warning" name="reset">Request Password Change</button>
        </form>
        <?php if ($resultMessage) {
            echo "<p class='result-message'>$resultMessage</p>";
        } ?>
        <br>
        <p class="note">In order to reflect the changes everywhere, please log out and log back in.</p>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>
<?php mysqli_close($con); ?>