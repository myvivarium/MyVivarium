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

### Function of Each File
- `index.php`: Main entry point for the application, handles user login.
- `config.php`: Contains SMTP configuration.
- `dbcon.php`: Manages database connections.
- `user_profile.php`: Allows users to update their profile and request password resets.
- `register.php`: Handles user registration and email verification.
- `reset_password.php`: Manages the password reset process.
- `manage_lab.php`: Allows admins to manage lab details.
- `manage_users.php`: Provides functionalities for admin to manage users.

## Usage
1. Access the application in your web browser:
    ```
    http://yourdomain.com
    ```

2. Register a new user or log in with existing credentials.

3. Manage your lab, users, and monitor environmental conditions in real-time.

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



## Contributing
We welcome contributions to improve MyVivarium. Please follow these steps to contribute:

1. Fork the repository.
2. Create a new branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/your-feature-name`
5. Open a pull request.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

