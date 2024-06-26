<?php

/**
 * Delete Note Script
 * 
 * This script handles the deletion of a note from the database.
 * It expects a POST request with the note ID. The note can only be deleted by the user who created it.
 * The response is returned as JSON.
 * 
 */

// Include the database connection file
include_once("dbcon.php");

// Start or resume the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to delete a note.']);
    exit;
}

// Initialize response array
$response = ['success' => false, 'message' => 'Invalid request.'];

// Check if the request is a POST and the 'note_id' field is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    // Retrieve the note ID from the POST data
    $note_id = $_POST['note_id'];
    $user_id = $_SESSION['username']; // Assuming 'username' is the user's identifier

    // Prepare the SQL statement
    $sql = "DELETE FROM nt_data WHERE id = ? AND user_id = ?";
    $stmt = $con->prepare($sql);
    if ($stmt === false) {
        $response['message'] = 'Prepare failed: ' . htmlspecialchars($con->error);
        echo json_encode($response);
        exit;
    }

    // Bind parameters
    $stmt->bind_param("is", $note_id, $user_id);

    // Execute the statement
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Note deleted successfully.';
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
