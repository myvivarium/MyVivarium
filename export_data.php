<?php
session_start(); // Start the session to use session variables

// Include the database connection file
include 'dbcon.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username'])) {
    header("Location: index.php"); // Redirect to login if not logged in
    exit();
}

// Check if the user is an admin
if ($_SESSION['role'] != 'admin') {
    $_SESSION['message'] = "Access Denied";
    exit();
}

// Function to export a single table to a CSV format
function exportTableToCSV($con, $tableName) {
    // Exclude sensitive data from the users table
    $query = ($tableName == 'users') ? "SELECT id, name, username, position, role, status FROM `$tableName`" : "SELECT * FROM `$tableName`";
    $result = $con->query($query);

    if (!$result) {
        echo "Failed to retrieve data from $tableName: " . $con->error;
        return '';
    }

    $columns = $result->fetch_fields();
    $csvContent = '';

    // Use output buffering to store CSV content
    ob_start();
    $output = fopen('php://output', 'w');

    // Output the column headings
    $headers = [];
    foreach ($columns as $column) {
        $headers[] = $column->name;
    }
    fputcsv($output, $headers);

    // Output all rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    $csvContent = ob_get_clean();

    return $csvContent;
}

// Fetch all table names from the database
$tableQuery = "SHOW TABLES";
$tableResult = $con->query($tableQuery);

if (!$tableResult) {
    die("Error retrieving tables: " . $con->error);
}

// Headers to prompt the browser to download the file
header('Content-Type: application/zip');
header('Content-Disposition: attachment;filename="exported_data.zip"');

// Create a new ZipArchive
$zip = new ZipArchive();
$zipFilename = tempnam(sys_get_temp_dir(), 'zip');
if (!$zip->open($zipFilename, ZipArchive::CREATE)) {
    die("Failed to create zip file");
}

// Loop through each table and add the CSV to the zip file
while ($tableRow = $tableResult->fetch_row()) {
    $tableName = $tableRow[0];
    $csvContent = exportTableToCSV($con, $tableName);
    if (!empty($csvContent)) {
        $zip->addFromString($tableName . '.csv', $csvContent);
    }
}

$zip->close();

// Output the zip file
readfile($zipFilename);

// Delete the temporary file
unlink($zipFilename);

// Close the connection
$con->close();

// Set a session message for success confirmation
$_SESSION['message'] = "Data exported successfully!";

// Redirect back to the admin page (adjust as necessary)
header("Location: index.php");
exit();
?>
