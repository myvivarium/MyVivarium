<?php
// Include your database connection file
include_once("dbcon.php");

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_text'])) {
    $note_text = $_POST['note_text'];
    $user_id = $_SESSION['username'];
    $cage = $_POST['cage_id'];
    echo $user_id;
    $sql = "INSERT INTO nt_data (user_id, note_text, cage_id) VALUES (?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sss", $user_id, $note_text, $cage);
    $stmt->execute();
    $stmt->close();
}
?>