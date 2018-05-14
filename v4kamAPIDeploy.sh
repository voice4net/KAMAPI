#!/bin/bash

#This script should be executed with root previlage
#Assumptions
#1. Underlying os is debian
#2. No other sites are running apart from kamapi.
#3. Enter credentials for mysql kamailio user
#4. ignore this error :	Failed to restart kamailio.service: Unit rtpengine.service failed to load: No such file or directory.

#usage sudo ./v4kamAPIDeploy.sh -p testpsd

passphrase=""

while getopts ":p:" opt; do
  case ${opt} in
    p )
      passphrase=$OPTARG
      ;;
  esac
done

if [ -z "$passphrase" ]
then
      read -p 'Enter Mysql kamailio user password: ' passphrase
fi

mysql -ukamailio -p$passphrase kamailio  -e"quit" || exit 1 

if ! grep -q 'modparam("ctl", "mode"' /etc/kamailio/kamailio.cfg
then
	sed -i 's/####### Routing Logic ########/# ----- ctl params ----- #\nmodparam("ctl","mode",0770)\nmodparam("ctl","use r","kamailio")\nmodparam("ctl","group","kamailio")\n\n\0/' /etc/kamailio/kamailio.cfg 
fi

cp kamailio_files/kamailio.service /lib/systemd/system/

kamctl stop

kamctl start

apt-get update -y

apt-get install apache2 -y

apt-get install php5 -y

apt-get install php5-mysql -y

apt-get install openssl -y

a2enmod ssl

a2ensite default-ssl

a2enmod headers

service apache2 restart

mkdir /var/www/html/kamapi/

sed -i -e 's/__PASSPHRASE__/'$passphrase'/g' kamapi/db.php

cp kamapi/* /var/www/html/kamapi/

cp apache_config_files/apache2.conf /etc/apache2/

cp apache_config_files/default-ssl.conf /etc/apache2/sites-available/

mysql -ukamailio -p$passphrase kamailio < configuringKAMTables.sql

usermod -a -G kamailio www-data

chown kamailio:kamailio /var/run/kamailio

service apache2 restart

#shutdown -r now
echo "System Should be rebooted before using API"
