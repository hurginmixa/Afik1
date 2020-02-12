<?php

/*
class
  class CAddressScreen extends screen
    function CAddressScreen()
    function mes()
    function SaveScreenStatus()
    function display()
        function SetData()
        function SetDataDomain()
        function SetDataFilter()
    function LastSelected()
    function Tools()
    function ToolsBar()
        function ToolsBarList()
        function ToolsBarEdit()
    function Scr()
        function ScrList()
            function SortIcons($ord)
        function ScrEdit()
    function rNewView()
    function rShortAddSubmit()
    function AddToGroup($fName, $FType)
    function NewGroup($ftype)
    function AddListUsersToGroup($ListUsers, $NameGroup, $TypeGroup)
    function AddUserToGroup($Num, $NameGroup, $TypeGroup)
    function rNewContact()
    function rDeleteContact()
    function rDuplicateContact()
    function rToEditContact()
    function rEditSubmit()
    function rEditCancel()
    function ToMessage()
    function GetSelectedAddress(&$To)
    function ChangeGroup()
    function rAddFilter()
    function rDelFilter()

  function CompareAddresses($a, $b)
  function GetSelectedAddressGroup($List, $UID)
  function ReadFromUsr($Num)

  type of items
    d - list users of domain
    f - folder
    u - user
    s - system user
*/

