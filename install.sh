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

# database connection data
hostname="db01.hostingbe.lan"
database="test_website"
username="test-website"
password=""
# if you have another directory name as public website enter it below
public="public_html"

#
# No need to edit the file below this line!
#
curl="/usr/bin/curl"

echo "Checking if we need to update?"

if [ -f $(pwd)'/config/config.php' ]; then
  echo "config.php found aborting install!"
  exit 0
fi

echo "Checking if public directory exists"

if [ -d $(pwd)'/public_html' ]; then
  mv $(pwd)'/public_html' $(pwd)'/public_html-'date("+%Y-%m-%d")
fi

echo "Downloading latest source code"
${curl} https://github.com/HostingBE/simple-cms/archive/refs/heads/master.zip -o simple-cms.zip 

echo "Move download to seperate directory"
mv simple-cms.zip $(pwd)/simple-cms/.

echo "Unzip download file in temp directory"
unzip $(pwd)/simple-cms/simple-cms.zip

echo "Moving the CMS to original directory"
${rsync} -rv $(pwd)/simple-cms/ $(pwd)

echo "Deleting the simple-cms directory"
rm -rf $(pwd)/simple-cms/

echo "Creating the .env file for the CMS"
mv $(pwd)/env ${pwd}/.env

echo "Running composer update"
if ! [ $(composer update; echo $0) ]; then
    exit 1
fi

echo "copy config.php to config directory"
mv -p $(pwd)/config/config-sample.php $(pwd)/config/config.php

sed -i -e "s/database=/database=${database}/" $(pwd)/.env
sed -i -e "s/username=/username=${username}/" $(pwd)/.env
sed -i -e "s/password=/password=${password}/" $(pwd)/.env
sed -i -e "s/host=/host=${host}/" $(pwd)/.env

if [ ${public} ne "public_html" ]; then
mv $(pwd)/public_html/ $(pwd)/${public}/
fi

echo "Moving htaccess to .htaccess";
mv $(pwd)/${public}/htaccess $(pwd)/${public}/.htaccess

echo "Running npm update\n"
cd $(pwd)/${public};npm install;cd ..

echo "Making the temp directory"

mkdir $(pwd)/tmp

echo "Import the database schema";
mysql -u ${script_variables['username']} -p${script_variables['password']} -h ${script_variables['host']} ${script_variables['database']} < $(pwd)/sql/simple_cms.sql
mysql -u ${script_variables['username']} -p${script_variables['password']} -h ${script_variables['host']} ${script_variables['database']} < $(pwd)/sql/simple_cms_data.sql

echo "done";
