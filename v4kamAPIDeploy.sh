#!/bin/bash

#This script should be executed with root previlage
#Assumptions
#1. Assumes required files are available in /tmp/v4Deploy
#2. Underlying os is debian
#3. No other sites are running apart from kamapi

#usage v4kamAPIDeploy.sh -p testpsd

passphrase=""

while getopts ":p:" opt; do
  case ${opt} in
    p )
      passphrase=$OPTARG
      ;;
  esac
done

sudo su

apt-get update -y

apt-get install apache2 -y

apt-get install php5 -y

apt-get install php5-mysql -y

apt-get install openssl -y

a2enmod ssl

a2ensite default-ssl

a2enmod headers

mkdir /var/www/html/kamapi/

sed -i -e 's/__PASSPHRASE__/'$passphrase'/g' kamapi/db.php

cp kamapi/* /var/www/html/kamapi/

cp apache_config_files/apache2.conf /etc/apache2/

cp apache_config_files/default-ssl.conf /etc/apache2/sites-available/

mysql -ukamailio -p$passphrase kamailio < configuringKAMTables.sql

usermod -a -G kamailio www-data

chown kamailio:kamailio /var/run/kamailio

chmod g+s /var/run/kamailio

setfacl -d -m g::rwx /var/run/kamailio

chmod 630 /var/run/kamailio/ -R

chmod 630 /var/run/kamailio/kamailio_ctl

service apache2 restart

#shutdown -r now
echo "System Should be rebooted before using API"