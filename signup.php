<?php
$resultMessage = "";

require 'dbcon.php'; // Include your database connection file

// Query to fetch the lab name
$labQuery = "SELECT lab_name FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

$labName = "My Vivarium"; // A default value in case the query fails or returns no result
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the honeypot field is filled
    if (!empty($_POST['honeypot'])) {
        $resultMessage = "Spam detected! Please try again.";
    } else {
        $name = $_POST['name'];
        $username = $_POST['email'];
        $password = $_POST['password'];
        $position = $_POST['position'];
        $role = "user";
        $status = "pending";

        // Check if the email already exists
        $checkEmailQuery = "SELECT username FROM users WHERE username = ?";
        $checkEmailStmt = $con->prepare($checkEmailQuery);
        $checkEmailStmt->bind_param("s", $username);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();

        if ($checkEmailStmt->num_rows > 0) {
            $resultMessage = "Email address already registered. Please try logging in or use a different email.";
        } else {
            // Hash the password using bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $con->prepare("INSERT INTO users (name, username, position, role, password, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $username, $position, $role, $hashedPassword, $status);

            if ($stmt->execute()) {
                $resultMessage = "Registration successful. After approval, you can <a href='index.php'>login</a> with your new account.";
            } else {
                $resultMessage = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $checkEmailStmt->close();
    }
    $con->close(); // Close the database connection
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | <?php echo htmlspecialchars($labName); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
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

        .note {
            color: #888;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <header class="bg-dark text-white text-center py-3">
        <h1><?php echo htmlspecialchars($labName); ?></h1>
    </header>
    <br>
    <div class="container">
        <h2>Sign Up</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
                <select class="form-control" id="position" name="position">
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
            <button type="submit" class="btn btn-primary" name="signup">Sign Up</button>
            <br>
            <a href="index.php" class="btn btn-secondary">Go Back</a>
        </form>
        <br>
        
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