<?php
session_start();
require 'dbcon.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the table with the specified ID
    $deleteQuery = "DELETE FROM holdingcage WHERE `cage id` = '$id'";
    mysqli_query($con, $deleteQuery);

    $_SESSION['message'] = 'Table deleted successfully.';
    header('Location: home.php');
    exit();
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header('Location: home.php');
    exit();
}
?>
