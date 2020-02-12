use strict 'vars';

sub cm_retr($)
{
    my ($path) = @_;
    my ($fs, $name);

    PLog("ftp : Command RETR");

    if ($path eq "") {
        Send2Client(500, "Missing File Name");
        return undef();
    }

    if ($session::TransferMode eq "undef") {
        Send2Client(500, "Don't previous command PASV or PORT");
        return undef();
    }

    if (!TransferConnection()) {
        Send2Client(500, "Inposible Connect");
        return undef();
    }

    my($FsNode) = GetFS($path);
    if (!defined($FsNode)) {
        Send2Client(550, "RETR command has error : " . $util::Mes . ".");
        #CloseTransferConnection();
        return undef;
    }

    if ($FsNode->path() eq "") {
        Send2Client(550, "Impossible get Root Directory");
        #CloseTransferConnection();
        return undef;
    }

    if ($FsNode->isFolder()) {
        Send2Client(550, "Impossible get Directory");
        #CloseTransferConnection();
        return undef;
    }

    my($In) = $FsNode->openEntryFile();
    if ( !defined($In) ) {
        Send2Client(550, "Impossible Open File");
        #CloseTransferConnection();
        return undef;
    }

    $In->seek($session::RestartPoint, 0);
    PLog("ftp : RETR : file size: '" . $FsNode->size() . "' RestartPoint : '" . $session::RestartPoint. "'");
    Send2Client(150, "Opening BINARY mode data connection for '/" . $FsNode->path() . "' (" . $FsNode->size() . " bytes)");

    PLog("ftp : RETR : Hendler2HendlerCopy started.");
    my($TransferResult) = Hendler2HendlerCopy($In, $session::TransferStream, $FsNode->size() - $session::RestartPoint);

    PLog("ftp : RETR : Hendler2HendlerCopy finished. result " . print_results($TransferResult));

    if ($session::CloseTransferStream) {
        PLog("ftp : RETR : Close Transfer Connection");
        CloseTransferConnection();
    }

    if ( defined($TransferResult) ) {
        Send2Client(226, "Transfer complete. ($TransferResult)");
        if ($FsNode->fs() != 0) {
            DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('".$FsNode->owner()."', getdomain('".$FsNode->owner()."'), 'ftpdownload', datetime('now'::abstime), '".$FsNode->size()."', '".$FsNode->fs()."', '" . substr($session::User, 0, 20) . "', -1, '${session::ClientAddress[0]}')");

            if ($FsNode->owner() != $session::UID) {
                my($r_fs) = DBExec("SELECT gettree(" . $FsNode->fs() . ") AS path");
                my($path) = $r_fs->Value("path");

                $session::Notification{$FsNode->owner()} .= "download " . $path . "\n";
            }
        }
    } else {
        Send2Client(500, "Transfer incomplete.");
    }

    $session::RestartPoint = 0;
    return 1;
}

1;

