<?php
session_start();
require 'dbcon.php';

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
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_EMAIL);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

    $updateQuery = "UPDATE users SET name = ? WHERE username = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bind_param("sss", $username, $name);
    $updateStmt->execute();
    $updateStmt->close();

    // Refresh user data
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Include header
require 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 600px;
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
    </style>
</head>

<body>
    <div class="container">
        <h2>User Profile</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" name="update_profile">Update Profile</button>
        </form>
        <br>
        <h2>Request Password Change</h2>
        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <label for="reset_email">Email Address</label>
                <input type="email" class="form-control" id="reset_email" name="email" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <button type="submit" class="btn btn-warning" name="reset">Request Password Change</button>
        </form>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>
<?php mysqli_close($con); ?>