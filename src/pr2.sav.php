<?php

include("_config.inc.php");
require "tools.inc.php";
require "file.inc.php";
require("screen.inc.php");

class myscreen extends screen {


        function myscreen()
        {
          global $Mes;
          global $SelGroup, $SelFolder;
          global $Group, $GroupType;

          $this->screen(); // inherited constructor
          $this->SetTempl("address");

          if ($Group != "") {
            $r_grp = DBFind("grpaddress", "sysnumusr = $this->UID and name = '$Group'", "");
            if ($r_grp->NumRows() == 0) {
              $GroupType = "";
            } else {
              $GroupType = $r_grp->ftype();
            }
          }

          $this->Request_actions["sAddrAdd"]        = "AddrAdd()";
          $this->LastSelected();
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
                global $Get, $Group, $GroupType;
                global $EditCont, $Sort;
                global $SelectTO;

                $numrow = 1;
                $this->Data = array();
                $Displayed = "";

                $r_grp = DBFind("grpaddress", "sysnumusr = $this->UID group by name, ftype", "name, ftype, count(sysnumaddress)");
                for($r_grp->Set(0); !$r_grp->eof(); $r_grp->Next()) {
                  $data = array();
                  for($i=0; $i<$r_grp->numfields(); $i++) {
                    $data[$r_grp->fieldname($i)] = $r_grp->Field($i);
                  }

                  if ($Group == "") {
                    $Displayed .= ($Displayed == "" ? "" : ",") . $data[ftype] . "_" .$data[name];
                  }

                  $this->Data[$numrow++] = $data;
                }

                if ($Group == "") {
                  $r_add = DBFind("address", "sysnumusr = $this->UID and sysnum not in (select sysnumaddress from grpaddress where sysnumusr = $this->UID and ftype = 'f')", "");
                } else {
                  $r_add = DBExec("select * from address where sysnum in (select sysnumaddress from grpaddress where sysnumusr = $this->UID and name = '$Group')");
                }
                for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
                  $data = array();
                  $data[ftype]="u";
                  for($i=0; $i<$r_add->numfields(); $i++) {
                    $data[$r_add->fieldname($i)] = $r_add->Field($i);
                  }

                  $Displayed .= ($Displayed == "" ? "" : ",") . "u_" .$data[sysnum];

                  $this->Data[$numrow++] = $data;
                }

                uasort($this->Data, "CompareAddresses");
                #=========================================

                $this->out("<form method='post' name='addrform'>");
                $this->SaveSubCallFlags();
                $this->out("<input type='hidden' name='Get'       value='$Get'>");
                $this->out("<input type='hidden' name='Group'     value='$Group'>");
                $this->out("<input type='hidden' name='EditCont'  value='$EditCont'>");
                $this->out("<input type='hidden' name='Sort'      value='$Sort'>");
                $this->out("<input type='hidden' name='Displayed' value='$Displayed'>");
                $this->out("<input type='hidden' name='SelectTO'  value='$SelectTO'>");

                screen::display();

