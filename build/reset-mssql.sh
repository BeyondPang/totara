#!/bin/sh

# .git/hooks/post-checkout must by symbolicly linked to this file

# Warning! It appears when an update to this file is checked out,
# the hook runs the previous version. So it takes an extra build
# to run the new reset file

echo "Update hudson directory permissions"
# when hudson updates it resets to 750 which, makes the webroot
# inaccessible. Resetting every build is overkill but does the
# job
chmod 755 /var/lib/hudson

echo "Delete config.php";
rm config.php

php -f build/reset-mssql-db.php

echo "Delete old moodledata";
rm -Rf ../moodledata/

echo "Re-create moodledata";
mkdir ../moodledata
chmod 777 ../moodledata

echo "Reset apache logs";
rm ../moodle_error.log
touch ../moodle_error.log
chmod 777 ../moodle_error.log

echo "Initialize installation";
/usr/bin/php admin/cliupgrade.php \
      --lang=en_utf8 \
      --webaddr="http://brumbies.wgtn.cat-it.co.nz/totara-mysql-hudson" \
      --moodledir="/var/lib/hudson/jobs/Totara-MySQL/workspace" \
      --datadir="/var/lib/hudson/jobs/Totara-MySQL/moodledata" \
      --dbtype="mysql_n" \
      --dbname="hudson" \
      --dbhost="192.168.198.44" \
      --dbuser="hudson" \
      --dbpass="hudson" \
      --prefix="mdl_" \
      --verbose=3 \
      --sitefullname="Totara" \
      --siteshortname="Totara" \
      --sitesummary="" \
      --sitenewsitems=0 \
      --adminfirstname=Admin \
      --adminlastname=User \
      --adminemail=simonc@catalyst.net.nz \
      --adminusername=admin \
      --adminpassword="passworD1!" \
      --interactivelevel=0

echo "Hit notifications page to complete installation";
wget -O - http://brumbies.wgtn.cat-it.co.nz/totara-mysql-hudson/admin/index.php
