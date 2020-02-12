#!/bin/bash
#
#
#


. /usr/share/afik1/read_config.sh

#-----------------------------------------------------------------------
# Read Configuration file
readconfig

#-----------------------------------------------------------------------
# Rotation

$PROGRAM_MISC/shiftlogs.pl --max_number=4 "$PROGRAM_LOG/*_log.txt"
$PROGRAM_MISC/shiftlogs.pl --max_number=2 "$PROGRAM_LOG/*.dmp" "$PROGRAM_LOG/*.debug"

exit 0
