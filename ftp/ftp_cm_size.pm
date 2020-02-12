use strict 'vars';

sub cm_size($)
{
   my ($path) = @_;
   my ($r, $FsNode);

   PLog("ftp : Command SIZE");

   if ($path eq "") {
      Send2Client(500, "Missing File Name");
      return undef();
   }

   $FsNode = GetFS($path);
   if (!defined($FsNode)) {
     Send2Client(550, "SIZE command has error : " . $util::Mes . ".");
     return undef;
   }

   if ($FsNode->isFolder()) {
     Send2Client(551, "SIZE command has error : Path '/" . $FsNode->path() . "' not a plain file.");
     return undef;
   }

   $r = $FsNode->size();
   if (!defined($r)) {
     Send2Client(552, "SIZE command has error : Path '/" . $FsNode->path() . "' No such file or directory.");
     return undef;
   }

   Send2Client(213, $r);
}
1;
