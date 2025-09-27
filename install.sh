#!/bin/bash

: '
/**
* @author Constan van Suchtelen van de Haere <constan.vansuchtelenvandehaere@hostingbe.com>
* @copyright 2024 - 2025 HostingBE
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
* files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
* is furnished to do so, subject to the following conditions:

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
* OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
*/
'

# Database Connection strings
hostname=""
database=""
username=""
password=""

# If you have another directory name as public website enter it below
public="public_html"

# domain without https://
url="yourdomain.com"

# SMTP settings 
smtp_port=587
smtp_username=
smtp_password=
smtp_host=
smtp_from=
smtp_tls=true
smtp_auth=true

#
# No need to edit the file below this line!
#
version="simple-cms-3.1"
curl="/usr/bin/curl"
mkdir="/usr/bin/mkdir"
rsync="/usr/bin/rsync"

echo "Checking if we need to update?"

if [ -f $(pwd)'/config/config.php' ]; then
  echo "config.php found aborting install!"
  exit 0
fi

echo "Checking if public directory exists"

if [ -d $(pwd)'/public_html' ]; then
  mv $(pwd)'/public_html' $(pwd)'/public_html-'$(date +"%Y-%m-%d")
fi

echo "Downloading latest source code"
${curl} -L -o simple-cms.zip https://github.com/HostingBE/simple-cms/archive/refs/tags/v3.1.zip 

echo "Making temp directory for install"
${mkdir} $(pwd)/simple-cms/

echo "Move download to seperate directory"
mv simple-cms.zip $(pwd)/simple-cms/.

echo "Unzip download file in temp directory"
unzip $(pwd)/simple-cms/simple-cms.zip

echo "Moving the CMS to original directory"
${rsync} -rv $(pwd)/${version}/ $(pwd)

echo "Deleting the simple-cms directory"
rm -rf $(pwd)/simple-cms/
rm -rf $(pwd)/${version}/

echo "Creating the .env file for the CMS"
mv $(pwd)/env $(pwd)/.env

echo "Running composer update"
if ! [ $(composer update; echo $0) ]; then
    exit 1
fi

echo "copy config.php to config directory"
mv $(pwd)/config/config-sample.php $(pwd)/config/config.php

echo "Changing database settings in env"
sed -i -e "s/^database=.*/database=${database}/" $(pwd)/.env
sed -i -e "s/^username=.*/username=${username}/" $(pwd)/.env
sed -i -e "s/^password=.*/password=${password}/" $(pwd)/.env
sed -i -e "s/^host=.*/host=${hostname}/" $(pwd)/.env

echo "Changing SMTP settings in env"
sed -i -e "s/^smtp_port=.*/smtp_port=${smtp_port}/" $(pwd)/.env
sed -i -e "s/^smtp_username=.*/smtp_username=${smtp_username}/" $(pwd)/.env
sed -i -e "s/^smtp_password=.*/smtp_password=${smtp_password}/" $(pwd)/.env
sed -i -e "s/^smtp_host=.*/smtp_host=${smtp_host}/" $(pwd)/.env
sed -i -e "s/^smtp_tls=.*/smtp_tls=${smtp_tls}/" $(pwd)/.env
sed -i -e "s/^smtp_auth=.*/smtp_auth=${smtp_auth}/" $(pwd)/.env
sed -i -e "s/^smtp_from=.*/smtp_from=${smtp_from}/" $(pwd)/.env

echo "Changing the domain name in the config file"
sed -i -e "s/\[domain\]/${url}/" $(pwd)/config/config.php
sed -i -e "s/\[domain\]/${url}/" $(pwd)/sql/simple_cms_data.sql
sed -i -e "s/\[email\]/${smtp_from}/" $(pwd)/sql/simple_cms_data.sql

if [ ${public} != "public_html" ]; then
mv $(pwd)/public_html/ $(pwd)/${public}/
fi

echo "Moving htaccess to .htaccess";
mv $(pwd)/${public}/htaccess $(pwd)/${public}/.htaccess

echo "Running npm update\n"
cd $(pwd)/${public};npm install;cd ..

echo "Making the temp and other directories"

mkdir $(pwd)/tmp
mkdir $(pwd)/${public}/uploads
mkdir $(pwd)/${public}/images/users

echo "Import the database schema";
mysql -u ${username} -p${password} -h ${hostname} ${database} < $(pwd)/sql/simple_cms.sql
mysql -u ${username} -p${password} -h ${hostname} ${database} < $(pwd)/sql/simple_cms_data.sql

echo "done";
