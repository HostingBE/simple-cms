# Simple CMS #

Simple CMS written in PHP with the use of slim 4 framework, needs PHP 8.1 or higher and composer installed.

![Screenshot Simple CMS](images/screenshot-simple-cms.png)

## Howto get started ##
* Clone the repository or download the zip file
* Copy config/config-sample.php to config/config.php and change the settings
* Import the database sql schema from the sql directory in your database
* Run composer update (this will install all dependencies which are needed)
* Run npm i in the public_html directory (this will install all dependencies which are needed)
* Rename htaccess to .htaccess in public_html directory (if it does not exist!)

## Features ##

* blog
* contact
* roles (visitor/customer/administrator)
* support wiki
* forum
* customization via seperate TWIG templates
* multi language
* administration backend

## Crontab ##

Their are several crontab jobs which you can set which will run on the times you specified

The activate reminder script will send reminders to users who did not activate the account yet.
```
0 3 * * * /usr/bin/php /home/username/bin/console.php reminder-email activate-reminder
```

The send-reminders will send reminders to users who are not logged in for a certain count of days (7,30,60)
```
30 3 * * * /usr/bin/php /home/username/bin/console.php reminder-email send-reminders
```

## Website design ##

Design used [https://getbootstrap.com], create a custom SCSS and create the CSS file for your design from bootstrap
