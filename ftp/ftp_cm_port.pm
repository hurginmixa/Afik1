use strict 'vars';


sub cm_port($)
{
   PLog("ftp : Command PORT");

   CloseTransferConnection();

   my(@arraddr) = split(',', $_[0]);

   if ($#arraddr != 5) {
       Send2Client(504, "Invalid Port definitions");
       return undef;
   }

   my($remaddr) = $arraddr[0] . "." . $arraddr[1] . "." . $arraddr[2] . "." . $arraddr[3] . ":" . ($arraddr[4] * 256 + $arraddr[5]);
   $session::TransferMode = $remaddr;

   Send2Client(200, "Set port Ok");

   return 1;
}

1
