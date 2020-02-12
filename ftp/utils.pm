package utils;

require 5.000;
require Exporter;

@ISA = qw(Exporter);
@EXPORT = qw(GetQuote CalcPasswHesh AuthorizeKey SetTempl SQLDate);

use strict 'vars';

use tools;
use db_pg;
use db_pg_res;


sub GetQuote($)
{
    my($UsrID) = @_;

    if (!($UsrID =~ /^[0-9]+$/)) {
        return undef;
    }

    my($r, $result);

    $r = DBExec("SELECT usr.quote        as usrquote," .
                       "domain.quote     as domainquote, " .
                       "usr.diskusage    as usrdiskusage, " .
                       "domain.diskusage as domaindiskusage, " .
                       "domain.userquote as defaultusrquote where usr.sysnumdomain = domain.sysnum and usr.sysnum = '$UsrID'");

    if ($r->NumRows() != 1) {
        return undef;
    }


    $result->{UsrQuote} = $r->Value("usrquote");
    if ($result->{UsrQuote} eq "0") {
        $result->{UsrQuote} = $r->Value("defaultusrquote");
    }
    $result->{DomainQuote} = $r->Value("domainquote");
    $result->{UsrDiskUsage} = $r->Value("usrdiskusage");
    $result->{DomainDiskUsage} = $r->Value("domaindiskusage");

    $result->{DomainQuoteOver} = ($result->{DomainQuote} <= $result->{DomainDiskUsage}            ? 1 : 0);
    $result->{UsrQuoteOver}    = ($result->{UsrQuote}    <= $result->{UsrDiskUsage}               ? 1 : 0);
    $result->{QuoteOver}       = ($result->{DomainQuoteOver} == 1 || $result->{UsrQuoteOver} == 1 ? 1 : 0);

    return $result;
}


sub CalcPasswHesh($)
{
    my($addr) = @_;
    my($rez) = crypt(md5($addr), 44);
    $rez =~ s/[\\\/;,]/1/g;
    return $rez;
}


sub AuthorizeKey($)
{
    my($addr) = @_;
    return CalcPasswHesh($addr);
}



sub SetTempl
{
    my($content, $FACE) = @_;

    #print "SetTempl " .  $main::PROGRAM_LANG . "/en/" . $content . ".txt";


    if ($content eq "") {
        return;
    }

    if (open(LANG, $main::PROGRAM_LANG . "/en/" . $content . ".txt")) {
        $a = join("", <LANG>);
        $a =~ s/\r//g;
        close(LANG);
        map {
            if(/^[ ]*([a-z0-9][^ =]+?)[ ]*=[ ]*?(.*?)[ ]*$/ism) {
                $main::TEMPL{$1} = $2;
            }
        } split(/\n/ms, $a);
     }

     if (!defined($FACE)) {
        $FACE = "";
     }

     if ( $FACE eq "" && exists( $ENV{HTTP_ACCEPT_LANGUAGE} ) && defined( $ENV{HTTP_ACCEPT_LANGUAGE} ) ) {
        $FACE = $ENV{HTTP_ACCEPT_LANGUAGE};
     }

     if ($FACE eq "") {
        return;
     }

     if (open(LANG, $main::PROGRAM_LANG . "/$FACE/" . $content . ".txt")) {
     	$a = join("", <LANG>);
        $a =~ s/\r//g;
        close(LANG);
        map {
            if(/^[ ]*([a-z0-9][^ =]+?)[ ]*=[ ]*?(.*?)[ ]*$/ism) {
                $main::TEMPL{$1} = $2;
            }
        } split(/\n/ms, $a);
     }
}


sub SQLDate
{
    my($Date, $fulltime) = @_;
    my($rez);
    my(@Mons) = ("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

    if (!defined($Date)) {
	return undef;
    }


    if (!defined($fulltime)) {
        $fulltime = 0;
    }

    my($year, $mon, $mday, $hour, $min, $sec, $dd);
    if ($Date =~ /([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})([\-\+]\d+)?/) {
        ($year, $mon, $mday, $hour, $min, $sec, $dd) = ($1, $2, $3, $4, $5, $6, $7);
    } else {
        ($sec, $min, $hour, $mday, $mon, $year) = localtime();
        $mon  += 1;
        $year += 1900;
    }

    if ($fulltime) {
        $dd = 0 if (!defined($dd));
        $rez = POSIX::mktime($sec, $min, $hour, $mday, $mon - 1, $year - 1900, $dd != 2);
    } else {
        my($sec_c, $min_c, $hour_c, $mday_c, $mon_c, $year_c) = localtime();
        $mon_c  += 1;
        $year_c += 1900;

        if ((($year_c * 365 + $mon_c * 30 + $mday_c) - ($year * 365 + $mon * 30 + $mday)) < 150) {
            $rez = sprintf("%s %2d %02d:%02d", $Mons[$mon - 1], $mday, $hour, $min);
        } else {
            $rez = sprintf("%s %2d  %d", $Mons[$mon - 1], $mday, $year);
        }
    }

    return $rez;
}

1
