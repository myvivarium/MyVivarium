<?php
/**
 * Process Reminders
 * 
 * This script is intended to be run as a cron job every 5 minutes.
 * It reads active reminders from the database and creates tasks based on their recurrence settings.
 * It also schedules emails by inserting records into the outbox table.
 */

require 'dbcon.php';

// Fetch the timezone from the settings table
$timezoneQuery = "SELECT value FROM settings WHERE name = 'timezone'";
$timezoneResult = mysqli_query($con, $timezoneQuery);
$timezoneRow = mysqli_fetch_assoc($timezoneResult);
$timezone = $timezoneRow['value'] ?? 'America/New_York';

// Set the default timezone
date_default_timezone_set($timezone);

// Get the current date and time
$now = new DateTime();

// Fetch active reminders
$reminderQuery = "SELECT * FROM reminders WHERE status = 'active'";
$reminderResult = $con->query($reminderQuery);

if ($reminderResult) {
    while ($reminder = $reminderResult->fetch_assoc()) {
        $shouldTrigger = false;

        // Calculate the reminder time and task creation time
        $reminderTime = new DateTime($reminder['time_of_day']);
        $taskCreationTime = clone $reminderTime;
        $taskCreationTime->modify('-1 day');

        // Adjust the reminder time based on recurrence
        if ($reminder['recurrence_type'] == 'daily') {
            // Set to today's date
            $reminderTime->setDate($now->format('Y'), $now->format('m'), $now->format('d'));
            $taskCreationTime->setDate($now->format('Y'), $now->format('m'), $now->format('d'));
            $taskCreationTime->modify('-1 day');
        } elseif ($reminder['recurrence_type'] == 'weekly') {
            // Set to the next occurrence of the specified day of the week
            $reminderTime->modify('next ' . $reminder['day_of_week']);
            $taskCreationTime = clone $reminderTime;
            $taskCreationTime->modify('-1 day');
        } elseif ($reminder['recurrence_type'] == 'monthly') {
            // Set to the specified day of the month
            $reminderDay = $reminder['day_of_month'];
            $reminderTime->setDate($now->format('Y'), $now->format('m'), $reminderDay);
            $taskCreationTime = clone $reminderTime;
            $taskCreationTime->modify('-1 day');
            // If the date has passed, move to next month
            if ($reminderTime < $now) {
                $reminderTime->modify('+1 month');
                $taskCreationTime = clone $reminderTime;
                $taskCreationTime->modify('-1 day');
            }
        }

        // Check if we should create the task
        $lastTaskCreated = $reminder['last_task_created'] ? new DateTime($reminder['last_task_created']) : null;

        if ($now >= $taskCreationTime && $now < $reminderTime) {
            if (!$lastTaskCreated || $lastTaskCreated < $taskCreationTime) {
                $shouldTrigger = true;
            }
        } elseif ($now >= $reminderTime) {
            // If we're past the reminder time and task wasn't created, create it immediately
            if (!$lastTaskCreated || $lastTaskCreated < $taskCreationTime) {
                $shouldTrigger = true;
            }
        }

        if ($shouldTrigger) {
            // Create a new task
            $title = $reminder['title'];
            $description = $reminder['description'];
            $assignedBy = $reminder['assigned_by'];
            $assignedTo = $reminder['assigned_to'];
            $status = 'Pending';
            $cageId = $reminder['cage_id'];
            $dueDate = $reminderTime->format('Y-m-d H:i:s');

            $stmt = $con->prepare("INSERT INTO tasks (cage_id, title, description, assigned_by, assigned_to, completion_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississs", $cageId, $title, $description, $assignedBy, $assignedTo, $dueDate, $status);
            $stmt->execute();
            $task_id = $stmt->insert_id;
            $stmt->close();

            // Update the last_task_created in the reminders table
            $updateStmt = $con->prepare("UPDATE reminders SET last_task_created = ? WHERE id = ?");
            $currentTimeStr = $now->format('Y-m-d H:i:s');
            $updateStmt->bind_param("si", $currentTimeStr, $reminder['id']);
            $updateStmt->execute();
            $updateStmt->close();

            // Fetch emails and names for assigned_by and assigned_to
            $emails = [];
            $assignedByName = '';
            $assignedToNames = [];

            // Fetch assigned_by details
            $userQuery = "SELECT name, email FROM users WHERE id = ?";
            $userStmt = $con->prepare($userQuery);
            $userStmt->bind_param("i", $assignedBy);
            $userStmt->execute();
            $userStmt->bind_result($assignedByName, $assignedByEmail);
            $userStmt->fetch();
            $userStmt->close();
            $emails[] = $assignedByEmail;

            // Fetch assigned_to details
            $assignedToArray = explode(',', $assignedTo);
            foreach ($assignedToArray as $assignedToUserId) {
                $userStmt = $con->prepare($userQuery);
                $userStmt->bind_param("i", $assignedToUserId);
                $userStmt->execute();
                $userStmt->bind_result($assignedToName, $assignedToEmail);
                if ($userStmt->fetch()) {
                    $assignedToNames[] = $assignedToName;
                    $emails[] = $assignedToEmail;
                }
                $userStmt->close();
            }
            $assignedToNamesString = implode(', ', $assignedToNames);

            // Prepare email content
            $subject = "New Task Created from Reminder: $title";
            $body = "A new task has been created from a reminder. Here are the details:<br><br>" .
                "<strong>Title:</strong> $title<br>" .
                "<strong>Description:</strong> $description<br>" .
                "<strong>Status:</strong> $status<br>" .
                "<strong>Due Date:</strong> $dueDate<br>" .
                "<strong>Assigned By:</strong> $assignedByName<br>" .
                "<strong>Assigned To:</strong> $assignedToNamesString<br>";

            // Schedule the email
            $scheduledAt = $now->format('Y-m-d H:i:s');
            $recipientList = implode(',', $emails);

            $emailStmt = $con->prepare("INSERT INTO outbox (task_id, recipient, subject, body, scheduled_at) VALUES (?, ?, ?, ?, ?)");
            $emailStmt->bind_param("issss", $task_id, $recipientList, $subject, $body, $scheduledAt);
            $emailStmt->execute();
            $emailStmt->close();
        }
    }
}
?>
