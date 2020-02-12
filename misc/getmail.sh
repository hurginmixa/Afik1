#!/bin/sh

#echo $1
#whoami
#echo =========================================

cd /var/afik1

../c/parsemail -d --dbase=afik1 --filedir=files --debug --log=maillog/$1 $1 2>>maillog/$1.error 1>>maillog/$1.debug
