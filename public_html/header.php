<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sathyanesan Lab's Vivarium</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap JS for Dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
        crossorigin="anonymous"></script>

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
        }
        .header-footer h2,
        .header-footer p {
            margin: 0;
            color: #fff;
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
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Sathyanesan Lab's Vivarium</h2>
                </div>
                <div class="col-md-4 text-end">
                    <!-- Navigation Menu -->
                    <nav class="nav">
                        <a href="home.php" class="btn btn-primary">Home</a>
                        <!-- Dropdown for Dashboard -->
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="dashboardMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                Dashboards
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dashboardMenuButton">
                                <li><a class="dropdown-item" href="hc_dash.php">Holding Cage</a></li>
                                <li><a class="dropdown-item" href="#">Breeding Cage</a></li>
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
        </div>
    </div>
</body>
</html>
