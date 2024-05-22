<?php
require 'dbcon.php';

// Query to fetch the lab name
$labQuery = "SELECT lab_name FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

$labName = "My Vivarium"; // A default value in case the query fails or returns no result
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($labName); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
        }

        /* Header and Footer Styling */
        .header-footer {
            background-color: #343a40;
            padding: 20px 0;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        .header-footer .logo-container {
            padding: 0;
            margin: 0;
        }

        .header-footer img.header-logo {
            width: 300px;
            height: auto;
            display: block;
            margin: 0;
        }

        .header-footer h2 {
            margin-left: 15px;
            margin-bottom: 0;
            margin-top: 12px;
            font-size: 3.5rem;
            white-space: nowrap;
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            color: #fff;
        }

        @media (max-width: 576px) {
            .header-footer h2 {
                font-size: 2.2rem;
                margin-left: 10px;
            }

            .header-footer img.header-logo {
                width: 150px;
            }
        }

        /* Navigation Menu Styling */
        .nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .dropdown-menu {
            min-width: auto;
        }

        /* Center Main Content */
        .main-content {
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <div class="header-footer">
        <div class="logo-container">
            <img src="images/logo1.webp" alt="Logo" class="header-logo">
        </div>
        <h2><?php echo htmlspecialchars($labName); ?></h2>

        <div class="row align-items-center">

                <!-- Navigation Menu -->
                <nav class="nav">
                    <a href="home.php" class="btn btn-primary">Home</a>
                    <!-- Dropdown for Dashboard -->
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="dashboardMenuButton"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Dashboards
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dashboardMenuButton">
                            <li><a class="dropdown-item" href="hc_dash.php">Holding Cage</a></li>
                            <li><a class="dropdown-item" href="bc_dash.php">Breeding Cage</a></li>
                        </ul>
                    </div>
                    <?php
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                        echo '<a href="admin.php" class="btn btn-primary">Admin</a>';
                    }
                    ?>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </nav>

        </div>
    </div>
</body>

</html>
