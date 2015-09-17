#!/bin/sh
mysql -uroot -pcontrol -e "CREATE DATABASE confone"
mysql -uroot -pcontrol -e "GRANT ALL ON confone.* TO 'confone'@'%' IDENTIFIED BY 'confonepass'"
mysql -uroot -pcontrol -e "FLUSH PRIVILEGES"

for jj in ../schema/*.sql; do
    echo $jj
    mysql -uconfone -pconfonepass confone < $jj
done
