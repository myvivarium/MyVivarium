<?php

/**
 * Manage Strains
 * 
 * This script provides functionality for managing strains in a database. It allows users to add new strains,
 * edit existing strains, and delete strains. The interface includes a responsive popup form for data entry and 
 * a table for displaying existing strains. The script uses PHP sessions for message handling and includes basic 
 * input sanitization for security.
 * 
 */

session_start(); // Start the session to use session variables
require 'dbcon.php'; // Include database connection
require 'header.php'; // Include the header for consistent page structure

// Initialize variables for strain data
$strainId = $strainName = $strainAka = $strainUrl = $strainRrid = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        // Add new strain
        $strainId = htmlspecialchars($_POST['strain_id']); // Sanitize input
        $strainName = htmlspecialchars($_POST['strain_name']); // Sanitize input
        $strainAka = htmlspecialchars($_POST['strain_aka']); // Sanitize input
        $strainUrl = htmlspecialchars($_POST['strain_url']); // Sanitize input
        $strainRrid = htmlspecialchars($_POST['strain_rrid']); // Sanitize input
        $stmt = $con->prepare("INSERT INTO strain (str_id, str_name, str_aka, str_url, str_rrid) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $strainId, $strainName, $strainAka, $strainUrl, $strainRrid);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Strain added successfully."; // Success message
        } else {
            $_SESSION['message'] = "Error adding strain."; // Error message
        }
        $stmt->close(); // Close the statement
    } elseif (isset($_POST['edit'])) {
        // Update existing strain
        $strainId = htmlspecialchars($_POST['strain_id']); // Sanitize input
        $strainName = htmlspecialchars($_POST['strain_name']); // Sanitize input
        $strainAka = htmlspecialchars($_POST['strain_aka']); // Sanitize input
        $strainUrl = htmlspecialchars($_POST['strain_url']); // Sanitize input
        $strainRrid = htmlspecialchars($_POST['strain_rrid']); // Sanitize input
        $stmt = $con->prepare("UPDATE strain SET str_name = ?, str_aka = ?, str_url = ?, str_rrid = ? WHERE str_id = ?");
        $stmt->bind_param("ssssi", $strainName, $strainAka, $strainUrl, $strainRrid, $strainId);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Strain updated successfully."; // Success message
        } else {
            $_SESSION['message'] = "Error updating strain."; // Error message
        }
        $stmt->close(); // Close the statement
    } elseif (isset($_POST['delete'])) {
        // Delete strain
        $strainId = htmlspecialchars($_POST['strain_id']); // Sanitize input
        $stmt = $con->prepare("DELETE FROM strain WHERE str_id = ?");
        $stmt->bind_param("i", $strainId);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Strain deleted successfully."; // Success message
        } else {
            $_SESSION['message'] = "Error deleting strain."; // Error message
        }
        $stmt->close(); // Close the statement
    }
}

// Fetch all strains for display
$strainQuery = "SELECT * FROM strain";
$strainResult = $con->query($strainQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Strains</title>
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
            left: 0%;
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
    </style>

</head>

<body>
    <div class="container content mt-5">
        <h2>Manage Strains</h2>
        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert alert-info">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Button to open the popup form -->
        <div class="add-button">
            <button onclick="openForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Strain</button>
        </div>

        <!-- Popup form for adding and editing strains -->
        <div class="popup-overlay" id="popupOverlay"></div>
        <div class="popup-form" id="popupForm">
            <h4 id="formTitle">Add New Strain</h4>
            <form action="manage_strain.php" method="post">
                <div class="form-group">
                    <label for="strain_id">Strain ID <span class="required-asterisk">*</span></label>
                    <input type="text" name="strain_id" id="strain_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="strain_name">Strain Name <span class="required-asterisk">*</span></label>
                    <input type="text" name="strain_name" id="strain_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="strain_aka">Common Names (comma separated)</label>
                    <input type="text" name="strain_aka" id="strain_aka" class="form-control">
                </div>
                <div class="form-group">
                    <label for="strain_url">Strain URL</label>
                    <input type="url" name="strain_url" id="strain_url" class="form-control">
                </div>
                <div class="form-group">
                    <label for="strain_rrid">Strain RRID</label>
                    <input type="text" name="strain_rrid" id="strain_rrid" class="form-control">
                </div>
                <div class="form-buttons">
                    <button type="submit" name="add" id="addButton" class="btn btn-primary"><i class="fas fa-plus"></i> Add Strain</button>
                    <button type="submit" name="edit" id="editButton" class="btn btn-success" style="display: none;"><i class="fas fa-save"></i> Update Strain</button>
                    <button type="button" class="btn btn-secondary" onclick="closeForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Display existing strains -->
        <h3>Existing Strains</h3>
        <table class="table table-bordered table-responsive">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Common Names</th>
                    <th>URL</th>
                    <th>RRID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $strainResult->fetch_assoc()) : ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($row['str_id']); ?></td>
                        <td data-label="Name"><?= htmlspecialchars($row['str_name']); ?></td>
                        <td data-label="Common Names"><?= htmlspecialchars($row['str_aka']); ?></td>
                        <td data-label="URL"><a href="<?= htmlspecialchars($row['str_url']); ?>" target="_blank"><?= htmlspecialchars($row['str_url']); ?></a></td>
                        <td data-label="RRID"><?= htmlspecialchars($row['str_rrid']); ?></td>
                        <td data-label="Actions" class="table-actions">
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" title="Edit" onclick="editStrain('<?= $row['str_id']; ?>', '<?= htmlspecialchars($row['str_name']); ?>', '<?= htmlspecialchars($row['str_aka']); ?>', '<?= htmlspecialchars($row['str_url']); ?>', '<?= htmlspecialchars($row['str_rrid']); ?>')"><i class="fas fa-edit"></i></button>
                                <form action="manage_strain.php" method="post" style="display:inline-block;">
                                    <input type="hidden" name="strain_id" value="<?= $row['str_id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this strain?');"><i class="fas fa-trash-alt"></i></button>
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
            document.getElementById('formTitle').innerText = 'Add New Strain';
            document.getElementById('addButton').style.display = 'block';
            document.getElementById('editButton').style.display = 'none';
            document.getElementById('strain_id').readOnly = false; // Ensure field is editable when adding
            document.getElementById('strain_id').value = '';
            document.getElementById('strain_name').value = '';
            document.getElementById('strain_aka').value = '';
            document.getElementById('strain_url').value = '';
            document.getElementById('strain_rrid').value = '';
        }

        // Function to close the popup form
        function closeForm() {
            document.getElementById('popupOverlay').style.display = 'none';
            document.getElementById('popupForm').style.display = 'none';
        }

        // Function to populate the form for editing
        function editStrain(id, name, aka, url, rrid) {
            openForm();
            document.getElementById('formTitle').innerText = 'Edit Strain';
            document.getElementById('addButton').style.display = 'none';
            document.getElementById('editButton').style.display = 'block';
            document.getElementById('strain_id').readOnly = true; // Make field read-only when editing
            document.getElementById('strain_id').value = id;
            document.getElementById('strain_name').value = name;
            document.getElementById('strain_aka').value = aka;
            document.getElementById('strain_url').value = url;
            document.getElementById('strain_rrid').value = rrid;
        }
    </script>

    <div class="extra-space"></div> <!-- Add extra space before the footer -->
    <?php require 'footer.php'; // Include the footer 
    ?>
</body>

</html>