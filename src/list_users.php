<?php

/*

class myscreen extends screen
    function myscreen()
    function Scr()
        function ScrList()
        function ScrNew()
        function ScrEdit()
        function ScrDelete()
    function SetView($k)
    function GetSortIcons($ord)
    function Sort()
    function Input()
    function sCreate()
    function sEdit()
    function ReadDB($NumEdit)
    function WriteDB($NumEdit, $Params)
    function sDelete()
    function sCancel()
    function sExit()
    function sSetSelecterChar()
    function mes()
    function refreshScreen()
*/



require("file.inc.php");
require("screen.inc.php");
require("db.inc.php");

class CListUsersScreen extends screen
{
    function CListUsersScreen()
    {
        global $DOMAIN, $TEMPL, $s_ListUsers;

        screen::screen(); // inherited constructor

        $this->SetTempl("list_users");
        session_register("s_ListUsers");

        $this->Numer = $DOMAIN;

        $this->disk_size_measure = array("B" => 1, "KB" => 1024, "MB" => (1024 * 1024), "GB" => (1024 * 1024 * 1024));

        if ($this->USR->lev() < 1) {
            $this->out("<h1>$TEMPL[access_denied] !</h1>");
            exit;
        }

        if ($this->USR->lev() == 1 && $this->USR->sysnumdomain() != $DOMAIN) {
            $this->out("<h1>$TEMPL[access_denied] !</h1>");
            exit;
        }

        $this->Trans("vEdit",   "NumEdit");
        $this->Trans("vDelete", "NumDelete");
        $this->Trans("vInput",  "NumInput");
        $this->Trans("vSort",   "NumSort");

        $R_DM = DBFind("domain", "sysnum = $DOMAIN", "", __LINE__);
        $this->PgTitle = "<b>$TEMPL[list_user] <u>".$R_DM->name()."</u></b>";

        $this->Request_actions["sGetSelectedChar"]  = "sSetSelecterChar()";
        $this->Request_actions["sCancel"]           = "sCancel()";
        $this->Request_actions["sExit"]             = "sExit()";
        $this->Request_actions["sCreate"]           = "sCreate()";
        $this->Request_actions["sEdit"]             = "sEdit()";
        $this->Request_actions["sDelete"]           = "sDelete()";
        $this->Request_actions["sPrevScreen"]       = "sNextPrevScreen(-1)";
        $this->Request_actions["sNextScreen"]       = "sNextPrevScreen(1)";
        $this->Request_actions["vNew"]              = "SetView(vNew)";
        $this->Request_actions["vEdit"]             = "SetView(vEdit)";
        $this->Request_actions["vDelete"]           = "SetView(vDelete)";
        $this->Request_actions["vSort"]             = "Sort()";
        $this->Request_actions["vInput"]            = "Input()";
    }

    function Scr()
    {
        global $View;
        global $vEdit;

        switch ($View) {
            case "vNew"    :
                            $this->ScrNew();
                            break;
            case "vEdit"   :
                            $this->ScrEdit();
                            break;
            case "vDelete" :
                            $this->ScrDelete();
                            break;
            default        :
                            //$this->SubTable("border=1"); {
                            //    $this->out("=$View=");
                            //} $this->SubTableDone();

                            $this->ScrList();
                            break;
        }
    }


