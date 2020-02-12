#!/bin/bash

. /usr/share/afik1/read_config.sh

#-----------------------------------------------------------------------
# Read Configuration file
readconfig

#-----------------------------------------------------------------------
# Run programm
$PROGRAM_MISC/check_database.pl --diagnostic-level=2 --silently
