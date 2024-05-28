<?php
// Include your database connection file
include_once("dbcon.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start or resume the session
}

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    die('You must be logged in to add a note.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_text'])) {
    $note_text = $_POST['note_text'];
    $user_id = $_SESSION['username']; // Assuming 'username' is the user's identifier
    $cage = isset($_POST['cage_id']) ? $_POST['cage_id'] : null;

    // Prepare the SQL statement
    $sql = "INSERT INTO nt_data (user_id, note_text, cage_id) VALUES (?, ?, ?)";
    $stmt = $con->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($con->error));
    }

    // Bind parameters
    $stmt->bind_param("sss", $user_id, $note_text, $cage);

    // Execute the statement
    if ($stmt->execute()) {
        echo 'Note added successfully.';
    } else {
        echo 'Execute failed: ' . htmlspecialchars($stmt->error);
    }

    // Close the statement
    $stmt->close();
} else {
    echo 'Invalid request.';
}

// Close the database connection
$con->close();
?>
