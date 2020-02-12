use strict 'vars';


sub cm_cwd($)
{
   my ($PATH) = $_[0];
   PLog("ftp : Command CWD");

   if ($PATH eq "") {
      Send2Client(500, "Missing File Name");
      #CloseTransferMode();
      return undef();
   }

   my($FsNode) = GetFS($PATH);

   if (!defined($FsNode)) {
     Send2Client(550, "CWD command has error : " . $util::Mes . ".");
     return 0;
   }

   if (!$FsNode->isFolder()) {
     Send2Client(550, "CWD command has error : '/" . $FsNode->path() . "' not a directory.");
     return 0;
   }

   $session::WDNode = $FsNode;

   Send2Client(250, "CWD command successful.");
   return 1;
}

1;
