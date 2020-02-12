<?php

/*

class CDatebookCalendar extends CCalendar
    function CDatebookCalendar($SDate, $TargetDays)
    function getCalendarLink($month, $year)
    function getDateLink($mday, $month, $year)
    function getDateStyle($mday, $month, $year)
    function getToday()
}

class CDatebook extends screen
    function CDatebook()
    function OpenSession()   // overlap virtuals function
    function rCreate()
    function rEditSubmit()
    function rEditCancel()
    function display()       // overlap virtuals function
        function ToolsBar()  // overlap virtuals function
            function ToolsBarList()
            function ToolsBarEdit()
        function Scr()       // overlap virtuals function
            function ScrList()
            function ScrEdit()
    function refreshScreen() // overlap virtuals function
}

function date_pad($year, $mon, $mday)
function infservisselect($list)

*/

include("_config.inc.php");
require("cont.inc.php");
require("file.inc.php");
require("utils.inc.php");
require("calendar.inc.php");

require("db.inc.php");

require("screen.inc.php");


class CDatebookCalendar extends CCalendar
{
    function CDatebookCalendar($SDate, $TargetDays)
    {
        $this->CCalendar(); // inherited constructor
        $this->SDate      = $SDate;
        $this->Today      = getdate(time());
        $this->TargetDays = $TargetDays;
    }


    function getCalendarLink($month, $year)
	{
        global $INET_SRC, $UID, $FACE;

        $mday = $this->SDate[mday];
        $DaysInMonth = $this->getDaysInMonth($month, $year);
        if ($DaysInMonth < $mday) {
            $mday = $DaysInMonth;
        }

        return "$INET_SRC/datebook.php?UID=$UID&FACE=$FACE&SDate=" . urlencode(date_pad($year, $month, $mday));
    }


    function getDateLink($mday, $month, $year)
    {
        global $INET_SRC, $UID, $FACE;

        return "$INET_SRC/datebook.php?UID=$UID&FACE=$FACE&SDate=" . urlencode(date_pad($year, $month, $mday));
    }


    function getDateStyle($mday, $month, $year)
    {
        $style = parent::getDateStyle($mday, $month, $year);

        if ($this->TargetDays[date_pad($year, $month, $mday)]) {
            $style = array('style' => $style['style'] . ($style['style'] == "" ? "" : "; ") . "",
                           'text'  => $style['text']  . ($style['text']  == "" ? "" : "; ") . "font-weight: bold; text-decoration: underline");
        }

        if (($year == $this->Today["year"] && $month == $this->Today["mon"] && $mday == $this->Today["mday"])) {
            $style = array('style' => $style['style'] . ($style['style'] == "" ? "" : "; ") . "",
                           'text'  => $style['text']  . ($style['text']  == "" ? "" : "; ") . "color: red");
        }

        return $style;
    }


    function getToday()
    {
        return $this->SDate;
    }

    var $calendarTable_class            = "body";
    var $calendarHeader_class           = "ttp";
    var $calendarHeader_text_class      = "ttp";

    var $calendarPrevMon_class          = "ttp";
    var $calendarPrevMon_text_class     = "ttp";
    var $calendarNextMon_class          = "ttp";
    var $calendarNextMon_text_class     = "ttp";

    var $calendar_class                 = "tlp";
    var $calendar_text_class            = "tlpa";
    var $calendarToday_class            = "tla";
    var $calendarToday_text_class       = "tlaa";

}



