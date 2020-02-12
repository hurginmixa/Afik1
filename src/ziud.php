<?php

include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("compose.inc.php");

require("db.inc.php");
ConnectToDB();


require("view.inc.php");

class myscreen extends view {

    function run()
    {
        global $TagFile, $PROGRAM_ZIUD;

        $bufsize = 40960;


        $f = @fopen("$PROGRAM_ZIUD/$TagFile", "r");
        if ($f <= 0) {
          echo "File not open !";
          return;
        }
        clearstatcache();
        $size = filesize("$PROGRAM_ZIUD/$TagFile");
        header("Content-Type: application/x-msdownload");
        header("Content-Length: $size");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=\"$TagFile\"");
        while(!feof($f)) {
            $buf = fread($f, $bufsize);
            echo $buf;
        }

        fclose($f);
    }


    function PageHeader() // overlaped virtual's function
    {
    }


    function PageFoot() // overlaped virtual's function
    {
    }

    function SetIdentificationCookies() // overlaped virtual's function
    {
    }


    function OpenSession() // overlaped virtual's function
    {
    }
}

// ShGlobals();

$s = new myscreen();
$s->run();

?>
