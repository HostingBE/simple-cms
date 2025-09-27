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

# some program locations
composer="/usr/bin/composer"
rm="/usr/bin/rm"
rsync="/usr/bin/rsync"
cat="/usr/bin/cat"
sed="/usr/bin/sed"

echo "Updating Simple CMS to Latest version"

echo "Cloning the latest release from git"
git clone https://github.com/HostingBE/simple-cms.git

echo "Create a backup of the current version\n"
tar -cvf  $(pwd)/backup-$(date +"%Y-%m-%d-%H").tar $(pwd)/app $(pwd)/bootstrap $(pwd)/Classes composer.json $(pwd)/config $(pwd)/Classes  $(pwd)/lang $(pwd)/routes $(pwd)/logs $(pwd)/templates $(pwd)/public_html

echo "Moving the CMS to original directory"
${rsync} -rv $(pwd)/simple-cms/ $(pwd)

echo "Getting the new version from the config file"
version=`${cat} $(pwd)/simple-cms/config/config-sample.php | grep version | awk '{ print $3 }' | sed -e s/[\'\,]//g`

echo "Removing the source github directory"
${rm} -rf $(pwd)/simple-cms

echo "Moving old node_modules to start fresh"
${rm} -rf $(pwd)/public_html/node_modules/

echo "Installing the new npm files"
cd public_html/;npm i;cd $(pwd)/../

echo "Removing the old vendor directory"
${rm} -rf $(pwd)/vendor

echo "Running composer update to the latest"
${composer} update

echo "Updating current version in config"
${sed} -i -e "s/'version' => '.*'/'version' => '${version}'/" $(pwd)/config/config.php

echo "Remove all unwanted files"