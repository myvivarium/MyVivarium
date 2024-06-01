<?php
session_start();
require 'dbcon.php';

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Redirect to index.php if the user is not logged in
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
    exit;
}

// Query to retrieve options where role is 'Principal Investigator'
$query1 = "SELECT name FROM users WHERE position = 'Principal Investigator' AND status = 'approved'";
$result1 = $con->query($query1);

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the breedingcage record with the specified ID
    $query = "SELECT * FROM bc_basic WHERE `cage_id` = '$id'";
    $result = mysqli_query($con, $query);

    // Fetch files associated with the specified cage ID
    $query2 = "SELECT * FROM files WHERE cage_id = '$id'";
    $files = $con->query($query2);

    // Fetch the breedingcage litter record with the specified ID
    $query3 = "SELECT * FROM bc_litter WHERE `cage_id` = '$id'";
    $litters = mysqli_query($con, $query3);

    if (mysqli_num_rows($result) === 1) {
        $breedingcage = mysqli_fetch_assoc($result);

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die('CSRF token validation failed');
            }

            // Retrieve and sanitize form data
            $cage_id = mysqli_real_escape_string($con, $_POST['cage_id']);
            $pi_name = mysqli_real_escape_string($con, $_POST['pi_name']);
            $cross = mysqli_real_escape_string($con, $_POST['cross']);
            $iacuc = mysqli_real_escape_string($con, $_POST['iacuc']);
            $user = mysqli_real_escape_string($con, $_POST['user']);
            $male_id = mysqli_real_escape_string($con, $_POST['male_id']);
            $female_id = mysqli_real_escape_string($con, $_POST['female_id']);
            $male_dob = mysqli_real_escape_string($con, $_POST['male_dob']);
            $female_dob = mysqli_real_escape_string($con, $_POST['female_dob']);
            $remarks = mysqli_real_escape_string($con, $_POST['remarks']);

            // Prepare the update query with placeholders
            $updateQuery = $con->prepare("UPDATE bc_basic SET 
                                    `cage_id` = ?, 
                                    `pi_name` = ?, 
                                    `cross` = ?, 
                                    `iacuc` = ?, 
                                    `user` = ?, 
                                    `male_id` = ?, 
                                    `female_id` = ?, 
                                    `male_dob` = ?, 
                                    `female_dob` = ?, 
                                    `remarks` = ? 
                                    WHERE `cage_id` = ?");

            // Bind parameters
            $updateQuery->bind_param("sssssssssss", $cage_id, $pi_name, $cross, $iacuc, $user, $male_id, $female_id, $male_dob, $female_dob, $remarks, $id);

            // Execute the statement and check if it was successful
            if ($updateQuery->execute()) {
                $_SESSION['message'] = 'Entry updated successfully.';
            } else {
                $_SESSION['message'] = 'Update failed: ' . $updateQuery->error;
            }

            // Close the prepared statement
            $updateQuery->close();

            // Handle file upload
            if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == UPLOAD_ERR_OK) {
                $targetDirectory = "uploads/$cage_id/";

                // Create the cage_id specific sub-directory if it doesn't exist
                if (!file_exists($targetDirectory)) {
                    mkdir($targetDirectory, 0777, true); // true for recursive create (if needed)
                }

                $originalFileName = basename($_FILES['fileUpload']['name']);
                $targetFilePath = $targetDirectory . $originalFileName;

                // Check if file already exists
                if (!file_exists($targetFilePath)) {
                    if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $targetFilePath)) {
                        // Insert file info into the database
                        $insert = $con->prepare("INSERT INTO files (file_name, file_path, cage_id) VALUES (?, ?, ?)");
                        $insert->bind_param("sss", $originalFileName, $targetFilePath, $cage_id);
                        if ($insert->execute()) {
                            $_SESSION['message'] = "File uploaded successfully.";
                        } else {
                            $_SESSION['message'] = "File upload failed, please try again.";
                        }
                    } else {
                        $_SESSION['message'] = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $_SESSION['message'] = "Sorry, file already exists.";
                }
            } else if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] != UPLOAD_ERR_NO_FILE) {
                $_SESSION['message'] = "File upload error: " . $_FILES['fileUpload']['error'];
            }

            // Initialize arrays
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
            if (count($dom) > 0) {
                for ($i = 0; $i < count($dom); $i++) {
                    $dom_i = mysqli_real_escape_string($con, $dom[$i]);
                    $litter_dob_i = mysqli_real_escape_string($con, $litter_dob[$i]);
                    $pups_alive_i = mysqli_real_escape_string($con, $pups_alive[$i]);
                    $pups_dead_i = mysqli_real_escape_string($con, $pups_dead[$i]);
                    $pups_male_i = mysqli_real_escape_string($con, $pups_male[$i]);
                    $pups_female_i = mysqli_real_escape_string($con, $pups_female[$i]);
                    $remarks_litter_i = mysqli_real_escape_string($con, $remarks_litter[$i]);
                    $litter_id_i = mysqli_real_escape_string($con, $litter_id[$i]);

                    if (!empty($litter_id_i)) {
                        // Update existing litter entry
                        $updateLitterQuery = $con->prepare("UPDATE bc_litter SET `dom` = ?, `litter_dob` = ?, `pups_alive` = ?, `pups_dead` = ?, `pups_male` = ?, `pups_female` = ?, `remarks` = ? WHERE `id` = ?");
                        $updateLitterQuery->bind_param("ssssssss", $dom_i, $litter_dob_i, $pups_alive_i, $pups_dead_i, $pups_male_i, $pups_female_i, $remarks_litter_i, $litter_id_i);
                        $updateLitterQuery->execute();
                        $updateLitterQuery->close();
                    } else {
                        // Insert new litter entry
                        $insertLitterQuery = $con->prepare("INSERT INTO bc_litter (`cage_id`, `dom`, `litter_dob`, `pups_alive`, `pups_dead`, `pups_male`, `pups_female`, `remarks`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $insertLitterQuery->bind_param("ssssssss", $cage_id, $dom_i, $litter_dob_i, $pups_alive_i, $pups_dead_i, $pups_male_i, $pups_female_i, $remarks_litter_i);
                        $insertLitterQuery->execute();
                        $insertLitterQuery->close();
                    }
                }
            }

            // Handle deleted litter entries
            if (count($delete_litter_ids) > 0) {
                foreach ($delete_litter_ids as $delete_litter_id) {
                    if (!empty($delete_litter_id)) {
                        $deleteLitterQuery = $con->prepare("DELETE FROM bc_litter WHERE id = ?");
                        $deleteLitterQuery->bind_param("s", $delete_litter_id);
                        $deleteLitterQuery->execute();
                        $deleteLitterQuery->close();
                    }
                }
            }

            header("Location: bc_dash.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: bc_dash.php");
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header("Location: bc_dash.php");
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
        }

        function adjustTextareaHeight(element) {
            element.style.height = "auto";
            element.style.height = (element.scrollHeight) + "px";
        }

        function addLitter() {
            const litterDiv = document.createElement('div');
            litterDiv.className = 'litter-entry';

            litterDiv.innerHTML = `
        <hr>
        <div class="mb-3">
            <label for="dom[]" class="form-label">DOM</label>
            <input type="date" class="form-control" name="dom[]" required>
        </div>
        <div class="mb-3">
            <label for="litter_dob[]" class="form-label">Litter DOB</label>
            <input type="date" class="form-control" name="litter_dob[]">
        </div>
        <div class="mb-3">
            <label for="pups_alive[]" class="form-label">Pups Alive</label>
            <input type="number" class="form-control" name="pups_alive[]" required min="0" step="1">
        </div>
        <div class="mb-3">
            <label for="pups_dead[]" class="form-label">Pups Dead</label>
            <input type="number" class="form-control" name="pups_dead[]" required min="0" step="1">
        </div>
        <div class="mb-3">
            <label for="pups_male[]" class="form-label">Pups Male</label>
            <input type="number" class="form-control" name="pups_male[]" required min="0" step="1">
        </div>
        <div class="mb-3">
            <label for="pups_female[]" class="form-label">Pups Female</label>
            <input type="number" class="form-control" name="pups_female[]" required min="0" step="1">
        </div>
        <div class="mb-3">
            <label for="remarks_litter[]" class="form-label">Remarks Litter</label>
            <textarea class="form-control" name="remarks_litter[]" oninput="adjustTextareaHeight(this)"></textarea>
        </div>
        <input type="hidden" name="litter_id[]" value="">
        <button type="button" class="btn btn-danger" onclick="removeLitter(this)">Remove</button>
    `;

            document.getElementById('litterEntries').appendChild(litterDiv);
        }

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
    </script>

    <title>Edit Breeding Cage | <?php echo htmlspecialchars($labName); ?></title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            /* Light grey background */
        }

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

        @media (max-width: 768px) {

            .table-wrapper th,
            .table-wrapper td {
                padding: 12px 8px;
            }

            .table-wrapper th,
            .table-wrapper td {
                text-align: center;
            }
        }
    </style>