if(!isset($_ADDRESS_INC_)) {

$_ADDRESS_INC_=0;


require("tools.inc.php");
require("file.inc.php");

require("db.inc.php");
require("screen.inc.php");

class CAddressScreen extends screen
{

    function CAddressScreen() // Constructor
    {
        global $SelGroup, $SelFolder;
        global $s_Address, $FACE, $TEMPL;
        global $ADDRESS_BOOK_COLUMNS;

        $this->screen(); // inherited constructor
        $this->SetTempl("address");
        session_register("s_Address");

        $this->Request_actions["sNewView"]          = "rNewView()";

        $this->Request_actions["pGetSelectedChar"]  = "SetSelecterChar()";
        $this->Request_actions["pGetSelectedGroup"] = "ChangeGroup()";
        $this->Request_actions["pGetSortOrder"]     = "SetSortOrder()";
        $this->Request_actions["pAddressEditCont"]  = "rToEditContact()";

        $this->Request_actions["sExit"]             = "rExit()";
        $this->Request_actions["sToMes"]            = "ToMessage()";
        $this->Request_actions["sNew"]              = "rNewContact()";
        $this->Request_actions["sDel"]              = "rDeleteContact()";
        $this->Request_actions["sDupl"]             = "rDuplicateContact()";
        $this->Request_actions["sShortAdd"]         = "rShortAddSubmit()";
        $this->Request_actions["sEditSubmit"]       = "rEditSubmit()";
        $this->Request_actions["sEditCancel"]       = "rEditCancel()";
        $this->Request_actions["sNewGroup"]         = "NewGroup('g')";
        $this->Request_actions["sNewFolder"]        = "NewGroup('f')";
        $this->Request_actions["sAddGroup"]         = "AddToGroup('$SelGroup', 'g')";
        $this->Request_actions["sAddFolder"]        = "AddToGroup('$SelFolder', 'f')";
        $this->Request_actions["sAddFilter"]        = "rAddFilter()";
        $this->Request_actions["sDelFilter"]        = "rDelFilter()";


        $this->PgTitle = "<b>$TEMPL[title]</b>";

        $s_Address[GroupType] = "";
        if ($s_Address[SelectedGroup] != "") {
            $TMP = preg_replace("/'/", "''", $s_Address[SelectedGroup]);
            $r_grp = DBFind("grpaddress", "sysnumusr = $this->UID and name = '{$TMP}'", "");
            if ($r_grp->NumRows() != 0) {
                $s_Address[GroupType] = $r_grp->ftype();
            } else {
                $r_grp = DBFind("domain", "name = '{$TMP}'", "");
                if ($r_grp->NumRows() != 0) {
                    $s_Address[GroupType] = "d";
                }
            }
        }

        if (!$ADDRESS_BOOK_COLUMNS) {
            $ADDRESS_BOOK_COLUMNS = "[mailto, 35, 25] [home_address, 25, 20] [home_mphone, 15, 10]";
        }
        if (preg_match_all ("/\[(.*?)\]/", $ADDRESS_BOOK_COLUMNS, $MATH)) {
            $this->ListColumns = array();
            foreach($MATH[1] as $str) {
                $arr = preg_split("/\s*\,\s*/", $str);
                $this->ListColumns[] = array("name" => $arr[0], "width" => $arr[1], "size" => $arr[2]);
            }
        }

        $this->LastSelected();
        $this->SaveScreenStatus();
    }


    function mes() // overlaping inherited function
    {
        global $Mes, $MesParam, $s_Address, $TEMPL;

        if ($Mes == "") {
            $Mes = $s_Address[Mes];
            unset($s_Address[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_Address[MesParam];
            unset($s_Address[MesParam]);
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


    function SaveScreenStatus()
    {
        global $s_Address, $_REQUEST;

        $SaveFieldsList = array("ShortForm", "Params", "$fNewGroup", "fFilter");
        foreach($this->ListColumns as $column) {
            $SaveFieldsList[] = "f{$column[name]}";
        }

        reset($SaveFieldsList);
        while(list($n, $v) = each($SaveFieldsList)) {
            if (!isset($_REQUEST[$v])) {
                continue;
            }
            if (!is_array($_REQUEST[$v])) {
                $s_Address[Status][$v] = $_REQUEST[$v];
            } else {
                reset($_REQUEST[$v]);
                while(list($ins_n, $ins_v) = each($_REQUEST[$v])) {
                    $s_Address[Status][$v][$ins_n] = $ins_v;
                }
            }
        }
    }


    function Display() // overlaping inherited function
    {
        global $s_Address;

        if ($s_Address[Filter] != "") {
            $this->SetDataFilter();
        } else {
            if ($s_Address[GroupType] != "d") {
                $this->SetData();
            } else {
                if ($this->DOMAIN->showdomainaddress()) {
                    $this->SetDataDomain();
                }
            }
        }


        //$this->Log("s_Address[SortOrder] $s_Address[SortOrder]");
        usort($this->Data, "CompareAddresses");
        //echo sharr($this->Data);
        #=========================================

        $this->out("<form method='post' name='addrform'>"); {

            // fields for dialog with onclick's scripts
            $this->out("<input type='hidden' name='pGetSelectedChar'>");
            $this->out("<input type='hidden' name='pGetSelectedGroup'>");
            $this->out("<input type='hidden' name='pGetSortOrder'>");
            $this->out("<input type='hidden' name='pAddressEditCont'>");

            screen::display();

        } $this->out("</form>");

        //$this->SubTable("border = 1");
        //$this->out("=".sharr($GLOBALS[_SESSION]));
        //$this->out("=".shGLOBALS());
        //$this->SubTableDone();
    }


    function SetData()
    {
        global $s_Address;

        $this->Data = array();

        $s_Address[Displayed] = "";

        $r_grp = DBFind("domain, usr",
                        "domain.sysnum = usr.sysnumdomain and domain.sysnum = '" . $this->USR->sysnumdomain() . "' " .
                        "group by domain.sysnum, domain.name, domain.showdomainaddress",
                        "domain.name as name, 'd' as ftype, count(usr.sysnum), domain.showdomainaddress");

        if($r_grp->NumRows() != 0) {
            if($r_grp->showdomainaddress()) {
                for($r_grp->Set(0); !$r_grp->eof(); $r_grp->Next()) {
                    $data =& $this->Data[];

                    for($i=0; $i < $r_grp->numfields(); $i++) {
                        $data[$r_grp->fieldname($i)] = $r_grp->Field($i);
                    }
                    $data [fullname] = $data[name];

                    if ($s_Address[SelectedGroup] == "") {
                        $s_Address[Displayed] .= ($s_Address[Displayed] == "" ? "" : ",") . $data[ftype] . "_" .$data[name];
                    }
                }
            }
        }

        $r_grp = DBFind("grpaddress",
                        "sysnumusr = $this->UID " .
                        "group by name, ftype",
                        "name, ftype, count(sysnumaddress)");
        for($r_grp->Set(0); !$r_grp->eof(); $r_grp->Next()) {
            $data =& $this->Data[];

            for($i=0; $i<$r_grp->numfields(); $i++) {
                $data[$r_grp->fieldname($i)] = $r_grp->Field($i);
            }
            $data[fullname] = $data[name];

            if ($s_Address[SelectedGroup] == "") {
                $s_Address[Displayed] .= ($s_Address[Displayed] == "" ? "" : ",") . $data[ftype] . "_" .$data[name];
            }
        }

        if ($s_Address[SelectedGroup] == "") {
            $r_add = DBFind("address",
                            "sysnumusr = $this->UID and sysnum not in (select sysnumaddress from grpaddress where sysnumusr = $this->UID and ftype = 'f')",
                            "", __LINE__);
        } else {
            $TMP = preg_replace("/'/", "''", $s_Address[SelectedGroup]);
            $r_add = DBFind("address",
                            "sysnum in (select sysnumaddress from grpaddress where sysnumusr = $this->UID and name = '{$TMP}')",
                            "", __LINE__);
        }
        for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
            $data =& $this->Data[];

            $data[ftype] = "u";
            for($i=0; $i < $r_add->numfields(); $i++) {
                $data[$r_add->fieldname($i)] = $r_add->Field($i);
            }

            $data[fullname] = $data[name];
            $data[fullname] .= ($data[fullname] != "" ? " " : "") . $data[middlename];
            $data[fullname] .= ($data[fullname] != "" ? " " : "") . $data[lastname];
            $data[fullname] = $data[fullname] != "" ? $data[fullname] : $data[company];

            $s_Address[Displayed] .= ($s_Address[Displayed] == "" ? "" : ",") . ("u_" . $data[sysnum]);
        }
    }


    function SetDataDomain()
    {
        global $s_Address;

        $this->Data = array();
        $s_Address[Displayed] = "";

        $r_add = DBExec("select usr.sysnum from usr where usr.sysnumdomain = '" . $this->USR->sysnumdomain() . "'", __LINE__);

        for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
            $data =& $this->Data[];
            $data[ftype]    = "s";

            $arr = ReadFromUsr($r_add->sysnum());

            $data[sysnum]   = URLEncode($arr["sysnum"]);
            if ($arr["firstname"] . $arr["lastname"] != "") {
                $data[name]     = URLEncode($arr["firstname"] . " " . $arr["lastname"]);
            } else {
                $data[name]     = URLEncode($arr["name"]);
            }
            $data[mailto]   = URLEncode($arr["name"] . "@". $s_Address[SelectedGroup]);
            $data[address]  = URLEncode("");
            $data[mphone]   = URLEncode("");
            $data[fullname] = $data[name];

            //for($i=0; $i<$r_add->numfields(); $i++) {
            //  $data[$r_add->fieldname($i)] = $r_add->Field($i);
            //}

            $s_Address[Displayed] .= ($s_Address[Displayed] == "" ? "" : ",") . "s_" .$data[sysnum];
        }
    }


    function SetDataFilter()
    {
        global $s_Address;

        $SearchFieldsList = array(
                                    'name', 'middlename', 'lastname', 'company', 'title', 'mailto', 'profession', 'home_address',
                                    'home_city', 'home_state', 'home_country', 'home_zip', 'home_phone', 'home_phone1', 'home_phone2',
                                    'home_fax', 'home_mphone', 'home_icq', 'home_page', 'biss_address', 'biss_office', 'biss_city',
                                    'biss_state', 'biss_country', 'biss_zip', 'biss_phone', 'biss_phone1', 'biss_phone2', 'biss_fax',
                                    'biss_mphone', 'biss_page'
                                  );

        $WhereString = "";
        reset($SearchFieldsList);
        foreach($SearchFieldsList as $Field) {
            $WhereString .= ($WhereString != "" ? " || " : "") . "address.{$Field}::varchar";
        }

        $Filter = preg_replace("/\%/", "\\\\\%", $s_Address[Filter]);

        $SQL = "SELECT * FROM address WHERE sysnumusr = {$this->UID} AND ".
               "(($WhereString) ilike '%{$Filter}%')";
        //echo $SQL; exit;

        $r_add = DBExec($SQL);

        $this->Data = array();

        for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
            $data =& $this->Data[];

            $data[ftype] = "u";
            for($i=0; $i < $r_add->numfields(); $i++) {
                $data[$r_add->fieldname($i)] = $r_add->Field($i);
            }

            $data[fullname] = $data[name];
            $data[fullname] .= ($data[fullname] != "" ? " " : "") . $data[middlename];
            $data[fullname] .= ($data[fullname] != "" ? " " : "") . $data[lastname];
            $data[fullname] = $data[fullname] != "" ? $data[fullname] : $data[company];

            $s_Address[Displayed] .= ($s_Address[Displayed] == "" ? "" : ",") . "u_" .$data[sysnum];
        }
    }


    function LastSelected()
    {
        global $To_List, $s_Address;

        $disp = split(',', $s_Address[Displayed]);
        $s_Address[Displayed] = ""; // chtoby pri powtornom wyzove ne zateret' pomechenye

        if (is_array($disp)) {
            reset($disp);
            while (list($n, $v) = each($disp)) {
                unset($s_Address[TO_Selected][$v]);
            }
        }

        if (is_array($To_List)) {
            reset($To_List);
            while (list($n, $v) = each($To_List)) {
                $s_Address[TO_Selected][$v] = $v;
            }
        }
    }


    function Tools()  // overlaping inherited function
    {
    }


    function ToolsBar()  // overlaping inherited function
    {
        global $s_Address;

        if ($s_Address[EditNumber] == "") {
            $this->ToolsBarList();
        } else {
            $this->ToolsBarEdit();
        }
    }


    function Scr()  // overlaping inherited function
    {
        global $s_Address;

        if ($s_Address[EditNumber] == "") {
            $this->ScrList();
        } else {
            $this->ScrEdit();
        }

        //$this->out(sharr($s_Address));
    }


    function ToolsBarList()
    {
        global $REQUEST_URI, $SCRIPT_NAME, $INET_IMG;
        global $s_Address;
        global $TEMPL, $FACE;

        $LettersList = "ABCDEFGHIJKLMNOPQRSTUVWXYZ" . $TEMPL[letters_list];

        $this->SubTable("border='0' width='100%' cellspacing=0 cellpadding=4"); {
            $this->TRNext(""); {
                $this->TDNext("class='toolsbarl'"); {
                    if ($s_Address[GroupType] != "d") {
                        $this->out(makeButton("type=1& form=addrform& name=sNew&  img=$INET_IMG/addrfoldernewcontact-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfoldernewcontact.gif?FACE=$FACE& title=$TEMPL[bt_new_contact_ico]") . $this->ButtonBlank);
                        $this->out(makeButton("type=1& form=addrform& name=sDel&  img=$INET_IMG/addrfolderdelcontact-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderdelcontact.gif?FACE=$FACE& title=$TEMPL[bt_del_contact_ico]") . $this->ButtonBlank);
                        $this->out(makeButton("type=1& form=addrform& name=sDupl& img=$INET_IMG/addrfolderdupl-passive.gif?FACE=$FACE&       imgact=$INET_IMG/addrfolderdupl.gif?FACE=$FACE&       title=$TEMPL[bt_dupl_contact_ico]"));
                        $this->out($this->SectionBlank);
                    }
                    $this->out(makeButton("type=1& form=addrform& name=sToMes& img=$INET_IMG/addrfoldernewmessage-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfoldernewmessage.gif?FACE=$FACE& title=$TEMPL[bt_new_message_ico]"));
                    $this->out($this->SectionBlank);

                    #------------------------------------------------------------------------
                    if ($s_Address[GroupType] != "d") {
                        $this->out("<input type='text' name='fNewGroup' value=\"" . htmlspecialchars($s_Address[Status][fNewGroup]) . "\" class='toolsbare'>&nbsp;&nbsp;");
                        $this->out(makeButton("type=1& form=addrform& name=sNewFolder& img=$INET_IMG/addrfoldernewfolder-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfoldernewfolder.gif?FACE=$FACE& title=$TEMPL[bt_new_folder_ico]"));
                    }

                    #------------------------------------------------------------------------
                    if (is_array($this->Data) && count($this->Data) != 0) {
                        for ($i = 0; $i < count($this->Data); $i++) {
                            if ($this->Data[$i][ftype] == "f") {
                                $s .= "<option value='" . $this->Data[$i][name] . "'" . ($this->Data[$i][name] == $s_Address[Status][SelFolder] ? " selected" : "") .  ">" . ($this->Data[$i][name]) . "</option>";
                            }
                        }
                    }

                    if ($s != "") {
                        $this->out($this->ButtonBlank);
                        $s = "<option>- $TEMPL[chfolder] -</option>" .
                             "<option value='-1'" . ($s_Address[Status][SelFolder] == "-1" ? " selected" : "") .  ">$TEMPL[chfolder_rootname]</option>" . $s;
                        $this->out($this->SectionBlank);
                        $this->out("<select name='SelFolder' class='toolsbare'>$s</select>" . $this->ButtonBlank);
                        $this->out(makeButton("type=1& form=addrform& name=sAddFolder& img=$INET_IMG/addrfoldermovetofolder-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfoldermovetofolder.gif?FACE=$FACE& title=$TEMPL[bt_mov_folder_ico]"));
                    }

                    #------------------------------------------------------------------------
                    $this->out($this->SectionBlank);
                    $this->out(makeButton("type=1& form=addrform& name=sExit& img=$INET_IMG/addrfolderexit-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderexit.gif?FACE=$FACE& title=$TEMPL[bt_exit_ico]"));
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler1x1.gif' border=0>");

        $this->SubTable("border='0' width='100%' cellspacing=0 cellpadding=4"); {
            $this->TDNext("class='toolsbarl' nowrap"); {
                $TMP = $s_Address[Status][fFilter] != "" ? $s_Address[Status][fFilter] : urldecode($s_Address[Filter]);
                $this->out("{$TEMPL[filter]} <input type='text' name='fFilter' value=\"" . htmlspecialchars($TMP) . "\" class='toolsbare'>", $this->ButtonBlank);
                $this->out(makeButton("type=1& form=addrform& name=sAddFilter&  img=$INET_IMG/addrfolderaddsearch-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderaddsearch.gif?FACE=$FACE& title=$TEMPL[bt_add_search_ico]"), $this->ButtonBlank);
                if ($s_Address[Filter] != "") {
                    $this->out(makeButton("type=1& form=addrform& name=sDelFilter&  img=$INET_IMG/addrfolderdelsearch-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderdelsearch.gif?FACE=$FACE& title=$TEMPL[bt_del_search_ico]"));
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler1x1.gif' border=0>");

        $this->SubTable("border='0' width='100%' cellspacing=0 cellpadding=4"); {
            $this->TRNext(""); {
                $this->TDNext("class='toolsbarl' nowrap colspan=2"); {
                    $this->out("<a href='javascript:document.addrform.pGetSelectedChar.value=\"All\"; document.addrform.submit();'><font class='toolsbara'>$TEMPL[lb_all]</font></a> &nbsp");
                    for ( $n = 0; $n < strlen($LettersList); $n++ ) {
                        $v = $LettersList[$n];

                        if ($v == " ") {
                            continue;
                        }

                        if ($v != $s_Address[SelectedChar]) {
                            #$this->out("<a href='$SCRIPT_NAME?UID=$this->UID&Get=$v'>$v</a>&nbsp;");
                            $this->out("<a href='javascript:document.addrform.pGetSelectedChar.value=\"$v\"; document.addrform.submit();'><font class='toolsbara'>$v</font></a>&nbsp;");
                        } else {
                            $this->out("$v&nbsp;");
                        }
                    }
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif' border=0>");
    }


    function ScrList()
    {
        global $INET_IMG;
        global $s_Address;
        global $FACE, $TEMPL;

       // echo (count($this->Data). "<br>");
       // echo ShArr($this->Data, "");

        if ($s_Address[SelectedGroup] != "") {
            if ($s_Address[GroupType] == "") {
                $this->Out("<i>$TEMPL[err_int1]</i>: " . htmlspecialchars($s_Address[SelectedGroup]) );
                return;
            }
            $this->out("<a href='javascript:document.addrform.pGetSelectedGroup.value=\"NULL\"; document.addrform.submit();'><img src='$INET_IMG/up2.gif' border=0 align='ABSMIDDLE'></a>" . $this->ButtonBlank);
            $this->out("<b>$s_Address[SelectedGroup]</b>");
            $this->out("<br><img src='$INET_IMG/filler2x1.gif'>");
        }

        //$this->out($s_Address[SortOrder], "<br>");
        //$this->out(serialize($this->ListColumns), "<br>");
        //$this->out(sharr($this->Data), "<br>");


        $this->SubTable("width='100%' border=0 cellspacing='0' cellpadding = '0' class='tab' grborder"); {

            $this->TRNext(); {
                $this->TDNext("class='ttp'"); {
                    $this->out("<input type='checkbox' name='To_List_All' title='$TEMPL[select_all_ico]' onclick='javascript:onTo_List_AllClick()'>");
                }

                $this->TDNext("class='ttp'"); {
                    //$this->out($this->SortIcons("t"));
                    $this->out("&nbsp;");
                }

                $this->TDNext("class='ttp' width='25%'"); {
                    $this->out("$TEMPL[lb_fullname]&nbsp;" . $this->SortIcons("fullname"));
                }

                reset($this->ListColumns);
                foreach($this->ListColumns as $column) {
                    $this->TDNext("class='ttp' width='{$column[width]}%' nowrap"); {
                        $this->out("&nbsp;", $TEMPL["lb_" . $column[name]], "&nbsp;" . $this->SortIcons($column[name]), "&nbsp;");
                    }
                }
            }
            if ($s_Address[GroupType] != "d") {
                $this->TRNext(); {
                    $ShortForm =& $s_Address[Status][ShortForm];

                    $this->TDNext("class='tla' colspan=3 align='right'"); {
                        $this->out("<center>" . makeButton("type=1& form=addrform& name=sShortAdd& title=$TEMPL[bt_add_ico]& img=$INET_IMG/addrfolderadd-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderadd.gif?FACE=$FACE&") . "</center>");
                    }
                    $this->TDNext("class='tla' align='center'"); {
                        $this->out("&nbsp;<INPUT size=15 name='ShortForm[name]'    value=\"" . htmlspecialchars($ShortForm[name]) . "\" class='toolsbare'>&nbsp;");
                    }
                    reset($this->ListColumns);
                    foreach($this->ListColumns as $column) {
                        $this->TDNext("class='tla' align='center'"); {
                            $this->out("&nbsp;<INPUT size={$column[size]} name='ShortForm[{$column[name]}]' value=\"" . htmlspecialchars($ShortForm[$column[name]]) . "\" class='toolsbare'>&nbsp;");
                        }
                    }
                }
            }

            if (is_array($this->Data) && count($this->Data) != 0) {
                $To_List_Count = 0;
                reset($this->Data);
                for ($i = 0; $i < count($this->Data); $i++) {
                    $Addr =& $this->Data[$i];

                    //echo sharr($Addr), "<hr>";

                    $Name_       = URLDecode($Addr[name]);
                    $MiddleName_ = URLDecode($Addr[middlename]);
                    $LastName_   = URLDecode($Addr[lastname]);
                    $Email_      = URLDecode($Addr[mailto]);
                    $Title_      = URLDecode($Addr[title]);
                    $Company_    = URLDecode($Addr[company]);

                    $FullName_ = $Name_ . " " . $MiddleName_ . " " . $LastName_;
                    if ($FullName_ == "  ") {
                        $FullName_ = $Company_;
                    }

                    $Comp_Char = ucfirst($s_Address[SelectedChar]);
                    if (($s_Address[SelectedChar] != "") && substr(ucfirst($Name_), 0, 1)       != $Comp_Char &&
                                                            substr(ucfirst($MiddleName_), 0, 1) != $Comp_Char &&
                                                            substr(ucfirst($LastName_), 0, 1)   != $Comp_Char &&
                                                            substr(ucfirst($Title_), 0, 1)      != $Comp_Char &&
                                                            substr(ucfirst($Company_), 0, 1)    != $Comp_Char &&
                                                            substr(ucfirst($Email_), 0, 1)      != $Comp_Char) {
                        continue;
                    }
                    //$r_r = substr(ucfirst($Name_), 0, 1);

                    if ($Addr[ftype] != "u" && $Addr[ftype] != "s" ) {
                        if ($s_Address[SelectedGroup] != "") {
                            continue;
                        }

                        $Name_ = $this->nbsp($Name_);
                        //$Name_m = (strlen($Name_) < 45 ? $Name_ : substr($Name_, 0, 45) . "...");
                        $Name_m = "<span title=\"$Name_\">$Name_</span>";

                        $this->TRNext("valign='top'"); {
                            // echo $Addr[ftype] . "_" . $Addr[name]. "<br>";
                            $this->TDNext("class='tlp'"); {
                                $CHECKED = ($s_Address[TO_Selected][$Addr[ftype] . "_" . $Addr[name]] == $Addr[ftype] . "_" . $Addr[name] ? "CHECKED" : "");
                                $this->Out("<font class='tlp'>" . "<center><input type='checkbox' name='To_List[" . $To_List_Count++ . "]' value=\"{$Addr[ftype]}_" . htmlspecialchars($Addr[name]) . "\" $CHECKED onclick='javascript:onTo_List_Click()'></center></font>");
                            }

                            $this->TDNext("class='tlp'"); {
                                $this->Out("<a href=\"javascript:document.addrform.pGetSelectedGroup.value=&quot;" . htmlspecialchars($Name_) . "&quot;; document.addrform.submit();\">");
                                if ($Addr[ftype] == "g") {
                                    $this->Out("<img src='$INET_IMG/group.gif' border='0' title='Enter in group'>");
                                } else {
                                    $this->Out("<img src='$INET_IMG/folder-yellow.gif' border='0' title='{$TEMPL[open_folder_ico]}'>");
                                }
                                $this->Out("</a>");
                            }

                            $this->TDNext("class='tlp'"); {
                                $this->Out("&nbsp;&nbsp;<a href=\"javascript:document.addrform.pGetSelectedGroup.value=&quot;" . htmlspecialchars($Name_) . "&quot;; document.addrform.submit();\"><font class='tlpa'><b>" . $this->nbsp($Name_m)    . "</b></font></a>");
                            }

                            reset($this->ListColumns);
                            foreach($this->ListColumns as $Column) {
                                $this->TDNext("class='tlp'"); {
                                    $this->Out("&nbsp;");
                                }
                            }
                        }
                    } else {
                        $FullName_ = $this->nbsp(htmlspecialchars($FullName_));

                        $this->TRNext("valign='top'"); {
                            $this->TDNext("class='tlp'"); {
                                $CHECKED = ($s_Address[TO_Selected][$Addr[ftype] . "_" . $Addr[sysnum]] == $Addr[ftype] . "_" . $Addr[sysnum] ? "CHECKED" : "");
                                $this->Out("<font class='tlp'>" . "<center><input type='checkbox' name='To_List[" . $To_List_Count++ . "]' value='".$Addr[ftype]."_".$Addr[sysnum]."' $CHECKED onclick='javascript:onTo_List_Click()'></center></font>");
                            }

                            $this->TDNext("class='tlp'"); {
                                $this->Out("<a href='javascript:document.addrform.pAddressEditCont.value=\"".$Addr[ftype] . "_" . $Addr[sysnum]."\"; document.addrform.submit();'>");
                                $this->Out("<img src='$INET_IMG/contact.gif' border=0 title='$TEMPL[view_prop_ico]'>");
                                $this->Out("</a>");
                            }

                            $this->TDNext("class='tlp'"); {
                                $this->Out("&nbsp;&nbsp;<a href='javascript:document.addrform.pAddressEditCont.value=\"".$Addr[ftype] . "_" . $Addr[sysnum]."\"; document.addrform.submit();' class='tlpa'><b>" . $this->nbsp($FullName_));
                                if ($Title_ != "") {
                                    $this->out("<br>&nbsp;&nbsp;({$Title_})");
                                }
                                $this->out("</b></a>");
                            }



                            reset($this->ListColumns);
                            foreach($this->ListColumns as $Column) {
                                $value = htmlspecialchars(urldecode($Addr[$Column[name]]));

                                $this->TDNext("class='tlp'"); {
                                    $this->Out("&nbsp;", $value, "&nbsp;");
                                }
                            }

                            // $this->TDNext("class='tlp'"); {
                            //     $this->Out("&nbsp;&nbsp;");
                            //     if ($Addr[name] != "") {
                            //         $this->Out("<a href='compose.php?UID=$this->UID&FACE=$FACE&sNewView=on&To=" . urlencode("\"".htmlspecialchars(URLDecode($Addr[name]))."\" " . "<" . htmlspecialchars(URLDecode($Addr[mailto])) . ">") . "&Ret=ADDR'><font class='tlpa'>" . $this->nbsp($Email_)   . "</font></a>");
                            //     } else {
                            //         $this->Out($Email_);
                            //     }
                            // }
                        }
                    }
                }

                $this->out("<script language='javascript'>");
                $this->out("onTo_List_Click()");
                $this->out("</script>");
            } else {
                $this->TRNext("class='tlp'"); {
                    $this->TDNext("class='tlp' colspan=80 align='center'"); {
                        $this->Out("<font class='tlp'><center>");
                        $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0"); {
                            $this->tds(0, 0, "width='250' height='70'", "<center><font size='+2'>$TEMPL[empty_list]</font></center>");
                        } $this->SubTableDone();
                        $this->Out("</center></font>");
                    }
                }
            }

        } $this->SubTableDone();
    }


    function ToolsBarEdit()
    {
        // shGlobals();
        global $s_Address, $INET_IMG, $FACE;
        global $TEMPL;

        $this->SubTable("width='100%' border=1 cellpadding=10"); {
            $this->OUTS("class=toolsbarl", "");
            $this->OUT("<center>");
            if ($s_Address[GroupType] != "d") {
                $this->OUT(makeButton("type=1& form=addrform& name=sEditSubmit& img=$INET_IMG/addrfoldersave-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfoldersave.gif?FACE=$FACE& title=$TEMPL[bt_submit_ico]") . $this->ButtonBlank);
            }
            $this->OUT(makeButton("type=1& form=addrform& name=sEditCancel& img=$INET_IMG/addrfoldercancel-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfoldercancel.gif?FACE=$FACE& title=$TEMPL[bt_cancel_ico]"));
            $this->OUT("</center>");
        } $this->SubTableDone();
    }


    function ScrEdit()
    {
        global $s_Address, $TEMPL;

        $this->SubTable("width='100%' border=0 cellpadding=10"); {
            $this->OUTS("class=tla", "<center>");
            $this->SubTable(""); {

                $this->TRNext(); {
                    $this->TDNext("class=tla colspan=2", "<center><b><font size='+2'>&nbsp; " . $TEMPL["lb_name_title"] . " &nbsp;</font></b></center>");
                }
                $this->PutInputs(array("name", "middlename", "lastname", "company",
                                       "title", "profession", "mailto"));

                $this->TRNext(); {
                    $this->TDNext("class=tla colspan=2", "<center><b><font size='+2'>&nbsp; " . $TEMPL["lb_home_title"] . " &nbsp;</font></b></center>");
                }
                $this->PutInputs(array("home_address", "home_city", "home_state",
                                       "home_country", "home_zip", "home_phone", "home_phone1",
                                       "home_phone2", "home_fax", "home_mphone", "home_icq",
                                       "home_page"));

                $this->TRNext(); {
                    $this->TDNext("class=tla colspan=2", "<center><b><font size='+2'>&nbsp; " . $TEMPL["lb_biss_title"] . " &nbsp;</font></b></center>");
                }
                $this->PutInputs(array("biss_address", "biss_city", "biss_state",
                                       "biss_country", "biss_zip", "biss_office", "biss_phone",
                                       "biss_phone1", "biss_phone2", "biss_fax",
                                       "biss_mphone", "biss_page"));

            } $this->SubTableDone();
        } $this->SubTableDone();

        $this->OUT("</center>");
    }


    function PutInputs($list)
    {
        global $s_Address, $TEMPL;

        reset($list);
        while(list($n, $v) = each($list)) {
            $this->TRNext(); {
                $this->TDNext("class=tlp", "<center>&nbsp; " . $TEMPL["lb_" . $v] . " &nbsp;</center>");
                $this->TDNext("class=tla", "&nbsp; <INPUT size=40 name='Params[{$v}]' class='toolsbare' value=\"" . $s_Address[Status][Params][$v]    . "\">  &nbsp;");
            }
        }
    }


    function SortIcons($ord)
    {
        global $INET_IMG;
        global $s_Address;

        $rez = "";

        if ($ord != $s_Address[SortOrder]) {
            $rez .= "<a href='javascript:document.addrform.pGetSortOrder.value=\"$ord\"; document.addrform.submit();'><img src='$INET_IMG/sort1.gif' alt='' border='0'></a>";
        }
        if (strtoupper($ord) != $s_Address[SortOrder]) {
            $rez .= "<a href='javascript:document.addrform.pGetSortOrder.value=\"" . strtoupper($ord) . "\"; document.addrform.submit();'><img src='$INET_IMG/sort2.gif' alt='' border='0'></a>";
        }

        return $rez;
    }


    function rNewView()
    {
        global $s_Address;

        $s_Address = array();
        $this->refreshScreen();
    }


    function SetSelecterChar()
    {
        global $pGetSelectedChar, $s_Address;
        $s_Address[SelectedChar] = $pGetSelectedChar;
        if ($pGetSelectedChar == "All") {
            $s_Address[SelectedChar] = "";
        }

        $this->refreshScreen();
    }


    function SetSortOrder()
    {
      global $pGetSortOrder, $s_Address;
      $s_Address[SortOrder] = $pGetSortOrder;

      $this->refreshScreen();
    }


    function ChangeGroup()
    {
        global $pGetSelectedGroup, $s_Address;
        $s_Address[SelectedGroup] = $pGetSelectedGroup;
        if ($pGetSelectedGroup == "NULL") {
            $s_Address[SelectedGroup] = "";
        }

        $this->refreshScreen();
    }


    function rAddFilter()
    {
        global $s_Address;

        $s_Address[Status][fFilter] = trim($s_Address[Status][fFilter]);

        if($s_Address[Status][fFilter] == "") {
            $this->refreshScreen();
        }

        $s_Address[Filter] = urlencode($s_Address[Status][fFilter]);
        $s_Address[SelectedGroup] = "";

        unset($s_Address[Status][fFilter]);

        $this->refreshScreen();
    }


    function rDelFilter()
    {
        global $s_Address;

        $s_Address[Filter]        = "";
        $s_Address[SelectedGroup] = "";
        unset($s_Address[Status][fFilter]);

        $this->refreshScreen();
    }


    function rShortAddSubmit()
    {
        global $fName, $fEmail, $fAddress, $fPhone, $fMPhone;
        global $s_Address;
        global $TEMPL;

        $Status = $s_Address[Status][ShortForm];

        // echo "=$s_Address[GroupType]=<br>";

        if ( $Status[name] == "" ) {
            $s_Address[Mes] = 1;
            $this->refreshScreen();
        }

        if (strpos($Status[name], " ")) {
            $Status[lastname]  = preg_replace("/^(\S+?)\s+(.*)/i", "\\2", $Status[name]);
            $Status[name]      = preg_replace("/^(\S+?)\s+(.*)/i", "\\1", $Status[name]);
            if (strpos($Status[lastname], " ")) {
                $Status[middlename]  = preg_replace("/^(\S+?)\s+(.*)/i", "\\1", $Status[lastname]);
                $Status[lastname]    = preg_replace("/^(\S+?)\s+(.*)/i", "\\2", $Status[lastname]);
            } else {
                $Status[middlename] = "";
            }
        } else {
            $Status[lastname]   = "";
            $Status[middlename] = "";
        }

        $i = NextVal("address_seq");
        $SQL_Fields_List = "sysnum, sysnumusr";
        $SQL_Values_List = "$i, $this->UID";

        reset($Status);
        while(list($field, $value) = each($Status)) {
            if ($field == "mailto" && $value != "") {
                if (!is_emailaddress($value)) {
                    $s_Address[Mes] = 7;
                    $s_Address[MesParam] = $value;
                    $this->refreshScreen();
                }
            }

            $value_ = URLEncode($value);
            if (strlen($value_) > 50) {
                $s_Address[Mes] = 12; $s_Address[MesParam] = $TEMPL["lb_" . $field];
                $this->refreshScreen();
            }

            $SQL_Fields_List .= ", $field";
            $SQL_Values_List .= ", '{$value_}'";
        }

        //echo "insert into address ($SQL_Fields_List) values ($SQL_Values_List)"; exit;

        DBExec("insert into address ($SQL_Fields_List) values ($SQL_Values_List)", __LINE__);
        if ($s_Address[SelectedGroup] != "") {
            $TMP = preg_replace("/'/", "''", $s_Address[SelectedGroup]);
            DBExec("insert into grpaddress (sysnumusr, name, sysnumaddress, ftype) values ($this->UID, '{$TMP}', '$i', '$s_Address[GroupType]')", __LINE__);
        }

        $s_Address[SelectedChar] = "";
        $s_Address[Status] = array();
        $this->refreshScreen();
    }


    function rEditSubmit()
    {
        global $s_Address, $TEMPL;

        $Params =& $s_Address[Status][Params];

        if (($Params[name] == "") || ($Params[mailto] == "")) {
            //$s_Address[Mes] = 1;
            //$this->refreshScreen();
        }

        if ($Params[mailto] != "" && !is_emailaddress($Params[mailto])) {
            $s_Address[Mes] = 7;
            $this->refreshScreen();
        }

        reset($Params);
        while(list($n, $v) = each($Params)) {
            $Params[$n] = URLEncode($v);
            if (strlen($Params[$n]) > 50) {
                $s_Address[Mes] = 12; $s_Address[MesParam] = $TEMPL["lb_" . $n];
                $this->refreshScreen();
            }
        }

        if ($s_Address[EditNumber] == "New") {
            $i = NextVal("address_seq");
            DBExec("INSERT INTO address (sysnum, sysnumusr) VALUES ($i, $this->UID)", __LINE__);
            if ($s_Address[SelectedGroup] != "") {
                $TMP = preg_replace("/'/", "''", $s_Address[SelectedGroup]);
                DBExec("insert into grpaddress (sysnumusr, name, sysnumaddress, ftype) values ($this->UID, '{$TMP}', '$i', '$s_Address[GroupType]')", __LINE__);
            }
            $s_Address[EditNumber] = "u_" . $i;
        }

        $EditType   = substr($s_Address[EditNumber], 0, 1);
        $EditNumber = substr($s_Address[EditNumber], 2);

        if ($EditType == "u" and $EditNumber != 0) {
            reset($Params);
            while(list($n, $v) = each($Params)) {
                DBExec("update address set $n = '$v' where sysnum = '$EditNumber' and sysnumusr = $this->UID", __LINE__);
            }
        }

        $s_Address[Status] = array();
        $this->rEditCancel();
    }


    function AddToGroup($GroupName, $FType)
    {
        global $s_Address;
        global $To_List;

        if (!is_array($To_List)) {
            $s_Address[Mes] = 2;
            $this->refreshScreen();
        }

        if ($GroupName == "") {
            $s_Address[Mes] = 8;
            $this->refreshScreen();
        }

        if ($GroupName != "-1") {
            $r = DBFind("grpaddress", "name = '$GroupName' and sysnumusr = $this->UID", "");
            if ($r->NumRows() == 0) {
                $s_Address[Mes] = 9;
                $this->refreshScreen();
            }
        }

        $this->AddListUsersToGroup($To_List, $GroupName, $FType);

        $s_Address[TO_Selected] = array();
        $s_Address[Status] = array();
        $this->refreshScreen();
    }


    function NewGroup($ftype)
    {
        global $To_List, $fNewGroup;
        global $s_Address;

        if ($fNewGroup == "") {
            $s_Address[Mes] = 3;
            $this->refreshScreen();
        }

        if (strlen($fNewGroup) > 50) {
            $s_Address[Mes] = 13;
            $this->refreshScreen();
        }

        //if (!preg_match("/^[a-z0-9][a-z0-9_\-]*(\.[a-z0-9][a-z0-9_\-]*)*$/i", $fNewGroup)) {
        if (preg_match("/[;,\"]/i", $fNewGroup)) {
            $s_Address[Mes] = 4;
            $this->refreshScreen();
        }

        $TMP = preg_replace("/'/", "''", $fNewGroup);
        $r = DBFind("grpaddress", "name = '$TMP' and sysnumusr = $this->UID", "");
        if ($r->NumRows() != 0 || $fNewGroup == $this->DOMAIN->name()) {
            $s_Address[Mes] = 5;
            $this->refreshScreen();
        }

        $this->AddUserToGroup(0, $fNewGroup, $ftype);
        if (is_array($To_List) && count($To_List) > 0) {
            $this->AddListUsersToGroup($To_List, $fNewGroup, $ftype);
        }

        $s_Address[TO_Selected] = array();
        $s_Address[Status] = array();
        $this->refreshScreen();
    }


    function AddListUsersToGroup($ListUsers, $NameGroup, $TypeGroup)
    {
        //echo  ShArr($ListUsers);
        reset($ListUsers);
        while (list($n, $v) = each($ListUsers)) {
            $m = substr($v, 0, 1);
            $v = substr($v, 2);

            if ( $m == "u" ) {
                $r_add = DBExec("select sysnumusr from address where sysnum = $v", __LINE__);
                if ($r_add->NumRows() == 1 and $r_add->sysnumusr() == $this->UID) {
                    $this->AddUserToGroup($v, $NameGroup, $TypeGroup);
                #} else {
                # echo $r_add->NumRows(), " ", $r_add->sysnumusr()"
                }
            } else if ( $m == "f" || $m == "g") {
                $r_add = DBExec("select sysnum from address where sysnum in (SELECT sysnumaddress from grpaddress where sysnumusr = $this->UID and name = '$v') and sysnumusr = $this->UID", __LINE__);
                while (!$r_add->eof()) {
                    $this->AddUserToGroup($r_add->sysnum(), $NameGroup, $TypeGroup);
                    $r_add->Next();
                }
            }
        }
    }


    function AddUserToGroup($Num, $NameGroup, $TypeGroup)
    {
        // Стирать адрес Num изо всех папок (ftype = 'f');
        if ($TypeGroup == "f" && $Num != 0) { // Если стирать с Num равным 0 то будут стираться созданные пустые папки
            // echo "$Num<br>";
            DBExec("DELETE FROM grpaddress WHERE sysnumaddress = $Num and ftype = 'f'", __LINE__);
        }


        // Если имя папки равно -1 то переносим в корень и создавать записи не надо
        if ($NameGroup != "-1") {
            // Если в указаной папке или группе нет адреса Num создать его;
            $TMP = preg_replace("/'/", "''", $NameGroup);
            $r_grp = DBExec("select * from grpaddress where sysnumusr = $this->UID and" .
                                                          " name = '$TMP' and" .
                                                          " sysnumaddress = '$Num' and" .
                                                          " ftype = '$TypeGroup'", __LINE__
                        );
            if ($r_grp->NumRows() == 0) {
                DBExec("insert into grpaddress (sysnumusr, name, sysnumaddress, ftype) values ($this->UID, '$TMP', '$Num', '$TypeGroup')", __LINE__);
            }
        }
    }


    function rNewContact()
    {
        global $s_Address;

        $s_Address[EditNumber] = "New";
        $s_Address[Status]     = array();
        $this->refreshScreen();
    }


    function rDeleteContact()
    {
        global $To_List;
        global $s_Address;

        if (!is_array($To_List) || count($To_List) == 0) {
            $s_Address[Mes] = 10;
            $this->refreshScreen();
        }

        //echo sharr($To_List); exit;

        $s = ""; // список для удаления юзеров
        $p = ""; // список для удаления юзеров из их групп
        $g = ""; // список для удаления групп

        reset($To_List);
        while (list($n, $v) = each($To_List)) {
            if (substr($v, 0, 1) == "u") {
                $s .= $s == "" ? "" : " or ";
                $s .= "sysnum = " . substr($v, 2);
                $p .= $p == "" ? "" : " or ";
                $p .= "sysnumaddress = " . substr($v, 2);
            } else if (substr($v, 0, 1) == "f" || substr($v, 0, 1) == "g") {
                $g .= $g == "" ? "" : " or ";
                $g .= "name = '" . preg_replace( "/'/", "''", substr($v, 2) ) . "'";
            }
        }

        if (($s . $p) != "") {
            DBExec("DELETE FROM address where ($s) and sysnumusr = $this->UID", __LINE__);
            DBExec("DELETE FROM grpaddress where ($p) and sysnumusr = $this->UID", __LINE__);
        }

        if ($g != "") {
            DBExec("DELETE FROM grpaddress where ($g) and sysnumusr = $this->UID", __LINE__);
        }

        $s_Address[TO_Selected] = array();
        $s_Address[Status] = array();
        $this->refreshScreen();
    }


    function rDuplicateContact()
    {
        global $s_Address;
        global $To_List;

        if (!is_array($To_List) || count($To_List) == 0) {
            $s_Address[Mes] = 14;
            $this->refreshScreen();
        }

        if (count($To_List) != 1) {
            $s_Address[Mes] = 15;
            $this->refreshScreen();
        }

        reset($To_List);
        list($n, $v) = each($To_List);
        if (substr($v, 0, 1) != "u") {
            $s_Address[Mes] = 16;
            $this->refreshScreen();
        }

        $r_add = DBFind("address", "sysnum = " . substr($v, 2) . " and sysnumusr = $this->UID", "");
        if ($r_add->NumRows() != 1) {
            $s_Address[Status] = array();
            $this->rEditCancel();
        }

        $s_Address[EditNumber] = "New";
        $s_Address[Status]     = array();

        for($i=0; $i < $r_add->numfields(); $i++) {
            $s_Address[Status][Params][$r_add->fieldname($i)] = URLDecode($r_add->Field($i));
        }
        unset($s_Address[Status][Params][sysnum]);
        $s_Address[Status][Params][name] .= " (duplicate)";

        $this->refreshScreen();
    }


    function rToEditContact()
    {
        global $s_Address, $pAddressEditCont;
        global $fName, $fEmail, $fAddress, $fPhone, $fMPhone;

        $s_Address[EditNumber] = $pAddressEditCont;

        if ($s_Address[EditNumber] == "New") {
            $s_Address[Status] = array();
        } else {
            $m = substr($s_Address[EditNumber], 0, 1);
            $v = substr($s_Address[EditNumber], 2);

            if($m == "u") {
                $r_add = DBFind("address", "sysnum = $v and sysnumusr = $this->UID", "");

                if ($r_add->NumRows() != 1) {
                    $s_Address[Status] = array();
                    $this->rEditCancel();
                }

                for($i=0; $i < $r_add->numfields(); $i++) {
                    $s_Address[Status][Params][$r_add->fieldname($i)] = URLDecode($r_add->Field($i));
                }
            } else if ($m == "s") {
                $arr = ReadFromUsr($v);

                if ($arr["firstname"] . $arr["lastname"] != "") {
                  $s_Address[Status][Params][name]     = htmlspecialchars($arr["firstname"]);
                  $s_Address[Status][Params][lastname] = htmlspecialchars($arr["lastname"]);
                } else {
                  $s_Address[Status][Params][name]     = htmlspecialchars($arr["name"]);
                  $s_Address[Status][Params][lastname] = "";
                }
                $s_Address[Status][Params][mailto] = htmlspecialchars($arr["name"] . "@". $s_Address[SelectedGroup]);

                $s_Address[Status][Params][home_address] = htmlspecialchars($arr[address]);
                $s_Address[Status][Params][home_phone]   = htmlspecialchars($arr[phone]);
                $s_Address[Status][Params][home_mphone]  = htmlspecialchars("");
            } else {
                $this->rEditCancel();
            }
        }

        $this->refreshScreen();
    }


    function rEditCancel()
    {
        global $s_Address;

        $s_Address[EditNumber] = "";
        $s_Address[Status] = array();
        $this->refreshScreen();
    }


    function rExit()
    {
        global $INET_SRC, $FACE;

        header("Location: $INET_SRC/welcome.php?UID={$this->UID}&FACE={$FACE}");
        exit;
    }


    function ToMessage()
    {
        global $s_Address, $s_Compose, $FACE;

        if (!is_array($s_Address[TO_Selected]) || count($s_Address[TO_Selected]) == 0) {
            $s_Address[Mes] = 11;
            $this->refreshScreen();
        }

        session_register("s_Compose");
        $s_Compose = array();

        $this->GetSelectedAddress($To);

        $s_Compose[Status][fTO] = URLDecode($To);

        $s_Compose[Ret] = "ADDR";

        header("Location: $INET_SRC/compose.php?UID=$this->UID&FACE=$FACE&sNewView=on&Ret=ADDR");
        exit;
    }


    function GetSelectedAddress(&$To)
    {
        global $s_Address;

        $To = GetSelectedAddressGroup($s_Address[TO_Selected], $this->UID);
    }

    function script()
    {
        screen::script();
        global $INET_SRC, $FACE;
        echo "<script language='javascript' src='$INET_SRC/address.js'></script>\n";
    }

    function refreshScreen()
    {
        global $_SERVER, $SCRIPT_NAME, $INET_SRC, $FACE;

        parse_str($_SERVER['QUERY_STRING'], $UrlArray);

        unset($UrlArray[sNewView]);
        unset($UrlArray[UID]);
        unset($UrlArray[FACE]);

        $URLString = "$INET_SRC$SCRIPT_NAME?UID=$this->UID&FACE=$FACE";

        reset($UrlArray);
        while(list($n, $v) = each($UrlArray)) {
            if (!is_array($v)) {
                $URLString .= "&" . $n . "=" . urlencode($v);
            } else {
                reset($v);
                while(list($ins_n, $ins_v) = each($v)) {
                    $URLString .= "&" . $n . "[$ins_n]=" . urlencode($ins_v);
                }
            }
        }

        header("Location: $URLString");
        exit;
    }
} // end of class


function CompareAddresses($a, $b)
{
    global $s_Address;

    $weight = array("d" => 1, "f" => 2, "g" => 2, "u" => 3, "s" => 4);

    if ($s_Address[SortOrder] == "") {
        $s_Address[SortOrder] = "fullname";
    }


    //echo "-{$a[ftype]}-{$a[fullname]}-{$a[name]}-" . strtoupper($a[name]) . "={$b[ftype]}-{$b[fullname]}-{$b[name]}-" . strtoupper($b[name]) . "-<br>\n";

    if ($weight[$a[ftype]] < $weight[$b[ftype]]) return -1;
    if ($weight[$a[ftype]] > $weight[$b[ftype]]) return 1;

    switch ($s_Address[SortOrder]) {
        case "t" :
            if ($a[ftype] < $b[ftype]) return -1;
            if ($a[ftype] > $b[ftype]) return 1;
            break;
        case "T" :
            if ($a[ftype] < $b[ftype]) return 1;
            if ($a[ftype] > $b[ftype]) return -1;
            break;
        default:
            $f1 = ($a[strtolower($s_Address[SortOrder])]);
            $f2 = ($b[strtolower($s_Address[SortOrder])]);
            if (strtoupper($s_Address[SortOrder]) != $s_Address[SortOrder]) {
                if ($f1 < $f2) { return -1; }
                if ($f1 > $f2) { return 1;  }
            } else {
                if ($f1 < $f2) { return 1;  }
                if ($f1 > $f2) { return -1; }
            }
            //break;
    }

    $f1 = strtoupper ($a[fullname]);
    $f2 = strtoupper ($b[fullname]);
    if ($f1 < $f2) return -1;
    if ($f1 > $f2) return 1;

    $f1 = strtoupper ($a[sysnum]);
    $f2 = strtoupper ($b[sysnum]);
    if ($f1 < $f2) return -1;
    if ($f1 > $f2) return 1;
    return 0;
} // end of function CompareAddresses($a, $b)


function GetSelectedAddressGroup($List, $UID)
{
    $Rez = "";

    if (is_array($List)) {
        $u = "";
        $n = "";
        $s = "";

        reset($List);
        while (list($n, $v) = each($List)) {
            $m = substr($v, 0, 1);
            $v = substr($v, 2);

            if ($m == "u") {
                $u .= $u == "" ? "" : " or ";
                $u .= "sysnum = $v";
            } else if ($m == "s") {
                $s .= $s == "" ? "" : " or ";
                $s .= "sysnum = $v";
            } else  if ($m == "g" || $m == "f") {
                $g .= $g == "" ? "" : " or ";
                $g .= "name = '$v'";
            }
        }

        $k = array();

        if ($u != "") {
            $r_addr=DBFind("address", "($u)", "");
            while(!$r_addr->EOF()) {
                if ($r_addr->mailto() != "") {
                    $k[$r_addr->sysnum()] = $r_addr->sysnum();
                    $Rez .= ($Rez != "" ? ", " : "");

                    $name = $r_addr->name();
                    if ($r_addr->middlename() != "") {
                        $name .= ($name != "" ? " " : "") . $r_addr->middlename();
                    }
                    if ($r_addr->lastname() != "") {
                        $name .= ($name != "" ? " " : "") . $r_addr->lastname();
                    }
                    $Rez .= ($name != "" ? "\"" . URLDecode($name) . "\" " : "");

                    $Rez .= URLDecode("<".$r_addr->mailto().">");
                }
                $r_addr->Next();
            }
        }

        if ($s != "") {
            $r_addr=DBFind("usr", "($s)", "");
            while(!$r_addr->EOF()) {
              $Rez .= ($Rez != "" ? ", " : "");

              $arr = ReadFromUsr($r_addr->sysnum());
              if ($arr["firstname"] . $arr["lastname"] != "") {
                $Rez .= "\"" . $arr["firstname"] . " " . $arr["lastname"]. "\" ";
              } else {
                $Rez .= "\"" . $arr["name"] . "\" ";
              }
              $Rez .=  "<". $arr["name"] . "@". $arr["domainname"] .">";

              $r_addr->Next();
            }
        }

        if ($g != "") {
            $r_addr = DBExec("select * from address where sysnum in (SELECT sysnumaddress from grpaddress where sysnumusr = $UID and ($g))", __LINE__);
            while(!$r_addr->EOF()) {
                if (!isset($k[$r_addr->sysnum()])) {
                    $k[$r_addr->sysnum()] = $r_addr->sysnum();
                    $Rez .= ($Rez != "" ? ", " : "");
                    $Rez .= ($r_addr->name() != "" ? "\"".URLDecode($r_addr->name())."\" " : "");
                    $Rez .= URLDecode("<".$r_addr->mailto().">");
                    $r_addr->Next();
                }
            }
        }
    }

    return $Rez;
} // end of function GetSelectedAddressGroup


function ReadFromUsr($Num)
{
    $UsrFields = array("sysnum", "name", "password", "lev", "country", "domainname");
    $r_usr = DBFind("usr, domain", "usr.sysnum = $Num and domain.sysnum = usr.sysnumdomain", "usr.*, domain.name as domainname");
    while (list($n, $fName) = each($UsrFields)) {
      $Rez[$fName] = URLDecode(trim($r_usr->Field($fName)));
    }

    $r_usr = DBFind("usr_ua", "sysnumusr = $Num", "");

    for ($r_usr->set(0); !$r_usr->eof(); $r_usr->Next()) {
      $fName  = $r_usr->name();
      $Value = URLDecode(trim($r_usr->value()));
      $Rez[$fName] = $Value;
    }

    return $Rez;
} // end of function ReadFromUsr($Num)




}

/*

(
sysnum         int8,            sysnum     ,
sysnumusr      int8,            sysnumusr  ,
name           varchar(50),     name       ,
middlename     varchar(50),     ''         ,
lastname       varchar(50),     ''         , lastname   ,
company        varchar(50),     ''         , company    ,
title          varchar(50),     ''         , title      ,
mailto         varchar(50),     mailto     ,
profession     varchar(50),     ''         ,
home_address   varchar(50),     address    ,
home_city      varchar(50),     ''         ,
home_state     varchar(50),     ''         ,
home_country   varchar(50),     ''         ,
home_zip       varchar(50),     ''         ,
home_phone     varchar(50),     phone      ,
home_phone1    varchar(50),     ''         , phone1     ,
home_phone2    varchar(50),     ''         , phone2     ,
home_fax       varchar(50),     ''         , fax        ,
home_mphone    varchar(50),     mphone     ,
home_icq       varchar(50),     ''         ,
home_page      varchar(50),     ''         ,
biss_address   varchar(50),     ''         ,
biss_office    varchar(50),     ''         ,
biss_city      varchar(50),     ''         ,
biss_state     varchar(50),     ''         ,
biss_country   varchar(50),     ''         ,
biss_zip       varchar(50),     ''         ,
biss_phone     varchar(50),     ''         ,
biss_phone1    varchar(50),     ''         ,
biss_phone2    varchar(50),     ''         ,
biss_fax       varchar(50),     ''         ,
biss_mphone    varchar(50),     ''         ,
biss_page      varchar(50)      ''
)


 sysnum    | bigint                | not null default nextval('address_seq'::text)
 sysnumusr | integer               |
 name      | character varying(50) |
 address   | character varying(50) |
 phone     | character varying(20) |
 mphone    | character varying(20) |
 mailto    | character varying(50) |
 lastname  | character varying(50) |
 title     | character varying(50) |
 company   | character varying(50) |
 phone1    | character varying(50) |
 phone2    | character varying(50) |
 fax       | character varying(50) |


*/

?>
