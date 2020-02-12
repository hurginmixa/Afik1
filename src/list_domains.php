<?php

require("file.inc.php");
require("screen.inc.php");
require("db.inc.php");

class CListDomainScreen extends screen
{

    function CListDomainScreen()
    {
        screen::screen(); // inherited constructor
        $this->SetTempl("list_domain");
        session_register("s_ListDomain");

        $this->disk_size_measure = array("B" => 1, "KB" => 1024, "MB" => (1024 * 1024), "GB" => (1024 * 1024 * 1024));

        if ($this->USR->lev() < 1) {
            $this->out("<h1>Access denited !</h1>");
            exit;
        }

        $this->Trans("vEdit",   "NumEdit");
        $this->Trans("vDelete", "NumDelete");

        $this->PgTitle = "<b>List domains</b>";

        $this->Request_actions["vNew"]        = "SetView(vNew)";
        $this->Request_actions["vEdit"]       = "SetView(vEdit)";
        $this->Request_actions["vDelete"]     = "SetView(vDelete)";

        $this->Request_actions["sCreate"]     = "CreateNewDomain()";
        $this->Request_actions["sEdit"]       = "EditDomain()";
        $this->Request_actions["sDelete"]     = "DeleteDomain()";
        $this->Request_actions["sCancel"]     = "CancelAction()";
        $this->Request_actions["sExit"]       = "ExitFromScreen()";
    }

    function Scr()
    {
        global $View;
        global $vEdit;

        switch ($View) {
            case vNew    : $this->ScrNew();     break;
            case vEdit   : $this->ScrEdit();    break;
            case vDelete : $this->ScrDelete();  break;
            default      : $this->ScrList();    break;
        }
    }

    function ScrList()
    {
        global $View, $FACE, $INET_IMG, $TEMPL;

        $o = $this->USR->lev() == 1 ? "sysnum = " . $this->USR->sysnumdomain() : "1=1";
        $r_dm = DBFind("domain", "$o order by sysnum", "", __LINE__);
        $r_usr = DBFind("usr", "sysnumdomain = domain.sysnum group by sysnumdomain", "sysnumdomain, count(sysnum) as cusr", __LINE__);

        $this->out("<Form name='FormDomain' method='post'>");

        $this->out("<input type='hidden' name='View' value='$View'>");

        $this->SubTable("width='100%' border = 0 cellspacing = 0 cellpadding='5'"); {
            $this->TRNext(); {
                $this->TDNext("class='toolsbarl'"); {
                    if ($this->USR->lev() == 2) {
                        $this->out(makeButton("type=1& name=vNew& value=$TEMPL[bt_new_domain]") . $this->ButtonBlank);
                    }
                    $this->out(makeButton("type=1& name=sExit& value=$TEMPL[bt_exit]") . "<br>");
                }
            }
        } $this->SubTableDone();

        $this->SubTable("width='100%'"); {
            $this->trs(0, "class='ttp'"); {
                $this->tds(0,  10, "class='ttp' width='5%'",  "&nbsp;$TEMPL[lb_num]&nbsp;");
                $this->tds(0,  20, "class='ttp'",             "&nbsp;<img src='$INET_IMG/edit.gif'>&nbsp;");
                $this->tds(0,  30, "class='ttp' width='20%'", "&nbsp;$TEMPL[lb_name_domain]&nbsp;");
                $this->tds(0,  40, "class='ttp' width='20%'", "&nbsp;$TEMPL[lb_admin_email]&nbsp;");
                $this->tds(0,  50, "class='ttp' width='5%'",  "&nbsp;$TEMPL[lb_UID]&nbsp;");
                $this->tds(0,  55, "class='ttp' width='5%'",  "&nbsp;$TEMPL[lb_max_user]&nbsp;");
                $this->tds(0,  60, "class='ttp' width='5%'",  "&nbsp;$TEMPL[lb_count_users]&nbsp;");
                $this->tds(0,  70, "class='ttp' width='10%'", "&nbsp;$TEMPL[lb_user_quote]&nbsp;");
                $this->tds(0,  80, "class='ttp' width='15%'", "&nbsp;$TEMPL[lb_quote]&nbsp;");
                $this->tds(0,  90, "class='ttp' width='15%'", "&nbsp;$TEMPL[lb_disk_usage]&nbsp;");
                $this->tds(0, 100, "class='ttp'",             "&nbsp;<img src='$INET_IMG/delete1.gif'>&nbsp;");
            }

            for($i=1; !$r_dm->Eof(); $i++) {
                $this->trs($i, "class='tlp'"); {
                    $this->tds($i,  10, "nowrap", $i);
                    $this->tds($i,  20, "nowrap", "<input type='IMAGE' src='$INET_IMG/edit.gif' border=0 alt='Edit' name=vEdit_".$r_dm->sysnum().">");
                    $this->tds($i,  30, "nowrap", "<a href='list_users.php?UID=$this->UID&FACE=$FACE&DOMAIN=".$r_dm->sysnum()."'><font class='tlpa'>".$r_dm->name()."</font></a>");
                    $this->tds($i,  40, "nowrap", $r_dm->admin());
                    $this->tds($i,  50, "nowrap", $r_dm->sysnum());
                    $this->tds($i,  55, "nowrap", $r_dm->maxusrnum());
                    $this->tds($i,  60, "nowrap", $r_usr->Find("sysnumdomain", $r_dm->sysnum()) != -1 ? $r_usr->cusr() : $this->nbsp("Empty"));
                    $this->tds($i,  70, "nowrap", $this->nbsp("<span title='" . $r_dm->userquote() . " bytes'>" . ($r_dm->userquote() != 0 ? AsSize($r_dm->userquote()) : "") . "</span>"));
                    $this->tds($i,  80, "nowrap", $this->nbsp("<span title='" . $r_dm->quote() . " bytes'>" . ($r_dm->quote() != 0 ? AsSize($r_dm->quote()) : "") . "</span>"));
                    $this->tds($i,  90, "nowrap", $this->nbsp("<span title='" . $r_dm->diskusage() . " bytes'>" . AsSize($r_dm->diskusage()) . "</span>"));
                    $this->tds($i, 100, "nowrap", "<input type='IMAGE' src='$INET_IMG/delete1.gif' border=0 alt='Delete' name=vDelete_".$r_dm->sysnum().">");
                }
                $r_dm->Next();
            }
        } $this->SubTableDone();

        $this->out("</Form>");
    }



