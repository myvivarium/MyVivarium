<?php
session_start();
require 'dbcon.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the table with the specified ID
    $deleteQuery = "DELETE FROM bc_basic WHERE `cage_id` = '$id'";
    mysqli_query($con, $deleteQuery);

    $_SESSION['message'] = 'Cage '.$id.' deleted successfully.';
    header("Location: bc_dash.php");
    exit();
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php");
    exit();
}
?>