    function ScrList()
    {
        global $View, $Sort, $TEMPL, $INET_IMG, $NumPage, $SelectedChar;

        $LinePerScreen = 50;
        $LettersList = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");

        if ($Sort == "") {
            $Sort = "n";
        }

        switch ($Sort) {
            case "n" :
            case "N" : $order = "name";      break;
            case "f" :
            case "F" : $order = "fullname";  break;
            case "l" :
            case "L" : $order = "lev";       break;
            case "q" :
            case "Q" : $order = "quote";     break;
            case "d" :
            case "D" : $order = "diskusage"; break;
            case "u" :
            case "U" : $order = "sysnum";    break;
            case "e" :
            case "E" : $order = "lastenter"; break;
            case "x" :
            case "X" : $order = "denied";    break;
            default  : $order = "name";
        }

        if ($Sort <= "Z") {
            $order .= " DESC";
        }
        if ($order != "name") {
            $order .= ", name";
        }

        $r_usr = DBFind("usr " .
                            "left join usr_ua ua1 on usr.sysnum = ua1.sysnumusr and ua1.name = 'firstname' " .
                            "left join usr_ua ua2 on usr.sysnum = ua2.sysnumusr and ua2.name = 'lastname' " .
                            "left join usr_ua ua3 on usr.sysnum = ua3.sysnumusr and ua3.name = 'edenaid' " .
                            "left join usr_ua ua4 on usr.sysnum = ua4.sysnumusr and ua4.name = 'coment'",
                        "sysnumdomain = $this->Numer" . ( $this->USR->lev() == 1 ? " and ((lev <= 1) or (lev is NULL))" : "" ) . ( $SelectedChar != "" ? " AND (usr.name like '" . strtolower($SelectedChar) . "%' OR usr.name like '" . strtoupper($SelectedChar) . "%')" : "") . " order by $order",
                        "usr.*, ua3.value as denied, (COALESCE(ua1.value, '') || ' ' || COALESCE(ua2.value, '')) as fullname, ua4.value as coment", __LINE__);


        if ($NumPage == "") {
            $NumPage = 0;
        }

        $MaxNumPage = (int)(($r_usr->NumRows() - 1) / $LinePerScreen);

        if ($NumPage > $MaxNumPage) {
            $NumPage = $MaxNumPage;
        }

        $r_usr->Set($LinePerScreen * $NumPage);

        $this->out("<form method='post' name='UserList'>");
        $this->out("<input type='hidden' name='sGetSelectedChar'>");

        $this->SubTable("width='100%' class='toolsbarl' cellpadding=3"); {
            $this->out("<input type='submit' name='vNew' value='$TEMPL[new_user]' class='toolsbarb' title='$TEMPL[new_user_ico]'>" . $this->ButtonBlank);
            $this->out("<input type='submit' value='$TEMPL[cancel]' name='sExit' class='toolsbarb' title='$TEMPL[cancel_ico]'>" . $this->ButtonBlank);
            if ($NumPage > 0) {
                $this->out(makeButton("type=1& form=UserList& name=sPrevScreen& img=$INET_IMG/arrowleft-passive.gif?FACE=$FACE& imgact=$INET_IMG/arrowleft.gif?FACE=$FACE& title=$TEMPL[bt_prev_ico]") . $this->ButtonBlank);
            } else {
                $this->out("<img src='$INET_IMG/arrowleft-unactive.gif?FACE=$FACE' align='absmiddle' title='$TEMPL[bt_prev_ico]'>" . $this->ButtonBlank);
            }

            if ($NumPage != $MaxNumPage) {
                $this->out(makeButton("type=1& form=UserList& name=sNextScreen& img=$INET_IMG/arrowright-passive.gif?FACE=$FACE& imgact=$INET_IMG/arrowright.gif?FACE=$FACE& title=$TEMPL[bt_next_ico]") . $this->ButtonBlank);
            } else {
                $this->out("<img src='$INET_IMG/arrowright-unactive.gif?FACE=$FACE' align='absmiddle' title='$TEMPL[bt_next_ico]'>" . $this->ButtonBlank);
            }
            $this->out("Page <b>" . ($NumPage + 1) . "</b> From <b>" . ($MaxNumPage + 1) . "</b>");
        } $this->SubTableDone();


        $this->out("<img src='$INET_IMG/filler3x1.gif' border=0>");

        $this->SubTable("width='100%' class='toolsbarl' cellpadding=3"); {
            $this->out("<a href='javascript:document.UserList.sGetSelectedChar.value=\"All\"; document.UserList.submit();'><font class='toolsbara'>All</font></a> &nbsp");

            reset($LettersList);
            while (list($n, $v) = each($LettersList)) {
                $this->out("<a href='javascript:document.UserList.sGetSelectedChar.value=\"$v\"; document.UserList.submit();'><font class='toolsbara'>$v</font></a>&nbsp;");
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif' border=0>");



        $this->SubTable("width='100%'"); {
            $this->trs(0, "class='ttp'"); {
                $this->tds(0,  0, "class='ttp' width='1%'  nowrap", "N");
                $this->tds(0,  1, "class='ttp' width='1%'  nowrap", "<img src='$INET_IMG/edit.gif' border=0 alt='Edit'>");
                $this->tds(0,  2, "class='ttp' width='1%'  nowrap", "<img src='$INET_IMG/view.gif' border=0 alt='View'>");
                $this->tds(0,  3, "class='ttp' width='10%' nowrap", "&nbsp;$TEMPL[user_name]&nbsp;" . $this->GetSortIcons("n"));
                $this->tds(0,  4, "class='ttp' width='5%'  nowrap", "&nbsp;$TEMPL[level]&nbsp;" . $this->GetSortIcons("l"));
                $this->tds(0,  5, "class='ttp' width='5%'  nowrap", "&nbsp;$TEMPL[UID]&nbsp;" . $this->GetSortIcons("u"));
                $this->tds(0,  6, "class='ttp' width='20%' nowrap", "&nbsp;$TEMPL[full_name]&nbsp;" . $this->GetSortIcons("f"));
                $this->tds(0,  7, "class='ttp' width='10%' nowrap", "&nbsp;$TEMPL[quote]&nbsp;" . $this->GetSortIcons("q"));
                $this->tds(0,  8, "class='ttp' width='10%' nowrap", "&nbsp;$TEMPL[disk_usage]&nbsp;" . $this->GetSortIcons("d"));
                $this->tds(0,  9, "class='ttp' width='10%' nowrap", "&nbsp;$TEMPL[lastenter]&nbsp;" . $this->GetSortIcons("e"));
                $this->tds(0, 10, "class='ttp' width='10%' nowrap", "&nbsp;$TEMPL[denied]&nbsp;" . $this->GetSortIcons("x"));
                $this->tds(0, 11, "class='ttp' width='1%'  nowrap", "&nbsp;<img src='$INET_IMG/delete1.gif' border=0 alt='$TEMPL[delete]'>&nbsp;");
            }

            for($i = 1; !$r_usr->Eof() && $i <= $LinePerScreen; $i ++) {
                $this->trs($i, "class='tlp'"); {

                    $this->tds($i,  0, "nowrap", ($LinePerScreen * $NumPage + $i));
                    $this->tds($i,  1, "nowrap", "<input type='IMAGE' src='$INET_IMG/edit.gif' border=0 alt='$TEMPL[edit]' name=vEdit_".$r_usr->sysnum().">");
                    $this->tds($i,  2, "nowrap", "<input type='IMAGE' src='$INET_IMG/view.gif' border=0 alt='$TEMPL[view]' name=vInput_".$r_usr->sysnum().">");
                    $this->tds($i,  3, "nowrap", $this->nbsp($r_usr->name()));
                    $this->tds($i,  4, "nowrap", $this->nbsp($r_usr->lev()));
                    $this->tds($i,  5, "nowrap", $this->nbsp($r_usr->sysnum()));
                    $this->tds($i,  6, "",       ""); {
                        $this->out(htmlspecialchars(urldecode($r_usr->fullname())) . "&nbsp" );
                        if ($r_usr->coment() != "") {
                            $this->out(htmlspecialchars( "(" . urldecode($r_usr->coment()) . ")" ));
                        }
                    }
                    $this->tds($i,  7, "nowrap", "<span title='" . $r_usr->quote() . " bytes'>" . ($r_usr->quote() != 0 ? $this->nbsp( AsSize($r_usr->quote()) ) : "") . "</span>");
                    $this->tds($i,  8, "nowrap", "<span title='" . $r_usr->diskusage() . "'>" .  $this->nbsp(AsSize($r_usr->diskusage())) . "</span>");
                    $this->tds($i,  9, "nowrap", mkdate($r_usr->lastenter()));
                    $this->tds($i, 10, "nowrap", "<center><img src='$INET_IMG/" . ( $r_usr->denied() == "" ? "check_out.gif" : "check_in.gif") . "'></center>");
                    $this->tds($i, 11, "nowrap", "<input type='IMAGE' src='$INET_IMG/delete1.gif' border=0 alt='$TEMPL[delete]' name=vDelete_".$r_usr->sysnum().">");
                }

                $r_usr->Next();
            }

        } $this->SubTableDone();

        $this->out("</form>");
    }


    function GetSortIcons($ord)
    {
        global $INET_IMG;
        global $Sort;
        global $TEMPL;

        if ($Sort == "") {
            $Sort = "u";
        }

        $rez = "";

        if ($ord != $Sort) {
            $rez .= "<input type='IMAGE' src='$INET_IMG/sort1.gif' border=0 alt='$TEMPL[sort]' name='vSort_".$ord."'>";
        }

        if (strtoupper ($ord) != $Sort) {
            $rez .= "<input type='IMAGE' src='$INET_IMG/sort2.gif' border=0 alt='$TEMPL[sort]' name='vSort_".strtoupper ($ord)."'>";
        }

        return $rez;
    }


    function ScrNew()
    {
        global $View;
        global $Params, $Sort;
        global $TEMPL;
        global $INET_IMG;

        $NParams = $Params;
        if (is_array($NParams) && count($NParams) != 0) {
            reset($NParams);
            while (list($n, $v) = each($NParams)) {
                $NParams[$n] = htmlspecialchars($v);
            }
        }

        $this->out("<center>");
        $this->out("<form method='post'>");
        $this->out("<hr><h1><u>$TEMPL[new_user]</u></h1>");

        $this->SubTable("border=1"); {
            $this->SubTable("width='100%' class='toolsbarl' border=0"); {
                  $this->TDNext("align='center'"); {
                      $this->out("<input type='submit' value='$TEMPL[create]' name='sCreate' width='100' class='toolsbarb'>" .  $this->ButtonBlank);
                      $this->out("<input type='submit' value='$TEMPL[cancel]' name='sCancel' width='100' class='toolsbarb'>");
                  }
            } $this->SubTableDone();

            $this->out("<img src='$INET_IMG/filler2x1.gif'>");

            $this->SubTable("class='tla'"); {
                $this->tds(10, 0, "class='tlp'", "$TEMPL[user_name]");
                $this->tds(10, 1, "", "<input name='Params[name]' value=\"$NParams[name]\" size='43' class='toolsbare'>");

                $this->tds(20, 0, "class='tlp'", "$TEMPL[password]");
                $this->tds(20, 1, "", "<input type='password' name='Params[password]' value=\"$NParams[password]\" size='43' class='toolsbare'>");

                $this->tds(21, 0, "class='tlp'", "$TEMPL[coment]");
                $this->tds(21, 1, "", "<input name='Params[coment]' value=\"$NParams[coment]\" size='43' class='toolsbare'>");

                $this->tds(40, 0, "colspan=2", "<hr>");

                $this->tds(50, 0, "colspan=2", ""); {
                    $this->out("$TEMPL[l_level] : <br>");
                    if ($this->USR->lev() == 2) {
                        $this->out("<input type='radio' name='Params[lev]' value = '0' " . ($NParams[lev] == 0 ? "CHECKED" : "") . "> $TEMPL[user_l]" .  $this->TextShift);
                        $this->out("<input type='radio' name='Params[lev]' value = '1' " . ($NParams[lev] == 1 ? "CHECKED" : "") . "> $TEMPL[admin_l]" .  $this->TextShift);
                        $this->out("<input type='radio' name='Params[lev]' value = '2' " . ($NParams[lev] == 2 ? "CHECKED" : "") . "> $TEMPL[super_l]");
                    } else {
                        $this->out("<input type='checkbox' name='Params[lev]' value = '1' " . ($NParams[lev] == 1 ? "CHECKED" : "") . "> $TEMPL[domain_admin]");
                    }
                }

                $this->tds(6, 0, "colspan=2", "<hr>");

                $this->tds(70, 0, "class='tlp'", "$TEMPL[first_name]&nbsp;");
                $this->tds(70, 1, "", "<input name='Params[firstname]' value=\"$NParams[firstname]\" size='43' class='toolsbare'>");

                $this->tds(80, 0, "class='tlp'", "$TEMPL[last_name]&nbsp;");
                $this->tds(80, 1, "", "<input name='Params[lastname]' value=\"$NParams[lastname]\" size='43' class='toolsbare'>");

                $this->tds(85, 0, "class='tlp'", "$TEMPL[country]&nbsp;");
                $this->tds(85, 1, "", "<input name='Params[country]' value=\"$NParams[country]\" size='43' class='toolsbare'>");

                $this->tds(87, 0, "class='tlp'", "$TEMPL[city]&nbsp;");
                $this->tds(87, 1, "", "<input name='Params[city]' value=\"$NParams[city]\" size='43' class='toolsbare'>");

                $this->tds(90, 0, "class='tlp'", "$TEMPL[address]&nbsp;");
                $this->tds(90, 1, "", "<input name='Params[address]' value=\"$NParams[address]\" size='43' class='toolsbare'>");

                $this->tds(92, 0, "class='tlp'", "$TEMPL[zip]");
                $this->tds(92, 1, "", "<input name='Params[zip]' value=\"$NParams[zip]\" size='6' class='toolsbare'>");

                $this->tds(100, 0, "class='tlp'", "$TEMPL[e_mail]&nbsp;");
                $this->tds(100, 1, "", "<input name='Params[email]' value=\"$NParams[email]\" size='43' class='toolsbare'>");

                $this->tds(110, 0, "class='tlp'", "$TEMPL[phone]&nbsp;");
                $this->tds(110, 1, "", "<input name='Params[phone]' value=\"$NParams[phone]\" size='43' class='toolsbare'>");

                $this->tds(120, 0, "class='tlp'", "$TEMPL[quote]&nbsp;");
                $this->tds(120, 1, "", ""); {
                    $this->out("<input name='Params[quote]' value=\"$NParams[quote]\" size='25' class='toolsbare'>&nbsp;");
                    $this->out("<select name='Params[quote_measure]' class='toolsbare'>"); {
                        $this->out("<option value='B'"  . ($NParams[quote_measure] == "B"  ? " SELECTED" : "") . ">B </option>");
                        $this->out("<option value='KB'" . ($NParams[quote_measure] == "KB" ? " SELECTED" : "") . ">KB</option>");
                        $this->out("<option value='MB'" . ($NParams[quote_measure] == "MB" ? " SELECTED" : "") . ">MB</option>");
                        $this->out("<option value='GB'" . ($NParams[quote_measure] == "GB" ? " SELECTED" : "") . ">GB</option>");
                    } $this->out("</select>");
                }

                $this->tds(130, 0, "class='tlp'", "$TEMPL[disabled]&nbsp;");
                $this->tds(130, 1, "", "<input type='checkbox' name='Params[edenaid]' value = '1' " . ($NParams[edenaid] == 1 ? "CHECKED" : "") . ">");

                if ($this->USR->lev() == 2) {
                    $this->tds(140, 0, "class='tlp'", "$TEMPL[map_user]");
                    $this->tds(140, 1, "", "<input name='Params[luser]' value=\"$NParams[luser]\" size='43' class='toolsbare'>");
                }
            } $this->SubTableDone();
        } $this->SubTableDone();


        $this->out("</form>");
        $this->out("</center>");
    }



    function ScrEdit()
    {
        global $Mes, $s_ListUsers;
        global $View;
        global $NumEdit;
        global $Params, $Sort;
        global $TEMPL;
        global $INET_IMG;

        $NumEdit = (int)$NumEdit;

        if ($Mes == 0) {
            // read with check authorization of dministrator
            $Params = $this->ReadDB($NumEdit);
        }

        $NParams = $Params;

        if (is_array($NParams) && count($NParams) != 0) {
            reset($NParams);
            while (list($n, $v) = each($NParams)) {
                $NParams[$n] = htmlspecialchars($v);
            }
        } else {
            $View = "";
            $this->Log("PERMISSION DENIED N 3");
            $s_ListUsers[Mes] = 7;
            $this->refreshScreen();
        }

        $this->out("<form method='post'>");
        $this->out("<input type='hidden' name='Params[history]' value=\"$NParams[history]\">");
        $this->out("<center>");

        $this->out("<hr><h1><u>Edit user</u></h1>");


        $this->SubTable("border=1"); {
            $this->SubTable("width='100%' class='toolsbarl' border=0"); {
                $this->TDNext("align='center'"); {
                    $this->out("<input type='submit' value='$TEMPL[save]'   name='sEdit'   width='100' class='toolsbarb'>" . $this->ButtonBlank);
                    $this->out("<input type='submit' value='$TEMPL[cancel]' name='sCancel' width='100' class='toolsbarb'>");
                }
            } $this->SubTableDone();

            $this->out("<img src='$INET_IMG/filler2x1.gif'>");

            $this->SubTable("class='tla'"); {
                $this->tds(10, 0, "class='tlp'", "$TEMPL[user_name]");
                $this->tds(10, 1, "", "<input name='Params[name]' value=\"$NParams[name]\" size='43' class='toolsbare'>");

                $this->tds(20, 0, "class='tlp'", "$TEMPL[password]");
                $this->tds(20, 1, "", "<input type='password' name='Params[password]' value=\"$NParams[password]\" size='43' class='toolsbare'>");

                $this->tds(21, 0, "class='tlp'", "$TEMPL[coment]");
                $this->tds(21, 1, "", "<input name='Params[coment]' value=\"$NParams[coment]\" size='43' class='toolsbare'>");

                $this->tds(40, 0, "class='tlp'", $TEMPL[create]);
                $this->tds(40, 1, "", mkdate($NParams[creat]));

                $this->tds(50, 0, "class='tlp'", $TEMPL[modify]);
                $this->tds(50, 1, "", mkdate($NParams[mod]));

                $this->tds(60, 0, "colspan=2", "<hr>");

                $this->tds(70, 0, "colspan=2 class='tla'", ""); {
                    $this->out("Level : <br>");
                    if ($this->USR->lev() == 2) {
                        $this->out("<input type='radio' name='Params[lev]' value = '0' " . ($NParams[lev] == 0 ? "CHECKED" : "") . "> $TEMPL[user_l]" .  $this->TextShift);
                        $this->out("<input type='radio' name='Params[lev]' value = '1' " . ($NParams[lev] == 1 ? "CHECKED" : "") . "> $TEMPL[admin_l]" .  $this->TextShift);
                        $this->out("<input type='radio' name='Params[lev]' value = '2' " . ($NParams[lev] == 2 ? "CHECKED" : "") . "> $TEMPL[super_l]");
                    } else {
                        $this->out("<input type='checkbox' name='Params[lev]' value = '1' " . ($NParams[lev] == 1 ? "CHECKED" : "") . " class='toolsbare'> $TEMPL[domain_admin]");
                    }
                }

                $this->tds(80, 0, "colspan=2", "<hr>");

                $this->tds(90, 0, "class='tlp'", "$TEMPL[first_name]");
                $this->tds(90, 1, "", "<input name='Params[firstname]' value=\"$NParams[firstname]\" size='43' class='toolsbare'>");

                $this->tds(100, 0, "class='tlp'", "$TEMPL[last_name]");
                $this->tds(100, 1, "", "<input name='Params[lastname]' value=\"$NParams[lastname]\" size='43' class='toolsbare'>");

                $this->tds(105, 0, "class='tlp'", "$TEMPL[country]&nbsp;");
                $this->tds(105, 1, "", "<input name='Params[country]' value=\"$NParams[country]\" size='43' class='toolsbare'>");

                $this->tds(107, 0, "class='tlp'", "$TEMPL[city]&nbsp;");
                $this->tds(107, 1, "", "<input name='Params[city]' value=\"$NParams[city]\" size='43' class='toolsbare'>");

                $this->tds(110, 0, "class='tlp'", "$TEMPL[address]");
                $this->tds(110, 1, "", "<input name='Params[address]' value=\"$NParams[address]\" size='43' class='toolsbare'>");

                $this->tds(112, 0, "class='tlp'", "$TEMPL[zip]");
                $this->tds(112, 1, "", "<input name='Params[zip]' value=\"$NParams[zip]\" size='6' class='toolsbare'>");

                $this->tds(120, 0, "class='tlp'", "$TEMPL[e_mail]");
                $this->tds(120, 1, "", "<input name='Params[email]' value=\"$NParams[email]\" size='43' class='toolsbare'>");

                $this->tds(130, 0, "class='tlp'", "$TEMPL[phone]");
                $this->tds(130, 1, "", "<input name='Params[phone]' value=\"$NParams[phone]\" size='43' class='toolsbare'>");

                $this->tds(140, 0, "class='tlp'", "$TEMPL[disk_usage]");
                $this->tds(140, 1, "", AsSize($NParams[diskusage]));

                $this->tds(150, 0, "class='tlp'", "$TEMPL[quote]");
                $this->tds(150, 1, "", ""); {
                    $this->out("<input name='Params[quote]' value=\"$NParams[quote]\" size='25' class='toolsbare'>&nbsp;");
                    $this->out("<select name='Params[quote_measure]' class='toolsbare'>"); {
                        $this->out("<option value='B'"  . ($NParams[quote_measure] == "B"  ? " SELECTED" : "") . ">B </option>");
                        $this->out("<option value='KB'" . ($NParams[quote_measure] == "KB" ? " SELECTED" : "") . ">KB</option>");
                        $this->out("<option value='MB'" . ($NParams[quote_measure] == "MB" ? " SELECTED" : "") . ">MB</option>");
                        $this->out("<option value='GB'" . ($NParams[quote_measure] == "GB" ? " SELECTED" : "") . ">GB</option>");
                    } $this->out("</select>");
                }

                $this->tds(160, 0, "class='tlp'", "$TEMPL[disabled]");
                $this->tds(160, 1, "", "<input type='checkbox' name='Params[edenaid]' value = '1' " . ($NParams[edenaid] == 1 ? "CHECKED" : "") . ">");

                if ($this->USR->lev() == 2) {
                    $this->tds(170, 0, "class='tlp'", "$TEMPL[map_user]");
                    $this->tds(170, 1, "", "<input name='Params[luser]' value=\"$NParams[luser]\" size='43' class='toolsbare'>");
                }

                $this->tds(180, 0, "class='tlp' valign='top'", "$TEMPL[history]");

                $this->tds(180, 1, "", ""); {
                    $History = ($Params[history] != "") ? unserialize($Params[history]) : array();
                    reset($History);
                    while(list($n, $hist) = each($History)) {
                        $this->SubTable(); {
                            $this->TRNext("class='tlp'"); {
                                $this->TDNext("class='tlp'"); {
                                    $this->Out("&nbsp;", $hist[date], "&nbsp;");
                                }
                                $this->TDNext("class='tlp'"); {
                                    $this->Out("&nbsp;", $hist[mess], "&nbsp;");
                                }
                            }
                            $this->TRNext(); {
                                $this->TDNext("class='tla'"); {
                                    $this->Out("&nbsp;", $TEMPL[quote], "&nbsp;");
                                }
                                $this->TDNext("class='tla'"); {
                                    $TMP = $hist[quote] != "" ? $hist[quote] : "[not seted]";
                                    $this->Out("&nbsp;<span title='{$TMP}'>", AsSize($hist[quote]), "</span>&nbsp;");
                                }
                            }
                            $this->TRNext(); {
                                $this->TDNext("class='tla'"); {
                                    $this->Out("&nbsp;", $TEMPL[disabled], "&nbsp;");
                                }
                                $this->TDNext("class='tla'"); {
                                    $TMP = $hist[edenaid] != "" ? $hist[edenaid] : "[not seted]";
                                    $this->Out("&nbsp;", $TMP, "&nbsp;");
                                }
                            }
                            if ($this->USR->lev() == 2) {
                                $this->TRNext(); {
                                    $this->TDNext("class='tla'"); {
                                        $this->Out("&nbsp;", $TEMPL[map_user], "&nbsp;");
                                    }
                                    $this->TDNext("class='tla'"); {
                                        $TMP = $hist[luser] != "" ? $hist[luser] : "[not seted]";
                                        $this->Out("&nbsp;", $TMP, "&nbsp;");
                                    }
                                }
                            }
                        } $this->SubTableDone();
                    }
                }

            } $this->SubTableDone();
        } $this->SubTableDone();

        $this->out("</center>");
        $this->out("</form>");
    }


    function ScrDelete()
    {
        global $View, $NumDelete, $Sort, $TEMPL;


        $r_dm = DBFind("usr", "sysnum = $NumDelete", "", __LINE__);

        if ($r_dm->NumRows() == 0) {
            $View = "";
            $NumDelete = "";
            $this->ScrList();
            return;
        }

        $this->out("<Form method='post' name='DeleteUser'>");

        $this->out("<center>");
        $this->out("<img src='../img/attension.gif'><br>");
        $this->out("<font color='#ff0000' size='+1'>$TEMPL[delete] $TEMPL[user]?</font><br>");
        $this->out("<h1>".$r_dm->name()."</h1><br>");
        $this->out("<input type='submit' name='sDelete' value='$TEMPL[delete]' class='toolsbarb'>" . $this->ButtonBlank);
        $this->out("<input type='submit' name='sCancel' value='$TEMPL[cancel]' class='toolsbarb'>");
        $this->out("</center>");

        $this->out("</Form>");
    }


    function SetView($k)
    {
        global $View;
        $View = $k;

        $this->refreshScreen();
    }


    function Sort()
    {
        global $Sort, $NumSort;
        $Sort = $NumSort;

        $this->refreshScreen();
    }



    function Input()
    {
        global $NumInput, $HTTP_HOST, $FACE, $Mes, $s_ListUsers;

        $r = DBExec("select * from usr where sysnum = $NumInput", __LINE__);

        if ($r->NumRows() != 1) {
            $s_ListUsers[Mes] = 7;
            $this->refreshScreen();
        }

        if ($r->sysnumdomain() != $this->USR->sysnumdomain() && $this->USR->lev() == 1) {
            $s_ListUsers[Mes] = 7;
            $this->refreshScreen();
        }


        $time = time();
        setcookie("CUID[$NumInput][time]", $time, 0, "/", "$HTTP_HOST");
        setcookie("CUID[$NumInput][code]", TimedependAuthorizeHash($NumInput, $time), 0, "/", "$HTTP_HOST");

        header("Location: welcome.php?UID=" . $NumInput . "&FACE=$FACE");
        Exit;
    }


    function sCreate()
    {
        global $View, $Mes, $MesParam, $s_ListUsers;
        global $Params;

        #echo sharr($Params); exit;

        reset($Params);
        while(list($n, $v) = each($Params)) {
            $Params[$n] = trim($v);
        }

        if ($Params[name] == "") {
            $s_ListUsers[Mes] = 2;
            return;
            $this->refreshScreen();
        }

        if (!ereg("^[A-Za-z][A-Za-z0-9_-]*$", $Params[name])) {
            $s_ListUsers[Mes]      = 3;
            $s_ListUsers[MesParam] = htmlspecialchars($Params[name]);
            return;
            $this->refreshScreen();
        }

        if (!ereg("^[A-Za-z0-9_-]*$", $Params[password])) {
            $s_ListUsers[Mes]      = 4;
            $s_ListUsers[MesParam] = htmlspecialchars($Params[password]);
            return;
            $this->refreshScreen();
        }

        $r_usr = DBFind("usr", "name = '$Params[name]' and sysnumdomain = $this->Numer", "", __LINE__);
        if ($r_usr->NumRows() != 0) {
            $s_ListUsers[Mes]      = 1;
            return;
            $this->refreshScreen();
        }

        if (!ereg("^[0-9]*$", $Params[quote])) {
            $s_ListUsers[Mes]      = 9;
            return;
            $this->refreshScreen();
        }

        $this->WriteDB($NumEdit, $Params);

        $View = "";
        $this->sCancel();
    }


    function sEdit()
    {
        global $View, $Mes, $MesParam;
        global $NumEdit;
        global $Params, $s_ListUsers;

        //echo sharr($Params); exit;
        $this->Log("sEdit : start");

        $NumEdit = (int)$NumEdit;

        if ($NumEdit == 0) {
            $this->Log("PERMISSION DENIED N 1");
            $s_ListUsers[Mes]      = 7;
            $this->refreshScreen();
        }

        $r_usr = DBFind("usr", "sysnum = $NumEdit and sysnumdomain = " . $this->Numer, "", __LINE__);
        if ($r_usr->NumRows() != 1) {
            $this->Log("PERMISSION DENIED N 2");
            $s_ListUsers[Mes]      = 7;
            $this->refreshScreen();
        }

        if ($Params[name] == "") {
            $s_ListUsers[Mes]      = 2;
            return;
            $this->refreshScreen();
        }

        if (!ereg("^[A-Za-z][A-Za-z0-9_-]*$", $Params[name])) {
            $s_ListUsers[Mes]      = 3;
            $s_ListUsers[MesParam] = htmlspecialchars($Params[name]);
            return;
            $this->refreshScreen();
        }

        if (!ereg("^[A-Za-z0-9_-]*$", $Params[password])) {
            $s_ListUsers[Mes]      = 4;
            $s_ListUsers[MesParam] = htmlspecialchars($Params[password]);
            return;
            $this->refreshScreen();
        }

        $r = DBFind("usr", "name = '$Params[name]' and sysnum != $NumEdit and sysnumdomain = $this->Numer", "", __LINE__);
        if ($r->NumRows() != 0) {
            $s_ListUsers[Mes]      = 1;
            return;
            $this->refreshScreen();
        }

        if (!ereg("^[0-9]*$", round( $Params[quote] * $this->disk_size_measure[$Params[quote_measure]] ))) {
            $s_ListUsers[Mes]      = 9;
            return;
            $this->refreshScreen();
        }

        $this->WriteDB($NumEdit, $Params);

        $View = "";
        $this->sCancel();
    }


    function ReadDB($NumEdit)
    {
        $NumEdit = (int)$NumEdit;

        if ($NumEdit == 0) {
            return array();
        }

        $UsrFields = array("name", "password", "lev", "quote", "creat", "mod", "diskusage");
        $r_usr = DBFind("usr", "sysnum = $NumEdit", "", __LINE__);

        if ($r_usr->sysnumdomain() != $this->USR->sysnumdomain() && $this->USR->lev() == 1) {
            return array();
        }

        while (list($n, $Name) = each($UsrFields)) {
            $Rez[$Name] = trim($r_usr->Field($Name));
        }

        $r_usr = DBFind("usr_ua", "sysnumusr = $NumEdit ORDER BY name, nset", "", __LINE__);

        for ($r_usr->set(0); !$r_usr->eof(); $r_usr->Next()) {
            $Name  = $r_usr->name();
            $Value = trim($r_usr->value());
            $Rez[$Name] .= $Value;
        }

        reset($Rez);
        while(list($n, $v) = each($Rez)) {
            $Rez[$n] = URLDecode($v);
        }

        //echo sharr($Rez); exit;

        if ($Rez[quote] == 0) {
            $Rez[quote] = "";
            $Rez[quote_measure] = "B";
        } else {
            $TMP = preg_replace("/(^ +)|( +$)/", "", AsSize($Rez[quote]));
            if(preg_match("/^(.+?)(&nbsp;)+(.+?)$/", $TMP, $MATH)) {
                $Rez[quote]   = $MATH[1];
                $Rez[quote_measure] = $MATH[3];
            } else {
                $Rez[quote]   = "";
                $Rez[quote_measure] = "B";
            }
        }

        return $Rez;
    }


    function WriteDB($NumEdit, $Params)
    {
        global $DBConn;

        #------------------------------------------------------------
        $NParams = $Params;
        if (!is_array($NParams) || count($NParams) == 0) {
                return 0;
        }

        #------------------------------------------------------------
        if ($NParams[quote] == "") {
            $NParams[quote] = 0;
        }

        $NParams[quote] = round($NParams[quote] * $this->disk_size_measure[$NParams[quote_measure]]);
        unset($NParams[quote_measure]);

        if ($NParams[lev] == "") {
            $NParams[lev] = 0;
        }

        if ($NParams[lev] > $this->USR->lev()) {
            $NParams[lev] = 0;
        }

        if ($NParams[edenaid] == "") {
            $NParams[edenaid] = ""; // put NULL's value item into array
        }

        unset( $NParams[creat] );
        unset( $NParams[mod] );

        #------------------------------------------------------------

        $History = ($NParams[history] != "") ? unserialize(urldecode($NParams[history])) : array();
        $hist =& $History[];

        $this->Log(sharr($History));

        $hist[date] = GetCurrDate();
        if ($NumEdit == 0) {
            $hist[mess] = "Created by {$this->USRNAME}";
        } else {
            $hist[mess] = "Modifyed by {$this->USRNAME}";
        }
        $hist[quote]   = $NParams[quote];
        $hist[edenaid] = $NParams[edenaid];
        $hist[luser]   = $NParams[luser];
        $NParams[history]    = serialize($History);

        $NParams["quote " . GetCurrDate()] = $NParams[quote];

        //echo sharr($NParams); exit;

        #------------------------------------------------------------
        while (list($n, $v) = each($NParams)) {
                $NParams[$n] = URLEncode($v);
        }

        #------------------------------------------------------------
        DBExec("BEGIN", __LINE__);

        if ($NumEdit != 0) {
                $r_usr = DBFind("usr", "sysnum = $NumEdit", "", __LINE__);
                if ($r_usr->NumRows() == 1 && ($r_usr->sysnumdomain() == $this->USR->sysnumdomain() || $this->USR->lev() == 2)) {
                        $User = $NumEdit;
                } else {
                        $NumEdit = 0;
                }
        }

        if ($NumEdit == 0) {
            $User = NextVal("usr_seq");
            DBExec("INSERT INTO usr (sysnum, sysnumdomain, creat, diskusage) VALUES ($User, $this->Numer, 'now'::abstime, 0)", __LINE__);

            DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Inbox', 1, 'd')", __LINE__);
            DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Sent Items',  2, 'd')", __LINE__);
            DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Trash', 5, 'd')", __LINE__);
        }

        $UsrFields = array("name", "password", "lev", "quote");

        while (list($n, $Name) = each($UsrFields)) {
            #echo htmlspecialchars("$Name >" . $NParams[$Name] . "<"), "<br>";

            DBExec("UPDATE usr SET $Name = '$NParams[$Name]' WHERE sysnum = '$User'", __LINE__);
            unset ($NParams[$Name]);
        }
        DBExec("UPDATE usr SET mod = 'now'::abstime WHERE sysnum = '$User'", __LINE__);
        #exit;

        if (count($NParams) == 0) {
            DBExec("COMMIT", __LINE__);
            return $User;
        }

        reset($NParams);
        while (list($Name, $Value) = each($NParams)) {
            DBExec("DELETE FROM usr_ua WHERE sysnumusr = '$User' and name = '$Name'", __LINE__);

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

        DBExec("COMMIT", __LINE__);
    }


    function sDelete()
    {
        global $View, $NumDelete, $s_ListUsers;

        if ($NumDelete == 0) {
            $s_ListUsers[Mes]      = 7;
            $this->refreshScreen();
        }

        $r_usr = DBFind("usr", "sysnum = $NumDelete", "", __LINE__);
        if ($r_usr->NumRows() != 1) {
            $s_ListUsers[Mes]      = 7;
            $this->refreshScreen();
        }

        DelUsr($NumDelete);

        $View = "";
        $this->sCancel();
    }


    function sNextPrevScreen($Num)
    {
        global $NumPage;
        $NumPage = (int)$NumPage;

        if ($Num < 0)  {
            if ($NumPage > 0) {
                $NumPage --;
            } else {
                $NumPage = 0;
            }
        }

        if ($Num > 0)  {
            $NumPage ++;
        }

        $this->refreshScreen();
    }


    function sCancel()
    {
        global $View;
        $View = "";

        $this->refreshScreen();
    }


    function sExit()
    {
        global $FACE, $INET_SRC;

        if ($this->USR->lev() == 2) {
            header("Location: $INET_SRC/list_domains.php?UID=$this->UID&FACE=$FACE");
            exit;
        } else {
            header("Location: $INET_SRC/admin_opt.php?UID=$this->UID&FACE=$FACE");
            exit;
        }
    }


    function sSetSelecterChar()
    {
        global $SelectedChar;
        global $sGetSelectedChar, $NumPage;

        $SelectedChar = $sGetSelectedChar;
        if ($SelectedChar == "All") {
            $SelectedChar = "";
        }
        $NumPage = 0;

        $this->refreshScreen();
    }


    function mes()
    {
        global $Mes, $MesParam, $s_ListUsers, $TEMPL;


        if ($Mes == "") {
            $Mes = $s_ListUsers[Mes];
            unset($s_ListUsers[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_ListUsers[MesParam];
            unset($s_ListUsers[MesParam]);
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


    function refreshScreen()
    {
        global $INET_SRC, $FACE, $DOMAIN, $Sort, $View, $NumPage, $SelectedChar;
        global $NumEdit, $NumDelete, $NumInput, $s_ListUsers;

        if ($s_ListUsers[Mes]) {
            $this->Log("refresh with error " . $s_ListUsers[Mes]);
        }


        $URL = "$INET_SRC/list_users.php?UID=$this->UID&FACE=$FACE";
        if ($DOMAIN != "") {
            $URL .= "&DOMAIN=" . URLENCODE($DOMAIN);
        }

        if ($Sort != "") {
            $URL .= "&Sort=" . URLENCODE($Sort);
        }

        if ($SelectedChar != "") {
            $URL .= "&SelectedChar=" . URLENCODE($SelectedChar);
        }

        if ($NumPage != "" && $NumPage != 0) {
            $URL .= "&NumPage=" . URLENCODE($NumPage);
        }

        if ($View != "") {
            $URL .= "&View=" . URLENCODE($View);
            if ($NumEdit != "") {
              $URL .= "&NumEdit=" . URLENCODE($NumEdit);
            }
            if ($NumDelete != "") {
              $URL .= "&NumDelete=" . URLENCODE($NumDelete);
            }
        }

        UnconnectFromDB();

        echo "<script language='javascript'>\n";
        echo "    document.location = \"$URL\";\n";
        echo "</script>";

        //header("Location: $URL");
        exit;
    }

} // end of class CListUsersScreen

ConnectToDB();

$ListUsersScreen = new CListUsersScreen();
$ListUsersScreen->Run();

UnconnectFromDB();
?>
