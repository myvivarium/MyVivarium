<?php

/**
 * Edit Breeding Cage Script
 *
 * This script handles the editing of a breeding cage and its related data. It starts a session, checks if the user is logged in,
 * retrieves existing cage data, processes form submissions for updating the cage and litter information, and handles file uploads.
 * It also ensures security by regenerating session IDs and validating CSRF tokens.
 */

// Start a new session or resume the existing session
session_start();

// Include the database connection
require 'dbcon.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit; // Exit to ensure no further code is executed
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCurrentUrlParams() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = isset($_GET['search']) ? urlencode($_GET['search']) : '';
    return "page=$page&search=$search";
}

// Query to retrieve users with initials and names
$userQuery = "SELECT id, initials, name FROM users WHERE status = 'approved'";
$userResult = $con->query($userQuery);

// Query to retrieve options where role is 'Principal Investigator'
$query1 = "SELECT id, initials, name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result1 = $con->query($query1);

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breeding cage record with the specified ID including PI name details
    $query = "SELECT b.*, c.remarks AS remarks, c.pi_name AS pi_name
          FROM breeding b
          LEFT JOIN cages c ON b.cage_id = c.cage_id
          WHERE b.cage_id = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch files associated with the specified cage ID
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Fetch the breeding cage litter record with the specified ID
    $query3 = "SELECT * FROM litters WHERE `cage_id` = '$id'";
    $litters = mysqli_query($con, $query3);

    // Query to retrieve IACUC values
    $iacucQuery = "SELECT iacuc_id, iacuc_title FROM iacuc";
    $iacucResult = $con->query($iacucQuery);

    // Check if the breeding cage exists
    if (mysqli_num_rows($result) === 1) {
        $breedingcage = mysqli_fetch_assoc($result);

        // Fetch selected IACUC values associated with the cage
        $selectedIacucsQuery = "SELECT i.iacuc_id, i.iacuc_title 
                                FROM cage_iacuc ci
                                JOIN iacuc i ON ci.iacuc_id = i.iacuc_id
                                WHERE ci.cage_id = '$id'";
        $selectedIacucsResult = $con->query($selectedIacucsQuery);
        $selectedIacucs = [];
        while ($row = $selectedIacucsResult->fetch_assoc()) {
            $selectedIacucs[] = $row['iacuc_id'];
        }

        // Fetch currently selected users and explode them into an array
        $selectedUsersQuery = "SELECT user_id FROM cage_users WHERE cage_id = '$id'";
        $selectedUsersResult = $con->query($selectedUsersQuery);
        $selectedUsers = [];
        while ($row = $selectedUsersResult->fetch_assoc()) {
            $selectedUsers[] = $row['user_id'];
        }

        // Check if the logged-in user is the owner or an admin
        $currentUserId = $_SESSION['user_id']; // User ID from session
        $userRole = $_SESSION['role']; // User role from session
        $cageUsers = $selectedUsers; // Array of user IDs associated with the cage

        // Check if the user is either an admin or one of the users associated with the cage
        if ($userRole !== 'admin' && !in_array($currentUserId, $cageUsers)) {
            $_SESSION['message'] = 'Access denied. Only the admin or the assigned user can edit.';
            header("Location: bc_dash.php?" . getCurrentUrlParams());
            exit();
        }

        // Fetch currently selected PI
        $selectedPiId = $breedingcage['pi_name'];

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Validate CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die('CSRF token validation failed');
            }

            /// Retrieve and sanitize form data
            $cage_id = trim(mysqli_real_escape_string($con, $_POST['cage_id']));
            $pi_name = mysqli_real_escape_string($con, $_POST['pi_name']);
            $cross = mysqli_real_escape_string($con, $_POST['cross']);
            $iacuc = isset($_POST['iacuc']) ? $_POST['iacuc'] : []; // Array of selected IACUC values
            $users = isset($_POST['user']) ? $_POST['user'] : []; // Array of selected users
            $male_id = mysqli_real_escape_string($con, $_POST['male_id']);
            $female_id = mysqli_real_escape_string($con, $_POST['female_id']);
            $male_dob = mysqli_real_escape_string($con, $_POST['male_dob']);
            $female_dob = mysqli_real_escape_string($con, $_POST['female_dob']);
            $remarks = mysqli_real_escape_string($con, $_POST['remarks']);

            // Begin transaction
            $con->begin_transaction();

            try {
                // Update cages table
                $updateCageQuery = $con->prepare("UPDATE cages SET 
                                pi_name = ?, 
                                remarks = ? 
                                WHERE cage_id = ?");
                $updateCageQuery->bind_param("sss", $pi_name, $remarks, $cage_id);
                $updateCageQuery->execute();
                $updateCageQuery->close();

                // Update breeding table
                $updateBreedingQuery = $con->prepare("UPDATE breeding SET 
                                    `cross` = ?, 
                                    male_id = ?, 
                                    female_id = ?, 
                                    male_dob = ?, 
                                    female_dob = ? 
                                    WHERE cage_id = ?");
                $updateBreedingQuery->bind_param("ssssss", $cross, $male_id, $female_id, $male_dob, $female_dob, $cage_id);
                $updateBreedingQuery->execute();
                $updateBreedingQuery->close();

                // Update IACUC values in cage_iacuc table
                $deleteIacucQuery = $con->prepare("DELETE FROM cage_iacuc WHERE cage_id = ?");
                $deleteIacucQuery->bind_param("s", $cage_id);
                $deleteIacucQuery->execute();
                $deleteIacucQuery->close();

                $insertIacucQuery = $con->prepare("INSERT INTO cage_iacuc (cage_id, iacuc_id) VALUES (?, ?)");
                foreach ($iacuc as $iacuc_id) {
                    $insertIacucQuery->bind_param("ss", $cage_id, $iacuc_id);
                    $insertIacucQuery->execute();
                }
                $insertIacucQuery->close();

                // Update users in cage_users table
                $deleteUsersQuery = $con->prepare("DELETE FROM cage_users WHERE cage_id = ?");
                $deleteUsersQuery->bind_param("s", $cage_id);
                $deleteUsersQuery->execute();
                $deleteUsersQuery->close();

                $insertUsersQuery = $con->prepare("INSERT INTO cage_users (cage_id, user_id) VALUES (?, ?)");
                foreach ($users as $user_id) {
                    $insertUsersQuery->bind_param("ss", $cage_id, $user_id);
                    $insertUsersQuery->execute();
                }
                $insertUsersQuery->close();

                // Handle maintenance log updates
                if (isset($_POST['log_ids']) && isset($_POST['log_comments'])) {
                    $logIds = $_POST['log_ids'];
                    $logComments = $_POST['log_comments'];

                    for ($i = 0; $i < count($logIds); $i++) {
                        $edit_log_id = intval($logIds[$i]);
                        $edit_comment = trim(mysqli_real_escape_string($con, $logComments[$i]));

                        $updateLogQuery = "UPDATE maintenance SET comments = ? WHERE id = ?";
                        $stmtUpdateLog = $con->prepare($updateLogQuery);
                        $stmtUpdateLog->bind_param("si", $edit_comment, $edit_log_id);
                        $stmtUpdateLog->execute();
                        $stmtUpdateLog->close();
                    }
                }

                // Process maintenance logs deletion
                if (!empty($_POST['logs_to_delete'])) {
                    $logsToDelete = explode(',', $_POST['logs_to_delete']);
                    foreach ($logsToDelete as $logId) {
                        $deleteLogQuery = "DELETE FROM maintenance WHERE id = ?";
                        $stmtDeleteLog = $con->prepare($deleteLogQuery);
                        $stmtDeleteLog->bind_param("i", $logId);
                        $stmtDeleteLog->execute();
                        $stmtDeleteLog->close();
                    }
                }

                // Commit transaction
                $con->commit();

                $_SESSION['message'] = 'Entry updated successfully.';
            } catch (Exception $e) {
                // Rollback transaction on error
                $con->rollback();
                $_SESSION['message'] = 'Update failed: ' . $e->getMessage();
            }


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

            // Initialize arrays for litter data
            $dom = isset($_POST['dom']) ? $_POST['dom'] : [];
            $litter_dob = isset($_POST['litter_dob']) ? $_POST['litter_dob'] : [];
            $pups_alive = isset($_POST['pups_alive']) ? $_POST['pups_alive'] : [];
            $pups_dead = isset($_POST['pups_dead']) ? $_POST['pups_dead'] : [];
            $pups_male = isset($_POST['pups_male']) ? $_POST['pups_male'] : [];
            $pups_female = isset($_POST['pups_female']) ? $_POST['pups_female'] : [];
            $remarks_litter = isset($_POST['remarks_litter']) ? $_POST['remarks_litter'] : [];
            $litter_id = isset($_POST['litter_id']) ? $_POST['litter_id'] : [];
            $delete_litter_ids = isset($_POST['delete_litter_ids']) ? $_POST['delete_litter_ids'] : [];

            // Process litter data
            if (isset($_POST['dom']) && isset($_POST['litter_dob'])) {
                // Iterate over each new litter entry
                for ($i = 0; $i < count($_POST['dom']); $i++) {
                    $dom_i = mysqli_real_escape_string($con, $_POST['dom'][$i]);
                    $litter_dob_i = mysqli_real_escape_string($con, $_POST['litter_dob'][$i]);
                    $pups_alive_i = !empty($_POST['pups_alive'][$i]) ? intval($_POST['pups_alive'][$i]) : 0;
                    $pups_dead_i = !empty($_POST['pups_dead'][$i]) ? intval($_POST['pups_dead'][$i]) : 0;
                    $pups_male_i = !empty($_POST['pups_male'][$i]) ? intval($_POST['pups_male'][$i]) : 0;
                    $pups_female_i = !empty($_POST['pups_female'][$i]) ? intval($_POST['pups_female'][$i]) : 0;
                    $remarks_litter_i = mysqli_real_escape_string($con, $_POST['remarks_litter'][$i]);
                    $litter_id_i = isset($_POST['litter_id'][$i]) ? mysqli_real_escape_string($con, $_POST['litter_id'][$i]) : '';

                    // If litter_id exists, update the entry
                    if (!empty($litter_id_i)) {
                        $updateLitterQuery = $con->prepare("UPDATE litters SET `dom` = ?, `litter_dob` = ?, `pups_alive` = ?, `pups_dead` = ?, `pups_male` = ?, `pups_female` = ?, `remarks` = ? WHERE `id` = ?");
                        $updateLitterQuery->bind_param("ssssssss", $dom_i, $litter_dob_i, $pups_alive_i, $pups_dead_i, $pups_male_i, $pups_female_i, $remarks_litter_i, $litter_id_i);
                        $updateLitterQuery->execute();
                        $updateLitterQuery->close();
                    } else {
                        // If no litter_id, insert a new entry
                        $insertLitterQuery = $con->prepare("INSERT INTO litters (`cage_id`, `dom`, `litter_dob`, `pups_alive`, `pups_dead`, `pups_male`, `pups_female`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $insertLitterQuery->bind_param("ssssssss", $cage_id, $dom_i, $litter_dob_i, $pups_alive_i, $pups_dead_i, $pups_male_i, $pups_female_i, $remarks_litter_i);
                        $insertLitterQuery->execute();
                        $insertLitterQuery->close();
                    }
                }
            }

            // Handle deletion of litter entries
            if (!empty($delete_litter_ids)) {
                foreach ($delete_litter_ids as $delete_litter_id) {
                    if (!empty($delete_litter_id)) {
                        $deleteLitterQuery = $con->prepare("DELETE FROM litters WHERE id = ?");
                        $deleteLitterQuery->bind_param("s", $delete_litter_id);
                        $deleteLitterQuery->execute();
                        $deleteLitterQuery->close();
                    }
                }
            }

            // Redirect to the dashboard
            header("Location: bc_dash.php?" . getCurrentUrlParams());
            exit();
        }
    } else {
        // Set an error message if the ID is invalid
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: bc_dash.php?" . getCurrentUrlParams());
        exit();
    }
} else {
    // Set an error message if the ID parameter is missing
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php?" . getCurrentUrlParams());
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
    <script>
        // Function to navigate back to the previous page
        function goBack() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page') || 1;
            const search = urlParams.get('search') || '';
            window.location.href = 'bc_dash.php?page=' + page + '&search=' + encodeURIComponent(search);
        }

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
                <label for="litter_dob[]" class="form-label">Litter DOB </label>
                <input type="date" class="form-control" name="litter_dob[]" min="1900-01-01">
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
                <label for="remarks_litter[]" class="form-label">Remarks</label>
                <textarea class="form-control" name="remarks_litter[]" oninput="adjustTextareaHeight(this)"></textarea>
            </div>
            <input type="hidden" name="litter_id[]" value="">
            <button type="button" class="btn btn-danger" onclick="removeLitter(this)">Remove</button>
        `;

                document.getElementById('litterEntries').appendChild(litterDiv);
                setMaxDate();
            }

            // Function to adjust the height of the textarea dynamically
            function adjustTextareaHeight(element) {
                element.style.height = "auto";
                element.style.height = (element.scrollHeight) + "px";
            }

            // Ensure the function addLitter is available globally
            window.addLitter = addLitter;
        });

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

        // Function to remove a litter entry dynamically
        function removeLitter(element) {
            const litterEntry = element.parentElement;
            const litterIdInput = litterEntry.querySelector('[name="litter_id[]"]');

            if (litterIdInput && litterIdInput.value) {
                const deleteLitterIdsInput = document.querySelector('[name="delete_litter_ids[]"]');
                const newInput = document.createElement('input');
                newInput.type = 'hidden';
                newInput.name = 'delete_litter_ids[]';
                newInput.value = litterIdInput.value;
                document.querySelector('form').appendChild(newInput);
            }

            litterEntry.remove();
        }

        $(document).ready(function() {
            $('#user').select2({
                placeholder: "Select User(s)",
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

        // Function to mark maintenance log for deletion and hide the row
        function markLogForDeletion(logId) {
            if (confirm('Are you sure you want to delete this maintenance record?')) {
                const logIdsInput = document.getElementById('logs_to_delete');
                let logsToDelete = logIdsInput.value ? logIdsInput.value.split(',') : [];
                logsToDelete.push(logId);
                logIdsInput.value = logsToDelete.join(',');

                // Hide the log row from the table
                const logRow = document.getElementById(`log-row-${logId}`);
                if (logRow) {
                    logRow.style.display = 'none';
                }
            }
        }

    </script>

    <title>Edit Breeding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />

    <!-- Include Select2 JavaScript -->
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

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
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

        @media (max-width: 768px) {

            .table-wrapper th,
            .table-wrapper td {
                padding: 12px 8px;
                text-align: center;
            }
        }
    </style>

</head>

<body>
    <div class="container content mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Edit Breeding Cage</h4>
                        <div class="action-buttons">
                            <!-- Button to go back to the previous page -->
                            <!-- Button to go back to the previous page -->
                            <a href="javascript:void(0);" onclick="goBack()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Go Back">
                                <i class="fas fa-arrow-circle-left"></i>
                            </a>
                            <!-- Button to save the form -->
                            <a href="javascript:void(0);" onclick="document.getElementById('editForm').submit();" class="btn btn-success btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Save">
                                <i class="fas fa-save"></i>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                    <form id="editForm" method="POST" action="bc_edit.php?id=<?= $id; ?>&<?= getCurrentUrlParams(); ?>" enctype="multipart/form-data">
                            <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id" value="<?= htmlspecialchars($breedingcage['cage_id']); ?>" readonly required>
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
                                <label for="cross" class="form-label">Cross <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="cross" name="cross" value="<?= htmlspecialchars($breedingcage['cross']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="iacuc" class="form-label">IACUC</label>
                                <select class="form-control" id="iacuc" name="iacuc[]" multiple>
                                    <option value="" disabled>Select IACUC</option>
                                    <?php
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
                                <label for="male_id" class="form-label">Male ID <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="male_id" name="male_id" value="<?= htmlspecialchars($breedingcage['male_id']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="female_id" class="form-label">Female ID <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="female_id" name="female_id" value="<?= htmlspecialchars($breedingcage['female_id']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="male_dob" class="form-label">Male DOB <span class="required-asterisk">*</span></label>
                                <input type="date" class="form-control" id="male_dob" name="male_dob" value="<?= htmlspecialchars($breedingcage['male_dob']); ?>" required min="1900-01-01">
                            </div>

                            <div class="mb-3">
                                <label for="female_dob" class="form-label">Female DOB <span class="required-asterisk">*</span></label>
                                <input type="date" class="form-control" id="female_dob" name="female_dob" value="<?= htmlspecialchars($breedingcage['female_dob']); ?>" required min="1900-01-01">
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" oninput="adjustTextareaHeight(this)"><?= htmlspecialchars($breedingcage['remarks']); ?></textarea>
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
                                                <?php while ($file = $files->fetch_assoc()) : ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($file['file_name']); ?></td>
                                                        <td>
                                                            <a href="<?= htmlspecialchars($file['file_path']); ?>" download="<?= htmlspecialchars($file['file_name']); ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-cloud-download-alt fa-sm"></i>
                                                            </a>
                                                            <a href="delete_file.php?url=bc_edit&id=<?= intval($file['id']); ?>" class="btn-sm" onclick="return confirm('Are you sure you want to delete this file?');" aria-label="Delete <?= htmlspecialchars($file['file_name']); ?>">
                                                                <i class="fas fa-trash fa-sm" style="color:red"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
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

                            <!-- Litter Details Section -->
                            <div class="card mt-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0">Litter Details - <?= htmlspecialchars($id) ?>
                                        <button type="button" class="btn btn-primary btn-icon" onclick="addLitter()" data-toggle="tooltip" data-placement="top" title="Add New Litter Data">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </h4>
                                </div>

                                <div class="card-body" id="litterEntries">
                                    <?php while ($litter = mysqli_fetch_assoc($litters)) : ?>
                                        <div class="litter-entry">
                                            <hr class="mt-4 mb-4" style="border-top: 3px solid #000;">
                                            <div class="mb-3">
                                                <label for="dom[]" class="form-label">DOM <span class="required-asterisk">*</span></label>
                                                <input type="date" class="form-control" name="dom[]" value="<?= htmlspecialchars($litter['dom']); ?>" required min="1900-01-01">
                                            </div>
                                            <div class="mb-3">
                                                <label for="litter_dob[]" class="form-label">Litter DOB</label>
                                                <input type="date" class="form-control" name="litter_dob[]" value="<?= htmlspecialchars($litter['litter_dob']); ?>" min="1900-01-01">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_alive[]" class="form-label">Pups Alive <span class="required-asterisk">*</span></label>
                                                <input type="number" class="form-control" name="pups_alive[]" value="<?= htmlspecialchars($litter['pups_alive']); ?>" required min="0" step="1">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_dead[]" class="form-label">Pups Dead <span class="required-asterisk">*</span></label>
                                                <input type="number" class="form-control" name="pups_dead[]" value="<?= htmlspecialchars($litter['pups_dead']); ?>" required min="0" step="1">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_male[]" class="form-label">Pups Male</label>
                                                <input type="number" class="form-control" name="pups_male[]" value="<?= htmlspecialchars($litter['pups_male']); ?>" min="0" step="1">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_female[]" class="form-label">Pups Female</label>
                                                <input type="number" class="form-control" name="pups_female[]" value="<?= htmlspecialchars($litter['pups_female']); ?>" min="0" step="1">
                                            </div>
                                            <div class="mb-3">
                                                <label for="remarks_litter[]" class="form-label">Remarks Litter</label>
                                                <textarea class="form-control" name="remarks_litter[]" oninput="adjustTextareaHeight(this)"><?= htmlspecialchars($litter['remarks']); ?></textarea>
                                            </div>

                                            <input type="hidden" name="delete_litter_ids[]" value="">

                                            <input type="hidden" name="litter_id[]" value="<?= htmlspecialchars($litter['id']); ?>">
                                            <button type="button" class="btn btn-danger" onclick="removeLitter(this)">Remove</button>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <br>

                            <!-- Display Maintenance Logs Section -->
                            <div class="card mt-4">
                                <div class="card-header d-flex flex-column flex-md-row justify-content-between">
                                    <h4>Maintenance Log for Cage ID: <?= htmlspecialchars($id ?? 'Unknown'); ?></h4>
                                    <div class="action-icons mt-3 mt-md-0">
                                        <!-- Maintenance button with tooltip -->
                                        <a href="maintenance.php?from=bc_dash" class="btn btn-warning btn-icon" data-toggle="tooltip" data-placement="top" title="Add Maintenance Record">
                                            <i class="fas fa-wrench"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php
                                // Fetch the maintenance logs for the current cage
                                $maintenanceQuery = "
                                    SELECT m.id, m.timestamp, u.name AS user_name, m.comments, m.user_id 
                                    FROM maintenance m
                                    JOIN users u ON m.user_id = u.id
                                    WHERE m.cage_id = ?
                                    ORDER BY m.timestamp DESC";
                                $stmtMaintenance = $con->prepare($maintenanceQuery);
                                $stmtMaintenance->bind_param("s", $id);
                                $stmtMaintenance->execute();
                                $maintenanceLogs = $stmtMaintenance->get_result();
                                ?>

                                <?php if ($maintenanceLogs->num_rows > 0) : ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="width: 25%;">Date</th>
                                                    <th style="width: 25%;">User</th>
                                                    <th style="width: 40%;">Comment</th>
                                                    <th style="width: 10%;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($log = $maintenanceLogs->fetch_assoc()) : ?>
                                                    <tr id="log-row-<?= $log['id']; ?>">
                                                        <td style="width: 25%;"><?= htmlspecialchars($log['timestamp'] ?? ''); ?></td>
                                                        <td style="width: 25%;"><?= htmlspecialchars($log['user_name'] ?? 'Unknown'); ?></td>
                                                        <td style="width: 40%;">
                                                            <input type="hidden" name="log_ids[]" value="<?= htmlspecialchars($log['id']); ?>">
                                                            <textarea name="log_comments[]" class="form-control"><?= htmlspecialchars($log['comments'] ?? 'No comment'); ?></textarea>
                                                        </td>
                                                        <td style="width: 10%;">
                                                            <button type="button" class="btn btn-danger btn-icon" onclick="markLogForDeletion(<?= $log['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else : ?>
                                    <p>No maintenance records found for this cage.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Hidden input field to store IDs of logs to delete -->
                            <input type="hidden" id="logs_to_delete" name="logs_to_delete" value="">

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