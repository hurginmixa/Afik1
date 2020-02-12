#!/usr/bin/perl -w

use strict 'vars';

sub BEGIN
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
    $prog =~ s/^\.\///;
    $prog =~ s/[^\/]*$//;
    $prog =~ s/\/$//;
    $prog = $pwd . "/" . $prog if ($prog =~ /^[^\/]/);
    @INC = ($prog, @INC) if $prog ne "";

    #print STDERR join("\n", @INC), "\n";
}
use Getopt::Long;

use db_pg;
use db_pg_res;
use tools;
use readconf; # read configurations file and fille fiels


local $main::Delay;
local $main::NoCheckDatebase;
local $main::ShowOnly;
local $main::NoNLinkUpdate;
local $main::Silently;

sub PLog
{
    if (!$main::Silently) {
        local $, = "";
        print @_, "\n";
    }
}



sub Job()
{
    my $r;
    my @list;
    my $tmp;

    PLog("Opening of data base");
    if(!ConnectDB("dbname=${main::DBASE} user=${main::POSTGRES_USER}")) {
        PLog("Error : Not connect to data base");
        exit 1;
    }


    if ( !$main::NoNLinkUpdate ) {
        PLog("Update 'nlink' fields");
        DBExec("UPDATE file set nlink = (select count(*) from fs where sysnumfile = file.sysnum);");
    }


    PLog("Begin work");
    DBExec("Begin Work");


    PLog("Lock");
    DBExec("LOCK TABLE file IN ACCESS EXCLUSIVE MODE");


    PLog("Geting list of files");
    $r = DBExec("select * from file where nlink = 0 AND lastmodify <= 'now'::timestamp - '" . $main::Delay . " day'::interval");

    PLog("To delete '" . $r->NumRows(), "' file(s)");

    if ( !$main::ShowOnly ) {
        while(!$r->Eof()) {
            push(@list, $main::PROGRAM_FILES . "/storage" . $r->Value("numstorage") . "/" . $r->Value("sysnum"));
            $r->Next();
        }

        PLog("Deleting link from data base");
        DBExec("delete from file where nlink = 0 AND lastmodify <= 'now'::timestamp - '" . $main::Delay . " day'::interval");

        PLog("Deleting file from data base");
        PLog("num of array ", $#list + 1);
        unlink @list;
    }

    PLog("Commit");
    DBExec("Commit Work");
}


sub main()
{
    GetOptions("Delay=i" => \$main::Delay,
               "no-check" => \$main::NoCheckDatebase,
               "show-only" => \$main::ShowOnly,
               "no-nlink-update" => \$main::NoNLinkUpdate,
               "silently" => \$main::Silently);

    if (!defined($main::Delay)) {
        PLog("2 days delay assume");
        $main::Delay = 2;
    }

    if ($main::Delay < 0) {
        PLog("Error: Delay has positive values only. 2 days assume\n");
        $main::Delay = 2;
    }

    if ( !$main::NoCheckDatebase ) {
        PLog("Checking database");
        system("$main::PROGRAM_MISC/check_database.pl --silently");
        if ($? != 0) {
            PLog("Error : Check database failed");
            exit 1;
        }
    }

    my ($login,$pass,$uid,$gid) = getpwnam($main::HTTP_USER); # masquarade as apache server
    $< = $uid;
    $( = ${\split(' ', $gid)};
    if ($< != $uid) {
        PLog("Error in Set UID");
        exit 1;
    }

    Job();

    exit(0);
}

main();
