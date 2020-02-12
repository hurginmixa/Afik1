<?php

/*
class view extends stable {
        $UID,                               // User Identification
        $Key,                               // User key for remote_access
        $DOMAIN,
        $USR,
        $URI;
        $Request_actions = array();
        $StartObjectTime = 0;
        $Numer = 0,                         // rezerwed for interited
        $Sort = "";
        $WinTitle = "Afik1 system",
        $USRNAME = "",
        $PgTitle = "";

        function view()
        function Run()
        function Actions()
        function HTTP_Header()
        function PageHeader()
        function HTML_Head()
        function HTML_Body()
        function BodyScripts()
        function PageFoot()
        function DeltaTime()
        function Log()
        function Help()
        function copyright()
        function display()
        function Script()
        function Styles()
        function WinTitle()
        function Show()
        function ErrMes($m)
        function WarMes($m)
        function TableSh($Color)
        function TableShDone()
        function ShResult($res)
        function Trans($s, $r)
        function Authorize()
        function AuthorizeError($mes)
        function SaveArrToFrom($arr, $prefix)
        function SubCallRun()
        function SubCallExitFlags() // abstract method
        function SaveSubCallFlags()
        function UnSetSubCallFlags($val)
        function nbsp($s)
        function URL()
        function StripSlashes()
        function StripSlashesFromArray($arr)
        function SaveScreenStatus($StatusField, $FieldsList)
        function ClearSession()
        function CheckConnectionNumber()
*/


