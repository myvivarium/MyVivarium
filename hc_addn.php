<?php

/**
 * Add New Holding Cage Script
 * 
 * This script handles the creation of new holding cages and includes functionalities to dynamically add or remove mouse data.
 * 
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if the user is not logged in, redirect them to index.php with the current URL for redirection after login
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Generate a CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Query to retrieve users with initials and names
$userQuery = "SELECT id, initials, name FROM users WHERE status = 'approved'";
$userResult = $con->query($userQuery);

// Query to retrieve options where role is 'Principal Investigator'
$piQuery = "SELECT id, initials, name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$piResult = $con->query($piQuery);

// Query to retrieve strain details including common names
$strainQuery = "SELECT str_id, str_name, str_aka FROM strain";
$strainResult = $con->query($strainQuery);

// Initialize an array to hold all options
$strainOptions = [];

// Process each row to generate options
while ($strainrow = $strainResult->fetch_assoc()) {
    $str_id = htmlspecialchars($strainrow['str_id'] ?? 'Unknown');
    $str_name = htmlspecialchars($strainrow['str_name'] ?? 'Unnamed Strain');
    $str_aka = $strainrow['str_aka'] ? htmlspecialchars($strainrow['str_aka']) : '';

    // Add the main strain option
    $strainOptions[] = "$str_id | $str_name";

    // Explode the common names if they exist
    if (!empty($str_aka)) {
        $akaNames = explode(', ', $str_aka);
        foreach ($akaNames as $aka) {
            $strainOptions[] = "$str_id | " . htmlspecialchars(trim($aka));
        }
    }
}

// Sort the options based on str_id
sort($strainOptions, SORT_STRING);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Retrieve form data
    $cage_id = mysqli_real_escape_string($con, $_POST['cage_id']);
    $pi_name = mysqli_real_escape_string($con, $_POST['pi_name']);
    $strain = mysqli_real_escape_string($con, $_POST['strain']);
    $iacuc = mysqli_real_escape_string($con, $_POST['iacuc']);
    $user = isset($_POST['user']) ? implode(',', array_map('trim', $_POST['user'])) : '';
    $dob = mysqli_real_escape_string($con, $_POST['dob']);
    $sex = mysqli_real_escape_string($con, $_POST['sex']);
    $parent_cg = mysqli_real_escape_string($con, $_POST['parent_cg']);
    $remarks = mysqli_real_escape_string($con, $_POST['remarks']);
    $mouse_data = [];

    // Collect mouse data
    $mouse_ids = $_POST['mouse_id'] ?? [];
    $genotypes = $_POST['genotype'] ?? [];
    $notes = $_POST['notes'] ?? [];

    for ($i = 0; $i < count($mouse_ids); $i++) {
        $mouse_id = $mouse_ids[$i];
        $genotype = $genotypes[$i];
        $note = $notes[$i];

        if (!empty(trim($mouse_id))) {
            $mouse_data[] = [
                'mouse_id' => mysqli_real_escape_string($con, $mouse_id),
                'genotype' => mysqli_real_escape_string($con, $genotype),
                'notes' => mysqli_real_escape_string($con, $note)
            ];
        }
    }

    $qty = count($mouse_data); // Calculate the quantity based on the number of mouse records

    // Check if the cage_id already exists in hc_basic or bc_basic
    $check_query_hc = "SELECT * FROM hc_basic WHERE cage_id = '$cage_id'";
    $check_query_bc = "SELECT * FROM bc_basic WHERE cage_id = '$cage_id'";
    $check_result_hc = mysqli_query($con, $check_query_hc);
    $check_result_bc = mysqli_query($con, $check_query_bc);

    if (mysqli_num_rows($check_result_hc) > 0 || mysqli_num_rows($check_result_bc) > 0) {
        // Cage_id already exists, throw an error
        $_SESSION['message'] = "Cage ID '$cage_id' already exists. Please use a different Cage ID.";
    } else {
        // Insert data into hc_basic table
        $query1 = "INSERT INTO hc_basic 
         (`cage_id`, `pi_name`, `strain`, `iacuc`, `user`, `qty`, `dob`, `sex`, `parent_cg`, `remarks`) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($query1);
        $stmt->bind_param(
            "sssssissss",
            $cage_id,
            $pi_name,
            $strain,
            $iacuc,
            $user,
            $qty,
            $dob,
            $sex,
            $parent_cg,
            $remarks
        );

        // Execute the statement
        $result1 = $stmt->execute();

        if ($result1) {
            // Insert mouse data into mouse table
            foreach ($mouse_data as $mouse) {
                $query2 = "INSERT INTO mouse (cage_id, mouse_id, genotype, notes) VALUES (?, ?, ?, ?)";
                $stmt2 = $con->prepare($query2);
                $stmt2->bind_param("ssss", $cage_id, $mouse['mouse_id'], $mouse['genotype'], $mouse['notes']);
                $stmt2->execute();
                $stmt2->close();
            }
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

    <!-- Include Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">

    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet">

    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

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

        .select2-container .select2-selection--single {
            height: 35px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            /* Adjust the gap as needed */
            margin-top: 10px;
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

        // Define a counter to keep track of the mouse fields
        let mouseFieldCounter = 0;

        // Function to dynamically add new mouse fields
        function addMouseField() {
            const mouseContainer = document.getElementById('mouse_fields_container');

            // Increment the counter
            mouseFieldCounter++;

            // Create new mouse field HTML
            const mouseFieldHTML = `
    <div id="mouse_fields_${mouseFieldCounter}" class="mouse-field">
        <br>
        <h4>Mouse #${mouseFieldCounter}</h4>
        <div class="mb-3">
            <label for="mouse_id_${mouseFieldCounter}" class="form-label">Mouse ID</label>
            <input type="text" class="form-control" id="mouse_id_${mouseFieldCounter}" name="mouse_id[]">
        </div>

        <div class="mb-3">
            <label for="genotype_${mouseFieldCounter}" class="form-label">Genotype</label>
            <input type="text" class="form-control" id="genotype_${mouseFieldCounter}" name="genotype[]">
        </div>

        <div class="mb-3">
            <label for="notes_${mouseFieldCounter}" class="form-label">Maintenance Notes</label>
            <textarea class="form-control" id="notes_${mouseFieldCounter}" name="notes[]" oninput="adjustTextareaHeight(this)"></textarea>
        </div>

        <div class="button-group">
            <button type="button" class="btn btn-danger btn-icon" onclick="removeMouseField(${mouseFieldCounter})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>`;

            // Add new mouse field to the container
            mouseContainer.insertAdjacentHTML('beforeend', mouseFieldHTML);
        }

        // Function to remove mouse fields
        function removeMouseField(mouseIndex) {
            const mouseField = document.getElementById(`mouse_fields_${mouseIndex}`);
            if (mouseField) {
                mouseField.remove(); // Remove the field from the DOM
            }
        }

        // Function to adjust the height of textareas dynamically
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

        $(document).ready(function() {
            $('#user').select2({
                placeholder: "Select User(s)",
                allowClear: true
            });
        });

        $(document).ready(function() {
            $('#strain').select2({
                placeholder: "Select Strain",
                allowClear: true
            });
        });
    </script>

