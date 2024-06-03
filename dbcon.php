<?php

/**
 * Database Connection Script
 *
 * This script loads environment variables from a .env file using the Dotenv library and establishes a connection
 * to a MySQL database using the credentials provided in the .env file. If the .env file is not found or the
 * connection fails, appropriate error messages are logged or displayed.
 *
 * Author: [Your Name]
 * Date: [Date]
 */

// Load Composer's autoload file
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Check if the .env file exists in the current directory
if (file_exists(__DIR__ . '/.env')) {
    // Create an instance of Dotenv and load the .env file
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    // Terminate the script with an error message if the .env file is not found
    die('.env file not found. Please create the file and add your database credentials.');
}

/*
    .env file format:
    DB_HOST=localhost
    DB_USERNAME=username
    DB_PASSWORD=password
    DB_DATABASE=database
*/

// Retrieve database connection credentials from environment variables
$servername = $_ENV['DB_HOST'] ?? 'localhost'; // Default to 'localhost' if not set
$username = $_ENV['DB_USERNAME'] ?? 'root'; // Default to 'root' if not set
$password = $_ENV['DB_PASSWORD'] ?? ''; // Default to an empty string if not set
$dbname = $_ENV['DB_DATABASE'] ?? 'database'; // Default to 'database' if not set

// Create a new connection to the database using the object-oriented style
$con = new mysqli($servername, $username, $password, $dbname);

// Check the connection to the database
if ($con->connect_error) {
    // Log the error message (for production, avoid displaying detailed errors to users)
    error_log('Connection Failed: ' . $con->connect_error);
    die('Connection Failed. Please try again later.');
}
?>