    function ScrNew()
    {
        global $View, $NameDomain, $ComentDomain, $AdminDomain, $TEMPL;

        $this->out("<Form method='post'>");
        $this->out("<input type='hidden' name='View' value='$View'>");

        //$this->out("<Center>");

        $this->SubTable("width='100%' class='tab' cellpadding='5'"); {

            $this->TRNext("class='toolsbarl'"); {
                $this->TDNext("colspan='2'"); {
                    $this->out(makeButton("type=1& name=sCreate& value=$TEMPL[bt_creat]") . $this->ButtonBlank);
                    $this->out(makeButton("type=1& name=sCancel& value=$TEMPL[bt_cancel]"));
                }
            }

            $this->TRNext("class='tlp'"); {
                $this->TDNext("width='15%' nowrap"); {
                    $this->out($TEMPL[lb_creat_title] .  " :" . $this->TextShift);
                }
                $this->TDNext("width='85%'"); {
                    $this->out("<input type='text' name='NameDomain' value='$NameDomain' class='toolsbare'>");
                }
            }

            $this->TRNext("class='tlp'"); {
                $this->TDNext("width='15%' nowrap"); {
                    $this->out($TEMPL[lb_coment] .  " :" . $this->TextShift);
                }
                $this->TDNext("width='85%'"); {
                    $this->out("<input type='text' name='ComentDomain' value='$ComentDomain' class='toolsbare' size=50>");
                }
            }

            $this->TRNext("class='tlp'"); {
                $this->TDNext("width='15%' nowrap"); {
                    $this->out($TEMPL[lb_admin_email] .  " :" . $this->TextShift);
                }
                $this->TDNext("width='85%'"); {
                    $this->out("<input type='text' name='AdminDomain' value='$AdminDomain' class='toolsbare' size=50>");
                }
            }

        } $this->SubTableDone();

        //$this->out("</Center>");

        $this->out("</Form>");
    }



