<?php
session_start();
require 'dbcon.php'; // Include the database connection

// Check if both 'id' and 'confirm' parameters are set, and if 'confirm' is 'true'
if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    // Sanitize the ID parameter to prevent SQL injection
    $id = mysqli_real_escape_string($con, $_GET['id']);

    // Prepare the SQL delete query
    $deleteQuery = "DELETE FROM hc_basic WHERE `cage_id` = ?";
    if ($stmt = mysqli_prepare($con, $deleteQuery)) {
        // Bind the sanitized ID to the prepared statement
        mysqli_stmt_bind_param($stmt, "s", $id);
        // Execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            // Set a success message in the session
            $_SESSION['message'] = 'Cage ' . $id . ' deleted successfully.';
        } else {
            // Log the error and set a user-friendly message
            error_log('Error executing delete statement: ' . mysqli_error($con));
            $_SESSION['message'] = 'Error executing the delete statement.';
        }
        // Close the prepared statement
        mysqli_stmt_close($stmt);
    } else {
        // Log the error and set a user-friendly message
        error_log('Error preparing delete statement: ' . mysqli_error($con));
        $_SESSION['message'] = 'Error preparing the delete statement.';
    }
    // Redirect to the dashboard page
    header("Location: hc_dash.php");
    exit();
} else {
    // Set an error message if deletion is not confirmed or ID is missing
    $_SESSION['message'] = 'Deletion was not confirmed or ID parameter is missing.';
    // Redirect to the dashboard page
    header("Location: hc_dash.php");
    exit();
}
?>
