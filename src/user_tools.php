<?php

require "tools.inc.php";
require "file.inc.php";
require("screen.inc.php");

class CUserToolsScreen extends screen {


    function CUserToolsScreen() // constructor
    {
        global $Mes;
        global $TEMPL;

        $this->screen(); // inherited constructor
        $this->SetTempl("user_tools");
        session_register("s_UserTools");

        $this->PgTitle = "<b>$TEMPL[title]</b> ";
        $this->Request_actions["sCancel"]       = "Cancel()";
        $this->Request_actions["sEraseTrash"]   = "EraseTrash()";
        $this->Request_actions["sEraseOldMes"]  = "EraseOldMes()";
        $this->Request_actions["sDelFile"]      = "DelFile()";

        $this->SaveScreenStatus();
    }


    function SaveScreenStatus()
    {
        parent::SaveScreenStatus("s_UserTools", array("EraseOldMesTime", "EraseOldMesFolder", "selDelFileTime", "chDelFileEmptyFolder"), 0);
    }


    function mes() // overload function
    {
        global $Mes, $MesParam, $s_UserTools, $TEMPL, $Inf, $InfParam;

        if ($Mes == "") {
            $Mes = $s_UserTools[Mes];
            unset($s_UserTools[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_UserTools[MesParam];
            unset($s_UserTools[MesParam]);
        }

        if ($Inf == "") {
            $Inf = $s_UserTools[Inf];
            unset($s_UserTools[Inf]);
        }

        if ($InfParam == "") {
            $InfParam = $s_UserTools[InfParam];
            unset($s_UserTools[InfParam]);
        }

        if ($Mes != "") {
            if ($TEMPL[err_mes . $Mes] != "") {
                $this->ErrMes(sprintf($TEMPL[err_mes . $Mes], $MesParam));
            } else {
                $this->ErrMes(sprintf("Unknow error number %s %s", $Mes, $MesParam));
            }
        } else {
            if ($Inf != "") {
                if ($TEMPL[inf_mes . $Inf] != "") {
                    $this->InfMes(sprintf($TEMPL[inf_mes . $Inf], $InfParam));
                } else {
                    $this->InfMes(sprintf("Unknow information number %s %s", $Inf, $InfParam));
                }
            }
        }
    }


    function Scr() // overlaped virtual function
    {
        global $View;
        global $INET_IMG, $TEMPL, $s_UserTools;

        $this->out("<form method='post'>");

        $this->SubTable("border='0' cellpadding='0' cellspacing='0' width='80%'"); {
            //-----------------------------------------------------------------------------
            // 1 line
            $this->TRNext(""); {
                $this->TDNext("nowrap class='toolsbarl' colspan=4 style='padding: 3'"); {
                    $this->OUT(makeButton("name=sCancel& value=$TEMPL[bt_exit]& width=100"));
                }
            }

            //-----------------------------------------------------------------------------
            $this->TRNext(""); {
                $this->TDNext("nowrap width='40%'"); {
                    $this->OUT("<img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            //-----------------------------------------------------------------------------
            // 2 line
            $this->TRNext(""); {
                $this->TDNext("nowrap class='tlp' width='40%' style='padding: 3'"); {
                    $this->OUT($TEMPL[lb_del_message]);
                }
                $this->TDNext("nowrap class='tlp' align='center' width='20%' style='padding: 3'"); {
                    $this->OUT("$TEMPL[lb_del_message_in_folder] : ");
                    $this->OUT("<select name='EraseOldMesFolder' class='toolsbare'>");
                    $this->out("<option value=0>" . $TEMPL[lb_del_message_sel_folder]);
                    $r = DBFind("fld", "fld.sysnumusr = $this->UID and fld.ftype <> 5 order by fld.sysnum", "fld.name, fld.sysnum");
                    while(!$r->eof()) {
                        $this->out("<option value=" . $r->sysnum() . ($s_UserTools[Status][EraseOldMesFolder] == $r->sysnum() ? " SELECTED" : "") . ">" . $r->name());
                        $r->next();
                    }
                    $this->out("</select><br>");
                    unset($s_UserTools[Status][EraseOldMesFolder]);
                }

                $this->TDNext("nowrap class='tlp' align='center' width='20%' style='padding: 3'"); {
                    $this->OUT("$TEMPL[lb_del_message_old_by]&nbsp;");
                    $this->OUT("<select name='EraseOldMesTime' class='toolsbare'>"); {
                        $this->out("<option value=0>$TEMPL[lb_del_message_sel_period]");
                        $this->out("<option value=20" . ($s_UserTools[Status][EraseOldMesTime] == 20 ? " SELECTED" : "") . ">$TEMPL[sel_exp_20day]");
                        $this->out("<option value=10" . ($s_UserTools[Status][EraseOldMesTime] == 10 ? " SELECTED" : "") . ">$TEMPL[sel_exp_10day]");
                        $this->out("<option value= 5" . ($s_UserTools[Status][EraseOldMesTime] ==  5 ? " SELECTED" : "") . ">$TEMPL[sel_exp_5day]");
                        $this->out("<option value=31" . ($s_UserTools[Status][EraseOldMesTime] == 31 ? " SELECTED" : "") . ">$TEMPL[sel_exp_31day]");
                    } $this->out("</select><br>");
                    unset($s_UserTools[Status][EraseOldMesTime]);
                }

                $this->TDNext("nowrap class='tlp' align='center' width='20%' style='padding: 3'"); {
                    $this->OUT(makeButton("name=sEraseOldMes& value=$TEMPL[bt_del_message]& width=80"));
                }
            }

            //-----------------------------------------------------------------------------
            $this->TRNext(""); {
                $this->TDNext("nowrap"); {
                    $this->OUT("<img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            //-----------------------------------------------------------------------------
            // 3 line
            $this->TRNext(""); {
                $this->TDNext("nowrap colspan=1 class='tla' style='padding: 3'"); {
                    $this->OUT($TEMPL[lb_del_files]);
                }

                $this->TDNext("nowrap colspan=1 class='tla' style='padding: 3'"); {
                    $this->OUT("<input type='checkbox' name='chDelFileEmptyFolder' value='1'" . ($s_UserTools[Status][chDelFileEmptyFolder] == '1' ? " CHECKED" : "") . ">&nbsp;");
                    $this->OUT($TEMPL[lb_del_files_empty_folder]);
                    unset($s_UserTools[Status][chDelFileEmptyFolder]);
                }

                $this->TDNext("nowrap class='tla' align='center' style='padding: 3'"); {
                    $this->OUT("$TEMPL[lb_del_files_old_by]&nbsp;");
                    $this->OUT("<select name='selDelFileTime'  class='toolsbare'>"); {
                        $this->out("<option value=0>$TEMPL[lb_del_files_sel_period]");
                        $this->out("<option value=20" . ($s_UserTools[Status][selDelFileTime] == 20 ? " SELECTED" : "") . ">$TEMPL[sel_exp_20day]");
                        $this->out("<option value=10" . ($s_UserTools[Status][selDelFileTime] == 10 ? " SELECTED" : "") . ">$TEMPL[sel_exp_10day]");
                        $this->out("<option value= 5" . ($s_UserTools[Status][selDelFileTime] ==  5 ? " SELECTED" : "") . ">$TEMPL[sel_exp_5day]");
                        $this->out("<option value=31" . ($s_UserTools[Status][selDelFileTime] == 31 ? " SELECTED" : "") . ">$TEMPL[sel_exp_31day]");
                    } $this->OUT("</select><br>");
                    unset($s_UserTools[Status][selDelFileTime]);
                }

                $this->TDNext("nowrap class='tla' align='center' style='padding: 3'"); {
                    $this->OUT(makeButton("name=sDelFile& value=$TEMPL[bt_del_files]& width=80"));
                }
            }

            //-----------------------------------------------------------------------------
            $this->TRNext(""); {
                $this->TDNext("nowrap"); {
                    $this->OUT("<img src='$INET_IMG/filler3x1.gif'>");
                }
            }

            //-----------------------------------------------------------------------------
            // 4 line
            $this->TRNext(""); {
                $this->TDNext("nowrap class='tlp' style='padding: 3'"); {
                    $this->OUT($TEMPL[lb_emp_trash]);
                }

                $this->TDNext("valign='top' colspan=2 nowrap class='tlp' align='center' style='padding: 2'"); {
                    $r_mes = DBExec("SELECT count(*) from msg where msg.sysnumfld = fld.sysnum and fld.ftype = 5 and fld.sysnumusr = '$this->UID'", __LINE__);
                    $this->OUT(sprintf($TEMPL[lb_emp_trash_contains], $r_mes->count()));
                }


                $this->TDNext("valign='top' nowrap class='tlp' align='center' style='padding: 2'"); {
                    $this->OUT(makeButton("name=sEraseTrash& value=$TEMPL[bt_emp_trash]& width=80"));
                }
            }
        } $this->SubTableDone();

        $this->out("</form>");

        //$this->SubTable("border = 1");
        //$this->out("=".sharr($GLOBALS[_SESSION]));
        //$this->out("=".sharr($s_UserTools));
        //$this->out("=".shGLOBALS());
        //$this->SubTableDone();

        unset($s_UserTools[Status]);
    }


    function Cancel()
    {
        global $FACE, $INET_SRC, $s_UserTools;

        unset($s_UserTools[Status]);

        header("Location: " . "$INET_SRC/welcome.php?UID=" . $this->UID . "&FACE=$FACE");
        exit;
    }


    function EraseTrash_old()
    {
        global $s_UserTools;

        $r = DBFind("msg, fld", "msg.sysnumfld = fld.sysnum and fld.ftype = 5 and fld.sysnumusr = $this->UID", "msg.sysnum");

        $s_UserTools[Inf]      = 3;
        $s_UserTools[InfParam] = $r->NumRows();

        while (!$r->eof()) {
            DelMsg($r->sysnum());
            $r->next();
        }

        unset($s_UserTools[Status]);
        $this->refreshScreen();
    }


    function EraseTrash()
    {
        global $s_UserTools;

        DBExec("Begin", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
        $r = DBFind("msg, fld", "msg.sysnumfld = fld.sysnum and fld.ftype = 5 and fld.sysnumusr = $this->UID", "msg.sysnum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

        $s_UserTools[Inf]      = 3;
        $s_UserTools[InfParam] = $r->NumRows();

        while (!$r->eof()) {
            $list[] = $r->sysnum();
            $r->next();
        }
        DBExec("COMMIT", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

        DBExec("begin", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
        DBExec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
        while(list($n, $v) = each($list)) {
            DelMsg($v, false);
        }
        DBExec("COMMIT");

        unset($s_UserTools[Status]);
        $this->refreshScreen();
    }


    function GetLimitDate($ExpDate)
    {
        //echo "$ExpDate<br>";

        if(!ereg("^[0-9]+$", $ExpDate)) {
            $ExpDate = 31;
        }

        if ($ExpDate == 31) {
            $ExpDate = date("d");
        }

        return date ("Y-m-d", mktime (0, 0, 0, date("m"), date("d") - $ExpDate, date("Y"))) . " 23:59";
    }


    function EraseOldMes()
    {
        global $EraseOldMesTime, $EraseOldMesFolder, $s_UserTools;

        if ($EraseOldMesTime == "" || $EraseOldMesTime == 0) {
            $s_UserTools[Mes] = 1;
            $this->refreshScreen();
        }

        if (!ereg("^[0-9]+$", $EraseOldMesFolder) || $EraseOldMesFolder == "" || $EraseOldMesFolder == 0) {
            $s_UserTools[Mes] = 2;
            $this->refreshScreen();
        }

        $lim = $this->GetLimitDate($EraseOldMesTime);

        $r = DBFind("fld", "fld.sysnum = $EraseOldMesFolder and fld.sysnumusr = $this->UID", "", __LINE__);
        if ($r->NumRows() == 0) {
            $s_UserTools[Mes] = 2;
            $this->refreshScreen();
        }
        // echo $r->NumRows(), "<br>";

        $r = DBFind("fld", "fld.ftype = 5 and fld.sysnumusr = $this->UID", "sysnum", __LINE__);
        if ($r->NumRows() == 0) {
            $s_UserTools[Mes] = 3;
            $this->refreshScreen();
        }

        $SQL = "select count(*) from msg where sysnumfld = $EraseOldMesFolder and recev <= '$lim'";
        $r_msg = DBExec($SQL, __LINE__);
        $s_UserTools[Inf]      = 2;
        $s_UserTools[InfParam] = $r_msg->count();

        $SQL = "update msg set sysnumfld = ".$r->sysnum()." where sysnumfld = $EraseOldMesFolder and recev <= '$lim'";
        DBExec($SQL, __LINE__);

        unset($s_UserTools[Status]);
        $this->refreshScreen();
    }


    function DelFile()
    {
        global $selDelFileTime, $chDelFileEmptyFolder, $s_UserTools;

        if ($selDelFileTime == "" || $selDelFileTime == 0) {
            $s_UserTools[Mes] = 1;
            $this->refreshScreen();
        }

        $lim = $this->GetLimitDate($selDelFileTime);

        //echo "$lim<br>$chDelFileEmptyFolder<br>";

        $SQL_From = "from fs fs_exteral where fs_exteral.creat <= '$lim' and fs_exteral.ftype = 'f' and fs_exteral.owner = $this->UID";
        if ($chDelFileEmptyFolder == "1") {
            $SQL_From .= " and (select count(fs.*) from fs where fs.up = fs_exteral.sysnum) = 0";
        } else {
            $SQL_From .= " and fs_exteral.sysnumfile <> 0";
        }

        //echo htmlspecialchars("SELECT * " . $SQL_From), "<br>";

        $NumDeletedFile = 0;
        $r_fs = DBExec("SELECT * " . $SQL_From, __LINE__);
        while ($r_fs->NumRows() <> 0) {
             $NumDeletedFile += $r_fs->NumRows();

            //echo $r_fs->NumRows() , "<br>";

            $SQL = "DELETE FROM fs where sysnum in (select fs_exteral.sysnum $SQL_From)";
            //echo htmlspecialchars($SQL), "<br>"; exit;

            DBExec($SQL, __LINE__);
            $r_fs = DBExec("SELECT * " . $SQL_From, __LINE__);
        }
        //echo $r_fs->NumRows() , "=<br>";

        unset($s_UserTools[Status]);

        $s_UserTools[Inf]      = 1;
        $s_UserTools[InfParam] = $NumDeletedFile;

        $this->refreshScreen();
    }
}


ConnectToDB();

$UserToolsScreen = new CUserToolsScreen();
$UserToolsScreen->Run();

UnconnectFromDB();
exit;

?>
