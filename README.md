# Simple CMS #

Simple CMS written in PHP with slim 4 framework

## Howto get started ##

* Copy config/config-sample.php to config/config.php and change the settings
* import the database sql schema in mysql
* run composer update
* run rpm i in the public_html directory

## crontab ##

their are several crontabs which you can set which will run on the times your specified

```
0 3 * * * /usr/bin/php /home/username/bin/console.php reminder-email activate-reminder
30 3 * * * /usr/bin/php /home/username/bin/console.php reminder-email send-reminders
```

## Website design ##

Design done by [https://getbootstrap.com], create a custom SCSS and create the CSS file for your design
