use strict 'vars';

sub cm_rename($)
{
   my ($path1) = @_;
   my ($FsNode1, $fs1, $name1, $filesysnum1);
   my ($path2);
   my ($FsNode2, $fs2, $name2, $filesysnum2);
   my ($r);

   PLog("ftp : Command RNFR");

   if ($path1 eq "") {
      Send2Client(500, "Missing File or Folder Name");
      return undef();
   }

   $FsNode1 = GetFS($path1);
   if (!defined($FsNode1)) {
     Send2Client(550, "REFR command has error : " . $util::Mes . ".");
     return undef;
   }
   PLog("ftp : src name '" . $FsNode1->path() . "'");

   Send2Client(350, "File exists, ready for destination name");

#=========================================================================================
   $path2 = ReadClientText();

   if (!($path2 =~ /RNTO[ ]+/i)) {
      Send2Client(500, "Rename Failed. No RNTO presend");
      return undef;
   }
   $path2 = $';
   PLog("ftp : destination path : '$path2'");

   if ($path2 eq "") {
      Send2Client(500, "Missing Destination Path");
      return undef();
   }

   if ( !($path2 =~ /[^\\\/]+$/) ) {
     Send2Client(550, "RNTO command has error : Destination file name not set.");
     return undef;
   }

   my($FName) = $&;
   $path2 = $`;
   $path2 =~ s/[\\\/]+$//;
   $path2 = "." if ($path2 eq "");

   $FsNode2 = GetFS($path2);
   if (!defined($FsNode2)) {
     Send2Client(550, "RNTO command has error : " . $util::Mes . ".");
     return undef;
   }

   PLog("ftp : src name '" . $FsNode2->path() . "'");

   if ( !$FsNode2->isFolder()) {
       Send2Client(504, "Path '" . $FsNode2->path() . "' is not folder");
       return undef;
   }

   if ( $FsNode2->isReadonly()) {
       Send2Client(504, "Path read only");
       return undef;
   }

   $r = $FsNode2->getSubEntry($FName);
   if ( defined($r) ) {
       Send2Client(504, "Target path exist.");
       return undef;
   }

   if ( $FsNode1->parent()->path() ne $FsNode2->path()) {
       Send2Client(504, "Moving not implemented. Renaming only.");
       return undef;
   }

   if ( !$FsNode2->renameSubEntry($FsNode1->fileName(), $FName) ) {
       Send2Client(550, "RENAME command has error : " . $FsNode2->{LASTERROR} . ".");
       return undef;
   }

   Send2Client(250, "RNTO command successful.");

   return 1;
}

1;
