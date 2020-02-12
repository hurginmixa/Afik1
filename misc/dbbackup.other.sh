#!/bin/bash
#
#
#


. /usr/share/afik1/read_config.sh

#-----------------------------------------------------------------------
# Read Configuration file
readconfig


#-----------------------------------------------------------------------
# logrotate
$PROGRAM_MISC/shiftlogs.pl --max_number=48 "$PROGRAM_ARC/dbbackup.sql" "$SQL_LogFileName"


#-----------------------------------------------------------------------
# backup
su - -c "pg_dump -U $POSTGRES_USER $DBASE > $PROGRAM_ARC/dbbackup.sql" $POSTGRES_USER
su - -c "echo \"vacuum;\" | psql -U $POSTGRES_USER $DBASE > /dev/null" $POSTGRES_USER


su - -c "pg_dump -U $POSTGRES_USER -s $DBASE > $PROGRAM_ARC/dbstructure.sql" $POSTGRES_USER

if [ ! -e $PROGRAM_SQL/dbstructure.sql -o `diff -f $PROGRAM_ARC/dbstructure.sql $PROGRAM_SQL/dbstructure.sql | wc -l` != 0 ]; then
        cp $PROGRAM_ARC/dbstructure.sql $PROGRAM_SQL/dbstructure.sql
fi

exit 0
