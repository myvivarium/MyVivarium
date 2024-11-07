![Logo](https://myvivarium.online/images/logo1.jpg)

# MyVivarium

![Project Status](https://img.shields.io/badge/status-active-brightgreen) [![LGPL License](https://img.shields.io/badge/License-LGPL--3.0-blue.svg)](https://choosealicense.com/licenses/lgpl-3.0/)

[![Visit Demo Site](https://img.shields.io/badge/Visit-Demo%20Site-blue?style=for-the-badge)](https://demo.myvivarium.online)

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?&style=for-the-badge&logo=php&logoColor=white) ![HTML](https://img.shields.io/badge/html5-%23E34F26.svg?&style=for-the-badge&logo=html5&logoColor=white) ![CSS](https://img.shields.io/badge/css3-%231572B6.svg?&style=for-the-badge&logo=css3&logoColor=white) ![JavaScript](https://img.shields.io/badge/javascript-%23F7DF1E.svg?&style=for-the-badge&logo=javascript&logoColor=black) ![Font Awesome](https://img.shields.io/badge/font%20awesome-%23339AF0.svg?&style=for-the-badge&logo=font-awesome&logoColor=white) ![Bootstrap](https://img.shields.io/badge/bootstrap-%23563D7C.svg?&style=for-the-badge&logo=bootstrap&logoColor=white)

MyVivarium is an online platform designed to manage your vivarium effectively. It provides features such as user registration, profile management, lab management, and real-time environmental monitoring with IoT sensors.

## Table of Contents
- [Features](#features)
- [Screenshot](#screenshot)
- [Installation](#installation)
- [Usage](#usage)
- [Function of Each File](#function-of-each-file)
- [Citations](#citations)
- [Contributing](#contributing)
- [License](#license)

## Features
- User registration and login with email verification.
- User profile management and password reset.
- Admin functionalities for managing users and labs.
- Real-time environmental monitoring using IoT sensors. For more details, refer to the [RPi-IoT Repository](https://github.com/myvivarium/RPi-IoT).
- Secure and compliant data management.

![image](https://myvivarium.online/images/illustration.jpg)

## Installation

### 1. DigitalOcean One-Click Install (Recommended for Ease of Use)

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=fdb1aa3adb7d&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)

> Get $200 in credit over 60 days when you sign up using the above referral link!

#### Steps:

1. **Sign up for a DigitalOcean account** using the referral link above to get your credits.

2. **Create a PHPMyAdmin Droplet**: Use this link to deploy DigitalOcean's [PHPMyAdmin Droplet](https://marketplace.digitalocean.com/apps/phpmyadmin), which installs PHPMyAdmin, MySQL Server, Apache, PHP, and Certbot as a 1-click setup.

3. **Access the Droplet**:
   - Once your droplet is ready, use the console option in the DigitalOcean dashboard to access the server.
   - If you have a domain, use the droplet’s IPv4 address to set up an A record in your domain DNS settings.

4. **Download the Installation Script**:
    After connecting to your Linux server's console, use the following command to download the installation shell script:

   ```bash
   curl -O https://raw.githubusercontent.com/myvivarium/MyVivarium/main/setup/setup.sh```
    ```
5. **Make the Script Executable**:
   ```bash
   chmod +x setup.sh
    ```
6. **Run the Script**:
   ```bash
   sudo ./setup.sh
    ```
7. **Follow the Script Prompts**:
   - Provide details such as database password, email, domain name, and SMTP settings to complete the installation and configuration.

8. **Complete Setup**:
   - Once DNS settings propagate (if using a domain), the site will be accessible, and you can begin using MyVivarium

### 2. Custom Installation

#### Prerequisites
- PHP 7.4 or higher
- MySQL
- Composer
- Web server (e.g., Apache, Nginx)
- Tutorial to install Linux, Apache, MySQL, PHP (LAMP) Stack on Ubuntu - [DigitalOcean LAMP Stack Tutorial](https://www.digitalocean.com/community/tutorials/how-to-install-lamp-stack-on-ubuntu)

#### Steps

1. **Clone the repository:**
    ```bash
    git clone https://github.com/myvivarium/MyVivarium.git
    ```
    or
    ```bash
    git clone git@github.com:myvivarium/MyVivarium.git
    ```
    ```bash
    cd MyVivarium
    ````

2. **Set up the environment configuration:**
    - Copy the `.env.example` to `.env`:
        ```bash
        cp .env.example .env
        ```
    - Update the `.env` file with your database and SMTP settings. See [Configuration](#configuration)

3. **Place the project files in the web server directory:**
    - Move all the contents of the MyVivarium directory to your web server’s public directory (e.g., `public_html`, `www`):
        ```bash
        mv * /path/to/your/public_html/
        cp .env /path/to/your/public_html/
        ```

4. **Install dependencies using Composer:**
    ```bash
    composer install
    ```

5. **Set up the database:**
    - Log in to your MySQL server:
        ```bash
        mysql -u yourusername -p
        ```
    - Create the database and import the schema:
        ```sql
        CREATE DATABASE myvivarium;
        USE myvivarium;
        SOURCE /path/to/your/public_html/database/schema.sql;
        ```
    - Alternatively, you can use your own database or an already existing database:
        ```sql
        SOURCE /path/to/your/public_html/database/schema.sql;
        ```

6. **Set up a cron job for `send_email.php`:**
    Follow the instructions on [Cloudways Blog](https://www.cloudways.com/blog/schedule-cron-jobs-in-php/#run-a-cron-job-in-php) to schedule a cron job for `send_email.php`.

7. **Set ownership and permissions (if required):**
    ```bash
    sudo chown -R www-data:www-data /path/to/your/public_html
    sudo chmod -R 755 /path/to/your/public_html
    ```

#### Configuration
##### SMTP Configuration
Update the following environment variables in your `.env` file:
 ```bash
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=username
SMTP_PASSWORD=password
SMTP_ENCRYPTION=tls
SENDER_EMAIL=sender@example.com
SENDER_NAME=MyVivarium
 ```

##### Database Configuration
Ensure the database credentials are set correctly in the `.env` file:
 ```bash
DB_HOST=localhost
DB_USERNAME=username
DB_PASSWORD=password
DB_DATABASE=myvivarium
 ```

## Screenshot

![image](https://myvivarium.online/images/myvivarium.gif)

## Usage
1. Access the application in your web browser:
    ```
    http://yourdomain.com
    ```

2. Register a new user or log in with existing credentials.

3. Manage your lab, users, and monitor environmental conditions in real-time.

### Default Admin User

For initial setup, use the following default admin credentials:

- **Email**: admin@myvivarium.online
- **Password**: password

**Important**: Delete this default admin user and create a new admin user after the initial setup for security reasons.

## Function of Each File
- `dbcon.php`: Manages database connections.
- `config.php`: Contains SMTP configuration.
- `index.php`: Main entry point for the application, handles user login.
- `register.php`: Handles user registration and email verification.
- `home.php`: Displays the home page with a welcome message, cage statistics, and general notes.
- `forgot_password.php`: Handles the password reset process, including generating and sending reset emails.
- `reset_password.php`: Manages the password reset process.
- `confirm_email.php`: Manages email confirmation by verifying tokens and updating user status.
- `user_profile.php`: Allows users to update their profile and request password resets.
- `manage_users.php`: Provides functionalities for admin to manage users.
- `manage_lab.php`: Allows admins to manage lab details.
- `manage_strain.php`: Allows admins to manage mouse strain details.
- `manage_iacuc.php`: Allows admins to manage IACUC details.
- `iot_sensors.php`: Displays IoT sensor data for different rooms using iframes.
- `bc_dash.php`: Displays a dashboard for managing breeding cages with search and pagination.
- `bc_fetch_data.php`: Handles pagination and search functionality for breeding cages.
- `bc_addn.php`: Handles the creation of new breeding cages and associated litter data.
- `bc_view.php`: Viewing the details of a breeding cage.
- `bc_edit.php`: Manages editing of breeding cage details, including litter information and file uploads.
- `bc_drop.php`: Handles the deletion of breeding cages and their related data.
- `bc_slct_crd.php`: Selects breeding cages for printing cage cards.
- `bc_prnt_crd.php`: Generates printable cards for breeding cages with their latest litter records.
- `hc_dash.php`: Displays a dashboard for managing holding cages.
- `hc_fetch_data.php`: Handles pagination and search functionality for holding cages.
- `hc_addn.php`: Adds new holding cages.
- `hc_view.php`: Viewing the details of a holding cage.
- `hc_edit.php`: Manages editing of holding cage details.
- `hc_drop.php`: Handles the deletion of holding cages.
- `hc_slct_crd.php`: Selects holding cages for printing cage cards.
- `hc_prnt_crd.php`: Generates printable cards for holding cages.
- `nt_app.php`: Main script for the sticky note application.
- `nt_add.php`: Adds new sticky notes.
- `nt_edit.php`: Edits existing sticky notes.
- `nt_rmv.php`: Removes sticky notes.
- `header.php`: Generates the header and navigation menu for the web application.
- `footer.php`: Provides the footer section with dynamic lab name and current year.
- `message.php`: Displays session messages as Bootstrap alerts.
- `logout.php`: Logs out the user by destroying the session and redirecting to the login page.
- `delete_file.php`: Script handles deleting uploaded files from the server and database.
- `export_data.php`: Allows admins to export all database tables into CSV files.
- `manage_tasks.php`: Manages tasks in a database, allowing users to add, edit, and delete tasks.
- `get_task.php`: Retrieves specific task details from the database with the provided task ID.
- `send_email.php`: Processes a queue of pending emails, requires setting it up as a cron job.
- `demo-banner.php`: Displays a banner at the top of the page in demo mode.
- `demo-credentials.php`: Displays the demo admin credentials on the login page.
- `demo-disclaimer.php`: Displays the demo disclaimer on the login page.
- `maintenance.php`: Adds maintenance records for cages with optional comments.

## Demo Website

### Explore MyVivarium

We have a demo website available for you to explore the features of MyVivarium. Click the button below to access the demo site:

[![Visit Demo Site](https://img.shields.io/badge/Visit-Demo%20Site-blue?style=for-the-badge)](https://demo.myvivarium.online) _(opens in a new tab)_

### Access Details

To log in and explore the functionalities, please use the following access details:

- **Email**: `admin@myvivarium.online`
- **Password**: `password`

### Important Notice

**Disclaimer**: This is a demo site for exploring features. All data will be cleared periodically. Do not enter any sensitive or critical information.

Feel free to explore, test, and provide feedback. Enjoy your experience with MyVivarium!

---

## Citations

If you use this code, please cite the following paper:

Vidva, R., Raza, M. A., Prabhakaran, J., Sheikh, A., Sharp, A., Ott, H., Moore, A., Fleisher, C., Pitychoutis, P. M., Nguyen, T. V., & Sathyanesan, A. (2024). MyVivarium: A cloud-based lab animal colony management application with near-realtime ambient sensing. *bioRxiv*. https://doi.org/10.1101/2024.08.10.607395

### BibTeX

```bibtex
@article {Vidva2024.08.10.607395,
	author = {Vidva, Robinson and Raza, Mir Abbas and Prabhakaran, Jaswant and Sheikh, Ayesha and Sharp, Alaina and Ott, Hayden and Moore, Amelia and Fleisher, Christopher and Pitychoutis, Pothitos M. and Nguyen, Tam V. and Sathyanesan, Aaron},
	title = {MyVivarium: A cloud-based lab animal colony management application with near-realtime ambient sensing},
	elocation-id = {2024.08.10.607395},
	year = {2024},
	doi = {10.1101/2024.08.10.607395},
	publisher = {Cold Spring Harbor Laboratory},
	URL = {https://www.biorxiv.org/content/early/2024/08/10/2024.08.10.607395},
	eprint = {https://www.biorxiv.org/content/early/2024/08/10/2024.08.10.607395.full.pdf},
	journal = {bioRxiv}
}
```

## Contributing
We welcome contributions to improve MyVivarium. Please follow these steps to contribute:

1. Fork the repository.
2. Create a new branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/your-feature-name`
5. Open a pull request.

## License
This project is licensed under the LGPL License - see the [LICENSE](LICENSE) file for details.

