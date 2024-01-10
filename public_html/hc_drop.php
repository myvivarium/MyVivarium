<?php
session_start();
require 'dbcon.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the table with the specified ID
    $deleteQuery = "DELETE FROM hc_basic WHERE `cage_id` = '$id'";
    mysqli_query($con, $deleteQuery);

    $_SESSION['message'] = 'Table deleted successfully.';
    header("Location: hc_dash.php");
    exit();
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: hc_dash.php");
    exit();
}
?>
