<?php

/*

class SignUsr extends screen
    function SignUsr()
    function Scr()
        function ViewList()
            function GetInputHTMLText($name)
        function ViewComplete()
        function Introduction()
    function mes()
    function rCancel()
    function rCheck()
    function rConfirm()
    function Save()

     function Authorize()
     function Referens()
     function UserName()
*/




include "_config.inc.php";
require "utils.inc.php";
require "file.inc.php";
require "db.inc.php";
ConnectToDB();

require "view.inc.php";
require "screen.inc.php";

class CSignUsr extends screen
{
    function CSignUsr()
    {
        global $TEMPL, $HTTP_HOST, $LOCAL_SERVER;

        if (!preg_match("/^[a-z0-9._-]+$/i", $HTTP_HOST)) {
            echo "Host '<b>" . htmlspecialchars($HTTP_HOST) . "</b>' invalid.<br>Hosts server $LOCAL_SERVER";
            exit;
        }

        $this->r_dm = DBFind("domain", "name = '$HTTP_HOST'", "", __LINE__);
        if ($this->r_dm->NumRows() != 1) {
            echo "Host '<b>$HTTP_HOST</b>' NOT AVAILABLE (OR) NOT PRESENT.<br>Hosts server $LOCAL_SERVER";
            exit;
        }
        if (!$this->r_dm->signup()) {
            echo "Host '<b>$HTTP_HOST</b>' sign new user prohibition by admin.<br>Hosts server $LOCAL_SERVER";
            exit;
        }

        $this->screen(); // inherited constructor
        $this->SetTempl("signusr");

        $this->PgTitle = "<b>$TEMPL[title]</b> ";

        $this->ChList = array();
        $this->ErrList = array();
        $this->Checked = 0;

        $this->Request_actions["sCancel"]       = "rCancel()";
        $this->Request_actions["sSubmit"]       = "rCheck()";
        $this->Request_actions["sConfirm"]      = "rConfirm()";
    }


    function Scr()
    {
         global $Confirm;

         //$GLOBALS[Params][name] = "mixa";
         //$this->ViewComplete(); return;

         if ($this->r_dm->trialsignup() && !$Confirm) {
            $this->Introduction();
         } else {
            if ($this->Checked && $this->Save()) {
                $this->ViewComplete();
            } else {
                $this->ViewList();
            }
         }
    }


    function GetFeedBackEmailLink() // override
    {
        global $FEEDBACK_EMAIL;

        return "mailto:$FEEDBACK_EMAIL";
    }


    function Introduction()
    {
        global $TEMPL, $PROGRAM_HELP;

        $this->out(join("", FILE("$PROGRAM_HELP/sign_up_introduction.html")));

        $this->out("<p class='body'><h5 class='body'>TERMS AND CONDITIONS OF SERVICE &amp; PRIVACY POLICY</h5></p>");

        $this->out("<CENTER>"); {
            $this->out("<TEXTAREA ROWS=15 COLS=90 WRAP=Off ReadOnly>"); {
                $this->out(join("", FILE("$PROGRAM_HELP/tos.txt")));
                $this->out(join("", FILE("$PROGRAM_HELP/priv_policy.txt")));
            } $this->out("</TEXTAREA>");
        } $this->out("</CENTER>");

        $this->out("<br><center><hr><h1 class='body'>I confirm I have read &amp agree with<br>the TERMS &amp; CONDITIONS OF SERVICE &amp PRIVACY POLICY</h1><br>");

        $this->OUT( "<form method='POST' name='confirmform'>" );
        $this->OUT(makeButton("type=1& name=sConfirm& form=confirmform& value=$TEMPL[bt_confirm]& title=$TEMPL[bt_confirm_ico]") . "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp");
        $this->OUT(makeButton("type=1& name=sCancel&  form=confirmform& value=$TEMPL[bt_cancel]&  title=$TEMPL[bt_cancel_ico]"));
        $this->OUT( "</form>" );

        $this->out("</center>");
    }


