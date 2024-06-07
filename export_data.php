<?php
// Include the database connection file
include 'dbcon.php';

// Function to export a single table to a CSV format
function exportTableToCSV($con, $tableName) {
    $query = "SELECT * FROM `$tableName`";
    $result = $con->query($query);

    if (!$result) {
        echo "Failed to retrieve data from $tableName: " . $con->error;
        return;
    }

    $columns = $result->fetch_fields();
    $csvFileName = $tableName . ".csv";

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
$zip->open($zipFilename, ZipArchive::CREATE);

// Loop through each table and add the CSV to the zip file
while ($tableRow = $tableResult->fetch_row()) {
    $tableName = $tableRow[0];
    $csvContent = exportTableToCSV($con, $tableName);
    $zip->addFromString($tableName . '.csv', $csvContent);
}

$zip->close();

// Output the zip file
readfile($zipFilename);

// Delete the temporary file
unlink($zipFilename);

$con->close();
exit();
?>
