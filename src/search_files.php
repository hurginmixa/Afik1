<?php

/*

class CSearchFilesScreen extends screen
    function CSearchFilesScreen() // constructor
    function OpenSession()
    function Display()
        function ToolsBar()
        function Scr()
            function PutSortsIcons($ord)
            function GetSortKey()
    function rSearch()
    function rSortSet()
    function rNewView()
    function rNextPrevScreen($Num)
    function refreshScreen()
    function Mes()
*/

include("_config.inc.php");
require("cont.inc.php");
require("file.inc.php");
require("utils.inc.php");
require("db.inc.php");
require("screen.inc.php");
class CSearchFilesScreen extends screen
{
    function CSearchFilesScreen() // constructor
    {
        global $TEMPL, $_SERVER;

        parent::screen(); // inherited constructor
        $this->SetTempl("searchfiles");

        $this->PgTitle = "<b>$TEMPL[title]</b> ";

        $this->Trans("sSortSet", "sSortSetDirection");

        $this->LinePerScreen = 50;


        $this->Request_actions["sPrevScreen"]   = "rNextPrevScreen(-1)";
        $this->Request_actions["sNextScreen"]   = "rNextPrevScreen(1)";

        $this->Request_actions["sNewView"]      = "rNewView()";
        $this->Request_actions["sSearch"]       = "rSearch()";
        $this->Request_actions["sSortSet"]      = "rSortSet()";

        if ($_SERVER[REQUEST_METHOD] == "POST") {
            $this->SaveScreenStatus("s_SearchFiles", array("SearchMask", "CaseSensitive") );
        }
    }


    function OpenSession() // overlaping virtual function
    {
        global $s_SearchFiles;

        parent::OpenSession();
        session_register("s_SearchFiles");
    }


    function Display() // overlaping virtual function
    {
        global $s_SearchFiles;

        //------------------------------------------------
        // Select data

        $SearchString = $s_SearchFiles[SearchString];
        if ($SearchString != "") {
            $this->RES_FS = DBExec("SELECT fs.name, fs.sysnum, fs.up, fs.sysnumfile, fs.creat, fs.ftype, file.fsize, gettree(fs.sysnum) AS path FROM fs LEFT JOIN file ON fs.sysnumfile = file.sysnum WHERE fs.owner = $this->UID AND $SearchString ORDER BY " . $this->GetSortKey());
            $s_SearchFiles[MaxPage] = (int)(($this->RES_FS->NumRows() - 1) / $this->LinePerScreen);

            if (!preg_match("/^[0-9]+$/", $s_SearchFiles[Page])) {
                $s_SearchFiles[Page] = 0;
            }
            if ($s_SearchFiles[Page] > $s_SearchFiles[MaxPage]) {
                $s_SearchFiles[Page] = $s_SearchFiles[MaxPage];
            }
        }


        $this->out("<form method='post' name='searchfiles'>");

        parent::Display(); // inherited method

        $this->out("</form>");
    }


    function rSearch()
    {
        global $s_SearchFiles;

        $s_SearchFiles[SearchString] = "";

        if ($s_SearchFiles[Status][SearchMask] == "") {
            $s_SearchFiles[Mes] = 1;
            $this->refreshScreen();
        }

        if (!preg_match("/^[^%;,\"]+$/i", $s_SearchFiles[Status][SearchMask])) {
            $s_SearchFiles[Mes] = 2;
            $this->refreshScreen();
        }

        $SearchList = split(" " , $s_SearchFiles[Status][SearchMask]);

        if (!is_array($SearchList) || count($SearchList) == 0) {
            $s_SearchFiles[Mes] = 1;
            $this->refreshScreen();
        }

        $SQL = "";


        if ($s_SearchFiles[Status][CaseSensitive]) {
            $LIKE = "LIKE";
        } else {
            $LIKE = "ILIKE";
        }

        reset($SearchList);
        while (list($n, $v) = each($SearchList)) {
            $v = preg_replace("/'/", "''", $v);
            $SQL .= ($SQL != "" ? " AND " : "") . "fs.name $LIKE '%$v%'";
        }

        $s_SearchFiles[SearchString] = $SQL;
        $s_SearchFiles[Sort] = "";
        $s_SearchFiles[Page] = 0;

        $this->refreshScreen();
    }


