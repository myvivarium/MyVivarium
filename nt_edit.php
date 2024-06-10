<?php

/**
 * Edit Note Script
 * 
 * This script handles the updating of a note in the database.
 * It expects a POST request with the note ID and the updated note text.
 * The response is returned as JSON.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

 // Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to edit a note.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'dbcon.php'; // Include the database connection file

    // Retrieve the note ID and updated note text from the POST request
    $noteId = $_POST['note_id'];
    $noteText = $_POST['note_text'];

    // Prepare the SQL statement to update the note in the database
    $sql = "UPDATE nt_data SET note_text = ? WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('si', $noteText, $noteId);

    // Execute the statement and return the response as JSON
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Note updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update note.']);
    }

    // Close the statement and database connection
    $stmt->close();
    $con->close();
}