class CDatebook extends screen
{
    function CDatebook()
    {
        global $SDate, $Item;
        global $s_Datebook, $TEMPL, $REQUEST_METHOD;

        $this->screen(); // inherited constructor
        $this->SetTempl("datebook");

        if (preg_match('/^([1-9][0-9]{3})-([0-9]{2})-([0-9]{2})$/', $SDate, $MATH)) {
            $this->SDate = getdate(mktime(12, 0, 0, $MATH[2], $MATH[3], $MATH[1]));
            $this->SDate[orig] = $SDate;
            //echo sharr($this->SDate), "<br>", sharr($MATH), "<br>";
        } else {
            $this->SDate = getdate();
            $this->refreshScreen();
        }

        if ($this->SDate[year] > 2100 || $this->SDate[0] < 0 ) {
            $this->SDate = getdate();
            $this->refreshScreen();
        }


        if ($Item != "" && $Item != "New") {
            if (!ereg("^[0-9]+$", $Item)) {
                $Item = "";
                $this->refreshScreen();
            }
        }

        $this->Request_actions["sCreate"]       = "rCreate()";
        $this->Request_actions["sDelete"]       = "rDelete()";
        $this->Request_actions["sExit"]         = "rExit()";
        $this->Request_actions["sEditSubmit"]   = "rEditSubmit()";
        $this->Request_actions["sEditCancel"]   = "rEditCancel()";

        $this->PgTitle = "<b>$TEMPL[title]</b>";

        if ($REQUEST_METHOD == "POST") {
            $this->SaveScreenStatus("s_Datebook", array("Select", "Params"));
        }
    }


    function OpenSession() // overlap virtuals function
    {
        parent::OpenSession();
        session_register("s_Datebook");
    }


    function rCreate()
    {
        global $Item;
        global $s_Datebook;

        $Item = "New";
        unset($s_Datebook[Status][Params]);
        $this->refreshScreen();
    }


    function rDelete()
    {
        global $s_Datebook;
        global $Select;

        if (!is_array($Select) || count($Select) == 0) {
            $s_Datebook[Mes] = 2;
            $s_Datebook[refresh] = true;
            $this->refreshScreen();
        }

        $s = "";
        reset($Select);
        while(list($n, $v) = each($Select)) {
            if (ereg("^[0-9]+$", $v)) {
                $s .= ($s != "" ? " OR " : "") . "sysnum = {$v}";
            }
        }

        DBExec("BEGIN", __LINE__);
        DBExec("DELETE FROM datebook WHERE ({$s}) AND sysnumusr = $this->UID");
        DBExec("COMMIT", __LINE__);

        $this->refreshScreen();
    }


    function rExit()
    {
        global $INET_SRC, $FACE;

        header("Location: $INET_SRC/welcome.php?UID={$this->UID}&FACE={$FACE}");
        exit;
    }


    function rEditSubmit()
    {
        global $Item;
        global $s_Datebook;

        $Params =& $s_Datebook[Status][Params];

        $Temp = getdate(mktime($Params[BeginHour], $Params[BeginMin], 0, $Params[BeginMonth] + 1, $Params[BeginDay], $Params[BeginYear]));
        $Params[BeginMin]    =  $Temp[minutes]; $Params[BeginHour]   =  $Temp[hours];
        $Params[BeginDay]    =  $Temp[mday];    $Params[BeginMonth]  =  $Temp[mon] - 1;
        $Params[BeginYear]   =  $Temp[year];
        $DateBegin = date_pad($Params[BeginYear], $Params[BeginMonth] + 1, $Params[BeginDay], $Params[BeginHour], $Params[BeginMin]);


        $Temp = getdate(mktime($Params[EndHour], $Params[EndMin], 0, $Params[EndMonth] + 1, $Params[EndDay], $Params[EndYear]));
        $Params[EndMin]    =  $Temp[minutes]; $Params[EndHour]   =  $Temp[hours];
        $Params[EndDay]    =  $Temp[mday];    $Params[EndMonth]  =  $Temp[mon] - 1;
        $Params[EndYear]   =  $Temp[year];
        $DateEnd = date_pad($Params[EndYear], $Params[EndMonth] + 1, $Params[EndDay], $Params[EndHour], $Params[EndMin]);

        if ($DateEnd < $DateBegin) {
            $s_Datebook[Mes]     = 1;
            $s_Datebook[refresh] = true;
            $this->refreshScreen();
        }

        if ($Item == "New") {
            DBExec("BEGIN", __LINE__);
            DBExec(" INSERT INTO datebook (sysnum, sysnumusr, begindate, enddate, subject, memo) " .
                   " VALUES (nextval('datebook_seq'::text), '{$this->UID}', '{$DateBegin}', '{$DateEnd}', '" . urlencode(trim($Params[subject])) . "', '" . urlencode(trim($Params[memo])) . "' )", __LINE__);
            DBExec("COMMIT", __LINE__);
        } else {
            DBExec("BEGIN", __LINE__);
            $r_datebook = DBExec("SELECT * FROM datebook WHERE sysnum = '{$Item}' and sysnumusr = {$this->UID} FOR UPDATE", __LINE__);
            if ($r_datebook->NumRows() != 1) {
                DBExec("ROLLBACK", __LINE__);
                $Item = "";
                $this->refreshScreen();
            }

            DBExec(" UPDATE datebook SET begindate = '{$DateBegin}', " .
                                       " enddate = '{$DateEnd}', " .
                                       " subject = '" . urlencode(trim($Params[subject])) . "', " .
                                       " memo = '" . urlencode(trim($Params[memo])) . "' " .
                                       " WHERE sysnum = '{$Item}'" , __LINE__);


            DBExec("COMMIT", __LINE__);
        }

        $this->SDate[year] = $Params[BeginYear];
        $this->SDate[mon]  = $Params[BeginMonth] + 1;
        $this->SDate[mday] = $Params[BeginDay];
        $this->rEditCancel();
    }


