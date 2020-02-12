use strict 'vars';

use utils;

sub cm_list($$)
{
   my ($path, $nlst) = @_;

   if ($nlst) {
     PLog("ftp : Command NLST");
   } else {
     PLog("ftp : Command LIST");
   }

   my ($parm);
   my ($fulltime) = 0;

   if ($session::TransferMode eq "undef") {
      Send2Client(500, "Don't previous command PASV or PORT");
      return undef();
   }

   PLog("ftp : List - Get TransferConnection()");
   if (!TransferConnection()) {
      Send2Client(500, "Inposible Connect");
      return undef();
   }

   if ($path =~ /^-(\S+)\s*/) {
     $path = $';
     $parm = uc $1;
   } else {
     $parm = "";
   }

   if ($parm eq "FULLTIME") {
     $fulltime = 1;
   }

   $path = "." if ( $path eq "" );

   PLog("ftp : List - Call GetFS() path '$path'");
   my($FsNode) = GetFS($path);

   if (!defined($FsNode)) {
     #CloseTransferConnection();
     Send2Client(550, "LIST command has error : " . $util::Mes . ".");
     return undef;
   }

   Send2Client(150, "LIST data for '" . $FsNode->path() . "'.");

   my(@r) = $FsNode->listSubEntry();

   if ($nlst) {
       foreach (@r) {
         printf $session::TransferStream "%-s\r\n",
                     $_->{name};
       }
   } else {
       print $session::TransferStream "total " . ($#r + 1) . "\r\n" ;
       foreach (@r) {
         printf $session::TransferStream "%srw-------    1 %-8s %-8s %8s %s %-s\r\n",
                     $_->{sysnumfile},
                     $_->{owner},
                     $_->{owner},
                     $_->{fsize},
                     SQLDate($_->{creat}, $fulltime),
                     $_->{name};
       }
   }

   if ($session::CloseTransferStream) {
      CloseTransferConnection();
   }

   Send2Client(226, "Transfer complete.");

   return 1;
}

1;
