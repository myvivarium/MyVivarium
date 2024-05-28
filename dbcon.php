<?php
// Load environment variables from a .env file
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection credentials from environment variables
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_DATABASE'];

// Create a new connection to the database using the object-oriented style
$con = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($con->connect_error) {
    // Log the error message (for production, avoid displaying detailed errors)
    error_log('Connection Failed: ' . $con->connect_error);
    die('Connection Failed. Please try again later.');
}
?>
