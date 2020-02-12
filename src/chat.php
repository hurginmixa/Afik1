<?php

include("_config.inc.php");
require("file.inc.php");
require("utils.inc.php");

require("db.inc.php");
ConnectToDB();

require("screen.inc.php");

class myscreen extends screen
{
  function myscreen()
  {
    $this->screen(); // inherited constyructor
    $this->PgTitle = "Chat";

    $this->Request_actions["sSend"]      = "Send()";
    $this->Request_actions["sClear"]     = "Clear()";
  }

  function Script()
  {
    screen::script();
    echo "<script language=\"JavaScript\">\n",
         "var Possible = 1;\n",
         "function Refresh()\n",
         "{\n",
         "  if (Possible) {\n",
         "    window.Form.submit();\n",
	 "  } else {\n",
	 "    self.status = \"Refresh time out. Send message.\";\n",
	 "  }\n",
         "}\n",
         "</script>\n";

  }

  function Scr()
  {
    Global $To, $Message, $Time;

    $To      = htmlspecialchars($To);
    $Message = htmlspecialchars($Message);

    $LTime   = array(10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60);
    if ($Time == "") {
      $Time = "60";
    }

    $this->out("<script language=\"JavaScript\">");
    $this->out("  window.setTimeout(\"Refresh()\", $Time"."000);");
    $this->out("</script>\n");

    $this->out("<form method='POST' name='Form'>");

    $this->out("<center>");
    $this->SubTable("border=1 cellpadding=0 cellspacing=0");
    $this->SubTable("cellpadding=3 cellspacing=1");
    $this->TDNext("class='tlp'");
    $this->out("To");
    $this->TDNext("class='tla'");
    $this->out("<input name='To' value=\"$To\" onFocus=\"Possible=0;\">");
    $this->TDNext("class='tlp'");
    $this->out("Refresh time");
    $this->TDNext("class='tla'");
    $this->out("<select name='Time'>");
    while (list($n, $v) = each($LTime)) {
      $this->out("<option value=$v".($v == $Time ? " selected" : "").">$v");
    }
    $this->out("</select>");
    $this->TRNext("");
    $this->TDNext("class='tlp'");
    $this->out("Message");
    $this->TDNext("class='tla'");
    $this->out("<input name='Message' value=\"$Message\" size=40 onFocus=\"Possible=0;\">");
    $this->SubTableDone();
    $this->SubTableDone();
    $this->out("<br>");

    $this->out("<input type='submit' name='sSend'    value='Send'>");
    $this->out("<input type='submit' name='sRefresh' value='Refresh'>");
    $this->out("<input type='submit' name='sClear'   value='Clear'>");
    $this->out("<input type='submit' name='sExit'    value='Exit'>");
    $this->out("</center>");
    $this->out("<hr>");

    $this->out("</form>");

    $r_chat = DBFind("chat", "1=1 order by send desc", "");
    $r_usr  = DBFind("chat", "usr.sysnumdomain = domain.sysnum and usr.sysnum in (select DISTINCT usrfrom from chat) order by send desc", "usr.sysnum, usr.name as usrname, domain.name as domname");
    while (!$r_chat->eof()) {
      if ($r_usr->Find("sysnum", $r_chat->usrfrom()) >= 0) {
        $this->out("<b>" . $r_usr->usrname() . "</b> :");
        $this->out(URLDecode($r_chat->Message())."<br>");
        $this->out("<font size='-2'>" . mkdatetime($r_chat->Send()) . "</font><br>");
      }

      $r_chat->Next();
    }
  }

  function Send()
  {
    Global $To, $Message;

    if ($Message == "") {
      return;
    }

    $Message_ = urlencode($Message);

    DBExec("insert into chat (usrfrom, domfrom, usrto, domto, message, send) values ($this->UID, ".$this->USR->sysnum().", 0, 0, '$Message_', 'now'::abstime)");

    $this->Clear();
  }

  function Clear()
  {
    Global $To, $Message;

    $Message = "";
  }

}


$s = new myscreen();
$s->run();


?>
