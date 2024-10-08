<?php

/**
 * SMTP Configuration Script
 *
 * This script loads environment variables from a .env file using the Dotenv library and defines constants
 * for SMTP server settings and sender information. These constants are used for configuring the SMTP
 * server for sending emails.
 *
 */

// Load Composer's autoload file
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from the .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define constants for SMTP server settings and sender information
define('SMTP_HOST', $_ENV['SMTP_HOST']); // SMTP server hostname
define('SMTP_PORT', $_ENV['SMTP_PORT']); // SMTP server port
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME']); // SMTP server username
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD']); // SMTP server password
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION']); // SMTP server encryption type (tls or ssl)
define('SENDER_EMAIL', $_ENV['SENDER_EMAIL']); // Sender's email address
define('SENDER_NAME', $_ENV['SENDER_NAME']); // Sender's name
