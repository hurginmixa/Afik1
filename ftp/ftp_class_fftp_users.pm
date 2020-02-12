package ftp_class_fftp_users;

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


use ftp_class_fs;
use ftp_class_fftp_resour;
use db_pg;
use ftp_util;

# ------------------------------------------------------------------------
#  new($parent, $path)
#  listSubEntry()
#  getSubEntry($FName)

use ftp_class_spfolder;
@ISA = qw/ftp_class_spfolder/;

use strict 'vars';

sub new
{
    my $class      = shift;
    my $parent     = shift;
    my $fs         = -2;
    my $path       = shift;
    my $sysnumfile = 0;
    my $readonly   = 1;
    my $size       = 0;
    my $owner      = $session::User;

    return $class->SUPER::new($parent, $fs, $path, $sysnumfile, $readonly, $size,  $owner);
}

sub listSubEntry()
{
   my ($self) = @_;
   return 0 if (!defined($self));
   my(@rez);
   my($r) = DBExec("select usr.name, usr.sysnum, domain.name as domain from usr, domain where usr.sysnumdomain = domain.sysnum and usr.sysnum in (select DISTINCT fs.owner from fs, acc where acc.sysnumfs = fs.sysnum and fs.ftype = 'f' and acc.username = '$session::User' and acc.access != 'n' and (acc.expdate >= 'now'::abstime or acc.expdate is NULL))");
   while(!$r->Eof()) {
        $rez[$#rez + 1] = { sysnumfile => "d",
                            owner => "$session::User",
                            fsize => 0,
                            name => $r->Value("name") . "\@" .  $r->Value("domain"),
                            creat =>  "" };
        $r->Next();
   }

   if ($#rez >= 0) {
        $rez[$#rez + 1] = { sysnumfile => "d",
                            owner => "$session::User",
                            fsize => 0,
                            name => "all_files",
                            creat =>  "" };
   }

   return @rez;
}


sub getSubEntry($)
{
  my ($self, $FName) = @_;
  my ($fs)         = $self->{FS};
  my ($path)       = $self->{PATH};
  my ($sysnumfile) = $self->{SYSNUMFILE};
  my ($readonly)   = $self->{READONLY};
  my ($r_usr);

  if ($FName eq "all_files") {
      return ftp_class_fftp_resour->new($self, ($self->{PATH} ne "" ? $self->{PATH} . "/" : "") . $FName, 0);
  }

  $r_usr = DBExec("select usr.name, usr.sysnum, domain.name as domain from usr, domain where usr.sysnumdomain = domain.sysnum and usr.sysnum in (select DISTINCT fs.owner from fs, acc where acc.sysnumfs = fs.sysnum and fs.ftype = 'f' and acc.username = '$session::User' and acc.access != 'n' and (acc.expdate >= 'now'::abstime or acc.expdate is NULL))");
  while(!$r_usr->Eof()) {
       if ($FName eq ($r_usr->Value("name") . "\@" .  $r_usr->Value("domain"))) {
         return ftp_class_fftp_resour->new($self, ($self->{PATH} ne "" ? $self->{PATH} . "/" : "") . $FName, $r_usr->Value("sysnum"));
       }
       $r_usr->Next();
  }

  return undef;
}


1;

