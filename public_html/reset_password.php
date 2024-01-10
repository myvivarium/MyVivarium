<?php
$resultMessage = "";
$updateStmt = null; // Initialize $updateStmt

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    require 'dbcon.php';

    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];

    // Check if the token exists and is valid
    $query = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiration >= NOW()";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Token is valid, update the password
        $row = $result->fetch_assoc();
        $username = $row['username'];

        $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiration = NULL WHERE username = ?";
        $updateStmt = $con->prepare($updateQuery);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt->bind_param("ss", $hashedPassword, $username);

        if ($updateStmt->execute()) {
            $resultMessage = "Password reset successfully. You can now <a href='index.php'>login</a> with your new password.";
        } else {
            $resultMessage = "Password reset failed. Please try again.";
        }
    } else {
        $resultMessage = "Invalid or expired token. Please request a new password reset.";
    }

    $stmt->close();
    
    // Check if $updateStmt is defined before closing
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
    <title>Reset Password</title>

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
            background-color: #dff0d8; /* Green background color */
            border: 1px solid #3c763d; /* Green border color */
            color: #3c763d; /* Green text color */
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Header with Lab Name -->
    <header class="bg-dark text-white text-center py-3">
        <h1>Sathyanesan Lab's Vivarium</h1>
    </header>
    <br>
    <br>
    <div class="container">
        <h2>Reset Password</h2>
        <form method="POST" action="">
            <div class="form-group">
                <input type="text" class="form-control" id="token" name="token" value="<?= $_GET['token']; ?>" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <button type="submit" class="btn btn-primary" name="reset">Reset Password</button>
        </form>
        <?php if (!empty($resultMessage)) { echo "<p class='result-message'>$resultMessage</p>"; } ?>
        <br>
        <a href="index.php" class="btn btn-secondary">Go Back</a>
    </div>
    <br>
    <br>
    <!-- Footer Section -->
    <div class="header-footer">
        <p style="color:white">&copy; 2024 MyVivarium.online. All rights reserved.</p>
    </div>
</body>
