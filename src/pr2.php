<?php

include("_config.inc.php");
require "cont.inc.php";
require "tools.inc.php";
require "file.inc.php";
require("screen.inc.php");

class CPR2Screen extends screen {


    function CPR2Screen()
    {
        global $Mes;
        global $SelGroup, $SelFolder;
        global $s_PR2;

        $this->screen(); // inherited constructor
        $this->SetTempl("address");
        session_register("s_PR2");


        $s_PR2[GroupType] = "";
        if ($s_PR2[SelecterGroup] != "") {
            $r_grp = DBFind("grpaddress", "sysnumusr = $this->UID and name = '$s_PR2[SelecterGroup]'", "", __LINE__);
            if ($r_grp->NumRows() != 0) {
                $s_PR2[GroupType] = $r_grp->ftype();
            } else {
                $r_grp = DBFind("domain", "name = '$s_PR2[SelecterGroup]'", "", __LINE__);
                if ($r_grp->NumRows() != 0) {
                    $s_PR2[GroupType] = "d";
                }
            }
        }

        $this->Request_actions["sNewView"]          = "NewView()";
        $this->Request_options["sNewView"]          = "blankok";
        $this->Request_actions["sShortAddSubmit"]   = "rShortAddSubmit()";
        $this->Request_actions["pGetSelectedChar"]  = "SetSelecterChar()";
        $this->Request_actions["pGetSelecterGroup"] = "ChangeGroup()";
        $this->Request_actions["pGetSortOrder"]     = "SetSortOrder()";

        $this->LastSelected();
    }


    function NewView()
    {
        global $s_PR2, $sNewView;

        $s_PR2 = array();
        $this->refreshScreen();
    }


    function HTML_Body()
    {
        echo "<body class='body' TOPMARGIN='0' LEFTMARGIN='0' onload='Body_OnLoad()'>";
        $this->display();
        $this->Show();
        echo GetMakeButtonBlankList();
        echo "</body>\n";
    }


    function DisplayHeader()
    {
    }


    function referens()
    {
    }


    function Logo()
	{
	}


    function Mes()
	{
	}


    function UserName()
	{
	}


    function Script()
    {
        global $INET_SRC;
        screen::script();
        echo "<script language='javascript' src='$INET_SRC/pr2.js'>";
        echo "</script>";
    }


    function Title()
	{
	}


    function display()
    {
        global $s_PR2;
        global $EditCont;
        global $pSubmit;

        if ($s_PR2[GroupType] != "d") {
            $this->SetData();
        } else {
            if ($this->DOMAIN->showdomainaddress()) {
                $this->SetDataDomain();
            }
        }

        //echo sharr($this->Data), "<hr>";

        uasort($this->Data, "Pr2CompareAddresses");
        #=========================================

        $this->out("<form method='post' name='addrform'>");

        if(!$pSubmit) {
            // fields for dialog with onclick's scripts
            $this->out("<input type='hidden' name='pSubmit'>");
            $this->out("<input type='hidden' name='pGetSelectedChar'>");
            $this->out("<input type='hidden' name='pGetSelecterGroup'>");
            $this->out("<input type='hidden' name='pGetSortOrder'>");

            screen::display();
        } else {
            $this->out("<input type='hidden' name='pSelectTO'  value=\"" . htmlspecialchars(GetSelectedAddressGroup(split(",", $s_PR2[SelectTO]), $this->UID)) . "\">");
            $s_PR2 = array();
        }
        $this->out("</form>");
    }


