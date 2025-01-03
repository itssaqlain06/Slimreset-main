#!/bin/bash

# Update the system
sudo yum -y update

# Install essential utilities
sudo yum -y install ruby
sudo yum -y install wget
sudo yum -y install unzip
sudo yum -y install nano

# Install and configure Apache, PHP, and dependencies
sudo yum -y install httpd php php-mysqlnd php-cli php-zip php-json php-curl php-mbstring php-xml php-bcmath
sudo systemctl start httpd
sudo systemctl enable httpd

# Install Python and AWS CLI
sudo yum -y install python3 python3-pip
sudo pip3 install awscli

# Install CodeDeploy Agent
cd /home/ec2-user
wget https://aws-codedeploy-ap-south-1.s3.ap-south-1.amazonaws.com/latest/install
sudo chmod +x ./install
sudo ./install auto

# Install MySQL community server
sudo yum clean all
sudo rpm --import https://repo.mysql.com/RPM-GPG-KEY-mysql-2022
sudo yum install -y mysql-community-server --nogpgcheck
sudo systemctl start mysqld
sudo systemctl enable mysqld

# Install and configure phpMyAdmin
cd /var/www/html
sudo wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz
sudo tar -xvzf phpMyAdmin-latest-all-languages.tar.gz
sudo mv phpMyAdmin-*-all-languages phpmyadmin
sudo rm phpMyAdmin-latest-all-languages.tar.gz

# Configure phpMyAdmin
cd /var/www/html/phpmyadmin
sudo cp config.sample.inc.php config.inc.php
sudo nano config.inc.php
# In nano, update the blowfish secret key:
# $cfg['blowfish_secret'] = 'your_random_32_character_key';

# Set permissions for phpMyAdmin
sudo chown -R apache:apache /var/www/html/phpmyadmin
sudo chmod -R 755 /var/www/html/phpmyadmin
sudo systemctl restart httpd

# Configure MySQL root password
sudo nano /etc/my.cnf
# Add these lines temporarily for password reset:
# [mysqld]
# skip-grant-tables
# skip-networking

sudo systemctl restart mysqld
mysql -u root <<EOF
USE mysql;
UPDATE user SET authentication_string = PASSWORD('new_password'), plugin = 'mysql_native_password' WHERE User = 'root';
FLUSH PRIVILEGES;
EOF

# Remove temporary MySQL settings
sudo nano /etc/my.cnf
# Comment out the following lines:
# # skip-grant-tables
# # skip-networking

sudo systemctl restart mysqld

# Finalize permissions and setup
sudo chmod -R 755 /var/www/html/phpmyadmin

# Test phpMyAdmin access
echo "phpMyAdmin setup complete. Access it at: http://<YOUR_EC2_PUBLIC_IP>/phpmyadmin"
