<?php
session_start(); // Start a new session
require 'dbcon.php'; // Include the database connection file

// Query to fetch the lab name
$labQuery = "SELECT lab_name FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

$labName = "My Vivarium"; // A default value in case the query fails or returns no result
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
}

$resultMessage = "";
$updateStmt = null; // Initialize $updateStmt for later use

// Check if the form is submitted via POST and the reset button was clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if new password and confirm password are the same
    if ($newPassword === $confirmPassword) {
        // Prepare SQL to check if the token exists and is valid
        $query = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiration >= NOW()";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the token is valid
        if ($result->num_rows == 1) {
            // Fetch user data
            $row = $result->fetch_assoc();
            $username = $row['username'];

            // Prepare SQL to update the user's password
            $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiration = NULL WHERE username = ?";
            $updateStmt = $con->prepare($updateQuery);
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateStmt->bind_param("ss", $hashedPassword, $username);

            // Execute the update and set a success or failure message
            if ($updateStmt->execute()) {
                $resultMessage = "Password reset successfully. You can now <a href='index.php'>login</a> with your new password.";
            } else {
                $resultMessage = "Password reset failed. Please try again.";
            }
        } else {
            $resultMessage = "Invalid or expired token. Please request a new password reset.";
        }

        // Close the statement
        $stmt->close();
        if (isset($updateStmt)) {
            $updateStmt->close();
        }
    } else {
        $resultMessage = "New Password and Confirm Password do not match.";
    }

    // Close the database connection
    $con->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <!-- Page Metadata -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?php echo htmlspecialchars($labName); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* CSS styles for layout and appearance */
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

        /* Ensure the header, image, and h1 have the correct styles */
        header {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            background-color: #343a40;
            /* Dark background color for the header */
            color: white;
            padding: 1rem;
            text-align: center;
        }

        .logo-container {
            padding: 0;
            /* No padding inside the logo container */
            margin: 0;
            /* No margin around the logo container */
        }

        header img.header-logo {
            width: 300px;
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
            margin-top: 12px;
            font-size: 3.5rem;
            /* Adjust font size as needed */
            white-space: nowrap;
            /* Prevents wrapping of text */
            font-family: 'Poppins', sans-serif;
            /* Apply Google Font Poppins */
            font-weight: 500;
        }

        @media (max-width: 576px) {
            header h1 {
                font-size: 2.2rem;
                /* Adjust font size for smaller screens */
                margin-left: 10px;
                /* Adjust margin for smaller screens */
            }

            header img.header-logo {
                width: 150px;
                /* Adjust logo size for smaller screens */
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header class="bg-dark text-white text-center py-3 d-flex flex-wrap justify-content-center align-items-center">
        <div class="logo-container d-flex justify-content-center align-items-center">
            <img src="images/logo1.jpg" alt="Logo" class="header-logo">
        </div>
        <h1 class="ml-3 mb-0"><?php echo htmlspecialchars($labName); ?></h1>
    </header>
    <br>
    <br>
    <div class="container">
        <h2>Reset Password</h2>
        <form method="POST" action="">
            <!-- Hidden field for the token -->
            <input type="hidden" id="token" name="token" value="<?= htmlspecialchars($_GET['token']); ?>">

            <!-- New Password Field -->
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary" name="reset">Reset Password</button>
        </form>

        <!-- Display Result Message -->
        <?php if (!empty($resultMessage)) {
            echo "<p class='result-message'>$resultMessage</p>";
        } ?>
        <br>
        <a href="index.php" class="btn btn-secondary">Go Back</a>
    </div>

    <!-- Footer Section -->
    <br>
    <?php include 'footer.php'; ?>

</body>

</html>