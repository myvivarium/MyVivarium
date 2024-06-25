#!/usr/bin/php
<?php

require 'dbcon.php';  // Include database connection file
require 'config.php';  // Include configuration file
require 'vendor/autoload.php'; // Load PHPMailer library

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send an email using PHPMailer
function sendEmail($recipients, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;

        // Sender info
        $mail->setFrom(SENDER_EMAIL, SENDER_NAME);

        // Handle multiple recipients
        if (is_array($recipients)) {
            foreach ($recipients as $recipient) {
                $mail->addAddress(trim($recipient));
            }
        } else {
            $recipientList = explode(',', $recipients);
            foreach ($recipientList as $recipient) {
                $mail->addAddress(trim($recipient)); // Single recipient case
            }
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send email
        $mail->send();
        return true; // Return true if email sent successfully
    } catch (Exception $e) {
        error_log('Mail could not be sent. Mailer Error: ' . $mail->ErrorInfo);
        return false; // Return false if email sending fails
    }
}

// Function to send pending emails from the queue
function sendPendingEmails()
{
    global $con;

    // Fetch pending emails
    $stmt = $con->prepare("SELECT id, recipient, subject, body FROM email_queue WHERE status = 'pending'");
    $stmt->execute();
    $stmt->store_result(); // Store the result set to free the connection
    $stmt->bind_result($id, $recipient, $subject, $body);

    while ($stmt->fetch()) {
        // Attempt to send the email
        $result = sendEmail($recipient, $subject, $body);
        $sentAt = date('Y-m-d H:i:s');

        // Update the email status based on the result
        if ($result) {
            $status = 'sent';
            $errorMessage = null;
        } else {
            $status = 'failed';
            $errorMessage = 'Error sending email'; // Customize this with more detailed error info if needed
        }

        // Update email status in the database
        // Prepare and close update statement within the loop to avoid the "Commands out of sync" error
        $updateStmt = $con->prepare("UPDATE email_queue SET status = ?, sent_at = ?, error_message = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $status, $sentAt, $errorMessage, $id);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Close the select statement
    $stmt->close();
}

// Call the sendPendingEmails function if the script is executed directly
if (php_sapi_name() == "cli") {
    sendPendingEmails();
}

?>
