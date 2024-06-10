<?php

/**
 * Add New Breeding Cage Script
 *
 * This script handles the creation of new breeding cages in a laboratory management system. It starts a session,
 * regenerates the session ID to prevent session fixation attacks, and checks if the user is logged in.
 * It generates a CSRF token for form submissions, retrieves a list of Principal Investigators (PIs),
 * and processes the form submission for adding a new breeding cage. The script also includes the functionality
 * to add litter data associated with the breeding cage.
 *
 * Author: [Your Name]
 * Date: [Date]
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

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Query to retrieve users with initials and names
$userQuery = "SELECT initials, name FROM users WHERE status = 'approved'";
$userResult = $con->query($userQuery);

// Query to retrieve options where role is 'Principal Investigator'
$query = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result = $con->query($query);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Retrieve and sanitize form data
    $cage_id = $_POST['cage_id'];
    $pi_name = $_POST['pi_name'];
    $cross = $_POST['cross'];
    $iacuc = $_POST['iacuc'];
    $user = $_POST['user'];
    $male_id = $_POST['male_id'];
    $female_id = $_POST['female_id'];
    $male_dob = $_POST['male_dob'];
    $female_dob = $_POST['female_dob'];
    $remarks = $_POST['remarks'];

    // Check if the cage_id already exists in either bc_basic or hc_basic
    $check_query_bc = $con->prepare("SELECT * FROM bc_basic WHERE cage_id = ?");
    $check_query_bc->bind_param("s", $cage_id);
    $check_query_bc->execute();
    $check_result_bc = $check_query_bc->get_result();

    $check_query_hc = $con->prepare("SELECT * FROM hc_basic WHERE cage_id = ?");
    $check_query_hc->bind_param("s", $cage_id);
    $check_query_hc->execute();
    $check_result_hc = $check_query_hc->get_result();

    if ($check_result_bc->num_rows > 0 || $check_result_hc->num_rows > 0) {
        // Set an error message if cage_id already exists
        $_SESSION['message'] = "Cage ID '$cage_id' already exists. Please use a different Cage ID.";
    } else {
        // Prepare the insert query with placeholders
        $insert_query = $con->prepare("INSERT INTO bc_basic (`cage_id`, `pi_name`, `cross`, `iacuc`, `user`, `male_id`, `female_id`, `male_dob`, `female_dob`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind parameters to the query
        $insert_query->bind_param("ssssssssss", $cage_id, $pi_name, $cross, $iacuc, $user, $male_id, $female_id, $male_dob, $female_dob, $remarks);

        // Execute the statement and check if it was successful
        if ($insert_query->execute()) {
            // Set a success message
            $_SESSION['message'] = "New breeding cage added successfully.";

            // Handle litter data insertion if provided
            if (isset($_POST['dom'])) {
                $dom = $_POST['dom'];
                $litter_dob = $_POST['litter_dob'];
                $pups_alive = $_POST['pups_alive'];
                $pups_dead = $_POST['pups_dead'];
                $pups_male = $_POST['pups_male'];
                $pups_female = $_POST['pups_female'];
                $litter_remarks = $_POST['remarks'];

                // Loop through each litter entry and insert into the database
                for ($i = 0; $i < count($dom); $i++) {
                    $insert_litter_query = $con->prepare("INSERT INTO bc_litter (`cage_id`, `dom`, `litter_dob`, `pups_alive`, `pups_dead`, `pups_male`, `pups_female`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                    // Bind parameters for each litter entry
                    $insert_litter_query->bind_param("ssssssss", $cage_id, $dom[$i], $litter_dob[$i], $pups_alive[$i], $pups_dead[$i], $pups_male[$i], $pups_female[$i], $litter_remarks[$i]);

                    // Execute the statement and check if it was successful
                    if ($insert_litter_query->execute()) {
                        // Append success message for litter data
                        $_SESSION['message'] .= " Litter data added successfully.";
                    } else {
                        // Append error message for litter data
                        $_SESSION['message'] .= " Failed to add litter data: " . $insert_litter_query->error;
                    }

                    // Close the prepared statement for litter data
                    $insert_litter_query->close();
                }
            }
        } else {
            // Set an error message if the cage insertion failed
            $_SESSION['message'] = "Failed to add new breeding cage.";
        }

        // Close the prepared statement for cage data
        $insert_query->close();
    }

    // Close the check query prepared statements
    $check_query_bc->close();
    $check_query_hc->close();

    // Redirect back to the main page
    header("Location: bc_dash.php");
    exit();
}

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>Add New Breeding Cage | <?php echo htmlspecialchars($labName); ?></title>
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

            // Function to dynamically add new litter entry
            function addLitter() {
                const litterDiv = document.createElement('div');
                litterDiv.className = 'litter-entry';

                litterDiv.innerHTML = `
                <hr>
                <div class="mb-3">
                    <label for="dom[]" class="form-label">DOM <span class="required-asterisk">*</span></label>
                    <input type="date" class="form-control" name="dom[]" required min="1900-01-01">
                </div>
                <div class="mb-3">
                    <label for="litter_dob[]" class="form-label">Litter DOB <span class="required-asterisk">*</span></label>
                    <input type="date" class="form-control" name="litter_dob[]" required min="1900-01-01">
                </div>
                <div class="mb-3">
                    <label for="pups_alive[]" class="form-label">Pups Alive <span class="required-asterisk">*</span></label>
                    <input type="number" class="form-control" name="pups_alive[]" required min="0" step="1">
                </div>
                <div class="mb-3">
                    <label for="pups_dead[]" class="form-label">Pups Dead <span class="required-asterisk">*</span></label>
                    <input type="number" class="form-control" name="pups_dead[]" required min="0" step="1">
                </div>
                <div class="mb-3">
                    <label for="pups_male[]" class="form-label">Pups Male</label>
                    <input type="number" class="form-control" name="pups_male[]" min="0" step="1">
                </div>
                <div class="mb-3">
                    <label for="pups_female[]" class="form-label">Pups Female</label>
                    <input type="number" class="form-control" name="pups_female[]" min="0" step="1">
                </div>
                <div class="mb-3">
                    <label for="remarks[]" class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks[]" oninput="adjustTextareaHeight(this)"></textarea>
                </div>
                <button type="button" class="btn btn-danger" onclick="removeLitter(this)">Remove</button>
            `;

                document.getElementById('litterEntries').appendChild(litterDiv);

                // Apply max date to new date fields
                setMaxDate();
            }

            // Function to adjust the height of the textarea dynamically
            function adjustTextareaHeight(element) {
                element.style.height = "auto";
                element.style.height = (element.scrollHeight) + "px";
            }

            // Function to remove a litter entry dynamically
            function removeLitter(element) {
                element.parentElement.remove();
            }

            // Ensure the function addLitter is available globally
            window.addLitter = addLitter;
        });

        // Function to navigate back to the previous page
        function goBack() {
            window.history.back();
        }

        // Function to validate date format & provide feedback
        document.addEventListener('DOMContentLoaded', function() {
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

            // Function to attach event listeners to date fields
            function attachDateValidation() {
                const dateFields = document.querySelectorAll('input[type="date"]');
                dateFields.forEach(field => {
                    if (!field.dataset.validated) { // Check if already validated
                        const warningText = document.createElement('span');
                        warningText.style.color = 'red';
                        warningText.style.display = 'none';
                        field.parentNode.appendChild(warningText);

                        field.addEventListener('input', function() {
                            const dateValue = field.value;
                            const isValidDate = validateDate(dateValue);
                            if (!isValidDate) {
                                warningText.textContent = 'Invalid Date. Please enter a valid date.';
                                warningText.style.display = 'block';
                            } else {
                                warningText.textContent = '';
                                warningText.style.display = 'none';
                            }
                        });

                        // Mark the field as validated
                        field.dataset.validated = 'true';
                    }
                });
            }

            // Initial call to validate existing date fields
            attachDateValidation();

            // Observe the form for changes (e.g., new nodes added dynamically)
            const form = document.querySelector('form');
            const observer = new MutationObserver(() => {
                attachDateValidation(); // Reattach validation to new nodes
            });

            // Start observing the form
            observer.observe(form, {
                childList: true,
                subtree: true
            });

            // Prevent form submission if dates are invalid
            form.addEventListener('submit', function(event) {
                let isValid = true;
                const dateFields = document.querySelectorAll('input[type="date"]');
                dateFields.forEach(field => {
                    const dateValue = field.value;
                    const warningText = field.nextElementSibling;
                    if (!validateDate(dateValue)) {
                        warningText.textContent = 'Invalid Date. Please enter a valid date.';
                        warningText.style.display = 'block';
                        isValid = false;
                    }
                });
                if (!isValid) {
                    event.preventDefault(); // Prevent form submission if any date is invalid
                }
            });
        });
    </script>
</head>

<body>

    <div class="container mt-4">
        <h4>Add New Breeding Cage</h4>

        <?php include('message.php'); ?>

        <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>

        <form method="POST">

            <!-- CSRF token field -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

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
                        echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="cross" class="form-label">Cross <span class="required-asterisk">*</span></label>
                <input type="text" class="form-control" id="cross" name="cross" required>
            </div>

            <div class="mb-3">
                <label for="iacuc" class="form-label">IACUC</label>
                <input type="text" class="form-control" id="iacuc" name="iacuc">
            </div>

            <div class="mb-3">
                <label for="user" class="form-label">User <span class="required-asterisk">*</span></label>
                <select class="form-control" id="user" name="user" required>
                    <option value="" disabled selected>Select User</option>
                    <?php
                    // Populate the dropdown with options from the database
                    while ($userRow = $userResult->fetch_assoc()) {
                        $initials = htmlspecialchars($userRow['initials']);
                        $name = htmlspecialchars($userRow['name']);
                        echo "<option value='$initials'>$initials ! $name</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="male_id" class="form-label">Male ID <span class="required-asterisk">*</span></label>
                <input type="text" class="form-control" id="male_id" name="male_id" required>
            </div>

            <div class="mb-3">
                <label for="female_id" class="form-label">Female ID <span class="required-asterisk">*</span></label>
                <input type="text" class="form-control" id="female_id" name="female_id" required>
            </div>

            <div class="mb-3">
                <label for="male_dob" class="form-label">Male DOB <span class="required-asterisk">*</span></label>
                <input type="date" class="form-control" id="male_dob" name="male_dob" required min="1900-01-01">
            </div>

            <div class="mb-3">
                <label for="female_dob" class="form-label">Female DOB <span class="required-asterisk">*</span></label>
                <input type="date" class="form-control" id="female_dob" name="female_dob" required min="1900-01-01">
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" oninput="adjustTextareaHeight(this)"></textarea>
            </div>

            <!-- Litter Data Section -->
            <div class="mt-4">
                <h5>Litter Data</h5>
                <div id="litterEntries">
                    <!-- Litter entries will be added here dynamically -->
                </div>
                <button type="button" class="btn btn-success mt-3" onclick="addLitter()">Add Litter Entry</button>
            </div>

            <br>

            <button type="submit" class="btn btn-primary">Add Cage</button>
            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

        </form>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>