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
        $reminderTime = DateTime::createFromFormat('H:i:s', $reminder['time_of_day']);
        $reminderTime->setDate($now->format('Y'), $now->format('m'), $now->format('d'));

        // Clone reminderTime to avoid modifying the original object
        $reminderTimeClone = clone $reminderTime;
        $reminderTimePlus5 = $reminderTimeClone->modify('+5 minutes');

        // Check if the reminder should trigger
        if ($reminder['recurrence_type'] == 'daily') {
            if ($now >= $reminderTime && $now < $reminderTimePlus5) {
                $shouldTrigger = true;
            }
        } elseif ($reminder['recurrence_type'] == 'weekly') {
            if ($now->format('l') == $reminder['day_of_week']) {
                if ($now >= $reminderTime && $now < $reminderTimePlus5) {
                    $shouldTrigger = true;
                }
            }
        } elseif ($reminder['recurrence_type'] == 'monthly') {
            if ($now->format('j') == $reminder['day_of_month']) {
                if ($now >= $reminderTime && $now < $reminderTimePlus5) {
                    $shouldTrigger = true;
                }
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
            $completionDate = NULL;
            $dueDate = $now->format('Y-m-d H:i:s');

            $stmt = $con->prepare("INSERT INTO tasks (cage_id, title, description, assigned_by, assigned_to, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississs", $cageId, $title, $description, $assignedBy, $assignedTo, $dueDate, $status);
            $stmt->execute();
            $task_id = $stmt->insert_id;
            $stmt->close();

            // Fetch emails of assigned_by and assigned_to users
            $emails = [];

            // Fetch assigned_by email
            $assignedByEmailQuery = "SELECT email FROM users WHERE id = ?";
            $assignedByEmailStmt = $con->prepare($assignedByEmailQuery);
            $assignedByEmailStmt->bind_param("i", $assignedBy);
            $assignedByEmailStmt->execute();
            $assignedByEmailStmt->bind_result($assignedByEmail);
            $assignedByEmailStmt->fetch();
            $assignedByEmailStmt->close();
            $emails[] = $assignedByEmail;

            // Fetch assigned_by name
            $assignedByNameQuery = "SELECT name FROM users WHERE id = ?";
            $assignedByNameStmt = $con->prepare($assignedByNameQuery);
            $assignedByNameStmt->bind_param("i", $assignedBy);
            $assignedByNameStmt->execute();
            $assignedByNameStmt->bind_result($assignedByName);
            $assignedByNameStmt->fetch();
            $assignedByNameStmt->close();

            // Fetch assigned_to emails and names
            $assignedToNames = [];
            $assignedToArray = explode(',', $assignedTo);
            foreach ($assignedToArray as $assignedToUserId) {
                // Fetch email
                $assignedToEmailQuery = "SELECT email FROM users WHERE id = ?";
                $assignedToEmailStmt = $con->prepare($assignedToEmailQuery);
                $assignedToEmailStmt->bind_param("i", $assignedToUserId);
                $assignedToEmailStmt->execute();
                $assignedToEmailStmt->bind_result($assignedToEmail);
                if ($assignedToEmailStmt->fetch()) {
                    $emails[] = $assignedToEmail;
                }
                $assignedToEmailStmt->close();

                // Fetch name
                $assignedToNameQuery = "SELECT name FROM users WHERE id = ?";
                $assignedToNameStmt = $con->prepare($assignedToNameQuery);
                $assignedToNameStmt->bind_param("i", $assignedToUserId);
                $assignedToNameStmt->execute();
                $assignedToNameStmt->bind_result($assignedToName);
                if ($assignedToNameStmt->fetch()) {
                    $assignedToNames[] = $assignedToName;
                }
                $assignedToNameStmt->close();
            }
            $assignedToNamesString = implode(', ', $assignedToNames);

            // Prepare email content
            $subject = "New Task Created from Reminder: $title";
            $body = "A new task has been created from a reminder. Here are the details:<br><br>" .
                "<strong>Title:</strong> $title<br>" .
                "<strong>Description:</strong> $description<br>" .
                "<strong>Status:</strong> $status<br>" .
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
