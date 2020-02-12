#!/bin/bash

. /usr/share/afik1/read_config.sh

readconfig
RETVAL=$?
if [ $RETVAL != 0 ]; then
   failure "$prog: Error read config - "
   echo
   return $RETVAL
fi


if [ -e $FTP_ProcNumer ]
then
  a=`cat $FTP_ProcNumer`
  kill $a
fi
