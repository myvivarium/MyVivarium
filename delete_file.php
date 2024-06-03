<?php

/**
 * File Deletion Script
 *
 * This script handles the deletion of a file from the server and the database based on the file ID passed via a GET request.
 * It starts a session, checks if the file exists, deletes it from the server and database, and then redirects the user
 * to a specified URL.
 *
 * Author: [Your Name]
 * Date: [Date]
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the 'id' parameter is set in the GET request
if (isset($_GET['id'])) {
    $id = $_GET['id']; // Get the file ID from the GET request

    // SQL query to select the file record from the database
    $fileQuery = "SELECT * FROM files WHERE id = '$id'";

    // Execute the query and fetch the file record as an associative array
    $file = $con->query($fileQuery)->fetch_assoc();

    // Get the cage ID from the file record
    $cage_id = $file['cage_id'];

    // Check if the file exists on the server
    if (file_exists($file['file_path'])) {
        unlink($file['file_path']); // Delete the file from the server

        // SQL query to delete the file record from the database
        $delete = "DELETE FROM files WHERE id = '$id'";

        // Execute the delete query
        $con->query($delete);

        // Decode the URL parameter and append the cage ID
        $url = urldecode($_GET['url']) . ".php?id=" . $cage_id;

        // Redirect to the specified URL
        header("Location: $url");
    }
}
