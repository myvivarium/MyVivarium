<?php
// Include the database connection file
include_once("dbcon.php");

// Start a new session or resume the existing one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the request is a POST and the 'note_id' field is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    // Retrieve the note ID from the POST data
    $note_id = $_POST['note_id'];

    // SQL statement to delete the sticky note from the database
    $sql = "DELETE FROM nt_data WHERE id = ? AND user_id = ?";
    // Prepare the SQL statement for execution
    $stmt = $con->prepare($sql);

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . htmlspecialchars($con->error)]);
        exit;
    }

    // Bind the note ID and user ID to the prepared statement
    $stmt->bind_param("is", $note_id, $_SESSION['username']);

    // Execute the prepared statement
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Note deleted successfully.';
        echo json_encode(['success' => true, 'message' => 'Note deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete note: ' . htmlspecialchars($stmt->error)]);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

// Close the database connection
$con->close();
?>
