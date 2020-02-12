

package ftp_class_fs;

#
# ftp_class_fs.pm
# |\
# | |- ftp_class_fftp_fs.pm
# | |- ftp_class_spfolder.pm
# |    \
# |     |- ftp_class_fftp_resour.pm
# |     |- ftp_class_fftp_users.pm
# |     |- ftp_class_root.pm
#  \
#   |- ftp_class_lfs.pm
#

# ------------------------------------------------------------------------
#  new($parent, $fs, $path, $sysnumfile, $readonly, $size,  $owner)
#  isFolder()
#  isReadonly()
#  parent()
#  path()
#  fileName()
#  fs()
#  size()
#  touch()
#  owner()
#  getLocalFileName()
#  CalcMd5Part()
#  CalcMd5()
#  NumStorage()

#  listSubEntry()
#  insertSubEntry($Name, $TmpFileName, $md5)
#  deleteSubEntry($Name)
#  renameSubEntry($SName, $DName)
#  getSubEntry($FName)
#  openEntryFile()
#  RestoreMD5($md5)

use db_pg;
use ftp_util;
use utils;
use tools;
use IO::File;
use Digest::MD5;

@ISA = qw(Exporter);

use strict 'vars';

sub new
{
    my $class      = shift;
    my $parent     = shift;
    my $fs         = shift;
    my $path       = shift;
    my $sysnumfile = shift;
    my $readonly   = shift;
    my $size       = shift;
    my $owner      = shift;
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter class"),      return undef) if (!defined($class));
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter fs"),         return undef) if (!defined($fs));
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter parent"),     return undef) if (!defined($parent) && ($fs != -1 && $fs != -2));
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter path"),       return undef) if (!defined($path));
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter sysnumfile"), return undef) if (!defined($sysnumfile));
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter readonly"),   return undef) if (!defined($readonly));
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter size"),       return undef) if (!defined($size));
    (ftp_util::PLog("ftp : ftp_class_fs : new : invalid parameter owner"),      return undef) if (!defined($owner));

    my $self = {};

    $class = ref($class) || $class;
    bless ($self, $class);
    $self->{PARENT}     = $parent;
    $self->{FS}         = $fs;
    $self->{PATH}       = $path;
    $self->{SYSNUMFILE} = $sysnumfile;
    $self->{READONLY}   = $readonly;
    $self->{SIZE}       = $size;
    $self->{OWNER}      = $owner;

    $self->{AllocateStep} = 100 * 1024;

    return $self;
}


sub DESTROY()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    if (defined($self->{TMPFILE})) {
        $self->UnlinkTMPFile();
    }
}


sub isFolder()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    return $self->{SYSNUMFILE} == 0;
}


sub isReadonly()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 1;
    }

    return $self->{READONLY};
}


sub parent()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    return $self->{PARENT};
}


sub path()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    return $self->{PATH};
}


sub fileName()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }
    if ($self->{PATH} =~ /[^\\\/]+$/) {
        return $&;
    }
    return $self->{PATH};
}


sub fs()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }
    return $self->{FS};
}


sub size()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }
    return $self->{SIZE};
}


sub touch()
{
    my ($self, $date) = @_;
    if (!defined($self) || !defined($date)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }
    my ($fs) = $self->{FS};
    my ($r);


    my ($S,$M,$H,$d,$m,$y) = localtime($date);
    $y += 1900; $m += 1;

    ftp_util::PLog("ftp : fs.touch : set touch to $y-$m-$d $H:$M:$S");

    $r = DBExec("update fs set creat = '$y-$m-$d $H:$M:$S' where sysnum = $fs;");
    if ($r->isError()) {
        $self->{LASTERROR} = DBLastError();
        return 0;
    }

    return 1;
}


sub owner()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }
    return $self->{OWNER};
}