    function rSortSet()
    {
        global $s_SearchFiles, $sSortSet, $sSortSetDirection;

        $s_SearchFiles[Sort] = $sSortSetDirection;

        $this->refreshScreen();
    }


    function rNextPrevScreen($Num)
    {
        global $s_SearchFiles;
        $s_SearchFiles[Page] = (int)$s_SearchFiles[Page];

        if ($Num < 0)  {
            if ($s_SearchFiles[Page] > 0) {
                $s_SearchFiles[Page] --;
            } else {
                $s_SearchFiles[Page] = 0;
            }
        }

        if ($Num > 0)  {
            $s_SearchFiles[Page] ++;
        }

        $this->refreshScreen();
    }


    function rNewView()
    {
        global $s_SearchFiles;

        $s_SearchFiles = array();
        $this->refreshScreen();
    }


    function ToolsBar()  // overlaping virtual function
    {
        global $TEMPL, $s_SearchFiles, $FACE;
        global $INET_IMG;

        $this->SubTable("border = '0' width = '100%' cellpading='0' cellspacing='0'"); {
            $this->TRNext(); {
                $this->TDNext("class='toolsbarl'"); {
                    $this->SubTable("border = '0' cellpading='0' cellspacing='5'"); {
                        $this->TRNext(); {
                            $this->TDNext("class='toolsbarl'"); {
                                $this->OUT($TEMPL[search_query], $this->TextShift);
                                $this->OUT("<input name='SearchMask' type='text' value=\"" . htmlspecialchars($s_SearchFiles[Status][SearchMask]) . "\">", $this->TextShift);
                                $this->OUT($TEMPL[case_sensitive], $this->TextShift);
                                $this->OUT("<input name='CaseSensitive' type='checkbox' value='1'" . ($s_SearchFiles[Status][CaseSensitive] != "" ? " CHECKED" : "") . ">", $this->TextShift);
                                $this->OUT(makeButton("type=1& form=searchfiles& name=sSearch& img=$INET_IMG/searchfiles-passive.gif?FACE=$FACE& imgact=$INET_IMG/searchfiles.gif?FACE=$FACE& title=$TEMPL[bt_search_ico]"), $this->SectionBlank);
                                if (isset($this->RES_FS)) {
                                    if ($s_SearchFiles[MaxPage] > 0) {
                                        if ($s_SearchFiles[Page] > 0) {
                                            $this->out(makeButton("type=1& form=searchfiles& name=sPrevScreen& img=$INET_IMG/arrowleft-passive.gif?FACE=$FACE& imgact=$INET_IMG/arrowleft.gif?FACE=$FACE& title=$TEMPL[bt_prev_ico]") . $this->ButtonBlank);
                                        } else {
                                            $this->out("<img src='$INET_IMG/arrowleft-unactive.gif?FACE=$FACE' align='absmiddle'>" . $this->ButtonBlank);
                                        }

                                        if ($s_SearchFiles[Page] != $s_SearchFiles[MaxPage]) {
                                            $this->out(makeButton("type=1& form=searchfiles& name=sNextScreen& img=$INET_IMG/arrowright-passive.gif?FACE=$FACE& imgact=$INET_IMG/arrowright.gif?FACE=$FACE& title=$TEMPL[bt_next_ico]") . $this->ButtonBlank);
                                        } else {
                                            $this->out("<img src='$INET_IMG/arrowright-unactive.gif?FACE=$FACE' align='absmiddle'>" . $this->ButtonBlank);
                                        }

                                        $this->out("Page <b>" . ($s_SearchFiles[Page] + 1) . "</b> From <b>" . ($s_SearchFiles[MaxPage] + 1) . "</b>", $this->TextShift);
                                    }
                                }
                            }
                        }
                    }  $this->SubTableDone();
                }
            }
        } $this->SubTableDone();
    } // function ToolsBar


