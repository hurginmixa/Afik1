#package ftp_const;

require 5.000;
require Exporter;

@ISA = qw/Exporter/;
@EXPORT = qw/IPPROTO_IP IP_TOS/;


# use strict 'vars';

BEGIN
{
    $const::const = {
                        IPPROTO_IP => 0,
                        IP_TOS => 1
                     };
}


sub AUTOLOAD {
    my($s) = $AUTOLOAD;
    # print "AUTOLOAD ", $s, "\n";
    $s =~ s/.*:://;

    return ${$const::const}{$s} if (defined(${$const::const}{$s}));
    return ("$s" . 1);
}


1;