sub getLocalFileName()
{
        my ($self) = @_;
        if (!defined($self)) {
            $self->{LASTERROR} = "Parameter not set";
            return 0;
        }

        my ($sysnumfile) = $self->{SYSNUMFILE};
        my ($storage) = $self->NumStorage();

        return "${main::PROGRAM_FILES}/storage${storage}/${sysnumfile}";
}



sub CalcMd5Part()
{
    my ($self, $Length) = @_;
    if (!defined($self) || !defined($Length)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    ftp_util::PLog("ftp : fs.CalcMd5Part : Length $Length");

    my($FHandler) = $self->openEntryFile();
    if(!defined($FHandler)) {
        return undef;
    }

    my ( $ctx ) = Digest::MD5->new;

    my ( $BufSize ) = 10240;
    my ( $TotalRead ) = 0;
    my ( $Buf, $ReadBlockSize, $ReadCount );

    $FHandler->seek(0, 0);

    ftp_util::PLog("ftp : fs.CalcMd5Part : Firsl Read Block Size $ReadBlockSize");

    $ReadBlockSize = ( $Length < $BufSize ) ? $Length : $BufSize;
    $ReadCount = sysread($FHandler, $Buf, $ReadBlockSize);

    while( defined($ReadCount) && $ReadCount > 0 && $ReadBlockSize > 0 ) {

          $TotalRead += $ReadCount;
          $ctx->add($Buf);

          $ReadBlockSize = ( ( $Length - $TotalRead ) < $BufSize ) ? $Length - $TotalRead : $BufSize;
          $ReadCount = sysread($FHandler, $Buf, $ReadBlockSize);
    }

    if (!defined($ReadCount)) {
      $self->{LASTERROR} = "Reading Error";
      return undef;
    }

    return $ctx->hexdigest ;
}


sub CalcMd5()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    if ($self->isFolder()) {
        $self->{LASTERROR} = "Unpossible calc md5 on folder";
        return 0;
    }

    my ($sysnumfile) = $self->{SYSNUMFILE};
    my ($r_file) = DBExec("select * from file where sysnum = '$sysnumfile'");
    if ($r_file->NumRows() != 1) {
        $self->{LASTERROR} = "Unpossible find md5 in database";
        return 0;
    }

    return $r_file->Value("fcrc");
}


sub NumStorage()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    if ($self->isFolder()) {
        $self->{LASTERROR} = "Unpossible storage num on folder";
        return 0;
    }

    my ($sysnumfile) = $self->{SYSNUMFILE};
    my ($r_file) = DBExec("select * from file where sysnum = '$sysnumfile'");
    if ($r_file->NumRows() != 1) {
        $self->{LASTERROR} = "Unpossible find storage in database";
        return 0;
    }

    return $r_file->Value("numstorage");
}


