<?php

include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("screen.inc.php");

require("db.inc.php");
ConnectToDB();


require("view.inc.php");

class CMessageView extends screen
{
    function CMessageView()
    {
        global $Fld, $Msg;
        global $TEMPL;

        if(!ereg("^[0-9]+$", $Msg)) {
            $Msg = 0;
        }

        if(!ereg("^[0-9]+$", $Fld)) {
            $Fld = 0;
        }

        screen::screen();     // inherited constructor
        $this->SetTempl("mail_folder");
    }


    function Run()
    {
        $this->Display();
    }


    function Display()
    {
        global $Fld, $Msg, $View;
        global $TEMPL, $INET_SRC, $FACE;

        $r = DBExec("SELECT msg.* FROM msg, fld WHERE msg.sysnumfld = fld.sysnum AND fld.sysnum = '$Fld' AND fld.sysnumusr = '$this->UID' AND msg.sysnum = '$Msg'", __LINE__);
        if ($r->NumRows() != 1) {
            header("Content-Type: text/plain");
            echo "Message not found ! $Fld , $Msg";
            return;
        }

        $HTTPContentType = "Content-Type: TEXT/HTML";

        $CharSet = "";
        if ($r->charset() != "") {
            $CharSet = $r->charset();
            $CharSet = preg_replace("/(^\")|(\"$)/", "", $CharSet);
            $HTTPContentType .= "; charset=$CharSet";
        }

        //header("Content-Type: TEXT/HTML");
        header($HTTPContentType);

        $title = "";
        if ($View == "Print") {
            $title .= "<tr style='background-color:#D4D0C8'><td width='10%' nowrap valign='top'>&nbsp;{$TEMPL[lb_to]}&nbsp;</td><td width='90%' valign='top'>" . htmlspecialchars(URLDecode($r->addrto())) . "</td></tr>\n";
            $title .= "<tr style='background-color:#E4E0D8'><td width='10%' nowrap valign='top'>&nbsp;{$TEMPL[lb_from]}&nbsp;</td><td width='90%' valign='top'>" . htmlspecialchars(URLDecode($r->addrfrom())) . "</td></tr>\n";
            $title .= "<tr style='background-color:#D4D0C8'><td width='10%' nowrap valign='top'>&nbsp;{$TEMPL[lb_subject]}&nbsp;</td><td width='90%' valign='top'>" . htmlspecialchars(URLDecode($r->subj())) . "</td></tr>\n";
            $title .= "<tr style='background-color:#E4E0D8'><td width='10%' nowrap valign='top'>&nbsp;{$TEMPL[lb_date]}&nbsp;</td><td width='90%' valign='top'>" . htmlspecialchars(mkdatetime($r->send())) . "</td></tr>\n";
            $title .= "<tr style='background-color:#D4D0C8'><td width='10%' nowrap valign='top'>&nbsp;{$TEMPL[lb_date]}&nbsp;</td><td width='90%' valign='top'>" . htmlspecialchars($CharSet) . "</td></tr>\n";
            $title = "<table border=0 width='100%'>\n$title</table><br>\n";
            $title = "<a href='$INET_SRC/mail_folder.php?UID=$this->UID&FACE=$FACE&Fld=$Fld&Msg=$Msg'>" . $TEMPL[lb_return_to_mes] . "</a><br>\n" . $title;
        }

        if ($r->Content() == "TEXT/HTML") {
            $Body = ParseMesHTML(URLDecode($this->GetBodyMsg($Msg)), $this->UID);

            if (preg_match("/^(.*?<\s*body(\s+[^>]*)*>)(.*)/si", $Body, $MATH)) {
                $Body = $MATH[1] . $title . $MATH[3];
            } else {
                $Body = $title . $Body;
            }

            echo $Body;
        } else {
            echo "<html>\n";
            echo "<head>\n";
            echo "<title>" . htmlspecialchars(urldecode($r->subj())) . "</title>\n";
            echo "</head>\n";
            echo "<body>\n";
            echo $title;
            echo ParseMesText(URLDecode($this->GetBodyMsg($Msg)), $this->UID);
            echo "</body>\n";
            echo "</html>\n";
        }
    }


    function GetBodyMsg($sysnummsg)
    {
        $ret = "";
        $r_msgbody = DBFind("msgbody", "sysnummsg='$sysnummsg' order by sysnum", "");
        // Debug("body ".$r_msgbody->NumRows());
        while( !$r_msgbody->Eof()) {
            $ret .= $r_msgbody->body();
            $r_msgbody->Next();
        }

        //$ret .= "<hr>";

        return $ret;
    }

} // end class CMessageView

$MessageView = new CMessageView();
$MessageView->run();

exit;

?>
