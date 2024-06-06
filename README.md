# MyVivarium

MyVivarium is an online platform designed to manage your vivarium effectively. It provides features such as user registration, profile management, lab management, and real-time environmental monitoring with IoT sensors.

## Table of Contents
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Contributing](#contributing)
- [License](#license)

## Features
- User registration and login with email verification.
- User profile management and password reset.
- Admin functionalities for managing users and labs.
- Real-time environmental monitoring using IoT sensors.
- Secure and compliant data management.

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL
- Composer
- Web server (e.g., Apache, Nginx)

### Steps

1. **Clone the repository:**
    ```bash
    git clone https://github.com/myvivarium/MyVivarium.git
    cd MyVivarium
    ```

2. **Install dependencies using Composer:**
    ```bash
    composer install
    ```

3. **Set up the environment configuration:**
    - Copy the `.env.example` to `.env`:
        ```bash
        cp .env.example .env
        ```
    - Update the `.env` file with your database and SMTP settings. See [Configuration](#configuration)

4. **Place the project files in the web server directory:**
    - Move all the contents of the MyVivarium directory to your web server’s public directory (e.g., `public_html`, `www`):
        ```bash
        mv * /path/to/your/public_html/
        cp .env /path/to/your/public_html/
        ```
5. **Set ownership and permissions:**
    ```bash
    sudo chown -R www-data:www-data /path/to/your/public_html
    sudo chmod -R 755 /path/to/your/public_html
    ```

6. **Set up the database:**
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
## Configuration
### SMTP Configuration
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

### Database Configuration
Ensure the database credentials are set correctly in the `.env` file:
 ```bash
DB_HOST=localhost
DB_USERNAME=username
DB_PASSWORD=password
DB_DATABASE=myvivarium
 ```

### Default Admin User

For initial setup, use the following default admin credentials:

- **Email**: admin@myvivarium.online
- **Password**: password

**Important**: Delete this default admin user and create a new admin user after the initial setup for security reasons.

## Usage
1. Access the application in your web browser:
    ```
    http://yourdomain.com
    ```

2. Register a new user or log in with existing credentials.

3. Manage your lab, users, and monitor environmental conditions in real-time.

### Function of Each File
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
- `delete_file.php`: Handles the deletion of uploaded files from the server and database.

## Contributing
We welcome contributions to improve MyVivarium. Please follow these steps to contribute:

1. Fork the repository.
2. Create a new branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/your-feature-name`
5. Open a pull request.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
