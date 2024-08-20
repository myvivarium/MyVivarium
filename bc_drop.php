<?php

/**
 * Breeding Cage Deletion Script
 *
 * This script handles the deletion of a cage and its related data from the database. It starts a session,
 * checks if the required 'id' and 'confirm' parameters are set, sanitizes the ID parameter, and executes
 * delete queries in a transaction to ensure data integrity. If the deletion is successful, it commits the transaction
 * and redirects the user to the dashboard with a success message. If any errors occur, the transaction is rolled back,
 * and an error message is set.
 *
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit; // Exit to ensure no further code is executed
}

// Check if both 'id' and 'confirm' parameters are set, and if 'confirm' is 'true'
if (isset($_GET['id'], $_GET['confirm']) && $_GET['confirm'] == 'true') {
    // Sanitize the ID parameter to prevent SQL injection
    $id = mysqli_real_escape_string($con, $_GET['id']);

    // Start a transaction
    mysqli_begin_transaction($con);

    // Fetch the logged-in user's ID and role from the session
    $currentUserId = $_SESSION['user_id']; // Assuming user ID is stored in session
    $userRole = $_SESSION['role']; // Assuming user role is stored in session

    // Fetch the cage record to check for user assignment
    $cageQuery = "SELECT c.pi_name, cu.user_id FROM cages c LEFT JOIN cage_users cu ON c.cage_id = cu.cage_id WHERE c.cage_id = ?";
    if ($stmt = mysqli_prepare($con, $cageQuery)) {
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cage = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$cage) {
            $_SESSION['message'] = 'Cage not found.';
            header("Location: bc_dash.php");
            exit();
        }

        $cageUsers = [];
        do {
            if ($cage['user_id']) {
                $cageUsers[] = $cage['user_id'];
            }
        } while ($cage = mysqli_fetch_assoc($result));
    } else {
        $_SESSION['message'] = 'Error retrieving cage data.';
        header("Location: bc_dash.php");
        exit();
    }

    // Check if the user is either an admin or assigned to the cage
    if ($userRole !== 'admin' && !in_array($currentUserId, $cageUsers)) {
        $_SESSION['message'] = 'Access denied. Only the assigned user or an admin can delete this cage.';
        header("Location: bc_dash.php");
        exit();
    }

    try {
        // Delete records from all related tables
        $tables = [
            'breeding' => 'cage_id',
            'litters' => 'cage_id',
            'files' => 'cage_id',
            'notes' => 'cage_id',
            'cage_iacuc' => 'cage_id',
            'cage_users' => 'cage_id',
            'tasks' => 'cage_id',
            'maintenance' => 'cage_id',
            'cages' => 'cage_id'
        ];

        foreach ($tables as $table => $column) {
            $deleteQuery = "DELETE FROM $table WHERE $column = ?";
            if ($stmt = mysqli_prepare($con, $deleteQuery)) {
                mysqli_stmt_bind_param($stmt, "s", $id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error executing delete statement for $table table: " . mysqli_error($con));
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception("Error preparing delete statement for $table table: " . mysqli_error($con));
            }
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