    function SetDataDomain()
    {
        global $s_PR2;

        $this->Data = array();
        $s_PR2[Displayed] = "";
        $numrow = 1;

        $r_add = DBExec("select usr.sysnum from usr, domain where usr.sysnumdomain = domain.sysnum and domain.name = '" . $s_PR2[SelecterGroup] . "'", __LINE__);

        //echo $r_add->NumRows(), "<hr>";

        for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
            $data =& $this->Data[$numrow++];
            $data[ftype]    = "s";

            $arr = ReadFromUsr($r_add->sysnum());

            $data[sysnum]   = URLEncode($arr["sysnum"]);
            if ($arr["firstname"] . $arr["lastname"] != "") {
                $data[name] = URLEncode($arr["firstname"] . " " . $arr["lastname"]);
            } else {
                $data[name] = URLEncode($arr["name"]);
            }
            $data[mailto]   = URLEncode($arr["name"] . "@". $s_PR2[SelecterGroup]);

            //for($i=0; $i<$r_add->numfields(); $i++) {
            //  $data[$r_add->fieldname($i)] = $r_add->Field($i);
            //}

            $s_PR2[Displayed] .= ($s_PR2[Displayed] == "" ? "" : ",") . "s_" .$data[sysnum];
        }
    }


    function SetData()
    {
        global $s_PR2;

        $this->Data = array();
        $s_PR2[Displayed] = "";
        $numrow = 1;

        $r_grp = DBFind("domain, usr",
                        "domain.sysnum = usr.sysnumdomain and domain.sysnum = '" . $this->USR->sysnumdomain() . "' " .
                        "group by domain.sysnum, domain.name, domain.showdomainaddress",
                        "domain.name as name, 'd' as ftype, count(usr.sysnum), domain.showdomainaddress", __LINE__);

        if($r_grp->NumRows() != 0) {
            if($r_grp->showdomainaddress()) {
                for($r_grp->Set(0); !$r_grp->eof(); $r_grp->Next()) {
                    $data =& $this->Data[$numrow++];

                    for($i=0; $i < $r_grp->numfields(); $i++) {
                        $data[$r_grp->fieldname($i)] = $r_grp->Field($i);
                    }

                    if ($s_PR2[SelecterGroup] == "") {
                        $s_PR2[Displayed] .= ($s_PR2[Displayed] == "" ? "" : ",") . $data[ftype] . "_" .$data[name];
                    }
                }
            }
        }


        $r_grp = DBFind("grpaddress", "sysnumusr = $this->UID group by name, ftype", "name, ftype, count(sysnumaddress)", __LINE__);
        for($r_grp->Set(0); !$r_grp->eof(); $r_grp->Next()) {
            $data =& $this->Data[$numrow++];

            for($i=0; $i<$r_grp->numfields(); $i++) {
                $data[$r_grp->fieldname($i)] = $r_grp->Field($i);
            }

            if ($s_PR2[SelecterGroup] == "") {
                $s_PR2[Displayed] .= ($s_PR2[Displayed] == "" ? "" : ",") . $data[ftype] . "_" .$data[name];
            }
        }

        if ($s_PR2[SelecterGroup] == "") {
            $r_add = DBFind("address", "sysnumusr = $this->UID and sysnum not in (select sysnumaddress from grpaddress where sysnumusr = $this->UID and ftype = 'f')", "");
        } else {
            $r_add = DBExec("select * from address where sysnum in (select sysnumaddress from grpaddress where sysnumusr = $this->UID and name = '$s_PR2[SelecterGroup]')");
        }
        for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
            $data =& $this->Data[$numrow++];

            $data[ftype] = "u";
            for($i=0; $i<$r_add->numfields(); $i++) {
                $data[$r_add->fieldname($i)] = $r_add->Field($i);
            }

            $s_PR2[Displayed] .= ($s_PR2[Displayed] == "" ? "" : ",") . "u_" .$data[sysnum];
        }
    }


    function LastSelected()
    {
        global $s_PR2;
        global $To_List, $To_Select;

        $sel  = split(',', $s_PR2[SelectTO]);
        _reset($sel);
        while(list($n, $v) = _each($sel)) {
            $To_Select[$v] = $v;
        }

        $disp = split(',', $s_PR2[Displayed]);
        $s_PR2[Displayed] = ""; // chtoby pri powtornom wyzove ne zateret' pomechenye

        if (is_array($disp)) {
            reset($disp);
            while (list($n, $v) = each($disp)) {
                unset($To_Select[$v]);
            }
        }

        if (is_array($To_List)) {
            reset($To_List);
            while (list($n, $v) = each($To_List)) {
                $To_Select[$v] = $v;
            }
        }

        _reset($To_Select);
        $s_PR2[SelectTO] = "";
        while(list($n, $v) = _each($To_Select)) {
            $s_PR2[SelectTO] .= ($s_PR2[SelectTO] == "" ? "" : ",") . $v;
        }

    }


    function Scr()
    {
        global $INET_IMG, $TEMPL, $FACE;
        global $s_PR2;
        global $To_Select;


        $this->SubTable("width='100%' border=0 cellpadding='3' cellspacing='0' class='tab'"); {
            $this->TDS(0, 0, "class='toolsbarl' colspan = '7' nowrap", ""); {
                $this->out(makeButton("type=2& form=SharingForm& name=SendAddress& value=Send addresses& onclick=javascript:PutSubmit();"));
            }
            $this->TDS(1, 0, "class='toolsbarl' colspan = '7' nowrap", ""); {
                $this->out("<a href='javascript:document.addrform.pGetSelectedChar.value=\"All\"; document.addrform.submit();'><font class='toolsbara'>$TEMPL[lb_all]</font></a> &nbsp");
                $let = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
                reset($let);
                while (list($n, $v) = each($let)) {
                    if ($v != $s_PR2[SelectedChar]) {
                      #$this->out("<a href='$SCRIPT_NAME?UID=$this->UID&Get=$v'>$v</a>&nbsp;");
                      $this->out("<a href='javascript:document.addrform.pGetSelectedChar.value=\"$v\"; document.addrform.submit();'><font class='toolsbara'>$v</font></a>&nbsp;");
                    } else {
                      $this->out("<font class='toolsbarl'>$v</font>&nbsp;");
                    }
                }
                // $this->out("<hr>");

                if ($s_PR2[SelecterGroup] != "") {
                    if ($s_PR2[GroupType] == "") {
                      $this->Out("<i>Internal Error. Folder Not Found.</i>");
                      return;
                    }
                    $this->out("<br>" . makeButton("type=2& form= addrform& name=Step_up& img=$INET_IMG/addrfolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_step_up_ico]& onclick=javascript:document.addrform.pGetSelecterGroup.value='NULL'; document.addrform.submit();") . $this->ButtonBlank);
                    $this->out("<b>$Group</b>");
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

        $this->SubTable("width='100%' border=0 cellpadding='1' cellspacing='1' class='tab'");

        $this->TDS(2, 1, "class='ttp'", "<input type='checkbox' name='To_List_All' title='$TEMPL[select_all_ico]' onclick='javascript:onTo_List_AllClick()'>");
        $this->TDS(2, 2, "class='ttp'", $this->SortIcons("t"));
        $this->TDS(2, 3, "class='ttp' width='50%'", "$TEMPL[lb_name]&nbsp;" . $this->SortIcons("n"));
        $this->TDS(2, 4, "class='ttp' width='50%'", "$TEMPL[lb_mailto]&nbsp;" . $this->SortIcons("m"));

        $r_add = DBFind("address", "sysnumusr = $this->UID", "");
        for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
            for($i=0; $i<$r_add->numfields(); $i++) {
                $ADD[$r_add->Row() + 1][$r_add->fieldname($i)] = $r_add->Field($i);
            }
        }

        if ($s_PR2[GroupType] != "d") {
            //$this->TRS(3, "height=30");
            //$this->TDS(3, 1, "class='tla' colspan='3' align='center' height=30", "<input type='submit' value='Add' Name='sShortAddSubmit' class='toolsbarb'>");
            $this->TDS(3, 1, "class='tla' colspan='2' align='center' height=30", makeButton("type=1& form=addrform& name=sShortAddSubmit& title=$TEMPL[bt_add_ico]& img=$INET_IMG/addrfolderadd-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderadd.gif?FACE=$FACE&"));

            $this->TDS(3, 2, "class='tla' colspan='1' align='center' height=30", "&nbsp;<input name='AddrName'  value='$AddrName' class='toolsbare'>&nbsp;");
            $this->TDS(3, 3, "class='tla' colspan='1' align='center' height=30", "&nbsp;<input name='AddrEMail' value='$AddrEMail' class='toolsbare'>&nbsp;");
        }

        $To_List_Count = 0;

        for (_reset($this->Data); $key = _key($this->Data); _next($this->Data)) {
            $Addr =& $this->Data[$key];

            $Name_       = URLDecode($Addr[name]);
            $MiddleName_ = URLDecode($Addr[middlename]);
            $LastName_   = URLDecode($Addr[lastname]);
            $Name_       = URLDecode($Addr[name]);
            $Email_      = URLDecode($Addr[mailto]);
            $Address_    = URLDecode($Addr[address]);
            $MPhone_     = URLDecode($Addr[mphone]);

            if (($s_PR2[SelectedChar] != "") && substr(ucfirst($Name_), 0, 1) != $s_PR2[SelectedChar]) {
               continue;
            }

            if ($Addr[ftype] != "u" && $Addr[ftype] != "s") {
                if ($s_PR2[SelecterGroup] != "") {
                    continue;
                }
                $Name_m = "<span title=\"" . htmlspecialchars($Name_) . "\">" . ReformatToLeft($Name_, 25) . "</span>";
                $Email_m = "&nbsp;"; //ReformatToLeft("&nbsp;", 25);

                $this->TRNext("valign='top'");
                // echo $Addr[ftype] . "_" . $Addr[name]. "<br>";
                $CHECKED = ($To_Select[$Addr[ftype] . "_" . $Addr[name]] == $Addr[ftype] . "_" . $Addr[name] ? "CHECKED" : "");
                $this->TDNext("class='tlp' nowrap");
                $this->Out("<font class='tlp'>" . "<center><input type='checkbox' name='To_List[" . $To_List_Count++ . "]' value='$Addr[ftype]_$Name_' $CHECKED onclick='javascript:onTo_List_Click()'></center></font>");

                $this->TDNext("class='tlp' nowrap");
                if ($Addr[ftype] == "g") {
                    $this->Out("<a href='javascript:document.addrform.pGetSelecterGroup.value=\"$Name_\"; document.addrform.submit();'><img src='$INET_IMG/group.gif' title='$TEMPL[open_group_ico]' border=0></a>");
                } else {
                    $this->Out("<a href='javascript:document.addrform.pGetSelecterGroup.value=\"$Name_\"; document.addrform.submit();'><img src='$INET_IMG/folder-yellow.gif' border=0 title='$TEMPL[open_folder_ico]'></a>");
                }

                $this->TDNext("class='tlp' nowrap");
                $this->Out("&nbsp;&nbsp;" . "<a href='javascript:document.addrform.pGetSelecterGroup.value=\"$Name_\"; document.addrform.submit();'><font class='tlpa'><b>" . $this->nbsp($Name_m)    . "</b></font></a>");

                $this->TDNext("class='tlp' nowrap");
                $this->Out($Email_m);
            } else {
                $Name_ .= " " . $MiddleName_ . " " . $LastName_;

                $Name_ = "<span title=\"" . htmlspecialchars($Name_) . "\">" . ReformatToLeft($Name_, 25) . "</span>";
                $Email_ = "<span title=\"" . htmlspecialchars($Email_) . "\">" . ReformatToLeft($Email_, 25) . "</span>";

                $this->TRNext("valign='top'");

                $this->TDNext("class='tlp' nowrap"); {
                    $CHECKED = ($To_Select[$Addr[ftype] . "_" . $Addr[sysnum]] == $Addr[ftype] . "_" . $Addr[sysnum] ? "CHECKED" : "");
                    $this->Out("<font class='tlp'>" . "<center><input type='checkbox' name='To_List[" . $To_List_Count++ . "]' value='{$Addr[ftype]}_{$Addr[sysnum]}' $CHECKED onclick='javascript:onTo_List_Click()'></center></font>");
                }

                $this->TDNext("class='tlp'");
                $this->Out("<img src='$INET_IMG/contact.gif' border=0>");

                $this->TDNext("class='tlp' nowrap");
                $this->Out("&nbsp;&nbsp;" . $this->nbsp($Name_));

                $this->TDNext("class='tlp' nowrap");
                $this->Out("&nbsp;&nbsp;" . $this->nbsp($Email_));
            }
        }

        if (count($Addr) != 0) {
            $this->out("<script language='javascript'>");
            $this->out("onTo_List_Click()");
            $this->out("</script>");
        } else {
            $this->TRNext("class='tlp'");
            $this->TDNext("class='tlp' colspan=8 align='center'");
            $this->Out("<font class='tlp'>");
            $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0");
            $this->tds(0, 0, "width='250' height='70'", "<center><font size='+2'>$TEMPL[empty_list]</font></center>");
            $this->SubTableDone();
            $this->Out("</font>");
        }

        $this->SubTableDone();

        //$this->SubTable();
        //$this->out(shglobals());
        //$this->SubTableDone();
    }


    function refreshScreen()
    {
        global $SCRIPT_NAME, $FACE, $Field;
        $URL = "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE";

        if ($Field != "") {
            $URL .= "&Field=$Field";
        }

        UnconnectFromDB();

        header("Location: $URL");
        exit;
    }


    function SortIcons($ord)
    {
        global $INET_IMG;
        global $s_PR2;

        $rez = "";

        if ($ord == $s_PR2[SortOrder]) {
            $rez = "<a href='javascript:document.addrform.pGetSortOrder.value=\"" . strtoupper($ord) . "\"; document.addrform.submit();'><img src='$INET_IMG/sort2.gif' alt='' border='0'></a>";
        } else {
            $rez = "<a href='javascript:document.addrform.pGetSortOrder.value=\"$ord\"; document.addrform.submit();'><img src='$INET_IMG/sort1.gif' alt='' border='0'></a>";
        }

        return $rez;
    }


    function rShortAddSubmit()
    {
        global $REQUEST_URI;
        global $AddrName, $AddrEMail;

        // echo "$AddrName,= $AddrEMail";

        if (($AddrName == "") && ($AddrEMail == "")) {
            return;
        }

        if ($AddrEMail == "") {
            $AddrEMail = $AddrName;
            $AddrName = "";
        }


        if ($AddrEMail != "") {
            if (!is_emailaddress($AddrEMail)) {
                return;
            }

            if ($AddrName == "") {
               $AddrName = preg_replace("/^([^@]*).*$/", "\\1", $AddrEMail);
            }
        }


        if (strpos($AddrName, " ")) {
            $LastName  = preg_replace("/^(\S+?)\s+(.*)/i", "\\2", $AddrName);
            $AddrName      = preg_replace("/^(\S+?)\s+(.*)/i", "\\1", $AddrName);
            if (strpos($LastName, " ")) {
                $MiddleName  = preg_replace("/^(\S+?)\s+(.*)/i", "\\1", $LastName);
                $LastName    = preg_replace("/^(\S+?)\s+(.*)/i", "\\2", $LastName);
            } else {
                $MiddleName = "";
            }
        } else {
            $LastName   = "";
            $MiddleName = "";
        }



        $AddrName_    = URLEncode($AddrName);
        $MiddleName_  = URLEncode($MiddleName);
        $LastName_    = URLEncode($LastName);
        $AddrEMail_   = URLEncode($AddrEMail);

        DBExec("insert into address (sysnum, sysnumusr, name, middlename, lastname, mailto) values (NextVal('address_seq'), '$this->UID', '$AddrName_', '$MiddleName_', '$LastName_', '$AddrEMail_')");

        $AddrName = $AddrEMail = "";
        $this->refreshScreen();
    }


    function SetSelecterChar()
    {
        global $pGetSelectedChar, $s_PR2;
        $s_PR2[SelectedChar] = $pGetSelectedChar;
        if ($pGetSelectedChar == "All") {
            $s_PR2[SelectedChar] = "";
        }

        $this->refreshScreen();
    }


    function ChangeGroup()
    {
        global $pGetSelecterGroup, $s_PR2;

        if (preg_match("/[;,]/", $pGetSelecterGroup)) {
            $pGetSelecterGroup = "NULL";
        }

        $s_PR2[SelecterGroup] = $pGetSelecterGroup;
        if ($pGetSelecterGroup == "NULL") {
            $s_PR2[SelecterGroup] = "";
        }

        $this->refreshScreen();
    }


    function SetSortOrder()
    {
        global $pGetSortOrder, $s_PR2;
        $s_PR2[SortOrder] = $pGetSortOrder;

        $this->refreshScreen();
    }

} // end class CPR2Screen

function Pr2CompareAddresses($a, $b)
{
    global $s_PR2;

    if ($s_PR2[SortOrder] == "") {
       $s_PR2[SortOrder] = "t";
    }

    switch ($s_PR2[SortOrder]) {
        case "a" :
            $f1 = strtoupper ($a[ftype] == "u" ? $a[address] : $a[name]);
            $f2 = strtoupper ($b[ftype] == "u" ? $b[address] : $b[name]);
            if ($f1 < $f2) return -1;
            if ($f1 > $f2) return 1;
            break;
        case "A" :
            $f1 = strtoupper ($a[ftype] == "u" ? $a[address] : $a[name]);
            $f2 = strtoupper ($b[ftype] == "u" ? $b[address] : $b[name]);
            if ($f1 < $f2) return 1;
            if ($f1 > $f2) return -1;
            break;
        case "m" :
            $f1 = strtoupper ($a[ftype] == "u" ? $a[mailto] : $a[name]);
            $f2 = strtoupper ($b[ftype] == "u" ? $b[mailto] : $b[name]);
            if ($f1 < $f2) return -1;
            if ($f1 > $f2) return 1;
            break;
        case "M" :
            $f1 = strtoupper ($a[ftype] == "u" ? $a[mailto] : $a[name]);
            $f2 = strtoupper ($b[ftype] == "u" ? $b[mailto] : $b[name]);
            if ($f1 < $f2) return 1;
            if ($f1 > $f2) return -1;
            break;
        case "c" :
            $f1 = strtoupper ($a[ftype] == "u" ? $a[mphone] : $a[name]);
            $f2 = strtoupper ($b[ftype] == "u" ? $b[mphone] : $b[name]);
            if ($f1 < $f2) return -1;
            if ($f1 > $f2) return 1;
            break;
        case "C" :
            $f1 = strtoupper ($a[ftype] == "u" ? $a[mphone] : $a[name]);
            $f2 = strtoupper ($b[ftype] == "u" ? $b[mphone] : $b[name]);
            if ($f1 < $f2) return 1;
            if ($f1 > $f2) return -1;
            break;
        case "t" :
            if ($a[ftype] < $b[ftype]) return -1;
            if ($a[ftype] > $b[ftype]) return 1;
        case "T" :
            if ($a[ftype] < $b[ftype]) return 1;
            if ($a[ftype] > $b[ftype]) return -1;
        case "n" :
            break;
        case "N" :
            $f1 = strtoupper ($a[name]);
            $f2 = strtoupper ($b[name]);
            if ($f1 < $f2) return 1;
            if ($f1 > $f2) return -1;
            break;
    }

    $f1 = strtoupper ($a[name]);
    $f2 = strtoupper ($b[name]);
    if ($f1 < $f2) return -1;
    if ($f1 > $f2) return 1;
    return 0;
} // end of function CompareAddresses($a, $b)

ConnectToDB();

$PR2Screen = new CPR2Screen();
$PR2Screen->Run();

UnconnectFromDB();

?>
