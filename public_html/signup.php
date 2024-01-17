<?php
$resultMessage = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $name = $_POST['name'];
    $username = $_POST['email'];
    $password = $_POST['password'];
    $position = $_POST['position'];
    $role = "user";
    $status = "pending";

    // Hash the password using bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Database connection
    require 'dbcon.php';

    // Prepare SQL statement
    $stmt = $con->prepare("INSERT INTO users (name, username, position, role, password, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $username, $position, $role, $hashedPassword, $status);

    // Execute statement and set result message
    if ($stmt->execute()) {
        $resultMessage = "Registration successful. After approval you can <a href='index.php'>login</a> with your new account.";
    } else {
        $resultMessage = "Registration failed. Please try again.";
    }

    // Close statement and connection
    $stmt->close();
    $con->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Styling for the container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        /* Form styling */
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

        .note {
            color: #888;
            font-size: 12px;
        }

        /* Styling for header and footer */
        .header-footer {
            background-color: #343a40;
            padding: 20px 0;
            text-align: center;
            color: #fff;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header class="bg-dark text-white text-center py-3">
        <h1>Sathyanesan Lab's Vivarium</h1>
    </header>
    <br>
    <!-- Main Content -->
    <div class="container">

        <h2>Sign Up</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <!-- Name Field -->
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <!-- Position Selector -->
            <div class="form-group">
                <label for="position">Position</label>
                <select class="form-control" id="position" name="position">
                    <!-- Position options -->
                </select>
            </div>

            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Email Address <span class="note">(Your email address will be your username for
                        login)</span></label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <!-- Submit and Back Buttons -->
            <button type="submit" class="btn btn-primary" name="signup">Sign Up</button>
            <br>
            <a href="index.php" class="btn btn-secondary">Go Back</a>
        </form>
        <!-- Result Message Display -->
        <?php if (!empty($resultMessage)) {
            echo "<p class='result-message'>$resultMessage</p>";
        } ?>
    </div>

    <!-- Footer Section -->
    <br>
    <?php include 'footer.php'; ?>

</body>

</html>