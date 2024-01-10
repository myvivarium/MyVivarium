<?php
session_start();
require 'dbcon.php';

// Check if the ID parameter is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the matingcage record with the specified ID
    $query = "SELECT * FROM matingcage WHERE `cage id` = '$id'";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) === 1) {
        $matingcage = mysqli_fetch_assoc($result);

        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get the form data
            $cageID = $_POST['cage_id'];
            $piName = $_POST['pi_name'];
            $cross = $_POST['cross'];
            $iacuc = $_POST['iacuc'];
            $user = $_POST['user'];
            $maleID = $_POST['male_id'];
            $maleDOB = $_POST['male_DOB'];
            $DOM = $_POST['DOM'];
            $femaleID = $_POST['female_id'];
            $femaleDOB = $_POST['female_DOB'];
            $litterDOB = $_POST['litter_DOB'];
            $pupsAD = $_POST['pups_ad'];
            $male = $_POST['male'];
            $female = $_POST['female'];
            $remarks = $_POST['remarks'];

            // Update the matingcage record
            $updateQuery = "UPDATE matingcage SET
                            `cage id` = '$cageID',
                            `pi name` = '$piName',
                            `cross` = '$cross',
                            `IACUC` = '$iacuc',
                            `user` = '$user',
                            `male id` = '$maleID',
                            `male DOB` = '$maleDOB',
                            `DOM` = '$DOM',
                            `female id` = '$femaleID',
                            `female DOB` = '$femaleDOB',
                            `litter DOB` = '$litterDOB',
                            `pups ad` = '$pupsAD',
                            `male` = '$male',
                            `female` = '$female',
                            `remarks` = '$remarks'
                            WHERE `cage id` = '$id'";
            mysqli_query($con, $updateQuery);

            // Redirect to mating.php with a success message
            $_SESSION['message'] = 'Entry updated successfully.';
            header('Location: mating.php');
            exit();
        }
    } else {
        $_SESSION['message'] = 'Invalid ID.';
        header('Location: mating.php');
        exit();
    }
} else {
    $_SESSION['message'] = 'ID parameter is missing.';
    header('Location: mating.php');
    exit();
}
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <title>Edit mating Cage</title>

</head>

<body>

    <div class="container mt-4">

        <?php include('message.php'); ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit mating Cage</h4>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="matingedit.php?id=<?= $id; ?>">
                        <div class="mb-3">
                                <label for="cage_id" class="form-label">Cage ID</label>
                                <input type="text" class="form-control" id="cage_id" name="cage_id"
                                    value="<?= $matingcage['cage id']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="pi_name" class="form-label">PI Name</label>
                                <input type="text" class="form-control" id="pi_name" name="pi_name"
                                    value="<?= $matingcage['pi name']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="cross" class="form-label">Cross</label>
                                <input type="text" class="form-control" id="cross" name="cross"
                                    value="<?= $matingcage['cross']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="iacuc" class="form-label">IACUC</label>
                                <input type="text" class="form-control" id="iacuc" name="iacuc"
                                    value="<?= $matingcage['IACUC']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="user" class="form-label">User</label>
                                <input type="text" class="form-control" id="user" name="user"
                                    value="<?= $matingcage['user']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="male_id" class="form-label">Male ID</label>
                                <input type="text" class="form-control" id="male_id" name="male_id"
                                    value="<?= $matingcage['male id']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="male_DOB" class="form-label">Male DOB</label>
                                <input type="date" class="form-control" id="male_DOB" name="male_DOB"
                                    value="<?= $matingcage['male DOB']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="female_id" class="form-label">Female ID</label>
                                <input type="text" class="form-control" id="female_id" name="female_id"
                                    value="<?= $matingcage['female id']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="female_DOB" class="form-label">female DOB</label>
                                <input type="text" class="form-control" id="female_DOB" name="female_DOB"
                                    value="<?= $matingcage['female DOB']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="DOM" class="form-label">DOM (Separate with Commas)</label>
                                <input type="text" class="form-control" id="DOM" name="DOM"
                                    value="<?= $matingcage['DOM']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="litter_DOB" class="form-label">Litter DOB (Separate with Commas)</label>
                                <input type="text" class="form-control" id="litter_DOB" name="litter_DOB"
                                    value="<?= $matingcage['litter DOB']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="pups_ad" class="form-label">Pups (A/D) (Separate with Commas)</label>
                                <input type="text" class="form-control" id="pups_ad" name="pups_ad"
                                    value="<?= $matingcage['pups ad']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="male" class="form-label">Male (Separate with Commas)</label>
                                <input type="text" class="form-control" id="male" name="male"
                                    value="<?= $matingcage['male']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="female" class="form-label">Female (Separate with Commas)</label>
                                <input type="text" class="form-control" id="female" name="female"
                                    value="<?= $matingcage['female']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks (Separate with Commas)</label>
                                <input type="text" class="form-control" id="remarks" name="remarks"
                                    value="<?= $matingcage['remarks']; ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="mating.php" class="btn btn-secondary">Go Back</a>
        </div>

    </div>

</body>

</html>
