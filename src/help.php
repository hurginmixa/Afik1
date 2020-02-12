<?php

include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("compose.inc.php");

require("db.inc.php");
ConnectToDB();


require("view.inc.php");

class myscreen extends screen {
        function display()
        {
          global $INET_HELP;
          $this->tableopt("width='466px' height='310px' cellspacing=0 cellpadding=0 border=0");
          $this->out("<CENTER>");
          $this->SubTable("width='100%' border='1'");
                $this->outs("valign='top' align='center' class='toolsbarl'", "");
                $this->out("<br>");
                        // $this->SubTable("border='1'");
                        #$this->out("<IFRAME id = 'help_frame_inside' width='400px' height='230px' src='$INET_HELP/file_folder.html'>");
                        $this->out("<IFRAME id = 'help_frame_inside' width='100%' height='247px' src='$INET_HELP/file_folder.html'>");
                        $this->out("Need upgrade yours browser");
                        $this->out("</IFRAME>");
                        // $this->SubTableDone();
                $this->out("<br><br>");
                $this->out("<input type='button' onclick=\"javascript:hiddeHelp()\" value='Close'>");
                $this->out("<br><br>");
          $this->SubTableDone();
          $this->out("</CENTER>");
        }

        function HTML_Body()
        {
          echo "<body class='body' TOPMARGIN=0 LEFTMARGIN=0'>";
          $this->display();
          $this->Show();
          echo "</body>\n";
        }

        function script()
        {
            global $INET_SRC;
            screen::script();
            echo "<script language='javascript' src='$INET_SRC/help.js'></script>\n";
        }

        function Authorize()
        {
        }

}

// ShGlobals();

$s = new myscreen();
$s->run();

?>
