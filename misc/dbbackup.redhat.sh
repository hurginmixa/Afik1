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
cat > /tmp/dbbackup_rotate.conf_$$ <<-EOM
$PROGRAM_ARC/dbbackup.sql $SQL_LogFileName {
    rotate 48
    daily
    nocompress
    missingok
}
EOM


#-----------------------------------------------------------------------
# logrotate
logrotate -f /tmp/dbbackup_rotate.conf_$$
rm /tmp/dbbackup_rotate.conf_$$


#-----------------------------------------------------------------------
# backup
su - -s /bin/bash -c "pg_dump -U $POSTGRES_USER $DBASE > $PROGRAM_ARC/dbbackup.sql" $POSTGRES_USER
su - -s /bin/bash -c "echo \"vacuum;\" | psql -U $POSTGRES_USER $DBASE > /dev/null" $POSTGRES_USER


su - -s /bin/bash -c "pg_dump -U $POSTGRES_USER -s $DBASE > $PROGRAM_ARC/dbstructure.sql" $POSTGRES_USER

if [ ! -e $PROGRAM_SQL/dbstructure.sql -o `diff -f $PROGRAM_ARC/dbstructure.sql $PROGRAM_SQL/dbstructure.sql | wc -l` != 0 ]; then
        cp $PROGRAM_ARC/dbstructure.sql $PROGRAM_SQL/dbstructure.sql
fi

exit 0
