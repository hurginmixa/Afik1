#!/usr/bin/perl -w

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

use readconf;
use db_pg;
use tools;
use Fcntl ':flock';

select(STDERR); $| = 1;     # make unbuffered
select(STDOUT); $| = 1;     # make unbuffered

if ($#ARGV == -1) {
    print STDERR "Invalid args number\n";
    exit 1;
}

if ($ARGV[0] =~ /^\>([^\<]+)\<\@(.*?)\.?$/) {
    $user = $1;
    $domain = $2;
} else {
    print STDERR "Invalid firs arg\n";
    exit 1;
}

chomp($pwd = `pwd`);
#print "$user $domain\n";

{
    my ($login,$pass,$uid,$gid) = getpwnam($HTTP_USER);

    $) = $( = $gid;
    $> = $< = $uid;
}

if(!open(LOCK, ">>$PROGRAM_LOG/${user}\@${domain}.loc")) {
    print STDERR "failed open file $PROGRAM_LOG/${user}.loc : $!; mailer user : $<\n";
    exit 1;
}
select(LOCK);   $| = 1;     # make unbuffered

flock (LOCK, LOCK_EX);

seek (LOCK, 0, 0); truncate (LOCK, 0);

print LOCK "$$\n";

if(!open(OUT, ">>$PROGRAM_LOG/${user}\@${domain}.dmp")) {
    print STDERR "failed open file $PROGRAM_LOG/$user : $!; mailer user : $<\n";
    exit 1;
}
select(OUT);    $| = 1;     # make unbuffered

print OUT "\n\n\n\n===========\n";
for ($i = 0; $i <= $#ARGV; $i++) {
        print OUT "$i=$ARGV[$i]\n";
}
print OUT $>, "=", $), "\n";
print OUT $<, "=", $(, "\n";

print OUT "===========\n";
print OUT "parametrs: user : '$user' domain : '$domain' pwd : '$pwd' mailers UID : '$<' mailers GID : '$('\n";

if(!ConnectDB("dbname=${main::DBASE} user=${main::POSTGRES_USER}")) {
    print STDERR "Error connect to Afik1's data base ", DBLastError(), "\n";
    exit 1;
}

$r = DBExec("SELECT usr_ua.value from usr, usr_ua, domain where domain.sysnum = usr.sysnumdomain and usr.sysnum = usr_ua.sysnumusr and usr.name = '$user' and domain.name = '$domain' and usr_ua.name = 'edenaid'");
if ($r->NumRows() == 1 && $r->Value('value') ne "") {
    print STDERR "access mailbox user denied\n";
    exit 1;
}

#$parse_comstr = "| ${main::PROGRAM_C}/parsemail --stdin --domain=$domain --dbase=$DBASE --filedir=$PROGRAM_FILES --debug --sqllog=$SQL_LogFileName $user 1>>$PROGRAM_LOG/${user}\@${domain}.debug";
$parse_comstr = "| ${main::PROGRAM_C}/parsemail --stdin --domain=$domain --dbase=${main::DBASE} --dbuser=${main::POSTGRES_USER} --filedir=$PROGRAM_FILES --tmpdir=$PROGRAM_TMP --debug $user 1>>$PROGRAM_LOG/${user}\@${domain}.debug";

$r = DBExec("SELECT usr_ua.value from usr, usr_ua, domain where domain.sysnum = usr.sysnumdomain and usr.sysnum = usr_ua.sysnumusr and usr.name = '$user' and domain.name = '$domain' and usr_ua.name = 'frwmail'");
print OUT "frwmail :", $r->Value('value'), "\n";
if ($r->NumRows() == 1 && $r->Value('value') ne "") {
    if ($r->Value('value') eq "1") {
        $MailAddressFieldName = 'email';
    } else {
        $MailAddressFieldName = 'frwaddres';
    }

    $r = DBExec("SELECT usr_ua.value from usr, usr_ua, domain where domain.sysnum = usr.sysnumdomain and usr.sysnum = usr_ua.sysnumusr and usr.name = '$user' and domain.name = '$domain' and usr_ua.name = '$MailAddressFieldName'");

    print OUT "email :", $r->Value('value'), "\n";

    if ($r->NumRows() == 1 && $r->Value('value') ne "") {
        my $frwaddr = tools::urldecode($r->Value('value'));
        $parse_comstr = "|/usr/sbin/sendmail $frwaddr";
    }
}

print OUT "command line: $parse_comstr\n";


if(!open (PARS, $parse_comstr)) {
    print STDERR "failed open file PARS : $!; mailer user : $<\n";
    close (OUT);
    exit 1;
}

while (<STDIN>) {
    print OUT;
    print PARS;
}

close (PARS);

$exit = $?;


print OUT "-end-parser-with errorcode '" . $exit/256 . "'\n";
close(OUT);

flock(LOCK, LOCK_UN);
close(LOCK);

#print $exit, "\n";

exit $exit/256;
