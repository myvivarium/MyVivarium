<?php

/**
 * Holding Cage Deletion Script
 * 
 * This script handles the deletion of a cage and its related files and mouse data from the database. 
 * It uses prepared statements to prevent SQL injection and transactions to ensure data integrity.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit; // Exit to ensure no further code is executed
}

// Check if both 'id' and 'confirm' parameters are set, and if 'confirm' is 'true'
if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    // Sanitize the ID parameter to prevent SQL injection
    $id = mysqli_real_escape_string($con, $_GET['id']);

    // Start a transaction
    mysqli_begin_transaction($con);

    // Fetch the logged-in user's ID and role from the session
    $currentUserId = $_SESSION['user_id']; // Assuming user ID is stored in session
    $userRole = $_SESSION['role']; // Assuming user role is stored in session

    // Fetch the cage record to check for user assignment
    $cageQuery = "SELECT `user` FROM hc_basic WHERE `cage_id` = ?";
    if ($stmt = mysqli_prepare($con, $cageQuery)) {
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cage = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$cage) {
            $_SESSION['message'] = 'Cage not found.';
            header("Location: hc_dash.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Error retrieving cage data.';
        header("Location: hc_dash.php");
        exit();
    }

    // Check if the user is either an admin or assigned to the cage
    $cageUsers = explode(',', $cage['user']); // Array of user IDs associated with the cage
    if ($userRole !== 'admin' && !in_array($currentUserId, $cageUsers)) {
        $_SESSION['message'] = 'Access denied. Only the assigned user or an admin can delete this cage.';
        header("Location: hc_dash.php");
        exit();
    }

    try {
        // Prepare the SQL delete query for the hc_basic table
        $deleteQuery = "DELETE FROM hc_basic WHERE `cage_id` = ?";
        if ($stmt = mysqli_prepare($con, $deleteQuery)) {
            // Bind the sanitized ID to the prepared statement
            mysqli_stmt_bind_param($stmt, "s", $id);
            // Execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error executing delete statement for hc_basic table: ' . mysqli_error($con));
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Error preparing delete statement for hc_basic table: ' . mysqli_error($con));
        }

        // Prepare the SQL delete query for the files table
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

        // Prepare the SQL delete query for the mouse table
        $deleteMouseQuery = "DELETE FROM mouse WHERE `cage_id` = ?";
        if ($stmt = mysqli_prepare($con, $deleteMouseQuery)) {
            // Bind the sanitized ID to the prepared statement
            mysqli_stmt_bind_param($stmt, "s", $id);
            // Execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error executing delete statement for mouse table: ' . mysqli_error($con));
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Error preparing delete statement for mouse table: ' . mysqli_error($con));
        }

        // Commit the transaction to save the changes
        mysqli_commit($con);

        // Set a success message in the session
        $_SESSION['message'] = 'Cage ' . $id . ', related files and mouse data were deleted successfully.';
    } catch (Exception $e) {
        // Roll back the transaction in case of an error
        mysqli_rollback($con);
        // Log the error and set a user-friendly message
        $_SESSION['message'] = 'Error executing the delete statements. ' . $e->getMessage();
    }

    // Redirect to the dashboard page
    header("Location: hc_dash.php");
    exit();
} else {
    // Ask for confirmation if 'confirm' is not set or not 'true'
    if (isset($_GET['id']) && !isset($_GET['confirm'])) {
        $id = htmlspecialchars($_GET['id']); // Sanitize the ID for display

        echo "<script>
            if (confirm('Are you sure you want to delete cage $id and all related mouse data?')) {
                window.location.href = 'hc_delete.php?id=$id&confirm=true';
            } else {
                window.location.href = 'hc_dash.php';
            }
        </script>";
        exit();
    }

    // Set an error message if deletion is not confirmed or ID is missing
    $_SESSION['message'] = 'Deletion was not confirmed or ID parameter is missing.';
    // Redirect to the dashboard page
    header("Location: hc_dash.php");
    exit();
}
