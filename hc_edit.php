<?php

/**
 * Edit Holding Cage Script
 * 
 * This script handles the editing of holding cage records. It includes functionalities such as fetching existing
 * cage data, updating the cage information, handling CSRF protection, and managing file uploads and deletions.
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

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Generate a CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Query to retrieve options where role is 'PI' (Principal Investigator)
$query1 = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result1 = $con->query($query1);

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the holding cage record with the specified ID
    $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch associated files for the holding cage
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Check if the holding cage record exists
    if (mysqli_num_rows($result) === 1) {
        $holdingcage = mysqli_fetch_assoc($result);

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
            $iacuc = mysqli_real_escape_string($con, $_POST['iacuc']);
            $user = mysqli_real_escape_string($con, $_POST['user']);
            $qty = mysqli_real_escape_string($con, $_POST['qty']);
            $dob = mysqli_real_escape_string($con, $_POST['dob']);
            $sex = mysqli_real_escape_string($con, $_POST['sex']);
            $parent_cg = mysqli_real_escape_string($con, $_POST['parent_cg']);
            $remarks = mysqli_real_escape_string($con, $_POST['remarks']);
            $mouse_id_1 = mysqli_real_escape_string($con, $_POST['mouse_id_1']);
            $genotype_1 = mysqli_real_escape_string($con, $_POST['genotype_1']);
            $notes_1 = mysqli_real_escape_string($con, $_POST['notes_1']);
            $mouse_id_2 = mysqli_real_escape_string($con, $_POST['mouse_id_2']);
            $genotype_2 = mysqli_real_escape_string($con, $_POST['genotype_2']);
            $notes_2 = mysqli_real_escape_string($con, $_POST['notes_2']);
            $mouse_id_3 = mysqli_real_escape_string($con, $_POST['mouse_id_3']);
            $genotype_3 = mysqli_real_escape_string($con, $_POST['genotype_3']);
            $notes_3 = mysqli_real_escape_string($con, $_POST['notes_3']);
            $mouse_id_4 = mysqli_real_escape_string($con, $_POST['mouse_id_4']);
            $genotype_4 = mysqli_real_escape_string($con, $_POST['genotype_4']);
            $notes_4 = mysqli_real_escape_string($con, $_POST['notes_4']);
            $mouse_id_5 = mysqli_real_escape_string($con, $_POST['mouse_id_5']);
            $genotype_5 = mysqli_real_escape_string($con, $_POST['genotype_5']);
            $notes_5 = mysqli_real_escape_string($con, $_POST['notes_5']);

            // Update query for hc_basic table
            $updateQuery = "UPDATE hc_basic SET
                            `cage_id` = ?,
                            `pi_name` = ?,
                            `strain` = ?,
                            `iacuc` = ?,
                            `user` = ?,
                            `qty` = ?,
                            `dob` = ?,
                            `sex` = ?,
                            `parent_cg` = ?,
                            `remarks` = ?,
                            `mouse_id_1` = ?,
                            `genotype_1` = ?,
                            `notes_1` = ?,
                            `mouse_id_2` = ?,
                            `genotype_2` = ?,
                            `notes_2` = ?,
                            `mouse_id_3` = ?,
                            `genotype_3` = ?,
                            `notes_3` = ?,
                            `mouse_id_4` = ?,
                            `genotype_4` = ?,
                            `notes_4` = ?,
                            `mouse_id_5` = ?,
                            `genotype_5` = ?,
                            `notes_5` = ?
                            WHERE `cage_id` = ?";

            // Prepare the update query
            $stmt = $con->prepare($updateQuery);

            // Bind parameters to the prepared statement
            $stmt->bind_param(
                "sssssissssssssssssssssssss",
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
                $mouse_id_1,
                $genotype_1,
                $notes_1,
                $mouse_id_2,
                $genotype_2,
                $notes_2,
                $mouse_id_3,
                $genotype_3,
                $notes_3,
                $mouse_id_4,
                $genotype_4,
                $notes_4,
                $mouse_id_5,
                $genotype_5,
                $notes_5,
                $id
            );

            // Execute the prepared statement
            $result = $stmt->execute();

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

                $uploadsDir = __DIR__ . "/uploads/";
                $targetDirectory = $uploadsDir . "$cage_id/";

                // Ensure the correct permissions and ownership for the uploads directory
                if (!file_exists($uploadsDir)) {
                    if (!mkdir($uploadsDir, 0777, true)) {
                        $_SESSION['message'] .= " Failed to create uploads directory.";
                    }
                }
                chown($uploadsDir, 'www-data');
                chgrp($uploadsDir, 'www-data');
                chmod($uploadsDir, 0755);
                
                // Create the cage_id specific sub-directory if it doesn't exist
                $targetDirectory = $uploadsDir . "$cage_id/";
                if (!file_exists($targetDirectory)) {
                    if (!mkdir($targetDirectory, 0777, true)) {
                        $_SESSION['message'] .= " Failed to create cage_id directory.";
                    }
                }
                chown($targetDirectory, 'www-data');
                chgrp($targetDirectory, 'www-data');
                chmod($targetDirectory, 0755);
                

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
                            $_SESSION['message'] .= " File upload failed, please try again. Error: " . $insert->error;
                        }
                    } else {
                        $_SESSION['message'] .= " Sorry, there was an error uploading your file. Error: " . $_FILES['fileUpload']['error'];
                    }
                } else {
                    $_SESSION['message'] .= " Sorry, file already exists.";
                }
            } else if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] != UPLOAD_ERR_NO_FILE) {
                $_SESSION['message'] .= " Error uploading file: " . $_FILES['fileUpload']['error'];
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

// Include the header file
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>
    <title>Edit Holding Cage | <?php echo htmlspecialchars($labName); ?></title>
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
        // Function to go back to the previous page
        function goBack() {
            window.history.back();
        }

        // Function to show/hide mouse fields based on the quantity
        function showMouseFields() {
            var qty = document.getElementById('qty').value;
            for (var i = 1; i <= 5; i++) {
                document.getElementById('mouse_fields_' + i).style.display = i <= qty ? 'block' : 'none';
            }
        }

        // Function to adjust the height of textareas dynamically
        function adjustTextareaHeight(element) {
            element.style.height = "auto";
            element.style.height = (element.scrollHeight) + "px";
        }
    </script>

</head>

<body>
    <div class="container mt-4" style="max-width: 800px; background-color: #f8f9fa; padding: 20px; border-radius: 8px;">

        <?php include('message.php'); ?> <!-- Include the message file -->

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Holding Cage</h4>
                        <p class="warning-text">Fields marked with <span class="required-asterisk">*</span> are required.</p>

                    </div>

                    <div class="card-body">
                        <form method="POST" action="hc_edit.php?id=<?= $id; ?>" enctype="multipart/form-data">

                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id" value="<?= htmlspecialchars($holdingcage['cage_id']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="pi_name" class="form-label">PI Name <span class="required-asterisk">*</span></label>
                                <select class="form-control" id="pi_name" name="pi_name" required>
                                    <option value="<?= htmlspecialchars($holdingcage['pi_name']); ?>" selected>
                                        <?= htmlspecialchars($holdingcage['pi_name']); ?>
                                    </option>
                                    <?php
                                    while ($row = $result1->fetch_assoc()) {
                                        if ($row['name'] != $holdingcage['pi_name']) {
                                            echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="strain" class="form-label">Strain <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="strain" name="strain" value="<?= htmlspecialchars($holdingcage['strain']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="iacuc" class="form-label">IACUC</label>
                                <input type="text" class="form-control" id="iacuc" name="iacuc" value="<?= htmlspecialchars($holdingcage['iacuc']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="user" class="form-label">User <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control" id="user" name="user" value="<?= htmlspecialchars($holdingcage['user']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="qty" class="form-label">Qty <span class="required-asterisk">*</span></label>
                                <select class="form-control" id="qty" name="qty" required onchange="showMouseFields()">
                                    <option value="<?= htmlspecialchars($holdingcage['qty']); ?>" selected>
                                        <?= htmlspecialchars($holdingcage['qty']); ?>
                                    </option>
                                    <?php
                                    for ($i = 0; $i <= 5; $i++) {
                                        if ($i != $holdingcage['qty']) {
                                            echo "<option value=\"$i\">$i</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="dob" class="form-label">DOB <span class="required-asterisk">*</span></label>
                                <input type="date" class="form-control" id="dob" name="dob" value="<?= htmlspecialchars($holdingcage['dob']); ?>" required>
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

                            <!-- Loop to dynamically create fields for each mouse -->
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <div id="mouse_fields_<?php echo $i; ?>" style="display: <?= $i <= $holdingcage['qty'] ? 'block' : 'none'; ?>;">
                                    <h4>Mouse #<?php echo $i; ?></h4>
                                    <div class="mb-3">
                                        <label for="mouse_id_<?php echo $i; ?>" class="form-label">Mouse ID</label>
                                        <input type="text" class="form-control" id="mouse_id_<?php echo $i; ?>" name="mouse_id_<?php echo $i; ?>" value="<?= htmlspecialchars($holdingcage["mouse_id_$i"]); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="genotype_<?php echo $i; ?>" class="form-label">Genotype</label>
                                        <input type="text" class="form-control" id="genotype_<?php echo $i; ?>" name="genotype_<?php echo $i; ?>" value="<?= htmlspecialchars($holdingcage["genotype_$i"]); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes_<?php echo $i; ?>" class="form-label">Maintenance Notes</label>
                                        <textarea class="form-control" id="notes_<?php echo $i; ?>" name="notes_<?php echo $i; ?>" oninput="adjustTextareaHeight(this)"><?= htmlspecialchars($holdingcage["notes_$i"]); ?></textarea>
                                    </div>
                                </div>
                            <?php endfor; ?>

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