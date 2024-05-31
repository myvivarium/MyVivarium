<?php
session_start();
require 'dbcon.php'; // Include the database connection

// Check if both 'id' and 'confirm' parameters are set, and if 'confirm' is 'true'
if (isset($_GET['id'], $_GET['confirm']) && $_GET['confirm'] == 'true') {
    // Sanitize the ID parameter to prevent SQL injection
    $id = mysqli_real_escape_string($con, $_GET['id']);

    // Start a transaction
    mysqli_begin_transaction($con);

    try {
        // Prepare the SQL delete query for bc_basic table
        $deleteQuery = "DELETE FROM bc_basic WHERE `cage_id` = ?";
        if ($stmt = mysqli_prepare($con, $deleteQuery)) {
            // Bind the sanitized ID to the prepared statement
            mysqli_stmt_bind_param($stmt, "s", $id);
            // Execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error executing delete statement for bc_basic table: ' . mysqli_error($con));
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Error preparing delete statement for bc_basic table: ' . mysqli_error($con));
        }

        // Prepare the SQL delete query for bc_litter table
        $deleteLitterQuery = "DELETE FROM bc_litter WHERE `cage_id` = ?";
        if ($stmt = mysqli_prepare($con, $deleteLitterQuery)) {
            // Bind the sanitized ID to the prepared statement
            mysqli_stmt_bind_param($stmt, "s", $id);
            // Execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error executing delete statement for bc_litter table: ' . mysqli_error($con));
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Error preparing delete statement for bc_litter table: ' . mysqli_error($con));
        }

        // Prepare the SQL delete query for files table
        $deleteFilesQuery = "DELETE FROM files WHERE `cage_id` = ?";
        if ($stmt = mysqli_prepare($con, $deleteFilesQuery)) {
            // Bind the sanitized ID to the prepared statement
            mysqli_stmt_bind_param($stmt, "s", $id);
            // Execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error executing delete statement for files table: ' . mysqli_error($con));
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Error preparing delete statement for files table: ' . mysqli_error($con));
        }

        // Commit the transaction
        mysqli_commit($con);

        // Set a success message in the session
        $_SESSION['message'] = 'Cage ' . $id . ' and related data deleted successfully.';
    } catch (Exception $e) {
        // Roll back the transaction
        mysqli_rollback($con);
        // Log the error and set a user-friendly message
        error_log($e->getMessage());
        $_SESSION['message'] = 'Error executing the delete statements.';
    }

    // Redirect to the dashboard page
    header("Location: bc_dash.php");
    exit();
} else {
    // Set an error message if deletion is not confirmed or ID is missing
    $_SESSION['message'] = 'Deletion was not confirmed or ID parameter is missing.';
    // Redirect to the dashboard page
    header("Location: bc_dash.php");
    exit();
}
?>
