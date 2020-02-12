#!/bin/bash

. /usr/share/afik1/read_config.sh

#-----------------------------------------------------------------------
# Read Configuration file

readconfig


#-----------------------------------------------------------------------
# Expand name to full path

execpath=`echo $0 | sed 's/^\.\///'`
pwd=`pwd`

if [ `echo $execpath | grep -v '^/'` ]; then
    execpath=${pwd}/${execpath}
fi

if [ `echo $execpath | grep -v '/etc/cron\(\.\|\/\)[^/]\+'` ]; then
    echo "cron only"
    exit
fi

cronpart=`echo $execpath | sed 's/.*cron\(\.\|\/\)\([^\/]\+\).*/cron.\2/'`
cronpath=${PROGRAM_CRON}/${cronpart}

if [ ! -d $cronpath ]; then
    echo "Cron directory '$cronpath' not found"
fi


${PROGRAM_SYSTEM}/run-parts.sh $cronpath