</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Breeding Cage</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="bc_edit.php?id=<?= htmlspecialchars($id); ?>" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID</label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id" value="<?= htmlspecialchars($breedingcage['cage_id']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="pi_name" class="form-label">PI Name</label>
                                <select class="form-control" id="pi_name" name="pi_name" required>
                                    <option value="<?= htmlspecialchars($breedingcage['pi_name']); ?>" selected>
                                        <?= htmlspecialchars($breedingcage['pi_name']); ?>
                                    </option>
                                    <?php while ($row = $result1->fetch_assoc()) : ?>
                                        <?php if ($row['name'] != $breedingcage['pi_name']) : ?>
                                            <option value="<?= htmlspecialchars($row['name']); ?>"><?= htmlspecialchars($row['name']); ?></option>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="cross" class="form-label">Cross</label>
                                <input type="text" class="form-control" id="cross" name="cross" value="<?= htmlspecialchars($breedingcage['cross']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="iacuc" class="form-label">IACUC</label>
                                <input type="text" class="form-control" id="iacuc" name="iacuc" value="<?= htmlspecialchars($breedingcage['iacuc']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="user" class="form-label">User</label>
                                <input type="text" class="form-control" id="user" name="user" value="<?= htmlspecialchars($breedingcage['user']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="male_id" class="form-label">Male ID</label>
                                <input type="text" class="form-control" id="male_id" name="male_id" value="<?= htmlspecialchars($breedingcage['male_id']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="female_id" class="form-label">Female ID</label>
                                <input type="text" class="form-control" id="female_id" name="female_id" value="<?= htmlspecialchars($breedingcage['female_id']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="male_dob" class="form-label">Male DOB</label>
                                <input type="date" class="form-control" id="male_dob" name="male_dob" value="<?= htmlspecialchars($breedingcage['male_dob']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="female_dob" class="form-label">Female DOB</label>
                                <input type="date" class="form-control" id="female_dob" name="female_dob" value="<?= htmlspecialchars($breedingcage['female_dob']); ?>" required>
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
                                                <label for="dom[]" class="form-label">DOM</label>
                                                <input type="date" class="form-control" name="dom[]" value="<?= htmlspecialchars($litter['dom']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="litter_dob[]" class="form-label">Litter DOB</label>
                                                <input type="date" class="form-control" name="litter_dob[]" value="<?= htmlspecialchars($litter['litter_dob']); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_alive[]" class="form-label">Pups Alive</label>
                                                <input type="number" class="form-control" name="pups_alive[]" value="<?= htmlspecialchars($litter['pups_alive']); ?>" required min="0" step="1">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_dead[]" class="form-label">Pups Dead</label>
                                                <input type="number" class="form-control" name="pups_dead[]" value="<?= htmlspecialchars($litter['pups_dead']); ?>" required min="0" step="1">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_male[]" class="form-label">Pups Male</label>
                                                <input type="number" class="form-control" name="pups_male[]" value="<?= htmlspecialchars($litter['pups_male']); ?>" required min="0" step="1">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pups_female[]" class="form-label">Pups Female</label>
                                                <input type="number" class="form-control" name="pups_female[]" value="<?= htmlspecialchars($litter['pups_female']); ?>" required min="0" step="1">
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