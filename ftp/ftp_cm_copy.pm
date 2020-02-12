use strict 'vars';

sub cm_copy($)
{
   my ($path1) = @_;
   my ($FsNode1, $fs1, $name1, $LocalFSName1);
   my ($path2);
   my ($FsNode2, $fs2, $name2);
   my ($r);

   PLog("ftp : Command CPFR");

   if ($path1 eq "") {
      Send2Client(500, "Missing File or Folder Name");
      return undef();
   }

   $FsNode1 = GetFS($path1);
   if (!defined($FsNode1)) {
     Send2Client(550, "CPFR command has error : " . $util::Mes . ".");
     return undef;
   }
   PLog("ftp : src name '" . $FsNode1->path() . "'");

   Send2Client(350, "File exists, ready for destination name");

#=========================================================================================
   $path2 = ReadClientText();

   if (!($path2 =~ /CPTO[ ]+/i)) {
      Send2Client(500, "COPY Failed. No CPTO presend");
      return undef;
   }
   $path2 = $';
   PLog("ftp : destination path : '$path2'");

   if ($path2 eq "") {
      Send2Client(500, "Missing Destination Path");
      return undef();
   }

   if ( !($path2 =~ /[^\\\/]+$/) ) {
     Send2Client(550, "CPTO command has error : Destination file name not set.");
     return undef;
   }

   my($FName) = $&;
   $path2 = $`;
   $path2 =~ s/[\\\/]+$//;
   $path2 = "." if ($path2 eq "");

   $FsNode2 = GetFS($path2);
   if (!defined($FsNode2)) {
     Send2Client(550, "CPTO command has error : " . $util::Mes . ".");
     return undef;
   }

   PLog("ftp : dst name '" . $FsNode2->path() . "'");

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

   if ( $FsNode1->path() eq $FsNode2->path() . "/$FName") {
       Send2Client(504, "Cannot copy the file onto itself.");
       return undef;
   }

   if ( defined($LocalFSName1 = $FsNode1->getLocalFileName()) && !$FsNode2->insertSubEntry($FName, $LocalFSName1) ) {
       Send2Client(550, "COPY command has error : " . $FsNode2->{LASTERROR} . ".");
       return undef;
   }

   Send2Client(250, "CPTO command successful.");

   return 1;
}

1;
