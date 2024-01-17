<?php
// Include the database connection file
include_once("dbcon.php");

// Start a new session or resume the existing one
session_start();

// Check if the request is a POST and the 'note_id' field is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    // Retrieve the note ID from the POST data
    $note_id = $_POST['note_id'];

    // SQL statement to delete the sticky note from the database
    $sql = "DELETE FROM nt_data WHERE id = ?";
    // Prepare the SQL statement for execution
    $stmt = $con->prepare($sql);
    // Bind the note ID to the prepared statement
    $stmt->bind_param("i", $note_id);
    // Execute the prepared statement
    $stmt->execute();
    // Close the statement
    $stmt->close();
}
?>
