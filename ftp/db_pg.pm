package db_pg;

require 5.000;
require Exporter;

@ISA = qw(Exporter);
@EXPORT = qw(ConnectDB DBExec DBLastError);

use strict 'vars';
use Pg;
use db_pg_res;
use tools;

sub ConnectDB($)
{
    my($Param) = @_;

    $db_pg::PgConn = Pg::connectdb($Param);
    if ($db_pg::PgConn->status != PGRES_CONNECTION_OK) {
        return 0;
    }

    $main::Mes = "";
    return 1;
}


sub DBExec($)
{
    my($SQL) = @_;
    my($cursor);
    my($result);

    SQL_PLog($SQL);
    $SQL =~ s/;//g; # INPOSSIBLE ; IN SQL

    if(defined($db_pg::PgConn)) {
        $cursor = $db_pg::PgConn->exec($SQL);
    } else {
        SQL_PLog("Error: Not defined connection");
        $cursor = -1;
    }

    $result = db_pg_res->new($cursor);
    if ($result->isError()) {
        SQL_PLog("Error: " . DBLastError());
    }
    return $result;
}


sub DBLastError()
{
    my($SQL) = @_;
    my($res);

    if(defined($db_pg::PgConn)) {
        $res = $db_pg::PgConn->errorMessage();
        $res =~ s/(\r\n|\n)$//;
    } else {
        $res = "Not initialized connection";
    }

    return $res;
}


sub SQL_PLog($)
{
    local *LOG;
    my($SQL) = @_;
    my($out) = GetCurrDate() . "> '$SQL'";

   my($flagCreat) = 0;

    if (! -e $main::SQL_LogFileName) {
           $flagCreat = 1;
    }

    if (!open(LOG, ">>$main::SQL_LogFileName")) {
        return;
    }

    print LOG "$out\n";
    close(LOG);

    if ($flagCreat == 1) {
        my($login,$pass,$uid,$gid) = getpwnam($main::HTTP_USER); # masquarade as apache server
        chown $uid, $gid, $main::SQL_LogFileName;
    }
}


END ()
{
    if(defined($db_pg::PgConn)) {
        #print "close\n";
        $db_pg::PgConn->reset;
    }
}


1
