#!/bin/bash
 
date=`date +%m-%d-%Y`
tar='/usr/bin/tar'
filename='cms-hosting-be'
exclude="--exclude=./backup  
         --exclude=./cache/*
         --exclude=./vendor  
         --exclude=./public_html/node_modules
         --exclude=./config/config.php
         --exclude=./ssl.*
         --exclude=./tmp
         --exclude=./fcgi-bin
         --exclude=./Maildir
         --exclude=./cgi-bin
         --exclude=./etc
         --exclude=./logs
         --exclude=./homes
         --exclude=./.*
         --exclude=./create-package.sh
         --exclude=./public_html/DlVWpBGNqk1oY4H1eYqod6KEw88oDYL5.txt
         --exclude=./virtualmin-backup"
         

dir='/home/cms/'

$tar $exclude -czvf /home/cms/backup/$filename-$date.tar -C $dir . 

