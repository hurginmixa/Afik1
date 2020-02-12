#!/bin/bash

. /usr/share/afik1/read_config.sh

readconfig
RETVAL=$?
if [ $RETVAL != 0 ]; then
   failure "$prog: Error read config - "
   echo
   return $RETVAL
fi

$PROGRAM_FTP/stop_ftp.sh
$FTP_Program
