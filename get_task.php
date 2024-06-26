<?php
/**
 * Task Detail Fetcher
 * 
 * This script fetches the details of a specific task from the database based on the provided task ID.
 * It first checks if the user is logged in, then retrieves the task details from the database, and finally
 * returns the task information as a JSON response.
 * 
 */

// Start the session to use session variables
session_start();

// Include database connection
require 'dbcon.php';

// Initialize response array
$response = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    // Validate and sanitize the task ID
    $taskId = intval($_GET['id']);

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $con->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $taskId);

    if ($stmt->execute()) {
        // Execute the query and get the result
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch task data if a matching task is found
            $task = $result->fetch_assoc();

            // Populate the response array with task data
            $response = [
                'id' => $task['id'],
                'title' => $task['title'],
                'description' => $task['description'],
                'assigned_by' => $task['assigned_by'],
                'assigned_to' => $task['assigned_to'],
                'status' => $task['status'],
                'completion_date' => $task['completion_date'],
                'creation_date' => $task['creation_date'],
                'cage_id' => $task['cage_id']
            ];
        } else {
            // Set error response if no matching task is found
            $response = ['error' => 'Task not found.'];
        }

        // Close the statement
        $stmt->close();
    } else {
        // Set error response if the query execution fails
        $response = ['error' => 'Error executing query: ' . $stmt->error];
    }
} else {
    // Set error response if the task ID is invalid
    $response = ['error' => 'Invalid task ID.'];
}

// Output the response as JSON
header('Content-Type: application/json');
echo json_encode($response);

?>