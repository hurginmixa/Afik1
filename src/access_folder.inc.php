<?php


/*
class myscreen extends screen
{
    function myscreen()
        function SetData()
    function mes()
    function Scr()
        function ScrFiles()
        function PutStepUp()
        function PutSortsIcons($ord)
        function ScrFilesTable()
    function rNewView()
    function rNewFolder()
    function rCopyFile($b)
    function rCopyAddFile($b)
    function rPaste()
        function rPaste_PasteFolder(&$Item, $InsertFS)
        function rPaste_PasteFile(&$Item, $InsertFS)
        function rPaste_FilesLoop($FSNum, &$InsertList, $ParentRef)
    function GetPasteName($FS, $name_org, $owner)
    function rDeleteFile()
    function rDeletePermission()
    function rRename()
    function rSelFriend()
    function rDownloadZip()
        function DlZip($level, $root, $files, &$UserTable, &$ZipSize)
    function refreshScreen()
}
*/


require("cont.inc.php");
require("file.inc.php");
require("utils.inc.php");

require("db.inc.php");

require("screen.inc.php");

class CAccessFolderScreen extends screen {

    function CAccessFolderScreen()
    {
        global $FS, $ShareID, $Fri, $SCRIPT_NAME, $TEMPL;

        $this->screen(); // Inherited conctructor
        $this->SetTempl("access_folder");

        if(!ereg("^[0-9]+$", $FS)) {
            $FS = 0;
        }

        if(!ereg("^[0-9]+$", $ShareID)) {
            if ($FS != 0) {
                $ShareID = $FS;
            } else {
                $ShareID = "";
            }
        }

        $this->Log("access_folder : CAccessFolderScreen : mess N 1 =$FS=$ShareID=");

        if($Fri != "A" && !ereg("^[0-9]+$", $Fri)) {
            $Fri = "A";
        }

        $this->SetData();
        //echo ShArr($this->Data); exit;


        // $R_FS->Set(0);
        $this->PgTitle = "<b>$TEMPL[title]</b> ";
        if ($FS != 0) {
            $this->PgTitle .= "<span title=\"" . htmlspecialchars($this->Data[name]) . "\">" . ReformatToLeft($this->Data[name], 30) . "</span>";
        } else {
            $FS = 0;
            $this->PgTitle .= $TEMPL[home_title];
        }

        $this->Trans("sCopy",   "");
        $this->Trans("sCut",   "");
        $this->Trans("sPaste",  "");
        $this->Trans("sDeleteFiles",  "");
        $this->Trans("sDeletePerms",  "");
        $this->Trans("sNewFolder",  "");
        $this->Trans("sRename",  "");

        $this->Request_actions["sNewView"]       = "rNewView()";
        $this->Request_actions["sNewFolder"]     = "rNewFolder()";
        $this->Request_actions["sDownloadZip"]   = "rDownloadZip()";
        $this->Request_actions["sCopy"]          = "rCopyFile('c')";
        $this->Request_actions["sCopyAdd"]       = "rCopyAddFile('c')";
        $this->Request_actions["sCut"]           = "rCopyFile('r')";
        $this->Request_actions["sCutAdd"]        = "rCopyAddFile('r')";
        $this->Request_actions["sPaste"]         = "rPaste()";
        $this->Request_actions["sDeleteFiles"]   = "rDeleteFile()";
        $this->Request_actions["sDeletePerms"]   = "rDeletePermission()";
        $this->Request_actions["sRename"]        = "rRename()";
        $this->Request_actions["sSelFriend"]     = "rSelFriend()";

        $this->SaveScreenStatus();
    }


    function Actions() // overlaped virtuals function
    {
        if (!$this->Data[error]) {
            parent::Actions();
        }
    }


    function OpenSession() // overload function
    {
        screen::OpenSession();
        session_register("s_AccessFolder");
    }

    function mes() // overload function
    {
        global $Mes, $TEMPL, $s_AccessFolder;

        if ($Mes == "") {
            $Mes = $s_AccessFolder[Mes];
            unset($s_AccessFolder[Mes]);
        }

        if((int)$Mes != 0) {
            $this->ErrMes($TEMPL[ErrMes . $Mes]);
        }
    }


    function SaveScreenStatus()
    {
        global $s_AccessFolder, $_REQUEST;

        $SaveFieldsList = array("NewName");

        reset($SaveFieldsList);
        while(list($n, $v) = each($SaveFieldsList)) {
            if (!isset($_REQUEST[$v])) {
                continue;
            }
            if (!is_array($_REQUEST[$v])) {
                $s_AccessFolder[Status][$v] = $_REQUEST[$v];
            } else {
                reset($_REQUEST[$v]);
                while(list($ins_n, $ins_v) = each($_REQUEST[$v])) {
                    $s_AccessFolder[Status][$v][$ins_n] = $ins_v;
                }
            }
        }
    }


    function Scr()
    {
        if ($this->Data[error]) {
            return;
        }

        $this->ScrFiles();

        //$this->SubTable("border = 1"); {
            //$this->out(sharr($GLOBALS[_SESSION]));
            //$this->out(ShGlobals(), "<hr>");
            //$this->out(ShArr($this->Data), "<hr>");
        //} $this->SubTableDone();
    }


