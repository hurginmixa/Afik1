package ftp_class_fftp_fs;

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

# ------------------------------------------------------------------------

use ftp_class_fs; #ftp_class_fs.pm
use db_pg;

@ISA = qw/ftp_class_fs Explorer/;

use strict 'vars';

sub openEntryFile()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }

   my ($fs)         = $self->{FS};
   my($r_perm) = DBExec("select getpermission('$session::User', $fs) & 1 as perm");
   if ($r_perm->Value("perm") ne "1") {
     $self->{LASTERROR} = "Download is inpossible";
     return undef;
   }

   return $self->ftp_class_fs::openEntryFile();
}

sub getLocalFileName()
{
   my ($self) = @_;
   if (!defined($self)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }

   my ($fs)         = $self->{FS};
   my($r_perm) = DBExec("select getpermission('$session::User', $fs) & 1 as perm");
   if ($r_perm->Value("perm") ne "1") {
     $self->{LASTERROR} = "Download is inpossible";
     return undef;
   }

   return $self->ftp_class_fs::getLocalFileName();
}

sub deleteSubEntry
{
   my ($self, $Name) = @_;
   if (!defined($self) || !defined($Name)) {
     $self->{LASTERROR} = "Parameter not set";
     return 0;
   }

   my ($fs)         = $self->{FS};
   my($r_perm) = DBExec("select getpermission('$session::User', $fs) & 3 as perm");
   if ($r_perm->Value("perm") ne "3") {
     $self->{LASTERROR} = "Download is inpossible";
     return undef;
   }

   return $self->ftp_class_fs::deleteSubEntry($Name);
}

1;

