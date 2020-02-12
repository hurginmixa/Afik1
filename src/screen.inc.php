<?php

/*
class screen extends view {
        $PgTitle  = "No title"
        $logoimg = ""
        $WidthTools = 0
        $Referens = array()
        $WinTitle = "Afik1 system"

        function screen ()
        function Styles()
	function display()
        function Logo()
	function UserName()
        function Title()
	function Mes()
	function Referens()
        function Referen($NAME, $WIDTH, $URI, $URI_ADD, $ALT)
    function ToolsBar()
	function Tools()
	function Scr()
        function SetTempl($content)
*/


if(!isset($_SCREEN_INC_)) {


$_SCREEN_INC_=0;

include("_config.inc.php");
require("tools.inc.php");
require("view.inc.php");



class screen extends view {
        var $PgTitle  = "No title",             // title of page
            $logoimg = "",
            $WidthTools = 0,
            $ButtonBlank  = "&nbsp;&nbsp;",
            $TextShift    = "&nbsp;&nbsp;",
            $SectionBlank = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
            $Referens = array();


    function screen() // Constructor
    {
        $this->view(); // inherited constructor
        $this->SetTempl("screen");
    }


    function display()
	{
        global $INET_IMG;

        $this->tableopt("width='100%' cellspacing=0 cellpadding=0 border=0");
        $this->outs("align='center'" , "");

        $this->DisplayHeader();

        $this->Referens();

        $this->tds(4, 0, "valign='top' colspan=3", ""); {
            $this->Mes();
        }

        $this->SubTable("border=0 width='100%' cellspacing=0 cellpadding=0 class='body'"); {
            $this->out("<img src='$INET_IMG/filler3x1.gif' border=0>");
        } $this->SubTableDone();

        $this->SubTable("border=0 width = '100%' cellspacing=0 cellpadding=0"); {
            $this->tds(0, 0, "colspan=3", ""); {
                $this->ToolsBar();
            }
            if ((int)$this->WidthTools != 0) {
                $this->tds(1, 0, "width='".$this->WidthTools."%' valign='top' rowspan=2", ""); {
                    $this->Tools();
                }
                $this->tds(1, 1, "valign='top' nowrap", ""); {
                    $this->out("<img src='$INET_IMG/filler1x3.gif'>");
                }
            }
            $this->tds(1, 2, "width='".(100 - $this->WidthTools)."%' valign='top'", ""); {
                $this->Scr();
            }
        } $this->SubTableDone();
    }


    function DisplayHeader()
    {
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' cellspacing=0 cellpadding=4 class='title'"); {

            $this->tds(1, 0, "valign='middle' align='right' class='title' width='10px' rowspan=2", ""); {
                $this->out("<img src='$INET_IMG/filler1x10.gif' border=0>");
            }

            $this->tds(1, 1, "valign='middle' class='title'", ""); {
                $this->out("<font class='title' style='font-size: 20px'>");
                $this->Title();
                $this->out("</font>");
            }

            $this->tds(1, 2, "valign='middle' align='right' class='title' width='118px' rowspan=2", ""); {
                //$this->out("&nbsp;");
                $this->Logo();
            }

            $this->tds(3, 0, "valign='middle' align='left' class='title'", ""); { //
                $this->out("<font class='title'>");
                $this->UserName();
                $this->out("</font><br><img src='$INET_IMG/filler3x1.gif' border=0>");
            }

        } $this->SubTableDone();
    }


    function Logo()
    {
        global $INET_IMG;

         #$filename = '$INET_IMG/?FACE=$FACE";
         #
         #if (file_exists($filename)) {
         #$this->out("<img src='$INET_IMG/afik-logo.gif' >");
         #$this->out("<img src='$INET_IMG/$filename' alt='Logo'>");
         #
         #} else {
          $this->out("<img src='$INET_IMG/afik-logo.gif' >");
         # $this->out("<img src='$INET_IMG/en.gif ' alt='Logo'>");

         # }
	}


	function UserName()
	{
        global $TEMPL;

        $text = "$TEMPL[sc_username] :<b>&nbsp;" .  $this->USRNAME . "</b>";

        $r_usr_ua = DBExec("SELECT usr.creat + '30 days'::interval - 'now'::abstime as lefttime from usr left join usr_ua on usr.sysnum = usr_ua.sysnumusr and usr_ua.name = 'usertype' where usr.sysnum = $this->UID and usr_ua.value = 'Trial'", __LINE__);
        if ($r_usr_ua->NumRows() != 0) {
            $interval = $r_usr_ua->lefttime();

            if (preg_match("/^\s*([\d-]+) days?/", $interval, $MATH)) {
                $interval = $MATH[1];
            } else {
                $interval = 0;
            }

            $text .= "&nbsp;&nbsp;<span style='color:red'>" . sprintf($TEMPL[sc_trial_time_left], $interval + 1) . "</span>";
        }

        $this->out($text);
	}


    function Title()
	{
        $this->out($this->PgTitle);
	}


	function Mes()
	{
        // $this->out("&nbsp");
	}

	function Referens()
	{
        global $INET_SRC, $INET_IMG, $TEMPL, $FACE;

        $r_fld = DBFind("fld", "sysnumusr='$this->UID' and (ftype = 1) order by sysnum", "", "file: " . __FILE__ . " line " . __LINE__);
        $width = (int)(100 / 6);

        //$this->SubTable("border=0 width='100%' cellspacing=0 cellpadding=2 class='toolsbarl' rules='none'");
        $this->SubTable("border=0 width='100%' cellspacing=0 cellpadding=0 class='body'"); {
            $this->out("<img src='$INET_IMG/filler3x1.gif' border=0>");
        } $this->SubTableDone();

        $this->SubTable("border=0 width='100%' cellspacing=0 cellpadding=1 class='body' rules='none' grborder");

        $this->Referen($TEMPL[sc_welcome],    $width + 1, "$INET_SRC/welcome.php?UID=$this->UID&FACE=$FACE",                          "",              $TEMPL[sc_welcome_ico]);
        $this->Referen($TEMPL[sc_ftp_myfile],  $width,     "$INET_SRC/file_folder.php?UID=$this->UID&FACE=$FACE",                       "&sNewView=on",  $TEMPL[sc_ftp_myfile_ico]);
        $this->Referen($TEMPL[sc_ftp_frfile],  $width,     "$INET_SRC/access_folder.php?UID=$this->UID&Key=&FACE=$FACE",                "",              $TEMPL[sc_ftp_frfile_ico]);

        for($r_fld->set(0); !$r_fld->eof(); $r_fld->next()) {
            $this->Referen($TEMPL[sc_inbox],       $width,     "$INET_SRC/mail_folder.php?UID=$this->UID&FACE=$FACE&Fld=".$r_fld->sysnum(), "&sNewView=on",  $TEMPL[sc_inbox_ico]);
        }

        $this->Referen($TEMPL[sc_compose],     $width,     "$INET_SRC/compose.php?UID=$this->UID&FACE=$FACE",                           "&sNewView=on",  $TEMPL[sc_compose_ico]);
        //$this->Referen($TEMPL[sc_address],     $width,     "$INET_SRC/address.php?UID=$this->UID&FACE=$FACE",                           "&sNewView=on",  $TEMPL[sc_address_ico]);
        $this->Referen($TEMPL[sc_logout],      $width + 1, "$INET_SRC/logout.php?UID=$this->UID&FACE=$FACE",                            "",              $TEMPL[sc_logout_ico]);

        $this->SubTableDone();
        //$this->SubTableDone();
        // $this->out("Referens");
        unset($r_fld);
	}


    function Referen($NAME, $WIDTH, $URI, $URI_ADD, $ALT)
	{
        global $REQUEST_URI, $INET_SRC;
        $r = 0;

        if ($URI != "") {
            $URI1 = substr($URI, strlen($INET_SRC) + 1);

            $p = strpos($REQUEST_URI, $URI1);
            if ($p) {
                if ($p + strlen($URI1) == strlen($REQUEST_URI)) {
                    $r = 1;
                } else {
                    if (substr($REQUEST_URI, $p + strlen($URI1), 1) == '&') {
                        $r = 1;
                    }
                }
            }
        }

        if ($r) {
            $this->TDNext("width=\"$WIDTH%\" nowrap class=\"ra\" title=\"$ALT\"");
            $this->Out("<a href=\"$URI$URI_ADD\" alt=\"$ALT\" class=\"ra\"><font class=\"ra\">$NAME</font></a>");
        } else {
            $this->TDNext("width=\"$WIDTH%\" nowrap class=\"rp\" title=\"$ALT\"");
            $this->Out("<a href=\"$URI$URI_ADD\" alt=\"$ALT\" class=\"rp\"><font class=\"rp\">$NAME</font></a>");
        }
	}


    function SetTempl($content)
    {
        global $PROGRAM_LANG, $FACE;
        global $HTTP_SERVER_VARS;
        global $TEMPL, $TEMPL_CHARSET;

        if ($content == "") {
            return;
        }

        if ($FACE == "") {
            $FACE = $HTTP_SERVER_VARS[HTTP_ACCEPT_LANGUAGE];
        }

        if ($FACE == "") {
            $FACE = "en_US";
        }


        $TEMPL_CHARSET = "ISO-8859-1";
        // $TEMPL         = array();

        #==================================================================================

        $a = @implode("", file($PROGRAM_LANG . "/en/charset"));

        $a = preg_replace("/\r?\n.*/s", "", $a);
        if ($a != "") {
            $TEMPL_CHARSET = $a;
        }

        $a = @implode("", file($PROGRAM_LANG . "/en/" . $content . ".txt"));
        $a = eregi_replace("\r", "", $a);

        if (preg_match_all("'^[ ]*([a-z0-9][^ =]+?)[ ]*=[ ]*(.*?)[ ]*$'ism", $a, $find)) {
            while (list($n, $v) = each($find[0])) {
                $TEMPL[$find[1][$n]] = preg_replace("'(?<!\\\\)(\\\$([a-z0-9_]+))'ise", "read_config_callback('\\2')", $find[2][$n]);
            }
        }

        #==================================================================================

        $a = @implode("", file($PROGRAM_LANG . "/" . $FACE . "/charset"));
        $a = preg_replace("/\r?\n.*/s", "", $a);
        if ($a != "") {
            $TEMPL_CHARSET = $a;
        }

        $a = @implode("", file($PROGRAM_LANG . "/" . $FACE . "/" . $content . ".txt"));
        $a = eregi_replace("\r", "", $a);
        if ($a == "") {
            return;
        }

        //$this->out("=$a=<br>");
        if (preg_match_all("'^[ ]*([a-z0-9][^ =]+?)[ ]*=[ ]*(.*?)[ ]*$'ism", $a, $find)) {
            while (list($n, $v) = each($find[0])) {
                $TEMPL[$find[1][$n]] = preg_replace("'(?<!\\\\)(\\\$([a-z0-9_]+))'ise", "read_config_callback('\\2')", $find[2][$n]);
            }
        }
    }

    function HTTP_Header_ContentType() // inherited overlaped function
    {
        global $TEMPL_CHARSET, $FACE;
        //echo $TEMPL_CHARSET; exit;

        //if ($FACE != "en") {
        if ($TEMPL_CHARSET != "") {
            header("Content-Type: text/html; charset=$TEMPL_CHARSET");
        }
    }


    function copyright()
    {
        global $INET_IMG, $TEMPL, $VERSION, $FACE, $FEEDBACK_EMAIL;
        global $INET_HELP;

        echo "<br>\n";
        echo "<table class='body' align='left' border='0' width='100%'>\n"; {
            echo "<tr>\n"; {
                echo "<td width='90%'>"; {
                    echo "<font class='body' size='-1'>"; {
                        echo "<a href='$INET_HELP/Copyright.htm' target='_BLANK' class='body'>$TEMPL[sc_copyright]<br>$TEMPL[sc_reserved]</a><br>$TEMPL[sc_version]&nbsp;$VERSION";
                    } echo "</font>";
                } echo "</td>";
            } echo "</tr>\n";
            if ($this->Key == "") {
                echo "<tr>\n"; {
                    echo "<td width='90%'>\n"; {
                        echo "<font class='body' size='-1'>"; {
                            //echo "<a href='$INET_SRC/compose.php?UID=$this->UID&FACE=$FACE&sNewView=on&To=" . urlencode($FEEDBACK_EMAIL) . "' class='body'>$TEMPL[sc_feedback]</a>";
                            echo "<a href='" . $this->GetFeedBackEmailLink() . "' class='body'>$TEMPL[sc_feedback]</a>";
                        } echo "</font>";
                    } echo "</td>\n";
                } echo "</tr>\n";
            }
        } echo "</table>\n";
    }

    function GetFeedBackEmailLink()
    {
        global $INET_IMG, $TEMPL, $VERSION, $FACE, $FEEDBACK_EMAIL;
        global $INET_HELP;

        return "$INET_SRC/compose.php?UID=$this->UID&FACE=$FACE&sNewView=on&To=" . urlencode($FEEDBACK_EMAIL);
    }


	function ToolsBar()
	{
              // $this->out("ToolsBar");
	}

	function Tools()
	{
              // $this->outs("", "Tools");
	}

	function Scr()
	{
              // $this->out("Scr");
	}


}

} // $_SCREEN_INC;

?>