if(!isset($_VIEW_INC_)) {


$_VIEW_INC_=0;

include("_config.inc.php");
require("utils.inc.php");
require("tools.inc.php");
require("db.inc.php");
require("stable.inc.php");
require("file.inc.php");


class view extends stable {
    var $UID,                               // User Identification
        $Key,                               // User key for remote_access
        $DOMAIN,
        $USR,
        $URI;
    var $Request_actions = array();
    var $StartObjectTime = 0;

    var $Numer = 0,                         // rezerwed for interited
        $Sort = "";

    var $WinTitle = "Afik1 system",
        $USRNAME = "",
        $PgTitle = "";


    function view()
    {
        global $UID, $Key, $CUID;
        global $REQUEST_URI, $SCRIPT_NAME, $PHPSESSID;
        global $SpecialConfirmPassword, $SpecialSavedRequestMethod, $REQUEST_METHOD;

        //$this->SetGlobals();

        if (!isset($GLOBALS[CurrentViewObject])) {
            $GLOBALS[CurrentViewObject] =& $this;
        }

        $this->CheckConnectionNumber();

        $this->Log("started $REQUEST_URI session $PHPSESSID");

        $this->StartObjectTime = split(" ", microtime());

        $this->table("");  // inherited constructor

        $this->Authorize();

        $this->UID = $UID;
        $this->Key = $Key;

        if($SpecialSavedRequestMethod == "GET") {
            $this->refreshScreen();
        }

        $this->OpenSession();

        $this->StripSlashes();

        //$this->USR = DBFind("usr", "sysnum = $this->UID", "", "file: " . __FILE__ . " line " . __LINE__);

        if ($this->UID != "") {
            DomainUsrResult($this->UID, &$this->DOMAIN, &$this->USR);
            if ($this->USR->NumRows() != 0) {
              $this->USRNAME = $this->USR->name() . "@" . $this->DOMAIN->name();
            } else {
              $this->USRNAME = $this->UID;
            }

            $this->URI = $SCRIPT_NAME . "?UID=" . $this->UID;
        } else {
            $this->URI = $SCRIPT_NAME;
        }
    }


    function SetGlobals()
    {
        if (empty($GLOBALS[HTTP_SESSION_VARS])) {
            $GLOBALS[HTTP_SESSION_VARS] = array();
        }

        $SRC =& $GLOBALS[HTTP_ENV_VARS];     $DST =& $GLOBALS[_ENV];     reset($SRC); while(list($n, $v) = each($SRC)) { $DST[$n] =& $SRC[$n]; $GLOBALS[$n] =& $SRC[$n]; }
        $SRC =& $GLOBALS[HTTP_SERVER_VARS];  $DST =& $GLOBALS[_SERVER];  reset($SRC); while(list($n, $v) = each($SRC)) { $DST[$n] =& $SRC[$n]; $GLOBALS[$n] =& $SRC[$n]; }
        $SRC =& $GLOBALS[HTTP_GET_VARS];     $DST =& $GLOBALS[_GET];     reset($SRC); while(list($n, $v) = each($SRC)) { $DST[$n] =& $SRC[$n]; $GLOBALS[$n] =& $SRC[$n]; $GLOBALS[_REQUEST][$n] =& $SRC[$n]; }
        $SRC =& $GLOBALS[HTTP_POST_VARS];    $DST =& $GLOBALS[_POST];    reset($SRC); while(list($n, $v) = each($SRC)) { $DST[$n] =& $SRC[$n]; $GLOBALS[$n] =& $SRC[$n]; $GLOBALS[_REQUEST][$n] =& $SRC[$n]; }
        $SRC =& $GLOBALS[HTTP_POST_FILES];   $DST =& $GLOBALS[_FILES];   reset($SRC); while(list($n, $v) = each($SRC)) { $DST[$n] =& $SRC[$n]; $GLOBALS[$n] =& $SRC[$n]; }
        $SRC =& $GLOBALS[HTTP_COOKIE_VARS];  $DST =& $GLOBALS[_COOKIE];  reset($SRC); while(list($n, $v) = each($SRC)) { $DST[$n] =& $SRC[$n]; $GLOBALS[$n] =& $SRC[$n]; $GLOBALS[_REQUEST][$n] =& $SRC[$n]; }
        $SRC =& $GLOBALS[HTTP_SESSION_VARS]; $DST =& $GLOBALS[_SESSION]; reset($SRC); while(list($n, $v) = each($SRC)) { $DST[$n] =& $SRC[$n]; $GLOBALS[$n] =& $SRC[$n]; $GLOBALS[_REQUEST][$n] =& $SRC[$n]; }
    }


    function Run()
    {
        //echo "\n==========\n", $this->DeltaTime(), "\n==========\n" ;
        $this->Actions();

        //echo "\n==========\n", $this->DeltaTime(), "\n==========\n" ;
        $this->HTTP_Header();

        //echo "\n==========\n", $this->DeltaTime(), "\n==========\n" ;
        $this->PageHeader(); {
        //echo "\n==========\n", $this->DeltaTime(), "\n==========\n" ;
            $this->HTML_Head();
        //echo "\n==========\n", $this->DeltaTime(), "\n==========\n" ;
            $this->HTML_Body();
        //echo "\n==========\n", $this->DeltaTime(), "\n==========\n" ;
        } $this->PageFoot();

        //$this->Log($this->URL() . " '" . $this->DeltaTime() . "' Sec.");
    }


    function OpenSession()
    {
        session_start();
    }


    function SaveScreenStatus($StatusField, $FieldsList)
    {
        global $_REQUEST;

        $ClearStatus = 1;
        if (func_num_args() > 2) {
            $ClearStatus = func_get_arg(2);
        }

        if ($StatusField == "") {
            return;
        }

        if ( !is_array($FieldsList) || count($FieldsList) == 0 ) {
            return;
        }

        if ($ClearStatus) {
            $GLOBALS[$StatusField][Status] = array();
        }

        reset($FieldsList);
        while(list($n, $v) = each($FieldsList)) {
            if (!isset($_REQUEST[$v])) {
                continue;
            }
            if (!is_array($_REQUEST[$v])) {
                $GLOBALS[$StatusField][Status][$v] = $_REQUEST[$v];
            } else {
                reset($_REQUEST[$v]);
                while(list($ins_n, $ins_v) = each($_REQUEST[$v])) {
                    $GLOBALS[$StatusField][Status][$v][$ins_n] = $ins_v;
                }
            }
        }
    }


    function ClearSession()
    {
        global $_SESSION, $HTTP_SESSION_VARS;

        $arr = array_keys($_SESSION);

        foreach($arr as $VarName) {
            unset($_SESSION[$VarName]);
            unset($HTTP_SESSION_VARS[$VarName]);
            unset($GLOBALS[$VarName]);
        }
    }


    function Actions()
    {
        reset ($this->Request_actions);
        while (list($n, $v) = each($this->Request_actions)) {
            $opt = strtolower($this->Request_options[$n]);

            if ( isset($GLOBALS[$n]) && ($GLOBALS[$n] != "" || strstr($opt, "blankok")) ) {
                //echo "run $n = $v = $opt<br>";
                $this->Log("Actions : $n");
                eval("\$this->$v;");
                if (!strstr($opt, "continue")) {
                    return;
                }
            }
        }

        //exit;
    }


    function HTTP_Header()
    {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");

        $this->HTTP_Header_ContentType();
    }


    function HTTP_Header_ContentType()
    {
        header("Content-Type: text/html;");
    }


    function PageHeader()
    {
        echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n";
        echo "<html>\n";
    }


    function HTML_Head()
    {
        echo "<head>\n";

        $this->HeadsTags();
        $this->Styles();
        $this->WinTitle();
        $this->Script();

        echo "</head>\n";
    }


    function HeadsTags()
    {
        global $INET_IMG;

        echo "<META NAME=\"Author\" CONTENT=\"WAN Vision Ltd. 2001\">\n",
             "<META NAME=\"Generator\" CONTENT=\"PHP 4. Original script\">\n",
             "<META HTTP-EQUIV=\"Cache-control\" CONTENT=\"Private\">\n",
             "<LINK REL=\"SHORTCUT ICON\" HREF=\"$INET_IMG/afik1.ico\">\n";
    }

    function HTML_Body()
    {
        echo "<body class='body' " . $this->BodyScripts() . ">\n";
        $this->display();
        $this->Show();
        $this->Help();
        $this->copyright();
        echo GetMakeButtonBlankList();
        echo "</body>\n";
    }

    function BodyScripts()
    {
        return "";
    }

    function PageFoot()
    {
        echo "</html>\n";
    }


    function DeltaTime()
    {
        $PointTime = split(" ", microtime());

        return ($PointTime[0] - $this->StartObjectTime[0]) + ($PointTime[1] - $this->StartObjectTime[1]);
    }

    function Log()
    {
        if (func_num_args() > 0) {
            $Mes = "";
            for ($i=0; $i < func_num_args(); $i++) {
                $Mes .= func_get_arg($i);
            }
        } else {
            $Mes = $this->URL();
        }

        WebLog($Mes);
    }


    function Help()
    {
        global $INET_SRC;

        echo "<script language='javascript'>\n";

        // echo "function CenteredHelp()\n";
        // echo "{\n";
        // echo "  var div = document.all[\"help\"];\n";
        // echo "  var body = document.body;\n";
        // echo "  if (div.style.visibility == \"visible\") {\n";
        // echo "    div.style.top = document.body.scrollTop + (body.clientHeight - div.offsetHeight)/2;\n";
        // echo "    div.style.left = document.body.scrollLeft + (body.clientWidth - div.offsetWidth)/2;\n";
        // echo "    window.setTimeout(\"CenteredHelp()\", 500);\n";
        // echo "  }\n";
        // echo "}\n";

        echo "function showHelp(src)\n";
        echo "{\n";
          echo "window.open(src, \"\", \"status=yes,toolbar=yes,menubar=no,location=no,resizable=yes,scrollbars=yes\");\n";
        // echo "  var div = document.all[\"help\"];\n";
        // echo "  div.style.visibility = \"visible\";\n";
        // echo "  div.style.height = \"330px\";\n";

        // echo "  var frm = window.frames[\"help_frame\"];\n";
        // echo "  frm.setSrc(src);\n";

        // echo "  CenteredHelp();\n";
        // echo "}\n";

        // echo "function hiddeHelp()\n";
        // echo "{\n";
        // echo "  var div = document.all[\"help\"];\n";
        // echo "  div.style.visibility = \"hidden\";\n";
        echo "}\n";

        echo "</script>\n";

        // echo "<div id = 'help' class='toolsbarl' style=\"FONT-SIZE: 12px;  HEIGHT: 0; LEFT: 0px; POSITION: absolute; TOP: 0px; VISIBILITY: hidden; WIDTH: 470px; zindex=80000\">\n";
        // echo "<IFRAME id = 'help_frame' width='100%' height='100%' src='$INET_SRC/help.php?UID=$this->UID'>\n";
        // echo "Need upgrade yours browser\n";
        // echo "</IFRAME>\n";
        // echo "</div>\n";
    }

    function copyright()
    {
    }

    function Display()
    {
    }


    function Script()
    {
        global $INET_SRC;

        echo "<script language='javascript' src='$INET_SRC/view.js'>\n";
        echo "</script>\n";
    }


    function Styles()
    {
        global $HTTP_HOST, $INET_SRC, $PROGRAM_SRC;
        echo implode("", file("$PROGRAM_SRC/standard.css"));
    }


    function WinTitle()
    {
        echo "<title>" . $this->WinTitle . " " . $this->USRNAME . " " . strip_tags($this->PgTitle) . "</title>\n";
    }


    function Show()
    {
        echo "\n", stable::Show();
    }


    function ErrMes($m)
    {
        global $INET_IMG;

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");
        $this->out("<center>");
        $this->SubTable("border=0 CELLSPACING=1 CELLPADDING=3 bgcolor='#ff0000'");
        $this->TDNext("align='center'");
        $this->SubTable("border=1 CELLSPACING=1 CELLPADDING=10 bgcolor='#ff0000'");
        $this->out("<font color='#fefefe'>Error : <B>$m</B></font>");
        $this->SubTableDone();
        $this->SubTableDone();
        $this->out("</center>");
    }


    function InfMes($m)
    {
        $this->out("<span class='body'><center><i><b>$m</b></i></center></span>");
    }


    function TableSh($Color)
    {
        if ($Color == "") {
            $Color = "#fefefe";
        }
        $this->SubTable("border=0 CELLSPACING=0 CELLPADDING=0 width='100%'");
        $this->tds(0, 0, "bgcolor='$Color'", "");
        $this->SubTable("border=0 CELLSPACING=1 CELLPADDING=0 width='100%' bgcolor='$this->color1'");
    }


    function TableShDone()
    {
        $this->SubTableDone();
        $this->SubTableDone();
    }


    function ShResult($res)
    {
        $this->TableSh("");

        for ($i = 0; $i < $res->NumFields(); $i++) {
            $this->out($res->FieldName($i));
            $this->TDNext("");
        }
        $this->TRNext("");

        for ($res->set(0); !$res->Eof(); $res->Next()) {
            for ($i = 0; $i < $res->NumFields(); $i++) {
                $s = $res->Field($i);
                $this->tds("*", $i, "", ($s != "" ? $s : "&nbsp"));
                // $this->TDNext("");
            }
            $this->TRNext("");
        }

        $this->TableShDone();
    }


    function Trans($s, $r)
    {
        reset($GLOBALS);
        $r = ($r == "" ? $s : $r);

        //echo "$s, $r";
        //echo SHGLOBALS();

        while (list($n, $v) = each($GLOBALS)) {
            //echo "$n, ", substr($n, 0, strlen($s) + 1), ", ", $s . "_", "<br>";

            //if ((($k = strspn("$n", $s . "_")) == strlen($s) + 1) && ($v != "")) {

            $k = strlen($s) + 1;
            if ((substr($n, 0, $k) == $s . "_") && ($v != "")) {
                //echo "$s = $n =$v=<br>";
                $GLOBALS[$s] = $n;
                $GLOBALS[$r] = substr($n, $k);
                $pos = strpos ($GLOBALS[$r], "_");
                if ($pos != false) {
                   $GLOBALS[$r] = substr($GLOBALS[$r], 0, $pos);
                }
                //echo "$k<br>";
                //echo "$n $GLOBALS[$n]<br>";
                //echo "$s $GLOBALS[$s]<br>";
                //echo "$r $GLOBALS[$r]<br>";
                //exit;
                break;
            }
        }
    }


    function Authorize()
    {
        global $UID, $Key, $CUID;

        if ($Key != "") {
            if (!is_emailaddress($UID, true) || $Key != AuthorizeKey($UID)) {
                $this->AuthorizeError("Error authorizing.");
            }
        } else {
            $UID = (int)$UID;
            if ( !preg_match("/^[1-9][0-9]*$/", $UID) || !is_array($CUID) || !is_array($CUID[$UID]) ) {
                $this->AuthorizeError("Error authorizing.");
            } else  if ((!ereg("^[1-9][0-9]*$", $CUID[$UID][time])) || ($CUID[$UID][time] <= 0) || ((time() - $CUID[$UID][time]) > (60 * 60))) {
                $this->AuthorizeError("Time out. " . ShInterval(time() - $CUID[$UID][time]) );
            } else  if (TimedependAuthorizeHash($UID, $CUID[$UID][time]) != $CUID[$UID][code]) {
                $this->AuthorizeError("Error authorizing.");
            }

            $res = DBExec("SELECT * FROM usr_ua WHERE sysnumusr = $UID and name = 'edenaid'", "file: " . __FILE__ . " line " . __LINE__);
            if ($res->NumRows() != 0 && $res->value() != "") {
                $UID = "Denied";
                $this->AuthorizeError("Access for user denied.");
            }

            if ($UID != "") {
                $this->SetIdentificationCookies();
            }
        }
    }


    function SetIdentificationCookies()
    {
        global $UID, $HTTP_HOST;

        $time = time();
        $hash = TimedependAuthorizeHash($UID, $time);

        $this->Log("setcookie CUID[$UID][time] to $time for $HTTP_HOST");
        setcookie("CUID[$UID][time]", $time, 0, "/", $HTTP_HOST);

        $this->Log("setcookie CUID[$UID][code] to $hash for $HTTP_HOST");
        setcookie("CUID[$UID][code]", $hash, 0, "/", $HTTP_HOST);
    }


    function ClearIdentificationCookies()
    {
        global $UID, $HTTP_HOST;

        $this->Log("setcookie CUID[$UID][time] to deleted for $HTTP_HOST");
        $this->Log("setcookie CUID[$UID][code] to deleted for $HTTP_HOST");

        setcookie("CUID[$UID][time]", "", 0, "/", $HTTP_HOST);
        setcookie("CUID[$UID][code]", "", 0, "/", $HTTP_HOST);
    }


    function AuthorizeError($mes)
    {
        global $UID, $Key, $CUID;
        global $SpecialConfirmPassword, $SpecialSavedRequestMethod, $REQUEST_METHOD;
        global $INET_ROOT, $INET_SRC;

        $this->Log("AuthorizeError : $mes");

        $UID = (int)$UID;
        if (!ereg("^[1-9][0-9]*$", $UID) || ($Key != "")) {
            $this->Log("UID :'" . $UID . "'. AuthorizeError N 1");
            $this->Log("CUID :\n" . sharr($CUID));
            echo "<html>";
            echo "<body>";
            echo "<h1>$mes</h1>";
            echo "</body>";
            echo "</html>";
            exit;
        }

        $r_usr = DBFind("usr, domain", "usr.sysnum = '$UID' and usr.sysnumdomain = domain.sysnum", "usr.name as usrname, domain.name as domainname, usr.password", "file: " . __FILE__ . " line " . __LINE__);

        $this->Log("UID :" . $UID . ". "  . $r_usr->usrname() . "@" . $r_usr->domainname() . " Geted Password '$SpecialConfirmPassword'");

        if ($r_usr->NumRows() == 1 && $r_usr->password() == $SpecialConfirmPassword) {
            $this->Log("UID :" . $UID . ". "  . $r_usr->usrname() . "@" . $r_usr->domainname() . " Authorization Confirmed");
            return;
        }

        $this->Log("UID :$UID. AuthorizeError N 2");
        $this->Log("CUID :\n" . sharr($CUID));

        $this->ClearIdentificationCookies();

        echo "
        <html>
        <head>";

        $this->Styles();

        echo "
            </head>
            <body class='body'>
            <form method='post'>
            <center>
              <h1 class='body'>$mes</h1><br>
              <h2 class='body'>security check</h2><hr>
            </center>
            <center><h5 class='body'>To confirm identity please enter youre password:&nbsp;
            <input type='password' name='SpecialConfirmPassword'>&nbsp;<input type='submit' value='Ok'></h5></center>
            <hr><span class='body'>Check cookies or proxy in your browser preferences.</span>";

        unset($GLOBALS[HTTP_POST_VARS][SpecialConfirmPassword]);
        $this->SaveArrToFrom($GLOBALS[HTTP_POST_VARS], "");

        if ($SpecialSavedRequestMethod == "") {
            $SpecialSavedRequestMethod = $REQUEST_METHOD;
        }

        echo "
            <input type = 'hidden' name='SpecialSavedRequestMethod'
                  value=\"" . htmlspecialchars($SpecialSavedRequestMethod) . "\">
            </form>
            </body>
            </html>";

        exit;
    }


    function SaveArrToFrom($arr, $prefix)
    {
        if (!isset($arr)) {
            return;
        }
        if (!is_array($arr)) {
            if ($prefix != "") {
                echo "<input type='hidden' name=\"".htmlspecialchars($prefix)."\" value=\"".htmlspecialchars($arr)."\">\n";
            }
            return;
        }
        reset($arr);
        while(list($n, $v) = each($arr)) {
            $this->SaveArrToFrom($v, ($prefix == "" ? $n : $prefix . "[" . $n . "]"));
        }
    }


    function SubCallRun()
    {
        $r = $this->SubCallExitFlags();

        if (!isset($r) || !is_array($r)) {
                return;
        }

        $pr = 1;
        reset($r);
        while(list($n, $v) = each($r)) {
            if (isset($GLOBALS[$v]) && $GLOBALS[$v] != "") {
                $pr = 0;
                break;
            }
        }

        if ($pr) {
            $this->Run();
            exit;
        }

        return $n;
    }


    function SubCallExitFlags() // abstract method
    {
        return array();
    }


    function SaveSubCallFlags()
    {
        global $SubCallFlags;

        if (!isset($SubCallFlags) || !is_array($SubCallFlags)) {
            return;
        }

        reset ($SubCallFlags);
        while (list($n, $v) = each($SubCallFlags)) {
            $s = htmlspecialchars(stripslashes($GLOBALS[$v]));
            $this->out("<input type='hidden' name='$v'               value=\"$s\">");
            $this->out("<input type='hidden' name='SubCallFlags[]'   value='$v'>");
        }
    }


    function UnSetSubCallFlags($val)
    {
        global $SubCallFlags;

        if (!isset($SubCallFlags) || !is_array($SubCallFlags)) {
            return;
        }

        reset ($SubCallFlags);
        while (list($n, $v) = each($SubCallFlags)) {
            if($v == $val) {
                unset($SubCallFlags[$n]);
                while (list($n, $v) = each($SubCallFlags)) {
                    unset($SubCallFlags[$n]);
                }

                if (count($SubCallFlags) == 0) {
                    unset($GLOBALS[SubCallFlags]);
                    // echo "=1=";
                }

                return;
            }
        }
    }


    function nbsp($s)
    {
        return $s != "" ? $s : "&nbsp";
    }


    function URL()
    {
        global $SERVER_NAME, $SERVER_PORT, $PHP_SELF, $argc, $argv;

        $url = "http://" . $SERVER_NAME . ":" . $SERVER_PORT . $PHP_SELF;
        if ($argc > 0) {
            $url .= "?";
            for ( $i = 0; $i < $argc; $i++ ) {
                $url .= ($i != 0 ? "&" : "") . $argv[$i];
            }
        }

        return $url;
    }


    function StripSlashes()
    {
        global $HTTP_POST_VARS, $HTTP_GET_VARS, $_POST, $_GET, $_REQUEST;
        global $PermSelectAddr;

        if (is_array($HTTP_GET_VARS)) {
          // $HTTP_GET_VARS  = $this->StripSlashesFromArray($HTTP_GET_VARS);

            reset($HTTP_GET_VARS);
            while (list($n, $v) = each($HTTP_GET_VARS)) {
                $GLOBALS[$n] = $HTTP_GET_VARS[$n] = $this->StripSlashesFromArray($HTTP_GET_VARS[$n]);
            }
        }

        if (is_array($_GET)) {
            reset($_GET);
            while (list($n, $v) = each($_GET)) {
                // $GLOBALS[$n] = $v;
                $GLOBALS[$n] = $_GET[$n] = $this->StripSlashesFromArray($_GET[$n]);
            }
        }

        if (is_array($HTTP_POST_VARS)) {
          // $HTTP_POST_VARS = $this->StripSlashesFromArray($HTTP_POST_VARS);

            reset($HTTP_POST_VARS);
            while (list($n, $v) = each($HTTP_POST_VARS)) {
                // $GLOBALS[$n] = $v;
                $GLOBALS[$n] = $HTTP_POST_VARS[$n] = $this->StripSlashesFromArray($HTTP_POST_VARS[$n]);
            }
        }

        if (is_array($_POST)) {
            reset($_POST);
            while (list($n, $v) = each($_POST)) {
                // $GLOBALS[$n] = $v;
                $GLOBALS[$n] = $_POST[$n] = $this->StripSlashesFromArray($_POST[$n]);
            }
        }

        if (is_array($_REQUEST)) {
            reset($_REQUEST);
            while (list($n, $v) = each($_REQUEST)) {
                // $GLOBALS[$n] = $v;
                $GLOBALS[$n] = $_REQUEST[$n] = $this->StripSlashesFromArray($_REQUEST[$n]);
            }
        }
    }


    function StripSlashesFromArray($arr)
    {
        $rez = $arr;
        if (is_array($arr)) {
            //echo "============<br>";
            //ShArr($arr, "");
            _reset($arr);
            while (list($n, $v) = _each($arr)) {
                if (!is_array($v)) {
                    $rez[$n] = stripslashes($v);
                    //echo htmlspecialchars($rez[$n] . "<-" . $v), "<br>";
                } else {
                    $rez[$n] = $this->StripSlashesFromArray($v);
                }
            }
            //echo "============<br>";
        } else {
            $rez = stripslashes($arr);
        }
        return $rez;
    }


    function refreshScreen()
    {
        global $INET_SRC, $REQUEST_URI;

        $URL = $INET_SRC . $REQUEST_URI;
        if (func_num_args() > 0) {
            $URL = func_get_arg(0);
        }

        if (!headers_sent()) {
            header("Location: " . $URL);
            exit;
        }

        echo "<script language='javascript'>\n";
        echo "    document.location = \"$URL\";\n";
        echo "</script>";
        exit;
    }


    function CheckConnectionNumber()
    {
        global $REMOTE_ADDR;
        global $ConnectionPassed;


	//return;

        register_shutdown_function (view_shutdown);

        $sem_key = 0x01ff;
        $shm_key = 0x01ff;
        $var_key = 0x0001;

        $sem_id = sem_get ( $sem_key );
        sem_acquire( $sem_id );

        $shm_id = shm_attach( $shm_key );

        $list = @shm_get_var ( $shm_id, $var_key);
        if (!is_array($list)) {
            $list = array();
        }

		if ( ! isset($list[$REMOTE_ADDR][pid][posix_getpid()]) ) {
			$list[$REMOTE_ADDR]['Count'] = (int)($list[$REMOTE_ADDR]['Count']) + 1;
		}
		$list[$REMOTE_ADDR][pid][posix_getpid()] = GetCurrDate();
        shm_put_var ( $shm_id, $var_key, $list);

        if ($list[$REMOTE_ADDR]['Count'] >= 10) {
            $this->Log("Connection Count for $REMOTE_ADDR is {$list[$REMOTE_ADDR]}. Current conncection refused");
            $ConnectionPassed = false;
        } else {
            $this->Log("Connection Count for $REMOTE_ADDR is {$list[$REMOTE_ADDR]}. Current connection accepted");
            $ConnectionPassed = true;
        }

        sem_release ($sem_id);

        if ( !$ConnectionPassed ) {
            header("Status: 403 Too many connections from one ip {$REMOTE_ADDR}");
            echo "Too many connections from one IP {$REMOTE_ADDR} ( {$list[$REMOTE_ADDR]['Count']} ). Conection refused<br>";
            echo "Pleas chect what youre neighbor don't use download accselerator<br>";
            exit;
        }
    }

} // class


function view_shutdown()
{
    global $REMOTE_ADDR;
    global $CurrentViewObject;

    $sem_key = 0x01ff;
    $shm_key = 0x01ff;
    $var_key = 0x0001;

    $sem_id = sem_get ( $sem_key );
    sem_acquire( $sem_id );

    $shm_id = shm_attach($shm_key);
    $list = @shm_get_var ( $shm_id, $var_key );

    if (is_array($list)) {
        if ($list[$REMOTE_ADDR]['Count'] > 0) {
            $list[$REMOTE_ADDR]['Count'] -= 1;
			unset($list[$REMOTE_ADDR][pid][posix_getpid()]);
			WebLog("Connection Count for $REMOTE_ADDR is {$list[$REMOTE_ADDR]['Count']} Connection canceled");

            if ($list[$REMOTE_ADDR]['Count'] == 0) {
                unset($list[$REMOTE_ADDR]);
            }
            shm_put_var ( $shm_id, $var_key, $list);
        }
    }

    sem_release ($sem_id);

    //echo "\n==========\n", $CurrentViewObject->DeltaTime(), "\n==========\n" ;
}

} // $_VIEW_INC;

?>