sub listSubEntry()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }
    my ($fs)         = $self->{FS};
    my ($path)       = $self->{PATH};
    my ($sysnumfile) = $self->{SYSNUMFILE};
    my ($readonly)   = $self->{READONLY};
    my ($r, @rez);

    if ($fs != 0 && $sysnumfile != 0) {
        $r = DBExec("select fs.name, usr.name as owner, domain.name as ownerdomain, fs.creat, file.fsize from domain, usr, fs, file where fs.sysnum = '" . $fs . "' and fs.owner = usr.sysnum and fs.sysnumfile = file.sysnum and domain.sysnum = usr.sysnumdomain");
        while(!$r->Eof()) {
            $rez[$#rez + 1] = { sysnumfile => ($r->Value("sysnumfile") eq 0 ? "d" : "-"),
                                owner => $r->Value("owner") . "\@" . $r->Value("ownerdomain"),
                                fsize => $r->Value("fsize"),
                                name => $r->Value("name"),
                                creat => $r->Value("creat") };
            $r->Next();
        }
    } else {
        $r = DBExec("select fs.name, usr.name as owner, domain.name as ownerdomain, fs.creat, fs.sysnumfile, 0 as fsize from domain, usr, fs left join acc on fs.sysnum = acc.sysnumfs and acc.username = '$session::User'       where fs.up = '" . $fs . "' and fs.owner = usr.sysnum and fs.sysnumfile = 0           and fs.owner = '" . $self->{OWNER} . "' and domain.sysnum = usr.sysnumdomain and (acc.access <> 'n' or acc.access is NULL)");
        while(!$r->Eof()) {
            $rez[$#rez + 1] = { sysnumfile => ($r->Value("sysnumfile") eq 0 ? "d" : "-"),
                                owner => $r->Value("owner") . "\@" . $r->Value("ownerdomain"),
                                fsize => $r->Value("fsize"),
                                name => $r->Value("name"),
                                creat => $r->Value("creat") };
            $r->Next();
        }

        $r = DBExec("select fs.name, usr.name as owner, domain.name as ownerdomain, fs.creat, fs.sysnumfile, file.fsize from domain, usr, fs left join acc on fs.sysnum = acc.sysnumfs and acc.username = '$session::User', file where fs.up = '" . $fs . "' and fs.owner = usr.sysnum and fs.sysnumfile = file.sysnum and fs.owner = '" . $self->{OWNER} . "' and domain.sysnum = usr.sysnumdomain and (acc.access <> 'n' or acc.access is NULL)");
        while(!$r->Eof()) {
            $rez[$#rez + 1] = { sysnumfile => ($r->Value("sysnumfile") eq 0 ? "d" : "-"),
                                owner => $r->Value("owner") . "\@" . $r->Value("ownerdomain"),
                                fsize => $r->Value("fsize"),
                                name => $r->Value("name"),
                                creat => $r->Value("creat") };
            $r->Next();
        }
    }

    return @rez;
}


