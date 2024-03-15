<?php
session_start();
require 'dbcon.php';

if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    $id = $_GET['id'];

    // Proceed with deletion
    $deleteQuery = "DELETE FROM hc_basic WHERE `cage_id` = ?";
    if ($stmt = mysqli_prepare($con, $deleteQuery)) {
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['message'] = 'Cage ' . $id . ' deleted successfully.';
    } else {
        $_SESSION['message'] = 'Error: Could not prepare statement.';
    }
    
    header("Location: hc_dash.php");
    exit();
} else {
    // Redirect or show an error message if ID or confirmation is missing
    $_SESSION['message'] = 'Deletion was not confirmed or ID parameter is missing.';
    header("Location: hc_dash.php");
    exit();
}
?>
