<?php

/**
 * Edit Holding Cage Script
 * 
 * This script handles the editing of holding cage records, including mouse data. It allows for dynamic addition and deletion of mouse records.
 * 
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection file
require 'dbcon.php';

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$query1 = "SELECT id, initials, name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result1 = $con->query($query1);

// Query to retrieve IACUC values
$iacucQuery = "SELECT iacuc_id, iacuc_title FROM iacuc";
$iacucResult = $con->query($iacucQuery);

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

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the holding cage record with the specified ID including PI name details
    $query = "SELECT hc.*, pi.initials AS pi_initials, pi.name AS pi_full_name 
                FROM hc_basic hc 
                LEFT JOIN users pi ON hc.pi_name = pi.id 
                WHERE hc.cage_id = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch associated files for the holding cage
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Fetch the mouse data related to this cage
    $mouseQuery = "SELECT * FROM mouse WHERE cage_id = '$id'";
    $mouseResult = mysqli_query($con, $mouseQuery);
    $mice = mysqli_fetch_all($mouseResult, MYSQLI_ASSOC);

    // Check if the holding cage record exists
    if (mysqli_num_rows($result) === 1) {
        $holdingcage = mysqli_fetch_assoc($result);

        // If PI name is null, re-query the hc_basic table without the join
        if (is_null($holdingcage['pi_name'])) {
            $queryBasic = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
            $resultBasic = mysqli_query($con, $queryBasic);

            if (mysqli_num_rows($resultBasic) === 1) {
                $holdingcage = mysqli_fetch_assoc($resultBasic);
                $holdingcage['pi_initials'] = 'NA'; // Set empty initials
                $holdingcage['pi_name'] = 'NA'; // Set empty PI name
            } else {
                // If the re-query also fails, set an error message and redirect to the dashboard
                $_SESSION['message'] = 'Error fetching the cage details.';
                header("Location: hc_dash.php");
                exit();
            }
        }

        // Fetch currently selected users and explode them into an array
        $selectedUsers = explode(',', $holdingcage['user']);

        // Check if the logged-in user is the owner or an admin
        $currentUserId = $_SESSION['user_id']; // User ID from session
        $userRole = $_SESSION['role']; // User role from session
        $cageUsers = explode(',', $holdingcage['user']); // Array of user IDs associated with the cage

        // Check if the user is either an admin or one of the users associated with the cage
        if ($userRole !== 'admin' && !in_array($currentUserId, $cageUsers)) {
            $_SESSION['message'] = 'Access denied. Only the admin or the assigned user can edit.';
            header("Location: hc_dash.php");
            exit();
        }

        // Fetch currently selected PI
        $selectedPiId = $holdingcage['pi_name'];

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Validate CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die('CSRF token validation failed');
            }

            // Retrieve and sanitize form data
            $cage_id = mysqli_real_escape_string($con, $_POST['cage_id']);
            $pi_name = mysqli_real_escape_string($con, $_POST['pi_name']);
            $strain = mysqli_real_escape_string($con, $_POST['strain']);
            $iacuc = isset($_POST['iacuc']) ? implode(',', array_map(function ($value) use ($con) {
                return mysqli_real_escape_string($con, $value);
            }, $_POST['iacuc'])) : '';
            $user = isset($_POST['user']) ? implode(',', array_map(function ($user_id) use ($con) {
                return mysqli_real_escape_string($con, trim($user_id));
            }, $_POST['user'])) : '';
            $dob = mysqli_real_escape_string($con, $_POST['dob']);
            $sex = mysqli_real_escape_string($con, $_POST['sex']);
            $parent_cg = mysqli_real_escape_string($con, $_POST['parent_cg']);
            $remarks = mysqli_real_escape_string($con, $_POST['remarks']);

            // Update query for hc_basic table
            $updateQuery = "UPDATE hc_basic SET
                    `cage_id` = ?,
                    `pi_name` = ?,
                    `strain` = ?,
                    `iacuc` = ?,
                    `user` = ?,
                    `dob` = ?,
                    `sex` = ?,
                    `parent_cg` = ?,
                    `remarks` = ?
                    WHERE `cage_id` = ?";

            // Prepare the update query
            $stmt = $con->prepare($updateQuery);

            // Bind parameters to the prepared statement
            $stmt->bind_param(
                "ssssssssss",
                $cage_id,
                $pi_name,
                $strain,
                $iacuc,
                $user,
                $dob,
                $sex,
                $parent_cg,
                $remarks,
                $id
            );

            // Execute the prepared statement
            $result = $stmt->execute();

            // Update the mouse table with new data
            $mouse_ids = $_POST['mouse_id'] ?? [];
            $genotypes = $_POST['genotype'] ?? [];
            $notes = $_POST['notes'] ?? [];
            $existing_mouse_ids = $_POST['existing_mouse_id'] ?? [];

            // Handle existing and new mouse records
            for ($i = 0; $i < count($mouse_ids); $i++) {
                $mouse_id = mysqli_real_escape_string($con, $mouse_ids[$i]);
                $genotype = mysqli_real_escape_string($con, $genotypes[$i]);
                $note = mysqli_real_escape_string($con, $notes[$i]);
                $existing_mouse_id = isset($existing_mouse_ids[$i]) ? mysqli_real_escape_string($con, $existing_mouse_ids[$i]) : null;

                if (!empty($mouse_id)) {
                    if ($existing_mouse_id) {
                        // Update existing mouse record
                        $updateMouseQuery = "UPDATE mouse SET mouse_id = ?, genotype = ?, notes = ? WHERE id = ?";
                        $stmtMouseUpdate = $con->prepare($updateMouseQuery);
                        $stmtMouseUpdate->bind_param("sssi", $mouse_id, $genotype, $note, $existing_mouse_id);
                        $stmtMouseUpdate->execute();
                        $stmtMouseUpdate->close();
                    } else {
                        // Insert new mouse record
                        $insertMouseQuery = "INSERT INTO mouse (cage_id, mouse_id, genotype, notes) VALUES (?, ?, ?, ?)";
                        $stmtMouseInsert = $con->prepare($insertMouseQuery);
                        $stmtMouseInsert->bind_param("ssss", $cage_id, $mouse_id, $genotype, $note);
                        $stmtMouseInsert->execute();
                        $stmtMouseInsert->close();
                    }
                }
            }

            // Delete mouse records if marked for deletion
            if (!empty($_POST['mice_to_delete'])) {
                $miceToDelete = explode(',', $_POST['mice_to_delete']);
                foreach ($miceToDelete as $mouseId) {
                    $deleteMouseQuery = "DELETE FROM mouse WHERE id = ?";
                    $stmtMouseDelete = $con->prepare($deleteMouseQuery);
                    $stmtMouseDelete->bind_param("i", $mouseId);
                    $stmtMouseDelete->execute();
                    $stmtMouseDelete->close();
                }
            }

            // Update the qty field in hc_basic based on the number of mouse records
            $newQtyQuery = "SELECT COUNT(*) AS new_qty FROM mouse WHERE cage_id = ?";
            $stmtNewQty = $con->prepare($newQtyQuery);
            $stmtNewQty->bind_param("s", $cage_id);
            $stmtNewQty->execute();
            $stmtNewQty->bind_result($newQty);
            $stmtNewQty->fetch();
            $stmtNewQty->close();

            $updateQtyQuery = "UPDATE hc_basic SET qty = ? WHERE cage_id = ?";
            $stmtUpdateQty = $con->prepare($updateQtyQuery);
            $stmtUpdateQty->bind_param("is", $newQty, $cage_id);
            $stmtUpdateQty->execute();
            $stmtUpdateQty->close();

            // Check if the update was successful
            if ($result) {
                $_SESSION['message'] = 'Entry updated successfully.';
            } else {
                $_SESSION['message'] = 'Update failed: ' . $stmt->error;
            }

            // Close the prepared statement
            $stmt->close();

            // Handle file upload
            if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == UPLOAD_ERR_OK) {
                $targetDirectory = "uploads/$cage_id/"; // Define the target directory

                // Create the cage_id specific sub-directory if it doesn't exist
                if (!file_exists($targetDirectory)) {
                    if (!mkdir($targetDirectory, 0777, true)) {
                        $_SESSION['message'] .= " Failed to create directory.";
                        exit;
                    }
                }

                $originalFileName = basename($_FILES['fileUpload']['name']);
                $targetFilePath = $targetDirectory . $originalFileName;
                $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

                // Check if file already exists
                if (!file_exists($targetFilePath)) {
                    if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $targetFilePath)) {
                        // Insert file info into the database
                        $insert = $con->prepare("INSERT INTO files (file_name, file_path, cage_id) VALUES (?, ?, ?)");
                        $insert->bind_param("sss", $originalFileName, $targetFilePath, $cage_id);
                        if ($insert->execute()) {
                            $_SESSION['message'] .= " File uploaded successfully.";
                        } else {
                            $_SESSION['message'] .= " File upload failed, please try again.";
                        }
                    } else {
                        $_SESSION['message'] .= " Sorry, there was an error uploading your file.";
                    }
                } else {
                    $_SESSION['message'] .= " Sorry, file already exists.";
                }
            }

            // Redirect to the dashboard page
            header("Location: hc_dash.php");
            exit();
        }
    } else {
        // Set an error message if the ID is invalid
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: hc_dash.php");
        exit();
    }
} else {
    // Set an error message if the ID parameter is missing
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: hc_dash.php");
    exit();
}

function getUserDetailsByIds($con, $userIds)
{
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $query = "SELECT id, initials, name FROM users WHERE id IN ($placeholders)";
    $stmt = $con->prepare($query);
    $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
    $stmt->execute();
    $result = $stmt->get_result();
    $userDetails = [];
    while ($row = $result->fetch_assoc()) {
        $userDetails[$row['id']] = htmlspecialchars($row['initials'] . ' [' . $row['name'] . ']');
    }
    $stmt->close();
    return $userDetails;
}

// Fetch currently selected PI details
$piDetails = getUserDetailsByIds($con, [$selectedPiId]);
$piDisplay = isset($piDetails[$selectedPiId]) ? $piDetails[$selectedPiId] : 'Unknown PI';

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>Edit Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

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
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: bold;
        }

        .btn-primary {
            margin-right: 10px;
        }

        .table-wrapper {
            margin-bottom: 50px;
            overflow-x: auto;
        }

        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper th,
        .table-wrapper td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .btn-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-icon i {
            font-size: 16px;
            margin: 0;
        }

        .fixed-width th,
        .fixed-width td {
            width: 30%;
        }

        .fixed-width th:nth-child(2),
        .fixed-width td:nth-child(2) {
            width: 70%;
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
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 10px;
            padding-left: 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 35px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            /* Adjust the gap as needed */
            margin-top: 10px;
        }

        @media (max-width: 768px) {

            .table-wrapper th,
            .table-wrapper td {
                padding: 12px 8px;
                text-align: center;
            }
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

        // Function to dynamically add new mouse fields
        function addMouseField() {
            const mouseContainer = document.getElementById('mouse_fields_container');
            const mouseCount = mouseContainer.childElementCount + 1;

            const mouseFieldHTML = `
                <div id="mouse_fields_${mouseCount}" class="mouse-field">
                    <br>
                    <h4>Mouse #${mouseCount}</h4>
                    <div class="mb-3">
                        <label for="mouse_id_${mouseCount}" class="form-label">Mouse ID</label>
                        <input type="text" class="form-control" id="mouse_id_${mouseCount}" name="mouse_id[]" value="">
                    </div>

                    <div class="mb-3">
                        <label for="genotype_${mouseCount}" class="form-label">Genotype</label>
                        <input type="text" class="form-control" id="genotype_${mouseCount}" name="genotype[]" value="">
                    </div>

                    <div class="mb-3">
                        <label for="notes_${mouseCount}" class="form-label">Maintenance Notes</label>
                        <textarea class="form-control" id="notes_${mouseCount}" name="notes[]" oninput="adjustTextareaHeight(this)"></textarea>
                    </div>

                    <button type="button" class="btn btn-danger btn-icon" onclick="markForDeletion(${mouseCount}, null)"><i class="fas fa-trash"></i></button>
                </div>`;

            mouseContainer.insertAdjacentHTML('beforeend', mouseFieldHTML);
        }

        // Function to mark mouse fields for deletion
        function markForDeletion(mouseIndex, mouseId) {
            const mouseField = document.getElementById(`mouse_fields_${mouseIndex}`);
            mouseField.style.display = 'none'; // Hide the field visually

            // Add the mouse ID to the hidden input for deletion tracking
            if (mouseId !== null) {
                const miceToDeleteInput = document.getElementById('mice_to_delete');
                let miceToDelete = miceToDeleteInput.value ? miceToDeleteInput.value.split(',') : [];
                miceToDelete.push(mouseId);
                miceToDeleteInput.value = miceToDelete.join(',');
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

            $('#iacuc').select2({
                placeholder: "Select IACUC",
                allowClear: true,
                templateResult: function(data) {
                    if (!data.id) {
                        return data.text;
                    }
                    var $result = $('<span>' + data.text + '</span>');
                    $result.attr('title', data.element.title);
                    return $result;
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const piSelect = document.getElementById('pi_name');
                const selectedPiText = piSelect.options[piSelect.selectedIndex].text;

                // Check if "Unknown PI" is selected
                if (selectedPiText.includes('Unknown PI')) {
                    event.preventDefault(); // Prevent form submission
                    alert('Cannot proceed with "Unknown PI". Please select a valid PI.');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const strainSelect = document.getElementById('strain');
                const selectedStrainText = strainSelect.options[strainSelect.selectedIndex].text;

                // Check if "Unknown Strain" is selected
                if (selectedStrainText.includes('Unknown Strain')) {
                    event.preventDefault(); // Prevent form submission
                    alert('Cannot proceed with "Unknown Strain". Please select a valid Strain.');
                }
            });
        });
    </script>

</head>

<body>
    <div class="container content mt-4">

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Holding Cage</h4>
                        <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="hc_edit.php?id=<?= $id; ?>" enctype="multipart/form-data">

                            <input type="hidden" id="mice_to_delete" name="mice_to_delete" value="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id" value="<?= htmlspecialchars($holdingcage['cage_id']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="pi_name" class="form-label">PI Name <span class="required-asterisk">*</span></label>
                                <select class="form-control" id="pi_name" name="pi_name" required>
                                    <!-- Display the currently selected PI, with the option to disable if "Unknown PI" -->
                                    <option value="<?= htmlspecialchars($selectedPiId); ?>" <?= ($piDisplay === 'Unknown PI') ? 'disabled' : '' ?> selected>
                                        <?= htmlspecialchars($piDisplay); ?>
                                    </option>
                                    <!-- Iterate through the PI options, skipping the selected one -->
                                    <?php while ($row = $result1->fetch_assoc()) : ?>
                                        <?php if ($row['id'] !== $selectedPiId) : ?>
                                            <option value="<?= htmlspecialchars($row['id']); ?>">
                                                <?= htmlspecialchars($row['initials']) . ' [' . htmlspecialchars($row['name']) . ']'; ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="strain" class="form-label">Strain <span class="required-asterisk">*</span></label>
                                <select class="form-control" id="strain" name="strain" required>
                                    <option value="" disabled>Select Strain</option>
                                    <?php
                                    // Initialize a flag to check if the current strain exists in the options
                                    $strainExists = false;

                                    // Populate the dropdown with options
                                    foreach ($strainOptions as $option) {
                                        $value = explode(" | ", $option)[0]; // Extract str_id
                                        $selected = ($value == $holdingcage['strain']) ? 'selected' : '';

                                        if ($value == $holdingcage['strain']) {
                                            $strainExists = true;
                                        }

                                        echo "<option value='$value' $selected>$option</option>";
                                    }

                                    // If the current strain is not in the list, add it as a separate option
                                    if (!$strainExists && !empty($holdingcage['strain'])) {
                                        $currentStrainId = htmlspecialchars($holdingcage['strain']);
                                        echo "<option value='$currentStrainId' disabled selected>$currentStrainId | Unknown Strain</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="iacuc" class="form-label">IACUC</label>
                                <select class="form-control" id="iacuc" name="iacuc[]" multiple>
                                    <option value="" disabled>Select IACUC</option>
                                    <?php
                                    // Retrieve selected IACUC values
                                    $selectedIacucs = isset($holdingcage['iacuc']) ? explode(',', $holdingcage['iacuc']) : [];

                                    // Check if there are any IACUC values from the database
                                    if ($iacucResult->num_rows > 0) {
                                        // Populate the dropdown with IACUC values from the database
                                        while ($iacucRow = $iacucResult->fetch_assoc()) {
                                            $iacuc_id = htmlspecialchars($iacucRow['iacuc_id']);
                                            $iacuc_title = htmlspecialchars($iacucRow['iacuc_title']);
                                            $truncated_title = strlen($iacuc_title) > 40 ? substr($iacuc_title, 0, 40) . '...' : $iacuc_title;
                                            $selected = in_array($iacuc_id, $selectedIacucs) ? 'selected' : '';
                                            echo "<option value='$iacuc_id' title='$iacuc_title' $selected>$iacuc_id | $truncated_title</option>";
                                        }
                                    } else {
                                        // Show an empty option if there are no IACUC values
                                        echo "<option value='' disabled>No IACUC available</option>";
                                    }
                                    ?>
                                </select>
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
                                        $selected = in_array($user_id, $selectedUsers) ? 'selected' : '';
                                        echo "<option value='$user_id' $selected>$initials [$name]</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="dob" class="form-label">DOB <span class="required-asterisk">*</span></label>
                                <input type="date" class="form-control" id="dob" name="dob" value="<?= htmlspecialchars($holdingcage['dob']); ?>" required min="1900-01-01">
                            </div>

                            <div class="mb-3">
                                <label for="sex" class="form-label">Sex <span class="required-asterisk">*</span></label>
                                <select class="form-control" id="sex" name="sex" required>
                                    <option value="<?= htmlspecialchars($holdingcage['sex']); ?>" selected>
                                        <?= htmlspecialchars($holdingcage['sex']); ?>
                                    </option>
                                    <?php
                                    if ($holdingcage['sex'] != 'Male') {
                                        echo "<option value='Male'>Male</option>";
                                    }
                                    if ($holdingcage['sex'] != 'Female') {
                                        echo "<option value='Female'>Female</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="parent_cg" class="form-label">Parent Cage <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="parent_cg" name="parent_cg" value="<?= htmlspecialchars($holdingcage['parent_cg']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" oninput="adjustTextareaHeight(this)"><?= htmlspecialchars($holdingcage['remarks']); ?></textarea>
                            </div>

                            <!-- Separator -->
                            <hr class="mt-4 mb-4" style="border-top: 3px solid #000;">

                            <!-- HTML Form Section for Mouse Fields -->
                            <div id="mouse_fields_container">
                                <!-- Loop to dynamically create fields for each mouse -->
                                <?php foreach ($mice as $i => $mouse) : ?>
                                    <div id="mouse_fields_<?= $i + 1 ?>" class="mouse-field" data-mouse-id="<?= $mouse['id'] ?>">
                                        <br>
                                        <h4>Mouse #<?= $i + 1 ?></h4>
                                        <input type="hidden" name="existing_mouse_id[]" value="<?= htmlspecialchars($mouse['id']) ?>">
                                        <div class="mb-3">
                                            <label for="mouse_id_<?= $i + 1 ?>" class="form-label">Mouse ID</label>
                                            <input type="text" class="form-control" id="mouse_id_<?= $i + 1 ?>" name="mouse_id[]" value="<?= htmlspecialchars($mouse['mouse_id']) ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="genotype_<?= $i + 1 ?>" class="form-label">Genotype</label>
                                            <input type="text" class="form-control" id="genotype_<?= $i + 1 ?>" name="genotype[]" value="<?= htmlspecialchars($mouse['genotype']) ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="notes_<?= $i + 1 ?>" class="form-label">Maintenance Notes</label>
                                            <textarea class="form-control" id="notes_<?= $i + 1 ?>" name="notes[]" oninput="adjustTextareaHeight(this)"><?= htmlspecialchars($mouse['notes']) ?></textarea>
                                        </div>

                                        <div class="button-group">
                                            <button type="button" class="btn btn-danger btn-icon" onclick="markForDeletion(<?= $i + 1 ?>, <?= $mouse['id'] ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Button to add new mouse fields -->
                            <div class="button-group">
                                <button type="button" class="btn btn-primary" onclick="addMouseField()">
                                    <i class="fas fa-plus"></i> Add Mouse
                                </button>
                            </div>

                            <!-- Separator -->
                            <hr class="mt-4 mb-4" style="border-top: 3px solid #000;">

                            <!-- Display Files Section -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4>Manage Files</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>File Name</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($file = $files->fetch_assoc()) {
                                                    $file_path = htmlspecialchars($file['file_path']);
                                                    $file_name = htmlspecialchars($file['file_name']);
                                                    $file_id = intval($file['id']);

                                                    echo "<tr>";
                                                    echo "<td>$file_name</td>";
                                                    echo "<td>
                                                    <a href='$file_path' download='$file_name' class='btn btn-sm btn-outline-primary'><i class='fas fa-cloud-download-alt fa-sm'></i></a>
                                                    <a href='delete_file.php?url=hc_edit&id=$file_id' class='btn-sm' onclick='return confirm(\"Are you sure you want to delete this file?\");' aria-label='Delete $file_name'><i class='fas fa-trash fa-sm' style='color:red'></i></a>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Files Section -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4>Upload New File</h4>
                                </div>
                                <div class="card-body">
                                    <div class="input-group mb-3">
                                        <input type="file" class="form-control" id="fileUpload" name="fileUpload">
                                    </div>
                                </div>
                            </div>

                            <br>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="goBack()">Go Back</button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <?php include 'footer.php'; ?>
</body>

</html>