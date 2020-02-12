package sendmail;


use tools;
use POSIX;

sub sendmail(%)
{
        my ($Par) = @_;
        my ($S1) = "sendmail::Socket";
        my (@AddrList, $Header, $RightEMailAddr, $i);


        if ($Par->{SMTPServ} eq "") {
                $sendmail::Mes = "2.1 error not set name SMTP server";
                return 0;
        }

        if ($Par->{To} eq "") {
                $sendmail::Mes = "2.2 error not set TO address";
                return 0;
        }

        if ($Par->{From} eq "") {
                $sendmail::Mes = "2.3 error not set From address";
                return 0;
        }

        if ($Par->{Message} eq "") {
                $sendmail::Mes = "2.4 error not set Message";
                return 0;
        }

	#####################################################################

        if (!($Par->{SMTPServ} =~ /:\d$/)) {
                $Par->{SMTPServ} .= ":25";
        }

        $Par->{Message} =~ s/(\r\n|\n)\.(\r\n|\n)/(\r\n|\n)\ .(\r\n|\n)/gm;

        $Header = $Par->{Header};
        $Header .= "\r\n"                                          if ($Header !~ /(\r\n|\n)$/);
        $Header .= "To: " .           $Par->{To}   .       "\r\n"  if ($Header !~ /^To[ ]*:/im);
        $Header .= "From: " .         $Par->{From} .       "\r\n"  if ($Header !~ /^From[ ]*:/im);
        $Header .= "Subject: " .      $Par->{Subject} .    "\r\n"  if ($Header !~ /^Subject[ ]*:/im && $Par->{Subject} ne "");
        $Header .= "Content-Type: " . "text/plain".        "\r\n"  if ($Header !~ /^Content-Type[ ]*:/im);
        $Header .= "Message-Id: " .   MessId($Par->{To}) . "\r\n"  if ($Header !~ /^Message-Id:[ ]*:/im);
        $Header .= "Date: " .         MessDate() .         "\r\n"  if ($Header !~ /^Date:[ ]*:/im);
        ## print STDERR "Header $Header\n";

        @AddrList = split(",|;", $Par->{To});
        $RightEMailAddr = "[0-9A-Z\-\_\.\@]+";
        for ($i=0; $i <= $#AddrList; $i++) {
                if ($AddrList[$i] =~ /^[ ]*($RightEMailAddr)[ ]*$/i) {
                  $AddrList[$i] = $1;
                  next;
                }

                if ($AddrList[$i] =~ /\<[ ]*($RightEMailAddr)[ ]*\>[ ]*$/i) {
                  $AddrList[$i] = $1;
                  next;
                }

                if ($AddrList[$i] =~ /^[ ]*['"].*['"][ ]*($RightEMailAddr)[ ]*$/i) {
                  $AddrList[$i] = $1;
                  next;
                }

                $sendmail::Mes = "2.5 error error in TO's address component $AddrList[$i]";
                return 0;
        }

        #####################################################################

        if (!($addr = tools::PackAddr("$Par->{SMTPServ}"))) {
                $sendmail::Mes = "3 $tools::Mes";
                return 0;
        }

        if (!tools::OpenSocket($S1, "")) {
                $sendmail::Mes = "1 $tools::Mes";
                close $S1;
                return 0;
        }

        if (!connect($S1, $addr)) {
                $sendmail::Mes = "4 error with connect : $!";
                close $S1;
                return 0;
        }

        #####################################################################

        if (!($line = <$S1>)) {
                $sendmail::Mes = "5.1 error with read LOGIN from server : $!";
                close $S1;
                return 0;
        }
        ## print "$line\n";
        if (!($line =~ /^2\d\d/)) {
                $sendmail::Mes = "5.2 error with LOGIN : $line";
                close $S1;
                return 0;
        }

        if ($Par->{Domain} ne "") {
                print $S1 "HELO ", $Par->{Domain}, "\r\n";
                if (!($line = <$S1>)) {
                        $sendmail::Mes = "6.1 error with read HELO from server : $!";
                        close $S1;
                        return 0;
                }
                ## print "$line\n";
                if (!($line =~ /^2\d\d/)) {
                        $sendmail::Mes = "6.2 error with HELO : $line";
                        close $S1;
                        return 0;
                }
        }


        for ($i=0; $i <= $#AddrList; $i++) {
                print $S1 "MAIL FROM: ", $Par->{From}, "\r\n";
                if (!($line = <$S1>)) {
                        $sendmail::Mes = "8.1 error with read MAIL FROM from server : $!";
                        close $S1;
                        return 0;
                }
                ## print "$line\n";
                if (!($line =~ /^2\d\d/)) {
                        $sendmail::Mes = "8.2 error with MAIL FROM : $line";
                        close $S1;
                        return 0;
                }

                print $S1 "RCPT TO: ", $AddrList[$i], "\r\n";
                if (!($line = <$S1>)) {
                        $sendmail::Mes = "7.1 error with read RCPT TO from server : $!";
                        close $S1;
                        return 0;
                }
                ## print "$line\n";
                if (!($line =~ /^2\d\d/)) {
                        $sendmail::Mes = "7.2 error with RCPT TO : $line";
                        close $S1;
                        return 0;
                }

                print $S1 "DATA\r\n";
                if (!($line = <$S1>)) {
                        $sendmail::Mes = "8.1 error with read DATA from server : $!";
                        close $S1;
                        return 0;
                }
                ## print "$line\n";
                if (!($line =~ /^(2|3)\d\d/)) {
                        $sendmail::Mes = "8.2 error with DATA : $line";
                        close $S1;
                        return 0;
                }

                print $S1 $Header, "\r\n", $Par->{Message}, "\r\n.\r\n";
                if (!($line = <$S1>)) {
                        $sendmail::Mes = "9.1 error with read MESSAGE ACCEPT from server : $!";
                        close $S1;
                        return 0;
                }
                ## print "$line\n";
                if (!($line =~ /^2\d\d/)) {
                        $sendmail::Mes = "9.2 error with MESSAGE ACCEPT : $line";
                        close $S1;
                        return 0;
                }
        }

        print $S1 "QUIT\r\n";
        if (!($line = <$S1>)) {
                $sendmail::Mes = "10.1 error with read QUIT from server : $!";
                close $S1;
                return 0;
        }
        ## print "$line\n";
        if (!($line =~ /^(2|3)\d\d/)) {
                $sendmail::Mes = "10.2 error with QUIT : $line";
                close $S1;
                return 0;
        }

        close $S1;

        $sendmail::Mes = "";
        return 1;
}


sub MessDate()
{
    my($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime();
    return strftime("%a, %d %b %Y %H:%M:%S %z", $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst);
}


sub MessId($)
{
    my($Addr) = @_;
    my($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime();
    my($res) = strftime("%Y%m%d%H%M%S", $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst);

    $res .= "XXX" . md5($res . $Addr) . $Addr;
    $res = "<$res>";

    return $res;
}

1
