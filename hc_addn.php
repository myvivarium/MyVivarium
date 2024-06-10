<?php

/**
 * Add New Holding Cage Script
 * 
 * This PHP script handles the creation of new holding cages in a laboratory management system.
 * It starts by initializing a session and regenerating the session ID to prevent session fixation attacks.
 * It checks if the user is logged in and redirects them to the login page if not. The script generates a CSRF token
 * to protect against CSRF attacks and retrieves a list of Principal Investigators (PIs) from the database for a dropdown selection.
 *
 * When the form is submitted, the script validates the CSRF token and checks if the cage ID already exists in the database.
 * If the cage ID is unique, it collects the form data, including information about up to five mice associated with the cage,
 * and inserts this data into the `hc_basic` table. If the insertion is successful, a success message is set in the session;
 * otherwise, an error message is set. Finally, the user is redirected to the dashboard.
 *
 * The accompanying HTML form allows users to input the cage ID, select a PI, specify mouse details, and add remarks.
 * JavaScript functions dynamically show or hide mouse detail fields based on the quantity selected and adjust the height
 * of text areas for remarks and maintenance notes.
 *
 * Author: [Your Name]
 * Date: [Date]
 */

session_start();
require 'dbcon.php';  // Include database connection file

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Query to retrieve options where role is 'Principal Investigator'
$query = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result = $con->query($query);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Retrieve form data
    $cage_id = $_POST['cage_id'];
    $pi_name = $_POST['pi_name'];
    $strain = $_POST['strain'];
    $iacuc = $_POST['iacuc'];
    $user = $_POST['user'];
    $qty = $_POST['qty'];
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $parent_cg = $_POST['parent_cg'];
    $remarks = $_POST['remarks'];
    $mouse_data = [];

    // Collect mouse data
    for ($i = 1; $i <= 5; $i++) {
        $mouse_data[] = [
            'mouse_id' => $_POST["mouse_id_$i"] ?? null,
            'genotype' => $_POST["genotype_$i"] ?? null,
            'notes' => $_POST["notes_$i"] ?? null
        ];
    }

    // Check if the cage_id already exists in hc_basic or bc_basic
    $check_query_hc = "SELECT * FROM hc_basic WHERE cage_id = '$cage_id'";
    $check_query_bc = "SELECT * FROM bc_basic WHERE cage_id = '$cage_id'";
    $check_result_hc = mysqli_query($con, $check_query_hc);
    $check_result_bc = mysqli_query($con, $check_query_bc);

    if (mysqli_num_rows($check_result_hc) > 0 || mysqli_num_rows($check_result_bc) > 0) {
        // Cage_id already exists, throw an error
        $_SESSION['message'] = "Cage ID '$cage_id' already exists. Please use a different Cage ID.";
    } else {
        // Prepare the SQL statement with placeholders
        $query1 = "INSERT INTO hc_basic 
         (`cage_id`, `pi_name`, `strain`, `iacuc`, `user`, `qty`, `dob`, `sex`, `parent_cg`, `remarks`, 
         `mouse_id_1`, `genotype_1`, `notes_1`, `mouse_id_2`, `genotype_2`, `notes_2`, `mouse_id_3`, 
         `genotype_3`, `notes_3`, `mouse_id_4`, `genotype_4`, `notes_4`, `mouse_id_5`, `genotype_5`, `notes_5`) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($query1);

        // Bind parameters
        $stmt->bind_param(
            "sssssisssssssssssssssssss",
            $cage_id,
            $pi_name,
            $strain,
            $iacuc,
            $user,
            $qty,
            $dob,
            $sex,
            $parent_cg,
            $remarks,
            $mouse_data[0]['mouse_id'],
            $mouse_data[0]['genotype'],
            $mouse_data[0]['notes'],
            $mouse_data[1]['mouse_id'],
            $mouse_data[1]['genotype'],
            $mouse_data[1]['notes'],
            $mouse_data[2]['mouse_id'],
            $mouse_data[2]['genotype'],
            $mouse_data[2]['notes'],
            $mouse_data[3]['mouse_id'],
            $mouse_data[3]['genotype'],
            $mouse_data[3]['notes'],
            $mouse_data[4]['mouse_id'],
            $mouse_data[4]['genotype'],
            $mouse_data[4]['notes']
        );

        // Execute the statement
        $result1 = $stmt->execute();

        // Check if the insertion was successful
        if ($result1) {
            $_SESSION['message'] = "New holding cage added successfully.";
        } else {
            $_SESSION['message'] = "Failed to add new holding cage.";
        }

        // Close the prepared statement
        $stmt->close();
    }

    // Redirect back to the main page
    header("Location: hc_dash.php");
    exit();
}