    function Scr()  // overlaping virtual function
    {
        global $s_SearchFiles, $INET_SRC, $INET_IMG, $FACE;
        global $TEMPL;

        if (!isset($this->RES_FS)) {
            return;
        }

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("border = '0' cellpadding = '0' cellspacing = '0' class = 'tab' width = '100%' grborder"); {

            $this->TRNext(); {
                $this->TDNext("class='ttp' width='35%'"); {
                    $this->out("&nbsp;" , $TEMPL[name], "&nbsp;", $this->PutSortsIcons("n"), "&nbsp;");
                }
                $this->TDNext("class='ttp' width='15%'"); {
                    $this->out("&nbsp;" , $TEMPL[size], "&nbsp;", $this->PutSortsIcons("s"), "&nbsp;");
                }
                $this->TDNext("class='ttp' width='15%'"); {
                    $this->out("&nbsp;" , $TEMPL[date], "&nbsp;", $this->PutSortsIcons("d"), "&nbsp;");
                }
                $this->TDNext("class='ttp' width='35%'"); {
                    $this->out("&nbsp;" , $TEMPL[up],   "&nbsp;");
                }
            }

            $res_fs = $this->RES_FS;
            $res_fs->Set($this->LinePerScreen * $s_SearchFiles[Page]);

            for($i = 1; !$res_fs->Eof() && $i <= $this->LinePerScreen; $i ++) {
                $this->TRNext(); {
                    $this->TDNext("class='tlp'"); {
                        $this->SubTable("border = '0' cellpadding = '0' cellspacing = '0' class = 'tab'"); {
                            $this->TDNext("class='tlp' nowrap"); {
                                if ($res_fs->sysnumfile() == 0) {
                                    $URL = "$INET_SRC/file_folder.php?UID=$this->UID&FACE=$FACE";
                                    if ($res_fs->sysnum() != 0) {
                                        $URL .= "&FS=" . $res_fs->sysnum();
                                    }
                                    $this->Out("&nbsp;");
                                    $this->Out("<a href='$URL'>");
                                    $this->Out("<img src='$INET_IMG/folder-yellow.gif' border=0 alt='$TEMPL[open_folder_ico]'>");
                                    $this->Out("</a>");
                                    $this->Out("&nbsp;");
                                } else {
                                    $URL = MakeOwnerFileDownloadURL($res_fs->name(), $res_fs->sysnum(), $this->UID, 2);
                                    $this->Out("&nbsp;");
                                    $this->Out("<a href='$URL' target = '_blank'>");
                                    $this->out("<img src='$INET_IMG/view.gif' border=0 alt='$TEMPL[view_file_ico]'>");
                                    $this->Out("</a>");
                                    $this->Out("&nbsp;");
                                    $URL = MakeOwnerFileDownloadURL($res_fs->name(), $res_fs->sysnum(), $this->UID, 1);
                                }
                            }
                            //$this->TDNext("class='tlp' nowrap"); {
                            //    $this->out("&nbsp;");
                            //}
                            $this->TDNext("class='tlp'"); {
                                $this->Out("<a href='$URL'>");
                                $this->Out("<span class='tlpa'>");
                                $this->out("&nbsp;" , $res_fs->name(), "&nbsp;");
                                $this->Out("</span>");
                                $this->Out("</a>");
                            }
                        } $this->SubTableDone();
                    }
                    $this->TDNext("class='tlp' align='right'"); {
                        if ($res_fs->sysnumfile() != 0) {
                            $size = $res_fs->fsize();
                            $this->out("&nbsp;<span title='$size'>" , AsSize($size), "</span>&nbsp;");
                        } else {
                            $this->out("&nbsp;");
                        }
                    }
                    $this->TDNext("class='tlp'"); {
                        $this->out("&nbsp;" , mkdatetime($res_fs->creat()), "&nbsp;");
                    }
                    $this->TDNext("class='tlp'"); {
                        if ($res_fs->ftype() == "f") {
                            $PATH = preg_replace("/[^\/]+$/", "", $res_fs->path());
                            if ($PATH != "/") {
                                $PATH = rtrim($PATH, "/");
                            }

                            $URL = "$INET_SRC/file_folder.php?UID=$this->UID&FACE=$FACE";
                            if ($res_fs->up() != 0) {
                                $URL .= "&FS=" . $res_fs->up();
                            }
                            $this->Out("<a href='$URL'>");
                            $this->Out("<span class='tlpa'>");
                            $this->out("&nbsp;" , $PATH, "&nbsp;");
                            $this->Out("</span>");
                            $this->Out("</a>");
                        } else {
                            $URL = "$INET_SRC/mail_folder.php?UID=$this->UID&FACE=$FACE&Msg=" . $res_fs->up();
                            $this->Out("<a href='$URL' target = '_blank'>");
                            $this->Out("<span class='tlpa'>");
                            $this->out("&nbsp;" , $TEMPL[attachFile], "&nbsp;");
                            $this->Out("</span>");
                            $this->Out("</a>");
                        }
                    }
                }

                $res_fs->Next();
            }


            if ($res_fs->NumRows() == 0) {
                $this->TRNext(); {
                    $this->TDNext("class='tlp' colspan=300 align='center'"); {
                        $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0"); {
                            $this->TRNext(); {
                                $this->TDNext("width='250' height='70' align='center'"); {
                                    $this->Out("&nbsp;<font size='+2'>$TEMPL[empty]</font>&nbsp;");
                                }
                            }
                        } $this->SubTableDone();
                    }
                }
            }

        } $this->SubTableDone();

