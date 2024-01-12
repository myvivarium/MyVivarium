<?php
// Include your database connection file
include_once("dbcon.php");

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    $note_id = $_POST['note_id'];

    // Delete the specified sticky note from the database
    $sql = "DELETE FROM nt_data WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $stmt->close();
}
?>