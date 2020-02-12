<?php

require("view.inc.php");

class myscreen extends view {

        function HTML_Body()
        {
            global $FS, $FACE;
            global $INET_CGI;

            $uni = MD5(microtime() . " " . GetMyPID() . $FS . " " . $this->UID . " " . rand());

            echo "<FRAMESET rows='80%, *' border=0>\n";
            echo "<FRAME name='main'  src='$INET_CGI/wwwupld.pl?UID=$this->UID&FS=$FS&Key=$this->Key&FACE=$FACE&uni=$uni' scrolling = 'no'>\n";
            echo "<FRAME name='prbar' src='$INET_CGI/wwwupld_mes.pl?UID=$this->UID&FS=$FS&Key=$this->Key&FACE=$FACE' scrolling = 'no'>\n";
            echo "<NOFRAMES>\n";
            echo "</NOFRAMES>\n";
            echo "</FRAMESET>\n";
        }

        function Script()
        {
            view::script();

            echo "<script language='javascript'>\n";
            echo "  function Reloadd(UploadFS)\n";
            echo "  {\n";
            echo "    //window.alert(UploadFS);\n";
            echo "    if (window.opener != null) {\n";
            echo "         if (window.location.search.indexOf(\"AttForm=\")  != -1) {\n";
            echo "           window.opener.document.AttForm.sNewUpld.value=UploadFS;\n";
            echo "           window.opener.document.AttForm.submit();\n";
            echo "         } else if (window.location.search.indexOf(\"ComposeForm=\")  != -1) {\n";
            echo "           window.opener.refreshAfterUpload(UploadFS);\n";
            echo "         } else {\n";
            echo "           window.opener.location = window.opener.location;\n";
            echo "         }\n";
            echo "    }\n";
            echo "    window.close(1);\n";
            echo "  }\n";
            echo "</script>\n";
        }

}

ConnectToDB();

$s = new myscreen();
$s->run();

UnconnectFromDB();

?>
