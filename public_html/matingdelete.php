<?php
session_start();
require 'dbcon.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the table with the specified ID
    $deleteQuery = "DELETE FROM matingcage WHERE `cage id` = '$id'";
    mysqli_query($con, $deleteQuery);

    $_SESSION['message'] = 'Table deleted successfully.';
    header('Location: mating.php');
    exit();
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header('Location: mating.php');
    exit();
}
?>
