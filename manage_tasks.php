<?php

/**
 * Manage Tasks
 * 
 * This script provides functionality for managing tasks in a database. It allows users to add new tasks,
 * edit existing tasks, and delete tasks. The interface includes a responsive popup form for data entry and 
 * a table for displaying existing tasks. The script uses PHP sessions for message handling and includes basic 
 * input sanitization for security.
 * 
 * Author: [Your Name]
 * Date: [Date]
 */

ob_start(); // Start output buffering
session_start(); // Start the session to use session variables
require 'header.php';
require 'dbcon.php'; // Include database connection

// Check if the user is logged in, redirect to login page if not logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit;
}

// Get the current user ID and name from the session
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['name'] ?? '';
$isAdmin = $_SESSION['role'] == 'admin'; // Assuming you have a 'role' key in session to check admin status

// Redirect function with session message and cage_id in URL
function redirectToPage($message, $cageId = null)
{
    $_SESSION['message'] = $message;
    $location = 'manage_tasks.php';
    if ($cageId) {
        $location .= '?id=' . $cageId;
    }
    header('Location: ' . $location);
    exit();
}

// Function to schedule an email
function scheduleEmail($recipients, $subject, $body, $scheduledAt)
{
    global $con;
    $stmt = $con->prepare("INSERT INTO email_queue (recipient, subject, body, scheduled_at) VALUES (?, ?, ?, ?)");
    $recipientList = is_array($recipients) ? implode(',', $recipients) : $recipients;
    $stmt->bind_param("ssss", $recipientList, $subject, $body, $scheduledAt);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Error scheduling email: " . $stmt->error);
        return false;
    }

    $stmt->close();
}

// Fetch users and cages for dropdowns
$userQuery = "SELECT id, name FROM users";
$userResult = $con->query($userQuery);
$users = $userResult ? array_column($userResult->fetch_all(MYSQLI_ASSOC), 'name', 'id') : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $assignedBy = htmlspecialchars($_POST['assigned_by_id']);
    $assignedTo = htmlspecialchars(implode(',', $_POST['assigned_to'] ?? []));
    $status = htmlspecialchars($_POST['status']);
    $completionDate = !empty($_POST['completion_date']) ? htmlspecialchars($_POST['completion_date']) : NULL;
    $cageId = htmlspecialchars($_POST['cage_id']);
    $taskAction = '';

    if (isset($_POST['add'])) {
        $stmt = $con->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, status, completion_date, cage_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $title, $description, $assignedBy, $assignedTo, $status, $completionDate, $cageId);
        $taskAction = 'added';
    } elseif (isset($_POST['edit'])) {
        $id = htmlspecialchars($_POST['id']);
        $stmt = $con->prepare("UPDATE tasks SET title = ?, description = ?, assigned_by = ?, assigned_to = ?, status = ?, completion_date = ?, cage_id = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $title, $description, $assignedBy, $assignedTo, $status, $completionDate, $cageId, $id);
        $taskAction = 'updated';
    } elseif (isset($_POST['delete'])) {
        $id = htmlspecialchars($_POST['id']);
        $stmt = $con->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $taskAction = 'deleted';
    }

    if (!$stmt || !$stmt->execute()) {
        redirectToPage("Error: " . ($stmt ? $stmt->error : $con->error));
    } else {
        // Fetch the emails of the assigned by and assigned to users
        $emails = [];
        $assignedByEmailQuery = "SELECT username FROM users WHERE id = ?";
        $assignedByEmailStmt = $con->prepare($assignedByEmailQuery);
        $assignedByEmailStmt->bind_param("i", $assignedBy);
        $assignedByEmailStmt->execute();
        $assignedByEmailStmt->bind_result($assignedByEmail);
        $assignedByEmailStmt->fetch();
        $assignedByEmailStmt->close();
        $emails[] = $assignedByEmail;

        $assignedToArray = explode(',', $assignedTo);
        foreach ($assignedToArray as $assignedToUserId) {
            $assignedToEmailQuery = "SELECT username FROM users WHERE id = ?";
            $assignedToEmailStmt = $con->prepare($assignedToEmailQuery);
            $assignedToEmailStmt->bind_param("i", $assignedToUserId);
            $assignedToEmailStmt->execute();
            $assignedToEmailStmt->bind_result($assignedToEmail);
            while ($assignedToEmailStmt->fetch()) {
                $emails[] = $assignedToEmail;
            }
            $assignedToEmailStmt->close();
        }

        // Fetch the timezone from the settings table
        $timezoneQuery = "SELECT value FROM settings WHERE name = 'timezone'";
        $timezoneResult = mysqli_query($con, $timezoneQuery);
        $timezoneRow = mysqli_fetch_assoc($timezoneResult);
        $timezone = $timezoneRow['value'] ?? 'America/New_York';

        // Set the default timezone
        date_default_timezone_set($timezone);

        // Get the current date and time
        $dateTime = date('Y-m-d H:i:s');

        // Convert assignedBy and assignedTo IDs to names
        $assignedByName = isset($users[$assignedBy]) ? $users[$assignedBy] : 'Unknown';
        $assignedToNames = array_map(function ($id) use ($users) {
            return isset($users[$id]) ? $users[$id] : 'Unknown';
        }, explode(',', $assignedTo));
        $assignedToNamesStr = implode(', ', $assignedToNames);

        // Update the subject to include date and time
        $subject = "Task $taskAction: $title on $dateTime";
        $body = "The task id '$id' has been $taskAction. Here are the details:<br><br>" .
            "<strong>Title:</strong> $title<br>" .
            "<strong>Description/Update:</strong> $description<br>" .
            "<strong>Status:</strong> $status<br>" .
            "<strong>Assigned By:</strong> $assignedByName<br>" .
            "<strong>Assigned To:</strong> $assignedToNamesStr<br>" .
            "<strong>Completion Date:</strong> $completionDate<br>" .
            "<strong>Cage ID:</strong> $cageId<br>";

        // Schedule the email
        //$scheduledAt = date('Y-m-d H:i:s'); // You can modify this to schedule at a later time if needed
        $result = scheduleEmail($emails, $subject, $body, $scheduledAt);

        // Debug output: print email result
        if ($result) {
            error_log("Email scheduled successfully.");
            redirectToPage("Task $taskAction successfully.");
        } else {
            error_log("Email scheduling failed.");
            redirectToPage("Task $taskAction, but email scheduling failed.");
        }
    }

    $stmt->close();
}

