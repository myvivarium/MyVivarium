<?php
// Start the session to use session variables
session_start();

// Include database connection
require 'dbcon.php';

// Initialize response
$response = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $taskId = intval($_GET['id']);

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $con->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $taskId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch task data
            $task = $result->fetch_assoc();

            // Populate the response with task data
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
            $response = ['error' => 'Task not found.'];
        }

        $stmt->close();
    } else {
        $response = ['error' => 'Error executing query: ' . $stmt->error];
    }
} else {
    $response = ['error' => 'Invalid task ID.'];
}

// Output the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
