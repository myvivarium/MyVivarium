<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, return an error
if (!isset($_SESSION['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $cage_id = $_POST['cage_id'];
    $dom = $_POST['dom'];
    $litter_dob = $_POST['litter_dob'] ?: NULL;
    $pups_alive = $_POST['pups_alive'];
    $pups_dead = $_POST['pups_dead'];
    $pups_male = $_POST['pups_male'];
    $pups_female = $_POST['pups_female'];
    $remarks = $_POST['remarks'];

    // Prepare the insert query with placeholders
    $query = $con->prepare("INSERT INTO bc_litter (`cage_id`, `dom`, `litter_dob`, `pups_alive`, `pups_dead`, `pups_male`, `pups_female`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters
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
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
