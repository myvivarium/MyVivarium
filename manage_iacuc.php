<?php
/**
 * Manage IACUC
 * 
 * This script provides functionality for managing IACUC records in a database. It allows users to add new IACUC records,
 * edit existing records, and delete records. The interface includes a responsive popup form for data entry and 
 * a table for displaying existing records. The script uses PHP sessions for message handling and includes basic 
 * input sanitization for security. File upload functionality is included for the IACUC records.
 * 
 */

session_start(); // Start the session to use session variables
require 'dbcon.php'; // Include database connection
require 'header.php'; // Include the header for consistent page structure

// Handle file upload with validation
function handleFileUpload($file) {
    // Define allowed file types and maximum size
    $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx',
                          'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB in bytes

    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Validate file size
    if ($file['size'] > $maxFileSize) {
        $_SESSION['message'] = "File size exceeds 10MB limit.";
        return false;
    }

    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validate file extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        $_SESSION['message'] = "Invalid file type. Allowed: pdf, doc, docx, txt, xls, xlsx, ppt, pptx, jpg, jpeg, png, gif, bmp, svg, webp";
        return false;
    }

    // Sanitize filename to prevent directory traversal
    $safeFilename = preg_replace("/[^a-zA-Z0-9._-]/", "_", basename($file["name"]));

    $targetDir = "uploads/iacuc/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true); // Create directory if it doesn't exist
    }

    $targetFile = $targetDir . $safeFilename;

    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $targetFile;
    } else {
        $_SESSION['message'] = "Error uploading file.";
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        // Add new IACUC
        $iacucId = htmlspecialchars($_POST['iacuc_id']); // Sanitize input
        $iacucTitle = htmlspecialchars($_POST['iacuc_title']); // Sanitize input

        // Check if the IACUC ID already exists
        $checkQuery = $con->prepare("SELECT iacuc_id FROM iacuc WHERE iacuc_id = ?");
        $checkQuery->bind_param("s", $iacucId);
        $checkQuery->execute();
        $checkQuery->store_result();
        if ($checkQuery->num_rows > 0) {
            $_SESSION['message'] = "IACUC ID already exists. Please use a different ID.";
            $checkQuery->close();
        } else {
            $checkQuery->close();
            $fileUrl = !empty($_FILES['iacuc_file']['name']) ? handleFileUpload($_FILES['iacuc_file']) : null;
            if ($fileUrl !== false) {
                $stmt = $con->prepare("INSERT INTO iacuc (iacuc_id, iacuc_title, file_url) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $iacucId, $iacucTitle, $fileUrl);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "IACUC record added successfully."; // Success message
                } else {
                    $_SESSION['message'] = "Error adding IACUC record."; // Error message
                }
                $stmt->close(); // Close the statement
            } else {
                $_SESSION['message'] = "Error uploading file.";
            }
        }
    } elseif (isset($_POST['edit'])) {
        // Update existing IACUC
        $iacucId = htmlspecialchars($_POST['iacuc_id']); // Sanitize input
        $iacucTitle = htmlspecialchars($_POST['iacuc_title']); // Sanitize input
        $fileUrl = !empty($_FILES['iacuc_file']['name']) ? handleFileUpload($_FILES['iacuc_file']) : htmlspecialchars($_POST['existing_file_url']);
        
        if ($fileUrl !== false) {
            $stmt = $con->prepare("UPDATE iacuc SET iacuc_title = ?, file_url = ? WHERE iacuc_id = ?");
            $stmt->bind_param("sss", $iacucTitle, $fileUrl, $iacucId);
            if ($stmt->execute()) {
                $_SESSION['message'] = "IACUC record updated successfully."; // Success message
            } else {
                $_SESSION['message'] = "Error updating IACUC record."; // Error message
            }
            $stmt->close(); // Close the statement
        } else {
            $_SESSION['message'] = "Error uploading file.";
        }
    } elseif (isset($_POST['delete'])) {
        // Delete IACUC
        $iacucId = htmlspecialchars($_POST['iacuc_id']); // Sanitize input
        $stmt = $con->prepare("DELETE FROM iacuc WHERE iacuc_id = ?");
        $stmt->bind_param("s", $iacucId);
        if ($stmt->execute()) {
            $_SESSION['message'] = "IACUC record deleted successfully."; // Success message
        } else {
            $_SESSION['message'] = "Error deleting IACUC record."; // Error message
        }
        $stmt->close(); // Close the statement
    }
}

