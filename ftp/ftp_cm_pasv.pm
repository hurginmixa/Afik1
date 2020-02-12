use strict 'vars';

sub cm_pasv($)
{
   PLog("ftp : Command PASV");

   CloseTransferConnection();

   PLog("ftp : PASV : LOCAL_SERVER - $main::LOCAL_SERVER");

   PLog("ftp : PASV : Create new TransferSock");
   $session::TransferSock = IO::Socket::INET->new(Type => SOCK_STREAM, Proto => "tcp", Listen => 1, LocalHost => $main::LOCAL_SERVER, ReuseAddr, MultiHomed);     # LocalAddr => $main::LOCAL_SERVER . ":0", LocalPort => 0,
   if (!defined($session::TransferSock)) {
       PLog("ftp : PASV : Create new TransferSock - failed : $!");
       $session::TransferMode = "undef";
       Send2Client(425, "Can't open passive connection : $!");
       return undef();
   }

   if (!($session::TransferSock->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1))) {
      PLog("ftp : PASV : Exit - setsockopt (SO_KEEPALIVE) - failed: $!");
      exit;
   }

   $session::TransferMode = "passv";


   my(@port) = unpack("C2", pack("n", $session::TransferSock->sockport()));
   my(@addr) = unpack("C4", $session::TransferSock->sockaddr());

   Send2Client(227, "Entering Passive Mode ($addr[0],$addr[1],$addr[2],$addr[3],$port[0],$port[1])");
}

1
