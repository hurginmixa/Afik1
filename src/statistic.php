<?php
/*
class CStatisticReport extends CPostgreSQLReport
    function GetValue($FieldName) // overlaping virtual function
    function GetAmountField($lev, $LevelScaningStepNumber, $FieldName) // overlaping virtual function

class CStatisticScreen extends screen
    function CStatisticScreen() // constructor
    function OpenSession() // overlaped virtuals function
    function mes() // overlaped virtual function
    function toolsbar() // overlaped inherited function
    function Scr() // overlaped inherited function
        function Scr_1($beginDate, $endDate)
        function Scr_2($domain, $beginDate, $endDate)
    function makeDate($Month, $Year, $Part)
    function SetPeriod()
    function SaveScreenStatus() // save status of fields after submit of form and before refresh of screen
    function CheckPeriod()
*/



require "view.inc.php";
require "screen.inc.php";
require "tools.inc.php";
require "report.inc.php";

require "db.inc.php";

class CStatisticReport extends CPostgreSQLReport
{

    function GetValue($FieldName) // overlaping virtual function
    {
        if (eregi("^download$", $FieldName)) {
            if (parent::GetValue("direct") == -1) {
                return parent::GetValue("traficsize");
            } else {
                return 0;
            }
        }

        if (eregi("^upload$", $FieldName)) {
            if (parent::GetValue("direct") == 1) {
                return parent::GetValue("traficsize");
            } else {
                return 0;
            }
        }

        if (eregi("^copy$", $FieldName)) {
            if (parent::GetValue("direct") == 0) {
                return parent::GetValue("traficsize");
            } else {
                return 0;
            }
        }

        $result = parent::GetValue($FieldName);
        return $result;
    }

    function GetAmountField($lev, $LevelScaningStepNumber, $FieldName) // overlaping virtual function
    {
        $result = parent::GetAmountField($lev, $LevelScaningStepNumber, $FieldName);

        if (eregi("^(download|upload|copy)$", $FieldName)) {
            if ($result != 0) {
                $result = "<span title='$result'>" . AsSize($result) . "</span>";
            } else {
                $result = "&nbsp";
            }
        }

        return $result;
    }
}


class CStatisticScreen extends screen
{
    function CStatisticScreen() // constructor
    {
        global $TEMPL, $s_Statistic;

        $this->screen();                      // inherited constructor
        $this->SetTempl("statistic");

        $this->PgTitle = "<b>$TEMPL[title]</b> ";

        $this->Request_actions["sSetPeriod"]            = "SetPeriod()";

        $this->SaveScreenStatus();
    }


    function OpenSession() // overlaped virtuals function
    {
        parent::OpenSession();
        session_register("s_Statistic");
    }


