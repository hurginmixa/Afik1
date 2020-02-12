use strict 'vars';

sub cm_mkd($)
{
    my($path) = @_;
    my($FsNode);

    if ($path eq "") {
        Send2Client(501, "Folder name not set");
        return 0;
    }

    $FsNode = GetFS($path);
    if (defined($FsNode)) {
     Send2Client(521, "\"/" . $FsNode->path() . "\" directory exists");
     return 0;
    }

    $FsNode = GetFS($path, 1);
    if (!defined($FsNode)) {
     Send2Client(550, "MKD command has error : $util::Mes");
     return 0;
    }

    Send2Client(257, "\"/" . $FsNode->path() . "\" new directory created.");
    return 1;
}

1;