require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>Add New Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        .container {
            max-width: 800px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: auto;
        }

        .form-label {
            font-weight: bold;
        }

        .required-asterisk {
            color: red;
        }

        .warning-text {
            color: #dc3545;
            font-size: 14px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to get today's date in YYYY-MM-DD format
            function getCurrentDate() {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            // Function to set the max date to today for all date input fields
            function setMaxDate() {
                const currentDate = getCurrentDate();
                const dateFields = document.querySelectorAll('input[type="date"]');
                dateFields.forEach(field => {
                    field.setAttribute('max', currentDate);
                });
            }

            // Initial call to set max date on page load
            setMaxDate();
        });

        // Function to go back to the previous page
        function goBack() {
            window.history.back();
        }

        // Function to show or hide mouse fields based on the quantity selected
        function showMouseFields() {
            var qty = document.getElementById('qty').value;
            for (var i = 1; i <= 5; i++) {
                document.getElementById('mouse_fields_' + i).style.display = i <= qty ? 'block' : 'none';
            }
        }

        // Function to adjust the height of the textarea dynamically
        function adjustTextareaHeight(element) {
            element.style.height = "auto";
            element.style.height = (element.scrollHeight) + "px";
        }

        // Function to validate date format & provide feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const dobInput = document.getElementById('dob');
            const warningText = document.createElement('span');
            warningText.style.color = 'red';
            warningText.style.display = 'none';
            dobInput.parentNode.appendChild(warningText);

            // Function to validate date
            function validateDate(dateString) {
                const regex = /^\d{4}-\d{2}-\d{2}$/;
                if (!dateString.match(regex)) return false;

                const date = new Date(dateString);
                const now = new Date();
                const year = date.getFullYear();

                // Check if the date is valid and within the range 1900-2099 and not in the future
                return date && !isNaN(date) && year >= 1900 && date <= now;
            }

            // Listen for input changes to provide immediate feedback
            dobInput.addEventListener('input', function() {
                const dobValue = dobInput.value;
                const isValidDate = validateDate(dobValue);
                if (!isValidDate) {
                    warningText.textContent = 'Invalid Date. Please enter a valid date.';
                    warningText.style.display = 'block';
                } else {
                    warningText.textContent = '';
                    warningText.style.display = 'none';
                }
            });

            // Prevent form submission if the date is invalid
            form.addEventListener('submit', function(event) {
                const dobValue = dobInput.value;
                if (!validateDate(dobValue)) {
                    event.preventDefault(); // Prevent form submission
                    warningText.textContent = 'Invalid Date. Please enter a valid date.';
                    warningText.style.display = 'block';
                    dobInput.focus();
                }
            });
        });
    </script>

</head>

<body>
    <div class="container mt-4" style="max-width: 800px; background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h4>Add New Holding Cage</h4>

        <?php include('message.php'); ?>

        <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>

        <form method="POST">

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="mb-3">
                <label for="cage_id" class="form-label">Cage ID <span class="required-asterisk">*</span></label>
                <input type="text" class="form-control" id="cage_id" name="cage_id" required>
            </div>

            <div class="mb-3">
                <label for="pi_name" class="form-label">PI Name <span class="required-asterisk">*</span></label>
                <select class="form-control" id="pi_name" name="pi_name" required>
                    <option value="" disabled selected>Select PI</option>
                    <?php
                    // Populate dropdown with options from the database
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['name'] . "'>" . $row['name'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="strain" class="form-label">Strain <span class="required-asterisk">*</span></label>
                <input type="text" class="form-control" id="strain" name="strain" required>
            </div>

            <div class="mb-3">
                <label for="iacuc" class="form-label">IACUC</label>
                <input type="text" class="form-control" id="iacuc" name="iacuc">
            </div>

            <div class="mb-3">
                <label for="user" class="form-label">User <span class="required-asterisk">*</span></label>
                <input type="text" class="form-control" id="user" name="user" required>
            </div>

            <div class="mb-3">
                <label for="qty" class="form-label">Qty <span class="required-asterisk">*</span></label>
                <select class="form-control" id="qty" name="qty" required onchange="showMouseFields()">
                    <option value="" disabled selected>Select Number</option>
                    <?php
                    // Generate options dynamically
                    for ($i = 0; $i <= 5; $i++) {
                        echo "<option value=\"$i\">$i</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="dob" class="form-label">DOB <span class="required-asterisk">*</span></label>
                <input type="date" class="form-control" id="dob" name="dob" required min="1900-01-01">
            </div>

            <div class="mb-3">
                <label for="sex" class="form-label">Sex <span class="required-asterisk">*</span></label>
                <select class="form-control" id="sex" name="sex" required>
                    <option value="" disabled selected>Select Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="parent_cg" class="form-label">Parent Cage <span class="required-asterisk">*</span></label>
                <input type="text" class="form-control" id="parent_cg" name="parent_cg" required>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" oninput="adjustTextareaHeight(this)"></textarea>
            </div>

            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <div id="mouse_fields_<?php echo $i; ?>" style="display: none;">
                    <h4>Mouse #<?php echo $i; ?></h4>
                    <div class="mb-3">
                        <label for="mouse_id_<?php echo $i; ?>" class="form-label">Mouse ID</label>
                        <input type="text" class="form-control" id="mouse_id_<?php echo $i; ?>" name="mouse_id_<?php echo $i; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="genotype_<?php echo $i; ?>" class="form-label">Genotype</label>
                        <input type="text" class="form-control" id="genotype_<?php echo $i; ?>" name="genotype_<?php echo $i; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="notes_<?php echo $i; ?>" class="form-label">Maintenance Notes</label>
                        <textarea class="form-control" id="notes_<?php echo $i; ?>" name="notes_<?php echo $i; ?>" oninput="adjustTextareaHeight(this)"></textarea>
                    </div>
                </div>
            <?php endfor; ?>

            <button type="submit" class="btn btn-primary">Add Cage</button>
            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

        </form>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>