    function ScrFiles()
    {
        global $FS, $Fri, $SCRIPT_NAME, $INET_CGI, $INET_SRC, $INET_IMG;
        global $R_FS, $s_AccessFolder;
        global $HTTP_HOST, $FTP_PORT;
        global $TEMPL, $FACE;

        $this->out("<form method='post' name='ScrFilesForm'>");

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        if ($FS == 0 && $this->Key == "") {
            $this->SubTable("border=0 width='100%' cellspacing=0 cellpadding=5"); {

            // button only on first page and not show with remote_accsses (Key != "")
                $this->TDS(0, 1,"valign='middle' nowrap width='50%' class='toolsbarl'",""); {
                    $this->out("$TEMPL[lb_sel_friends]", $this->ButtonBlank);
                    $this->out("<select name='SelFriend' size=0 class='toolsbare' title = '$TEMPL[bt_sel_friends_ico]'>");
                    $usr = array();
                    for (_reset($this->Data[Files]); $key=_key($this->Data[Files]); _next($this->Data[Files])) {
                        $file = $this->Data[Files][$key];

                        if (!$file) {
                            continue;
                        }

                        if ($file[access][p] == "n") {
                            continue;
                        }

                        //if (($Fri != "A") && ($Fri == 0)) {
                        //    $Fri = $file[owner];
                        //}

                        $nam = $file[usrname] . "@" . $file[domainname];
                        if (!in_array($file[owner], $usr)) {
                            $usr[] = $file[owner];
                            $this->out("<option value=$file[owner]" . (($file[owner] == $Fri) ? " SELECTED" : "") . ">$nam</option>");
                        }
                    }
                    $this->out("<option value='A'" . (($Fri == "A") ? " SELECTED" : "") . ">$TEMPL[bt_sel_friends]</option>");
                    $this->out("</select>" .$this->ButtonBlank);
                    $this->out("<input type='submit' name='sSelFriend' value='>>' class='toolsbarb' title = '$TEMPL[bt_sel_friends_ico]'>");
                }
            } $this->SubTableDone();

            $this->out("<img src='$INET_IMG/filler2x1.gif'>");
        }

        $this->SubTable("border=0 width='100%' cellspacing=0 cellpadding=5"); {
            $this->TDS(0,0,"valign='top' nowrap class='toolsbarl' colspan=2 valign='bottom'",""); {
                $this->SubTable("border=0 cellspacing=0 cellpadding=0"); {
                    $this->TDS(2,0,"valign='top' nowrap class='toolsbarl' colspan=2 valign='bottom'",""); {
                        if ($this->Data[FldRO]) {
                            $this->Out("<img src='$INET_IMG/accsfoldercut-unactive.gif?FACE=$FACE' border=0 align='middle' alt='$TEMPL[bt_cut_ico]'>" . $this->ButtonBlank);
                            if (($this->Data[FolderPermission] & 1) == 1) {
                                $this->Out(makeButton("type=1& name=sCopy_1& form=ScrFilesForm& img=$INET_IMG/accsfoldercopy-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfoldercopy.gif?FACE=$FACE& title=$TEMPL[bt_copy_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                            } else {
                                $this->Out("<img src='$INET_IMG/accsfoldercopy-unactive.gif?FACE=$FACE' border=0 align=middle alt='$TEMPL[bt_copy_ico]'>" . $this->ButtonBlank);
                            }
                            $this->Out("<img src='$INET_IMG/accsfolderpaste-unactive.gif?FACE=$FACE' border=0 align=middle alt='$TEMPL[bt_paste_ico]'>" . $this->ButtonBlank);
                        } else {
                            if (($this->Data[FolderPermission] & 1) == 1) {
                                $this->Out(makeButton("type=1& name=sCut_1& form=ScrFilesForm& img=$INET_IMG/accsfoldercut-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfoldercut.gif?FACE=$FACE& title=$TEMPL[bt_cut_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                                $this->Out(makeButton("type=1& name=sCopy_1& form=ScrFilesForm& img=$INET_IMG/accsfoldercopy-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfoldercopy.gif?FACE=$FACE& title=$TEMPL[bt_copy_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                            } else {
                                $this->Out("<img src='$INET_IMG/accsfoldercut-unactive.gif?FACE=$FACE' border=0 align='middle' alt='$TEMPL[bt_cut_ico]'>" . $this->ButtonBlank);
                                $this->Out("<img src='$INET_IMG/accsfoldercopy-unactive.gif?FACE=$FACE' border=0 align=middle alt='$TEMPL[bt_copy_ico]'>" . $this->ButtonBlank);
                            }
                            $this->Out(makeButton("type=1& name=sPaste_1& form=ScrFilesForm& img=$INET_IMG/accsfolderpaste-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfolderpaste.gif?FACE=$FACE& title=$TEMPL[bt_paste_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                        }
                        if (($this->Data[FolderPermission] & 3) == 3 || $FS == "" || $FS == 0) {
                          if ($FS == 0 || $FS == "") {
                            $this->Out(makeButton("type=1& name=sDeletePerms_1& form=ScrFilesForm& img=$INET_IMG/accsfolderdelete-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfolderdelete.gif?FACE=$FACE& title=$TEMPL[bt_delete_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                          } else {
                            $this->Out(makeButton("type=1& name=sDeleteFiles_1& form=ScrFilesForm& img=$INET_IMG/accsfolderdelete-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfolderdelete.gif?FACE=$FACE& title=$TEMPL[bt_delete_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                          }
                          // $this->Out("<img src='$INET_IMG/line_v.gif' border=0 alt='' align='middle'>" .$this->ButtonBlank);
                        } else {
                          $this->Out("<img src='$INET_IMG/accsfolderdelete-unactive.gif?FACE=$FACE' border=0 align=middle alt='$TEMPL[bt_delete_ico]'>");
                        }

                        $this->out($this->ButtonBlank);
                    }

                    $this->TDS(2, 1, "class='body' width='1' nowrap", ""); {
                        $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                    }

                    if (!$this->Data[FldRO]) {
                        $this->TDS(2,2, "valign='top' nowrap class='toolsbarl' colspan=2 valign='bottom'",""); {
                            $this->out($this->ButtonBlank);

                            $this->Out("<input name='NewName' value=\"".htmlspecialchars($s_AccessFolder[Status][NewName])."\" class='toolsbare'>" .$this->ButtonBlank);
                            unset($s_AccessFolder[Status][NewName]);
                            $this->Out(makeButton("type=1& name=sRename_1& form=ScrFilesForm& img=$INET_IMG/filefolderrename-passive.gif& imgact=$INET_IMG/filefolderrename.gif& title=$TEMPL[bt_new_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                            $this->Out(makeButton("type=1& name=sNewFolder_1& form=ScrFilesForm& img=$INET_IMG/filefoldernew-passive.gif& imgact=$INET_IMG/filefoldernew.gif& title=$TEMPL[bt_new_ico]& imgalign=absmiddle"));

                            $this->out($this->ButtonBlank);
                        }

                        $this->TDS(2, 3, "class='body' width='1' nowrap", ""); {
                            $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                        }
                    }

                    $this->TDS(2, 4, "valign='top' nowrap class='toolsbarl'",""); {
                        $this->out($this->ButtonBlank);

                        if (($this->Data[FolderPermission] & 1) == 1) {
                            $this->OUT(makeButton("type=1& form=ScrFilesForm& name=sDownloadZip& img=$INET_IMG/downloadzip-passive.gif?FACE=$FACE& imgact=$INET_IMG/downloadzip.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_dwnl_zip_ico]") .$this->ButtonBlank);
                        } else {
                            $this->OUT("<img src='$INET_IMG/downloadzip-unactive.gif?FACE=$FACE' alt='$TEMPL[bt_dwnl_zip_ico]' align='absmiddle'>" .$this->ButtonBlank);
                        }

                        if (($this->Data[FolderPermission] & 2) == 2) {
                            $this->out(makeButton("type=2& name=uploadbr_zip& onclick=javascript:wUpld('$INET_SRC/upld.php?UID=$this->UID%26Key=$this->Key%26FACE=$FACE%26FS=$FS')& img=$INET_IMG/uploadbrowser-passive.gif?FACE=$FACE& imgact=$INET_IMG/uploadbrowser.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_upld_brw_ico]") .$this->ButtonBlank);
                        } else {
                            $this->OUT("<img src='$INET_IMG/uploadbrowser-unactive.gif?FACE=$FACE' alt='$TEMPL[bt_upld_brw_ico]' align='absmiddle'>" .$this->ButtonBlank);
                        }

                        $this->out(makeButton("type=2& form=ScrFilesForm& name=runftpclient& img=$INET_IMG/runftpclient-passive.gif?FACE=$FACE& imgact=$INET_IMG/runftpclient.gif?FACE=$FACE& imgalign=middle& title=$TEMPL[bt_run_ftp_ico]& onclick=javascript:wFtpOpen('ftp://" . ereg_replace("\@", "\$", $this->USRNAME) . ":" . AuthorizeKey($this->USRNAME) . "@$HTTP_HOST:$FTP_PORT" . ($this->FilePath) . "')"));
                    }
                }  $this->SubTableDone();
            }
        } $this->SubTableDone();

        $this->out( "<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("cellpadding = 5 cellspacing=0 border=0 width='100%' class='toolsbarl'"); {
            $this->SubTable("cellpadding = 0 cellspacing=0 border=0 class='toolsbarl'"); {
                $this->TDNext("class=toolsbarl"); {
                    $this->PutStepUp();
                    $this->Out($this->ButtonBlank);
                }

                $this->TDNext("class='body' width='1' nowrap"); {
                    $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                }


                $this->TDNext("class=toolsbarl"); {
                    $this->out($this->ButtonBlank, "<b>", $TEMPL[curr_path], " :</b><br>");
                    $this->out($this->ButtonBlank, "<b>", $this->HtmlPath, "</b>");
                }
            }  $this->SubTableDone();
        } $this->SubTableDone();

        $this->out( "<img src='$INET_IMG/filler2x1.gif'>");

        $this->ScrFilesTable();

        $this->Out("</form>");
    }


    function PutStepUp()
    {
        global $FS, $ShareID, $INET_IMG, $INET_SRC;
        global $SCRIPT_NAME, $Fri, $FACE, $TEMPL;

        if ($FS == 0 || $FS == "") {
            if (ereg("^[0-9]+$", $this->UID)) {
                $this->out(makeButton("type=2& name=Step_up& img=$INET_IMG/accsfolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[step_up_ico]& onclick=javascript:location = '$INET_SRC/welcome.php?UID=$this->UID%26FACE=$FACE';"));
            } else {
                $this->out("<img src='$INET_IMG/accsfolderup-passive.gif?FACE=$FACE' border=0 align='absmiddle' title='$TEMPL[step_up_ico]'>");
            }
            return;
        }

        if ($this->Data[up] == 0 || $FS == $ShareID){
            $this->out(makeButton("type=2& name=Step_up& img=$INET_IMG/accsfolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[step_up_ico]& onclick=javascript:location.href = '$SCRIPT_NAME?UID=$this->UID%26Key=$this->Key%26FACE=$FACE%26Fri=$Fri';"));
        } else {
            $URL = "$SCRIPT_NAME?UID=$this->UID%26Key=$this->Key%26FACE=$FACE%26Fri=$Fri%26FS=".$this->Data[up];
            if ($this->Data[up] != $ShareID) {
                $URL .= "%26ShareID=" . $ShareID;
            }
            $this->out(makeButton("type=2& name=Step_up& img=$INET_IMG/accsfolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/accsfolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[step_up_ico]& onclick=javascript:location.href = '$URL';"));
        }
    }


    function ScrFilesTable()
    {
        global $FS, $ShareID, $INET_IMG;
        global $s_AccessFolder;
        global $SCRIPT_NAME;
        global $Fri;
        global $TEMPL, $FACE;

        $TagFileCount = 0;
        $s_AccessFolder[DisplayFile] = array();

        // List Attach
        $FS = (int)$FS;

        //$this->SubTable("border=0 width='100%' class='toolsbarl'");
        //$this->TRNext("");
        //$this->TDNext(""); $this->out(ShArr($this->Data));
        //$this->SubTableDone();

        $this->SubTable("border=0 width='100%' class='toolsbarl'"); {

            $this->TRNext(""); {
                $this->TDNext("class='ttp'"); $this->out("<input type='checkbox' name='TagFileAll' title='$TEMPL[select_all_ico]' onClick='javascript:onTagFileAllClick()'>");
                //$this->TDNext("class='ttp'"); $this->out("&nbsp");
                $this->TDNext("class='ttp' width='50%' nowrap"); $this->out("<b>$TEMPL[lb_file_name]</b>" .$this->ButtonBlank  . $this->PutSortsIcons("n"));
                $this->TDNext("class='ttp' width='15%' nowrap"); $this->out("<b>$TEMPL[lb_date]</b>" .$this->ButtonBlank . $this->PutSortsIcons("t"));
                $this->TDNext("class='ttp' width='15%' nowrap"); $this->out("<b>$TEMPL[lb_size]</b>" .$this->ButtonBlank . $this->PutSortsIcons("s"));
                $this->TDNext("class='ttp' width='20%' nowrap"); $this->out("<b>$TEMPL[lb_owner]</b>" .$this->ButtonBlank . $this->PutSortsIcons("o"));
            }

            $FilesCount = 0;
            $FilesSize  = 0;
            for (_reset($this->Data[Files]); $key=_key($this->Data[Files]); _next($this->Data[Files])) {

                //Debug("=".$this->Data[access][p]);
                //if ($this->Data[access][p] == "u") {
                //  continue;
                //}

                $file =& $this->Data[Files][$key];
                if (!$file) {
                    continue;
                }


                if ($FS == 0) {
                    if (($Fri != 0) && ($file[owner] != $Fri)) {
                        continue;
                    }
                }

                if ($file[access][p] == "n") {
                    continue;
                }

                $class = "tlp";
                $class_a = "tlpa";
                if ($file[Clip] != "" || $file[clipflag] != "") {
                    $class = "tla";
                    $class_a = "tlaa";
                }

                $FilesCount += 1;
                $FilesSize  += $file[fsize];

                if ($file[sign] == 0) {
                    $this->TRNext(""); {

                        $this->TDNext("class='$class'"); {
                            $checked = "";
                            $this->out( "<input type='checkbox' name='TagFile[" . $TagFileCount++ . "]' value='".$file[sysnum]."' $checked onClick='javascript:onTagFileClick()'>");
                        }

                        $this->TDNext("class='$class'"); {
                            $this->SubTable("border = 0"); {
                                $this->TDNext("class='$class'"); {
                                    $this->out( "<center><a href='$SCRIPT_NAME?UID=$this->UID&Key=$this->Key&FACE=$FACE&Fri=$Fri&FS=" . $file[sysnum] . ($ShareID != "" ? "&ShareID=$ShareID" : "") . "'><img src='$INET_IMG/folder-yellow.gif' border=0 alt='$TEMPL[open_folder_ico]'></a></center>");
                                }

                                $this->TDNext("class='$class'"); {
                                    $Name_ = $file[name];
                                    if ($file[rem] != "") {
                                        $Name_ .= " ( " . $file[rem] ." )";
                                    }
                                    $Name_ = "<span title=\"" . htmlspecialchars($file[name]) . "\">" . htmlspecialchars($Name_) . "</span>";
                                    $this->out( "<a href='$SCRIPT_NAME?UID=$this->UID&Key=$this->Key&FACE=$FACE&Fri=$Fri&FS=" . $file[sysnum] . ($ShareID != "" ? "&ShareID=$ShareID" : "") . "' alt='$TEMPL[open_folder_ico]'><font class='$class_a'>".$Name_."</font></a>");
                                }
                            } $this->SubTableDone();
                        }

                        $this->TDNext("class='$class' nowrap"); {
                            //$this->out( "File's folder" );
                            $this->out( $this->TextShift . mkdatetime($file[creat]));
                        }

                        $this->TDNext("class='$class'"); {
                            $this->out( "&nbsp" );
                        }

                        $this->TDNext("class='$class'"); {
                            $this->out( $this->TextShift . $file[usrname] . "@" . $file[domainname]);
                        }
                    } // TRNext
                } else {
                    $this->TRNext(""); {

                        $this->TDNext("class='$class'"); {
                            $checked = "";
                            $this->out( "<input type='checkbox' name='TagFile[" . $TagFileCount++ . "]' value='".$file[sysnum]."' $checked onClick='javascript:onTagFileClick()'>");
                        }

                        $this->TDNext("class='$class'"); {
                            $this->SubTable("border = 0"); {
                                $this->TDNext("class='$class'"); {
                                    if (($this->Data[FolderPermission] & 1) == 1) {
                                        $this->out("<a href='" . MakeOwnerFileDownloadURL($file[name], $file[sysnum], $this->USRNAME, 2) . "' target='_blank'>" .
                                                   "<img src='$INET_IMG/view.gif' border=0 alt='$TEMPL[view_file_ico]'></a>");
                                    } else {
                                        $this->out("<img src='$INET_IMG/view-unactive.gif' border=0 alt='$TEMPL[view_file_ico]'>");
                                    }
                                }

                                $this->TDNext("class='$class'"); {
                                    $Name_ = $file[name];
                                    if ($file[rem] != "") {
                                        $Name_ .= " ( " . $file[rem] ." )";
                                    }
                                    $Name_ = "<span title=\"" . htmlspecialchars($file[name]) . "\">" . htmlspecialchars($Name_) . "</span>";
                                    if (($this->Data[FolderPermission] & 1) == 1) {
                                        $this->out("<a href='" . MakeOwnerFileDownloadURL($file[name], $file[sysnum], $this->USRNAME, 1) . "' target='_blank'>" .
                                                    "<font class='$class_a'><b>" . $this->nbsp($Name_) . "</b></font></a>" );
                                    } else {
                                        $this->out( "<font class='$class_a'><b>" . $this->nbsp($Name_) . "</b></font>" );
                                    }
                                }
                            } $this->SubTableDone();
                        }

                        $this->TDNext("class='$class' nowrap"); {
                            //$this->out( "" . $this->nbsp($file[cont]) );
                            $this->out($this->TextShift . mkdatetime($file[creat]));
                        }

                        $this->TDNext("class='$class' align='right' nowrap"); {
                            $k = $file[fsize];
                            $k1 = AsSize($k);
                            $this->out( $this->TextShift . "<span title = '$k bytes'>" . $this->nbsp($k1) . $this->TextShift . "</span>" );
                        }

                        $this->TDNext("class='$class'"); {
                            $this->out( $this->TextShift . $file[usrname] . "@" . $file[domainname]);
                        }
                    }
                }
            }

            $this->TRNext(""); {

                $this->TDNext("class='$class'");
                $this->out( "&nbsp");

                //$this->TDNext("class='$class'"); {
                    //$this->out( "&nbsp");
                //}

                $this->TDNext("class='$class'"); {
                    $this->out( $this->TextShift . "<b>$FilesCount $TEMPL[cnt_files]</b>" . $this->TextShift);
                }

                $this->TDNext("class='$class' align='right'"); {
                    $this->out( "<b>$TEMPL[cnt_size]</b>" );
                }

                $k = $FilesSize;
                $k1 = AsSize($k);
                $this->TDNext("class='$class' align='right'"); {
                    $this->out( "<span title='$k bytes'><b>$k1</b></span>" );
                }

                $this->TDNext("class='$class'"); {
                    $this->out( "&nbsp");
                }
            }

        } $this->SubTableDone("");

        $this->script2();
    }


    function PutSortsIcons($ord)
    {
          global $INET_IMG;
          global $Sort;
          global $REQUEST_URI, $SCRIPT_NAME;
          global $HTTP_GET_VARS;

          $a = $HTTP_GET_VARS;

          $rez = "";

          if ($ord != $Sort) {
            $URL = "";
            $a[Sort] = $ord;
            _reset($a);
            while(list($n, $v) = _each($a)) {
              $URL .= ($URL != "" ? "&" : "") . $n . "=" . UrlEncode($v);
            }
            $rez .= "<a href='$SCRIPT_NAME?$URL'><img src='$INET_IMG/sort1.gif' alt='' border='0'></a>";
          }

          if (strtoupper ($ord) != $Sort) {
            $URL = "";
            $a[Sort] = strtoupper ($ord);
            _reset($a);
            while(list($n, $v) = _each($a)) {
              $URL .= ($URL != "" ? "&" : "") . $n . "=" . UrlEncode($v);
            }
            $rez .= "<a href='$SCRIPT_NAME?$URL'><img src='$INET_IMG/sort2.gif' alt='' border='0'></a>";
          }

          return $rez;
    }



    function SetData()
    {
        global $FS, $R_FS, $ShareID, $Sort;
        global $TEMPL, $Fri, $FACE;

        $this->HtmlPath = "";
        $this->FilePath = "";

        if ($FS != 0) {
            if ($ShareID == "") {
                $this->Log("access_folder : SetData : error N 1 =$FS=$ShareID=");
                $this->Data = array("error" => 1);
                return;
            }

            $this->Data = SetData($this->USR, $this->USRNAME, $FS, $File, &$R_FS);
            if (is_array($this->Data[Files])) {
                uasort($this->Data[Files], "CompareFiles");
            }

            $r_perm = DBExec("SELECT * FROM acc WHERE acc.sysnumfs = '$ShareID' AND username = '$this->USRNAME'", "file: " . __FILE__ . " line: " . __LINE__);
            if ($r_perm->NumRows() == 0) {
                $this->Log("access_folder : SetData : error N 2. ShareID '$ShareID' USRNAME '$this->USRNAME'");
                $this->Data = array("error" => 1);
                return;
            }

            for($i = 0; $i < $r_perm->numfields(); $i++) {
                $this->Data[Sharing][$r_perm->fieldname($i)] = $r_perm->Field($i);
            }

            $this->Data[FolderPermission] = DecodeAccessFlag($r_perm->access());
            $this->Log("FolderPermission " . $this->Data[FolderPermission]);

            $r_fs = DBExec("SELECT * FROM fs WHERE sysnum = $FS", __LINE__);
            while($r_fs->NumRows() == 1 && $r_fs->sysnum() != $ShareID) {
                $this->HtmlPath = "&nbsp;>&nbsp;" . "<a href='$INET_SRC" . "$SCRIPT_NAME?UID=$this->UID&Key=$this->Key&FACE=$FACE&Fri=$Fri&FS=" . $r_fs->sysnum() . (($ShareID != "" && $ShareID != $r_fs->sysnum()) ? "&ShareID=$ShareID" : "") . "' class='toolsbara'><span title=\"" . htmlspecialchars($r_fs->name()) . "\">" . ReformatToLeft($r_fs->name(), 20) . "</span></a>" . $this->HtmlPath;
                $this->FilePath = "/" . rawurlencode($r_fs->name()) . $this->FilePath;

                $r_fs = DBExec("SELECT * FROM fs WHERE sysnum = " . $r_fs->up(), __LINE__);
            }

            if ($r_fs->NumRows() != 1 || $r_fs->sysnum() != $ShareID) {
                $this->Log("access_folder : SetData : error N 3");
                $this->Data = array("error" => 1);
                return;
            }

            #---------------------------------------
            $this->HtmlPath = "&nbsp;>&nbsp;" . "<a href='$INET_SRC" . "$SCRIPT_NAME?UID=$this->UID&Key=$this->Key&FACE=$FACE&Fri=$Fri&FS=" . URLEncode($r_fs->sysnum()) . (($ShareID != "" && $ShareID != $r_fs->sysnum()) ? "&ShareID=$ShareID" : "") . "' class='toolsbara'><span title=\"" . htmlspecialchars($r_fs->name()) . "\">" . ReformatToLeft($r_fs->name(), 20) . "</span></a>" . $this->HtmlPath;
            $this->FilePath = "/" . rawurlencode($r_fs->name()) . $this->FilePath;
        } else {
            $result[FolderPermission] = 1;
            $result[FldAccDirect]     = 0;
            $result[FldAcc]           = 1;
            $result[FldRO]            = 1;
            $result[ownername]        = $this->USRNAME;

            $SQL = "select fs.*, sign(fs.sysnumfile), usr.name as usrname, usr.sysnumdomain, domain.name as domainname, file.fsize, file.ftype as cont, acc.access, acc.access_tracking, gettree(fs.sysnum) as path, clip.ftype AS clipflag from fs LEFT JOIN clip ON clip.sysnumfs = fs.sysnum AND clip.owner = '$this->USRNAME', file, usr, acc, domain where fs.owner = usr.sysnum and usr.sysnumdomain = domain.sysnum and fs.sysnum = acc.sysnumfs and fs.ftype = 'f' and acc.username = '$this->USRNAME' and (acc.expdate >= 'now'::abstime or acc.expdate is NULL) and fs.sysnumfile = file.sysnum union \n"
                 . "select fs.*, sign(fs.sysnumfile), usr.name as usrname, usr.sysnumdomain, domain.name as domainname, 0 as fsize, ''         as cont, acc.access, acc.access_tracking, gettree(fs.sysnum) as path, clip.ftype AS clipflag from fs LEFT JOIN clip ON clip.sysnumfs = fs.sysnum AND clip.owner = '$this->USRNAME',       usr, acc, domain where fs.owner = usr.sysnum and usr.sysnumdomain = domain.sysnum and fs.sysnum = acc.sysnumfs and fs.ftype = 'f' and acc.username = '$this->USRNAME' and (acc.expdate >= 'now'::abstime or acc.expdate is NULL) and fs.sysnumfile = 0 \n";
                 //. "order by sign(fs.sysnumfile), fs.name, fs.sysnum";

            $r_fs = DBExec($SQL, "file: " . __FILE__ . " line: " . __LINE__);
            for($r_fs->Set(0); !$r_fs->eof(); $r_fs->Next()) {
                for($i=0; $i < $r_fs->numfields(); $i++) {
                    $result[Files][$r_fs->sysnum()][$r_fs->fieldname($i)] = $r_fs->Field($i);
                }
                $result[Files][$r_fs->sysnum()][access][p] = $r_fs->access();

                $result[Files][$r_fs->sysnum()][FileAccDirect] = 0;
                $result[Files][$r_fs->sysnum()][FileAcc]       = 1;
                $result[Files][$r_fs->sysnum()][FileRO]        = 1;
            }

            $this->Data = $result;

            if (is_array($this->Data[Files])) {
               uasort($this->Data[Files], "CompareFiles");
            }
        }

        $FriRoot = "/all_files";
        if (ereg("^[0-9]+$", $Fri)) {
            $r_usr = DBFind("usr, domain", "usr.sysnumdomain = domain.sysnum and usr.sysnum=$Fri", "usr.name, domain.name as domainname", "file: " . __FILE__ . " line: " . __LINE__);
            if ($r_usr->NumRows() == 1) {
                $FriRoot = "/" . $r_usr->name() . "@" . $r_usr->domainname();
            }
        }

        $r_guest = DBExec("SELECT * from domain, usr where usr.sysnumdomain = domain.sysnum and usr.name || '@' || domain.name = '$this->USRNAME'", "file: " . __FILE__ . " line: " . __LINE__);
        if ($r_guest->NumRows() == 1) {
            //$FriRoot = "/" . $TEMPL[home_name] . $FriRoot;
            $FriRoot = "/Friends_FTP" . $FriRoot;
        }

        $this->HtmlPath = ">&nbsp;" . "<a href='$INET_SRC" . "$SCRIPT_NAME?UID=$this->UID&Key=$this->Key&FACE=$FACE&Fri=$Fri' class='toolsbara'><span title=\"" . htmlspecialchars($TEMPL[home_name]) . "\">" . htmlspecialchars($TEMPL[home_name]) . "</span></a>" . $this->HtmlPath;
        $this->FilePath = $FriRoot . $this->FilePath;

    }


    function script2()
    {
        $this->out("<script language='javascript'>\n");
        $this->out("  function wUpld(url)\n");
        $this->out("  {\n");
        $this->out("    window.open(url, \"\", \"status=yes,toolbar=no,menubar=no,location=no,resizable=yes\");\n");
        // $this->out("    window.Open(url, \"\", \"width=800,height=300,status=no,toolbar=no,menubar=no,location=no\");\n");
        // $this->out("    window.open(url, \"w1\", \"\");\n");
        $this->out("  }\n");

        $this->out("  function wFtpOpen(url)\n");
        $this->out("  {\n");
        $this->out("    window.open(url, \"\", \"status=yes,toolbar=yes,menubar=no,location=no,resizable=yes\");\n");
        $this->out("  }\n");


        $this->out("</script>\n");
    }


    function rNewView()
    {
        global $s_AccessFolder;

        $s_AccessFolder = array();
        $this->refreshScreen();
    }


    function rNewFolder()
    {
        global  $NewName, $Mes, $FS, $R_FS, $TEMPL, $s_AccessFolder;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if ($FS == 0) {
            $s_AccessFolder[Mes] = 10;
            $this->refreshScreen();
        }


        if ($this->Data[FldRO]) {
            $s_AccessFolder[Mes] = 14;
            $this->refreshScreen();
        }

        $owner = $R_FS->owner();

        if (eregi("[\";`]", $NewName)) {
            $s_AccessFolder[Mes] = 9;
            $this->refreshScreen();
        }

        $NewName = preg_replace("/'/", "''", $NewName);

        if ($NewName == "") {
            $NewName = $this->GetPasteName($FS, $TEMPL[cnt_newfoldername], $owner);
        }

        $r_fld = DBFind("FS", "ftype = 'f' and name = '$NewName' and up = $FS and owner = '$owner'", "", "file: " . __FILE__ . " line: " . __LINE__);
        if ($r_fld->NumRows() != 0) {
            $s_AccessFolder[Mes] = 1;
            $this->refreshScreen();
        }

        DBExec("insert into fs (sysnum,      name,         ftype, up,  sysnumfile, owner,    creat) values ".
                                                  "(NextVal('fs_seq'), '$NewName', 'f',   $FS, 0,          '$owner', 'now'::abstime)", "file: " . __FILE__ . " line: " . __LINE__);
        $this->refreshScreen();
    }



    function rCopyFile($b)
    {
        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        DBExec("delete from clip where owner = '$this->USRNAME'", "file: " . __FILE__ . " line: " . __LINE__);

        $r = $this->Data[Files];

        _reset($r);
        while (list($n, $v) = _each($r)) {
            unset($this->Data[Files][$n][Clip]);
        }

        $this->rCopyAddFile($b);
        $this->refreshScreen();
    }


    function rCopyAddFile($b)
    {
        global $TagFile, $s_AccessFolder;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if (!is_array($TagFile)) {
            $s_AccessFolder[Mes] = 11;
            $this->refreshScreen();
        }

        if (($this->Data[FolderPermission] & 1) != 1) {
            $s_AccessFolder[Mes] = 12;
            $this->refreshScreen();
        }


        while (list($n, $v) = _each($TagFile)) {
            $file = $this->Data[Files][$v];
            // echo "$file[name] ";

            if (!$file) {
                continue;
            }

            // if ($file[sign] == 0) {
            //     $Mes = 7;
            //     return;
            // }

            if ($file[FileRO] && $b == "r") {
                $s_AccessFolder[Mes] = 6;
                $this->refreshScreen();
            } else {
                $this->Data[Files][$v][Clip] = $b;
                DBExec("insert into clip (sysnumfs, owner, ftype) values ('$v', '$this->USRNAME', '$b')", "file: " . __FILE__ . " line: " . __LINE__);
            }
        }

        $this->refreshScreen();
    }


    function rPaste()
    {
        global $FS, $R_FS, $Mes, $s_AccessFolder;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if ($FS == 0) {
            $s_AccessFolder[Mes] = 10;
            $this->refreshScreen();
        }

        if ($this->Data[FldRO]) {
            $s_AccessFolder[Mes] = 14;
            $this->refreshScreen();
        }

        $Owner = $R_FS->owner();

        // -----------------------------------------------
        // Scaning selected list

        $InsertList = array();
        $this->rPaste_FilesLoop(0, $InsertList, NULL);

        if (count($InsertList) == 0) {
            $this->refreshScreen();
        }

        reset($InsertList);
        foreach($InsertList as $Item) {
            if ($Item[sysnum] == $FS) {
                $s_AccessFolder[Mes] = 20;
                $this->refreshScreen();
            }
        }

        // -----------------------------------------------
        // Calc insert's files size

        $InsertSize = 0;
        reset($InsertList);
        while(list($n, $v) = each($InsertList)) {
            if ($v[size] != "" && ($v[prz] == "c" || $v[owner] != $Owner)) {
                $InsertSize += $v[size];
            }
        }

        // -----------------------------------------------
        // Check Quote

        $r = DBExec("SELECT usr.quote        as usrquote," .
                           "domain.quote     as domainquote, " .
                           "usr.diskusage    as usrdiskusage, " .
                           "domain.diskusage as domaindiskusage, " .
                           "domain.userquote as defaultusrquote where usr.sysnumdomain = domain.sysnum and usr.sysnum = '$Owner'");

        if ($r->NumRows() != 1) {
            $this->refreshScreen();
        }

        $UsrQuote = $r->usrquote() != 0 ? $r->usrquote() : $r->defaultusrquote();
        $DomainQuote = $r->domainquote();
        $UsrDiskUsage = $r->usrdiskusage();
        $DomainDiskUsage = $r->domaindiskusage();

        if ($UsrDiskUsage + $InsertSize >= $UsrQuote) {
            $s_AccessFolder[Mes] = 15;
            $this->refreshScreen();
        }
        if ($DomainDiskUsage + $InsertSize >= $DomainQuote) {
            $s_AccessFolder[Mes] = 19;
            $this->refreshScreen();
        }

        // -----------------------------------------------
        // Pasting

        $SourceList = array();
        $DestinationList = array();

        DBExec("BEGIN");

        reset($InsertList);
        while(list($n, $v) = each($InsertList)) {
            $Item =& $InsertList[$n];

            if ($Item[parent]) {
                $InsertFS       = $Item[parent][newsysnum];
                $Item[newowner] = $Item[parent][newowner];
            } else {
                $InsertFS       = $FS;
                $Item[newowner] = $Owner;

                // if shanged owner - need send notification
                if ($Item[newowner] != $Item[owner]) {
                    if ($Item[ownername] != $this->USRNAME) {
                        $SourceList[$Item[ownername]][] =& $Item;
                    }

                    if ($this->Data[ownername] != $this->USRNAME) {
                        $DestinationList[]              =& $Item;
                    }
                }
            }
            $Item[newup] = $InsertFS;

            if ($Item[up] == $InsertFS && $Item[newowner] == $Item[owner] && $Item[prz] == "r") {
                $Item[opr][] = "use without shange";
                $Item[newsysnum] = $Item[sysnum];
                continue;
            }

            if ($Item[sysnumfile] == 0) {
                $this->rPaste_PasteFolder($Item, $InsertFS);
            } else {
                $this->rPaste_PasteFile($Item, $InsertFS);
            }
        }

        DBExec("DELETE FROM clip WHERE clip.owner = '$this->USRNAME' and clip.ftype <> 'c'", __LINE__);

        //DBExec("ROLLBACK");
        //echo ShArr($InsertList), "====<br>\n", ShArr($SourceList), "====<br>\n", ShArr($DestinationList), "====\n<br>", ShArr($this->Data), "====<br>\n";
        //exit;

        // -----------------------------------------------
        // Send notification to sources folder owner

        reset($SourceList);
        while(list($SourceOwnerAddr, $Items) = each($SourceList)) {
            reset($Items);
            $Message = "";
            while(list($ItemID, $v) = each($Items)) {
                $Item =& $Items[$ItemID];

                $r_acc = DBExec("select * from acc where sysnum in (select get_acc('{$this->USRNAME}', {$Item[sysnum]}))", "file: " . __FILE__ . " line: " . __LINE__);
                if ($r_acc->NumRows() != 0 && $r_acc->access_tracking() == "1") {
                    $Item[access_tracking] = $r_acc->access_tracking();
                }
                $r_acc->free();

                if ($Item[access_tracking]) {
                    $Message .= $Item[path] . "\n";
                }
            }

            if ($Message != "") {
                $d = date("r");
                $Message = "User {$this->USRNAME} at {$d} cut/copy file(s)\n" . $Message;
                $this->SendMessage(
                            $SourceOwnerAddr,
                            "System_messager" . preg_replace("/^[^@]+/", "", $SourceOwnerAddr),
                            $Message,
                            "User cut/copy file(s) $this->USRNAME"
                       );
            }
        }

        // -----------------------------------------------
        // Send notification to destination folder owner

        $r_acc = DBExec("select * from acc where sysnum in (select get_acc('{$this->USRNAME}', {$FS}))", "file: " . __FILE__ . " line: " . __LINE__);
        if ($r_acc->NumRows() != 0 && $r_acc->access_tracking() == "1") {
            $Message = "";

            reset($DestinationList);
            while(list($ItemID, $v) = each($DestinationList)) {
                $Item =& $DestinationList[$ItemID];
                $Message .= $this->Data[path] . "/" . $Item[newname] . "\n";
            }

            if ($Message != "") {
                $d = date("r");
                $Message = "User $this->USRNAME at $d paste file(s)\n" . $Message;
                $this->SendMessage(
                            $this->Data[ownername],
                            "System_messager" . preg_replace("/^[^@]+/", "", $this->Data[ownername]),
                            $Message,
                            "User paste file(s) $this->USRNAME"
                        );
            }
        }

        DBExec("COMMIT");


        $this->refreshScreen();
    }


    function rPaste_PasteFolder(&$Item, $InsertFS)
    {
        $TMP = preg_replace("/'/", "''", $Item[name]);
        $r_fs = DBExec("SELECT * FROM fs WHERE up = {$InsertFS} AND owner = {$Item[newowner]} AND ftype = 'f' AND name = '{$TMP}' AND sysnumfile = 0 AND sysnum <> '{$Item[sysnum]}'", __LINE__);

        if ($r_fs->NumRows() != 0) {
            $Item[newsysnum] = $r_fs->sysnum();
            $Item[opr][] = "use foreign";
            if ($Item[prz] == "r") {
                $Item[opr][] = "use delete my old";
                DBExec("DELETE FROM fs WHERE sysnum = {$Item[sysnum]}", __LINE__);
            }
        } else {
            if ($Item['parent']['new']) {
                $Item[newname] = $Item[name];
            } else {
                $Item[newname] = $this->GetPasteName($InsertFS, $Item[name], $Item[newowner]);
            }

            $Item[newname] = preg_replace("/'/", "''", $Item[newname]);
            $Item[rem] = preg_replace("/'/", "''", $Item[rem]);

            if ($Item[prz] == "r") {
                $Item[opr][] = "update";
                $Item[newsysnum] = $Item[sysnum];
                DBExec("UPDATE fs SET up = {$InsertFS}, name = '{$Item[newname]}', owner = '{$Item[newowner]}', creat = 'now'::abstime WHERE sysnum = '{$Item[sysnum]}'", __LINE__);
            } else {
                $r_fs->free();
                $r_fs = DBExec("SELECT nextval('fs_seq'::text) as newsysnum", __LINE__);
                $Item[newsysnum] = $r_fs->newsysnum();

                $Item[opr][] = "insert";
                DBExec("insert into FS (sysnum, ftype, up, name, owner, sysnumfile, rem, creat) values ({$Item[newsysnum]}, 'f', {$InsertFS}, '{$Item[newname]}', '{$Item[newowner]}', '0', '{$Item[rem]}', 'now'::abstime)", __LINE__);
            }

            $Item['new'] = TRUE;
        }

        $r_fs->free();
    }


    function rPaste_PasteFile(&$Item, $InsertFS)
    {
        if ($Item['parent']['new']) {
            $Item[newname] = $Item[name];
        } else {
            $Item[newname] = $this->GetPasteName($InsertFS, $Item[name], $Item[newowner]);
        }

        if($Item[prz] == "r") {
            $Item[newsysnum] = $Item[sysnum];

            $Item[opr][] = "update";
            if ($Item[newowner] != $Item[owner] || $Item[newname] != $Item[name] || $Item[up] != $InsertFS) {
                $Item[opr][] = "updated";
                $Item[newname] = preg_replace("/'/", "''", $Item[newname]);
                DBExec("UPDATE fs SET up = {$InsertFS}, name = '{$Item[newname]}', owner = '{$Item[newowner]}', creat = 'now'::abstime WHERE sysnum = '{$Item[sysnum]}'");
            }
        } else {
            $r_fs = DBExec("SELECT nextval('fs_seq'::text) as newsysnum");
            $Item[newsysnum] = $r_fs->newsysnum();

            $Item[opr][] = "insert";
            $Item[newname] = preg_replace("/'/", "''", $Item[newname]);
            $Item[rem] = preg_replace("/'/", "''", $Item[rem]);
            DBExec("insert into FS (sysnum, ftype, up, name, owner, sysnumfile, rem, creat) values ({$Item[newsysnum]}, 'f', {$InsertFS}, '{$Item[newname]}', '{$Item[newowner]}', '{$Item[sysnumfile]}', '{$Item[rem]}', 'now'::abstime)", __LINE__);

            $Item['new'] = TRUE;
        }
    }


    function rPaste_FilesLoop($FSNum, &$InsertList, $ParentRef)
    {
        if ($FSNum == 0) {
            $r_fs = DBExec("SELECT fs.*, file.fsize, clip.ftype AS prz,  gettree(fs.sysnum) AS path, (usr.name || '@' || domain.name) AS ownername FROM fs LEFT JOIN file ON fs.sysnumfile = file.sysnum, clip, usr, domain WHERE fs.owner = usr.sysnum AND usr.sysnumdomain = domain.sysnum AND clip.sysnumfs = fs.sysnum and clip.owner = '$this->USRNAME'", __LINE__);
        } else {
            $r_fs = DBExec("SELECT fs.*, file.fsize, '' AS prz,          '' AS path,                 '' AS ownername                               FROM fs LEFT JOIN file ON fs.sysnumfile = file.sysnum       WHERE fs.ftype = 'f' AND fs.up = $FSNum", __LINE__);
        }

        while (!$r_fs->EOF()) {
            $Item =& $InsertList[];

            $Item[sysnum] = $r_fs->sysnum();
            $Item[name] = $r_fs->name();
            $Item[up] = $r_fs->up();
            $Item[owner] = $r_fs->owner();
            $Item[ownername] = $r_fs->ownername();
            $Item[rem] = $r_fs->rem();
            $Item[path] = $r_fs->path();
            $Item[sysnumfile] = $r_fs->sysnumfile();

            if (isset($ParentRef)) {
                $Item[parent] =& $ParentRef;
                $Item[parent][numchild]++;
            }

            if (isset($Item[parent])) {
                $Item[prz] =& $Item[parent][prz];
            } else {
                $Item[prz] = $r_fs->prz();
            }

            if ($Item[sysnumfile] == 0) {
                $this->rPaste_FilesLoop($Item[sysnum], $InsertList, &$Item);
            } else {
                $Item[size] = $r_fs->fsize();
            }

            $r_fs->Next();
        }

        $r_fs->free();
    }


    function GetPasteName($FS, $name_org, $owner)
    {
        preg_match("/^([^\.]*)(.*)$/i", $name_org, $MATH);
        $filename = preg_replace("/'/", "''", $MATH[1]);
        $fileext  = preg_replace("/'/", "''", $MATH[2]);

        $r = DBExec("SELECT pastename('$filename', '$fileext', $owner, $FS) as newname", "file: " . __FILE__ . " line: " . __LINE__);
        return $r->newname();
    }


    function rDownloadZip()
    {
        global $TagFile, $Mes, $PROGRAM_TMP, $INET_SRC, $s_AccessFolder;
        global $REMOTE_ADDR;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if (($this->Data[FolderPermission] & 1) != 1) {
            $s_AccessFolder[Mes] = 12;
            $this->refreshScreen();
        }

        if (!is_array($TagFile)) {
            $s_AccessFolder[Mes] = 16;
            $this->refreshScreen();
        }

        $SourseOwner = array(); //list pasted files for send to sourses files owners
        $files = array();

        //echo "TagFile:<br>", sharr($TagFile), "<br>";

        reset($TagFile);
        while (list($n, $v) = _each($TagFile)) {
            $file =& $this->Data[Files][$v];

            if (!$file) {
                continue;
            }

            $files[] = $file[sysnum];

            if ($this->Data[path] != "" && $this->Data[Sharing][access_tracking] == "1") {
                $SourseOwner[ $file[usrname] . "@" . $file[domainname] ][] = $this->Data[path] ."/" . $file[name];
            } else {
                if ($file[path] != "" && $file[access_tracking] == "1") {
                    $SourseOwner[ $file[usrname] . "@" . $file[domainname] ][] = $file[path];
                }
            }
        }

        //echo "SourseOwner:<br>", sharr($SourseOwner);
        //exit;

        if (!is_array($files)) {
            $s_AccessFolder[Mes] = 16;
            $this->refreshScreen();
        }

        $RootPath = "$PROGRAM_TMP/dwnl_dir_" . posix_getpid() . "_" . time();
        if (!mkdir($RootPath, 0777)) {
            $Mes = 3;
            return;
        }


        $ZipSize = 0;
        $UserTable = array();
        if(!$this->DlZip(0, $RootPath, $files, &$UserTable, &$ZipSize)) {
            system("rm -rf $RootPath");
            $Mes = 3;
            return;
        }

        $this->Log("Zip Files size " . $ZipSize);
        if($ZipSize > 50 * 1024 * 1024) {
            system("rm -rf $RootPath");
            $Mes = 21;
            return;
        }

        $ZipName = "$PROGRAM_TMP/dwnl_zip_" . posix_getpid() . "_" . time();
        system("cd $RootPath; zip -uqr $ZipName *");

        system("rm -rf $RootPath");
        #system("rm -rf $ZipName.zip");

        _reset($UserTable);
        //echo sharr($UserTable);
        while(list($n, $v) = _each($UserTable)) {
           DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('$n', getdomain('$n'), 'downzip', datetime('now'::abstime), '$v', '0', '" . substr($this->USRNAME, 0, 20) . "', -1, '$REMOTE_ADDR')", "file: " . __FILE__ . " line: " . __LINE__);
        }

        $this->Log("SourseOwner:\n", sharr($SourseOwner));
        reset($SourseOwner);
        //echo sharr($own);
        while(list($ownername, $v) = each($SourseOwner)) {
            $message = "";
            reset($v);
            while(list($n1, $v1) = each($v)) {
                $message .= $v1 . "\n";
            }
            $d = date("r");

            if ($message != "") {
                $message = "User $this->USRNAME at $d Download via zip file(s)\n" . $message;
                $this->SendMessage(
                            $ownername,
                            "System_messager" . preg_replace("/^[^@]+/", "", $ownername),
                            $message,
                            "Download via zip file(s) $this->USRNAME"
                       );
                $this->Log($message);
            }
        }

        header("Location: $INET_SRC/view_file.php/download.zip?UID=$this->UID&Key=$this->Key&DownloadZip=". urlencode(basename("$ZipName.zip")));
        exit;
    }


    function DlZip($level, $root, $files, &$UserTable, &$ZipSize)
    {

        global $PROGRAM_FILES;

        if (!is_array($files) || !(Count($files) > 0)) {
            return 1;
        }

        $s = "";
        reset($files);
        while (list($n, $v) = _each($files)) {
            $s .= ($s != "" ? " or " : "") . "fs.sysnum = " . $v;
        }

        $this->Log("Select $s");

        #$r = DBFind("fs, file", "fs.ftype = 'f' and fs.sysnumfile <> 0 and fs.sysnumfile = file.sysnum and ($s)", "fs.name, fs.sysnumfile, fs.owner, file.fsize", "file: " . __FILE__ . " line: " . __LINE__);
        $r = DBExec("SELECT fs.name, fs.sysnumfile, fs.owner, file.fsize, file.numstorage " .
                                                                         "from " .
                                                                            "file, " .
                                                                            "fs left join acc on fs.sysnum = acc.sysnumfs " .
                                                                         "where " .
                                                                            "fs.ftype = 'f' and " .
                                                                            "fs.sysnumfile <> 0 and " .
                                                                            "fs.sysnumfile = file.sysnum and ($s)", "file: " . __FILE__ . " line: " . __LINE__);
        $this->Log("Selected " . $r->NumRows());

        while (!$r->eof()) {
            #Debug("2 " . $r->name());
            $this->Log($PROGRAM_FILES . "/storage"  . $r->numstorage() . "/" . $r->sysnumfile(). " " . $r->fsize());
            if(!@symlink($PROGRAM_FILES . "/storage"  . $r->numstorage() . "/" . $r->sysnumfile(), $root. "/" . $r->name())) {
                if(!@symlink($PROGRAM_FILES . "/storage"  . $r->numstorage() . "/" . $r->sysnumfile(), $root. "/" . $r->name() . " " . $r->sysnum())) {
                    return 0;
                }
            }
            $ZipSize += $r->fsize();
            $UserTable[$r->owner()] += $r->fsize();

            $r->Next();
        }

        #$r = DBFind("fs", "fs.ftype = 'f' and fs.sysnumfile = 0 and ($s)", "name, sysnum", "file: " . __FILE__ . " line: " . __LINE__);
        $r = DBExec("SELECT fs.name, fs.sysnum from " .
                                                   "fs left join acc on fs.sysnum = acc.sysnumfs " .
                                               "where " .
                                                   "fs.ftype = 'f' and " .
                                                   "fs.sysnumfile = 0 and ($s)",
                                                   "file: " . __FILE__ . " line: " . __LINE__);
        while (!$r->eof()) {

            $this->Log("Folder name " . $root . "/" . $r->name());

            mkdir($root . "/" . $r->name(), 0777);

            $r1 = DBFind("fs", "fs.up = " . $r->sysnum(), "sysnum", "file: " . __FILE__ . " line: " . __LINE__);

            $Arr = array();
            while (!$r1->eof()) {
               $Arr[] = $r1->sysnum();
               $r1->Next();
            }

            if (!$this->DlZip($level + 1, $root . "/" . $r->name(), $Arr, $UserTable, &$ZipSize)) {
               return 0;
            }

            $r->Next();
        }

        return 1;
    }


    function rDeletePermission()
    {
        global $TagFile, $s_AccessFolder;

        if (!is_array($TagFile)) {
            $this->refreshScreen();
        }

        if ($FS != 0 && $FS != "") {
            $s_AccessFolder[Mes] = 10;
            $this->refreshScreen();
        }

        $s = "";
        reset($TagFile);
        while (list ($n, $v) = each($TagFile)) {
            $s .= ($s != "" ? " or " : "") . "acc.sysnumfs = $v";
        }

        if ($s != "") {
            DBExec("DELETE FROM acc WHERE ($s) AND acc.username = '" . $this->USRNAME. "'");
        }

        $this->refreshScreen();
    }


    function rDeleteFile()
    {
        global $TagFile, $s_AccessFolder, $FS;

        if (!is_array($TagFile)) {
            $this->refreshScreen();
        }

        if ($FS == 0 || $FS == "") {
            $s_AccessFolder[Mes] = 10;
            $this->refreshScreen();
        }

        if ($this->Data[FldRO]) {
            $s_AccessFolder[Mes] = 14;
            $this->refreshScreen();
        }

        if (($this->Data[FolderPermission] & 3) != 3) {
            $s_AccessFolder[Mes] = 12;
            $this->refreshScreen();
        }

        while (list($n, $v) = _each($TagFile)) {
            $file = $this->Data[Files][$v];
            // echo "$file[name] ";

            if (!$file) {
                continue;
            }


            if ($file[FileRO]) {
                $s_AccessFolder[Mes] = 5;
                $this->refreshScreen();
            } else {
                $s .= ($s != "" ? " or " : "") . "fs.sysnum = $v";
                unset($this->Data[Files][$v]);
            }
        }

        if ($s == "") {
            $this->refreshScreen();
        }

        // echo "$s<br>";

        $r_fs = DBFind("fs", "($s)", "", "file: " . __FILE__ . " line: " . __LINE__);

        while(!$r_fs->eof()) {
            if ($r_fs->sysnumfile() == 0) {
                DeleteDirectory($r_fs->sysnum());
            }
            $r_fs->Next();
        }

        DBExec("begin", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
        DBExec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

        DBExec("delete from fs where ($s) and ftype = 'f'", "file: " . __FILE__ . " line: " . __LINE__);

        DBExec("COMMIT", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

        $this->refreshScreen();
    }


    function rRename()
    {
        global $FS, $TagFile, $NewName, $s_AccessFolder;
        global $Mes;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if ($FS == 0) {
            $s_AccessFolder[Mes] = 10;
            $this->refreshScreen();
        }

        if ($this->Data[FldRO]) {
            $s_AccessFolder[Mes] = 14;
            $this->refreshScreen();
        }

        if (!is_array($TagFile)) {
            $s_AccessFolder[Mes] = 11;
            $this->refreshScreen();
        }

        if (count($TagFile) != 1) {
            $s_AccessFolder[Mes] = 8;
            $this->refreshScreen();
        }

        reset($TagFile);
        list($n, $TargetNumber) = each($TagFile);

        if (!$this->Data[Files][$TargetNumber]) {
            $this->refreshScreen();
        }

        if ($NewName == "") {
            $s_AccessFolder[Mes] = 9;
            $this->refreshScreen();
        }

        if (eregi("[\";`]", $NewName)) {
            $s_AccessFolder[Mes] = 9;
            $this->refreshScreen();
        }

        $NewName = preg_replace("/'/", "''", $NewName);

        $r_fld = DBFind("FS", "ftype = 'f' and name = '$NewName' and up = $FS and owner = '$this->UID'", "", "file: " . __FILE__ . " line: " . __LINE__);
        if ($r_fld->NumRows() != 0) {
            $s_AccessFolder[Mes] = 1;
            $this->refreshScreen();
        }

        $s = "UPDATE fs set name = '$NewName' where sysnum = " . $TargetNumber;
        // echo $s;

        DBExec($s, "file: " . __FILE__ . " line: " . __LINE__);

        $this->refreshScreen();
    }


    function rSelFriend()
    {
        global $SelFriend, $Fri;
        global $SCRIPT_NAME, $FS, $FACE;

        if (($FS == 0) && (($SelFriend == "A") || ($SelFriend != 0))) {
            $Fri = $SelFriend;
        }

        $this->refreshScreen();
    }

    function SendMessage($To, $From, $Message, $Subj)
    {
        mail(
                $To,
                $Subj,
                $Message,
                "Content-Type : Text/PLAIN\r\nFrom: $From\r\nReply-To: $From\r\nX-Afik1-Access-Notification: on\r\n",
                "-f$From"
        );
    }


    function refreshScreen()
    {
        global $Fri, $FS, $ShareID, $SCRIPT_NAME, $FACE, $Sort;

        $URL = "$SCRIPT_NAME?UID=$this->UID&Key=$this->Key&FACE=$FACE";

        if ($FS != "") {
            $URL .= "&FS=" . URLENCODE($FS);
        }
        if ($ShareID != "" && $ShareID != 0) {
            $URL .= "&ShareID=" . URLENCODE($ShareID);
        }
        if ($Fri != "") {
            $URL .= "&Fri=" . URLENCODE($Fri);
        }
        if ($Sort != "") {
            $URL .= "&Sort=" . URLENCODE($Sort);
        }

        header("Location: $URL");
        exit;

        global $INET_SRC, $REQUEST_URI;

        UnconnectFromDB();

        header("Location: " . $INET_SRC . $REQUEST_URI);
        exit;
    }

    function script()
    {
        global $INET_SRC;
        screen::script();
        echo "<script language='javascript' src='$INET_SRC/access_folder.js'></script>\n";
    }

} // end of class CAccessFolderScreen


function CompareFiles($a, $b)
{
          global $Sort;
          if ($Sort == "") {
             $Sort = "n";
          }

          // echo "$a[name] $b[name]<br>";


          if ($a[sign] < $b[sign]) return -1;
          if ($a[sign] > $b[sign]) return 1;

          switch ($Sort) {
            case "t" :
                       if ($a[creat] < $b[creat]) return -1;
                       if ($a[creat] > $b[creat]) return 1;
                       break;
            case "T" :
                       if ($a[creat] < $b[creat]) return 1;
                       if ($a[creat] > $b[creat]) return -1;
                       break;
            case "s" :
                       if ($a[fsize] < $b[fsize]) return -1;
                       if ($a[fsize] > $b[fsize]) return 1;
                       break;
            case "S" :
                       if ($a[fsize] < $b[fsize]) return 1;
                       if ($a[fsize] > $b[fsize]) return -1;
                       break;
            case "o" :
                       if ($a[creat] < $b[creat]) return -1;
                       if ($a[creat] > $b[creat]) return 1;
                       break;
            case "O" :
                       if ($a[creat] < $b[creat]) return 1;
                       if ($a[creat] > $b[creat]) return 1;
                       break;
            case "n" :
                       break;
            case "N" :
                       if ($a[name] < $b[name]) return 1;
                       if ($a[name] > $b[name]) return -1;
                       break;
          }

          if ($a[name] < $b[name]) return -1;
          if ($a[name] > $b[name]) return 1;
          return 0;

}

?>
