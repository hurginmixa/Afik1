use strict 'vars';
use Digest::MD5;
use tools;

sub cm_stor($$)
{
    my ($path, $isAppend) = @_;
    my ($sysnumfile);
    my ($TmpFileName);
    my ($TmpFileStream, $ProgressSub);
    my ($r);
    my ($CommandName) = (!$isAppend) ? "STOR" : "APPE";

    PLog("ftp : Command $CommandName :  isAppend : $isAppend");

    if ($path eq "") {
        Send2Client(500, "$CommandName Missing File Name");
        #CloseTransferConnection();
        return undef();
    }

    if ( !($path =~ /[^\\\/]+$/) ) {
        Send2Client(550, "$CommandName command has error : File name not set.");
        #CloseTransferConnection();
        return undef;
    }

    my($FName) = $&;
    $path = $`;
    $path =~ s/[\\\/]+$//;
    $path = "." if ($path eq "");

    my($FsNode) = GetFS($path, 1);
    if (!defined($FsNode)) {
        Send2Client(550, "$CommandName command has error : " . $util::Mes . ".");
        #CloseTransferConnection();
        return undef;
    }

    PLog("ftp : $CommandName :  folder name '" . $FsNode->path() . "'");

    if (!$FsNode->isFolder()) {
        Send2Client(550, "$CommandName Target path is not folder.");
        #CloseTransferConnection();
        return undef;
    }

    if ( $FsNode->isReadonly() ) {
        Send2Client(550, "$CommandName Path read only");
        #CloseTransferConnection();
        return undef;
    }

    my ($FileNode) = $FsNode->getSubEntry($FName);

    if ( defined($FileNode) && $FileNode->isFolder()) {
        Send2Client(550, "$CommandName Target path is folder.");
        #CloseTransferConnection();
        return undef;
    }

    if ($session::TransferMode eq "undef") {
        Send2Client(500, "$CommandName Don't previous command PASV or PORT");
        #CloseTransferConnection();
        return undef();
    }

    if ( !TransferConnection() ) {
        Send2Client(500, "$CommandName Inposible Connect");
        #CloseTransferConnection();
        return undef();
    }

    if ( !($FsNode->CheckQuote()) ) {
        Send2Client(500, "$CommandName Quote overflow");
        #CloseTransferConnection();
        return undef();
    }


    if (defined($FileNode) && ($session::RestartPoint || $isAppend)) {
        PLog("ftp : $CommandName :  command for restart or append");

        $TmpFileName = $FileNode->getLocalFileName();
        if ($isAppend) {
            $session::RestartPoint = -s $TmpFileName;
        }
        $TmpFileStream = new IO::File->new($TmpFileName, "r+");
        $ProgressSub = sub($) {  $FileNode->TmpStoreProcess($_[0]);  };
    } else {
        PLog("ftp : $CommandName :  command for store");

        undef $FileNode; # create new file

        $session::RestartPoint = 0;
        $TmpFileName = $FsNode->GetTMPFile();
        $TmpFileStream = new IO::File->new($TmpFileName, "w+");

        $ProgressSub = sub($) {  $FsNode->TmpStoreProcess($_[0]);  };
    }

    if ( !defined($TmpFileStream) ) {
        Send2Client(550, "$CommandName Impossible Open File : $!");
        #CloseTransferConnection();
        return undef;
    }

    $TmpFileStream->truncate($session::RestartPoint);
    $TmpFileStream->seek(0, 0);

    $Hendler2HendlerCopy::DigestMD5Object = Digest::MD5->new();
    $Hendler2HendlerCopy::DigestMD5Object->addfile($TmpFileStream);

    $Hendler2HendlerCopy::WriteProgressSub = $ProgressSub;
    # $Hendler2HendlerCopy::ReadProgressSub = sub($) {  PLog("ReadSize $_[0], BlockSize $_[1]");  };


    Send2Client(150, "Opening BINARY mode data connection for '$path'");
    PLog("ftp : $CommandName : Hendler2HendlerCopy started");
    my($StorageResult) = Hendler2HendlerCopy($session::TransferStream, $TmpFileStream);
    PLog("ftp : $CommandName : Hendler2HendlerCopy finished result " . print_results($StorageResult));

    my($md5) = $Hendler2HendlerCopy::DigestMD5Object->hexdigest;
    undef $Hendler2HendlerCopy::DigestMD5Object;
    PLog("ftp : $CommandName : Hendler2HendlerCopy md5 " . $md5);

    $TmpFileStream->close();

    if ($session::CloseTransferStream) {
        CloseTransferConnection();
    }

    PLog("ftp : $CommandName : '" . print_results($StorageResult) . "' file size '" . print_results(-s $TmpFileName) . "' error mess : '" . print_results($Hendler2HendlerCopy::Message) . "'");

    if ( !defined($StorageResult) && (-s $TmpFileName) == 0) {
        Send2Client(500, "$CommandName Transfer incomplete. $Hendler2HendlerCopy::Message");
        $FsNode->UnlinkTMPFile();
        return undef();
    }

    my($FsResult);
    if (defined($FileNode)) {
        $FsResult = $FileNode;
        $FsResult->RestoreMD5($md5);
    } else {
        $FsResult = $FsNode->insertTmpFile($FName, $md5);
        if(!defined($FsResult)) {
            Send2Client(452, "$CommandName  Impossible save link : " . $FsNode->{LASTERROR} . ".");
            $FsNode->UnlinkTMPFile();
            return undef();
        }
    }

    if ($StorageResult && $FsResult->fs() != 0) {
        DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('" . $FsResult->owner() . "', getdomain('" . $FsResult->owner() . "'), 'ftpupload', datetime('now'::abstime), '" . $StorageResult . "', '" . $FsResult->fs() . "', '" . substr($session::User, 0, 20) . "', 1, '${session::ClientAddress[0]}')");

        if ($FsResult->owner() != $session::UID) {
            my($r_fs) = DBExec("SELECT gettree(" . $FsResult->fs() . ") AS path");
            my($path) = $r_fs->Value("path");

            if ($session::RestartPoint || $isAppend) {
                $session::Notification{$FsResult->owner()} .= "resume / append file " . $path . "\n";
            } else {
                $session::Notification{$FsResult->owner()} .= "upload file " . $path . "\n";
            }
        }
    }

    Send2Client(226, "$CommandName Transfer complete. (" . print_results($StorageResult) . ")");

    $session::RestartPoint = 0;
    return 1;
}


1;

