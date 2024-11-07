#!/bin/bash

# === Variables - Set your repository and application directory ===
REPO_URL="https://github.com/myvivarium/MyVivarium.git"
APP_DIR="/var/www/html"
DB_NAME="myvivarium"
DB_USER="myvivarium"
DB_PASSWORD=""

# === Start of Script ===
echo "Welcome to the MyVivarium Deployment Script!"
echo

# Inform user of fixed database name and user
echo "Using fixed database name as '$DB_NAME' and database user as '$DB_USER'."
echo

# === Prompt for Required Details ===
read -sp "Enter the database password: " DB_PASSWORD
echo
echo

read -p "Enter your email (for SSL certificate setup and SMTP): " EMAIL
echo

read -p "Enter your domain name (or droplet IP if not using a domain): " DOMAIN
echo

# === Step 0: Create deployuser if it doesn't exist ===
if id "deployuser" &>/dev/null; then
    echo "User 'deployuser' already exists."
else
    echo "Creating user 'deployuser'..."
    sudo adduser --disabled-password --gecos "" deployuser
    sudo usermod -aG www-data deployuser
    sudo chown -R deployuser:www-data $APP_DIR
    echo "User 'deployuser' created and configured."
fi
echo

# === Step 1: Check and Install Required Packages ===
echo "Checking and installing necessary packages..."
declare -a packages=("git" "composer" "unzip" "apache2" "mysql-server" "certbot" "python3-certbot-apache")
for pkg in "${packages[@]}"; do
    if ! dpkg -l | grep -q $pkg; then
        echo "Installing $pkg..."
        sudo apt install -y $pkg
    else
        echo "$pkg is already installed."
    fi
done
echo

# === Step 2: Clone the Repository if Needed ===
if [ ! -f "$APP_DIR/setup/setup.sh" ]; then
    echo "Cloning the repository from $REPO_URL..."
    sudo rm -rf $APP_DIR/*  # Clear any default files
    sudo git clone $REPO_URL $APP_DIR
    echo "Repository cloned to $APP_DIR."
else
    echo "Repository already cloned in $APP_DIR."
fi
cd $APP_DIR
echo

# === Step 3: Install Composer Dependencies ===
echo "Installing Composer dependencies..."
sudo -u deployuser composer install --no-interaction --no-scripts
echo

# === Step 4: Configure Environment Variables ===
if [ ! -f ".env" ]; then
    echo "Creating .env file from example..."
    cp .env.example .env
fi
echo

# === Step 5: Update .env with Database and SMTP Configurations ===
echo "Configuring database and SMTP settings in .env file..."
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env

echo "Updating SMTP settings in .env file..."
echo

# Prompt for SMTP settings and update or add them in .env
read -p "Enter SMTP Host (e.g., smtp.gmail.com): " SMTP_HOST
sed -i "s/^SMTP_HOST=.*/SMTP_HOST=$SMTP_HOST/" .env || echo "SMTP_HOST=$SMTP_HOST" >> .env
echo

read -p "Enter SMTP Port (e.g., 587): " SMTP_PORT
sed -i "s/^SMTP_PORT=.*/SMTP_PORT=$SMTP_PORT/" .env || echo "SMTP_PORT=$SMTP_PORT" >> .env
echo

read -p "Enter SMTP Username (e.g., myvivarium.online@gmail.com): " SMTP_USERNAME
sed -i "s/^SMTP_USERNAME=.*/SMTP_USERNAME=$SMTP_USERNAME/" .env || echo "SMTP_USERNAME=$SMTP_USERNAME" >> .env
echo

read -sp "Enter SMTP Password: " SMTP_PASSWORD
echo
sed -i "s/^SMTP_PASSWORD=.*/SMTP_PASSWORD=$SMTP_PASSWORD/" .env || echo "SMTP_PASSWORD=$SMTP_PASSWORD" >> .env
echo

read -p "Enter SMTP Encryption (e.g., tls): " SMTP_ENCRYPTION
sed -i "s/^SMTP_ENCRYPTION=.*/SMTP_ENCRYPTION=$SMTP_ENCRYPTION/" .env || echo "SMTP_ENCRYPTION=$SMTP_ENCRYPTION" >> .env
echo

read -p "Enter Sender Email Address (e.g., myvivarium.online@gmail.com): " SENDER_EMAIL
sed -i "s/^SENDER_EMAIL=.*/SENDER_EMAIL=$SENDER_EMAIL/" .env || echo "SENDER_EMAIL=$SENDER_EMAIL" >> .env
echo

read -p "Enter Sender Name (e.g., MyVivarium): " SENDER_NAME
sed -i "s/^SENDER_NAME=.*/SENDER_NAME=$SENDER_NAME/" .env || echo "SENDER_NAME=$SENDER_NAME" >> .env
echo

# === Step 6: Set Up MySQL Database and Import Schema ===
echo "Setting up MySQL database '$DB_NAME' with user '$DB_USER'..."
sudo mysql -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS $DB_NAME;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT
echo

echo "Importing database schema..."
if [ -f "database/schema.sql" ]; then
    sudo mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME < database/schema.sql
    echo "Database schema imported successfully."
else
    echo "Error: schema.sql file not found in /database directory."
fi
echo

# === Step 7: Set Permissions ===
echo "Setting permissions for Apache..."
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
echo

# === Step 8: Set Up Apache Virtual Host ===
echo "Setting up Apache virtual host for $DOMAIN..."
VHOST_CONFIG="/etc/apache2/sites-available/$DOMAIN.conf"
sudo bash -c "cat > $VHOST_CONFIG" <<EOL
<VirtualHost *:80>
    ServerAdmin $EMAIL
    ServerName $DOMAIN
    DocumentRoot $APP_DIR

    <Directory $APP_DIR>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/$DOMAIN_error.log
    CustomLog \${APACHE_LOG_DIR}/$DOMAIN_access.log combined
</VirtualHost>
EOL

sudo a2ensite $DOMAIN.conf
sudo systemctl reload apache2
echo

# === Step 9: Obtain SSL Certificate (Optional) ===
if [ "$DOMAIN" != "" ]; then
    echo "Obtaining SSL certificate for $DOMAIN..."
    sudo certbot --apache --non-interactive --agree-tos -m $EMAIL -d $DOMAIN
fi

sudo systemctl reload apache2

# === Step 10: Set Up Cron Jobs for PHP Scripts ===
echo "Setting up cron jobs to run send_email.php and process_reminders.php every minute..."

# Define paths for the PHP scripts
SEND_EMAIL_PATH="$APP_DIR/send_email.php"
PROCESS_REMINDERS_PATH="$APP_DIR/process_reminders.php"

# Add cron jobs
(crontab -l 2>/dev/null; echo "* * * * * /usr/bin/php $SEND_EMAIL_PATH > /dev/null 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "* * * * * /usr/bin/php $PROCESS_REMINDERS_PATH > /dev/null 2>&1") | crontab -

echo "Cron jobs have been set up to run every minute."
echo

echo
echo "Deployment completed successfully! You can access your application at:"
echo " - http://$DOMAIN (without SSL)"
echo " - https://$DOMAIN (with SSL if enabled)"