// Fetch all IACUC records for display
$iacucQuery = "SELECT * FROM iacuc";
$iacucResult = $con->query($iacucQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage IACUC</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Popup Form Styles */
        .popup-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border: 2px solid #000;
            z-index: 1000;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
            width: 80%;
            max-width: 800px;
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Button and Form Layout */
        .table-actions,
        .action-buttons,
        .form-buttons {
            display: flex;
            gap: 10px;
        }

        .table-actions {
            justify-content: flex-start;
        }

        .form-buttons {
            justify-content: space-between;
        }

        .add-button {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .add-button .btn {
            margin-bottom: 20px;
        }

        .required-asterisk {
            color: red;
        }

        .extra-space {
            margin-bottom: 50px;
        }

        /* Responsive Styles */
        @media (max-width: 767px) {
            .form-buttons {
                flex-direction: column;
            }

            .form-buttons button {
                width: 100%;
                margin-bottom: 10px;
            }
        }

        @media (max-width: 576px) {
            .table thead {
                display: none;
            }

            .table tr {
                display: flex;
                flex-direction: column;
                margin-bottom: 20px;
            }

            .table td {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                border: 1px solid #dee2e6;
            }

            .table td::before {
                content: attr(data-label);
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 5px;
                display: block;
            }

            .table-actions,
            .action-buttons {
                flex-direction: column;
            }

            .table-actions button,
            .action-buttons .btn {
                width: 100%;
                margin-bottom: 10px;
            }

            .table-actions {
                gap: 10px;
                flex-wrap: wrap;
            }
        }

        .table {
            width: 100%;
        }
    </style>

</head>

<body>
    <div class="container content mt-5">
        <h2>Manage IACUC</h2>
        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert alert-info">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Button to open the popup form -->
        <div class="add-button">
            <button onclick="openForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add New IACUC</button>
        </div>

        <!-- Popup form for adding and editing IACUC records -->
        <div class="popup-overlay" id="popupOverlay"></div>
        <div class="popup-form" id="popupForm">
            <h4 id="formTitle">Add New IACUC</h4>
            <form action="manage_iacuc.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="iacuc_id">IACUC ID <span class="required-asterisk">*</span></label>
                    <input type="text" name="iacuc_id" id="iacuc_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="iacuc_title">Title <span class="required-asterisk">*</span></label>
                    <input type="text" name="iacuc_title" id="iacuc_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="iacuc_file">Upload File</label>
                    <input type="file" name="iacuc_file" id="iacuc_file" class="form-control">
                    <div id="existingFile" style="margin-top: 10px;"></div>
                </div>
                <input type="hidden" name="existing_file_url" id="existing_file_url">
                <div class="form-buttons">
                    <button type="submit" name="add" id="addButton" class="btn btn-primary"><i class="fas fa-plus"></i> Add IACUC</button>
                    <button type="submit" name="edit" id="editButton" class="btn btn-success" style="display: none;"><i class="fas fa-save"></i> Update IACUC</button>
                    <button type="button" class="btn btn-secondary" onclick="closeForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Display existing IACUC records -->
        <h3>Existing IACUC Records</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>File</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $iacucResult->fetch_assoc()) : ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($row['iacuc_id']); ?></td>
                        <td data-label="Title"><?= htmlspecialchars($row['iacuc_title']); ?></td>
                        <td data-label="File">
                            <?php if ($row['file_url']) : ?>
                                <a href="<?= htmlspecialchars($row['file_url']); ?>" target="_blank">View/Download</a>
                            <?php else : ?>
                                No file uploaded
                            <?php endif; ?>
                        </td>
                        <td data-label="Actions" class="table-actions">
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" title="Edit" onclick="editIACUC('<?= $row['iacuc_id']; ?>', '<?= htmlspecialchars($row['iacuc_title']); ?>', '<?= htmlspecialchars($row['file_url']); ?>')"><i class="fas fa-edit"></i></button>
                                <form action="manage_iacuc.php" method="post" style="display:inline-block;">
                                    <input type="hidden" name="iacuc_id" value="<?= $row['iacuc_id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this IACUC record?');"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Function to open the popup form
        function openForm() {
            document.getElementById('popupOverlay').style.display = 'block';
            document.getElementById('popupForm').style.display = 'block';
            document.getElementById('formTitle').innerText = 'Add New IACUC';
            document.getElementById('addButton').style.display = 'block';
            document.getElementById('editButton').style.display = 'none';
            document.getElementById('iacuc_id').readOnly = false; // Ensure field is editable when adding
            document.getElementById('iacuc_id').value = '';
            document.getElementById('iacuc_title').value = '';
            document.getElementById('iacuc_file').value = '';
            document.getElementById('existing_file_url').value = '';
            document.getElementById('existingFile').innerHTML = ''; // Clear existing file info
        }

        // Function to close the popup form
        function closeForm() {
            document.getElementById('popupOverlay').style.display = 'none';
            document.getElementById('popupForm').style.display = 'none';
        }

        // Function to populate the form for editing
        function editIACUC(id, title, fileUrl) {
            openForm();
            document.getElementById('formTitle').innerText = 'Edit IACUC';
            document.getElementById('addButton').style.display = 'none';
            document.getElementById('editButton').style.display = 'block';
            document.getElementById('iacuc_id').readOnly = true; // Make field read-only when editing
            document.getElementById('iacuc_id').value = id;
            document.getElementById('iacuc_title').value = title;
            document.getElementById('existing_file_url').value = fileUrl;
            document.getElementById('existingFile').innerHTML = fileUrl ? `<a href="${fileUrl}" target="_blank">Current File</a>` : 'No file uploaded';
        }
    </script>

    <div class="extra-space"></div> <!-- Add extra space before the footer -->
    <?php require 'footer.php'; // Include the footer ?>
</body>

</html>
