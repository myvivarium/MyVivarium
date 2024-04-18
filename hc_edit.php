<?php
session_start();
require 'dbcon.php';

// Check if the user is not logged in, redirect them to index.php
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);


// Query to retrieve options where role is 'PI'
$query1 = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result1 = $con->query($query1);

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the holdingcage record with the specified ID
    $query = "SELECT * FROM hc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    if (mysqli_num_rows($result) === 1) {
        $holdingcage = mysqli_fetch_assoc($result);

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Retrieve form data
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

            $stmt = $con->prepare($updateQuery);

            // Bind parameters to the prepared statement
            $stmt->bind_param("sssssissssssssssssssssssss", $cage_id, $pi_name, $strain, $iacuc, $user, $qty, $dob, $sex, $parent_cg, $remarks, $mouse_id_1, $genotype_1, $notes_1, $mouse_id_2, $genotype_2, $notes_2, $mouse_id_3, $genotype_3, $notes_3, $mouse_id_4, $genotype_4, $notes_4, $mouse_id_5, $genotype_5, $notes_5, $id);

            // Execute the statement
            $result = $stmt->execute();

            // Check if the update was successful
            if ($result) {
                $_SESSION['message'] = 'Entry updated successfully.';
            } else {
                $_SESSION['error'] = 'Update failed: ' . $stmt->error;
            }

            if (isset($_FILES['fileUpload'])) {
                $targetDirectory = "uploads/";
                $originalFileName = basename($_FILES['fileUpload']['name']);
                $targetFilePath = $targetDirectory . basename($_FILES['fileUpload']['name']);
                $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

                // Check if file already exists
                if (!file_exists($targetFilePath)) {
                    if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $targetFilePath)) {
                        // Insert file info into the database
                        $insert = $con->prepare("INSERT INTO files (file_name, file_path, cage_id) VALUES (?, ?, ?)");
                        $insert->bind_param("ssi", $originalFileName, $targetFilePath, $cage_id);
                        $insert->execute();

                        if ($insert) {
                            $_SESSION['message'] = "File uploaded successfully.";
                        } else {
                            $_SESSION['error'] =  "File upload failed, please try again.";
                        }
                    } else {
                        $_SESSION['error'] =  "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $_SESSION['error'] =  "Sorry, file already exists.";
                }
            }

            // Close the prepared statement
            $stmt->close();

            header("Location: hc_dash.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: hc_dash.php");
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: hc_dash.php");
    exit();
}
require 'header.php';
?>

<!doctype html>
<html lang="en">

<head>

    <script>
        function goBack() {
            window.history.back();
            //window.location.href = 'specific_php_file.php';
        }
    </script>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <title>Edit Holding Cage | <?php echo htmlspecialchars($labName); ?></title>

</head>

