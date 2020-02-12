package ftp_class_root;

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
#  new()
#  listSubEntry()
#  getSubEntry($FName)

use ftp_util;
use ftp_class_fs;
use ftp_class_lfs;
use ftp_class_fftp_users;

use ftp_class_spfolder;
@ISA = qw/ftp_class_spfolder ftp_util/;

use strict 'vars';

sub new
{
    my $class      = shift;
    my $parent     = undef;
    my $fs         = -1;
    my $path       = "";
    my $sysnumfile = 0;
    my $readonly   = 1;
    my $size       = 0;
    my $owner      = $session::User;


    return $class->SUPER::new($parent, $fs, $path, $sysnumfile, $readonly, $size,  $owner);
}

sub listSubEntry()
{
    my ($self) = @_;
    if (!defined($self)) {
        $self->{LASTERROR} = "Parameter not set";
        return 0;
    }
    my(@rez);

    $rez[$#rez + 1] = { sysnumfile => "d",
                        owner => $session::User,
                        fsize => 0,
                        name => "My_Files",
                        creat => "" };
    $rez[$#rez + 1] = { sysnumfile => "d",
                        owner => $session::User,
                        fsize => 0,
                        name => "Friends_FTP",
                        creat => "" };
    if (defined($session::LUser)) {
        $rez[$#rez + 1] = { sysnumfile => "d",
                            owner => $session::User,
                            fsize => 0,
                            name => "Local_FS",
                            creat => "" };
    }
    return @rez;
}


sub getSubEntry($)
{
    my ($self, $FName) = @_;
    my ($fs)        = $self->{FS};
    my ($path)      = $self->{PATH};
    my ($sysnumfile) = $self->{SYSNUMFILE};
    my ($readonly)   = $self->{READONLY};
    my ($r);
    my ($lpath);

    if ( $FName eq "My_Files" ) {
        return ftp_class_fs->new($self, 0, $FName, 0, 0, 0, $session::UID);
    } elsif ( $FName eq "Friends_FTP" ) {
        return ftp_class_fftp_users->new($self, $FName);
    } elsif ( $FName eq "Local_FS" && defined($session::LUser)) {
        $lpath = $session::LUserInfo[7];
        $lpath =~ s/^\///;
        return ftp_class_lfs->new($self, $FName, $lpath, $session::UID);
    } else {
        return undef;
    }
}

1;

