<?php
/**
 * Manage Reminders
 * 
 * This script provides functionality for managing reminders in the database.
 * It allows users to add new reminders, edit existing ones, and delete them.
 * The interface includes a responsive popup form for data entry and a table for displaying existing reminders.
 */

ob_start(); // Start output buffering
session_start();
require 'header.php';
require 'dbcon.php';

// Check if the user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

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
    $dayOfWeek = htmlspecialchars($_POST['day_of_week'] ?? null);
    $dayOfMonth = htmlspecialchars($_POST['day_of_month'] ?? null);
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
        $stmt->bind_param("sisssssii", $title, $description, $assignedTo, $recurrenceType, $dayOfWeek, $dayOfMonth, $timeOfDay, $status, $id);
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
    <title>Manage Reminders</title>
    <!-- Include necessary styles and scripts -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"/>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Add your custom styles here */
        /* ... (similar to the styles in manage_tasks.php) ... */
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
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['title']); ?></td>
                            <td>
                                <?= ucfirst($row['recurrence_type']); ?>
                                <?php if ($row['recurrence_type'] == 'weekly') : ?>
                                    (<?= $row['day_of_week']; ?>)
                                <?php elseif ($row['recurrence_type'] == 'monthly') : ?>
                                    (Day <?= $row['day_of_month']; ?>)
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $assignedToNames = array_map(function ($id) use ($users) {
                                    return isset($users[$id]) ? $users[$id] : 'Unknown';
                                }, explode(',', $row['assigned_to']));
                                echo htmlspecialchars(implode(', ', $assignedToNames));
                                ?>
                            </td>
                            <td><?= htmlspecialchars(ucfirst($row['status'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm editButton" data-id="<?= $row['id']; ?>"><i class="fas fa-edit"></i></button>
                                    <form action="manage_reminder.php" method="post" style="display:inline-block;">
                                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this reminder?');"><i class="fas fa-trash-alt"></i></button>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
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
        });

        // Reset form fields
        function resetForm() {
            $('#reminderForm')[0].reset();
            $('#assigned_to').val(null).trigger('change');
            $('#weeklyOptions').hide();
            $('#monthlyOptions').hide();
            $('#titleCounter').text(`0/100 characters used`);
            $('#descriptionCounter').text(`0/500 characters used`);
        }

        // Fetch reminder data for editing
        function fetchReminderData(id) {
            $.ajax({
                url: 'get_reminder.php',
                type: 'GET',
                data: { id: id },
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
    </script>
    <?php require 'footer.php'; ?>
</body>
</html>
