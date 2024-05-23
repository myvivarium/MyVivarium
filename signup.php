<?php
session_start();  // Start or resume a session
require 'dbcon.php'; // Include your database connection file

// Query to fetch the lab name
$labQuery = "SELECT lab_name FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);
$labName = "My Vivarium"; // A default value in case the query fails or returns no result

if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['honeypot'])) {
        $_SESSION['resultMessage'] = "Spam detected! Please try again.";
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
            $_SESSION['resultMessage'] = "Email address already registered. Please try logging in or use a different email.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $con->prepare("INSERT INTO users (name, username, position, role, password, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $username, $position, $role, $hashedPassword, $status);

            if ($stmt->execute()) {
                $_SESSION['resultMessage'] = "Registration successful. After approval, you can <a href='index.php'>login</a> with your new account.";
            } else {
                $_SESSION['resultMessage'] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $checkEmailStmt->close();
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
    <title>Sign Up | <?php echo htmlspecialchars($labName); ?></title>

        <!-- Standard favicon -->
        <link rel="icon" href="/icons/favicon.ico" type="image/x-icon">
    
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    
    <!-- Favicon for different sizes -->
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    
    <!-- Android Chrome Icons -->
    <link rel="icon" sizes="192x192" href="/icons/android-chrome-192x192.png">
    <link rel="icon" sizes="512x512" href="/icons/android-chrome-512x512.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="/icons/site.webmanifest">

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
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

        /* Ensure the header, image, and h1 have the correct styles */
        header {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            background-color: #343a40; /* Dark background color for the header */
            color: white;
            padding: 1rem;
            text-align: center;
        }

        .logo-container {
            padding: 0; /* No padding inside the logo container */
            margin: 0; /* No margin around the logo container */
        }

        header img.header-logo {
            width: 300px; /* Adjust size as needed */
            height: auto;
            display: block; /* Removes any extra space below the image */
            margin: 0; /* No margin around the image */
        }

        header h1 {
            margin-left: 15px; /* Maintain space between the logo and h1 text */
            margin-bottom: 0;
            margin-top: 12px;
            font-size: 3.5rem; /* Adjust font size as needed */
            white-space: nowrap; /* Prevents wrapping of text */
            font-family: 'Poppins', sans-serif; /* Apply Google Font Poppins */
            font-weight: 500;
        }

        @media (max-width: 576px) {
            header h1 {
                font-size: 2.2rem; /* Adjust font size for smaller screens */
                margin-left: 10px; /* Adjust margin for smaller screens */
            }

            header img.header-logo {
                width: 150px; /* Adjust logo size for smaller screens */
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
    <!-- Header with Lab Name -->
    <header class="bg-dark text-white text-center py-3 d-flex flex-wrap justify-content-center align-items-center">
        <div class="logo-container d-flex justify-content-center align-items-center">
            <img src="images/logo1.jpg" alt="Logo" class="header-logo">
        </div>
        <h1 class="ml-3 mb-0"><?php echo htmlspecialchars($labName); ?></h1>
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