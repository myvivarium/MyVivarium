<?php

/**
 * File Deletion Script
 *
 * This script handles the deletion of a file from the server and the database based on the file ID passed via a GET request.
 * It starts a session, checks if the file exists, deletes it from the server and database, and then redirects the user
 * to a specified URL.
 *
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

// Check if the 'id' parameter is set in the GET request
if (isset($_GET['id'])) {
    $id = $_GET['id']; // Get the file ID from the GET request

    // SQL query to select the file record from the database
    $fileQuery = "SELECT * FROM files WHERE id = ?";

    // Prepare the statement to prevent SQL injection
    $stmt = $con->prepare($fileQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    // Check if a file record was found
    if ($file) {
        // Get the cage ID from the file record
        $cage_id = $file['cage_id'];

        // Check if the file exists on the server
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']); // Delete the file from the server
        }

        // SQL query to delete the file record from the database
        $deleteQuery = "DELETE FROM files WHERE id = ?";

        // Prepare the statement to prevent SQL injection
        $deleteStmt = $con->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();

        // Decode the URL parameter and append the cage ID
        $url = urldecode($_GET['url']) . ".php?id=" . $cage_id;

        // Redirect to the specified URL
        header("Location: $url");
        exit();
    } else {
        // Handle the case where the file record was not found
        echo "File record not found.";
    }
} else {
    // Handle the case where the 'id' parameter is not set in the GET request
    echo "File ID not specified.";
}