sub insertSubEntry
{
    my ($self, $Name, $TmpFileName, $md5) = @_;
    if (!defined($self) || !defined($Name)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }
    my ($SysNumFile, $SizeFile);
    my ($fs)          = $self->{FS};
    my ($path)        = $self->{PATH};
    my ($sysnumfile)  = $self->{SYSNUMFILE};
    my ($readonly)    = $self->{READONLY};
    my ($owner)       = $self->{OWNER};

    ftp_util::PLog("ftp : fs.insertSubEntry " . join(" ", @_));

    if (!$self->isFolder()) {
        $self->{LASTERROR} = "path /${path} is not folder.";
        return undef;
    }

    if ($self->isReadonly()) {
        $self->{LASTERROR} = "Folder readonly.";
        return undef;
    }

    if (defined($self->{TMPFILE})) {
        ftp_util::PLog("ftp : fs.insertSubEntry : Used TMP File");
        if (!defined($TmpFileName)) {
            $TmpFileName = $self->{TMPFILE}->{NamFile}
        }
        if (!defined($md5)) {
            $md5 = $self->{TMPFILE}->{md5}
        }
    }

    #Check quote
    if (defined($TmpFileName)) {
        my $quotes = GetQuote($self->{OWNER});

        $SizeFile = -s $TmpFileName;

        ftp_util::PLog(sprintf("ftp : fs.insertSubEntry : Check Disk's Quote : Domain's Disk Usage '%s'; User's Disk Usage '%s'; Domain's Quote '%s'; User's Quote '%s'; Insert Size '%s'", $quotes->{DomainDiskUsage}, $quotes->{UsrDiskUsage}, $quotes->{DomainQuote}, $quotes->{UsrQuote}, $SizeFile));


        if ( $quotes->{UsrDiskUsage} + $SizeFile >= $quotes->{UsrQuote} ) {
            $self->{LASTERROR} = sprintf("Over User Disk's Quote : Disk Usage %s Insert Size %d Quote %s", $quotes->{UsrDiskUsage}, $SizeFile, $quotes->{UsrQuote});
            return undef;
        }

        if ( $quotes->{DomainDiskUsage} + $SizeFile >= $quotes->{DomainQuote} ) {
            $self->{LASTERROR} = sprintf("Over Domain Disk's Quote : Disk Usage %s Insert Size %d Quote %s", $quotes->{DomainDiskUsage}, $SizeFile, $quotes->{DomainQuote});
            return undef;
        }
    } else {
        $SizeFile = 0;
    }


    DBExec("begin");
    DBExec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE");

    if (defined($TmpFileName)) {
        my($SearchMask) = "^${main::PROGRAM_FILES}/storage([0-9]+)/([0-9]+)\$";
        if ($TmpFileName =~ /$SearchMask/) {
            ftp_util::PLog("ftp : fs.insertSubEntry : Call RefreshLink with '$TmpFileName'");
            my($storage) = $1;
            my($sysnum) = $2;
            ftp_util::PLog("ftp : fs.insertSubEntry : storage '$storage' sysnum '$sysnum'");
            $SysNumFile = ftp_util::RefreshLink($sysnum, $md5);
        } else {
            ftp_util::PLog("ftp : fs.insertSubEntry : Call SaveAsLink");

            $SysNumFile = ftp_util::SaveAsLink($TmpFileName, $md5);
            if ($SysNumFile <= 0) {
                $self->{LASTERROR} = "Impossible save link";
                DBExec("commit");
                return undef;
            }
        }

        ftp_util::PLog("ftp : fs.insertSubEntry :  File saved with number link  $SysNumFile");

        ftp_util::PLog("ftp : fs.insertSubEntry : Delete previous version of file");
        $self->deleteSubEntry($Name, 1); # "1" - что бы не ставить блокировок на базу. Они уже стоят в этой функции
        ftp_util::PLog("ftp : fs.insertSubEntry : fs.deleteSubEntry LASTERROR : '$self->{LASTERROR}'");
    } else {
        ftp_util::PLog("ftp : fs.insertSubEntry : New Folder detected");
        $SysNumFile = 0;
    }

    $Name =~ s/'/''/g; # convert quote to 2 quotes

    #UPDATE usr SET diskusage = diskusage - 1  where file.sysnum = 7692 and fs.sysnumfile = file.sysnum and fs.owner = usr.sysnum

    my($SysNumFs);
    if (defined($self->{TMPFILE})) {
        $SysNumFs = $self->{TMPFILE}->{NumFS};
        ftp_util::PLog("ftp : fs.insertSubEntry : Update fs with SysNum : '$SysNumFs'");
        my($r) = DBExec("UPDATE fs SET name = '$Name', ftype = 'f', up = $fs WHERE sysnum = $SysNumFs");

        if ($r->isError()) {
            $self->{LASTERROR} = "Impossible save file into table fs";
            DBExec("commit");
            return undef;
        }
    } else {
        ftp_util::PLog("ftp : fs.insertSubEntry : Get SysNum");
        my($r) = DBExec("select NextVal('fs_seq') as maxsysnum");
        $SysNumFs = $r->Value("maxsysnum");

        ftp_util::PLog("ftp : fs.insertSubEntry : Insert into fs with SysNum : '$SysNumFs'");
        $r = DBExec("insert into fs (sysnum, name, ftype, up, sysnumfile, owner, creat) values ('$SysNumFs', '$Name', 'f', $fs, ${SysNumFile}, '$owner', 'now'::abstime)");

        if ($r->isError()) {
            $self->{LASTERROR} = "Impossible save file into table fs";
            DBExec("commit");
            return undef;
        }
    }

    DBExec("commit");

    ftp_util::PLog("ftp : fs.insertSubEntry : File saved with id $SysNumFs");

    my($result) =  $self->new($self, $SysNumFs, ($path ne "" ? "$path/" : "") . $Name, $SysNumFile, $readonly, $SizeFile, $owner);

    ftp_util::PLog("ftp : fs.insertSubEntry : Created file '/" . $result->path() . "' with id " . $result->{FS});
    return $result;
}