    function ViewList()
    {
        global $Params, $INET_IMG, $TEMPL;

        $this->OUT( "<form method='POST' name='signupform'>" );

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0'"); {

            $this->TRNext("class='body'"); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler2x1.gif'>");
                }
            }

            $this->TRNext("class='toolsbarl'"); {
                $this->TDNext( "class='toolsbarl' colspan=3 align='left'"); {
                    $this->SubTable("border=0 cellpadding='5' cellspacing=0"); {
                        $this->SubTable("border=0 cellspacing=0"); {
                            $this->TRNext("class='toolsbarl'"); {
                                $this->TDNext("class='toolsbarl' nowrap"); {
                                    $this->Out($this->TextShift);
                                }
                                $this->TDNext("class='toolsbarl' nowrap"); {
                                    $this->Out("<img src='$INET_IMG/num-1.gif' align='absmiddle'>");
                                }
                                $this->TDNext("class='toolsbarl' nowrap"); {
                                    $this->Out($this->TextShift);
                                }
                                $this->TDNext("class='toolsbarl'"); {
                                    $this->Out("<span class='toolsbarl' style='font-size: 18px'><b>$TEMPL[acc_info]</b>");
                                }
                            }
                        } $this->SubTableDone();
                    } $this->SubTableDone();
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler2x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle' width='10%'"); {
                    $this->Out($this->TextShift, $TEMPL[usrname], " <span style='color=red'>*</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp' width='40%'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(name, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlae' rowspan=4 valign='top' width='50%'"); {
                    $this->Out($this->TextShift, $TEMPL[passw_mes]);
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=2 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[password], " <span style='color=red'>*</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(password, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                //$this->TDNext( "class='tlae' rowspan=2 valign='top'"); {
                //    $this->Out($this->TextShift, #1, $this->TextShift);
                //}
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[repassword], " <span style='color=red'>*</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(passwordl, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[firstname], " <span style='color=red'>**</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(firstname, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlae' rowspan=2 valign='top'"); {
                    $this->Out($this->TextShift, $TEMPL[name_mes], $this->TextShift);
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[lastname], " <span style='color=red'>**</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(lastname, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[country], $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(country, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[city], $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(city, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[address], $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(address, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[zip], $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(zip, 30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                }
            }

            $this->TRNext(); {
                if ($this->ErrList[contacts] != "") {
                    $this->TDNext( "class='tlp' valign='middle'"); {
                        $this->Out("&nbsp");
                    }
                    $this->TDNext( "class='tlp' colspan=1"); {
                        $this->Out("<span class='tlp' style='color:red'>" . $this->ErrList[contacts] . "</span>");
                    }
                    $this->TDNext( "class='tlpe'"); {
                        $this->Out("&nbsp");
                    }
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp'  nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[email], " <span style='color=red'>**</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(email,30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[phone], " <span style='color=red'>**</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(phone,30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[mphone], " <span style='color=red'>**</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(mphone,30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[fax], " <span style='color=red'>**</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(fax,30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle'"); {
                    $this->Out($this->TextShift, $TEMPL[nicq], " <span style='color=red'>**</span>", $this->TextShift);
                }
                $this->TDNext( "class='tlp'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'><br>");
                    $this->Out($this->TextShift, $this->GetInputHTMLText(nicq,30), $this->TextShift);
                    $this->Out("<br><img src='$INET_IMG/filler3x1.gif'>");
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle' colspan=2"); {
                    $this->Out($this->TextShift, "<span style='color=red'>*</span> ", $TEMPL[need_fill], $this->TextShift);
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='tlp' nowrap valign='middle' colspan=2"); {
                    $this->Out($this->TextShift, "<span style='color=red'>**</span> ", $TEMPL[need_fill_one], $this->TextShift);
                }
                $this->TDNext( "class='tlpe'"); {
                    $this->Out("&nbsp");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            $this->TRNext("class='toolsbarl'"); {
                $this->TDNext( "class='toolsbarl' colspan=3 align='left'"); {
                    $this->SubTable("border=0 cellpadding='5' cellspacing=0"); {
                        $this->SubTable("border=0 cellspacing=0"); {
                            $this->TRNext("class='toolsbarl'"); {
                                $this->TDNext("class='toolsbarl' nowrap"); {
                                    $this->Out($this->TextShift);
                                }
                                $this->TDNext("class='toolsbarl' nowrap"); {
                                    $this->Out("<img src='$INET_IMG/num-2.gif' align='absmiddle'>");
                                }
                                $this->TDNext("class='toolsbarl' nowrap"); {
                                    $this->Out($this->TextShift);
                                }
                                $this->TDNext("class='toolsbarl'"); {
                                    $this->Out("<span class='toolsbarl' style='font-size: 18px'><b>$TEMPL[acc_info]</b>");
                                    if ($this->r_dm->trialsignup()) {
                                        $this->Out("<span class='toolsbarl' style='font-size: 18px'><b>$TEMPL[confirm_title_trial]</b>");
                                    } else {
                                        $this->Out("<span class='toolsbarl' style='font-size: 18px'><b>$TEMPL[confirm_title]</b>");
                                    }
                                }
                            }
                        } $this->SubTableDone();
                    } $this->SubTableDone();
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->Out("<img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            $this->TRNext(); {
                $this->TDnext("class='body' colspan=3"); {
                    if ($this->r_dm->trialsignup()) {
                        $this->Out("$TEMPL[confirm_text_trial]");
                    } else {
                        $this->Out("$TEMPL[confirm_text]");
                    }
                    $this->Out("<br><br>");
                }
            }

            $this->TRNext(); {
                $this->TDNext( "class='body' colspan=3 align='center'"); {
                    $this->OUT(makeButton("type=1& form=signupform& name=sSubmit& value=$TEMPL[bt_submit]& title=$TEMPL[bt_submit_ico]") . $this->ButtonBlank);
                    $this->OUT(makeButton("type=1& form=signupform& name=sCancel& value=$TEMPL[bt_cancel]& title=$TEMPL[bt_cancel_ico]") . "<br>&nbsp");
                }
            }

        } $this->SubTableDone();
    }


    function GetInputHTMLText($name)
    {
        global $Params;

        $size = 15;
        if (func_num_args() > 1) {
          $size = func_get_arg(1);
        }

        if (ereg("^password", $name)) {
          $ret = $this->ChList[$name] == $name ? "<b>-= Assigned =-</b><input type='hidden' name='Params[$name]' value=\"" . htmlspecialchars($Params[$name]) . "\">" : "<INPUT size=$size name='Params[$name]' class='toolsbarb' type='password' value=\"" . htmlspecialchars($Params[$name]) . "\">";
        } else {
          $ret = $this->ChList[$name] == $name ? "<b>" . htmlspecialchars($Params[$name]) . "</b><input type='hidden' name='Params[$name]' value=\"" . htmlspecialchars($Params[$name]) . "\">" : "<INPUT size=$size maxlength=$size name='Params[$name]' value=\"" . htmlspecialchars($Params[$name]) . "\" class='toolsbarb'>";
        }

        if ($this->ErrList[$name] != "") {
           $ret = "<table><tr><td><span class='tlp' style='color:red'>" . $this->ErrList[$name] . "</font></td></tr></table>" . $this->TextShift . $ret;
        }

        return $ret;
    }


    function rCancel()
    {
        global $INET_SRC, $FACE;
        global $HTTP_HOST, $REQUEST_URI;

        $this->refreshScreen("$INET_ROOT");
    }


    function rCheck()
    {
        global $Params;
        global $Mes;
        global $TEMPL;
        global $HTTP_HOST;
        global $_SERVER;


        $SQL = "SELECT  * " .
               "FROM " .
               "    usr " .
               "        LEFT JOIN usr_ua ON usr.sysnum = usr_ua.sysnumusr and usr_ua.name = 'signaddress' " .
               "WHERE " .
               "    usr_ua.value = '" . $_SERVER[REMOTE_ADDR] . "' AND " .
               "    'now'::abstime - usr.creat < '0 day 00:30'::interval ";

        $r_usr = DBExec($SQL, __LINE__);

        if ($r_usr->NumRows() > 0) {
            $this->Log("login locked from $_SERVER[REMOTE_ADDR]. Last singup in " . $r_usr->creat());
            $this->ErrList[Mes] = $TEMPL[err_mes0];
            $this->Checked = 0;
            return;
        }

        #-----------------------------------------------------------------------
        #

        _reset($Params);
        while(list($n, $v) = _each($Params)) {
            if (strlen($v) > 30) {
                $this->ErrList[$n] = $TEMPL[err_mes2];
            } else {
                if (!preg_match("/^[a-z0-9_\-. @]*$/i", $v)) {
                    $this->ErrList[$n] = $TEMPL[err_mes3];
                }
            }
        }

        $this->Checked = !count($this->ErrList);
        if (!$this->Checked) {
            return;
        }


        #-----------------------------------------------------------------------
        #
        if ($this->ErrList[name] == "") {
            if (strlen($Params[name]) < 1) {
                $this->ErrList[name] = $TEMPL[err_mes1];
            } else {
                if (!ereg("^[A-Za-z][A-Za-z0-9_\-]*$", $Params[name])) {
                    $this->ErrList[name] = $TEMPL[err_mes3];
                } else {
                    $r_dm = DBFind("domain, usr", "usr.sysnumdomain = domain.sysnum and domain.name = '$HTTP_HOST' and usr.name = '$Params[name]'", "", __LINE__);
                    if ($r_dm->NumRows() != 0) {
                        $this->ErrList[name] = $TEMPL[err_mes4];
                    } else {
                        $this->ChList[name] = name;
                    }
                }
            }
        }
        #-----------------------------------------------------------------------
        #
        if ($this->ErrList[password] == "" && $this->ErrList[passwordl] == "") {
            if (strlen($Params[password]) < 6) {
                $this->ErrList[password] = $TEMPL[err_mes1];
                unset($Params[password]);
                unset($Params[passwordl]);
            } else {
                if (!ereg("^[A-Za-z0-9_]*$", $Params[password])) {
                    $this->ErrList[password] = $TEMPL[err_mes3];
                    unset($Params[password]);
                    unset($Params[passwordl]);
                } else {
                    if ($Params[password] != $Params[passwordl]) {
                        $this->ErrList[passwordl] = $TEMPL[err_mes5];
                        #unset($Params[password]);
                        unset($Params[passwordl]);
                    } else {
                        $this->ChList[password]  = password;
                        $this->ChList[passwordl] = passwordl;
                    }
                }
            }
        } else {
            unset($Params[password]);
            unset($Params[passwordl]);
        }

        if ($Params[firstname] == "" && $Params[lastname] == "") {
            $this->ErrList[firstname] = $TEMPL[err_mes6];
        } else {
            $this->ChList[firstname] = firstname;
            $this->ChList[lastname]  = lastname;
        }

        if ($this->ErrList[country] == "") {
            if ($Params[country] != "") {
                $this->ChList[country]  = country;
            }
        }

        if ($this->ErrList[city] == "") {
            if ($Params[city] != "") {
                $this->ChList[city]  = city;
            }
        }

        if ($this->ErrList[address] == "") {
            if ($Params[address] != "") {
                $this->ChList[address]  = address;
            }
        }

        if ($this->ErrList[zip] == "") {
            if ($Params[zip] != "") {
                $this->ChList[zip]  = zip;
            }
        }

        if ($this->ErrList[email] == "") {
            if ($Params[email] != "") {
                $this->ChList[email]  = email;
            }
        }

        if ($this->ErrList[phone] == "") {
            if ($Params[phone] != "") {
                $this->ChList[phone]  = phone;
            }
        }

        if ($this->ErrList[mphone] == "") {
            if ($Params[mphone] != "") {
                $this->ChList[mphone] = mphone;
            }
        }

        if ($this->ErrList[fax] == "") {
            if ($Params[fax] != "") {
                $this->ChList[fax]    = fax;
            }
        }

        if ($this->ErrList[nicq] == "") {
            if ($Params[nicq] != "") {
                $this->ChList[nicq]   = nicq;
            }
        }

        if ($Params[email] == "" && $Params[phone] == "" && $Params[mphone] == "" && $Params[fax] == "" && $Params[nicq] == "") {
            $this->ErrList[contacts] = $TEMPL[err_mes7];
        }

        $this->Checked = !count($this->ErrList);
    }


    function rConfirm()
    {
        global $SCRIPT_NAME, $FACE;

        $this->refreshScreen("$SCRIPT_NAME?Confirm=1&FACE=$FACE");
    }


    function Save()
    {
        global $DBConn;
        global $Params, $HTTP_HOST, $_SERVER;

        $this->User = 0;

        $NParams = $Params;
        if (!is_array($Params) || count($Params) == 0) {
            return 0;
        }

        $NParams[signaddress]    = $_SERVER[REMOTE_ADDR];
        $NParams[useragent]      = $_SERVER[HTTP_USER_AGENT];
        if ($this->r_dm->trialsignup()) {
            $NParams[usertype]   = "Trial";

            $hist =& $History[];
            $hist[date] = GetCurrDate();
            $hist[mess] = "Created as trial user";
            $NParams[history]    = serialize($History);
        } else {
            $NParams[edenaid] = 1;

            $hist =& $History[];
            $hist[date] = GetCurrDate();
            $hist[mess] = "Created as signup user";
            $NParams[history]    = serialize($History);
        }

        while (list($n, $v) = each($NParams)) {
            $NParams[$n] = URLEncode($v);
        }

        $r_dm = DBFind("domain", "name = '$HTTP_HOST'", "", __LINE__);
        $User = NextVal("usr_seq");
        DBExec("INSERT INTO usr (sysnum, sysnumdomain, creat, lev) VALUES ($User, " . $r_dm->sysnum() . ", 'now'::abstime, 0)", __LINE__);
        DBExec("UPDATE usr SET name = '$NParams[name]' WHERE sysnum = $User", __LINE__);

        $r_dm = DBFind("domain, usr", "usr.sysnumdomain = domain.sysnum and domain.name = '$HTTP_HOST' and usr.name = '$NParams[name]'", "", __LINE__);
        if ($r_dm->NumRows() != 1) {
            $this->ErrList[name] = $TEMPL[err_mes4];
            unset($this->ChList[name]);
            DBExec("DELETE usr WHERE sysnum = $User", __LINE__);
            return 0;
        }

        if ($this->r_dm->trialsignup()) {
            DBExec("UPDATE usr SET quote = '" . 5 * 1024 * 1024 . "' WHERE sysnum = $User", __LINE__);
        }


        DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Inbox',       1, 'd')", __LINE__);
        DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Sent Items',  2, 'd')", __LINE__);
        DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Trash',       5, 'd')", __LINE__);

        $UsrFields = array("name", "password");
        while (list($n, $Name) = each($UsrFields)) {
                DBExec("UPDATE usr SET $Name = '$NParams[$Name]' WHERE sysnum = $User", __LINE__);
                unset ($NParams[$Name]);
        }
        DBExec("UPDATE usr SET mod = 'now'::abstime WHERE sysnum = $User", __LINE__);

        DBExec("DELETE FROM usr_ua WHERE sysnumusr = $User", __LINE__);

        if (count($NParams) != 0) {
            reset($NParams);
            while (list($Name, $Value) = each($NParams)) {
                if($Value != "") {
                        $nset = 0;
                        $len = 120;
                        do {
                            $TMP = substr($Value, 0, $len);
                            DBExec("INSERT INTO usr_ua (sysnumusr, name, value, nset) VALUES ($User, '$Name', '$TMP', '$nset')", __LINE__);

                            $Value = substr($Value, $len);
                            $nset++;
                        } while(strlen($Value) > 0);
                }
            }
        }

        $this->User = $User;
        return $User;
    }


    function ViewComplete()
    {
        global $TEMPL;
        global $Params, $HTTP_HOST, $INET_SRC, $INET_ROOT;

        $mailfrom = $Params[name] . "@" . $HTTP_HOST;

        if($this->r_dm->trialsignup()) {
            $MessageText = "<b>New Trial User</b><br>";
        } else {
            $MessageText = "<b>New User</b><br>";
        }


        $MessageText .= "<u>user  name</u> {$Params[name]}<br>" .
                        "<u>First Name</u> {$Params[firstname]}<br>" .
                        "<u>Last  Name</u> {$Params[lastname]}<br>" .
                        "<u>IP</u>         {$GLOBALS[REMOTE_ADDR]}<hr>";

        $r = DBExec(" SELECT " .
                    "    fld.sysnum AS ToFLD, " .
                    "    usr.*, " .
                    "    u1.value as firstname, " .
                    "    u2.value as lastname " .
                    " FROM " .
                    "    usr " .
                    "       LEFT JOIN usr_ua u1 ON usr.sysnum = u1.sysnumusr AND " .
                    "                              u1.name = 'firstname' " .
                    "       LEFT JOIN usr_ua u2 ON usr.sysnum = u2.sysnumusr AND " .
                    "                              u2.name = 'lastname', " .
                    "    domain, fld " .
                    " WHERE fld.sysnumusr = usr.sysnum AND " .
                    "    usr.sysnumdomain = domain.sysnum AND " .
                    "    domain.name = '$HTTP_HOST' AND " .
                    "    usr.lev >= 1 AND " .
                    "    fld.ftype = '1'", __LINE__);

        $adm = "";
        while ( ! $r->eof()) {
            $mailto = $r->name() . "@" . $HTTP_HOST;

            $name = URLDecode($r->firstname());
            if ($r->lastname() != "") {
                $name .= ($name != "" ? " " : "") . URLDecode($r->lastname());
            }

            $adm .= "<tr><td class=body>&nbsp;{$name}&nbsp;</td><td class=body>&nbsp;<a href='mailto:$mailto'><span class=body><u>$mailto</u></span></a>&nbsp;</td></tr>";
            mail($mailto, "New User", "<html><body>{$MessageText}<a href='$INET_SRC/list_users.php?UID=" . $r->sysnum() . "&FACE=en&DOMAIN=" . $r->sysnumdomain() . "&vEdit_" . $this->User . "=1'>Edit this user</a></body></html>", "Content-Type : Text/HTML\r\nFrom: $mailfrom\r\nReply-To: $mailfrom", "-f$mailfrom");
            $r->next();
        }
        $adm = "<table>$adm</table>";

        if ($this->r_dm->admin() != "") {
            $mailto = $this->r_dm->admin();
            $adm = "<a href='mailto:$mailto'><span class=body><u>$mailto</u></span></a>";
        }

        if($this->r_dm->trialsignup()) {
            $mes = $TEMPL[reg_mes_trial];
        } else {
            $mes = $TEMPL[reg_mes];
        }

        $mes = eregi_replace("[#]acc[#]",  $Params[name], $mes);
        $mes = eregi_replace("[#]mail[#]", $mailfrom, $mes);
        $mes = eregi_replace("[#]adm[#]",  $adm, $mes);
        $mes = eregi_replace("[#]link[#]",  "<a href='$INET_ROOT'><span class='body'><u>$HTTP_HOST</u></span></a>", $mes);
        $this->out("<p class='body'>", $mes, "</p>");

        $this->OUT( "<form method='POST' name='completeform'>" );
        $this->OUT("<center>");
        $this->OUT( "<form method='POST' name='completeform'>" );
        $this->OUT(makeButton("type=1& name=sCancel&  form=completeform& value=$TEMPL[bt_to_login]&  title=$TEMPL[bt_to_login_ico]"));
        $this->OUT("</center>");
        $this->OUT( "</form>" );
    }

    function Authorize()
    {
    }


    function Referens()
    {
    }

    function mes()
    {
        if ($this->ErrList[Mes]) {
            $this->ErrMes($this->ErrList[Mes]);
        }
    }

    function UserName()
    {
        $this->out("&nbsp;");
    }

}

ConnectToDB();

$SignUsr = new CSignUsr();
$SignUsr->run();

UnconnectFromDB();
exit;

?>
