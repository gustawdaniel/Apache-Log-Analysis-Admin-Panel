#!/usr/bin/env bash

# installation of goaccess

#echo "deb http://deb.goaccess.io/ $(lsb_release -cs) main" | sudo tee -a /etc/apt/sources.list.d/goaccess.list
#wget -O - https://deb.goaccess.io/gnugpg.key | sudo apt-key add -
#sudo apt-get update
#sudo apt-get install goaccess


# lines to uncomment in /etc/goaccess.conf

#time-format %H:%M:%S
#date-format %d/%b/%Y
#log-format %h %^[%d:%t %^] "%r" %s %b "%R" "%u"

composer install
bower install

bash build.sh

cd web && php -S localhost:8000