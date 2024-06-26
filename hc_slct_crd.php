<?php

/**
 * Select Holding Cages for Printing
 * 
 * This script allows the user to select up to 4 holding cages for printing their cage cards. 
 * It uses the Select2 library for an enhanced multi-select dropdown and opens the selected 
 * cage IDs in a new tab for printing.
 * 
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Fetch all distinct cage IDs from the database
$query = "SELECT DISTINCT `cage_id` FROM hc_basic";
$result = mysqli_query($con, $query);

// Initialize an array to store cage IDs
$cageIds = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cageIds[] = $row['cage_id'];
}

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>Select Holding Cages for Printing</title>

    <!-- Select2 CSS for enhanced dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Basic styling for the page */
        body {
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 50px auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .btn-container {
            margin-top: 20px;
        }

        .btn-container button {
            margin-right: 10px;
        }
    </style>

    <script>
        // Validate the selection of cage IDs
        function validateSelection() {
            var selectedIds = document.getElementById("cageIds").selectedOptions;
            if (selectedIds.length > 4) {
                alert("You can select up to 4 cage IDs only."); // Alert if more than 4 IDs are selected
                return false;
            }
            if (selectedIds.length === 0) {
                alert("Please select at least one cage ID."); // Alert if no ID is selected
                return false;
            }
            return true; // Return true if the selection is valid
        }

        // Handle form submission to open a new tab with the selected cage IDs
        function handleSubmit(event, url) {
            event.preventDefault(); // Prevent the default form submission
            if (validateSelection()) {
                var selectedIds = document.getElementById("cageIds").selectedOptions;
                var ids = Array.from(selectedIds).map(option => option.value); // Get the selected IDs
                var queryString = url + "?id=" + ids.join(","); // Create a query string with the selected IDs
                window.open(queryString, '_blank'); // Open the query string in a new tab
            }
        }

        // Initialize Select2 for the cage IDs dropdown
        $(document).ready(function() {
            $('#cageIds').select2({
                placeholder: "Select Cage IDs", // Placeholder text for the dropdown
                allowClear: true,
                width: '100%' // Set the width to 100%
            });
        });

        // Function to go back to the previous page
        function goBack() {
            window.history.back();
        }
    </script>
</head>

<body>
    <br>
    <br>
    <div class="content">
        <br>
        <br>
        <div class="container">
            <h4>Select Holding Cages for Printing</h4>
            <br>
            <form>
                <div class="mb-3">
                    <label for="cageIds" class="form-label">Select Cage IDs (up to 4):</label>
                    <br>
                    <!-- Multi-select dropdown for selecting cage IDs -->
                    <select id="cageIds" name="id[]" class="form-select" multiple size="10">
                        <?php foreach ($cageIds as $cageId) : ?>
                            <option value="<?= htmlspecialchars($cageId) ?>"><?= htmlspecialchars($cageId) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <br>
                <div class="btn-container">
                    <!-- Button to print cage cards -->
                    <button type="submit" class="btn btn-primary btn-print" onclick="handleSubmit(event, 'hc_prnt_crd.php')">Print Cage Card</button>
                    <!-- Button to go back to the previous page -->
                    <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>
                </div>
            </form>
        </div>
    </div>
    <br>
    <!-- Include the footer file -->
    <?php include 'footer.php'; ?>
</body>

</html>