</head>

<body>
    <div class="container mt-4 content">

        <h4>Add New Holding Cage</h4>

        <?php include('message.php'); ?>

        <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>

        <form method="POST">

            <!-- CSRF token field -->
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
                    while ($row = $piResult->fetch_assoc()) {
                        $pi_id = htmlspecialchars($row['id']);
                        $pi_initials = htmlspecialchars($row['initials']);
                        $pi_name = htmlspecialchars($row['name']);
                        echo "<option value='$pi_id'>$pi_initials [$pi_name]</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="strain" class="form-label">Strain <span class="required-asterisk">*</span></label>
                <select class="form-control" id="strain" name="strain" required>
                    <option value="" disabled selected>Select Strain</option>
                    <?php
                    // Populate the dropdown with all the options generated
                    foreach ($strainOptions as $option) {
                        echo "<option value='" . explode(" | ", $option)[0] . "'>$option</option>";
                    }
                    ?>
                </select>
            </div>


            <div class="mb-3">
                <label for="iacuc" class="form-label">IACUC</label>
                <input type="text" class="form-control" id="iacuc" name="iacuc">
            </div>

            <div class="mb-3">
                <label for="user" class="form-label">User <span class="required-asterisk">*</span></label>
                <select class="form-control" id="user" name="user[]" multiple required>
                    <?php
                    // Populate the dropdown with options from the database
                    while ($userRow = $userResult->fetch_assoc()) {
                        $user_id = htmlspecialchars($userRow['id']);
                        $initials = htmlspecialchars($userRow['initials']);
                        $name = htmlspecialchars($userRow['name']);
                        echo "<option value='$user_id'>$initials [$name]</option>";
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

            <!-- HTML Form Section for Mouse Fields -->
            <div id="mouse_fields_container">
                <!-- Mouse fields will be added here dynamically -->
            </div>

            <!-- Button to add new mouse fields -->
            <div class="button-group">
                <button type="button" class="btn btn-primary btn-icon" onclick="addMouseField()">
                    <i class="fas fa-plus"></i> Add Mouse
                </button>
            </div>

            <br>
            <br>

            <button type="submit" class="btn btn-primary">Add Cage</button>
            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

        </form>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>