<?php

/**
 * Litter Data Submission Script
 *
 * This script handles the submission of litter data to the database. It starts a session, checks if the user is logged in,
 * retrieves and sanitizes form data, and inserts it into the `bc_litter` table. If the data is successfully inserted,
 * it returns a success message; otherwise, it returns an error message.
 *
 * Author: [Your Name]
 * Date: [Date]
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is not logged in, return an error
if (!isset($_SESSION['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Retrieve and sanitize form data
    $cage_id = $_POST['cage_id'];
    $dom = $_POST['dom'];
    $litter_dob = $_POST['litter_dob'] ?: NULL; // Set to NULL if not provided
    $pups_alive = $_POST['pups_alive'];
    $pups_dead = $_POST['pups_dead'];
    $pups_male = $_POST['pups_male'];
    $pups_female = $_POST['pups_female'];
    $remarks = $_POST['remarks'];

    // Prepare the insert query with placeholders
    $query = $con->prepare("INSERT INTO bc_litter (`cage_id`, `dom`, `litter_dob`, `pups_alive`, `pups_dead`, `pups_male`, `pups_female`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters to the query
    $query->bind_param("ssssssss", $cage_id, $dom, $litter_dob, $pups_alive, $pups_dead, $pups_male, $pups_female, $remarks);

    // Execute the statement and check if it was successful
    if ($query->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'New litter data added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add new litter data: ' . $query->error]);
    }

    // Close the prepared statement
    $query->close();
} else {
    // Return an error if the request method is not POST
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
