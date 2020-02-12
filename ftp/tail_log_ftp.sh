#!/bin/bash

#-----------------------------------------------------------------------
# Get Exec Path for Script

execpath=$0
if [ "`ls -l $execpath | grep '\->'`" != "" ]; then
  execpath=`ls -l $execpath | sed 's/.* \([^\ ]*\)$/\1/g'`
fi

execpath=`echo $execpath | sed 's/[^\/]*$//' | sed 's/^\.\///'`
if [ `echo $execpath | grep '^[^\/]'` ]; then
  pwd=`pwd`
  execpath=`echo "$pwd/$execpath"` 
fi
execpath=`echo $execpath | sed 's/\/[^\/]*\/\.\.//g' | sed 's/^$/./'`


#-----------------------------------------------------------------------
# Read confugurations file
if [ -f ${execpath}/afik1.cf ]; then
  sed 's/ //g' ${execpath}/afik1.cf > /tmp/conf_$$
  . /tmp/conf_$$
  rm /tmp/conf_$$
else
  echo "afik1.cf configuration file in path ${execpath} not found" 1>&2
  exit 1
fi


if [ -e $FTP_LogFileName ]
then
  tail -f $FTP_LogFileName
fi
