use strict 'vars';

sub cm_dele($)
{
   my ($path) = @_;

   PLog("ftp : Command DELE");

   if ($path eq "") {
      Send2Client(500, "Missing File Name");
      CloseTransferMode();
      return undef();
   }

   if ( !($path =~ /[^\\\/]+$/) ) {
     Send2Client(550, "DELE command has error : File name not set.");
     return undef;
   }

   my($FName) = $&;
   $path = $`;
   $path =~ s/[\\\/]+$//;
   $path = "." if ($path eq "");

   my($FsNode) = GetFS($path, 1);
   if (!defined($FsNode)) {
     Send2Client(550, "DELE command has error : " . $util::Mes . ".");
     return undef;
   }

   if(!$FsNode->deleteSubEntry($FName)) {
     Send2Client(550, "DELE command has error : " . $FsNode->{LASTERROR} . ".");
     return undef;
   }

   Send2Client(250, "DELE command successful.");
}

1;
