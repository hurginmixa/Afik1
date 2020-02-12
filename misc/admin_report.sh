#!/bin/bash

. /usr/share/afik1/read_config.sh

#-----------------------------------------------------------------------
# Read Configuration file
readconfig

#-----------------------------------------------------------------------
# Run program

rm -f /tmp/admin_report.$$

echo "To: $ADMIN_RPT_RECIVERS"                              >> /tmp/admin_report.$$
echo "Subject: $LOCAL_DOMAIN Daily Administrator's Reports" >> /tmp/admin_report.$$

echo -n "Date: "                                            >> /tmp/admin_report.$$
echo date "+%a, %d %b %Y %T %z"                             >> /tmp/admin_report.$$


echo ""                                                     >> /tmp/admin_report.$$

echo "Admin report from $LOCAL_SERVER" >> /tmp/admin_report.$$

echo ========================== >> /tmp/admin_report.$$
echo 'SELECT sysnum AS num, size, used, (used + 0.0) / size * 100.0 AS percent FROM storages ORDER BY sysnum' | psql -U $POSTGRES_USER $DBASE >> /tmp/admin_report.$$

echo ========================== >> /tmp/admin_report.$$
du -hL $PROGRAM_FILES/storage*/ >> /tmp/admin_report.$$

echo ========================== >> /tmp/admin_report.$$
df -h $PROGRAM_FILES/storage*/  >> /tmp/admin_report.$$

echo ========================== >> /tmp/admin_report.$$
$PROGRAM_MISC/check_database.pl >> /tmp/admin_report.$$

echo ========================== >> /tmp/admin_report.$$
$PROGRAM_MISC/purge.pl --show-only --no-check --no-nlink-update --delay=0 >> /tmp/admin_report.$$

cat /tmp/admin_report.$$ | /usr/sbin/sendmail -f "$ADMIN_RPT_SENDER" "$ADMIN_RPT_RECIVERS"

rm -f /tmp/admin_report.$$