// Fetch tasks for display
$search = htmlspecialchars($_GET['search'] ?? '');
$cageIdFilter = htmlspecialchars($_GET['id'] ?? '');
$filter = htmlspecialchars($_GET['filter'] ?? '');

$taskQuery = "SELECT * FROM tasks WHERE 1";
if ($search) {
    $taskQuery .= " AND (title LIKE '%$search%' OR assigned_by LIKE '%$search%' OR assigned_to LIKE '%$search%' OR status LIKE '%$search%' OR cage_id LIKE '%$search%')";
}

if ($filter == 'assigned_by_me') {
    $taskQuery .= " AND assigned_by = '$currentUserId'";
} elseif ($filter == 'assigned_to_me') {
    $taskQuery .= " AND FIND_IN_SET('$currentUserId', assigned_to)";
} elseif (!$isAdmin) {
    $taskQuery .= " AND (assigned_by = '$currentUserId' OR FIND_IN_SET('$currentUserId', assigned_to))";
}

if ($cageIdFilter) {
    $taskQuery .= " AND cage_id = '$cageIdFilter'";
}

$taskResult = $con->query($taskQuery);

$cageQuery = "SELECT cage_id FROM hc_basic UNION SELECT cage_id FROM bc_basic";
$cageResult = $con->query($cageQuery);
$cages = $cageResult ? array_column($cageResult->fetch_all(MYSQLI_ASSOC), 'cage_id') : [];

