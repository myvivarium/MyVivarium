<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Fetch all distinct cage IDs from the database
$query = "SELECT DISTINCT `cage_id` FROM bc_basic";
$result = mysqli_query($con, $query);

// Initialize an array to store cage IDs
$cageIds = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cageIds[] = $row['cage_id'];
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <title>Select Breeding Cages for Printing</title>

    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .form-wrapper {
            margin: 50px auto;
            width: 80%;
        }

        .btn-container {
            margin-top: 20px;
        }

        .btn-container button {
            margin-right: 10px;
        }
    </style>

    <script>
        function validateSelection() {
            var selectedIds = document.getElementById("cageIds").selectedOptions;
            if (selectedIds.length > 4) {
                alert("You can select up to 4 cage IDs only.");
                return false;
            }
            if (selectedIds.length === 0) {
                alert("Please select at least one cage ID.");
                return false;
            }
            return true;
        }

        function handleSubmit(event, url) {
            event.preventDefault();
            if (validateSelection()) {
                var selectedIds = document.getElementById("cageIds").selectedOptions;
                var ids = Array.from(selectedIds).map(option => option.value);
                var queryString = url + "?id=" + ids.join(",");
                window.open(queryString, '_blank'); // Open in a new tab
            }
        }

        $(document).ready(function() {
            $('#cageIds').select2({
                placeholder: "Select Cage IDs",
                allowClear: true,
                width: '80%'
            });
        });
    </script>
</head>

<body>
    <div class="container mt-4">
        <div class="form-wrapper">
            <h4>Select Breeding Cages for Printing</h4>
            <form>
                <div class="mb-3">
                    <label for="cageIds" class="form-label">Select Cage IDs (up to 4):</label>
                    <br>
                    <select id="cageIds" name="id[]" class="form-select" multiple size="10">
                        <?php foreach ($cageIds as $cageId) : ?>
                            <option value="<?= htmlspecialchars($cageId) ?>"><?= htmlspecialchars($cageId) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn btn-primary btn-print" onclick="handleSubmit(event, 'bc_prnt_crd.php')">Print Cage Card</button>
                    <!--<button type="submit" class="btn btn-secondary btn-view" onclick="handleSubmit(event, 'hc_prnt_crd.php')">View Cage Card</button>-->
                </div>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>
