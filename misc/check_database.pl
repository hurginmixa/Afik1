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
}

use tools;
use db_pg;
use db_pg_res;
use Getopt::Long;

use readconf; # read configurations file and fille fiels

local $main::DiagnosticLevel;
local $main::Silently;

sub PErr($)
{
    local *MAIL;
    my($mes) = @_;
    my($out);

    PLog($mes);

    if ($main::DiagnosticLevel < 1) {
        PLog("Sending Mail prohibbited");
        return;
    }

    $out = "";

    $out .=  "Content-type: text/plain\r\n";
    $out .=  "Subject: Afik1's check error from ${main::LOCAL_SERVER}\r\n";
    $out .=  "\r\n";

    $out .=  GetCurrDate() . "\r\n";
    $out .= "afik1's check from ${main::LOCAL_SERVER}\r\n";
    $out .=  $mes . "\r\n";

    if (!open(MAIL, "|/usr/sbin/sendmail ${main::CheckAdmin}")) {
        PLog("Sending Mail failed");
        return;
    }
    print MAIL $out;
    close MAIL;

    PLog("Diagnostic mail Sended");
}

sub PLog($)
{
    local *LOG;
    my($mes) = @_;
    my($out) = GetCurrDate() . "> ";

    $out .= "[" . $$ . "] ";

    if ($mes eq "") {
        $out = "";
    }

    $out .= $mes;

    if ( !$main::Silently ) {
        print "$out\n";
    }

    if (!open(LOG, ">>$main::Check_DataBase_LogFileName")) {
        if ( !$main::Silently ) {
            print STDERR "error open log $!";
        }
        return;
    }
    print LOG "$out\n";
    close(LOG)
}


sub Job()
{
    PLog("open data base : dbname=${main::DBASE} user=${main::POSTGRES_USER}");

    if(!ConnectDB("dbname=${main::DBASE} user=${main::POSTGRES_USER}")) {
        PErr("Error : Not Connect to DBase");
        return 0;
    }

    my ($res, $res1, $res2);

    #-------------------------------------------------------------------------------
    # 1 Check
    $res1 = DBExec("SELECT sum(nlink) as num from file;");
    if ($res1->isError()) {
        PErr("Error : Check 1 step 1 SQL failed");
        return 0;
    }
    PLog("Check 1 step 1 result : " . $res1->Value("num"));

    $res2 = DBExec("SELECT count(*) as num from fs where sysnumfile <> 0;");
    if ($res2->isError()) {
        PErr("Error : Check 1 step 2 SQL failed");
        return 0;
    }
    PLog("Check 1 step 2 result : " . $res2->Value("num"));

    if ($res1->Value("num") ne $res2->Value("num")) {
        PErr("Error : Check 1 failed");
        return 0;
    }

    PLog("Check 1 passed");

    #-------------------------------------------------------------------------------
    # 2 Check
    $res = DBExec("SELECT count(*) as num from fs left join file on fs.sysnumfile = file.sysnum where fs.sysnumfile <> 0 and file.sysnum is null;");
    if ($res->isError()) {
        PErr("Error : Check 2 SQL failed");
        return 0;
    }
    PLog("Check 2 result : " . $res->Value("num"));

    if ($res->Value("num") ne 0) {
        PErr("Error : Check 2 failed");
        return 0;
    }

    PLog("Check 2 passed");

    #-------------------------------------------------------------------------------
    # 3 Check
    $res = DBExec("SELECT count(*) as num from file left join fs on fs.sysnumfile = file.sysnum where file.nlink <> 0 and fs.sysnum is NULL;");
    if ($res->isError()) {
        PErr("Error : Check 3 SQL failed");
        return 0;
    }
    PLog("Check 3 result : " . $res->Value("num"));

    if ($res->Value("num") ne 0) {
        PErr("Error : Check 3 failed");
        return 0;
    }

    PLog("Check 3 passed");


    #-------------------------------------------------------------------------------
    # 4 Check
    my(%FileList, %LinkList);

    PLog("Check 4 started");

    PLog("Check 4 Get File's list");
    opendir(F, $main::PROGRAM_FILES);

    map {

            opendir(FINS, $main::PROGRAM_FILES . "/" . $_);
            my($NamStorage) = $_;
            $NamStorage =~ /[0-9]+$/; my($NumStorage) = $&;

            map {
                ${$FileList{$_}}[0] = -s $main::PROGRAM_FILES . "/$NamStorage/" . $_;
                ${$FileList{$_}}[1] = $NumStorage;
            } grep {/^[0-9]+$/} readdir(FINS);

            closedir(FINS);

    } grep {/^storage[0-9]+$/} readdir(F);

    closedir(F);
    PLog(keys(%FileList) . " files loaded");

    PLog("Check 4 Geting Link's list");
    $res = DBExec("select * from file;");
    while(!$res->Eof()) {
        ${$LinkList{$res->Value("sysnum")}}[0] = $res->Value("fsize");
        ${$LinkList{$res->Value("sysnum")}}[1] = $res->Value("numstorage");
        $res->Next();
    }
    PLog(keys(%LinkList) . " links loaded");

    #if (keys(%FileList) ne keys(%LinkList)) {
    #    PErr("Error : Check 4 failed");
    #    return 0;
    #}

    my($Amount) = "";
    my($r);
    foreach $r (keys(%LinkList)) {
        if (!exists($FileList{$r})) {
            $Amount .= "File $r missing\r\n";
        } else {
            if (${$FileList{$r}}[0] ne ${$LinkList{$r}}[0]) {
                $Amount .= "File $r not mathing size. Size of file: '" . ${$FileList{$r}}[0] . "'. Size of link: '" . ${$LinkList{$r}}[0] . "'\r\n";
            }
            if (${$FileList{$r}}[1] ne ${$LinkList{$r}}[1]) {
                $Amount .= "File $r not mathing Num of storage. File storage: '" . ${$FileList{$r}}[1] . "'. Link storage: '" . ${$LinkList{$r}}[1] . "'\r\n";
            }
        }
    }

    foreach $r (keys(%FileList)) {
        if (!exists($LinkList{$r})) {
            $Amount .= "Link $r missing on storage " . ${$FileList{$r}}[1] . "\r\n";
        }
    }

    if ($Amount ne "") {
        PErr("Error : Check 4 failed\r\n" . $Amount);
        return 0;
    }

    PLog("Check 4 passed");

    #-------------------------------------------------------------------------------
    # Amount
    PLog("All checks passeds");

    return 1;
}

sub main()
{
    GetOptions("diagnostic-level=i" => \$main::DiagnosticLevel,
                "silently" => \$main::Silently);

    if (!defined($main::DiagnosticLevel)) {
        $main::DiagnosticLevel = 1;
    }

    if ($main::DiagnosticLevel < 0 && $main::DiagnosticLevel > 2) {
        print STDERR "Invalid diagnostic level. Must be 0 thru 2\n";
        $main::DiagnosticLevel = 1;
    }

    PLog("");
    PLog("Start Session");
    my $result = Job();
    PLog("End Session");

    exit(!$result);
}

main();