ob_end_flush(); // Flush the output buffer
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tasks</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Popup and Overlay Styles */
        .popup-form,
        .view-popup-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border: 1px solid #ccc;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 800px;
            overflow-y: auto;
            max-height: 90vh;
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Button and Form Styles */
        .add-button {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .add-button .btn {
            margin-bottom: 20px;
        }

        .ml-2 {
            margin-left: 10px;
            /* Adjust the spacing between buttons as needed */
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .form-buttons button {
            width: 100%;
            margin-bottom: 10px;
        }

        .required-asterisk {
            color: red;
        }

        .radio-group label {
            margin-right: 15px;
        }

        .radio-group input[type="radio"] {
            margin-right: 5px;
        }

        .form-control[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            box-shadow: none;
            border: 2px solid #ffffff;
        }

        .table th,
        .table td {
            border: 1px solid #ffffff;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        .table thead {
            background-color: #343a40;
            color: #ffffff;
            border-bottom: 2px solid #ffffff;
        }

        .table thead th {
            padding: 10px;
            font-weight: bold;
            text-align: center;
            border-top: 2px solid #ffffff;
            border-left: 2px solid #ffffff;
            border-right: 2px solid #ffffff;
            border-bottom: 2px solid #ffffff;
        }

        /* Specific Column Widths */
        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 10%;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 30%;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 20%;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 20%;
        }

        .table th:nth-child(5),
        .table td:nth-child(5) {
            width: 20%;
        }

        /* Status Colors */
        .status-pending {
            background-color: #f8d7da;
        }

        .status-in-progress {
            background-color: #fff3cd;
        }

        .status-completed {
            background-color: #d4edda;
        }

        /* Action Buttons */
        .table-actions,
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .table-actions {
            border: none !important;
        }

        .table-actions button,
        .action-buttons .btn {
            width: 100%;
            margin-bottom: 10px;
        }

        @media (max-width: 576px) {
            .table thead {
                display: none;
            }

            .table tbody {
                display: block;
                width: 100%;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 15px;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 15px;
            }

            .table tbody tr td {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                border: none;
                position: relative;
                padding-left: 40%;
                text-align: left;
            }

            .table tbody tr td:before {
                content: attr(data-label);
                font-weight: bold;
                text-transform: uppercase;
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                color: #343a40;
                text-align: left;
            }

            .table-actions {
                flex-direction: column;
                flex-wrap: wrap;
            }
        }

        .pr-0 {
            padding-right: 0 !important;
        }

        .pl-0 {
            padding-left: 0 !important;
        }

        .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .btn-primary {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
    </style>
</head>

<body>
    <div class="container content mt-5">

        <!-- Include message for user notifications -->
        <?php include('message.php'); ?>

        <h2>Manage Tasks <?= $cageIdFilter ? 'for Cage ' . htmlspecialchars($cageIdFilter) : ''; ?></h2>
        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert alert-info">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Buttons to add a new task and show all tasks -->
        <div class="add-button">
            <button id="addNewTaskButton" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Task</button>
            <?php if (isset($_GET['id']) && !empty($_GET['id'])) : ?>
                <a href="manage_tasks.php" class="btn btn-secondary ml-2">Show All Tasks</a>
            <?php endif; ?>
        </div>

        <!-- Search form -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-10 pr-0">
                    <input type="text" name="search" class="form-control" placeholder="Search tasks..." value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-2 pl-0">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
            <div class="form-check form-check-inline mt-2">
                <input class="form-check-input" type="radio" name="filter" id="assignedByMe" value="assigned_by_me" <?= $filter == 'assigned_by_me' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="assignedByMe">Assigned by Me</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="filter" id="assignedToMe" value="assigned_to_me" <?= $filter == 'assigned_to_me' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="assignedToMe">Assigned to Me</label>
            </div>
        </form>

        <!-- Popup form for adding and editing tasks -->
        <div class="popup-overlay" id="popupOverlay"></div>
        <div class="popup-form" id="popupForm">
            <h4 id="formTitle">Add New Task</h4>
            <form id="taskForm" action="manage_tasks.php" method="post">
                <input type="hidden" name="id" id="id">
                <div class="form-group">
                    <label for="title">Title <span class="required-asterisk">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" maxlength="100" required>
                    <small id="titleCounter" class="form-text text-muted">0/100 characters used</small>
                </div>

                <div class="form-group">
                    <label for="description">Description/Update <span class="required-asterisk">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="3" maxlength="500" required></textarea>
                    <small id="descriptionCounter" class="form-text text-muted">0/500 characters used</small>
                </div>
                <div class="form-group">
                    <label for="assigned_by">Assigned By <span class="required-asterisk">*</span></label>
                    <input type="text" name="assigned_by" id="assigned_by" class="form-control" readonly>
                    <input type="hidden" name="assigned_by_id" id="assigned_by_id">
                </div>
                <div class="form-group">
                    <label for="assigned_to">Assigned To <span class="required-asterisk">*</span></label>
                    <select name="assigned_to[]" id="assigned_to" class="form-control" multiple required>
                        <?php foreach ($users as $userId => $name) : ?>
                            <option value="<?= htmlspecialchars($userId); ?>"><?= htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status <span class="required-asterisk">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="status" value="Pending" required> Pending</label>
                        <label><input type="radio" name="status" value="In Progress"> In Progress</label>
                        <label><input type="radio" name="status" value="Completed"> Completed</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="completion_date">Completion Date</label>
                    <input type="date" name="completion_date" id="completion_date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="cage_id">Cage ID</label>
                    <select name="cage_id" id="cage_id" class="form-control">
                        <option value="">Select Cage</option>
                        <?php foreach ($cages as $cageId) : ?>
                            <option value="<?= htmlspecialchars($cageId); ?>"><?= htmlspecialchars($cageId); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="add" id="addButton" class="btn btn-primary" onclick="disableButton(this);"><i class="fas fa-plus"></i> Add Task</button>
                    <button type="submit" name="edit" id="editButton" class="btn btn-success" style="display: none;" onclick="disableButton(this);"><i class="fas fa-save"></i> Update Task</button>
                    <button type="button" class="btn btn-secondary" id="cancelButton">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Popup form for viewing tasks -->
        <div class="popup-overlay" id="viewPopupOverlay"></div>
        <div class="view-popup-form" id="viewPopupForm">
            <h4 id="viewFormTitle">View Task Details</h4>
            <div id="viewTaskDetails"></div>
            <button type="button" class="btn btn-secondary mt-3" id="closeViewFormButton">Close</button>
        </div>

        <!-- Table for displaying tasks -->
        <h3>Existing Tasks</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Assigned By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $taskResult->fetch_assoc()) : ?>
                        <tr class="status-<?= strtolower(str_replace(' ', '-', $row['status'])); ?>">
                            <td data-label="ID"><?= htmlspecialchars($row['id']); ?></td>
                            <td data-label="Title"><?= htmlspecialchars($row['title']); ?></td>
                            <td data-label="Assigned By"><?= htmlspecialchars($users[$row['assigned_by']] ?? $row['assigned_by']); ?></td>
                            <td data-label="Status"><?= htmlspecialchars($row['status']); ?></td>
                            <td data-label="Actions" class="table-actions">
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm editButton" data-id="<?= $row['id']; ?>"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-info btn-sm viewButton" data-id="<?= $row['id']; ?>"><i class="fas fa-eye"></i></button>

                                    <?php if ($row['assigned_by'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') : ?>
                                        <form action="manage_tasks.php" method="post" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this task?');"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Embed the PHP-encoded JSON into a JavaScript variable
        const users = <?= json_encode($users); ?>;
        const cageIdFilter = '<?= $cageIdFilter; ?>';

        $(document).ready(function() {
            $('#assigned_to').select2({
                placeholder: "Select users",
                allowClear: true
            });

            // Ensure the form closes when clicking outside
            $('#popupOverlay, #viewPopupOverlay').on('click', function() {
                console.log('Overlay clicked - closing form');
                closeForm();
                closeViewForm();
            });

            // Stop propagation when clicking inside the form
            $('.popup-form, .view-popup-form').on('click', function(e) {
                e.stopPropagation();
            });

            // Attach click event to the add button
            $('#addNewTaskButton').on('click', function() {
                console.log('Add new task button clicked');
                openForm();
            });

            // Attach click event to the cancel button
            $('#cancelButton').on('click', function() {
                console.log('Cancel button clicked');
                closeForm();
            });

            // Attach click event to the close view form button
            $('#closeViewFormButton').on('click', function() {
                console.log('Close view form button clicked');
                closeViewForm();
            });

            // Attach click events to edit and view buttons dynamically
            $(document).on('click', '.editButton', function() {
                const id = $(this).data('id');
                const cageId = $(this).data('cage');
                console.log('Edit button clicked for ID:', id);
                // Fetch task data and populate the form for editing
                fetchTaskData(id, 'edit', cageId);
            });

            $(document).on('click', '.viewButton', function() {
                const id = $(this).data('id');
                console.log('View button clicked for ID:', id);
                // Fetch task data and show the view popup
                fetchTaskData(id, 'view');
            });

            // Character counter for title
            $('#title').on('input', function() {
                const currentLength = $(this).val().length;
                $('#titleCounter').text(`${currentLength}/100 characters used`);
            });

            // Character counter for description
            $('#description').on('input', function() {
                const currentLength = $(this).val().length;
                $('#descriptionCounter').text(`${currentLength}/500 characters used`);
            });
        });

        function openForm() {
            document.getElementById('popupOverlay').style.display = 'block';
            document.getElementById('popupForm').style.display = 'block';
            document.getElementById('formTitle').innerText = 'Add New Task';
            document.getElementById('addButton').style.display = 'block';
            document.getElementById('editButton').style.display = 'none';
            resetForm();
        }

        function closeForm() {
            document.getElementById('popupOverlay').style.display = 'none';
            document.getElementById('popupForm').style.display = 'none';
        }

        function closeViewForm() {
            document.getElementById('viewPopupOverlay').style.display = 'none';
            document.getElementById('viewPopupForm').style.display = 'none';
        }

        function resetForm() {
            document.getElementById('id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('assigned_by').value = '<?= $currentUserName; ?>';
            document.getElementById('assigned_by_id').value = '<?= $currentUserId; ?>';
            $('#assigned_to').val(null).trigger('change');
            document.querySelector('input[name="status"][value="Pending"]').checked = true;
            document.getElementById('completion_date').value = '';

            // Set the cage_id dropdown to the cageIdFilter value if it exists
            if (cageIdFilter) {
                document.getElementById('cage_id').value = cageIdFilter;
            } else {
                document.getElementById('cage_id').value = '';
            }
        }

        function fetchTaskData(id, action, cageId = '') {
            $.ajax({
                url: 'get_task.php',
                type: 'GET',
                data: {
                    id: id
                },
                success: function(response) {
                    if (response.error) {
                        console.error('Error:', response.error);
                        return;
                    }
                    if (action === 'edit') {
                        openForm();
                        document.getElementById('formTitle').innerText = 'Edit Task';
                        document.getElementById('addButton').style.display = 'none';
                        document.getElementById('editButton').style.display = 'block';
                        document.getElementById('id').value = response.id;
                        document.getElementById('title').value = response.title;
                        document.getElementById('description').value = response.description;
                        document.getElementById('assigned_by').value = users[response.assigned_by] || response.assigned_by;
                        document.getElementById('assigned_by_id').value = response.assigned_by;

                        // Update the assigned_to select element with user names
                        const assignedToUsers = response.assigned_to.split(',');
                        $('#assigned_to').val(assignedToUsers).trigger('change');

                        document.querySelector(`input[name="status"][value="${response.status}"]`).checked = true;
                        document.getElementById('completion_date').value = response.completion_date;
                        document.getElementById('cage_id').value = response.cage_id;

                    } else if (action === 'view') {
                        const assignedToUsers = response.assigned_to.split(',').map(userId => users[userId] || userId).join(', ');

                        const taskDetails = `
                            <p><strong>ID:</strong> ${response.id}</p>
                            <p><strong>Title:</strong> ${response.title}</p>
                            <p><strong>Description/Update:</strong> ${response.description}</p>
                            <p><strong>Assigned By:</strong> ${users[response.assigned_by] || response.assigned_by}</p>
                            <p><strong>Assigned To:</strong> ${assignedToUsers}</p>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Completion Date:</strong> ${response.completion_date}</p>
                            <p><strong>Creation Date:</strong> ${response.creation_date}</p>
                            <p><strong>Cage ID:</strong> ${response.cage_id}</p>
                        `;
                        document.getElementById('viewTaskDetails').innerHTML = taskDetails;
                        document.getElementById('viewPopupOverlay').style.display = 'block';
                        document.getElementById('viewPopupForm').style.display = 'block';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching task data:', error);
                }
            });
        }

        // Function to validate date format & provide feedback
        document.addEventListener('DOMContentLoaded', function() {
            // Function to validate date
            function validateDate(dateString) {
                const regex = /^\d{4}-\d{2}-\d{2}$/;
                if (!dateString.match(regex)) return false;

                const date = new Date(dateString);
                const now = new Date();
                const year = date.getFullYear();

                // Check if the date is valid and within the range 1900-2099 and not in the future
                return date && !isNaN(date) && year >= 1900 && year <= 2099;
            }

            // Function to attach event listeners to date fields
            function attachDateValidation() {
                const dateFields = document.querySelectorAll('input[type="date"]');
                dateFields.forEach(field => {
                    if (!field.dataset.validated) { // Check if already validated
                        const warningText = document.createElement('span');
                        warningText.style.color = 'red';
                        warningText.style.display = 'none';
                        field.parentNode.appendChild(warningText);

                        field.addEventListener('input', function() {
                            const dateValue = field.value;
                            const isValidDate = validateDate(dateValue);
                            if (!isValidDate) {
                                warningText.textContent = 'Invalid Date. Please enter a valid date.';
                                warningText.style.display = 'block';
                            } else {
                                warningText.textContent = '';
                                warningText.style.display = 'none';
                            }
                        });

                        // Mark the field as validated
                        field.dataset.validated = 'true';
                    }
                });
            }

            // Initial call to validate existing date fields
            attachDateValidation();
        });
    </script>

    <div class="extra-space"></div> <!-- Add extra space before the footer -->
    <?php require 'footer.php'; // Include the footer 
    ?>
</body>

</html>