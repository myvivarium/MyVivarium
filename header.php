<?php
require 'dbcon.php';

// Query to fetch the lab name from the database
$labQuery = "SELECT * FROM data LIMIT 1";
$labResult = mysqli_query($con, $labQuery);

$labName = "My Vivarium"; // Default value if the query fails or returns no result
if ($row = mysqli_fetch_assoc($labResult)) {
    $labName = $row['lab_name'];
    $url = $row['url'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon and icons for different devices -->
    <link rel="icon" href="./icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="./icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./icons/favicon-16x16.png">
    <link rel="icon" sizes="192x192" href="./icons/android-chrome-192x192.png">
    <link rel="icon" sizes="512x512" href="./icons/android-chrome-512x512.png">
    <link rel="manifest" href="./icons/manifest.json">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .header-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            background-color: #343a40;
            color: white;
            padding: 1rem;
            text-align: center;
            margin: 0;
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
            font-weight: 500;
        }

        @media (max-width: 576px) {
            .header-footer h2 {
                font-size: 1.8rem;
            }

            .header-footer img.header-logo {
                width: 150px;
            }
        }

        .nav-container {
            background-color: #343a40;
            padding: 0px 0px 20px 0px;
            text-align: center;
            margin: 0;
        }

        .nav .btn {
            margin: 0 5px;
        }

        .dropdown-menu {
            min-width: auto;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <div class="header-footer">
        <div class="logo-container">
            <a href="home.php">
                <img src="images/logo1.jpg" alt="Logo" class="header-logo">
            </a>
        </div>
        <h2><?php echo htmlspecialchars($labName); ?></h2>
    </div>

    <!-- Navigation Menu Section -->
    <div class="nav-container">
        <nav class="nav justify-content-center">
            <a href="home.php" class="btn btn-primary">Home</a>
            
            <!-- Dropdown for Dashboard -->
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dashboardMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    Dashboards
                </button>
                <ul class="dropdown-menu" aria-labelledby="dashboardMenuButton">
                    <li><a class="dropdown-item" href="hc_dash.php">Holding Cage</a></li>
                    <li><a class="dropdown-item" href="bc_dash.php">Breeding Cage</a></li>
                    <li><a class="dropdown-item" href="iot_sensors.php">IOT Sensors</a></li>
                </ul>
            </div>
            
            <!-- Dropdown for Settings -->
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="settingsMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    Settings
                </button>
                <ul class="dropdown-menu" aria-labelledby="settingsMenuButton">
                    <li><a class="dropdown-item" href="user_profile.php">User Profile</a></li>
                    <?php
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                        echo '<li><a class="dropdown-item" href="manage_users.php">Manage Users</a></li>';
                        echo '<li><a class="dropdown-item" href="manage_lab.php">Manage Lab</a></li>';
                    }
                    ?>
                    <li><a class="dropdown-item" href="logout.php">User Profile</a></li>
                </ul>
            </div>

            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </nav>
    </div>

    <!-- Bootstrap and jQuery JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>

</html>
