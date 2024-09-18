<?php

/**
 * Manage Reminders
 * 
 * This script provides functionality for managing reminders in the database.
 * It allows users to add new reminders, edit existing ones, and delete them.
 * The interface includes a responsive popup form for data entry and a table for displaying existing reminders.
 * The script uses PHP sessions for message handling and includes basic input sanitization for security.
 */
ob_start();
session_start();

// Check if the user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

require 'header.php';
require 'dbcon.php';

// Get the current user ID and name from the session
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['name'] ?? '';
$isAdmin = $_SESSION['role'] == 'admin';

// Function to redirect with a message
function redirectToPage($message)
{
    $_SESSION['message'] = $message;
    header('Location: manage_reminder.php');
    exit();
}

// Handle form submission for adding, editing, or deleting reminders
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $assignedBy = $currentUserId;
    $assignedTo = htmlspecialchars(implode(',', $_POST['assigned_to'] ?? []));
    $recurrenceType = htmlspecialchars($_POST['recurrence_type']);
    $dayOfWeek = !empty($_POST['day_of_week']) ? htmlspecialchars($_POST['day_of_week']) : null;
    $dayOfMonth = !empty($_POST['day_of_month']) ? (int)$_POST['day_of_month'] : null;
    $timeOfDay = htmlspecialchars($_POST['time_of_day']);
    $status = htmlspecialchars($_POST['status']);
    $reminder_id = null;

    // Determine the action to perform (add, edit, or delete)
    if (isset($_POST['add'])) {
        $stmt = $con->prepare("INSERT INTO reminders (title, description, assigned_by, assigned_to, recurrence_type, day_of_week, day_of_month, time_of_day, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissssss", $title, $description, $assignedBy, $assignedTo, $recurrenceType, $dayOfWeek, $dayOfMonth, $timeOfDay, $status);
        if ($stmt->execute()) {
            redirectToPage("Reminder added successfully.");
        } else {
            redirectToPage("Error: " . $stmt->error);
        }
        $stmt->close();
    } elseif (isset($_POST['edit'])) {
        $id = htmlspecialchars($_POST['id']);
        $stmt = $con->prepare("UPDATE reminders SET title = ?, description = ?, assigned_to = ?, recurrence_type = ?, day_of_week = ?, day_of_month = ?, time_of_day = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sissssssi", $title, $description, $assignedTo, $recurrenceType, $dayOfWeek, $dayOfMonth, $timeOfDay, $status, $id);
        if ($stmt->execute()) {
            redirectToPage("Reminder updated successfully.");
        } else {
            redirectToPage("Error: " . $stmt->error);
        }
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        $id = htmlspecialchars($_POST['id']);
        $stmt = $con->prepare("DELETE FROM reminders WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            redirectToPage("Reminder deleted successfully.");
        } else {
            redirectToPage("Error: " . $stmt->error);
        }
        $stmt->close();
    }
}

// Fetch users for the dropdown
$userQuery = "SELECT id, name FROM users";
$userResult = $con->query($userQuery);
$users = $userResult ? array_column($userResult->fetch_all(MYSQLI_ASSOC), 'name', 'id') : [];

