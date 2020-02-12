#!/usr/bin/perl

use utils;
use tools;
use db_pg;
use sendmail;
use strict 'vars';

#use File::Copy;

#use Fcntl;

use readconf;

$main::BufferSize = 1024 * 50;

sub main()
{
    my($UNI) = "";
    if (defined($ENV{QUERY_STRING}) && $ENV{QUERY_STRING} =~ /UNI=([^&]*)/i) {
        $UNI = tools::Decode($1);
    }
    my($StatusFileName) = "/tmp/wwwupld_stat_" . $UNI;


    print "Cache-Control: no-cache, no-store\r\n";
    print "Content-Type: text/html\r\n\r\n";

    print "
                <HTML>
                <title>Upload file</title>
          ";

    if(open(STYLE, "$main::PROGRAM_SRC/standard.css")) {
        print(join("", <STYLE>));
        close(STYLE);
    }

    print "
                </head>
                <BODY class='body'><hr>
          ";

    #  print "
    #              $ENV{QUERY_STRING}<hr>
    #              $StatusFileName<hr>
    #        ";


    if ($UNI =~ /^[a-zA-Z0-9]+$/) {
        my($s) = "";
        if (open(MES, $StatusFileName)) {
            $s = <MES>;
            close(MES);
            print "<table width='100%' cellpading='5'><tr><td align='center' class='title'><font size='+1'>$s</font></td></tr></table>";
        } else {
            print "&nbsp;";
        }
        #print "<font size='-2'>", $UNI, "</font>";

        if ($s ne "") {
            print "
                    <script language='javascript'>
                        setTimeout(\"window.parent.frames.prbar.location = window.parent.frames.prbar.location;\", 500);
                    </script>
            ";
        } else {
            print "
                    <script language='javascript'>
                        setTimeout(\"window.parent.frames.prbar.location = window.parent.frames.prbar.location;\", 500);
                    </script>
            ";
        }
    }


    print "
                </BODY>
                </HTML>
          ";
}

main();
