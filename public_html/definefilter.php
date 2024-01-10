<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'dbcon.php'; // Include your database connection

// Check if the user is logged in as admin or EA student
if (!isset($_SESSION['admin_username']) && !isset($_SESSION['ea_username'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit;
}

// Get the user's username based on their role
$user_username = $_SESSION['admin_username'] ?? $_SESSION['ea_username'];

// Determine the appropriate table based on the user's role
$user_table = isset($_SESSION['admin_username']) ? 'udaytonadmin' : 'udayton_requests';

// Fetch table names from the database
$tables_query = "SHOW TABLES";
$tables_result = mysqli_query($con, $tables_query);
$tables = array();
while ($table_row = mysqli_fetch_row($tables_result)) {
    $table_name = $table_row[0];
    $tables[] = $table_name;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table_name = mysqli_real_escape_string($con, $_POST['table_name']);
    $column_name = mysqli_real_escape_string($con, $_POST['column_name']);
    $filter_value = mysqli_real_escape_string($con, $_POST['filter_value']);

    // Insert filter into user_filters table
    $insert_query = "INSERT INTO user_filters (user_id, table_name, column_name, filter_value)
                     VALUES ('$user_username', '$table_name', '$column_name', '$filter_value')";
    mysqli_query($con, $insert_query);

    // Redirect to user's dashboard
    if (isset($_SESSION['admin_username'])) {
        header("Location: adminlanding.php");
    } else {
        header("Location: EAstudentlanding.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Define Filter</title>
</head>
<body>
    <h1>Define Search Filter</h1>
    <form method="POST" action="">
        <label for="table_name">Select Table:</label>
        <select name="table_name" id="table_name">
            <?php foreach ($tables as $table) : ?>
                <option value="<?= $table ?>"><?= $table ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <label for="column_name">Select Column:</label>
        <select name="column_name" id="column_name">
        <?php
if (isset($_GET['get_columns'])) {
    $selectedTable = mysqli_real_escape_string($con, $_GET['get_columns']);
    
    // Fetch column names for the selected table
    $columns_query = "SHOW COLUMNS FROM $selectedTable";
    $columns_result = mysqli_query($con, $columns_query);
    $columns = array();
    while ($column_row = mysqli_fetch_assoc($columns_result)) {
        $columns[] = $column_row['Field'];
    }
    
    // Return column names as JSON response
    echo json_encode($columns);
    exit;
}
?>
        </select><br><br>
        <label for="filter_value">Enter Filter Value:</label>
        <input type="text" name="filter_value" id="filter_value"><br><br>
        <input type="submit" value="Create Filter">
    </form>

    <script>
    const tableDropdown = document.getElementById('table_name');
    const columnDropdown = document.getElementById('column_name');

    // Function to populate column dropdown based on selected table
function populateColumnDropdown() {
    const selectedTable = tableDropdown.value;
    columnDropdown.innerHTML = ''; // Clear previous options

    // Fetch column names for the selected table using AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'definefilter.php?get_columns=' + selectedTable, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const columns = JSON.parse(xhr.responseText);
            columns.forEach(column => {
                const option = document.createElement('option');
                option.value = column;
                option.textContent = column;
                columnDropdown.appendChild(option);
            });
        }
    };
    xhr.send();
}


    tableDropdown.addEventListener('change', populateColumnDropdown);
    populateColumnDropdown(); // Populate columns based on default selected table
</script>


</body>
</html>