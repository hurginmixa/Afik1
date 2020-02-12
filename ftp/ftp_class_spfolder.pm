package ftp_class_spfolder;

#
# ftp_class_fs.pm
#  \
#   |- ftp_class_fftp_fs.pm
#   |- ftp_class_spfolder.pm
#      \
#       |- ftp_class_fftp_resour.pm
#       |- ftp_class_fftp_users.pm
#       |- ftp_class_root.pm
#

use db_pg;
use ftp_util qw(Log);

# ------------------------------------------------------------------------
#  new($parent, $fs, $path, $sysnumfile, $readonly, $size,  $owner)
#  listSubEntry()
#  insertSubEntry($Name, $TmpFileName)
#  deleteSubEntry($Name)
#  renameSubEntry($SName, $DName)
#  getSubEntry($FName)
#  touch($date)


use ftp_class_fs;
@ISA = qw/Exporter ftp_class_fs/;

use strict 'vars';

sub owner()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }
   return 0;
}



sub listSubEntry()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }
   my (@rez);
   return @rez;
}


sub insertSubEntry
{
   my ($self, $Name, $TmpFileName, $md5) = @_;
   if (!defined($self) || !defined($Name)) {
     $self->{LASTERROR} = "Parameter not set";
     return undef;
   }

   $self->{LASTERROR} = "Folder readonly.";
   return undef;
}

sub deleteSubEntry
{
   my ($self, $Name) = @_;
   if (!defined($self) || !defined($Name)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }

   $self->{LASTERROR} = "Folder readonly.";
   return 0;
}

sub renameSubEntry
{
   my ($self, $SName, $DName) = @_;
   if (!defined($self) || !defined($SName) || !defined($DName)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }
   $self->{LASTERROR} = "Folder readonly.";
   return 0;
}

sub getSubEntry($)
{
  my ($self, $FName) = @_;
  if (!defined($self) || !defined($FName)) {
    $self->{LASTERROR} = "Parameter not set";
    return undef;
  }

  $self->{LASTERROR} = "Folder readonly.";
  return undef;
}


sub openEntryFile()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return undef;
   }

   $self->{LASTERROR} = "Folder readonly.";
   return undef;
}

sub touch()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return undef;
   }

   $self->{LASTERROR} = "Folder or file readonly.";
   return undef;
}


sub CheckQuote()
{
    return 0;
}


1;