    function mes() // overlaped virtual function
    {
        global $Mes, $MesParam, $s_Statistic, $TEMPL;


        if ($Mes == "") {
            $Mes = $s_Statistic[Mes];
            unset($s_Statistic[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_Statistic[MesParam];
            unset($s_Statistic[MesParam]);
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


    function toolsbar() // overlaped inherited function
    {
        global $TEMPL, $s_Statistic;

        $this->CheckPeriod();

        $CurrDate = getdate();

        $this->out("<form method='POST' name='StaricticForm'>");

        $this->SubTable("class='toolsbarl' width='100%' cellpadding='5' cellspacing='0'"); {
            $this->TRNext(); {
                $this->TDNext(); {
                    $this->out($this->ButtonBlank);
                    $this->out("$TEMPL[begindate]");

                    $this->out($this->ButtonBlank);
                    $this->out("<select name='BeginMonth' class='toolsbare'>");
                    for ( $i = 1; $i <= 12; $i ++ ) {
                        $this->out("<option value=$i" . ($s_Statistic[Status][BeginMonth] == $i ? " selected" : "") . ">" . $TEMPL["month" . $i] . "</option>");
                    }
                    $this->out("</select>");

                    $this->out($this->ButtonBlank);
                    $this->out("<select name='BeginYear' class='toolsbare'>");
                    for ( $i = 2003; $i <= $CurrDate[year]; $i ++ ) {
                        $this->out("<option value=$i" . ($s_Statistic[Status][BeginYear] == $i ? " selected" : "") . ">$i</option>");
                    }
                    $this->out("</select>");

                    $this->out($this->SectionBlank);
                    $this->out("$TEMPL[enddate]");

                    $this->out($this->ButtonBlank);
                    $this->out("<select name='EndMonth' class='toolsbare'>");
                    for ( $i = 1; $i <= 12; $i ++ ) {
                        $this->out("<option value=$i" . ($s_Statistic[Status][EndMonth] == $i ? " selected" : "") . ">" . $TEMPL["month" . $i] . "</option>");
                    }
                    $this->out("</select>");

                    $this->out($this->ButtonBlank);
                    $this->out("<select name='EndYear' class='toolsbare'>");
                    for ( $i = 2003; $i <= $CurrDate[year]; $i ++ ) {
                        $this->out("<option value=$i" . ($s_Statistic[Status][EndYear] == $i ? " selected" : "") . ">$i</option>");
                    }
                    $this->out("</select>");

                    $this->out($this->SectionBlank);
                    $this->out(makeButton("type=1& name=sSetPeriod& value=$TEMPL[bt_setperiod]& width=100"));
                }
            }
        } $this->SubTableDone();

        $this->out("</form>");
    } // function toolsbar()


    function Scr() // overlaped inherited function
    {
        global $s_Statistic;

        $Status = $s_Statistic[Status];

        if ( ($Status[EndYear] + $Status[EndMonth] / 100) < ($Status[BeginYear] + $Status[BeginMonth] / 100)) {
            return;
        }

        $beginDate = $this->makeDate($Status[BeginMonth], $Status[BeginYear], 1);
        $endDate   = $this->makeDate($Status[EndMonth],   $Status[EndYear],   2);

        #$this->Scr_1($beginDate, $endDate);
        $this->Scr_2($this->USR->sysnumdomain(), $beginDate, $endDate);

        $this->SubTable("border = 1"); {
            $this->TRNext(); {
                $this->TDNext(); {
                    $this->out(sharr($s_Statistic));
                }
            }
        } $this->SubTableDone();
    }


    function Scr_1($beginDate, $endDate)
    {
        global $TEMPL, $INET_IMG, $s_Statistic;

        $Status = $s_Statistic[Status];

        $this->SubTable(); {
            $this->TRNext(); {
                $this->TDNext(); {
                    $this->out( $TEMPL[src1_period] . " : " . $TEMPL["month" . $Status[BeginMonth]] . " " .  $Status[BeginYear] . " - " . $TEMPL["month" . $Status[EndMonth]] . " " .  $Status[EndYear] );
                }
            }
        } $this->SubTableDone();

        $this->SubTable(); {
            $this->TRNext(); {
                $this->TDNext(); {

                    $this->out("<table border = '0' cellpadding = '0' cellspacing = '0'>");

                    $SQL = "SELECT
                                    domain.name   AS namedomain,
                                    domain.sysnum AS sysnumdomain,
                                    billing.direct,
                                    sum(billing.traficsize) as traficsize
                            FROM
                                    domain
                                        LEFT JOIN
                                        billing ON billing.sysnumdomain = domain.sysnum AND
                                                    billing.date >= '$beginDate' AND
                                                    billing.date <= '$endDate'
                            GROUP BY
                                    domain.name, domain.sysnum, billing.direct";

                    $r_billing = DBExec($SQL, __LINE__);

                    $DeZap = array (
                        array (
                            10, "",
                            #--------
                            "<tr>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_namedomain]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_upload]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_download]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_copy]&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler2x1.gif></td>
                            </tr>",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;<b>$TEMPL[scr1_amount]</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^upload+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^download+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^copy+^</b>&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler2x1.gif></td>
                            </tr>"
                        ),
                        array (
                            2, "sysnumdomain",
                            #--------
                            "",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;^namedomain^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^upload+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^download+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^copy+^&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler1x1.gif></td>
                            </tr>"
                        )
                    );

                    $Report = new CStatisticReport($r_billing, $DeZap);

                    $this->out($Report->Run());

                    $SQL = "SELECT
                                    to_char(billing.date,'YYYY') || ', ' ||
                                    to_char(billing.date,'MM')   AS kontr,
                                    billing.direct,
                                    sum(billing.traficsize) as traficsize
                                FROM
                                    billing
                                WHERE
                                    billing.date >= '$beginDate' AND
                                    billing.date <= '$endDate'
                                GROUP BY
                                    kontr, billing.direct
                        ";

                    $r_billing = DBExec($SQL, __LINE__);

                    $DeZap = array (
                        array (
                            10, "",
                            #--------
                            "<tr>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_namedomain]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_upload]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_download]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_copy]&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler2x1.gif></td>
                            </tr>",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;<b>$TEMPL[scr1_amount]</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^upload+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^download+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^copy+^</b>&nbsp;</td>
                            </tr>"
                        ),
                        array (
                            2, "kontr",
                            #--------
                            "",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;^kontr^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^upload+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^download+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^copy+^&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler1x1.gif></td>
                            </tr>"
                        )
                    );

                    $Report = new CStatisticReport($r_billing, $DeZap);

                    $this->out($Report->Run());

                    $this->out("</table>");
                }
            }
        } $this->SubTableDone();
    }


    function Scr_2($domain, $beginDate, $endDate)
    {
        global $TEMPL, $INET_IMG, $s_Statistic;

        $Status = $s_Statistic[Status];

        $this->SubTable(); {
            $this->TRNext(); {
                $this->TDNext(); {
                    $this->out( $TEMPL[src1_period] . " : " . $TEMPL["month" . $Status[BeginMonth]] . " " .  $Status[BeginYear] . " - " . $TEMPL["month" . $Status[EndMonth]] . " " .  $Status[EndYear] );
                }
            }
        } $this->SubTableDone();

        $this->SubTable(); {
            $this->TRNext(); {
                $this->TDNext(); {

                    $this->out("<table border = '0' cellpadding = '0' cellspacing = '0'>");

                    $SQL = "SELECT
                                    usr.name   AS nameusr,
                                    usr.sysnum AS sysnumusr,
                                    billing.direct,
                                    sum(billing.traficsize) as traficsize
                            FROM
                                    billing
                                        LEFT JOIN usr ON billing.sysnumusr = usr.sysnum
                            WHERE
                                    billing.date >= '$beginDate' AND
                                    billing.date <= '$endDate' AND
                                    billing.sysnumdomain = $domain
                            GROUP BY
                                    usr.name, usr.sysnum, billing.direct";

                    $r_billing = DBExec($SQL, __LINE__);

                    $DeZap = array (
                        array (
                            10, "",
                            #--------
                            "<tr>
                                <td class='ttp'>&nbsp;$TEMPL[scr2_nameusr]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr2_upload]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr2_download]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr2_copy]&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler2x1.gif></td>
                            </tr>",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;<b>$TEMPL[scr2_amount]</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^upload+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^download+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^copy+^</b>&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler2x1.gif></td>
                            </tr>"
                        ),
                        array (
                            2, "sysnumusr",
                            #--------
                            "",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;^nameusr^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^upload+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^download+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^copy+^&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler1x1.gif></td>
                            </tr>"
                        )
                    );

                    $Report = new CStatisticReport($r_billing, $DeZap);

                    $this->out($Report->Run());

                    $SQL = "SELECT
                                    to_char(billing.date,'YYYY') || ', ' ||
                                    to_char(billing.date,'MM')   AS kontr,
                                    billing.direct,
                                    sum(billing.traficsize) as traficsize
                                FROM
                                    billing
                                WHERE
                                    billing.date >= '$beginDate' AND
                                    billing.date <= '$endDate' AND
                                    billing.sysnumdomain = $domain
                                GROUP BY
                                    kontr, billing.direct
                        ";

                    $r_billing = DBExec($SQL, __LINE__);

                    $DeZap = array (
                        array (
                            10, "",
                            #--------
                            "<tr>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_namedomain]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_upload]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_download]&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='ttp'>&nbsp;$TEMPL[scr1_copy]&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler2x1.gif></td>
                            </tr>",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;<b>$TEMPL[scr1_amount]</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^upload+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^download+^</b>&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;<b>^copy+^</b>&nbsp;</td>
                            </tr>"
                        ),
                        array (
                            2, "kontr",
                            #--------
                            "",
                            #--------
                            "<tr>
                                <td class='tlp'>&nbsp;^kontr^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^upload+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^download+^&nbsp;</td>
                                <td><img src=$INET_IMG/filler1x1.gif></td>
                                <td class='tlp' align=right>&nbsp;^copy+^&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan = '10'><img src=$INET_IMG/filler1x1.gif></td>
                            </tr>"
                        )
                    );

                    $Report = new CStatisticReport($r_billing, $DeZap);

                    $this->out($Report->Run());

                    $this->out("</table>");
                }
            }
        } $this->SubTableDone();
    }


    function makeDate($Month, $Year, $Part)
    {
        if ($Part == 1) {
            $result = "$Year-$Month-01";
            return $result;
        }

        $DayMonth = array(1=>31, 2=>28, 3=>31, 4=>30, 5=>31, 6=>30, 7=>31, 8=>31, 9=>30, 10=>31, 11=>30, 12=>31);
        $Day = $DayMonth[$Month];
        if ($Month == 2 && $Year % 4 == 0) {
            $Day++;
        }

        $result = "$Year-$Month-$Day";
        return $result;
    }


    function SetPeriod()
    {
        global $s_Statistic;

        $Status = $s_Statistic[Status];

        if ( ($Status[EndYear] + $Status[EndMonth] / 100) < ($Status[BeginYear] + $Status[BeginMonth] / 100)) {
            $s_Statistic[Mes] = 1;
        }

        $this->refreshScreen();
    }


    function SaveScreenStatus() // save status of fields after submit of form and before refresh of screen
    {
        global $s_Statistic, $_REQUEST, $_SERVER;

        if($_SERVER[REQUEST_METHOD] != "POST") {
            return;
        }

        $SaveFieldsList = array("BeginMonth", "BeginYear", "EndMonth", "EndYear");

        reset($SaveFieldsList);
        while(list($n, $v) = each($SaveFieldsList)) {
            if (!isset($_REQUEST[$v])) {
                continue;
            }
            if (!is_array($_REQUEST[$v])) {
                $s_Statistic[Status][$v] = $_REQUEST[$v];
            } else {
                reset($_REQUEST[$v]);
                while(list($ins_n, $ins_v) = each($_REQUEST[$v])) {
                    $s_Statistic[Status][$v][$ins_n] = $ins_v;
                }
            }
        }
    }


    function CheckPeriod()
    {
        global $s_Statistic;

        $CurrDate = getdate();

        $s_Statistic[Status][BeginMonth] = (int)$s_Statistic[Status][BeginMonth];  if ($s_Statistic[Status][BeginMonth] < 1    || $s_Statistic[Status][BeginMonth] > 12)              { $s_Statistic[Status][BeginMonth] = $CurrDate[mon]; }
        $s_Statistic[Status][BeginYear]  = (int)$s_Statistic[Status][BeginYear];   if ($s_Statistic[Status][BeginYear]  < 2003 || $s_Statistic[Status][BeginYear]  > $CurrDate[year]) { $s_Statistic[Status][BeginYear]  = $CurrDate[year];  }
        $s_Statistic[Status][EndMonth]   = (int)$s_Statistic[Status][EndMonth];    if ($s_Statistic[Status][EndMonth]   < 1    || $s_Statistic[Status][EndMonth]   > 12)              { $s_Statistic[Status][EndMonth]   = $CurrDate[mon]; }
        $s_Statistic[Status][EndYear]    = (int)$s_Statistic[Status][EndYear];     if ($s_Statistic[Status][EndYear]    < 2003 || $s_Statistic[Status][EndYear]    > $CurrDate[year]) { $s_Statistic[Status][EndYear]    = $CurrDate[year];  }
    }

} // CStatistic

ConnectToDB();

$StatisticScreen = new CStatisticScreen();
$StatisticScreen->run();

UnconnectFromDB();
exit;

?>
