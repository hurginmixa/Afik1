#!/bin/sh

. /usr/share/afik1/read_config.sh

readconfig
RETVAL=$?
if [ $RETVAL != 0 ]; then
   return $RETVAL
fi

#echo "$1 $2" > ../debug

#echo "$1 $2" > /tmp/sendmail_tmp

#echo /usr/sbin/sendmail -f "$2" -O DeliveryMod=q "$1" >> /tmp/sendmail_tmp

/usr/sbin/sendmail -f "$2" "$1"

