#!/bin/bash

# Variables - Set your repository and application directory
REPO_URL="https://github.com/myvivarium/MyVivarium.git"
APP_DIR="/var/www/html"
DB_NAME="myvivarium"
DB_USER="myvivarium"
DB_PASSWORD=""  # This will be set through user prompt

# Inform user of fixed database name and user
echo "Using fixed database name as '$DB_NAME' and database user as '$DB_USER'."

# Prompt user for required details
read -sp "Enter the database password: " DB_PASSWORD
echo
read -p "Enter your email (for SSL certificate setup and SMTP): " EMAIL
read -p "Enter your domain name (or droplet IP if not using a domain): " DOMAIN

# Step 0: Create deployuser if it doesn't exist
if id "deployuser" &>/dev/null; then
    echo "User deployuser already exists."
else
    echo "Creating user deployuser..."
    sudo adduser --disabled-password --gecos "" deployuser
    sudo usermod -aG www-data deployuser
    sudo chown -R deployuser:www-data $APP_DIR
fi

# Step 1: Check and Install Required Packages
echo "Checking and installing necessary packages..."
declare -a packages=("git" "composer" "unzip" "apache2" "mysql-server" "certbot" "python3-certbot-apache" "php-mbstring")
for pkg in "${packages[@]}"; do
    if ! dpkg -l | grep -q $pkg; then
        echo "Installing $pkg..."
        sudo apt install -y $pkg
    else
        echo "$pkg is already installed."
    fi
done

# Step 2: Clone the Full Repository if Needed
# Check if repository contents already exist
if [ ! -f "$APP_DIR/setup/setup.sh" ]; then
    echo "Cloning the repository..."
    sudo rm -rf $APP_DIR/*  # Clear any default files
    sudo git clone $REPO_URL $APP_DIR
else
    echo "Repository already cloned."
fi
cd $APP_DIR

# Step 3: Install Composer Dependencies as deployuser
echo "Installing Composer dependencies..."
sudo -u deployuser composer install --no-interaction --no-scripts

# Step 4: Configure Environment Variables
if [ ! -f ".env" ]; then
    echo "Setting up environment file..."
    cp .env.example .env
fi

# Step 5: Update .env with Database and SMTP Configurations
echo "Configuring environment variables in .env file..."
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env

# Prompt for SMTP settings if not present in .env
SMTP_HOST=$(grep -E '^MAIL_HOST=' .env)
SMTP_USER=$(grep -E '^MAIL_USERNAME=' .env)
SMTP_PASS=$(grep -E '^MAIL_PASSWORD=' .env)

if [ -z "$SMTP_HOST" ]; then
    read -p "Enter SMTP Host (e.g., smtp.yourdomain.com): " SMTP_HOST
    sed -i "s/^MAIL_HOST=.*/MAIL_HOST=$SMTP_HOST/" .env
fi
if [ -z "$SMTP_USER" ]; then
    read -p "Enter SMTP Username: " SMTP_USER
    sed -i "s/^MAIL_USERNAME=.*/MAIL_USERNAME=$SMTP_USER/" .env
fi
if [ -z "$SMTP_PASS" ]; then
    read -sp "Enter SMTP Password: " SMTP_PASS
    echo
    sed -i "s/^MAIL_PASSWORD=.*/MAIL_PASSWORD=$SMTP_PASS/" .env
fi

# Step 6: Set Up MySQL Database and Import Schema
echo "Setting up MySQL database with fixed name and user..."
sudo mysql -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS $DB_NAME;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

# Import the database schema from /database/schema.sql
echo "Importing database schema..."
if [ -f "database/schema.sql" ]; then
    sudo mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME < database/schema.sql
    echo "Database schema imported successfully."
else
    echo "Error: schema.sql file not found in /database directory."
fi

# Step 7: Set Permissions
echo "Setting permissions for Apache..."
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR

# Step 8: Obtain SSL Certificate (Optional)
if [ "$DOMAIN" != "" ]; then
    echo "Obtaining SSL certificate with Certbot..."
    sudo certbot --apache --non-interactive --agree-tos -m $EMAIL -d $DOMAIN
fi

echo "Deployment completed successfully! Access your application at http://$DOMAIN or https://$DOMAIN if SSL is enabled."