sub deleteSubEntry
{
    my ($self, $Name, $clockFlag) = @_; # $clockFlag - флаг не блокировать базу, она уже заблокирована
    if (!defined($self) || !defined($Name)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    ftp_util::PLog("ftp : fs.deleteSubEntry");
    ftp_util::PLog("ftp : fs.deleteSubEntry : clockFlag : " . print_results($clockFlag));

    $clockFlag = 0 if (!defined($clockFlag));

    my ($fs)          = $self->{FS};
    my ($path)        = $self->{PATH};
    my ($sysnumfile)  = $self->{SYSNUMFILE};
    my ($readonly)    = $self->{READONLY};
    my ($r, @rez);

    $self->{LASTERROR} = "";

    if (!$self->isFolder()) {
        $self->{LASTERROR} = "path /${path} is not folder.";
        return 0;
    }

    if ($self->isReadonly()) {
        $self->{LASTERROR} = "Folder readonly.";
        return 0;
    }

    $r = $self->getSubEntry($Name);
    if ( !defined($r) ) {
        $self->{LASTERROR} = "Entry '$Name' not exist in '/$path'";
        return 0;
    }

    if ($r->isFolder()) {
        @rez = $r->listSubEntry();
        if ($#rez != -1) {
            $self->{LASTERROR} = "Entry '/" . $r->path() . "' not empty";
            return 0;
        }
    }

    $Name =~ s/'/''/g; # convert quote to 2 quotes

    ftp_util::PLog("ftp : fs.deleteSubEntry : Deleting file '/" . $r->path() . "' with id " . $r->{FS});

    if (!$clockFlag) {
        DBExec("begin");
        DBExec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE");
    }

    DBExec("delete from fs where sysnum = " . $r->{FS});

    if (!$clockFlag) {
        DBExec("commit");
    }

    return 1;
}


sub renameSubEntry
{
    my ($self, $SName, $DName) = @_;
    if (!defined($self) || !defined($SName) || !defined($DName)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    my ($fs)         = $self->{FS};
    my ($path)       = $self->{PATH};
    my ($sysnumfile) = $self->{SYSNUMFILE};
    my ($readonly)   = $self->{READONLY};
    my ($r, @rez);

    if ($readonly) {
        $self->{LASTERROR} = "Folder readonly.";
        return 0;
    }

    $SName =~ s/'/''/g; # convert quote to 2 quotes
    $DName =~ s/'/''/g;

    $r = DBExec("select * from fs where up = '$self->{FS}' and owner = '$self->{OWNER}' and ftype = 'f' and name = '${SName}'");
    if ($r->NumRows() != 1) {
        $self->{LASTERROR} = "Source path not found";
        return 0;
    }

    DBExec("update fs set name = '$DName' where sysnum = '" . $r->Value("sysnum") . "'");

    return 1;
}

sub getSubEntry($)
{
    my ($self, $FName) = @_;
    if (!defined($self) || !defined($FName)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    my ($fs)         = $self->{FS};
    my ($path)       = $self->{PATH};
    my ($sysnumfile) = $self->{SYSNUMFILE};
    my ($readonly)   = $self->{READONLY};
    my ($r);

    $FName =~ s/'/''/g; # convert quote to 2 quotes

    $r = DBExec("select * from fs left join acc on fs.sysnum = acc.sysnumfs and acc.username = '$session::User' where up = '${fs}' and owner = '$self->{OWNER}' and ftype = 'f' and name = '$FName' and (acc.access <> 'n' or acc.access is NULL)");
    if ( $r->NumRows() == 0 ) {
        return undef;
    } else {
        my($SysNum) = $r->Value("sysnum");
        my($SysNumFile) = $r->Value("sysnumfile");
        my($Owner) = $r->Value("owner");
        my($Dir) = $path;
        $Dir .= "/" if ($Dir ne "");
        $Dir .= $r->Value("name");
        my($Size) = 0;
        if ($SysNumFile != 0) {
            $r = DBExec("select * from file where sysnum = '$SysNumFile'");
            $Size = $r->Value("fsize");
        }
        return $self->new($self, $SysNum, $Dir, $SysNumFile, $readonly, $Size, $Owner);
    }
}


sub openEntryFile()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }


    my($LocalName) = $self->getLocalFileName();

    ftp_util::PLog("ftp : fs.openEntryFile : localpath $LocalName");

    my($rez) = new IO::File->new($LocalName, "r");

    if (!$rez) {
        $self->{LASTERROR} = "Internal error : $!";
        return undef;
    }

    if (!$rez->opened()) {
        $self->{LASTERROR} = "Internal error : $!";
        return undef;
    }

    return $rez;
}


sub RestoreMD5
{
    my ($self, $md5) = @_;
    if (!defined($self) || !defined($md5)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    ftp_util::PLog("ftp : fs.RestoreMD5 : MD5 :" . $md5);

    my ($SysNumFile) = $self->{SYSNUMFILE};

    ftp_util::RefreshLink($SysNumFile, $md5)
}


sub GetTMPFile
{
    ftp_util::PLog ("ftp : fs.GetTMPFile");

    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    if (defined($self->{TMPFILE})) {
        $self->UnlinkTMPFile();
    }

    #-------------
    my($r_storage) = DBExec("SELECT sysnum from storages order by ( size - used ) desc LIMIT 1");
    my($StorageNumber) = $r_storage->Value("sysnum");
    ftp_util::PLog ("ftp : fs.GetTMPFile :  Selected storage number '$StorageNumber'");
    $self->{TMPFILE}->{NumStorage} = $StorageNumber;

    #-------------
    my($r_file) = DBExec("SELECT nextval('file_seq'::text) as sysnum");
    my($TMPFileNumber) = $r_file->Value("sysnum");
    $self->{TMPFILE}->{NumFile} = $TMPFileNumber;

    #-------------
    my($AllocateSize) = $self->{AllocateStep};
    ftp_util::PLog ("ftp : fs.GetTMPFile :  Allocated TMP file number $TMPFileNumber in size $AllocateSize");
    DBExec("insert into file (sysnum, fsize, numstorage) VALUES (${TMPFileNumber}, ${AllocateSize}, ${StorageNumber})");
    $self->{TMPFILE}->{AllocateSize} = $AllocateSize;
    $self->{TMPFILE}->{Size} = 0;

    #-------------
    my($TMPFileName) = "${main::PROGRAM_FILES}/storage${StorageNumber}/${TMPFileNumber}";
    $self->{TMPFILE}->{NamFile} = $TMPFileName;

    #-------------
    my($r_fs) = DBExec("SELECT nextval('fs_seq'::text) as sysnum");
    my($TMPFSNumber) = $r_fs->Value("sysnum");
    $self->{TMPFILE}->{NumFS} = $TMPFSNumber;

    #-------------
    ftp_util::PLog ("ftp : fs.GetTMPFile :  Allocated TMP FS number $TMPFSNumber");
    DBExec("insert into fs (sysnum, up, sysnumfile, owner) VALUES (${TMPFSNumber}, -1, ${TMPFileNumber}, $self->{OWNER})");

    return $TMPFileName;
}


sub UnlinkTMPFile
{
    ftp_util::PLog ("ftp : fs.UnlinkTMPFile");

    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    if (!defined($self->{TMPFILE})) {
        return;
    }

    my($r);
    $r = DBExec("SELECT * FROM fs WHERE sysnum = " . $self->{TMPFILE}->{NumFS});
    if ($r->NumRows() == 1 && $r->Value("up") == -1) {
        ftp_util::PLog ("ftp : fs.UnlinkTMPFile :  Uallocated TMP FS number " . $self->{TMPFILE}->{NumFS});
        DBExec("DELETE FROM fs WHERE sysnum = " . $self->{TMPFILE}->{NumFS});
    }

    $r = DBExec("SELECT * FROM file WHERE sysnum = " . $self->{TMPFILE}->{NumFile});
    if ($r->NumRows() == 1 && $r->Value("nlink") == 0) {
        ftp_util::PLog ("ftp : fs.UnlinkTMPFile :  Uallocated TMP file number " . $self->{TMPFILE}->{NumFile});
        DBExec("DELETE FROM file WHERE sysnum = " . $self->{TMPFILE}->{NumFile});

        if (-e $self->{TMPFILE}->{NamFile}) {
            unlink($self->{TMPFILE}->{NamFile});
        }
    }

    delete $self->{TMPFILE};
}


sub insertTmpFile
{
    ftp_util::PLog ("ftp : fs.insertTmpFile");

    my ($self, $Name, $md5) = @_;
    if (!defined($self) || !defined($Name)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    if (!defined($self->{TMPFILE})) {
        return undef;
    }

    $self->{TMPFILE}->{md5} = $md5;

    ftp_util::PLog ("ftp : fs.insertTmpFile : Insert TMP file number " . $self->{TMPFILE}->{NumFile} . " into name $Name");

    my($result) = $self->insertSubEntry($Name);

    if ($result) {
        ftp_util::PLog ("ftp : fs.insertTmpFile : insert success. Delete TMPFILE data");
        delete $self->{TMPFILE};
    }

    return $result;
}


sub TmpStoreProcess
{
    my ($self, $count) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    if (!defined($count)) {
        $count = 0;
    }

    $TmpStoreProcess::Message = "";

    if (!defined($self->{TMPFILE})) {
        if ($self->{SYSNUMFILE} == 0) {
            return 1;
        }
        if (!defined($self->{ResumingSize})) {
            $self->{ResumingSize} = -s $self->getLocalFileName();
            $self->{AllocateSize} = 0;
            ftp_util::PLog("ResumingSize " . $self->{ResumingSize})
        } else {
            $self->{ResumingSize} += $count;
        }

        if ($self->{ResumingSize} < $self->{AllocateSize}) {
            return 1;
        }

        while ($self->{ResumingSize} > $self->{AllocateSize}) {
            $self->{AllocateSize} += $self->{AllocateStep};
        }

        ftp_util::PLog ("ftp : fs.TMPStoreProcess : Reallocation Resuming File " . $self->{SYSNUMFILE} . " in size : " . $self->{AllocateSize});
        DBExec("UPDATE file SET fsize = " . $self->{AllocateSize} . ", lastmodify = 'now' WHERE sysnum = " . $self->{SYSNUMFILE});
    } else {
        $self->{TMPFILE}->{Size} += $count;
        #ftp_util::PLog ("ftp : fs.TmpStoreProcess : step size : $count tmp size : " . $self->{TMPFILE}->{Size});

        if ($self->{TMPFILE}->{Size} < $self->{TMPFILE}->{AllocateSize}) {
            return 1;
        }

        while ($self->{TMPFILE}->{Size} > $self->{TMPFILE}->{AllocateSize}) {
            $self->{TMPFILE}->{AllocateSize} += $self->{AllocateStep};
        }

        ftp_util::PLog ("ftp : fs.TMPStoreProcess : Reallocation TMP File " . $self->{TMPFILE}->{NumFile} . " in size : " . $self->{TMPFILE}->{AllocateSize});
        DBExec("UPDATE file SET fsize = " . $self->{TMPFILE}->{AllocateSize} . ", lastmodify = 'now' WHERE sysnum = " . $self->{TMPFILE}->{NumFile});
    }

    if ( !($self->CheckQuote()) ) {
        $TmpStoreProcess::Message = "Quote Overflow";
        return undef;
    }

    return 1;
}


sub CheckQuote()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    my($Quote) = GetQuote($self->{OWNER});
    return !($Quote->{QuoteOver});
}

1;

