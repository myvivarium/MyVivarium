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
declare -a packages=("git" "composer" "unzip" "apache2" "mysql-server" "certbot" "python3-certbot-apache")
for pkg in "${packages[@]}"; do
    if ! dpkg -l | grep -q $pkg; then
        echo "Installing $pkg..."
        sudo apt install -y $pkg
    else
        echo "$pkg is already installed."
    fi
done

# Step 2: Clone the Full Repository if Needed
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

echo "Update SMTP settings..."

# Prompt for SMTP settings and update or add them in .env
read -p "Enter SMTP Host (e.g., smtp.gmail.com): " SMTP_HOST
sed -i "s/^SMTP_HOST=.*/SMTP_HOST=$SMTP_HOST/" .env || echo "SMTP_HOST=$SMTP_HOST" >> .env

read -p "Enter SMTP Port (e.g., 587): " SMTP_PORT
sed -i "s/^SMTP_PORT=.*/SMTP_PORT=$SMTP_PORT/" .env || echo "SMTP_PORT=$SMTP_PORT" >> .env

read -p "Enter SMTP Username (e.g., myvivarium.online@gmail.com): " SMTP_USERNAME
sed -i "s/^SMTP_USERNAME=.*/SMTP_USERNAME=$SMTP_USERNAME/" .env || echo "SMTP_USERNAME=$SMTP_USERNAME" >> .env

read -sp "Enter SMTP Password: " SMTP_PASSWORD
echo
sed -i "s/^SMTP_PASSWORD=.*/SMTP_PASSWORD=$SMTP_PASSWORD/" .env || echo "SMTP_PASSWORD=$SMTP_PASSWORD" >> .env

read -p "Enter SMTP Encryption (e.g., tls): " SMTP_ENCRYPTION
sed -i "s/^SMTP_ENCRYPTION=.*/SMTP_ENCRYPTION=$SMTP_ENCRYPTION/" .env || echo "SMTP_ENCRYPTION=$SMTP_ENCRYPTION" >> .env

read -p "Enter Sender Email Address (e.g., myvivarium.online@gmail.com): " SENDER_EMAIL
sed -i "s/^SENDER_EMAIL=.*/SENDER_EMAIL=$SENDER_EMAIL/" .env || echo "SENDER_EMAIL=$SENDER_EMAIL" >> .env

read -p "Enter Sender Name (e.g., MyVivarium): " SENDER_NAME
sed -i "s/^SENDER_NAME=.*/SENDER_NAME=$SENDER_NAME/" .env || echo "SENDER_NAME=$SENDER_NAME" >> .env

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

# Step 8: Set Up Apache Virtual Host
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

# Step 9: Obtain SSL Certificate (Optional)
if [ "$DOMAIN" != "" ]; then
    echo "Obtaining SSL certificate with Certbot..."
    sudo certbot --apache --non-interactive --agree-tos -m $EMAIL -d $DOMAIN
fi

sudo systemctl reload apache2

echo "Deployment completed successfully! Access your application at http://$DOMAIN or https://$DOMAIN if SSL is enabled."
