package ftp_util;

require 5.000;
require Exporter;
@ISA = qw/Exporter/;
@EXPORT = qw/GetFS GetRootFS Send2Client ReadClientText PLog
             SaveAsLink TransferConnection
             CloseTransferConnection RefreshLink/;

use IO::Socket;

use strict 'vars';
use db_pg;
use ftp_class_root;
use ftp_class_fs;
use tools;
use File::Copy "cp";

sub GetRootFS()
{
    my($rez);
    if ($session::Priv >= 0) {
        #ftp_util::PLog(">>>>>>>>>>>>>>GetRootFS 0");
        $rez = ftp_class_root->new();
    } else {
        #ftp_util::PLog(">>>>>>>>>>>>>>GetRootFS 1");
        $rez = ftp_class_fftp_users->new(undef, "");
    }
    #ftp_util::PLog($rez);

    if (!defined($rez)) {
        ftp_util::PLog("Error !!! $session::Priv");
    }
    return $rez;
}

sub GetFS($;$)
{
    my ($PATH, $creat) = @_;
    if ( !defined($creat) ) {
        $creat = 0;
    }

    if ($PATH eq "") {
        $util::Mes = "Path not set";
        return ();
    }

    $PATH =~ s'\/[\/]+'/'g;
    $PATH =~ s/(^[ ]+)|([ ]+$)//g;

    if ($PATH =~ /^(\/|\\)/) {
        $PATH = $';
    } else {
        if (defined($session::WDNode) && $session::WDNode->path() ne "") {
        $PATH = $session::WDNode->path() . "/" . $PATH;
        }
    }

    $PATH =~ s/(\/|\\)$//;
    $util::Mes = "";


    my(@arr, $NewNode);
    @arr = split(/\/|\\/, $PATH);
    $NewNode = GetRootFS();

    for (my($i) = 0; $i <= $#arr; $i++) {
        my($dir) = $arr[$i];

        if ($dir eq ".") {
            next;
        }

        if ($dir ne "..") {
            my($TmpNode) = $NewNode->getSubEntry($dir);

            if (!defined($TmpNode)) {
                if ( !$creat ) {
                    $util::Mes = "Not found '"  . (($NewNode->path() ne "") ? ("/" . $NewNode->path()) : "") . "/${dir}'";
                    return undef;
                }

                if ( $NewNode->isReadonly() ) {
                    $util::Mes = "Impossible create '$dir' in folder '/" . $NewNode->path() . "'";
                    return undef;
                }

                $NewNode->insertSubEntry($dir);
                $TmpNode = $NewNode->getSubEntry($dir);
            }

            if ((!$TmpNode->isFolder()) && ($i < $#arr)) {
                $util::Mes = "'/" . $TmpNode->path() . "' is not directory";
                return undef;
            }

            $NewNode = $TmpNode;

            next;
        }

        if (!defined($NewNode->parent())) {
            $util::Mes = "'/ " . $NewNode->path() . "' is upper directory";
            return undef;
        }

        $NewNode = $NewNode->parent();

    }

    return $NewNode;
}

sub Send2Client($$)
{
    my($num, $mes) = @_;
    if ( $num != 0 ) {
      $mes = "$num $mes";
    }
    PLog("<--$mes");
    print $session::CommandStream "$mes\r\n";
}


sub ReadClientText()
{
    PLog("ftp : wait for command from client");
    my(@ReadyArray) = $session::SelectCommandStream->can_read(60 * 30);

    if ($#ReadyArray == -1) {
        PLog("ftp : Taim out for client command.");
        return undef;
    }

    my($a);
    $a = $session::CommandStream->getline();
    if (defined($a)) {
        $a = StripNL($a);
        PLog("--->$a");
    }

    return $a;
}


sub PLog($)
{
    local *LOG;
    my($mes) = @_;
    my($out) = GetCurrDate() . "> ";

    $out .= "[" . $$ . "] ";

    if (defined($session::User)) {
        $out .= "<" . $session::User . "> ";
    } else {
        $out .= "<none> ";
    }

    $out .= $mes;

    print "$out\n";

    if (!open(LOG, ">>$main::FTP_LogFileName")) {
        #print STDERR "error open log $!";
        return;
    }
    print LOG "$out\n";
    close(LOG)
}


sub SaveAsLink($$)
{
    my ($TmpFileName, $md5) = @_;

    PLog("ftp : SaveAsLink TmpFileName '$TmpFileName'");
    if (!defined($md5)) {
        $md5 = md5sum($TmpFileName);
        PLog("ftp : SaveAsLink calc md5 " . $md5);
    } else {
        PLog("ftp : SaveAsLink geted md5 " . $md5);
    }
    my ($size) = -s $TmpFileName;
    my ($ContentType) = "application/octet-stream";
    my ($sysnumfile);

    my($r_file) = DBExec("select sysnum from file where fsize = ${size} and ftype = '${ContentType}' and fcrc = '${md5}' order by sysnum");
    PLog("ftp : SaveAsLink : link search sql        :" . "select sysnum from file where fsize = ${size} and ftype = '${ContentType}' and fcrc = '${md5}' order by sysnum");
    PLog("ftp : SaveAsLink : links answer row count :" . $r_file->NumRows());

    if ($r_file->NumRows() > 0) {
        $sysnumfile = $r_file->Value("sysnum");

        PLog("ftp : SaveAsLink : old link foind : $sysnumfile");
    } else {
        $r_file = DBExec("select NextVal('file_seq') as maxsysnum");
        $sysnumfile = $r_file->Value("maxsysnum");
        PLog("ftp : SaveAsLink : $sysnumfile File number selected");

        my($r_storage) = DBExec("SELECT sysnum from storages order by ( size - used ) desc LIMIT 1");
        my($StorageNumber) = $r_storage->Value("sysnum");
        PLog("ftp : SaveAsLink : $StorageNumber storage number selected");

        $r_file = DBExec("insert into file (sysnum, fsize, ftype, fcrc, nlink, lastmodify, numstorage) values ('${sysnumfile}', '${size}', '${ContentType}', '${md5}', 0, 'now', $StorageNumber)");
        if ($r_file->isError()) {
            return -1;
        }

        if( !link ("$TmpFileName", "$main::PROGRAM_FILES/storage${StorageNumber}/$sysnumfile") ) {
            if( !cp ("$TmpFileName", "$main::PROGRAM_FILES/storage${StorageNumber}/$sysnumfile") ) {
                DBExec("delete from file where sysnum = ${sysnumfile}");
                return -1;
            }
        }

        my(@stat_arr) = stat($main::PROGRAM_FILES);
        chown($stat_arr[4], $stat_arr[5], "$main::PROGRAM_FILES/$sysnumfile");

        PLog("ftp : SaveAsLink : new link created : $sysnumfile");
    }

    return $sysnumfile;
}


sub RefreshLink($$)
{
    my ($SysNumFile, $md5) = @_;
    if (!defined($SysNumFile)) {
        return undef;
    }

    my($r_file) = DBExec("select * from file where sysnum = " . $SysNumFile);

    my ($StoragePath) = "${main::PROGRAM_FILES}/storage" . $r_file->Value("numstorage");
    my ($LocalPath) = "${StoragePath}/${SysNumFile}";
    ftp_util::PLog("ftp : RefreshLink : StoragePath : $StoragePath, LocalPath : $LocalPath");

    my($size) = -s $LocalPath;
    ftp_util::PLog("ftp : RefreshLink : size :" . $size);

    if (!defined($md5)) {
        $md5 = md5sum($LocalPath);
    }
    ftp_util::PLog("ftp : RefreshLink : MD5 :" . $md5);

    my($ctype) = $r_file->Value("ftype");
    if ($ctype eq "") {
        $ctype = "application/octet-stream";
    }
    ftp_util::PLog("ftp : RefreshLink : ctype :" . $ctype);

    my($SearchLinkSQL) = "select * from file where fcrc = '$md5' and ftype = '$ctype' and fsize = '$size' and sysnum <> '$SysNumFile'";
    $r_file            = DBExec($SearchLinkSQL);
    ftp_util::PLog("ftp : RefreshLink : searching links sql    :" . $SearchLinkSQL);
    ftp_util::PLog("ftp : RefreshLink : searching links answer row count :" . $r_file->NumRows());

    my($NewSysNumFile);
    if ($r_file->NumRows() == 0) {
        $NewSysNumFile = $SysNumFile;
        DBExec("LOCK TABLE file IN ACCESS EXCLUSIVE MODE");
        DBExec("update file set fcrc = '$md5', fsize = '$size', ftype ='$ctype', lastmodify = 'now' where file.sysnum = $SysNumFile");
        ftp_util::PLog("ftp : RefreshLink : updated link '$SysNumFile' set fcrc = '$md5', fsize = '$size', lastmodify = 'now'");
    } else {
        $NewSysNumFile = $r_file->Value("sysnum");
        ftp_util::PLog("ftp : RefreshLink : seted FSNodes from link '$SysNumFile' to '$NewSysNumFile'");
        DBExec("update fs set sysnumfile = $NewSysNumFile where sysnumfile = '$SysNumFile';");

        ftp_util::PLog("ftp : RefreshLink : Unallocate File Number $SysNumFile");
        DBExec("DELETE FROM file WHERE sysnum = '$SysNumFile';");
        unlink($LocalPath);

        $StoragePath = "${main::PROGRAM_FILES}/storage" . $r_file->Value("numstorage");
        $LocalPath   = "${StoragePath}/$NewSysNumFile";
    }

    ftp_util::PLog($StoragePath);

    my(@stat_arr) = stat($StoragePath);
    chown($stat_arr[4], $stat_arr[5], $LocalPath);

    ftp_util::PLog("ftp : RefreshLink : chahge owner $stat_arr[4], $stat_arr[5] to $LocalPath");

    return $NewSysNumFile;
}


sub TransferConnection()
{
    if ($session::TransferMode eq "undef") {
        PLog("ftp : ftp_util : TransferConnection : Exit - TransferMode is undef");
        return undef;
    }

    if (defined($session::TransferStream)) {
        PLog("ftp : ftp_util : TransferConnection : Old TransferStream is opened");
        if ($session::CloseTransferStream) {
            PLog("ftp : ftp_util : TransferConnection : Close Old TransferStream");
            undef $session::TransferStream;
        } else {
            PLog("ftp : ftp_util : TransferConnection : Exit - continue with Old TransferStream");
            return 1;
        }
    }

    PLog("ftp : ftp_util : TransferConnection : TransferMode is " . $session::TransferMode);

    if ($session::TransferMode eq "passv") {
        PLog("ftp : ftp_util : TransferConnection : Passiv mode wait to new connection");
        my($SelSocket) = new IO::Select( $session::TransferSock );
        my(@ReadySocket) = $SelSocket->can_read(15);
        if ($#ReadySocket == -1) {
            PLog("ftp : ftp_util : TransferConnection : Passiv mode wait to new connection - time out");
            close($main::TransferSock);
            $session::TransferMode = "undef";
            return undef();
        }

        PLog("ftp : ftp_util : TransferConnection : Passiv mode wait accept connection");
        if (!($session::TransferStream = $session::TransferSock->accept())) {
            PLog("ftp : ftp_util : TransferConnection : Passiv mode wait accept connection - failed : $!");
            $session::TransferSock->close();
            $session::TransferMode = "undef";
            return undef();
        }
    } else {
        PLog("ftp : ftp_util : TransferConnection : Active mode create new connection");
        $session::TransferStream = IO::Socket::INET->new(PeerAddr => $session::TransferMode, LocalPort => $main::SelfTransferPort, ReuseAddr => 1, Timeout => 15);
        if (!defined($session::TransferStream) || !$session::TransferStream->opened()) {
            PLog("ftp : ftp_util : TransferConnection : Active mode create new connection - failed : $!");
            $session::TransferMode = "undef";
            return undef();
        }
    }

    PLog("ftp : ftp_util : TransferConnection : set autoflush");
    $session::TransferStream->autoflush();

    PLog("ftp : ftp_util : TransferConnection : set binmode");
    binmode($session::TransferStream);

    PLog("ftp : ftp_util : TransferConnection : session::TransferStream->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1)");
    if (!($session::TransferStream->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1))) {
        PLog("ftp : ftp_util : TransferConnection :  session::TransferStream->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1) - failed: $!");
        $session::TransferSock->close();
        $session::TransferMode = "undef";
        return undef();
    }

    return 1;
}

sub CloseTransferConnection()
{
    if ($session::TransferMode eq "undef") {
        return;
    }

    if (defined($session::TransferStream)) {
        $session::TransferStream->close();
        undef $session::TransferStream;
        PLog("ftp : FTP_UTIL : CloseTransferConnection : close session::TransferStream");
    }

    if ($session::TransferMode eq "passv") {
        $session::TransferSock->close();
        undef $session::TransferSock;
        PLog("ftp : FTP_UTIL : CloseTransferConnection : close session::TransferSock");
    }

    $session::TransferMode = "undef";
}


1;