// Fetch reminders for display
$reminderQuery = "SELECT * FROM reminders";
$reminderResult = $con->query($reminderQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reminders</title>
    <!-- Include necessary styles and scripts -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
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
            width: 20%;
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
            width: 10%;
        }

        .table th:nth-child(6),
        .table td:nth-child(6) {
            width: 20%;
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

        /* Styles for the View Popup */
        #viewPopupOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        #viewPopupForm {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border: 1px solid #ccc;
            z-index: 1001;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
            overflow-y: auto;
            max-height: 90vh;
        }

        #viewPopupForm .form-group {
            margin-bottom: 15px;
        }

        #viewPopupForm label {
            font-weight: bold;
        }

        #viewPopupForm p {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container content mt-5">
        <?php include('message.php'); ?>
        <h2>Manage Reminders</h2>
        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert alert-info">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Button to add a new reminder -->
        <div class="add-button">
            <button id="addNewReminderButton" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Reminder</button>
        </div>

        <!-- Popup form for adding and editing reminders -->
        <div class="popup-overlay" id="popupOverlay"></div>
        <div class="popup-form" id="popupForm">
            <h4 id="formTitle">Add New Reminder</h4>
            <form id="reminderForm" action="manage_reminder.php" method="post">
                <input type="hidden" name="id" id="id">
                <div class="form-group">
                    <label for="title">Title <span class="required-asterisk">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" maxlength="100" required>
                    <small id="titleCounter" class="form-text text-muted">0/100 characters used</small>
                </div>
                <div class="form-group">
                    <label for="description">Description <span class="required-asterisk">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="3" maxlength="500" required></textarea>
                    <small id="descriptionCounter" class="form-text text-muted">0/500 characters used</small>
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
                    <label>Recurrence Type <span class="required-asterisk">*</span></label>
                    <select name="recurrence_type" id="recurrence_type" class="form-control" required>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div class="form-group" id="weeklyOptions" style="display: none;">
                    <label for="day_of_week">Day of the Week <span class="required-asterisk">*</span></label>
                    <select name="day_of_week" id="day_of_week" class="form-control">
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="form-group" id="monthlyOptions" style="display: none;">
                    <label for="day_of_month">Day of the Month (1-28) <span class="required-asterisk">*</span></label>
                    <input type="number" name="day_of_month" id="day_of_month" class="form-control" min="1" max="28">
                </div>
                <div class="form-group">
                    <label for="time_of_day">Time of Day <span class="required-asterisk">*</span></label>
                    <input type="time" name="time_of_day" id="time_of_day" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Status <span class="required-asterisk">*</span></label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="add" id="addButton" class="btn btn-primary"><i class="fas fa-plus"></i> Add Reminder</button>
                    <button type="submit" name="edit" id="editButton" class="btn btn-success" style="display: none;"><i class="fas fa-save"></i> Update Reminder</button>
                    <button type="button" class="btn btn-secondary" id="cancelButton">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Popup form for viewing reminders -->
        <div class="popup-overlay" id="viewPopupOverlay"></div>
        <div class="popup-form" id="viewPopupForm">
            <h4>View Reminder Details</h4>
            <div class="form-group">
                <label>Title:</label>
                <p id="viewTitle"></p>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <p id="viewDescription"></p>
            </div>
            <div class="form-group">
                <label>Assigned To:</label>
                <p id="viewAssignedTo"></p>
            </div>
            <div class="form-group">
                <label>Recurrence Type:</label>
                <p id="viewRecurrenceType"></p>
            </div>
            <div class="form-group">
                <label>Time of Day:</label>
                <p id="viewTimeOfDay"></p>
            </div>
            <div class="form-group">
                <label>Status:</label>
                <p id="viewStatus"></p>
            </div>
            <div class="form-buttons">
                <button type="button" class="btn btn-secondary" id="closeViewButton">Close</button>
            </div>
        </div>

        <!-- Table for displaying reminders -->
        <h3>Existing Reminders</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Recurrence</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $reminderResult->fetch_assoc()) : ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($row['id']); ?></td>
                            <td data-label="Title"><?= htmlspecialchars($row['title']); ?></td>
                            <td data-label="Recurrence">
                                <?= ucfirst($row['recurrence_type']); ?>
                                <?php if ($row['recurrence_type'] == 'weekly') : ?>
                                    (<?= $row['day_of_week']; ?>)
                                <?php elseif ($row['recurrence_type'] == 'monthly') : ?>
                                    (Day <?= $row['day_of_month']; ?>)
                                <?php endif; ?>
                                at <?= date('h:i A', strtotime($row['time_of_day'])); ?>
                            </td>
                            <td data-label="Assigned To">
                                <?php
                                $assignedToNames = array_map(function ($id) use ($users) {
                                    return isset($users[$id]) ? $users[$id] : 'Unknown';
                                }, explode(',', $row['assigned_to']));
                                echo htmlspecialchars(implode(', ', $assignedToNames));
                                ?>
                            </td>
                            <td data-label="Status"><?= htmlspecialchars(ucfirst($row['status'])); ?></td>
                            <td data-label="Actions" class="table-actions">
                                <div class="action-buttons">
                                    <!-- Add the View button here -->
                                    <button class="btn btn-info btn-sm viewButton" data-id="<?= $row['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <!-- Existing Edit and Delete buttons -->
                                    <button class="btn btn-warning btn-sm editButton" data-id="<?= $row['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="manage_reminder.php" method="post" style="display:inline-block;">
                                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this reminder?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>

    <!-- Include necessary scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // JavaScript code to handle form interactions
        $(document).ready(function() {
            $('#assigned_to').select2({
                placeholder: "Select users",
                allowClear: true
            });

            // Show/hide recurrence options based on selected recurrence type
            $('#recurrence_type').on('change', function() {
                const recurrenceType = $(this).val();
                if (recurrenceType === 'weekly') {
                    $('#weeklyOptions').show();
                    $('#monthlyOptions').hide();
                    $('#day_of_month').val('');
                } else if (recurrenceType === 'monthly') {
                    $('#weeklyOptions').hide();
                    $('#monthlyOptions').show();
                    $('#day_of_week').val('');
                } else {
                    $('#weeklyOptions').hide();
                    $('#monthlyOptions').hide();
                    $('#day_of_week').val('');
                    $('#day_of_month').val('');
                }
            });

            // Open the add reminder form
            $('#addNewReminderButton').on('click', function() {
                resetForm();
                $('#formTitle').text('Add New Reminder');
                $('#addButton').show();
                $('#editButton').hide();
                $('#popupOverlay').show();
                $('#popupForm').show();
            });

            // Close the form
            $('#cancelButton').on('click', function() {
                $('#popupOverlay').hide();
                $('#popupForm').hide();
            });

            // Close the form when clicking outside
            $('#popupOverlay').on('click', function() {
                $('#popupOverlay').hide();
                $('#popupForm').hide();
            });

            // Edit reminder
            $('.editButton').on('click', function() {
                const id = $(this).data('id');
                fetchReminderData(id);
            });

            // Character counters
            $('#title').on('input', function() {
                const currentLength = $(this).val().length;
                $('#titleCounter').text(`${currentLength}/100 characters used`);
            });
            $('#description').on('input', function() {
                const currentLength = $(this).val().length;
                $('#descriptionCounter').text(`${currentLength}/500 characters used`);
            });

            // Map PHP users array to JavaScript
            const users = <?= json_encode($users); ?>;

            // View reminder
            $('.viewButton').on('click', function() {
                const id = $(this).data('id');
                fetchViewReminderData(id);
            });

            // Close the view popup
            $('#closeViewButton').on('click', function() {
                $('#viewPopupOverlay').hide();
                $('#viewPopupForm').hide();
            });

            // Close the view popup when clicking outside
            $('#viewPopupOverlay').on('click', function() {
                $('#viewPopupOverlay').hide();
                $('#viewPopupForm').hide();
            });

            // Stop propagation when clicking inside the view popup
            $('#viewPopupForm').on('click', function(e) {
                e.stopPropagation();
            });
        });

        // Reset form fields
        function resetForm() {
            $('#reminderForm')[0].reset();
            $('#assigned_to').val(null).trigger('change');
            $('#weeklyOptions').hide();
            $('#monthlyOptions').hide();
            $('#titleCounter').text(`0/100 characters used`);
            $('#descriptionCounter').text(`0/500 characters used`);
            $('#assigned_by').val('<?= $currentUserName; ?>');
        }

        // Fetch reminder data for editing
        function fetchReminderData(id) {
            $.ajax({
                url: 'get_reminder.php',
                type: 'GET',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert(response.error);
                        return;
                    }
                    // Populate form fields
                    $('#id').val(response.id);
                    $('#title').val(response.title);
                    $('#description').val(response.description);
                    $('#assigned_to').val(response.assigned_to.split(',')).trigger('change');
                    $('#recurrence_type').val(response.recurrence_type).trigger('change');
                    $('#day_of_week').val(response.day_of_week);
                    $('#day_of_month').val(response.day_of_month);
                    $('#time_of_day').val(response.time_of_day);
                    $('#status').val(response.status);

                    // Update character counters
                    $('#titleCounter').text(`${response.title.length}/100 characters used`);
                    $('#descriptionCounter').text(`${response.description.length}/500 characters used`);

                    // Show the form
                    $('#formTitle').text('Edit Reminder');
                    $('#addButton').hide();
                    $('#editButton').show();
                    $('#popupOverlay').show();
                    $('#popupForm').show();
                },
                error: function(xhr, status, error) {
                    alert('Error fetching reminder data: ' + error);
                }
            });
        }

        // Function to fetch and display reminder data in the view popup
        function fetchViewReminderData(id) {
            $.ajax({
                url: 'get_reminder.php',
                type: 'GET',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert(response.error);
                        return;
                    }
                    // Populate the view popup with reminder details
                    $('#viewTitle').text(response.title);
                    $('#viewDescription').text(response.description);

                    // Get assigned to names
                    let assignedToNames = [];
                    if (response.assigned_to) {
                        const assignedToIds = response.assigned_to.split(',');
                        assignedToIds.forEach(function(id) {
                            const name = users[id];
                            if (name) {
                                assignedToNames.push(name);
                            } else {
                                assignedToNames.push('Unknown');
                            }
                        });
                    }
                    $('#viewAssignedTo').text(assignedToNames.join(', '));

                    // Display recurrence type and details
                    let recurrenceDetails = response.recurrence_type;
                    if (response.recurrence_type === 'weekly') {
                        recurrenceDetails += ' (' + response.day_of_week + ')';
                    } else if (response.recurrence_type === 'monthly') {
                        recurrenceDetails += ' (Day ' + response.day_of_month + ')';
                    }
                    $('#viewRecurrenceType').text(recurrenceDetails);

                    $('#viewTimeOfDay').text(response.time_of_day);
                    $('#viewStatus').text(response.status);

                    // Show the view popup
                    $('#viewPopupOverlay').show();
                    $('#viewPopupForm').show();
                },
                error: function(xhr, status, error) {
                    alert('Error fetching reminder data: ' + error);
                }
            });
        }
    </script>
    <?php require 'footer.php'; ?>
</body>

</html>