                $this->out("</form>");
        }

        function LastSelected() {
                global $SelectTO;
                global $To_List, $To_Select, $Displayed;

                $sel  = split(',', $SelectTO);
                _reset($sel);
                while(list($n, $v) = _each($sel)) {
                  $To_Select[$v] = $v;
                }

                $disp = split(',', $Displayed);
                $Displayed = ""; // chtoby pri powtornom wyzove ne zateret' pomechenye

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
                $SelectTO = "";
                while(list($n, $v) = _each($To_Select)) {
                  $SelectTO .= ($SelectTO == "" ? "" : ",") . $v;
                }
        }

        function Scr()
        {
                 global $INET_IMG, $TEMPL, $FACE;
                 global $Get, $Group, $GroupType;
                 global $SubCallFlags;
                 global $SelectAll;
                 global $To_Select;


                 $this->SubTable("width='100%' border=0 cellpadding='3' cellspacing='0' class='tab'");
                     $this->TDS(0, 0, "class='toolsbarl' colspan = '7' nowrap", "");
                         $this->out("<a href='javascript:document.addrform.Get.value=\"\"; document.addrform.submit();'><font class='toolsbara'>$TEMPL[lb_all]</font></a> &nbsp");
                         $let = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
                         reset($let);
                         while (list($n, $v) = each($let)) {
                             if ($v != $Get) {
                               #$this->out("<a href='$SCRIPT_NAME?UID=$this->UID&Get=$v'>$v</a>&nbsp;");
                               $this->out("<a href='javascript:document.addrform.Get.value=\"$v\"; document.addrform.submit();'><font class='toolsbara'>$v</font></a>&nbsp;");
                             } else {
                               $this->out("<font class='toolsbarl'>$v</font>&nbsp;");
                             }
                         }
                         // $this->out("<hr>");

                         if ($Group != "") {
                             if ($GroupType == "") {
                               $this->Out("<i>Internal Error. Folder Not Found.</i>");
                               return;
                             }
                             //$this->out("<br><a href='javascript:document.addrform.Group.value=\"\"; document.addrform.submit();'><img src='$INET_IMG/up2.gif' border=0 align='ABSMIDDLE'></a>");
                             $this->out("<br>" . makeButton("type=2& form= addrform& name=Step_up& img=$INET_IMG/addrfolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_step_up_ico]& onclick=javascript:document.addrform.Group.value=''; document.addrform.submit();") . $this->ButtonBlank);
                             $this->out("<b>$Group</b>");
                         }
                 $this->SubTableDone();

                 $this->out("<img src='$INET_IMG/filler3x1.gif'>");

                 $this->SubTable("width='100%' border=0 cellpadding='1' cellspacing='1' class='tab'");

                 $this->TDS(2, 1, "class='ttp'", "<input type='image' src='$INET_IMG/sel_all.gif' name='SelectAll_1' border=0 title='$TEMPL[select_all_ico]'>");
                 $this->TDS(2, 2, "class='ttp'", $this->SortIcons("t"));
                 $this->TDS(2, 3, "class='ttp' width='50%'", "$TEMPL[lb_name]&nbsp;" . $this->SortIcons("n"));
                 $this->TDS(2, 4, "class='ttp' width='50%'", "$TEMPL[lb_email]&nbsp;" . $this->SortIcons("m"));

                 $r_add = DBFind("address", "sysnumusr = $this->UID", "");
                 for($r_add->Set(0); !$r_add->eof(); $r_add->Next()) {
                   for($i=0; $i<$r_add->numfields(); $i++) {
                     $ADD[$r_add->Row() + 1][$r_add->fieldname($i)] = $r_add->Field($i);
                   }
                 }

                 //$this->TRS(3, "height=30");
                 //$this->TDS(3, 1, "class='tla' colspan='3' align='center' height=30", "<input type='submit' value='Add' Name='sAddrAdd' class='toolsbarb'>");
                 $this->TDS(3, 1, "class='tla' colspan='2' align='center' height=30", makeButton("type=1& form=addrform& name=sAddrAdd& title=$TEMPL[bt_add_ico]& img=$INET_IMG/addrfolderadd-passive.gif?FACE=$FACE& imgact=$INET_IMG/addrfolderadd.gif?FACE=$FACE&"));

                 $this->TDS(3, 2, "class='tla' colspan='1' align='center' height=30", "&nbsp;<input name='AddrName'  value='$AddrName' class='toolsbare'>&nbsp;");
                 $this->TDS(3, 3, "class='tla' colspan='1' align='center' height=30", "&nbsp;<input name='AddrEMail' value='$AddrEMail' class='toolsbare'>&nbsp;");

                 for (_reset($this->Data); $key = _key($this->Data); _next($this->Data)) {
                   $Addr = $this->Data[$key];

                   $Name_    = URLDecode($Addr[name]);
                   $Email_   = URLDecode($Addr[mailto]);
                   $Address_ = URLDecode($Addr[address]);
                   $MPhone_  = URLDecode($Addr[mphone]);

                   if (($Get != "") && substr(ucfirst($Name_), 0, 1) != $Get) {
                      continue;
                   }

                   if ($Addr[ftype] != "u") {
                        if ($Group != "") {
                            continue;
                        }
                        $Name_m = "<span title=\"" . htmlspecialchars($Name_) . "\">" . ReformatToLeft($Name_, 25) . "</span>";
                        $Email_m = "&nbsp;"; //ReformatToLeft("&nbsp;", 25);

                        $this->TRNext("valign='top'");
                        // echo $Addr[ftype] . "_" . $Addr[name]. "<br>";
                        $CHECKED = ($To_Select[$Addr[ftype] . "_" . $Addr[name]] == $Addr[ftype] . "_" . $Addr[name] ? "CHECKED" : "");
                        $this->TDNext("class='tlp' nowrap");
                        $this->Out("<font class='tlp'>" . "<center><input type='checkbox' name='To_List[]' value='$Addr[ftype]_$Name_' $CHECKED></center></font>");

                        $this->TDNext("class='tlp' nowrap");
                        if ($Addr[ftype] == "g") {
                          $this->Out("<a href='javascript:document.addrform.Group.value=\"$Name_\"; document.addrform.submit();'><img src='$INET_IMG/group.gif' title='$TEMPL[open_group_ico]' border=0></a>");
                        } else {
                          $this->Out("<a href='javascript:document.addrform.Group.value=\"$Name_\"; document.addrform.submit();'><img src='$INET_IMG/folder-yellow.gif' border=0 title='$TEMPL[open_folder_ico]'></a>");
                        }

                        $this->TDNext("class='tlp' nowrap");
                        $this->Out("&nbsp;&nbsp;" . "<a href='javascript:document.addrform.Group.value=\"$Name_\"; document.addrform.submit();'><font class='tlpa'><b>" . $this->nbsp($Name_m)    . "</b></font></a>");

                        $this->TDNext("class='tlp' nowrap");
                        $this->Out($Email_m);
                   } else {
                        $Name_ = "<span title=\"" . htmlspecialchars($Name_) . "\">" . ReformatToLeft($Name_, 25) . "</span>";
                        $Email_ = "<span title=\"" . htmlspecialchars($Email_) . "\">" . ReformatToLeft($Email_, 25) . "</span>";

                        $this->TRNext("valign='top'");

                        $CHECKED = ($To_Select["u_" . $Addr[sysnum]] == "u_" . $Addr[sysnum] ? "CHECKED" : "");
                        $this->TDNext("class='tlp' nowrap");
                        $this->Out("<font class='tlp'>" . "<center><input type='checkbox' name='To_List[]' value='u_".$Addr[sysnum]."' $CHECKED></center></font>");

                        $this->TDNext("class='tlp'");
                        $this->Out("<img src='$INET_IMG/contact.gif' border=0>");

                        $this->TDNext("class='tlp' nowrap");
                        $this->Out("&nbsp;&nbsp;" . $this->nbsp($Name_));

                        $this->TDNext("class='tlp' nowrap");
                        $this->Out("&nbsp;&nbsp;" . $this->nbsp($Email_));
                   }
                 }

                 if (count($Addr) == 0) {
                       $this->TRNext("class='tlp'");
                       $this->TDNext("class='tlp' colspan=8 align='center'");
                       $this->Out("<font class='tlp'>");
                       $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0");
                       $this->tds(0, 0, "width='250' height='70'", "<center><font size='+2'>$TEMPL[empty_list]</font></center>");
                       $this->SubTableDone();
                       $this->Out("</font>");
                 }

                 $this->SubTableDone();

              #$this->out("<font color='white'><b>Add New Address to Address Book &rarr;</b></font><br>");
              #$this->SubTable("border=1 width='100%'");
              #        $this->TDNext("class='toolsbarl'");
              #        $this->out("&nbsp;Name");
              #        $this->TDNext("class='toolsbarl'");
              #        $this->out("<input name='AddrName'  value='$AddrName' class='toolsbare'>");
              #        $this->TRNext("");
              #        $this->TDNext("class='toolsbarl'");
              #        $this->out("&nbsp;eMail");
              #        $this->TDNext("class='toolsbarl'");
              #        $this->out("<input name='AddrEMail' value='$AddrEMail' class='toolsbare'>");
              #        $this->out("&nbsp;&nbsp;<input type='submit' value='Add' Name='sAddrAdd' class='toolsbarb'>");
              #$this->SubTableDone();
        }

        function SortIcons($ord)
        {
                 global $INET_IMG;
                 global $Sort;

                 $rez = "";

                 #if ($ord != $Sort) {
                 #  $rez .= "<a href='javascript:document.addrform.Sort.value=\"$ord\"; document.addrform.submit();'><img src='$INET_IMG/sort1.gif' alt='' border='0'></a>";
                 #}

                 #if (strtoupper ($ord) != $Sort) {
                 #  $rez .= "<a href='javascript:document.addrform.Sort.value=\"" . strtoupper($ord) . "\"; document.addrform.submit();'><img src='$INET_IMG/sort2.gif' alt='' border='0'></a>";
                 #}

                 if ($ord == $Sort) {
                        $rez = "<a href='javascript:document.addrform.Sort.value=\"" . strtoupper($ord) . "\"; document.addrform.submit();'><img src='$INET_IMG/sort2.gif' alt='' border='0'></a>";
                 } else {
                        $rez = "<a href='javascript:document.addrform.Sort.value=\"$ord\"; document.addrform.submit();'><img src='$INET_IMG/sort1.gif' alt='' border='0'></a>";
                 }

                 return $rez;
        }

        function AddrAdd()
        {
                global $REQUEST_URI;
                global $AddrName, $AddrEMail;

                // echo "$AddrName,= $AddrEMail";

                if (($AddrName == "") or ($AddrEMail == "")) {
                      return;
                }

                DBExec("insert into address (sysnum, sysnumusr, name, mailto, address, phone) values (NextVal('address_seq'), '$this->UID', '$AddrName', '$AddrEMail', '', '')");

                $AddrName = $AddrEMail = "";
        }
} // end class myscreen

function CompareAddresses($a, $b)
{
          global $Sort;
          if ($Sort == "") {
             $Sort = "t";
          }

          switch ($Sort) {
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

$v = new myscreen();
$v->Run();

UnconnectFromDB();

?>
