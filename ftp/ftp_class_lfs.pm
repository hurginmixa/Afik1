package ftp_class_lfs;

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


use POSIX;
use IO::File;
use File::Copy;
use Fcntl ':mode';
use db_pg;
use ftp_util qw(Log);

use strict 'vars';

# ------------------------------------------------------------------------
#  new($parent, $path, $localpath, $owner)
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

#  listSubEntry()
#  insertSubEntry($Name, $TmpFileName, $md5)
#  deleteSubEntry($Name)
#  renameSubEntry($SName, $DName)
#  getSubEntry($FName)
#  openEntryFile()


sub new
{
    my $class      = shift;
    my $parent     = shift;
    my $path       = shift;
    my $localpath  = shift;
    my $owner      = shift;
    return undef if (!defined($class));
    return undef if (!defined($path));
    return undef if (!defined($localpath));
    return undef if (!defined($owner));

    my $self = {};

    $class = ref($class) || $class;

    bless ($self, $class);
    $self->{PARENT}     = $parent;
    $self->{PATH}       = $path;
    $self->{LOCALPATH}  = $localpath;
    $self->{OWNER}      = $owner;
    return $self;
}

sub isFolder()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }

   my ($localpath) = $self->{LOCALPATH};

   return -d "/" . $localpath;
}


sub isReadonly()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return 1;
   }

   return 0;
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

   return 0;
}

sub size()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }

   my ($localpath) = $self->{LOCALPATH};

   return -s "/" . $localpath;
}

sub touch()
{
    my ($self, $date) = @_;
    if (!defined($self) || !defined($date)) {
      $self->{LASTERROR} = "Parameter not set";
      return undef;
    }
    my ($localpath) = $self->{LOCALPATH};
    utime($date, $date, "/$localpath");

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

   my ($localpath) = $self->{LOCALPATH};
   return "/$localpath";
}


sub listSubEntry()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return ();
   }

   local *H;
   my ($path)      = $self->{PATH};
   my ($localpath) = $self->{LOCALPATH};
   my (@rez, @list, $r);

   $localpath = "/" . $localpath;

   if (!opendir(H, $localpath)) {
     $self->{LASTERROR} = "Internal : $!";
     return 0;
   }

   @list = readdir(H);

   close(H);

   foreach $r (@list) {
      my(@st) = stat($localpath . (($localpath ne "/") ? "/" : "") . $r);

      if ($r eq "." || $r eq "..") {
        next;
      }

      $rez[$#rez + 1] = { sysnumfile => (($st[2] & S_IFDIR) ? "d" : "-"),
                          owner => $session::User,
                          fsize => $st[7],
                          name => $r,
                          creat => POSIX::strftime("%Y-%m-%d %H:%M:%S", localtime($st[9])) };
   }

   return @rez;
}


sub getSubEntry($)
{
   my ($self, $FName) = @_;
   if (!defined($self) || !defined($FName)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }

   my ($parent)    = $self->{PARENT};
   my ($path)      = $self->{PATH};
   my ($localpath) = $self->{LOCALPATH};
   my ($owner)     = $self->{OWNER};
   my (@rez, @list, $r);

   $localpath = "/" . $localpath;

   if (-e $localpath . (($localpath ne "/") ? "/" : "") . $FName) {
     return $self->new($self, $path . (($path ne "/") ? "/" : "") . $FName, $localpath . (($localpath ne "/") ? "/" : "") . $FName, $owner);
   }
   return undef;
}