    function ScrEdit()
    {
        global $View, $NumEdit, $Mes, $NameDomain, $ComentDomain, $AdminDomain, $Quote, $Quote_measure, $UserQuote;
        global $SignNewUsr, $TrialSignUp, $UserQuote_measure, $MaxUserNum;
        global $TEMPL, $INET_IMG;

        $r_dm = DBFind("domain", "sysnum = $NumEdit", "", __LINE__);

        if ($Mes == 0) {
            $NameDomain = $r_dm->name();
            $ComentDomain = $r_dm->coment();
            $AdminDomain = $r_dm->admin();

            $Quote      = $r_dm->quote();
            $TMP = preg_replace("/(^ +)|( +$)/", "", AsSize($Quote));
            if($TMP != "" && preg_match("/^(.+?)(&nbsp;)+(.+?)$/", $TMP, $MATH)) {
                $Quote         = $MATH[1];
                $Quote_measure = $MATH[3];
            } else {
                $Quote         = 0;
                $Quote_measure = "B";
            }

            $UserQuote  = $r_dm->userquote();
            $TMP = preg_replace("/(^ +)|( +$)/", "", AsSize($UserQuote));
            if($TMP != "" && preg_match("/^(.+?)(&nbsp;)+(.+?)$/", $TMP, $MATH)) {
                $UserQuote         = $MATH[1];
                $UserQuote_measure = $MATH[3];
            } else {
                $UserQuote         = 0;
                $UserQuote_measure = "B";
            }

            $SignNewUsr = $r_dm->signup();

            $TrialSignUp = $r_dm->trialsignup();

            $MaxUserNum = $r_dm->maxusrnum();
        }

        $this->out("<Form method='post'>");
        $this->out("<input type='hidden' name='View' value='$View'>");
        $this->out("<input type='hidden' name='NumEdit' value='$NumEdit'>");

        $this->out("<span class='body'><font size='+2'><b>", $TEMPL[lb_edit_title], " ", $r_dm->name(), "</b></font></span>");

        $this->SubTable("width='100%' cellpadding='5' cellspacing='0'"); {
            $this->TRNext("class='toolsbarl'"); {
                $this->out(makeButton("type=1& name=sEdit& value=$TEMPL[bt_edit]") . $this->ButtonBlank);
                $this->out(makeButton("type=1& name=sCancel& value=$TEMPL[bt_cancel]"));
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

            $this->SubTable("width='100%' class='toolsbarl' cellpadding='5'"); {
                $this->TRNext(); {
                    $this->TDNext("class='tlp' width='20%'"); {
                        $this->out($this->TextShift, $TEMPL[lb_edit_name], $this->TextShift);
                    }
                    $this->TDNext("class='tla' width='80%'"); {
                        $this->out($this->TextShift, "<input type='text'   name='NameDomain' value=\"" . HtmlSpecialChars($NameDomain) . "\" class='toolsbare'>", $this->TextShift);
                    }
                }

                $this->TRNext(); {
                    $this->TDNext("class='tlp' width='20%'"); {
                        $this->out($this->TextShift, $TEMPL[lb_coment], $this->TextShift);
                    }
                    $this->TDNext("class='tla' width='80%'"); {
                        $this->out($this->TextShift, "<input type='text'   name='ComentDomain' value=\"" . HtmlSpecialChars($ComentDomain) . "\" class='toolsbare' size=50>", $this->TextShift);
                    }
                }

                $this->TRNext(); {
                    $this->TDNext("class='tlp' width='20%'"); {
                        $this->out($this->TextShift, $TEMPL[lb_admin_email], $this->TextShift);
                    }
                    $this->TDNext("class='tla' width='80%'"); {
                        $this->out($this->TextShift, "<input type='text'   name='AdminDomain' value=\"" . HtmlSpecialChars($AdminDomain) . "\" class='toolsbare' size=50>", $this->TextShift);
                    }
                }

                $this->TRNext(); {
                    $this->TDNext("class='tlp'"); {
                        $this->out($this->TextShift, $TEMPL[lb_edit_quote], $this->TextShift);
                    }
                    $this->TDNext("class='tla'"); {
                        $this->out($this->TextShift, "<input type='text'   name='Quote' value='$Quote' class='toolsbare'>", $this->TextShift);
                        $this->out("<select name='Quote_measure' class='toolsbare'>"); {
                            $this->out("<option value='B'"  . ($Quote_measure == "B"  ? " SELECTED" : "") . ">B </option>");
                            $this->out("<option value='KB'" . ($Quote_measure == "KB" ? " SELECTED" : "") . ">KB</option>");
                            $this->out("<option value='MB'" . ($Quote_measure == "MB" ? " SELECTED" : "") . ">MB</option>");
                            $this->out("<option value='GB'" . ($Quote_measure == "GB" ? " SELECTED" : "") . ">GB</option>");
                        } $this->out("</select>");
                    }
                }

                $this->TRNext(); {
                    $this->TDNext("class='tlp'"); {
                        $this->out($this->TextShift, $TEMPL[lb_edit_user_quote], $this->TextShift);
                    }
                    $this->TDNext("class='tla'"); {
                        $this->out($this->TextShift, "<input type='text'   name='UserQuote' value='$UserQuote' class='toolsbare'>", $this->TextShift);
                        $this->out("<select name='UserQuote_measure' class='toolsbare'>"); {
                            $this->out("<option value='B'"  . ($UserQuote_measure == "B"  ? " SELECTED" : "") . ">B </option>");
                            $this->out("<option value='KB'" . ($UserQuote_measure == "KB" ? " SELECTED" : "") . ">KB</option>");
                            $this->out("<option value='MB'" . ($UserQuote_measure == "MB" ? " SELECTED" : "") . ">MB</option>");
                            $this->out("<option value='GB'" . ($UserQuote_measure == "GB" ? " SELECTED" : "") . ">GB</option>");
                        } $this->out("</select>");
                    }
                }

                $this->TRNext(); {
                    $this->TDNext("class='tlp'"); {
                        $this->out($this->TextShift, $TEMPL[lb_edit_signup], $this->TextShift);
                    }
                    $this->TDNext("class='tla'"); {
                        $this->out($this->TextShift, "<input type='checkbox' name='SignNewUsr' value='on'" . ($SignNewUsr != "0" ? " checked" : "") . ">", $this->TextShift);
                    }
                }

                $this->TRNext(); {
                    $this->TDNext("class='tlp'"); {
                        $this->out($this->TextShift, $TEMPL[lb_edit_trialuser], $this->TextShift);
                    }
                    $this->TDNext("class='tla'"); {
                        $this->out($this->TextShift, "<input type='checkbox' name='TrialSignUp' value='on'" . ($TrialSignUp != "0" ? " checked" : "") . ">", $this->TextShift);
                    }
                }

                $this->TRNext(); {
                    $this->TDNext("class='tlp'"); {
                        $this->out($this->TextShift, $TEMPL[lb_max_user], $this->TextShift);
                     }
                    $this->TDNext("class='tla'"); {
                        $this->out($this->TextShift, "<input type='text'   name='MaxUserNum' value=\"" . HtmlSpecialChars($MaxUserNum) . "\" class='toolsbare'>", $this->TextShift);
                    }
                }

            } $this->SubTableDone();

        $this->out("</Form>");
    }



    function ScrDelete()
    {
      global $View, $NumDelete, $INET_IMG;


      $r_dm = DBFind("domain", "sysnum = $NumDelete", "", __LINE__);

      $this->out("<Form method='post'>");
      if ($r_dm->NumRows() == 1) {
          $this->out("<input type='hidden' name='View' value='$View'>");
          $this->out("<input type='hidden' name='NumDelete' value='$NumDelete'>");

          $this->out("<center>");
          $this->out("<img src='$INET_IMG/attension.gif'><br>");
          $this->out("<font color='#ff0000' size='+1'>Delete?</font><br>");
          $this->out("<h1>".$r_dm->name()."</h1><br>");
          $this->out(makeButton("type=1& name=sDelete& value=Delete") . $this->ButtonBlank);
          $this->out(makeButton("type=1& name=sCancel& value=Cancel"));
          $this->out("</center>");
      } else {
          $this->out("<center>");
          $this->out("<h1>Domain not found</h1><br>");
          $this->out(makeButton("type=1& name=sCancel& value=Cancel"));
          $this->out("</center>");
      }
      $this->out("</Form>");
    }

    function SetView($k)
    {
      global $View;
      $View = $k;
    }


    function CreateNewDomain()
    {
        global $View, $NameDomain, $ComentDomain, $AdminDomain, $Mes;
        global $DEFAULT_DOMAIN_DISK_QUOTE, $DEFAULT_USER_DISK_QUOTE;

        if ($this->USR->lev() != 2) {
            $View = "";
            return;
        }

        if ($NameDomain == "") {
            $Mes = 1;
            return;
        }

        if(!preg_match("/^[a-z0-9][a-z0-9\-\_]*(\.[a-z0-9][a-z0-9\-\_]*)+$/i", $NameDomain)) {
            $Mes = 3;
            return;
        }

        if(strlen($NameDomain) > 30) {
            $Mes = 9;
            return;
        }

        if(!preg_match("/^[a-z0-9.,_ '\-]*$/i", $ComentDomain)) {
            $Mes = 7;
            return;
        }

        if(strlen($ComentDomain) > 50) {
            $Mes = 10;
            return;
        }

        if($AdminDomain != "" && !is_emailaddress($AdminDomain)) {
            $Mes = 11;
            return;
        }

        if (!eregi("@", $AdminDomain)) {
            $AdminDomain .= "@" . $this->DOMAIN->name();
        }

        $r_dm = DBFind("domain", "name = '$NameDomain'", "", __LINE__);
        if ($r_dm->NumRows() != 0) {
            $Mes = 2;
            return;
        }

        DBExec("insert into domain (sysnum, name, diskusage, quote, userquote, signup, coment, admin) values (NextVal('domain_seq'), '$NameDomain', 0, $DEFAULT_DOMAIN_DISK_QUOTE, $DEFAULT_USER_DISK_QUOTE, '0', '$ComentDomain', '$AdminDomain')", __LINE__);
        // echo "<pre>$SQL</pre><br>";

        $this->CancelAction();
    }


    function EditDomain()
    {
        global $View, $NameDomain, $ComentDomain, $AdminDomain, $Quote, $Quote_measure, $UserQuote, $UserQuote_measure;
        global $SignNewUsr, $TrialSignUp, $Mes, $NumEdit, $MaxUserNum;

        if ($this->USR->lev() != 2) {
            $View = "";
            return;
        }

        if ($NameDomain == "") {
            $Mes = 1;
            return;
        }

        if (!preg_match("/^[a-z0-9][a-z0-9\-\_]*(\.[a-z0-9][a-z0-9\-\_]*)+$/i", $NameDomain)) {
            $Mes = 3;
            return;
        }

        if(strlen($NameDomain) > 30) {
            $Mes = 9;
            return;
        }

        if(!preg_match("/^[a-z0-9.,_ \-]*$/i", $ComentDomain)) {
            $Mes = 7;
            return;
        }

        if(strlen($ComentDomain) > 50) {
            $Mes = 10;
            return;
        }

        if($AdminDomain != "" && !is_emailaddress($AdminDomain)) {
            $Mes = 11;
            return;
        }

        if (!eregi("@", $AdminDomain)) {
            $AdminDomain .= "@" . $this->DOMAIN->name();
        }

        $NewQuote = round($Quote * $this->disk_size_measure[$Quote_measure]);
        if (!preg_match("/^[0-9]*$/", $NewQuote)) {
            $Mes = 4;
            return;
        }

        $NewUserQuote = round($UserQuote * $this->disk_size_measure[$UserQuote_measure]);
        if (!preg_match("/^[0-9]*$/", $NewUserQuote)) {
            $Mes = 5;
            return;
        }

        if ($MaxUserNum == "") {
            $MaxUserNum = 0;
        }
        if (!preg_match("/^[0-9]+$/", $MaxUserNum)) {
            $Mes = 6;
            return;
        }

        $r_dm = DBFind("domain", "name = '$NameDomain' and sysnum <> $NumEdit", "", __LINE__);
        if ($r_dm->NumRows() != 0) {
            $Mes = 2;
            return;
        }

        $SignNewUsr = ($SignNewUsr != "" ? 1 : 0);

        $TrialSignUp = ($TrialSignUp != "" ? 1 : 0);

        if ($TrialSignUp && !$SignNewUsr) {
            $Mes = 8;
            return;
        }

        DBExec("UPDATE domain SET name     = '$NameDomain', " .
                                "coment    = '$ComentDomain', " .
                                "admin     = '$AdminDomain', " .
                                "quote     = '$NewQuote', " .
                                "userquote = '$NewUserQuote', " .
                                "signup    = '$SignNewUsr', " .
                                "trialsignup = '$TrialSignUp', " .
                                "maxusrnum = '$MaxUserNum' WHERE sysnum = $NumEdit", __LINE__);

        $this->CancelAction();
    }


    function DeleteDomain()
    {
        global $View, $NumDelete;

        if ($this->USR->lev() != 2) {
            $View = "";
            return;
        }

        DelDomain($NumDelete);

        $this->CancelAction();
    }


    function CancelAction()
    {
        global $INET_SRC, $FACE;
        header("Location: $INET_SRC/list_domains.php?UID=$this->UID&FACE=$FACE");
        exit;
    }


    function ExitFromScreen()
    {
        global $INET_SRC, $FACE;
        header("Location: $INET_SRC/admin_opt.php?UID=$this->UID&FACE=$FACE");
        exit;
    }


    function mes()
    {
        global $Mes, $MesParam, $s_ListDomain, $TEMPL;


        if ($Mes == "") {
            $Mes = $s_ListDomain[Mes];
            unset($s_ListDomain[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_ListDomain[MesParam];
            unset($s_ListDomain[MesParam]);
        }

        if ($Mes == "") {
            return;
        }

        if ($TEMPL[err_mes . $Mes] != "") {
          $this->ErrMes(sprintf($TEMPL[err_mes . $Mes], $MesParam));
        } else {
          $this->ErrMes(sprintf("Unknow error number %s %s", $Mes, $MesParam));
        }
    }

} // end of class CListDomainScreen

ConnectToDB();

$ListDomainScreen = new CListDomainScreen();
$ListDomainScreen->Run();

UnconnectFromDB();
exit;
?>