        //$this->Out("<hr>", sharr($s_SearchFiles));
    }


    function refreshScreen()  // overlaping virtual function
    {
        global $SCRIPT_NAME, $FACE;

        $URL = "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE";

        parent::refreshScreen($URL);
    }


    function PutSortsIcons($ord) // @METAGS PutSortsIcons
    {
        global $INET_IMG;
        global $s_SearchFiles;
        global $REQUEST_URI, $SCRIPT_NAME;
        global $HTTP_GET_VARS, $FACE;

        $rez = "";

        if ($ord != $s_SearchFiles[Sort]) {
            $rez .= "<input type='image' name = 'sSortSet_$ord' src='$INET_IMG/sort1.gif' alt='' border='0'>";
        }

        if (strtoupper ($ord) != $s_SearchFiles[Sort]) {
            $rez .= "<input type='image' name = 'sSortSet_" . strtoupper ($ord) . "' src='$INET_IMG/sort2.gif' alt='' border='0'>";
        }

        return $rez;
    }


    function GetSortKey()
    {
        global $s_SearchFiles;

        $res = "";

        switch ($s_SearchFiles[Sort]) {
            case 'n' :
                $res = "fs.name";
                break;
            case 'N' :
                $res = "char_length(fs.name) DESC, fs.name DESC";
                break;
            case 's' :
                $res = "file.fsize";
                break;
            case 'S' :
                $res = "file.fsize DESC";
                break;
            case 'd' :
                $res = "fs.creat";
                break;
            case 'D' :
                $res = "fs.creat DESC";
                break;
            default :
                $res = "char_length(fs.name), fs.name";
        }

        return $res;
    }


    function Mes()   // overlaping virtual function
    {
        global $Mes, $MesParam, $s_SearchFiles, $TEMPL;


        if ($Mes == "") {
            $Mes = $s_SearchFiles[Mes];
            unset($s_SearchFiles[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_SearchFiles[MesParam];
            unset($s_SearchFiles[MesParam]);
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


} // class CSearchFilesScreen


ConnectToDB();

$SearchFilesScreen = new CSearchFilesScreen();
$SearchFilesScreen->Run();

UnconnectFromDB();

exit;
?>