    function rEditCancel()
    {
        global $Item;

        $Item = "";
        unset($s_Datebook[Status][Params]);
        $this->refreshScreen();
    }


    function display() // overlaping inherited function
    {
        global $s_Datebook;

        $this->out("<form method='post' name='mainform'>"); {
            screen::display();
        } $this->out("</form>");
    }


    function ToolsBar() // overlap virtuals function
    {
        global $Item;

        if ($Item == "") {
            $this->ToolsBarList();
        } else {
            $this->ToolsBarEdit();
        }
    }


    function ToolsBarList()
    {
        global $INET_IMG, $TEMPL;

        $this->SubTable("border=0 width='100%' cellspacing = '0' cellpadding = '3' class='toolsbarb'"); {
            $this->TRNext("class='toolsbarl'"); {
                $this->TDNext("nowrap"); {
                    $this->out(makeButton("type=1& form=mainform& name=sCreate& class=toolsbarbg& img=$INET_IMG/datebookcreate-passive.gif?FACE=$FACE& imgact=$INET_IMG/datebookcreate.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_new_item_ico]"), $this->ButtonBlank);
                    $this->out(makeButton("type=1& form=mainform& name=sDelete& class=toolsbarbg& img=$INET_IMG/datebookdelete-passive.gif?FACE=$FACE& imgact=$INET_IMG/datebookdelete.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_del_item_ico]"), $this->SectionBlank);
                    $this->out(makeButton("type=1& form=mainform& name=sExit& class=toolsbarbg& img=$INET_IMG/datebookexit-passive.gif?FACE=$FACE& imgact=$INET_IMG/datebookexit.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_exit_item_ico]"));
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler2x1.gif'><br>");
    }


    function ToolsBarEdit()
    {
        global $INET_IMG, $TEMPL;

        $this->SubTable("border=0 width='100%' cellspacing = '0' cellpadding = '3' class='toolsbarb'"); {
            $this->TRNext("class='toolsbarl'"); {
                $this->TDNext("nowrap"); {
                    $this->OUT(makeButton("type=1& form=mainform& name=sEditSubmit& img=$INET_IMG/datebooksave-passive.gif?FACE=$FACE& imgact=$INET_IMG/datebooksave.gif?FACE=$FACE& title=$TEMPL[bt_editsubmit_ico]") . $this->ButtonBlank);
                    $this->OUT(makeButton("type=1& form=mainform& name=sEditCancel& img=$INET_IMG/datebookcancel-passive.gif?FACE=$FACE& imgact=$INET_IMG/datebookcancel.gif?FACE=$FACE& title=$TEMPL[bt_editcancel_ico]"));
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler2x1.gif'><br>");
    }


    function Scr() // overlap virtuals function
    {
        global $s_Datebook, $Item;

        if ($Item == "") {
            $this->ScrList();
        } else {
            $this->ScrEdit();
        }

        $s_Datebook[refresh] = false;
    }


    function ScrList()
    {
        global $s_Datebook;
        global $SDate, $TEMPL, $INET_SRC, $INET_IMG, $FACE;

        $LastDay = getdate(mktime(0,0,0, $this->SDate[mon] + 1, 0, $this->SDate[year]));
        $LastDayMonth  = date_pad($LastDay['year'], $LastDay['mon'], $LastDay[mday]);
        $FirstDayMonth = date_pad($LastDay['year'], $LastDay['mon'], 1);

        $TargetDays = array();
        $Data       = array();

        $r_date = DBExec("SELECT * FROM datebook inf WHERE sysnumusr = $this->UID AND begindate <= '$LastDayMonth 23:59:59'::timestamp AND enddate >= '$FirstDayMonth 00:00:00'::timestamp ORDER BY begindate, enddate, sysnum", __LINE__);

        while ( !$r_date->Eof() ) {
            $BeginDate = preg_replace("/^([0-9]+)-([0-9]+)-([0-9]+).*$/", "\\1-\\2-\\3", $r_date->begindate());
            $EndDate   = preg_replace("/^([0-9]+)-([0-9]+)-([0-9]+).*$/", "\\1-\\2-\\3", $r_date->enddate());

            $daynum = 1; $d = date_pad($this->SDate['year'], $this->SDate['mon'], $daynum);
            while ($d <= $LastDayMonth && $d <= $EndDate) {
                if ($d >= $BeginDate) {
                   $TargetDays[$d] = 1;

                    if ($d == $SDate) {
                        $row =& $Data[];

                        for($i=0; $i < $r_date->numfields(); $i++) {
                            $row[$r_date->fieldname($i)] = urldecode($r_date->Field($i));
                        }
                    }
                }


                $daynum ++; $d = date_pad($this->SDate['year'], $this->SDate['mon'], $daynum);
            }

            $r_date->Next();
        }

        $this->SubTable("width='100%' border = '0' cellspacing = '0' cellpadding ='0'"); {
            $this->TRNext(); {
                $this->TDNext("width='90%' valign='top'"); {
                    $this->SubTable("width='100%' cellpadding='0' cellspacing='0' border='0' grborder class='tab'"); {
                        $this->TRNext("class='tab'"); {
                            $this->TDNext("width='1%' class='ttp' nowrap"); {
                                $this->Out("<input type='checkbox' name='Select_All' title='$TEMPL[select_all_ico]' onclick='javascript:onSelect_AllClick()'>");
                            }
                            $this->TDNext("width='10%' class='ttp' nowrap"); {
                                $this->Out("&nbsp;", $TEMPL[begindate], "&nbsp;");
                            }
                            $this->TDNext("width='10%' class='ttp' nowrap"); {
                                $this->Out("&nbsp;", $TEMPL[enddate], "&nbsp;");
                            }
                            $this->TDNext("width='79%' class='ttp' nowrap"); {
                                $this->Out("&nbsp;", $TEMPL[subject], "&nbsp;");
                            }
                        }

                        reset($Data);
                        while(list($n) = each($Data)) {
                            $row =& $Data[$n];

                            $this->TRNext("class='tab'"); {
                                $this->TDNext("class='tlp' nowrap"); {
                                    $CHECKED = "";
                                    if (is_array($s_Datebook[Status][Select])) {
                                        $CHECKED = in_array($row[sysnum], $s_Datebook[Status][Select]) ? "CHECKED" : "";
                                    }
                                    $this->Out("<input type='checkbox' name='Select[{$n}]' value='{$row[sysnum]}' $CHECKED onclick='javascript:onSelect_Click()'>");
                                }
                                $this->TDNext("class='tlp' nowrap valign='top'"); {
                                    $this->Out("&nbsp;", mkdatetime($row[begindate]), "&nbsp;");
                                }
                                $this->TDNext("class='tlp' nowrap valign='top'"); {
                                    $this->Out("&nbsp;", mkdatetime($row[enddate]), "&nbsp;");
                                }
                                $this->TDNext("class='tlp' valign='top'"); {
                                    $link = "$INET_SRC/datebook.php?UID={$this->UID}&FACE={$FACE}&SDate=" . urlencode($this->SDate[orig]) . "&Item=" . urlencode($row[sysnum]);
                                    $inter = ShInterval(MkSpecTime($row[enddate]) - MkSpecTime($row[begindate]));
                                    $this->SubTable(" width='100%' cellpadding='0' cellspacing='0' border='0'"); {
                                        $this->TDNext("class='tlp' width='1%'"); {
                                            $this->Out("&nbsp;");
                                        }
                                        $this->TDNext("class='tlp' width='98%' valign='top'"); {
                                            $this->Out("<a href='{$link}'><span class='tlpa' title=\"" . htmlspecialchars(trim($inter)) . "\">" . ($row[subject] != "" ? $row[subject] : "[none]") . "</span></a>");
                                        }
                                        $this->TDNext("class='tlp' width='1%'"); {
                                            $this->Out("&nbsp;");
                                        }
                                    } $this->SubTableDone();
                                }
                            }

                        }
                    } $this->SubTableDone();
                    $this->out("<script language='javascript'>");
                    $this->out("onSelect_Click()");
                    $this->out("</script>");

                    if (count($Data) == 0) {
                        $this->SubTable("width='100%' CELLSPACING=0 CELLPADDING=0"); {
                            $this->TRNext("class='tlp'"); {
                                $this->TDNext("class='tlp' colspan=80 align='center'"); {
                                    $this->Out("<font class='tlp'><center>");
                                    $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0"); {
                                        $this->TDNext("width='250' height='70'", "<center><font size='+2'>$TEMPL[empty_list]</font></center>");
                                    } $this->SubTableDone();
                                    $this->Out("</center></font>");
                                }
                            }
                        } $this->SubTableDone();
                    }
                }
                $this->TDNext("width='2' nowrap"); {
                    $s .= "<img src='$INET_IMG/filler2x1.gif'>\n";
                }
                $this->TDNext("width='10%' valign='top' nowrap"); {
                    $Plan = new CDatebookCalendar($this->SDate, $TargetDays);
                    $Plan->setMonthNames(split(' *, *', $TEMPL[month_names]));
                    $this->Out($Plan->getMonthHTML($this->SDate[mon], $this->SDate[year]));
                }
            } // TRNext
        } $this->SubTableDone();
    }


    function ScrEdit()
    {
        global $Item;
        global $SDate;
        global $s_Datebook, $TEMPL, $INET_IMG;

        $Params =& $s_Datebook[Status][Params];

        if (!$s_Datebook[refresh]) {
            $Params = array();

            if ($Item == "New") {
                $Temp = getdate();
                $Params[BeginMin]    =  $Temp[minutes];     $Params[BeginHour]   =  $Temp[hours];
                $Params[BeginDay]    =  $this->SDate[mday]; $Params[BeginMonth]  =  $this->SDate[mon] - 1;
                $Params[BeginYear]   =  $this->SDate[year];

                $Temp = getdate(mktime($Params[BeginHour] + 1, $Params[BeginMin], 0, $Params[BeginMonth] + 1, $Params[BeginDay], $Params[BeginYear]));
                $Params[EndMin]    =  $Temp[minutes]; $Params[EndHour]   =  $Temp[hours];
                $Params[EndDay]    =  $Temp[mday];    $Params[EndMonth]  =  $Temp[mon] - 1;
                $Params[EndYear]   =  $Temp[year];
            } else {
                $r_datebook = DBExec("SELECT * FROM datebook WHERE sysnum = '{$Item}' and sysnumusr = {$this->UID}", __LINE__);
                if ($r_datebook->NumRows() != 1) {
                    $Item = "";
                    $this->refreshScreen();
                }

                $Params[subject] = urldecode($r_datebook->subject());
                $Params[memo]    = urldecode($r_datebook->memo());

                ereg("([0-9]+)-([0-9]+)-([0-9]+) ([0-9]+):([0-9]+)", $r_datebook->begindate(), $MATH);
                $Params[BeginMin]    =  $MATH[5];    $Params[BeginHour]   =  $MATH[4];
                $Params[BeginDay]    =  $MATH[3];    $Params[BeginMonth]  =  $MATH[2] - 1;
                $Params[BeginYear]   =  $MATH[1];

                ereg("([0-9]+)-([0-9]+)-([0-9]+) ([0-9]+):([0-9]+)", $r_datebook->enddate(), $MATH);
                $Params[EndMin]    =  $MATH[5];    $Params[EndHour]   =  $MATH[4];
                $Params[EndDay]    =  $MATH[3];    $Params[EndMonth]  =  $MATH[2] - 1;
                $Params[EndYear]   =  $MATH[1];
            }
        }

        $Params[BeginMin]  = str_pad($Params[BeginMin], 2, 0, STR_PAD_LEFT);  $Params[BeginHour]  =  str_pad($Params[BeginHour], 2, 0, STR_PAD_LEFT);
        $Params[BeginDay]  = str_pad($Params[BeginDay], 2, 0, STR_PAD_LEFT);  $Params[BeginMonth] =  str_pad($Params[BeginMonth], 2, 0, STR_PAD_LEFT);
        $Params[BeginYear] = str_pad($Params[BeginYear], 4, 0, STR_PAD_LEFT);

        $Params[EndMin]  = str_pad($Params[EndMin], 2, 0, STR_PAD_LEFT);  $Params[EndHour]  =  str_pad($Params[EndHour], 2, 0, STR_PAD_LEFT);
        $Params[EndDay]  = str_pad($Params[EndDay], 2, 0, STR_PAD_LEFT);  $Params[EndMonth] =  str_pad($Params[EndMonth], 2, 0, STR_PAD_LEFT);
        $Params[EndYear] = str_pad($Params[EndYear], 4, 0, STR_PAD_LEFT);

        $this->SubTable("border='0' cellpadding='0' cellspacing='0' class='tab' width='100%'"); {
            $this->TRNext(); {
                $this->TDNext("class='tlp' nowrap width='10%'"); {
                    $this->Out("&nbsp;<b>{$TEMPL[lb_subject]}</b>", $this->SectionBlank);
                }
                $this->TDNext("class='tlp' nowrap"); {
                    $this->Out("<input type='text' name='Params[subject]' size='80' maxlength='120' class='toolsbare' value=\"" . htmlspecialchars($Params[subject]) . "\">", $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext("colspan='80'"); {
                    $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                }
            }
            $this->TRNext(); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->Out("&nbsp;<b>{$TEMPL[lb_begin_date]}</b>", $this->SectionBlank);
                }
                $this->TDNext("rowspan='2' class='tlp'"); {
                    $this->SubTable("border='0' cellpadding='0' cellspacing='0'"); {
                        $this->TRNext(); {
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_begin_year]} :", $this->TextShift, "<input type='text' name='Params[BeginYear]' size='4' maxlength='4' class='toolsbare' value=\"" . htmlspecialchars($Params[BeginYear]) . "\">", $this->TextShift);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_begin_month]} :", $this->TextShift, "<select class='toolsbare' name='Params[BeginMonth]'>");
                                foreach(split(' *, *', $TEMPL[month_names]) as $mouth_numer => $mouth_name) {
                                    $this->Out("<option value='$mouth_numer'" . ($Params[BeginMonth] == $mouth_numer ? " SELECTED" : "") . ">$mouth_name</option>");
                                }
                                $this->Out("</select>", $this->TextShift);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_begin_day]} :", $this->TextShift, "<input type='text' name='Params[BeginDay]' size='2' maxlength='2' value=\"" . htmlspecialchars($Params[BeginDay]) . "\" class='toolsbare'>", $this->SectionBlank);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_begin_hour]} :", $this->TextShift, "<input type='text' name='Params[BeginHour]' size='2' maxlength='2' value=\"" . htmlspecialchars($Params[BeginHour]) . "\" class='toolsbare'>", $this->TextShift);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_begin_min]} :", $this->TextShift, "<input type='text' name='Params[BeginMin]' size='2' maxlength='2' value=\"" . htmlspecialchars($Params[BeginMin]) . "\" class='toolsbare'>", $this->TextShift);
                            }
                        }
                        $this->TRNext(); {
                            $this->TDNext("colspan='80'"); {
                                $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                            }
                        }
                        $this->TRNext(); {
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_end_year]} :", $this->TextShift, "<input type='text' name='Params[EndYear]' size='4' maxlength='4' value=\"" . htmlspecialchars($Params[EndYear]) . "\" class='toolsbare'>", $this->TextShift);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_end_month]} :", $this->TextShift, "<select class='toolsbare' name='Params[EndMonth]'>");
                                foreach(split(' *, *', $TEMPL[month_names]) as $mouth_numer => $mouth_name) {
                                    $this->Out("<option value='$mouth_numer'" . ($Params[EndMonth] == $mouth_numer ? " SELECTED" : "") . ">$mouth_name</option>");
                                }
                                $this->Out("</select>", $this->TextShift);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_end_day]} :", $this->TextShift, "<input type='text' name='Params[EndDay]' size='2' maxlength='2' value=\"" . htmlspecialchars($Params[EndDay]) . "\" class='toolsbare'>", $this->SectionBlank);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_end_hour]} :", $this->TextShift, "<input type='text' name='Params[EndHour]' size='2' maxlength='2' value=\"" . htmlspecialchars($Params[EndHour]) . "\" class='toolsbare'>", $this->TextShift);
                            }
                            $this->TDNext("class='tlp' nowrap"); {
                                $this->Out("&nbsp;{$TEMPL[lb_end_min]} :", $this->TextShift, "<input type='text' name='Params[EndMin]' size='2' maxlength='2' value=\"" . htmlspecialchars($Params[EndMin]) . "\" class='toolsbare'>", $this->TextShift);
                            }
                        }
                    }  $this->SubTableDone();
                }
            }
            $this->TRNext(); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->Out("&nbsp;<b>{$TEMPL[lb_end_date]}</b>", $this->SectionBlank);
                }
            }
            $this->TRNext(); {
                $this->TDNext("colspan='80'"); {
                    $this->Out("<img src='$INET_IMG/filler1x1.gif'>");
                }
            }
            $this->TRNext(); {
                $this->TDNext("class='tlp' nowrap valign='top'"); {
                    $this->Out("&nbsp;<b>{$TEMPL[lb_memo]}</b>", $this->SectionBlank);
                }
                $this->TDNext("class='tlp' nowrap valign='top'"); {
                    $this->Out("<textarea name='Params[memo]' rows='10' cols='80' class='toolsbare'>" . htmlspecialchars($Params[memo]) . "</textarea>", $this->TextShift);
                }
            }
        } $this->SubTableDone();
    }


    function script()
    {
        global $INET_SRC, $FACE;
        parent::script();
        echo "<script language='javascript' src='$INET_SRC/datebook.js'></script>\n";
    }


    function refreshScreen() // overlaped virtuals function
    {
        global $SCRIPT_NAME, $FACE, $Item;
        global $s_Datebook;

        $URL = "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE";

        $URL .= "&SDate=" . urlencode(date_pad($this->SDate[year], $this->SDate[mon], $this->SDate[mday]));

        if ($Item != "") {
            $URL .= "&Item={$Item}";
        }

        parent::refreshScreen($URL);
    }


    function mes() // overlaping inherited function
    {
        global $Mes, $MesParam, $s_Datebook, $TEMPL;

        if ($Mes == "") {
            $Mes = $s_Datebook[Mes];
            unset($s_Datebook[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_Datebook[MesParam];
            unset($s_Datebook[MesParam]);
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
}


function infservisselect($list)
{
    if (!is_array($list)) {
        $list = array($list);
    }

    if (count($list) == 0) {
        return "";
    }

    reset($list);
    list($n, $main) = each($list);
    $selectlist = "inf0.tread, inf0.value as $main";
    $joinlist = "";

    while(list($n, $field) = each($list)) {
        $selectlist .= ", inf{$n}.value as {$field}";
        $joinlist .= " LEFT JOIN infservis inf{$n} ON inf0.tread = inf{$n}.tread AND inf{$n}.name='{$field}'";
    }

    return "SELECT {$selectlist} FROM infservis inf0 $joinlist WHERE inf0.name = '{$main}'";
}

ConnectToDB();

$Datebook = new CDatebook();
$Datebook->run();

UnconnectFromDB();
exit;

?>