sub insertSubEntry
{
    my ($self, $Name, $TmpFileName, $md5) = @_;
    if (!defined($self) || !defined($Name)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    my ($parent)    = $self->{PARENT};
    my ($path)      = $self->{PATH};
    my ($localpath) = "/" . $self->{LOCALPATH};
    my ($localname)  = $localpath . (($localpath ne "/") ? "/" : "") . $Name;
    my ($owner)     = $self->{OWNER};
    my (@stat_arr, $mod, $nlink, $uid, $grp);

    if (!$self->isFolder()) {
        $self->{LASTERROR} = "path /${path} is not folder.";
        return undef;
    }

    if ($self->isReadonly()) {
        $self->{LASTERROR} = "Folder /${path} is readonly.";
        return undef;
    }

    my ($r) = $self->getSubEntry($Name);
    if (defined($r)) {
        @stat_arr = stat("/" . $r->{LOCALPATH});
        $mod = $stat_arr[2]; $nlink = $stat_arr[3]; $uid = $stat_arr[4]; $grp = $stat_arr[5];
    } else {
        @stat_arr = stat("/" . $localpath);
        $mod = 0; $nlink = 0; $uid = $stat_arr[4]; $grp = $stat_arr[5];
    }
    if (!defined($uid) || !defined($grp)) {
        $mod = 0; $nlink = 0; $uid = $main::SystemUserUid; $grp = $main::SystemUserGid;
    }

    if (defined($TmpFileName)) {
        if(defined($r)) {
            $self->deleteSubEntry($Name);
        }
        if (!File::Copy::copy($TmpFileName, $localname)) {
            $self->{LASTERROR} = "Impossible save link";
            return undef;
        }
    } else {
        if (!mkdir($localname)) {
            $self->{LASTERROR} = "Impossible create folder";
            return undef;
        }
        $mod = 0;
    }

    chown($uid, $grp, $localname);
    if ($mod != 0) {
        chmod($mod, $localname);
    }

    #new($parent, $path, $localpath, $owner)

    $localname =~ s/^\///;
    return $self->new($self, ($path ne "" ? "$path/" : "") . $Name, $localname, $owner);
}

sub deleteSubEntry
{
    my ($self, $Name) = @_;
    if (!defined($self) || !defined($Name)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    my ($parent)    = $self->{PARENT};
    my ($path)      = $self->{PATH};
    my ($localpath) = $self->{LOCALPATH};
    my ($owner)     = $self->{OWNER};
    my (@rez, $r);

    $localpath = "/" . $localpath;

    if (!$self->isFolder()) {
        $self->{LASTERROR} = "Path /${path} is not folder.";
        return 0;
    }

    if ($self->isReadonly()) {
        $self->{LASTERROR} = "Folder /${path} is readonly.";
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
        rmdir($localpath . (($localpath ne "/") ? "/" : "") . $Name);
    } else {
        unlink($localpath . (($localpath ne "/") ? "/" : "") . $Name);
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

    my ($parent)    = $self->{PARENT};
    my ($path)      = $self->{PATH};
    my ($localpath) = $self->{LOCALPATH};
    my ($owner)     = $self->{OWNER};

    $localpath = "/" . $localpath;

    if (!$self->isFolder()) {
        $self->{LASTERROR} = "Path /${path} is not folder.";
        return 0;
    }

    if ($self->isReadonly()) {
        $self->{LASTERROR} = "Folder /${path} is readonly.";
        return 0;
    }

    if (!rename($localpath . (($localpath ne "/") ? "/" : "") . $SName, $DName)) {
        $self->{LASTERROR} = "Folder readonly.";
        return 0;
    }
    return 1;
}

sub openEntryFile()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }

    my ($localpath) = $self->{LOCALPATH};
    $localpath = "/" . $localpath;

    my($rez) = new IO::File->new($localpath, "r");

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


sub RestoreMD5()
{
    my ($self, $md5) = @_;
    if (!defined($self) || !defined($md5)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    return 1;
}


sub GetTMPFile()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    if (defined($self->{TMPFILE})) {
        $self->UnlinkTMPFile();
    }

    $self->{TMPFILE} = "/tmp/afik1ftp_upld_$$";

    return $self->{TMPFILE};
}

sub UnlinkTMPFile
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    if (!defined($self->{TMPFILE})) {
        return;
    }

    if (-e $self->{TMPFILE}) {
        unlink($self->{TMPFILE});
    }

    delete $self->{TMPFILE};
}


sub insertTmpFile
{
    my ($self, $Name, $md5) = @_;
    if (!defined($self) || !defined($Name)) {
        $self->{LASTERROR} = "Parameter not set";
        return undef;
    }

    if (!defined($self->{TMPFILE})) {
        return undef;
    }

    my($result) = $self->insertSubEntry($Name, $self->{TMPFILE}, $md5);

    if ($result) {
        $self->UnlinkTMPFile();
    }

    return $result;
}


sub TmpStoreProcess
{
    $TmpStoreProcess::Message = "";
    return 1;
}


sub CheckQuote()
{
    return 1;
}

1;

