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

use db_pg;
use db_pg_res;
use readconf; # read configurations file and fille fiels
use IO::File;

local %main::List;

sub ReadDbDump($$)
{
    my($f, $side) = @_;
    my($buf);
    my($line, $DataFlag);


    print "$f\n";

    open(INFILE, $f);

    $buf = "";
    $DataFlag = 0;
    while(<INFILE>) {
        $line = $_;
        if ($DataFlag) {
            if ($line =~ /^\\\./) {
                $DataFlag = 0;
            }
            next;
        }
        if ($line =~ /^copy/i) {
            $DataFlag = 1;
            next;
        }
        $buf .= $line;
    }

    close(INFILE);

    $buf =~ s/^ *--.*?\n//smg;
    $buf =~ s/\n+/\n/smg;

    #print "=" . $buf;

    my(@res);
    my($TMP) = $buf;
    while ( $TMP =~ /^ *(CREATE|ALTER)([^']*?)(\'[^']*?\'[^']*?)*?;/ism ) {
        $TMP = $';
        my($item) = $&;
        $item =~ /^[^\n]+/sm;
        my($title) = $&;

        if ($title =~ /CREATE +TABLE/) {
            my($TMP) = $item;
            $TMP =~ /^[^\n]+\n/s;
            my($firstline) = $&;
            $TMP = $';
            $TMP =~ /\n[^\n]+$/s;
            my($lastline) = $&;
            $TMP = $`;
            $TMP = join("\n", (map {s/,$//; $_} sort(split("\n", $TMP))));
            $item = $firstline . $TMP . $lastline;
        }


        ${$main::List{$title}}[$side] .= $item;
    }

    return @res;
}

sub main($)
{
    my($NewDumpName) = @_;
    my($CurrDumpName) = "pg_dump -s -U $main::POSTGRES_USER $main::DBASE |";

    ReadDbDump($CurrDumpName, 0);
    ReadDbDump($NewDumpName, 1);

    map {
        my($item1) = ${$main::List{$_}}[0];
        if (!defined($item1)) {
            $item1 = "not defined";
        }
        my($item2) = ${$main::List{$_}}[1];
        if (!defined($item2)) {
            $item2 = "not defined";
        }

        if ($item1 ne $item2) {
            print "---------------Current\n", $item1, "\n";
            print "---------------New\n",     $item2, "\n";
            print "===============\n\n";
        }
    } keys %main::List;
}

main($ARGV[0]);
