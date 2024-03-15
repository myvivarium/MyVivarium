<?php
session_start();
require 'dbcon.php';

// Check both ID and confirmation flag
if (isset($_GET['id'], $_GET['confirm']) && $_GET['confirm'] == 'true') {
    $id = mysqli_real_escape_string($con, $_GET['id']); // Sanitize ID to prevent SQL injection

    // Delete the table with the specified ID
    $deleteQuery = "DELETE FROM bc_basic WHERE `cage_id` = ?";
    if ($stmt = mysqli_prepare($con, $deleteQuery)) {
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['message'] = 'Cage ' . $id . ' deleted successfully.';
    } else {
        $_SESSION['message'] = 'Error preparing the delete statement.';
    }
    header("Location: bc_dash.php");
    exit();
} else {
    // No confirmation or ID missing
    $_SESSION['message'] = 'Deletion was not confirmed or ID parameter is missing.';
    header("Location: bc_dash.php");
    exit();
}
?>
