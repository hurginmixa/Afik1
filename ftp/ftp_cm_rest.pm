use strict 'vars';

sub cm_rest($)
{
   my ($num) = @_;
   PLog("ftp : Command REST");

   if (!($num =~ /^[0-9]+$/)) {
      Send2Client(500, "'REST $num': command not understood.");
      return undef();
   }

   $session::RestartPoint = $num;
   Send2Client(350, "Restarting at $session::RestartPoint. Send STORE or RETRIEVE to initiate transfer.");

   return 1;
}

1;

