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
$response = ['success' => false, 'message' => 'Invalid request.'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_text'])) {
    $note_text = $_POST['note_text'];
    $user_id = $_SESSION['username']; // Assuming 'username' is the user's identifier
    $cage = isset($_POST['cage_id']) ? $_POST['cage_id'] : null;

    // Prepare the SQL statement
    $sql = "INSERT INTO nt_data (user_id, note_text, cage_id) VALUES (?, ?, ?)";
    $stmt = $con->prepare($sql);
    if ($stmt === false) {
        $response['message'] = 'Prepare failed: ' . htmlspecialchars($con->error);
        echo json_encode($response);
        exit;
    }

    // Bind parameters
    $stmt->bind_param("sss", $user_id, $note_text, $cage);

    // Execute the statement
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Note added successfully.';
    } else {
        $response['message'] = 'Execute failed: ' . htmlspecialchars($stmt->error);
    }

    // Close the statement
    $stmt->close();
}

// Close the database connection
$con->close();

// Return the response as JSON
echo json_encode($response);
?>