<body>

    <div class="container mt-4">

        <?php include('message.php'); ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Holding Cage</h4>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="hc_edit.php?id=<?= $id; ?>">

                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID</label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id" value="<?= $holdingcage['cage_id']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="pi_name" class="form-label">PI Name</label>
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
                                <label for="strain" class="form-label">Strain</label>
                                <input type="text" class="form-control" id="strain" name="strain" value="<?= $holdingcage['strain']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="iacuc" class="form-label">IACUC</label>
                                <input type="text" class="form-control" id="iacuc" name="iacuc" value="<?= $holdingcage['iacuc']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="user" class="form-label">User</label>
                                <input type="text" class="form-control" id="user" name="user" value="<?= $holdingcage['user']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="qty" class="form-label">Qty</label>
                                <select class="form-control" id="qty" name="qty" required>
                                    <option value="<?= $holdingcage['qty']; ?>" selected>
                                        <?= $holdingcage['qty']; ?>
                                    </option>
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i != $holdingcage['qty']) {
                                            echo "<option value=\"$i\">$i</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="dob" class="form-label">DOB</label>
                                <input type="date" class="form-control" id="dob" name="dob" value="<?= $holdingcage['dob']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="sex" class="form-label">Sex</label>
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
                                <label for="parent_cg" class="form-label">Parent Cage</label>
                                <input type="text" class="form-control" id="parent_cg" name="parent_cg" value="<?= $holdingcage['parent_cg']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <input type="text" class="form-control" id="remarks" name="remarks" value="<?= $holdingcage['remarks']; ?>">
                            </div>

                            <h4>Mouse #1</h4>

                            <div class="mb-3">
                                <label for="mouse_id_1" class="form-label">Mouse ID</label>
                                <input type="text" class="form-control" id="mouse_id_1" name="mouse_id_1" value="<?= $holdingcage['mouse_id_1']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="genotype_1" class="form-label">Genotype</label>
                                <input type="text" class="form-control" id="genotype_1" name="genotype_1" value="<?= $holdingcage['genotype_1']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="notes_1" class="form-label">Maintenance Notes</label>
                                <input type="text" class="form-control" id="notes_1" name="notes_1" value="<?= $holdingcage['notes_1']; ?>">
                            </div>

                            <h4>Mouse #2</h4>

                            <div class="mb-3">
                                <label for="mouse_id_2" class="form-label">Mouse ID</label>
                                <input type="text" class="form-control" id="mouse_id_2" name="mouse_id_2" value="<?= $holdingcage['mouse_id_2']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="genotype_2" class="form-label">Genotype</label>
                                <input type="text" class="form-control" id="genotype_2" name="genotype_2" value="<?= $holdingcage['genotype_2']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="notes_2" class="form-label">Maintenance Notes</label>
                                <input type="text" class="form-control" id="notes_2" name="notes_2" value="<?= $holdingcage['notes_2']; ?>">
                            </div>

                            <h4>Mouse #3</h4>
                            <div class="mb-3">
                                <label for="mouse_id_3" class="form-label">Mouse ID</label>
                                <input type="text" class="form-control" id="mouse_id_3" name="mouse_id_3" value="<?= $holdingcage['mouse_id_3']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="genotype_2" class="form-label">Genotype</label>
                                <input type="text" class="form-control" id="genotype_3" name="genotype_3" value="<?= $holdingcage['genotype_3']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="notes_3" class="form-label">Maintenance Notes</label>
                                <input type="text" class="form-control" id="notes_3" name="notes_3" value="<?= $holdingcage['notes_3']; ?>">
                            </div>

                            <h4>Mouse #4</h4>

                            <div class="mb-3">
                                <label for="mouse_id_4" class="form-label">Mouse ID</label>
                                <input type="text" class="form-control" id="mouse_id_4" name="mouse_id_4" value="<?= $holdingcage['mouse_id_4']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="genotype_4" class="form-label">Genotype</label>
                                <input type="text" class="form-control" id="genotype_4" name="genotype_4" value="<?= $holdingcage['genotype_4']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="notes_4" class="form-label">Maintenance Notes</label>
                                <input type="text" class="form-control" id="notes_4" name="notes_4" value="<?= $holdingcage['notes_4']; ?>">
                            </div>

                            <h4>Mouse #5</h4>

                            <div class="mb-3">
                                <label for="mouse_id_5" class="form-label">Mouse ID</label>
                                <input type="text" class="form-control" id="mouse_id_5" name="mouse_id_5" value="<?= $holdingcage['mouse_id_5']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="genotype_5" class="form-label">Genotype</label>
                                <input type="text" class="form-control" id="genotype_5" name="genotype_5" value="<?= $holdingcage['genotype_5']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="notes_5" class="form-label">Maintenance Notes</label>
                                <input type="text" class="form-control" id="notes_5" name="notes_5" value="<?= $holdingcage['notes_5']; ?>">
                            </div>

                            <!-- Display Files Section -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4>Manage Files</h4>
                                </div>

                                <div class="card-body">
                                    <?php
                                    // Assume $files is fetched from database as per previous discussions
                                    while ($file = $files->fetch_assoc()) {
                                        echo "<a href='" . htmlspecialchars($file['file_path']) . "' download='" . htmlspecialchars($file['file_name']) . "'>" . htmlspecialchars($file['file_name']) . "</a> ";
                                        echo "<a href='delete_file.php?id=" . intval($file['id']) . "' onclick='return confirm(\"Are you sure you want to delete this file?\");'>Delete</a><br>";
                                    }
                                    ?>
                                </div>

                                <div class="mb-3">
                                    <label for="fileUpload" class="form-label">Upload File</label>
                                    <input type="file" class="form-control" id="fileUpload" name="fileUpload">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-primary" onclick="goBack()">Go Back</button>

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