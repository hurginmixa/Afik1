package ftp_class_fftp_resour;

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

use ftp_class_fftp_fs;
use db_pg;
use ftp_util qw(Log);

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
    my $path       = shift;
    my $sysnumusr  = shift;
    my $fs         = -3;
    my $sysnumfile = 0;
    my $readonly   = 1;
    my $size       = 0;
    my $owner      = $session::User;

    my($self) = $class->SUPER::new($parent, $fs, $path, $sysnumfile, $readonly, $size,  $owner);
    $self->{SYSNUMUSR} = $sysnumusr;
    return $self;
}

sub listSubEntry()
{
   my ($self) = @_;
   return 0 if (!defined($self));
   my(@rez, $r);
   my($SQL);

   $SQL = "select fs.name, usr.name as owner, domain.name as ownerdomain, fs.creat, fs.sysnumfile, 0 as fsize  from fs, usr,       acc where fs.ftype = 'f' and fs.owner = usr.sysnum and fs.sysnumfile = 0 and           acc.sysnumfs = fs.sysnum and acc.username = '$session::User' and acc.access != 'n' and (acc.expdate >= 'now'::abstime or acc.expdate is NULL) and domain.sysnum = usr.sysnumdomain" . (defined($self->{SYSNUMUSR}) && $self->{SYSNUMUSR} != 0 ? " and fs.owner = '$self->{SYSNUMUSR}'" : "");
   #ftp_util::PLog("ftp : ftp_class_fftp_resour : listSubEntry : SQL1 :-> $SQL");
   $r = DBExec($SQL);
   while(!$r->Eof()) {
        $rez[$#rez + 1] = { sysnumfile => ($r->Value("sysnumfile") eq 0 ? "d" : "-"),
                            owner => $r->Value("owner") . "\@" . $r->Value("ownerdomain"),
                            fsize => $r->Value("fsize"),
                            name  => $r->Value("name"),
                            creat => $r->Value("creat") };
        $r->Next();
   }

   $SQL = "select fs.name, usr.name as owner, domain.name as ownerdomain, fs.creat, fs.sysnumfile, file.fsize  from fs, usr, file, acc where fs.ftype = 'f' and fs.owner = usr.sysnum and fs.sysnumfile = file.sysnum and acc.sysnumfs = fs.sysnum and acc.username = '$session::User' and acc.access != 'n' and (acc.expdate >= 'now'::abstime or acc.expdate is NULL) and domain.sysnum = usr.sysnumdomain" . (defined($self->{SYSNUMUSR}) && $self->{SYSNUMUSR} != 0 ? " and fs.owner = '$self->{SYSNUMUSR}'" : "");
   ftp_util::PLog("ftp : ftp_class_fftp_resour : listSubEntry : SQL2 :-> $SQL");
   $r = DBExec($SQL);
   while(!$r->Eof()) {
        $rez[$#rez + 1] = { sysnumfile => ($r->Value("sysnumfile") eq 0 ? "d" : "-"),
                            owner => $r->Value("owner") . "\@" . $r->Value("ownerdomain"),
                            fsize => $r->Value("fsize"),
                            name  => $r->Value("name"),
                            creat => $r->Value("creat") };
        $r->Next();
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
  my ($r);

  if ($FName eq "all_files") {
      return ftp_class_fftp_resour->new($self, ($self->{PATH} ne "" ? $self->{PATH} . "/" : "") . $FName, 0);

  }

  my($SQL) = "select fs.name, fs.sysnum, fs.owner, fs.creat, fs.sysnumfile, acc.access from fs, acc where fs.name = '$FName' and fs.ftype = 'f' and acc.sysnumfs = fs.sysnum and acc.username = '$session::User' and acc.access != 'n' and (acc.expdate >= 'now'::abstime or acc.expdate is NULL)" . (defined($self->{SYSNUMUSR}) && $self->{SYSNUMUSR} != 0 ? " and fs.owner = '$self->{SYSNUMUSR}'" : "");
  #ftp_util::PLog("ftp : ftp_class_fftp_resour : getSubEntry : SQL :-> $SQL");
  $r = DBExec($SQL);
  if ($r->NumRows() == 0) {
     return undef;
  } else {
     my($SysNum) = $r->Value("sysnum");
     my($SysNumFile) = $r->Value("sysnumfile");
     my($Owner) = $r->Value("owner");
     my ($readonly) = ($r->Value("access") eq "w" || $r->Value("access") eq "u" ? 0 : 1);
     my($Dir) = $path;
     $Dir .= "/" if ($Dir ne "");
     $Dir .= $r->Value("name");
     my($Size) = 0;
     if ($SysNumFile != 0) {
       $r = DBExec("select * from file where sysnum = '$SysNumFile'");
       $Size = $r->Value("fsize");
     }
     #ftp_util::PLog("=$Dir=$readonly=");
     return ftp_class_fftp_fs->new($self, $SysNum, $Dir, $SysNumFile, $readonly, $Size, $Owner);
  }

  return undef;
}

1;
