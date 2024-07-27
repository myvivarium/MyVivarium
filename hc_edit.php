<?php

/**
 * Edit Holding Cage Script
 * 
 * This script handles the editing of holding cage records, including maintenance logs and mouse data.
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
$piQuery = "SELECT id, initials, name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$piResult = $con->query($piQuery);

// Query to retrieve IACUC values
$iacucQuery = "SELECT iacuc_id, iacuc_title FROM iacuc";
$iacucResult = $con->query($iacucQuery);

// Query to retrieve strain details including common names
$strainQuery = "SELECT str_id, str_name, str_aka FROM strains";
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
    $query = "SELECT h.*, c.pi_name AS pi_name, c.quantity, c.remarks
              FROM holding h
              LEFT JOIN cages c ON h.cage_id = c.cage_id 
              WHERE h.cage_id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch associated files for the holding cage
    $query2 = "SELECT * FROM files WHERE cage_id = ?";
    $stmt2 = $con->prepare($query2);
    $stmt2->bind_param("s", $id);
    $stmt2->execute();
    $files = $stmt2->get_result();

    // Fetch the mouse data related to this cage
    $mouseQuery = "SELECT * FROM mice WHERE cage_id = ?";
    $stmtMouse = $con->prepare($mouseQuery);
    $stmtMouse->bind_param("s", $id);
    $stmtMouse->execute();
    $mouseResult = $stmtMouse->get_result();
    $mice = $mouseResult->fetch_all(MYSQLI_ASSOC);

    // Check if the holding cage record exists
    if ($result->num_rows === 1) {
        $holdingcage = $result->fetch_assoc();

        // Fetch currently selected users
        $selectedUsersQuery = "SELECT user_id FROM cage_users WHERE cage_id = ?";
        $stmtUsers = $con->prepare($selectedUsersQuery);
        $stmtUsers->bind_param("s", $id);
        $stmtUsers->execute();
        $usersResult = $stmtUsers->get_result();
        $selectedUsers = array_column($usersResult->fetch_all(MYSQLI_ASSOC), 'user_id');

        // Fetching the selected IACUC values for the cage
        $selectedIacucs = [];
        $selectedIacucQuery = "SELECT iacuc_id FROM cage_iacuc WHERE cage_id = ?";
        $stmtSelectedIacuc = $con->prepare($selectedIacucQuery);
        $stmtSelectedIacuc->bind_param("s", $id);
        $stmtSelectedIacuc->execute();
        $resultSelectedIacuc = $stmtSelectedIacuc->get_result();
        while ($row = $resultSelectedIacuc->fetch_assoc()) {
            $selectedIacucs[] = $row['iacuc_id'];
        }
        $stmtSelectedIacuc->close();

        // Fetch currently selected PI
        $selectedPiId = $holdingcage['pi_name'];

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Validate CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die('CSRF token validation failed');
            }

            // Retrieve and sanitize form data
            $cage_id = trim(mysqli_real_escape_string($con, $_POST['cage_id']));
            $pi_name = mysqli_real_escape_string($con, $_POST['pi_name']);
            $strain = mysqli_real_escape_string($con, $_POST['strain']);
            $iacuc = isset($_POST['iacuc']) ? array_map(function ($value) use ($con) {
                return mysqli_real_escape_string($con, $value);
            }, $_POST['iacuc']) : [];
            $users = isset($_POST['user']) ? array_map(function ($user_id) use ($con) {
                return mysqli_real_escape_string($con, trim($user_id));
            }, $_POST['user']) : [];
            $dob = mysqli_real_escape_string($con, $_POST['dob']);
            $sex = mysqli_real_escape_string($con, $_POST['sex']);
            $parent_cg = mysqli_real_escape_string($con, $_POST['parent_cg']);
            $remarks = mysqli_real_escape_string($con, $_POST['remarks']);

            // Update query for holding table
            $updateQueryHolding = "UPDATE holding SET
                                    `strain` = ?,
                                    `dob` = ?,
                                    `sex` = ?,
                                    `parent_cg` = ?
                                    WHERE `cage_id` = ?";

            $stmtHolding = $con->prepare($updateQueryHolding);
            $stmtHolding->bind_param("sssss", $strain, $dob, $sex, $parent_cg, $cage_id);
            $resultHolding = $stmtHolding->execute();
            $stmtHolding->close();

            // Update query for cages table
            $updateQueryCages = "UPDATE cages SET
                                 `pi_name` = ?,
                                 `remarks` = ?
                                 WHERE `cage_id` = ?";

            $stmtCages = $con->prepare($updateQueryCages);
            $stmtCages->bind_param("iss", $pi_name, $remarks, $cage_id);
            $resultCages = $stmtCages->execute();
            $stmtCages->close();

            // Update the cage_users table
            $deleteUsersQuery = "DELETE FROM cage_users WHERE cage_id = ?";
            $stmtDeleteUsers = $con->prepare($deleteUsersQuery);
            $stmtDeleteUsers->bind_param("s", $cage_id);
            $stmtDeleteUsers->execute();
            $stmtDeleteUsers->close();

            $insertUsersQuery = "INSERT INTO cage_users (cage_id, user_id) VALUES (?, ?)";
            $stmtInsertUsers = $con->prepare($insertUsersQuery);
            foreach ($users as $user_id) {
                $stmtInsertUsers->bind_param("si", $cage_id, $user_id);
                $stmtInsertUsers->execute();
            }
            $stmtInsertUsers->close();

            // Update the cage_iacuc table
            $deleteIacucQuery = "DELETE FROM cage_iacuc WHERE cage_id = ?";
            $stmtDeleteIacuc = $con->prepare($deleteIacucQuery);
            $stmtDeleteIacuc->bind_param("s", $cage_id);
            $stmtDeleteIacuc->execute();
            $stmtDeleteIacuc->close();

            $insertIacucQuery = "INSERT INTO cage_iacuc (cage_id, iacuc_id) VALUES (?, ?)";
            $stmtInsertIacuc = $con->prepare($insertIacucQuery);
            foreach ($iacuc as $iacuc_id) {
                $stmtInsertIacuc->bind_param("ss", $cage_id, $iacuc_id);
                $stmtInsertIacuc->execute();
            }
            $stmtInsertIacuc->close();

            // Update the mice table with new data
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
                        $updateMouseQuery = "UPDATE mice SET mouse_id = ?, genotype = ?, notes = ? WHERE id = ?";
                        $stmtMouseUpdate = $con->prepare($updateMouseQuery);
                        $stmtMouseUpdate->bind_param("sssi", $mouse_id, $genotype, $note, $existing_mouse_id);
                        $stmtMouseUpdate->execute();
                        $stmtMouseUpdate->close();
                    } else {
                        // Insert new mouse record
                        $insertMouseQuery = "INSERT INTO mice (cage_id, mouse_id, genotype, notes) VALUES (?, ?, ?, ?)";
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
                    $deleteMouseQuery = "DELETE FROM mice WHERE id = ?";
                    $stmtMouseDelete = $con->prepare($deleteMouseQuery);
                    $stmtMouseDelete->bind_param("i", $mouseId);
                    $stmtMouseDelete->execute();
                    $stmtMouseDelete->close();
                }
            }

            // Update the qty field in cages based on the number of mouse records
            $newQtyQuery = "SELECT COUNT(*) AS new_qty FROM mice WHERE cage_id = ?";
            $stmtNewQty = $con->prepare($newQtyQuery);
            $stmtNewQty->bind_param("s", $cage_id);
            $stmtNewQty->execute();
            $stmtNewQty->bind_result($newQty);
            $stmtNewQty->fetch();
            $stmtNewQty->close();

            $updateQtyQuery = "UPDATE cages SET quantity = ? WHERE cage_id = ?";
            $stmtUpdateQty = $con->prepare($updateQtyQuery);
            $stmtUpdateQty->bind_param("is", $newQty, $cage_id);
            $stmtUpdateQty->execute();
            $stmtUpdateQty->close();

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

                $_SESSION['message'] = 'Maintenance logs updated successfully.';
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

            // Redirect to the same page to prevent resubmission on refresh
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

// Function to fetch user details by IDs
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

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
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

        // Function to navigate to the hc_dash.php page
        function goBackToDashboard() {
            window.location.href = 'hc_dash.php';
        }
    </script>

</head>

<body>
    <div class="container content mt-4">

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Edit Holding Cage</h4>
                        <div class="action-buttons">
                            <!-- Button to go back to the previous page -->
                            <a href="javascript:void(0);" onclick="goBackToDashboard()" class="btn btn-primary btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Go Back">
                                <i class="fas fa-arrow-circle-left"></i>
                            </a>
                            <!-- Button to save the form -->
                            <a href="javascript:void(0);" onclick="document.getElementById('editForm').submit();" class="btn btn-success btn-sm btn-icon" data-toggle="tooltip" data-placement="top" title="Save">
                                <i class="fas fa-save"></i>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <form id="editForm" method="POST" action="hc_edit.php?id=<?= $id; ?>" enctype="multipart/form-data">
                            <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>
                            <input type="hidden" id="mice_to_delete" name="mice_to_delete" value="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id" value="<?= htmlspecialchars($holdingcage['cage_id']); ?>" readonly required>
                            </div>

                            <div class="mb-3">
                                <label for="pi_name" class="form-label">PI Name <span class="required-asterisk">*</span></label>
                                <select class="form-control" id="pi_name" name="pi_name" required>
                                    <!-- Display a placeholder option for selection -->
                                    <option value="" disabled>Select PI</option>
                                    <?php while ($row = $piResult->fetch_assoc()) : ?>
                                        <option value="<?= htmlspecialchars($row['id']); ?>" <?= ($row['id'] == $selectedPiId) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($row['initials']) . ' [' . htmlspecialchars($row['name']) . ']'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="strain" class="form-label">Strain <span class="required-asterisk">*</span></label>
                                <select class="form-control" id="strain" name="strain" required>
                                    <option value="" disabled <?= empty($holdingcage['strain']) ? 'selected' : ''; ?>>Select Strain</option>
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
                                    <option value="" disabled <?= empty($holdingcage['sex']) ? 'selected' : ''; ?>>Select Sex</option>
                                    <option value="Male" <?= $holdingcage['sex'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?= $holdingcage['sex'] === 'female' ? 'selected' : ''; ?>>Female</option>
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
                            <!-- Separator -->
                            <hr class="mt-4 mb-4" style="border-top: 3px solid #000;">


                            <div class="card-body">
                                <div class="card-header d-flex flex-column flex-md-row justify-content-between">
                                    <h4>Maintenance Log for Cage ID: <?= htmlspecialchars($id ?? 'Unknown'); ?></h4>
                                    <div class="action-icons mt-3 mt-md-0">
                                        <!-- Maintenance button with tooltip -->
                                        <a href="maintenance.php?from=hc_dash" class="btn btn-warning btn-icon" data-toggle="tooltip" data-placement="top" title="Add Maintenance Record">
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