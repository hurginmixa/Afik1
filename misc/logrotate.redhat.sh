#!/bin/bash
#
#
#


. /usr/share/afik1/read_config.sh

#-----------------------------------------------------------------------
# Read Configuration file
readconfig

#-----------------------------------------------------------------------
# Define logrotate
logrotate=`which logrotate 2>/dev/null`
if [ "$logrotate" == "" ]; then
  echo "logrotate program not fount" 1>&2
  exit 1
fi


#-----------------------------------------------------------------------
# create logrotate.conf file
cat > /tmp/afik1_logrotate.conf_$$ <<-EOM
$PROGRAM_LOG/*_log.txt
{
           daily
           rotate 4
           missingok
           nocompress
}

$PROGRAM_LOG/*.dmp $PROGRAM_LOG/*.debug
{
           daily
           rotate 2
           missingok
           nocompress
}
EOM


#-----------------------------------------------------------------------
# logrotate
logrotate -f /tmp/afik1_logrotate.conf_$$
rm /tmp/afik1_logrotate.conf_$$


exit 0
