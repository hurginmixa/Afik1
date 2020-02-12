use strict 'vars';

sub cm_site($)
{
    my($a) = @_;

    if ($a =~ /^TOUCH[ ]*/i) {
        return cm_site_touch($');
    }

    if ($a =~ /^CHECKSUMPART[ ]*/i) {
        return cm_site_checksumpart($');
    }

    if ($a =~ /^CHECKSUM[ ]*/i) {
        return cm_site_checksum($');
    }

    if ($a =~ /^CRPERM[ ]*/i) {
        return cm_site_createpermishion($');
    }

    if ($a =~ /^TIME[ ]*/i) {
        return cm_site_time($');
    }

    if ($a =~ /^HELP[ ]*/i) {
        return cm_site_help($');
    }


    Send2Client(502, "Command not inplemented");
}

sub cm_site_touch()
{
    my($a) = @_;
    my($date, $path, $rez);
    PLog("ftp : SITE TOUCH");

    if ($a =~ /^(\d+)\s+(.+)$/) {
      ($date, $path) = ($1, $2);
    } else {
      Send2Client(550, "SITE TOUCH command has error in parameters.");
      return undef;
    }

    $path = "." if ( $path eq "" );

    PLog("ftp : SITE TOUCH - Call GetFS() path '$path'");
    my($FsNode) = GetFS($path);


    if ( !defined($FsNode) ) {
       Send2Client(550, "SITE TOUCH command has error : " . $util::Mes . ".");
       return undef;
    }

    PLog("ftp : " . ref($FsNode));

    if ( $FsNode->parent()->isReadonly() ) {
       Send2Client(550, "SITE TOUCH Path read only");
       return undef;
    }

    if(!($rez = $FsNode->touch($date))) {
       Send2Client(552, "SITE TOUCH Impossible change date : " . $FsNode->{LASTERROR} . ".");
       return undef();
    }

    Send2Client(226, "SITE TOUCH command successed");
    return 1;
}

sub cm_site_checksum($)
{
    my($path) = @_;
    my($FsNode) = GetFS($path);


    if ( !defined($FsNode) ) {
       Send2Client(550, "SITE CHECKSUM command has error : " . $util::Mes . ".");
       return undef;
    }

    PLog("ftp : " . ref($FsNode));

    if ($FsNode->isFolder()) {
       Send2Client(550, "SITE CHECKSUM command has error : Target is Folder.");
       return undef;
    }

    my($md5) = $FsNode->CalcMd5();
    if (!defined($md5) || $md5 eq "0") {
       Send2Client(550, "SITE CHECKSUM command has error : " . $FsNode->{LASTERROR} . ".");
       return undef;
    }

    Send2Client(226, $md5);
    return 1;
}

sub cm_site_checksumpart($)
{
    my($cmd) = @_;
    if ( !($cmd =~ /^[ ]*([1-9][0-9]*)[ ]+([^ ]+.*?)[ ]*$/i) ) {
       Send2Client(550, "SITE CHECKSUMPART command has syntax error.");
       return undef;
    }

    my ($Length, $path) = ($1, $2);

    PLog("ftp : site checksumpart  : length $Length file $path");

    my($FsNode) = GetFS($path);
    if ( !defined($FsNode) ) {
       Send2Client(550, "SITE CHECKSUMPART command has error : " . $util::Mes . ".");
       return undef;
    }

    PLog("ftp :  site checksumpart  : " . ref($FsNode));

    if ($FsNode->isFolder()) {
       Send2Client(550, "SITE CHECKSUMPART command has error : Target is Folder.");
       return undef;
    }

    my($md5) = $FsNode->CalcMd5Part($Length);
    if (!defined($md5) || $md5 eq "0") {
       Send2Client(550, "SITE CHECKSUMPART command has error : " . $FsNode->{LASTERROR} . ".");
       return undef;
    }

    Send2Client(226, $md5);
    return 1;
}

sub cm_site_createpermishion($)
{
    my($param) = @_;
    PLog("ftp : SITE CRPERM");


    if (!($param =~ /^\s*(\S+)\s+([rwneRWNE])\s+(\d{4}-\d{2}-\d{2}|-)\s+(\S+.*)$/)) {
       Send2Client(552, "SITE CRPERM invalid parameters");
       return undef();
    }

    my($addr, $acc, $date, $path) = ($1, $2, $3, $4);
    PLog("ftp : ($addr, $acc, $date, $path)");

    if (!($addr =~ /^[\.\-\_\@a-z0-9]+$/i)) {
       Send2Client(552, "SITE CRPERM invalid 1 parameter");
       return undef();
    }

    PLog("ftp : SITE CRPERM - Call GetFS() path '$path'");
    my($FsNode) = GetFS($path);

    if ( !defined($FsNode) ) {
       Send2Client(550, "SITE CRPERM command has error : " . $util::Mes . ".");
       return undef;
    }

    PLog("ftp : FSNode->" . ref($FsNode));

    if (ref($FsNode) ne "ftp_class_fs") {
       Send2Client(550, "SITE CRPERM inpossible set permision to this element.");
       return undef;
    }

    my($p) = $FsNode;
    while ( defined($p->parent()) && ref($FsNode) eq "ftp_class_fs") {
        $p = $p->parent();
        PLog("ftp : in loop " . ref($p));
    }

    if (ref($p) ne "ftp_class_root") {
       Send2Client(550, "SITE CRPERM inpossible set permision to this element.");
       return undef;
    }

    DBExec("delete from acc where sysnumfs = '" . $FsNode->fs() . "' and username = '" . $addr . "'");
    if (uc($acc) ne "E") {
      if ($date eq "-") {
        $date = "NULL";
      } else {
        $date = "'$date 23:59:59'";
      }
      DBExec("insert into acc (sysnum, sysnumfs, username, access ,expdate) values (nextval('acc_seq'), '" . $FsNode->fs() . "', '$addr', '$acc', $date)");
    }

    Send2Client(226, "SITE CRPERM command successed");
    return 1;
}

sub cm_site_time($)
{
    Send2Client(226, time());
    return 1;
}

sub cm_site_help()
{
    Send2Client(226, "HELP command successed");
    return 1;
}

1;
