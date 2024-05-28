<?php
// Load Composer's autoload file
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Check if the .env file exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    die('.env file not found. Please create the file and add your database credentials.');
}

// Database connection credentials from environment variables
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbname = $_ENV['DB_DATABASE'] ?? 'database';

// Create a new connection to the database using the object-oriented style
$con = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($con->connect_error) {
    // Log the error message (for production, avoid displaying detailed errors)
    error_log('Connection Failed: ' . $con->connect_error);
    die('Connection Failed. Please try again later.');
}
?>
