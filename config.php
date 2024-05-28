<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from the .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define SMTP server settings and sender information
define('SMTP_HOST', $_ENV['SMTP_HOST']);
define('SMTP_PORT', $_ENV['SMTP_PORT']);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME']);
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD']);
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION']);
define('SENDER_EMAIL', $_ENV['SENDER_EMAIL']);
define('SENDER_NAME', $_ENV['SENDER_NAME']);
?>
