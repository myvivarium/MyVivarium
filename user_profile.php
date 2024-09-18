<?php
/**
 * User Profile Management Page
 *
 * This script allows logged-in users to update their profile information, including their name, position,
 * and email address. It also provides an option to request a password change. The page fetches user details
 * from the database, displays them in a form, and handles form submissions to update the profile or request a
 * password reset.
 */

// Start the session with secure settings
session_start([
    'cookie_lifetime' => 0,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require 'dbcon.php';         // Database connection
require 'config.php';        // Configuration file for email settings
require 'header.php';        // Include the header file
require 'vendor/autoload.php'; // Include PHPMailer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Fetch user details from the database
$username = $_SESSION['username'];
$query    = "SELECT * FROM users WHERE username = ?";
$stmt     = $con->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

$updateMessage = ''; // Initialize message for profile update

// Function to generate initials from the user's name
function generateInitials($name)
{
    $parts    = explode(" ", $name);
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
    $baseInitials  = substr($initials, 0, 3);
    $uniqueInitials = $baseInitials;
    $suffix        = 1;

    $checkQuery = "SELECT 1 FROM users WHERE initials = ? AND username != ?";
    $stmt       = $con->prepare($checkQuery);

    while (true) {
        $stmt->bind_param("ss", $uniqueInitials, $currentUsername);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            break; // Unique initials found
        }

        $uniqueInitials = $baseInitials . $suffix;
        $suffix++;

        if ($suffix > 99) { // Limit suffix to two digits
            throw new Exception("Unable to generate unique initials. Please try different initials.");
        }

        $stmt->free_result();
    }

    $stmt->close();
    return $uniqueInitials;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    // Validate and sanitize input data
    $newUsername = filter_input(INPUT_POST, 'username', FILTER_VALIDATE_EMAIL);
    $name        = trim($_POST['name']);
    $initials    = strtoupper(trim($_POST['initials']));
    $position    = trim($_POST['position']);

    if (!$newUsername || empty($name) || empty($initials) || empty($position)) {
        $updateMessage = "Please fill in all required fields with valid information.";
    } else {
        // Ensure initials are unique
        try {
            $uniqueInitials = ensureUniqueInitials($con, $initials, $username);
        } catch (Exception $e) {
            $updateMessage  = $e->getMessage();
            $uniqueInitials = $initials;
        }

        // Check if the email address (username) has changed
        $emailChanged = ($newUsername !== $username);

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
                $username             = $newUsername;
                $updateMessage        = "Profile information updated successfully. Please log out and log back in to reflect the changes everywhere.";
            } else {
                $updateMessage = "Profile information updated successfully.";
            }
        } else {
            error_log("Database error: " . $updateStmt->error);
            $updateMessage = "An error occurred while updating your profile. Please try again or contact support.";
        }

        $updateStmt->close();

        // Refresh user data
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();
    }
}

// Handle form submission for password reset
$resultMessage = ''; // Initialize message for password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $email = $username;

    // Check if the email exists in the database
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt  = $con->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Email exists, generate and save a reset token
        $resetToken         = bin2hex(random_bytes(32));
        $expirationTimeUnix = time() + 3600; // 1 hour expiration time
        $expirationTime     = date('Y-m-d H:i:s', $expirationTimeUnix);

        $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expiration = ?, login_attempts = 0, account_locked = NULL WHERE username = ?";
        $updateStmt  = $con->prepare($updateQuery);
        $updateStmt->bind_param("sss", $resetToken, $expirationTime, $email);
        $updateStmt->execute();

        // Send the password reset email
        $resetLink = "https://" . $url . "/reset_password.php";
        $to        = $email;
        $subject   = 'Password Reset';
        $message   = "To reset your password, use the following token:\n\n$resetToken\n\nVisit this link to reset your password:\n$resetLink";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->Port       = SMTP_PORT;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;

            $mail->setFrom(SENDER_EMAIL, SENDER_NAME);
            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
            $resultMessage = "Password reset instructions have been sent to your email address.";
        } catch (Exception $e) {
            error_log("Email error: " . $mail->ErrorInfo);
            $resultMessage = "Email could not be sent. Please try again later.";
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
    <!-- Existing head content -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Profile</title>
    <!-- Include Bootstrap CSS for styling (assuming it's used) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <!-- Additional styles -->
    <style>
        /* Styles as per your existing code */
        .container {
            max-width: 800px;
            margin-top: 50px;
            margin-bottom: 50px;
            padding: 20px;
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
            border-radius: 3px;
            cursor: pointer;
        }

        .result-message,
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
        <h2>User Profile</h2>
        <?php if ($updateMessage) : ?>
            <p class='update-message'><?php echo htmlspecialchars($updateMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-group">
                <label for="initials">Initials <span class="note1">(Your initials will be displayed in Cage Card)</span></label>
                <input type="text" class="form-control" id="initials" name="initials" value="<?php echo htmlspecialchars($user['initials'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="3" required>
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <select class="form-control" id="position" name="position" required>
                    <option value="" disabled>Select Position</option>
                    <?php
                    $positions = [
                        "Principal Investigator",
                        "Research Scientist",
                        "Postdoctoral Researcher",
                        "PhD Student",
                        "Masters Student",
                        "Undergraduate",
                        "Laboratory Technician",
                        "Research Associate",
                        "Lab Manager",
                        "Animal Care Technician",
                        "Interns and Volunteers"
                    ];
                    foreach ($positions as $positionOption) {
                        $selected = ($user['position'] == $positionOption) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($positionOption, ENT_QUOTES, 'UTF-8') . "\" $selected>" . htmlspecialchars($positionOption, ENT_QUOTES, 'UTF-8') . "</option>";
                    }
                    ?>
                </select>
            </div>
            <?php if ($demo !== "yes") : ?>
                <div class="form-group">
                    <label for="username">Email Address</label>
                    <input type="email" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn1 btn-primary" name="update_profile">Update Profile</button>
        </form>
        <p class="note">In order to reflect the changes everywhere, please log out and log back in.</p>

        <h2>Request Password Change</h2>
        <?php if ($resultMessage) : ?>
            <p class='result-message'><?php echo htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn1 btn-warning" name="reset">Request Password Change</button>
        </form>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>
<?php mysqli_close($con); ?>
