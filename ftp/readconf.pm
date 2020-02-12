package readconf;

require 5.000;
require Exporter;

@ISA = qw/Exporter/;
@EXPORT = qw/ReadConfigFile ReadConfig/;


sub ReadConfigFile($)
{
    my($ExePath) = @_;

    %main::INI_ARG = ();
    $main::INI_ARG{PROTOCOL} = exists($ENV{HTTPS}) && defined($ENV{HTTPS}) && $ENV{HTTPS} eq "on" ? "https:" : "http:";

    my($conf);
    local *INI_FILE;
    if (!open (INI_FILE, "$ExePath/afik1.cf")) {
      #print STDERR "config file $ExePath/afik1.cf not found\n";
      return;
    } else {
      #print STDERR "config file $ExePath/afik1.cf found\n";
    }
    $conf = join("", <INI_FILE>);
    close(INI_FILE);

    while ( $conf =~ /^[ ]*([a-z0-9][^ =]+?)[ ]*=[ ]*(.*?)[ ]*$/ismg ) {
        my($var, $val) = ($1, $2);
        $main::INI_ARG{$var} = $val;
        $main::INI_ARG{$var} =~ s/(?<!\\)(\$([a-z0-9_]+))/(exists($main::INI_ARG{$2}) ? $main::INI_ARG{$2} : (exists($ENV{$2}) ? $ENV{$2} : $&))/ige;
        eval ("\$main::$var=\$main::INI_ARG{$var};\n") if (!($main::INI_ARG{$var} =~ /\$+/));
    }
}


sub ReadConfig()
{
    my $pwd;
    chomp($pwd = `pwd`);
    my $prog;
    chomp($prog = $0);

    while (-l $prog) {
       if ( $prog =~ /^[^\/]/ && $prog =~ /^(.+)\/[^\/]+$/) {
         $pwd .= "/" . $1 ; #if ($prog != /^\.\//);
       } else {
         $pwd = $prog;
         $pwd =~ s/\/[^\/]*$//;
       }
      $prog = readlink($prog);
      $prog = $pwd . "/" . $prog if ($prog =~ /^[^\/]/);
    }

    #print STDERR "$prog=$pwd\n";

    $prog =~ s/^\.\///;
    $prog =~ s/[^\/]*$//;
    $prog =~ s/\/$//;
    $prog = $pwd . "/" . $prog if ($prog ne "" && $prog =~ /^[^\/]/);
    $prog = "." if $prog eq "";

    ReadConfigFile($prog);
    return 1;
}


BEGIN {
    ReadConfig();
}


1;
