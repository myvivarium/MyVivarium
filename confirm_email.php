<?php
require 'dbcon.php'; // Include your database connection file

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $con->prepare("SELECT id FROM users WHERE email_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId);
        $stmt->fetch();

        $updateStmt = $con->prepare("UPDATE users SET email_verified = 1, email_token = NULL WHERE id = ?");
        $updateStmt->bind_param("i", $userId);
        if ($updateStmt->execute()) {
            echo "Email successfully confirmed. You can now <a href='index.php'>login</a>.";
        } else {
            echo "Error confirming email. Please try again.";
        }
        $updateStmt->close();
    } else {
        echo "Invalid or expired token.";
    }
    $stmt->close();
} else {
    echo "No token provided.";
}
$con->close();
?>
