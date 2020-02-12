#!/usr/bin/perl -w


use readconf;

use db_pg;
use db_pg_res;

if (!DBConnect($main::DBASE)) {
  print STDERR "error conect to data base $main::DBASE";
  exit 1;
}

$r = DBExec("select * from fs where ftype = 'f' and name like 'Upload%'");
while(!$r->Eof()) {
    $name = $r->Value("name");
    $nname = $name;
    $nname =~ s/^Upload[ ]+of[ ]+(.*)\.([^\.]*)$/$1\[1\].$2/;
    $nname =~ s/^Upload[ ]+\((\d+)\)[ ]+of[ ]+(.*)\.([^\.]*)$/$2\[$1\].$3/;
    print "$name=$nname\n";
    DBExec("update fs set name = '$nname' where sysnum = '" . $r->Value("sysnum") . "'\n");
    $r->Next();
}


$r = DBExec("select * from fs where ftype = 'f' and name like 'Paste%'");
while(!$r->Eof()) {
    $name = $r->Value("name");
    $nname = $name;
    $nname =~ s/^Paste[ ]+of[ ]+(.*)\.([^\.]*)$/$1\[1\].$2/;
    $nname =~ s/^Paste[ ]+\((\d+)\)[ ]+of[ ]+(.*)\.([^\.]*)$/$2\[$1\].$3/;
    print "$name=$nname\n";
    DBExec("update fs set name = '$nname' where sysnum = '" . $r->Value("sysnum") . "'\n");
    $r->Next();
}


$r = DBExec("select * from fs where ftype = 'f' and name like 'Copy%'");
while(!$r->Eof()) {
    $name = $r->Value("name");
    $nname = $name;
    $nname =~ s/^Copy[ ]+of[ ]+(.*)\.([^\.]*)$/$1\[1\].$2/;
    $nname =~ s/^Copy[ ]+\((\d+)\)[ ]+of[ ]+(.*)\.([^\.]*)$/$2\[$1\].$3/;
    print "3=$name=$nname\n";
    DBExec("update fs set name = '$nname' where sysnum = '" . $r->Value("sysnum") . "'\n");
    $r->Next();
}


print "1\n";
