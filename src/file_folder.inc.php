<?php

/*
class CFileFolderScreen extends screen // @METAGS CFileFolderScreen
    function CFileFolderScreen()
    function LastSelected()
    function mes() overlaped virtuals function
    function Referens() overlaped virtuals function
    function Scr() overlaped virtuals function
      function ScrFiles()
        function PutFilesTable()
          function PutSortsIcons($ord)
        function PutStepUp()
        function MakePath()
        function SetData()
      function ScrSharing()
      function ScrEditSharing()
      function ScrOptions()
      function ScrPostMes()
    function rNewView()
    function rChangeDir()
    function rNewFolder()
    function rRename()
    function rCopyFile($b)
    function rCopyAddFile($b)
    function rPaste()
        function rPaste_PasteFolder(&$Item, $InsertFS)
        function rPaste_PasteFile(&$Item, $InsertFS)
        function rPaste_FilesLoop($FSNum, &$InsertList, $ParentRef)
    function GetPasteName($FS, $name_org, $owner)
    function rDeleteFile()
    function rDownloadZip()
    function DlZip($root, $files)
    function Sharing()
    function rSharingSet()
    function SharingCancel()
    function rEditSharing()
    function rEditSharingSet()
    function rEditSharingCancel()
    function rSortSet()
    function rOptions()
    function rOptionsSet()
    function rOptionsCancel()
    function rQuickSharing()
    function refreshScreen() overlaped virtuals function
    function rRetMes()
    function script()
*/

include("_config.inc.php");
require("cont.inc.php");
require("file.inc.php");
require("utils.inc.php");

require("db.inc.php");

require("screen.inc.php");

class CFileFolderScreen extends screen
{

    function CFileFolderScreen()
    {
        global $FS, $File, $NPage, $SCRIPT_NAME;
        global $TEMPL;

        $this->screen(); // inherited constructor
        $this->SetTempl("file_folder");

        $FS = (int)$FS;
        if(!ereg("^[0-9]+$", $FS)) {
           $FS = 0;
        }

        $File = (int)$File;
        if(!ereg("^[0-9]+$", $File)) {
            $File = 0;
        }

        $NPage = (int)$NPage;
        if(!ereg("^[0-9]+$", $NPage)) {
            $NPage = 0;
        }

        $this->SetData();

        if(isset($GLOBALS[PermAddr]) && $GLOBALS[PermAddr] == -1) {
            unset($GLOBALS[PermAddr]);
        }

        // $R_FS->Set(0);
        $this->PgTitle = "<b>$TEMPL[title]</b> ";
        if ($FS != 0) {
            $this->PgTitle .= "<span title=\"" . htmlspecialchars($this->Data[name]) . "\">" . ReformatToLeft($this->Data[name], 30) . "</span>";
        } else {
            $FS = 0;
            $this->PgTitle .= $TEMPL[home_title];
        }
        $this->Trans("sSortSet", "sSortSetDirection");

        $this->Trans("sCopy",   "");
        $this->Trans("sCut",   "");
        $this->Trans("sPaste",  "");
        $this->Trans("sDelete",  "");
        $this->Trans("sNewFolder",  "");
        $this->Trans("sRename",  "");
        $this->Trans("sSharing", "");
        $this->Trans("sEditSharing", "");
        $this->Trans("sOptions", "sOptionsFileNumber");
        $this->Trans("sSortSet", "sSortSetDirection");

        $this->Request_actions["sNewView"]            = "rNewView()";
        $this->Request_actions["sChangeDir"]          = "rChangeDir()";
        $this->Request_actions["sNewFolder"]          = "rNewFolder()";
        $this->Request_actions["sCopy"]               = "rCopyFile('c')";
        $this->Request_actions["sCopyAdd"]            = "rCopyAddFile('c')";
        $this->Request_actions["sCut"]                = "rCopyFile('r')";
        $this->Request_actions["sCutAdd"]             = "rCopyAddFile('r')";
        $this->Request_actions["sPaste"]              = "rPaste()";
        $this->Request_actions["sDelete"]             = "rDeleteFile()";
        $this->Request_actions["sRename"]             = "rRename()";
        $this->Request_actions["sDownloadZip"]        = "rDownloadZip()";
        $this->Request_actions["sSortSet"]            = "rSortSet()";
        $this->Request_actions["sPrevScr"]            = "rShiftScr(-1)";
        $this->Request_actions["sNextScr"]            = "rShiftScr(1)";

        //-----------------------------------------
        $this->Request_actions["sOptionsSet"]         = "rOptionsSet()";
        $this->Request_actions["sOptionsCancel"]      = "rOptionsCancel()";
        $this->Request_actions["sOptions"]            = "rOptions()";
        $this->Request_actions["sQuickSharing"]       = "rQuickSharing()";
        //-----------------------------------------
        $this->Request_actions["sSharingSet"]         = "rSharingSet()";
        $this->Request_actions["sSharingSet_down"]    = "rSharingSet()";
        $this->Request_actions["sSharingCancel"]      = "SharingCancel()";
        $this->Request_actions["sSharingCancel_down"] = "SharingCancel()";
        $this->Request_actions["sSharing"]            = "Sharing()";
        //-----------------------------------------
        $this->Request_actions["sEditSharingSet"]     = "rEditSharingSet()";
        $this->Request_actions["sEditSharingCancel"]  = "rEditSharingCancel()";
        $this->Request_actions["sEditSharing"]        = "rEditSharing()";
        //-----------------------------------------
        $this->Request_actions["sRetMes"]             = "rRetMes()";

        $this->SaveScreenStatus();

        // Change globals variables for checked list
        $this->LastSelected();
    }


    function Actions() // overlaped virtuals function
    {
        if (!$this->Data[error]) {
            parent::Actions();
        }
    }


    function SetIdentificationCookies() // overlaped virtuals function
    {
        global $sDownloadZip, $s_FileFolder;

        if (!$sDownloadZip) {
            parent::SetIdentificationCookies();
        }
    }


    function OpenSession() // overlaped virtuals function
    {
        global $sDownloadZip, $s_FileFolder;

        parent::OpenSession();
        session_register("s_FileFolder");
    }


    function LastSelected()
    {
        global $TagFile;
        global $s_FileFolder, $sChangeDir, $_SERVER;

        if(!($_SERVER[REQUEST_METHOD] != "GET" || $sChangeDir == "on")) {
            return;
        }

        if (!is_array($s_FileFolder[SelectedFiles])) {
            $s_FileFolder[SelectedFiles] = array();
        }

        if (is_array($s_FileFolder[DisplayFile]) && count($s_FileFolder[DisplayFile]) != 0) {
            reset($s_FileFolder[DisplayFile]);
            while(list($n, $v) = each($s_FileFolder[DisplayFile])) {
                unset($s_FileFolder[SelectedFiles][$v]);
            }
        }

        if ($TagFile) {
            if (!is_array($TagFile)) {
                $TagFile = split(":", $TagFile);
            }
            if (is_array($TagFile)) {
                reset($TagFile);
                while(list($n, $v) = each($TagFile)) {
                    $s_FileFolder[SelectedFiles][$v] = $v;
                }
            }
        }
    }

    function mes()
    {
        global $Mes, $MesParam, $s_FileFolder, $TEMPL;


        if ($Mes == "") {
            $Mes = $s_FileFolder[Mes];
            unset($s_FileFolder[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_FileFolder[MesParam];
            unset($s_FileFolder[MesParam]);
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


    function Referens()
    {
        global $s_FileFolder;
        if ($s_FileFolder[Ret] == "") {
            screen::Referens();
        }
    }


    // save status of fields after submit of form and before refresh of screen
    function SaveScreenStatus()
    {
        global $s_FileFolder, $_REQUEST;

        $SaveFieldsList = array("NewName", "fQuickPermName", "fQuickPermLev", "Description");

        reset($SaveFieldsList);
        while(list($n, $v) = each($SaveFieldsList)) {
            if (!isset($_REQUEST[$v])) {
                continue;
            }
            if (!is_array($_REQUEST[$v])) {
                $s_FileFolder[Status][$v] = $_REQUEST[$v];
            } else {
                reset($_REQUEST[$v]);
                while(list($ins_n, $ins_v) = each($_REQUEST[$v])) {
                    $s_FileFolder[Status][$v][$ins_n] = $ins_v;
                }
            }
        }
    }


    function Scr() // @METAGS Scr
    {
        global $s_FileFolder, $View;

        if ($this->Data[error]) {
            return;
        }

        if ($View == "") {
            $View = "Files";
        }

        if ($s_FileFolder[Sort] == "") {
            $s_FileFolder[Sort] = "n";
        }

        // Debug($View);

        if ($View == "Sharing") {
            $this->ScrSharing();
        } else {
            if ($View == "EditSharing") {
                $this->ScrEditSharing();
            } else {
                if ($View == "Options") {
                    $this->ScrOptions();
                } else {
                    $View = "Files";
                    $this->ScrFiles();
                }
            }
        }

        //$this->SubTable("border = 1");
        //$this->out("=".sharr($s_FileFolder));
        //$this->out("=".sharr($GLOBALS[_SESSION]));
        //$this->out("=".sharr($this->Data));
        //$this->out("=".shGLOBALS());
        //$this->SubTableDone();
    }


    function ScrFiles() // @METAGS ScrFiles
    {
        global $s_FileFolder;
        global $FS, $SCRIPT_NAME;
        global $NewName, $R_FS, $HTTP_HOST, $FTP_HOST, $FTP_PORT;
        global $INET_SRC, $INET_CGI, $INET_IMG, $INET_HELP, $TEMPL, $FACE;
        global $NPage, $MaxNumPage, $LinePerScreen;


        $this->MakePath(&$HtmlPath, &$FullPath);

        $this->out("<form method='post' name='ScrFilesForm'>");

        $this->SubTable("border='0' width='100%' cellspacing = '0' cellpadding='0' grborder"); {

            if ($s_FileFolder[Ret] != "") {
                $this->TRNext("valign='middle' nowrap class='toolsbarl'"); {
                    $this->SubTable("cellpadding = 3 cellspacing=0 border=0"); {
                        $this->TRNext(); {
                            $this->Out($this->ButtonBlank);
                            $this->Out(makeButton("type=1& form=ScrFilesForm&  name=sRetMes& img=$INET_IMG/filefolderdone-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderdone.gif?FACE=$FACE") .$this->ButtonBlank);
                        }
                    } $this->SubTableDone();
                }
            }


            $this->TRNext(); {
                $this->TDNext("class='toolsbarl' nowrap"); {
                    $this->SubTable("cellpadding = 5 cellspacing=0 border=0"); {
                        $this->SubTable("cellpadding = 0 cellspacing=0 border=0"); {
                            $this->TDNext("class='toolsbarl' nowrap"); {
                                $this->Out($this->ButtonBlank);
                                $this->out(makeButton("type=1& form=ScrFilesForm& name=sSharing_1&    img=$INET_IMG/filefoldersharing-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldersharing.gif?FACE=$FACE& title=$TEMPL[bt_sharing_ico]"));
                                $this->Out($this->ButtonBlank);
                            }

                            $this->TDNext("class='body' width='1' nowrap"); {
                                $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                            }

                            $this->TDNext("class='toolsbarl' nowrap"); {
                                $this->Out($this->ButtonBlank);
                                $this->Out(makeButton("type=1& name=sCut_1& form=ScrFilesForm& img=$INET_IMG/filefoldercut-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldercut.gif?FACE=$FACE& title=$TEMPL[bt_cut_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                                $this->Out(makeButton("type=1& name=sCopy_1& form=ScrFilesForm& img=$INET_IMG/filefoldercopy-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldercopy.gif?FACE=$FACE& title=$TEMPL[bt_copy_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                                $this->Out(makeButton("type=1& name=sPaste_1& form=ScrFilesForm& img=$INET_IMG/filefolderpaste-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderpaste.gif?FACE=$FACE& title=$TEMPL[bt_paste_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                                $this->Out(makeButton("type=1& name=sDelete_1& form=ScrFilesForm& img=$INET_IMG/filefolderdelete-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderdelete.gif?FACE=$FACE& title=$TEMPL[bt_delete_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                            }

                            $this->TDNext("class='body' width='1' nowrap"); {
                               $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                            }

                            $this->TDNext("class='toolsbarl' nowrap"); {
                                $this->Out($this->ButtonBlank);
                                $this->Out("<input name='NewName' value=\"". htmlspecialchars($s_FileFolder[Status][NewName]) . "\" class='toolsbare'>" .$this->ButtonBlank);
                                unset($s_FileFolder[Status][NewName]);
                                $this->Out(makeButton("type=1& name=sRename_1& form=ScrFilesForm& img=$INET_IMG/filefolderrename-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderrename.gif?FACE=$FACE& title=$TEMPL[bt_rename_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                                $this->Out(makeButton("type=1& name=sNewFolder_1& form=ScrFilesForm& img=$INET_IMG/filefoldernew-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldernew.gif?FACE=$FACE& title=$TEMPL[bt_new_ico]& imgalign=absmiddle") . $this->ButtonBlank);
                            }

                            $this->TDNext("class='body' width='1' nowrap"); {
                                $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                            }

                            $this->TDNext("class='toolsbarl' nowrap"); {
                                $this->Out($this->ButtonBlank);
                                $this->Out(makeButton("type=1& form=ScrFilesForm& name=sDownloadZip& img=$INET_IMG/filefolderdownloadzip-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderdownloadzip.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_dwnl_zip_ico]") .$this->ButtonBlank);
                            }

                            $this->TDNext("class='body' width='1' nowrap"); {
                                $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                            }

                            $this->TDNext("class='toolsbarl' nowrap"); {
                                $this->Out($this->ButtonBlank);
                                $this->out(makeButton("type=2& form=ScrFilesForm& name=runftpclient& onclick=javascript:wFtpOpen('ftp://" . ereg_replace("\@", "\$", $this->USRNAME) . ":" . AuthorizeKey($this->USRNAME) . "@$FTP_HOST:$FTP_PORT" . ($FullPath) . "')& img=$INET_IMG/filefolderrunftpclient-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderrunftpclient.gif?FACE=$FACE& imgalign=middle& title=$TEMPL[bt_run_ftp_ico]") . $this->ButtonBlank);
                                if (!$this->Data[FldRO]) {
                                    $this->out(makeButton("type=2& name=uploadbr_zip& onclick=javascript:wUpld('$INET_SRC/upld.php?UID=$this->UID%26FACE=$FACE%26FS=$FS')& img=$INET_IMG/filefolderuploadbrowser-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderuploadbrowser.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_upld_brw_ico]") . $this->ButtonBlank);
                                }
                            }
                        } $this->SubTableDone();
                    } $this->SubTableDone();
                }
            }

            $this->TRNext("class=toolsbarl"); {
                $this->SubTable("cellpadding = '5' cellspacing = '0' border = '0' width='100%'"); {
                    $this->SubTable("cellpadding = '0' cellspacing = '0' border = '0'"); {
                        $this->TDNext("class='toolsbarl' nowrap"); {
                            $this->Out($this->ButtonBlank);
                            $this->PutStepUp();
                            $this->Out($this->ButtonBlank);
                        }

                        $this->TDNext("class='body' width='1' nowrap"); {
                            $this->out("<img src='$INET_IMG/filler1x1.gif'>");
                        }

                        $this->TDNext("class=toolsbarl"); {
                            $this->out($this->ButtonBlank, "<b>", $TEMPL[curr_path], " :</b><br>");
                            $this->out($this->ButtonBlank, "<b>", $HtmlPath, "</b>");
                        }
                    } $this->SubTableDone();
                } $this->SubTableDone();
            }

            if ($MaxNumPage > 0) {
                $this->TRNext(); {
                    $this->TDNext("class='toolsbarl' nowrap"); {
                        $this->SubTable("cellpadding = 5 cellspacing=0 border=0"); {
                            $this->TDNext("class='toolsbarl'"); {
                                if ($NPage > 0) {
                                    $s = makeButton("type=1& form=ScrFilesForm& name=sPrevScr& img=$INET_IMG/filefolderarrowleft-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderarrowleft.gif?FACE=$FACE& title=$TEMPL[bt_prev_scr_ico]");
                                } else {
                                    $s = "<img src='$INET_IMG/filefolderarrowleft-unactive.gif' align='absmiddle' alt='$TEMPL[bt_prev_scr_ico]'>";
                                }
                                $this->Out($s);

                                $this->Out($this->ButtonBlank);

                                if ($NPage < $MaxNumPage) {
                                    $s = makeButton("type=1& form=ScrFilesForm& name=sNextScr& img=$INET_IMG/filefolderarrowright-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderarrowright.gif?FACE=$FACE& title=$TEMPL[bt_next_scr_ico]");
                                } else {
                                    $s = "<img src='$INET_IMG/filefolderarrowright-unactive.gif' align='absmiddle' alt='$TEMPL[bt_next_scr_ico]'>";
                                }
                                $this->Out($s);

                                $this->Out($this->ButtonBlank);
                                $this->out("Page <b>" . ($NPage + 1) . "</b> From <b>" . ($MaxNumPage + 1) . "</b>");
                            }
                        } $this->SubTableDone();
                    }
                }
            }

        } $this->SubTableDone();


        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->PutFilesTable();

        $this->out("</form>");
    }


    function PutStepUp()
    {
        global $FS, $s_FileFolder, $TEMPL, $FACE;
        global $INET_SRC, $INET_IMG, $SCRIPT_NAME;

        #---------------------------------------
        if ($FS == 0 || $FS == "") {
            if ($s_FileFolder[Ret] == "") {
                $this->out(makeButton("type=2& name=Step_up& img=$INET_IMG/filefolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[step_up_ico]& onclick=javascript:location = '$INET_SRC/welcome.php?UID=$this->UID%26FACE=$FACE';"));
            } else {
                $this->out("<img src='$INET_IMG/filefolderup-passive.gif?FACE=$FACE' border=0 align='absmiddle' title='$TEMPL[step_up_ico]'>");
            }

            return;
        }

        #---------------------------------------
        if ($this->Data[up] != 0) {
            $this->out(makeButton("type=2& name=Step_up& img=$INET_IMG/filefolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[step_up_ico]& onclick=javascript:ChangeDir('$SCRIPT_NAME?UID=$this->UID%26FACE=$FACE%26FS=".$this->Data[up]."');"));
        } else {
            $this->out(makeButton("type=2& name=Step_up& img=$INET_IMG/filefolderup-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderup.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[step_up_ico]& onclick=javascript:ChangeDir('$SCRIPT_NAME?UID=$this->UID%26FACE=$FACE');"));
        }
    }


    function MakePath(&$HtmlPath, &$FilePath)
    {
        global $FS, $s_FileFolder;
        global $TEMPL, $FACE;
        global $INET_SRC, $SCRIPT_NAME;

        $r_fs = DBExec("SELECT * FROM fs WHERE sysnum = $FS", __LINE__);

        $HtmlPath = "";
        $FilePath = "";

        while($r_fs->NumRows() == 1) {
            $HtmlPath = "&nbsp;>&nbsp;" . "<a href='javascript:ChangeDir(\"$INET_SRC" . "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&FS=" . URLEncode($r_fs->sysnum()) . "\")' class='toolsbara'><span title=\"" . htmlspecialchars($r_fs->name()) . "\">" . ReformatToLeft($r_fs->name(), 20) . "</span></a>" . $HtmlPath;
            $FilePath = "/" . rawurlencode($r_fs->name()) . $FilePath;

            $r_fs = DBExec("SELECT * FROM fs WHERE sysnum = " . $r_fs->up(), __LINE__);
        }

        $HtmlPath = ">&nbsp;" . "<a href='javascript:ChangeDir(\"$INET_SRC" . "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE\")' class='toolsbara'><span title=\"" . htmlspecialchars($TEMPL[home_name]) . "\">" . htmlspecialchars($TEMPL[home_name]) . "</span></a>" . $HtmlPath;
        //$FilePath = "/" . rawurlencode($TEMPL[home_name]) . $FilePath;
        $FilePath = "/My_Files" . $FilePath;
    }


    function PutFilesTable() // @METAGS PutFilesTable
    {
        global $FS;
        global $s_FileFolder;
        global $SCRIPT_NAME, $INET_IMG, $FACE, $TEMPL;
        global $s_FileFolder;
        global $NPage, $MaxNumPage, $LinePerScreen;

        $TagFileCount = 0;
        $s_FileFolder[DisplayFile] = array();

        $FS = (int)$FS;
        //ShGlobals();


       # $this->SubTable("border='0' cellspacing='0' cellpadding='0' width='100%' class='toolsbarl' "); {
       #     $this->SubTable("border='0' cellspacing='0' cellpadding='0'"); {
       #         $this->TRNext(); {
       #            $this->TDNext("class='toolsbarl' colspan=5 nowrap"); {
       #               $this->out("<img src='$INET_IMG/filler3x1.gif'>");
       #            }
       #         }

       #         $this->TRNext(); {
       #            $this->TDNext("class='toolsbarl' nowrap");
       #               $this->Out($this->ButtonBlank);
       #               $this->PutStepUp();
       #               $this->Out($this->ButtonBlank);

       #            $this->TDNext("class='body' width='1' nowrap");
       #               $this->out("<img src='$INET_IMG/filler1x1.gif'>");

       #            $this->TDNext("class='toolsbarl' nowrap");
       #               $this->Out($this->ButtonBlank);
       #               $this->out(makeButton("type=1& form=ScrFilesForm& name=sSharing_1&    img=$INET_IMG/filefoldersharing-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldersharing.gif?FACE=$FACE& title=$TEMPL[bt_sharing_ico]"));
       #               $this->Out($this->ButtonBlank);

       #            $this->TDNext("class='body' width='1' nowrap");
       #               $this->out("<img src='$INET_IMG/filler1x1.gif'>");

       #            $this->TDNext("class='toolsbarl' nowrap");
       #               $this->Out($this->ButtonBlank);
       #               $this->Out(makeButton("type=1& name=sCut_1& form=ScrFilesForm& img=$INET_IMG/filefoldercut-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldercut.gif?FACE=$FACE& title=$TEMPL[bt_cut_ico]& imgalign=absmiddle") . $this->ButtonBlank);
       #               $this->Out(makeButton("type=1& name=sCopy_1& form=ScrFilesForm& img=$INET_IMG/filefoldercopy-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldercopy.gif?FACE=$FACE& title=$TEMPL[bt_copy_ico]& imgalign=absmiddle") . $this->ButtonBlank);
       #               $this->Out(makeButton("type=1& name=sPaste_1& form=ScrFilesForm& img=$INET_IMG/filefolderpaste-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderpaste.gif?FACE=$FACE& title=$TEMPL[bt_paste_ico]& imgalign=absmiddle") . $this->ButtonBlank);
       #               $this->Out(makeButton("type=1& name=sDelete_1& form=ScrFilesForm& img=$INET_IMG/filefolderdelete-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderdelete.gif?FACE=$FACE& title=$TEMPL[bt_delete_ico]& imgalign=absmiddle") . $this->ButtonBlank);

       #            $this->TDNext("class='body' width='1' nowrap"); {
       #               $this->out("<img src='$INET_IMG/filler1x1.gif'>");
       #            }

       #            $this->TDNext("class='toolsbarl' nowrap"); {
       #               $this->Out($this->ButtonBlank);
       #               $this->Out("<input name='NewName' value=\"". htmlspecialchars($s_FileFolder[Status][NewName]) . "\" class='toolsbare'>" .$this->ButtonBlank);
       #               unset($s_FileFolder[Status][NewName]);
       #               $this->Out(makeButton("type=1& name=sRename_1& form=ScrFilesForm& img=$INET_IMG/filefolderrename-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderrename.gif?FACE=$FACE& title=$TEMPL[bt_rename_ico]& imgalign=absmiddle") . $this->ButtonBlank);
       #               $this->Out(makeButton("type=1& name=sNewFolder_1& form=ScrFilesForm& img=$INET_IMG/filefoldernew-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldernew.gif?FACE=$FACE& title=$TEMPL[bt_new_ico]& imgalign=absmiddle") . $this->ButtonBlank);
       #            }
       #         }

       #         $this->TRNext(); {
       #            $this->TDNext("class='toolsbarl' nowrap colspan = 20"); {
       #               $this->out("<img src='$INET_IMG/filler3x1.gif'>");
       #            }
       #         }

       #     } $this->SubTableDone();
       # } $this->SubTableDone();



        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $table = "<table cellspacing='0' cellpadding='0' border = '0' class='tab' width='100%'>\n";

        $table .= "<tr>";
        $table .= "<td class='ttp' nowrap>" .               "&nbsp;<input type='checkbox' name='TagFileAll' title='$TEMPL[select_all_ico]' onClick='javascript:onTagFileAllClick()'>&nbsp;</td>";
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
        //$table .= "<td class='ttp'>" .                    "&nbsp" . "</td>";
        //$table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
        $table .= "<td class='ttp' width='60%'>"   . "&nbsp;<b>$TEMPL[lb_file_name]</b> " . $this->PutSortsIcons("n") . "</td>";
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
        //$table .= "<td class='ttp'>" .               "<input type='image' src='$INET_IMG/properties.gif' border=0 alt='$TEMPL[remark_ico]'>" . "</td>";
        $table .= "<td class='ttp'>" .               makeButton("type=1& form=ScrFilesForm& name=sEditSharing_1& img=$INET_IMG/allproperties-passive.gif?FACE=$FACE&  imgact=$INET_IMG/allproperties.gif?FACE=$FACE& title=$TEMPL[bt_chperm_ico]");
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
        $table .= "<td class='ttp' width='30%' nowrap>"   . "&nbsp;<b>$TEMPL[lb_date]</b>&nbsp;" . $this->PutSortsIcons("t") . "</td>";
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
        $table .= "<td class='ttp' width='30%' nowrap>"  . "&nbsp;<b>$TEMPL[lb_size]</b>&nbsp;" . $this->PutSortsIcons("s") . "</td>";
        if ($this->USR->lev() >= 1) {
            $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
            $table .= "<td class='ttp' width='30%'>" . "&nbsp;<b>$TEMPL[lb_owner]</b>&nbsp;" . $this->PutSortsIcons("o") . "</td>";
        }
        $table .= "</tr>\n";


        $FilesCount = 0;
        $FilesSize  = 0;

        reset($this->DataKeys);
        for ( $i = 0; ($i < $LinePerScreen * $NPage) && current($this->DataKeys); $i ++, next($this->DataKeys)) {
            // continue;
        }

        while( ($key = current($this->DataKeys)) && ($FilesCount < $LinePerScreen) ) {
            $file =& $this->Data[Files][$key];

            $FilesCount += 1;
            $FilesSize  += $file[fsize];

            $class = "tlp";
            $class_a = "tlpa";
            if ($file[Clip][ftype] != "") {
                $class = "tla";
                $class_a = "tlaa";
            }

            $table .= "<TR><TD colspan='9'><img src='$INET_IMG/filler1x1.gif'></TD></TR>\n";

            #---------------------------------------------------------------------------
            if ($file[sign] == 0) {
                $table .= "<TR>\n";

                $table .= "<TD class='$class' nowrap>";
                $checked = ($s_FileFolder[SelectedFiles][$file[sysnum]] ? "CHECKED" : "");
                $table .= "&nbsp;<input type='checkbox' name='TagFile[" . $TagFileCount++ . "]' $checked value='" . $file[sysnum] . "' onClick='javascript:onTagFileClick()'>&nbsp;";
                $table .= "</TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
                $s_FileFolder[DisplayFile][] = $file[sysnum];

                $table .= "<TD class='$class' nowrap>";
                $table .= "<table border='0' cellspacing='0' cellpadding='0'><TD class='$class' nowrap>&nbsp;";
                $table .= "<a href='javascript:ChangeDir(\"$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&FS=$file[sysnum]\")'>";
                if(is_array($file[linkto]) && count($file[linkto]) > 0) {
                    $table .= "<img src='$INET_IMG/folder-yellow-share.gif' border=0 alt='$TEMPL[open_folder_ico]'>";
                } else {
                    $table .= "<img src='$INET_IMG/folder-yellow.gif' border=0 alt='$TEMPL[open_folder_ico]'>";
                }
                $table .= "</a>&nbsp;";
                $table .= "</TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<TD class='$class'>";
                $Name_ = $file[name];
                if ($file[rem] != "") {
                    $Name_ .= " ( " . $file[rem] ." )";
                }
                //$Name_ =  "<span title=\"" . htmlspecialchars($file[name]) . "\">" . ReformatToLeft($file[name], 35, "<br>"  . $this->TextShift) . "</span>";
                $Name_ =  "<span title=\"" . htmlspecialchars($file[name]) . "\">" . htmlspecialchars($Name_) . "</span>";
                $table .= "<a href='javascript:ChangeDir(\"$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&FS=".$file[sysnum]."\")'><font class='$class_a'>".$Name_."</font></a>";
                $table .= "</TD>\n";
                $table .= "</table></TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<td class='$class'><center>";
                //$table .= "<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&FS=".$file[up]."&sOptions=".$file[sysnum]."'>";
                //$table .= "<img src='$INET_IMG/properties.gif' border=0 alt='".($file[rem] != "" ? $file[rem] : $TEMPL[remark_ico])."'></a></center>";
                $table .= "<input  type='image' src='$INET_IMG/properties.gif' name='sOptions_$file[sysnum]' border=0 alt='".($file[rem] != "" ? $file[rem] : $TEMPL[remark_ico])."'></center>";
                $table .= "</td>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<TD class='$class' nowrap>";
                $table .= $this->TextShift . "" . mkdatetime($file[creat]);
                $table .= "</TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<TD class='$class' nowrap align='right'>";
                $table .= "&nbsp;" . $file[SubItemsCount] . "&nbsp;" . $TEMPL[cnt_entryes] . "&nbsp;";
                $table .= "</TD>\n";

                if ($this->USR->lev() >= 1) {
                    $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
                    $table .= "<TD class='$class'>";
                    $table .= $this->TextShift . $file[usrname]."@".$file[domainname];
                    $table .= "</TD>\n";
                }
                $table .= "</TR>";

            #---------------------------------------------------------------------------
            } else {
                $table .= "<tr>\n";

                $table .= "<TD class='$class' nowrap>";
                $checked = ($s_FileFolder[SelectedFiles][$file[sysnum]] ? "CHECKED" : "");
                $table .=  "&nbsp;<input type='checkbox' name='TagFile[" . $TagFileCount++ . "]' $checked value='".$file[sysnum]."' onClick='javascript:onTagFileClick()'>&nbsp;";
                $table .= "</TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
                $s_FileFolder[DisplayFile][] = $file[sysnum];

                $table .= "<TD class='$class' nowrap>";
                $table .= "<table border='0' cellspacing='0' cellpadding='0'><TD class='$class' nowrap>&nbsp;";
                $table .= "<a href='" . MakeOwnerFileDownloadURL($file[name], $file[sysnum], $this->UID, 2) . "' target='_blank'>";
                if(is_array($file[linkto]) && count($file[linkto]) > 0) {
                    $table .= "<img src='$INET_IMG/view-share.gif' border=0 alt='$TEMPL[view_file_ico]'>";
                } else {
                    $table .= "<img src='$INET_IMG/view.gif' border=0 alt='$TEMPL[view_file_ico]'>";
                }
                $table .= "</a>&nbsp;";
                $table .= "</TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<TD class='$class'>";
                $Name_ = $file[name];
                if ($file[rem] != "") {
                    $Name_ .= " ( " . $file[rem] ." )";
                }
                $table .= "<a href='" . MakeOwnerFileDownloadURL($file[name], $file[sysnum], $this->UID, 1) . "' target='_blank'>";

                $Name_ = "<span title=\"" . htmlspecialchars($file[name]) . "\">" . htmlspecialchars($Name_) . "</span>";
                $table .= "<font class='$class_a'>" . $this->nbsp($Name_) . "</font></a>" ;
                $table .= "</TD>\n";
                $table .= "</table></TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<td class='$class'><center>";
                //$table .= "<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&FS=".$file[up]."&sOptions=" .$file[sysnum]. "'>";
                //$table .= "<img src='$INET_IMG/properties.gif' border=0 alt='".($file[rem] != "" ? $file[rem] : $TEMPL[remark_ico])."'></a></center>";
                $table .= "<input  type='image' src='$INET_IMG/properties.gif' name='sOptions_$file[sysnum]' border=0 alt='".($file[rem] != "" ? $file[rem] : $TEMPL[remark_ico])."'></center>";
                $table .= "</td>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<TD class='$class' nowrap>";
                $table .= $this->TextShift . "" . mkdatetime($file[creat]);
                $table .= "</TD>\n";
                $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

                $table .= "<TD class='$class' align='right' nowrap>";
                $k = $file[fsize];
                $k1 = AsSize($k);
                $table .= $this->TextShift . "<span title = '$k bytes'>" . $this->nbsp($k1) . $this->TextShift . "</span>";
                $table .= "</TD>\n";

                if ($this->USR->lev() >= 1) {
                    $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
                    $table .= "<TD class='$class'>";
                    $table .= $this->TextShift . $file[usrname]."@".$file[domainname];
                    $table .= "</TD>\n";
                }
                $table .= "</TR>\n";
            }

            next($this->DataKeys);
        } // FOR for all files

        $table .= "<TR><TD colspan='9'><img src='$INET_IMG/filler1x1.gif'></TD></TR>\n";
        $table .= "<TR>\n";

        $class = 'tlp';
        $table .= "<TD class='$class'>";
        $table .= "&nbsp";
        $table .= "</TD>\n";
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

        // $table .= "<TD class='$class'>";
        // $table .= "&nbsp";
        // $table .= "</TD>\n";
        // $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

        $table .= "<TD class='$class'>";
        $table .= "&nbsp;<b>$FilesCount $TEMPL[cnt_files]</b>&nbsp;";
        if ($MaxNumPage > 0) {
            $table .= "<b>$TEMPL[cnt_all_files] " . count($this->DataKeys) . "</b>&nbsp;";
        }
        $table .= "</TD>\n";
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

        $table .= "<TD class='$class' align='right'>";
        $table .= "<b>&nbsp;</b>";
        $table .= "</TD>\n";
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

        $table .= "<TD class='$class' align='right'>";
        $table .= "&nbsp;<b>$TEMPL[cnt_size]</b>&nbsp;";
        $table .= "</TD>\n";
        $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";

        $table .= "<TD class='$class' align='right' nowrap>";
        $k = $FilesSize;
        $k1 = AsSize($k);
        $table .= "&nbsp;<span title='$k bytes'><b>$k1</b></span>&nbsp;";
        $table .= "</TD>\n";

        if ($this->USR->lev() >= 1) {
            $table .= "<TD width='1px'><img src='$INET_IMG/filler1x1.gif'></TD>";
            $table .= "<TD class='$class'>";
            $table .= "&nbsp;";
            $table .= "</TD>\n";
        }

        $table .= "</TR>\n";

        $table .= "</table>";

        $this->out("$table");

        //$this->out(sharr($this->Data));


        $this->out("<script language='javascript'>");
        $this->out("onTagFileClick()");
        $this->out("</script>");
        //$this->SubTableDone();
        return $FilesCount;
    }


    function PutSortsIcons($ord) // @METAGS PutSortsIcons
    {
        global $INET_IMG;
        global $s_FileFolder;
        global $REQUEST_URI, $SCRIPT_NAME;
        global $HTTP_GET_VARS, $FACE;

        $a = $HTTP_GET_VARS;
        $a[FACE] = $FACE;

        $rez = "";

        if ($ord != $s_FileFolder[Sort]) {
            $rez .= "<input type='image' name = 'sSortSet_$ord' src='$INET_IMG/sort1.gif' alt='' border='0'>";
        }

        if (strtoupper ($ord) != $s_FileFolder[Sort]) {
            $rez .= "<input type='image' name = 'sSortSet_" . strtoupper ($ord) . "' src='$INET_IMG/sort2.gif' alt='' border='0'>";
        }

        return $rez;
    }

    function SetData() // @METAGS SetData
    {
        global $FS, $File, $R_FS, $s_FileFolder;
        global $NPage, $MaxNumPage, $LinePerScreen;

        $LinePerScreen = 15;

        $r_usr_ua = DBExec("SELECT * FROM usr_ua WHERE name = 'perscreencount_myftp' AND sysnumusr = '{$this->UID}'", __LINE__);
        if ((int)($r_usr_ua->value()) != 0) {
            $LinePerScreen = (int)($r_usr_ua->value());
        }


        $this->Data = SetData($this->USR, $this->USRNAME, $FS, $File, &$R_FS);
        $this->DataKeys = array();

        if (is_array($this->Data[Files])) {
            uasort($this->Data[Files], "CompareFiles");

            reset($this->Data[Files]);
            while ($key = key($this->Data[Files])) {
                $file =& $this->Data[Files][$key];

                if (!$file) {
                    continue;
                }

                if (($file[FileAccDirect] == 1) || ($file[access][d] != "") || ($file[access][a] != "") || ( ($file[access][p] != "n") && ($FS != 0) ) ) {
                    $this->DataKeys[] = $key;
                }

                next($this->Data[Files]);
            }

            $MaxNumPage = (int)((count($this->DataKeys) - 1) / $LinePerScreen);
            if ($NPage > $MaxNumPage) {
                $NPage = $MaxNumPage;
            }
        }
    }

    function ScrSharing()
    {
        global $s_FileFolder, $TEMPL, $FACE;
        global $INET_IMG, $INET_SRC, $SCRIPT_NAME;

        #$s_FileFolder[ScrSharing][fTO]

        $S = "";
        reset($s_FileFolder[SelectedFiles]);
        while(list($n, $v) = each($s_FileFolder[SelectedFiles])) {
            $S .= ($S != "" ? " or " : "") . "fs.sysnum = $v";
        }
        if ($S == "") {
            $S = "fs.sysnum = -1"; // что бы не выбрать не одного - пустой курсор
        }

        $r_fs = DBExec("SELECT fs.sysnum, fs.sysnumfile, gettree(fs.sysnum) as path, fs.name from fs where owner = $this->UID and ($S)", __LINE__);

        $TagFileCount = 0;
        $s_FileFolder[DisplayFile] = array();

        $this->out("<form method='post' name='SharingForm'>");

        $this->SubTable("class='toolsbarl' width='100%' cellspacing='0' cellpadding='3'"); {
            $this->Out($this->ButtonBlank);
            $this->Out(makeButton("type=1& form=SharingForm& name=sSharingSet& img=$INET_IMG/filefoldersendperm-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldersendperm.gif?FACE=$FACE& title=$TEMPL[bt_sendperm_ico]") .$this->ButtonBlank);
            $this->Out($this->ButtonBlank);
            $this->Out(makeButton("type=1& form=SharingForm& name=sSharingCancel& img=$INET_IMG/filefoldercancel-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldercancel.gif?FACE=$FACE& title=$TEMPL[bt_cancel_ico]"));
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

        $this->SubTable("width='100%' border = 0 cellpadding=3 cellspacing=0"); {
            $this->TRNext(); {
               $this->TDNext("class='ttp' nowrap valign='top' align='left' style='text-align: left'", ""); {
                   // $this->out("<b><font color='white' size = '+1'>2.</font> Select Addresses from the list below</b><br>");
                   //$this->Out($this->ButtonBlank);
                   $this->out("<A name='step-1'></A>");
                   $this->out("<A href='#step-2'><img src='$INET_IMG/num-1.gif' align='absmiddle' border = '0' alt = '$TEMPL[next_num_ico]'></a>");
                   $this->out("&nbsp;<b>$TEMPL[lb_num_1]</b>");
               }
            }
            $this->TRNext(); {
                $this->TDNext("class='tlp'"); {
                    $this->SubTable("border = 0 cellpadding=3 cellspacing=0"); {
                        $this->TRNext(); {
                           	$this->TDNext("class='tlp' nowrap valign='top' align='left'", ""); {
                               	//$this->Out($this->ButtonBlank);
                               	$this->out(makeButton("type=2& form=SharingForm& name=sAddressView1& img=$INET_IMG/filefolderto-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderto.gif?FACE=$FACE& onclick=javascript:wSelAddresses('$INET_SRC/pr2.php?UID=$this->UID%26FACE=$FACE')&  title=$TEMPL[bt_to_ico]"));
                               	$this->Out($this->ButtonBlank);
                           	}
                           	$this->TDNext("class='tlp' nowrap valign='top' align='left'", ""); {
                            	$this->Out("<input type='text' name='fTO' size=80 value=\"" . htmlspecialchars($s_FileFolder[ScrSharing][fTO]) . "\">");
                           	}
                        }
                        $this->TRNext(); {
                           	$this->TDNext("class='tlp' nowrap valign='top' align='left'", ""); {
                               	//$this->Out($this->ButtonBlank);
                               	$this->out("$TEMPL[lb_subject] :");
                               	$this->Out($this->ButtonBlank);
                           	}
                           	$this->TDNext("class='tlp' nowrap valign='top' align='left'", ""); {
                               	$this->Out("<input type='text' name='fSubj' size=80 value=\"" . htmlspecialchars($s_FileFolder[ScrSharing][fSubj]) . "\">");
                           	}
                        }
                        $this->TRNext(); {
                        	$this->TDNext("class='tlp' nowrap valign='top' align='left' colspan=2", ""); {
                				$checked = ( !isset($s_FileFolder[ScrSharing][fSaveSentMessage]) || $s_FileFolder[ScrSharing][fSaveSentMessage] != "" ? "CHECKED" : "");
                				$this->Out("<input type='checkbox' name='fSaveSentMessage' $checked value='1'>", $this->TextShift);
                            	$this->out($TEMPL[lb_save_sent]);
							}
						}
                    } $this->SubTableDone();
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

        $this->SubTable("border='0'  width='100%' cellspacing='0' cellpadding='3'"); {
            $this->TRNext(); {
               $this->TDNext("class='ttp' nowrap valign='top' align='left' colspan=2 style='text-align: left'", ""); {
                   // $this->out("<b><font color='white' size = '+1'>3.</font> Select Permission and Expire Date for Link</b><br>");
                   $this->out("<A name='step-2'></A>");
                   $this->out("<A href='#step-3'><img src='$INET_IMG/num-2.gif' align='absmiddle' border = '0' alt = '$TEMPL[next_num_ico]'></a>");
                   $this->out("&nbsp;<b>$TEMPL[lb_num_2]</b><br>");
               }
            }
            $this->TRNext(); {
               $this->TDNext("class='tlp' nowrap valign='top' align='left'", ""); {
                   $this->out("<input type='radio'    name='fPermLev'   value = 'r' CHECKED> $TEMPL[perm_r]<br>");
                   $this->out("<input type='radio'    name='fPermLev'   value = 'w'>         $TEMPL[perm_w]<br>");
                   $this->out("<input type='radio'    name='fPermLev'   value = 'u'>         $TEMPL[perm_u]<br>");
                   $this->out("<input type='radio'    name='fPermLev'   value = 'n'>         $TEMPL[perm_n]<br><br>");
                   $this->out("<input type='checkbox' name='fTracking'   value = '1' CHECKED> $TEMPL[lb_access_tracking]<br>");
               }
               $this->TDNext("class='tlp' nowrap valign='top' align='left'"); {
                   $this->out("$TEMPL[lb_exp_date]:<br>");
                   $this->out("<SELECT name = 'fExpPeriod' class='toolsbare'>");
                           // $this->out("<option value='0'>Always</option>");
                           $this->out("<option value='0'>$TEMPL[eper_0]</option>");
                           $this->out("<option value='1'>$TEMPL[eper_1]</option>");
                           $this->out("<option value='2'>$TEMPL[eper_2]</option>");
                           $this->out("<option value='7'>$TEMPL[eper_7]</option>");
                           $this->out("<option value='14'>$TEMPL[eper_14]</option>");
                           $this->out("<option value='31'>$TEMPL[eper_31]</option>");
                           $this->out("<option value='62'>$TEMPL[eper_62]</option>");
                           $this->out("<option value='365'>$TEMPL[eper_365]</option>");
                   $this->out("</SELECT><br>");
                   $this->out("<font class='toolsbarlt'>$TEMPL[lb_date_form]</font><br><input name = 'fExpDate' size='10' value=\"" . htmlspecialchars($s_FileFolder[ScrSharing][fExpDate]) . "\" class='toolsbare'>&nbsp;");
               }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

        $this->SubTable("border='0'  width='100%' cellspacing='0' cellpadding='3'"); //
        $this->TRNext(); {
            $this->TDNext("class='ttp' nowrap valign='top' align='left' style='text-align: left'", ""); {
                // $this->out("<b><font color='white' size = '+1'>4.</font> Send Permission</b><br>");
                $this->out("<A name='step-3'></A>");
                $this->out("<A href='#step-4'><img src='$INET_IMG/num-3.gif' align='absmiddle' border=0></a>");
                $this->out("&nbsp;<b>$TEMPL[lb_num_3]</b><br>");
            }
        }
        $this->TRNext(); {
            $this->TDNext("class='tlp' nowrap valign='top' align='left'", ""); {
                $checked = ( $s_FileFolder[ScrSharing][fSharingStandartMessage] != "2" ? "CHECKED" : "");
        		$this->out("<input type='radio' name='fSharingStandartMessage' value=1 $checked>");
				$this->out("&nbsp;", $TEMPL[lb_send_message_add], "<br>");

				$checked = ( $checked == "" ? "CHECKED" : "");
        		$this->out("<input type='radio' name='fSharingStandartMessage' value=2 $checked>");
				$this->out("&nbsp;", $TEMPL[lb_send_message_override], "<br>");

        		$this->out("<script language='javascript' src='$INET_SRC/htmlarea/init_code.js'></script>\n");
                $this->out("<TEXTAREA name = 'fSharingMemo' cols='80' rows='10' class='toolsbare'>" . htmlspecialchars($s_FileFolder[ScrSharing][fSharingMemo]) . "</TEXTAREA>");
        		$this->out("<script language=\"javascript1.2\">editor_generate('fSharingMemo');</script>");
            }
        }
        $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

        $this->SubTable("border='0'  width='100%' cellspacing='0' cellpadding='3'"); //
        $this->TRNext(); {
            $this->TDNext("class='ttp' nowrap valign='top' align='left' style='text-align: left'", ""); {
                // $this->out("<b><font color='white' size = '+1'>4.</font> Send Permission</b><br>");
                //$this->out("<A name='step-3'></A>");
                $this->out("<A href='#step-4'><img src='$INET_IMG/num-4.gif' align='absmiddle' border=0></a>");
                $this->out("&nbsp;<b>$TEMPL[lb_num_4]</b><br>");
            }
        }
        $this->TRNext(); {
            $this->TDNext("class='tlp' nowrap valign='top' align='left' style='text-align: left'", ""); {
                $this->SubTable("class='tab' width=100% border='0' cellspacing='0' cellpadding='0' grborder");
                while(!$r_fs->eof()) {
                       $this->TRNext(); {
                           $this->TDNext("width=30 class='tlp' nowrap valign='top' align='left' style='text-align: left'", ""); {
                               $checked = ($s_FileFolder[SelectedFiles][$r_fs->sysnum()] ? "CHECKED" : "");
                               $this->out("&nbsp;<input type='checkbox' name='TagFile[" . $TagFileCount++ . "]' $checked value='" . $r_fs->sysnum() . "'>&nbsp;");
                               $s_FileFolder[DisplayFile][] = $r_fs->sysnum();
                           }
                           $this->TDNext("class='tlp' nowrap valign='top' align='left' style='text-align: left'", ""); {
                               $this->SubTable("class='tlp' border=0 cellspacing='0' cellpadding='0'");
                                 $this->TDNext("class='tlp' ");
                                     if($r_fs->sysnumfile() == 0) {
                                        $this->out($this->TextShift . "<img src='$INET_IMG/folder-yellow.gif' border=0>");
                                     } else {
                                        $this->out($this->TextShift . "<a href='" . MakeOwnerFileDownloadURL($r_fs->name(), $r_fs->sysnum(), $this->UID, 2) . "' target='_blank'>");
                                        $this->out("<img src='$INET_IMG/view.gif' border=0 alt='$TEMPL[view_file_ico]'>");
                                        $this->out("</a>");
                                     }
                                 $this->TDNext("class='tlp' ");
                                     $Name_ = $r_fs->path();
                                     $Name_ = "<span title=\"" . htmlspecialchars($Name_) . "\">" . ReformatToLeft($Name_, 100, "<br>" . $this->TextShift) . "</span>";
                                     if($r_fs->sysnumfile() == 0) {
                                       $this->out($this->TextShift . "<font class='tlp'><b>" . $this->nbsp($Name_) . "</b></font>" . $this->TextShift);
                                     } else {
                                       $this->out($this->TextShift . "<a href='" . MakeOwnerFileDownloadURL($r_fs->name(), $r_fs->sysnum(), $this->UID, 2) . "' target='_blank'>");
                                       $this->out("<font class='tlpa'><b>" . $this->nbsp($Name_) . "</b></font></a>" . $this->TextShift);
                                     }
                               $this->SubTableDone();
                           }
                       }
                       $r_fs->next();
                }
                if ($r_fs->NumRows() == 0) {
                  $this->TDNext("align='center'", ""); {
                    $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0"); {
                        $this->tds(0, 0, "width='250' height='70' align='center'", "<font size='+2'>$TEMPL[lb_empty]</font>");
                    } $this->SubTableDone();
                  }
                }
                $this->SubTableDone();
            }
        }
        $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

        $this->SubTable("class='toolsbarl' width='100%' cellspacing='0' cellpadding='3'");
            $this->Out($this->ButtonBlank);
            $this->Out(makeButton("type=1& form=SharingForm& name=sSharingSet_down& img=$INET_IMG/filefoldersendperm-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldersendperm.gif?FACE=$FACE& title=$TEMPL[bt_sendperm_ico]") .$this->ButtonBlank);
            $this->Out($this->ButtonBlank);
            $this->Out(makeButton("type=1& form=SharingForm& name=sSharingCancel_down& img=$INET_IMG/filefoldercancel-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldercancel.gif?FACE=$FACE& title=$TEMPL[bt_cancel_ico]"));
        $this->SubTableDone();


        $this->out("</form>");
    }


    function ScrEditSharing()
    {
        global $s_FileFolder;
        global $TEMPL, $INET_IMG, $FACE;

        $res = DBFind("fs, acc", "fs.sysnum = acc.sysnumfs and fs.owner='$this->UID' order by fs.name, fs.sysnum, acc.username", "fs.sysnum, fs.up, fs.name, acc.access, acc.username, acc.expdate, acc.access_tracking", __LINE__);


        // $this->ShResult($res1);
        // $this->ShResult($res2);

        $this->OUT("<Form method='Post' name='ChangePerm'>");

        if(is_array($s_FileFolder[SelectedFiles]) && count($s_FileFolder[SelectedFiles]) > 0) {
            reset($s_FileFolder[SelectedFiles]);
            while(list($n, $v) = each($s_FileFolder[SelectedFiles])) {
                $this->out("<input type='hidden' name='TagFile[]' value='$v'>");
            }
        }

        $this->SubTable("border=0 width='100%' cellpadding='3'");

        $this->TRNext("class = 'toolsbarl'"); {
            $this->TDNext("width='100%'"); {
                $this->Out(makeButton("type=1& form=ChangePerm& name=sEditSharingSet& img=$INET_IMG/filefoldersaveperm-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldersaveperm.gif?FACE=$FACE& title=$TEMPL[bt_saveperm_ico]") .$this->ButtonBlank);
                $this->Out(makeButton("type=1& form=ChangePerm& name=sEditSharingCancel& img=$INET_IMG/filefoldercancel-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefoldercancel.gif?FACE=$FACE& title=$TEMPL[bt_cancel_ico]") ."<br>");
            }
        }
        $this->SubTableDone();

        $this->SubTable("border=0 width='100%'");

        $this->TRNext("class = 'ttp'"); {
            $this->TDNext("width='40%'"); {
                $this->OUT("$TEMPL[cp_lb_name]");
            }
            $this->TDNext("width='30%'"); {
                $this->OUT("$TEMPL[cp_lb_file]");
            }
            $this->TDNext("width='15%'"); {
                $this->OUT("$TEMPL[cp_lb_acc]");
            }
            $this->TDNext("width='5%'"); {
                $this->OUT("$TEMPL[cp_lb_access_tracking]");
            }
            $this->TDNext("width='10%'"); {
                $this->OUT("$TEMPL[cp_lb_edate]");
            }
        }

        $CurrDate = mktime(0,0,0, date("m")  ,date("d"), date("Y"));

        while(!$res->Eof()) {
            $class = "tlp";
            $fExpDate = MkSpecTime($res->expdate());
            if ($fExpDate != "" && $CurrDate > $fExpDate) {
              $class = "tla";
            }

            $this->TRNext("class='$class'"); {
                $this->TDNext("class='$class' align='left'"); {
                    $this->OUT($res->username());
                }

                // Get Full path to file or direcory
                $this->TDNext("class='$class' align='left'"); {
                    $path = "";
                    $r_fs = DBFind("fs", "sysnum = " . $res->up(), "up, name", __LINE__);
                    while ($r_fs->NumRows() != 0) {
                        $path = $r_fs->name() . "/" . $path;
                        $r_fs = DBFind("fs", "sysnum = " . $r_fs->up(), "up, name", __LINE__);
                    }
                    $path = "/" . $path;
                    $this->OUT($path . $res->name());
                }

                $this->TDNext("class='$class' align='left'"); {
                    if ($res->access() != "w" && $res->access() != "r") {
                        $Disable = "Disabled";
                    } else {
                        $Disable = "";
                    }
                    $this->out("<input type='radio' name='fPermLev[".$res->sysnum()." ".$res->username()."]'  value = 'r' " . (($res->access() == "r") ? "CHECKED" : "") . " $Disable >ro");
                    $this->out("<input type='radio' name='fPermLev[".$res->sysnum()." ".$res->username()."]'  value = 'w' " . (($res->access() == "w") ? "CHECKED" : "") . " $Disable >r/w");
                    if ($res->access() != "u") {
                        $Disable = "Disabled";
                    } else {
                        $Disable = "";
                    }
                    $this->out("<input type='radio' name='fPermLev[".$res->sysnum()." ".$res->username()."]'  value = 'u' " . (($res->access() == "u") ? "CHECKED" : "") . " $Disable >wo");
                    $this->out("<input type='radio' name='fPermLev[".$res->sysnum()." ".$res->username()."]'  value = 'n' " . (($res->access() == "n") ? "CHECKED" : "") . " >n/v");
                    $this->out("<input type='radio' name='fPermLev[".$res->sysnum()." ".$res->username()."]'  value = 'e'><i>er</i>");
                }

                $this->TDNext("class='$class' align='center'"); {
                    $this->OUT("<input type='checkbox' name='fTracking[".$res->sysnum()." ".$res->username()."]' value='1' " . (($res->access_tracking() == "1") ? "CHECKED" : "") . " >");
                }

                $this->TDNext("class='$class' align='left'"); {
                    $dat = $fExpDate == 0 ? "" : date("m/d/Y", $fExpDate);
                    $this->OUT("<input name='PermDate[".$res->sysnum()." ".$res->username()."]' value=\"$dat\" size='10'>");
                }
            }
            $res->Next();
        }

        $this->SubTableDone();

        $this->OUT("<b><font size='+1'>$TEMPL[cp_lb_abbrev]:</font></b><br>");
        $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_r] . "<br>");
        $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_w] . "<br>");
        $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_u] . "<br>");
        $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_n] . "<br>");
        $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_e] . "<br>");

        $this->OUT("</Form>");
    }


    function ScrOptions()
    {
        global $s_FileFolder, $sOptions, $Mes, $Description, $View;
        global $TEMPL, $INET_IMG, $INET_SRC, $INET_ROOT, $FACE, $FTP_HOST, $FTP_PORT;


        $file =& $this->Data[Files][$s_FileFolder[OptionsFile]];

        if(!$file) {
            $View = "Files";
            $this->Scr();
            return;
        }

        //echo ShArr($this->Data, "");

        $Value = $file[rem];
        if (isset($s_FileFolder[Status][Description])) {
            $Value = $s_FileFolder[Status][Description];
        }

        $this->out("<form method='post' name='OptionsForm'>");

        if(is_array($s_FileFolder[SelectedFiles]) && count($s_FileFolder[SelectedFiles]) > 0) {
            reset($s_FileFolder[SelectedFiles]);
            while(list($n, $v) = each($s_FileFolder[SelectedFiles])) {
                $this->out("<input type='hidden' name='TagFile[]' value='$v'>");
            }
        }

        $this->SubTable("border = 0 width='100%' cellpadding='0' cellspacing = '0' class='toolsbarl'"); {
            $this->SubTable("border = 0 cellpadding = '0' cellspacing = '0' grborder class = 'tab'"); {
                $this->TRNext(""); {
                    $this->TDNext("class='toolsbarl'"); {
                        $this->SubTable("border = 0 cellpadding = '5' cellspacing = '0'"); {
                            $this->Out(makeButton("type=1&  name=sOptionsSet& value=$TEMPL[opt_submit]& class=toolsbarb") .$this->ButtonBlank);
                            $this->Out(makeButton("type=1&  name=sOptionsCancel& value=$TEMPL[opt_exit]& class=toolsbarb"));
                        } $this->SubTableDone();
                    }

                }
            } $this->SubTableDone();
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("border = '0' cellpadding='0' cellspacing = '0' grborder"); {
            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                     $this->Out($this->TextShift, $TEMPL[opt_description], $this->TextShift);
                }
                $this->TDNext("class='tlp'"); {
                     $this->out($this->TextShift, "<input name='Description' value=\"".htmlspecialchars($Value)."\" size=60 class='toolsbare'>", $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap");
                     $this->Out($this->TextShift, $TEMPL[opt_content_type], $this->TextShift);
                $this->TDNext("class='tlp'"); {
                     if ($file[sysnumfile]) {
                       $this->Out($this->TextShift, $file[cont], $this->TextShift);
                     } else {
                       $this->Out($this->TextShift, $TEMPL[opt_folder], $this->TextShift);
                     }
                }
            }

            if ($file[sysnumfile]) {
                $this->TRNext(""); {
                    $this->TDNext("class='tlp' nowrap"); {
                        $this->Out($this->TextShift, $TEMPL[opt_links_count], $this->TextShift);
                    }
                    $this->TDNext("class='tlp'"); {
                        $r = DBFind("fs", "sysnumfile = " . $file[sysnumfile], "count(*) as count", __LINE__);
                        $this->Out($this->TextShift, $r->count(), $this->TextShift);
                    }
                }
                $this->TRNext(""); {
                    $this->TDNext("class='tlp' nowrap"); {
                       $this->Out($this->TextShift, $TEMPL[opt_file_size], $this->TextShift);
                    }
                    $this->TDNext("class='tlp'"); {
                       $this->Out($this->TextShift, "<span title='$file[fsize]'>", AsSize($file[fsize]), "</span>", $this->TextShift);
                    }
                }
            } else {
                $this->TRNext(""); {
                    $this->TDNext("class='tlp' nowrap"); {
                        $this->Out($this->TextShift, $TEMPL[opt_folder_size], $this->TextShift);
                    }
                    $this->TDNext("class='tlp'"); {
                        $TMP = FoldersSize($file[sysnum]);
                        $countFiles   += $TMP[0];
                        $countFolders += $TMP[1];
                        $sizeFiles    += $TMP[2];

                        $this->Out($this->TextShift, "$TEMPL[opt_tl_countFolder] :", $countFolders, " $TEMPL[opt_tl_countFile] :", $countFiles, " $TEMPL[opt_tl_file_size] :", "<span title='$sizeFiles'>", AsSize($sizeFiles), "</span>", $this->TextShift);
                    }
                }
            }

            // список перпишинов
            $this->TRNext(""); {
                $this->TDNext("class='tlp' valign='top' nowrap"); {
                    $this->Out($this->TextShift, $TEMPL[opt_share], $this->TextShift);
                }
                $this->TDNext("class='tlp' nowrap"); {
                    if(is_array($file[linkto]) && count($file[linkto]) > 0) {
                        $this->SubTable("border = '0' cellpadding='0' cellspacing = '0' class='tab' width = '100%' grborder"); {
                            $this->TRNext(""); {
                                 $this->TDNext("class='ttp'"); {
                                    $this->Out("&nbsp;", $TEMPL[opt_user_name], "&nbsp;");
                                 }
                                 $this->TDNext("class='ttp'"); {
                                    $this->Out("&nbsp;", $TEMPL[opt_perm_type], "&nbsp;");
                                 }
                                 $this->TDNext("class='ttp'"); {
                                    $this->Out("&nbsp;", $TEMPL[opt_expr_date], "&nbsp;");
                                 }
                                 $this->TDNext("class='ttp'"); {
                                    $this->Out("&nbsp;", $TEMPL[cp_lb_access_tracking], "&nbsp;");
                                 }
                                 $this->TDNext("class='ttp'"); {
                                    $this->Out("&nbsp;", $TEMPL[opt_tl_httplink], "&nbsp;");
                                 }
                                 $this->TDNext("class='ttp'"); {
                                    $this->Out("&nbsp;", $TEMPL[opt_tl_ftplink], "&nbsp;");
                                 }
                            }

                            $CurrDate = mktime(0,0,0, date("m")  ,date("d"), date("Y"));
                            reset($file[linkto]);
                            while(list($access_index, $access_list) = each($file[linkto])) {
                                $class = "tlp";
                                $class_a = "tlpa";
                                $fExpDate = MkSpecTime($access_list[expdate]);
                                if ($fExpDate != "" && $CurrDate > $fExpDate) {
                                    $class = "tla";
                                    $class_a = "tlaa";
                                }

                                $this->TRNext(""); {
                                    $this->TDNext("class='$class' nowrap"); {
                                        //$link = "<a href='$INET_ROOT/ra.php?HASH=" . $access_list[hash] . "' target='_blank' class='$class_a'>";
                                        //$this->Out($this->TextShift, $link, "<span class='$class_a'>" . htmlspecialchars($access_list[username]) . "</span>", "</a>", $this->TextShift);
                                        $this->Out($this->TextShift, htmlspecialchars($access_list[username]), $this->TextShift);
                                    }

                                    $this->TDNext("class='$class' nowrap"); {
                                        $this->out("&nbsp;");
                                        if ($access_list[access] != "w" && $access_list[access] != "r") {
                                            $Disable = "Disabled";
                                        } else {
                                            $Disable = "";
                                        }
                                        $this->out("<input type='radio' name='fPermLev[".$file[sysnum]." ".$access_list[username]."]'  value = 'r' " . (($access_list[access] == "r") ? "CHECKED" : "") . " $Disable >ro");
                                        $this->out("<input type='radio' name='fPermLev[".$file[sysnum]." ".$access_list[username]."]'  value = 'w' " . (($access_list[access] == "w") ? "CHECKED" : "") . " $Disable >r/w");
                                        if ($access_list[access] != "u") {
                                            $Disable = "Disabled";
                                        } else {
                                            $Disable = "";
                                        }
                                        $this->out("<input type='radio' name='fPermLev[".$file[sysnum]." ".$access_list[username]."]'  value = 'u' " . (($access_list[access] == "u") ? "CHECKED" : "") . " $Disable >wo");
                                        $this->out("<input type='radio' name='fPermLev[".$file[sysnum]." ".$access_list[username]."]'  value = 'n' " . (($access_list[access] == "n") ? "CHECKED" : "") . " >n/v");
                                        $this->out("<input type='radio' name='fPermLev[".$file[sysnum]." ".$access_list[username]."]'  value = 'e'><i>er</i>");
                                        $this->out("&nbsp;&nbsp;&nbsp;");
                                    }

                                    $this->TDNext("class='$class' align='center' nowrap"); {
                                        $dat = $fExpDate == 0 ? "" : date("m/d/Y", $fExpDate);
                                        $this->OUT($this->TextShift, "<input name='PermDate[".$file[sysnum]." ".$access_list[username]."]' value=\"$dat\" size='10' class='toolsbare'>", $this->TextShift);
                                    }

                                    $this->TDNext("class='$class' align='center' nowrap"); {
                                        $this->out("<input type='checkbox' name='fTracking[" . $file[sysnum] . " " . $access_list[username] . "]'  value = '1' " .(($access_list[access_tracking] == "1") ? "CHECKED" : "") . ">");
                                    }

                                    $this->TDNext("class='$class' nowrap"); {
                                        $link = "$INET_ROOT/ra.php/" . URLEncode($file[name]) . "?HASH=" . $access_list[hash];
                                        $this->Out($this->TextShift, "<a href='javascript:OpenPrompt(\"HTTP URL\", \"$link\")'><span class='$class_a'>$TEMPL[opt_httplink]</span></a>", $this->TextShift);
                                    }

                                    $this->TDNext("class='$class' nowrap"); {
                                        $link = "ftp://" . htmlspecialchars( ereg_replace("\@", "\$", $access_list[username]) ) . ":" . AuthorizeKey($access_list[username]) . "@$FTP_HOST:$FTP_PORT/";
                                        $r_usr = DBExec("SELECT * from usr, domain where usr.sysnumdomain = domain.sysnum and usr.name || '@' || domain.name = '{$access_list[username]}'", __LINE__);
                                        if ($r_usr->NumRows() == 1) {
                                            $link .= "Friends_FTP/";
                                        }
                                        $link .= htmlspecialchars($this->USRNAME) . "/";
                                        $this->Out($this->TextShift, "<a href='javascript:OpenPrompt(\"HTTP URL\", \"$link\")'><span class='$class_a'>$TEMPL[opt_ftplink]</span></a>", $this->TextShift);
                                    }
                                }
                            }


                            $this->TRNext(""); {
                                $this->TDNext("class='tlp' valign='top'"); {
                                    $this->Out("&nbsp;");
                                }
                                $this->TDNext("class='tlp' valign='top' nowrap"); {
                                    $this->OUT($this->TextShift    . "<b><font size='+1'>$TEMPL[cp_lb_abbrev]:</font></b>" . $this->TextShift . "<br>");
                                    $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_r] . $this->TextShift . "<br>");
                                    $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_w] . $this->TextShift . "<br>");
                                    $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_u] . $this->TextShift . "<br>");
                                    $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_n] . $this->TextShift . "<br>");
                                    $this->OUT($this->SectionBlank . $TEMPL[cp_lb_perm_e] . $this->TextShift . "<br>");
                                }
                                $this->TDNext("class='tlp' valign='top' nowrap"); {
                                    $this->Out($this->TextShift . "<b>$TEMPL[lb_date_form]</b>" . $this->TextShift);
                                }
                                $this->TDNext("class='tlp' valign='top'"); {
                                    $this->Out("&nbsp");
                                }
                                $this->TDNext("class='tlp' valign='top'"); {
                                    $this->Out("&nbsp");
                                }
                                $this->TDNext("class='tlp' valign='top'"); {
                                    $this->Out("&nbsp");
                                }
                            }
                        } $this->SubTableDone();
                    } else {
                        $this->Out($this->TextShift, $TEMPL[opt_not_shared], $this->TextShift);
                    }
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler2x1.gif'><hr><img src='$INET_IMG/filler2x1.gif'>");

        # Quick Sharing
        $this->SubTable("border = '0' cellpadding='0' cellspacing = '0' grborder"); {
            $this->TRNext(""); {
                $this->TDNext("class='body' colspan=3"); {
                    $this->SubTable("width = '100%' border = '0' cellpadding = '5' cellspacing = '0'"); {
                        $this->TDNext("class='ttp'"); {
                            $this->Out($TEMPL[opt_tl_quick_sharing]);
                        }
                    } $this->SubTableDone();

                    $this->SubTable("width = '100%' border = '0' cellpadding = '5' cellspacing = '0'"); {
                        $this->TRNext(); {
                            $this->TDNext("class='tlp'"); {
                                $this->Out($this->ButtonBlank);
                                $this->out("<input type='radio' class='tlp' name='fQuickTakeTo' value = 'u' CHECKED>", " ", $TEMPL[opt_tl_take_to_user]);
                            }
                        }
                        $this->TRNext(); {
                            $this->TDNext("class='tlp'"); {
                                $this->Out($this->ButtonBlank);
                                $this->out(makeButton("type=2& form=OptionsForm& name=sQuickPermAddressView& img=$INET_IMG/filefolderto-passive.gif?FACE=$FACE& imgact=$INET_IMG/filefolderto.gif?FACE=$FACE& onclick=javascript:wSelAddresses('$INET_SRC/pr2.php?UID=$this->UID%26FACE=$FACE%26Field=QUICKSHARE')&  title=$TEMPL[bt_to_ico]"));
                                $this->Out($this->ButtonBlank);
                                $this->Out("<input class='toolsbare' name='fQuickPermName' size='50' value=\"" . htmlspecialchars($s_FileFolder[Status][fQuickPermName]) . "\">");
                            }
                        }
                    } $this->SubTableDone();

                    $this->out("<img src='$INET_IMG/filler1x1.gif'>");

                    $this->SubTable("width = '100%' border = '0' cellpadding = '5' cellspacing = '0'"); {
                        $this->TRNext(); {
                            $this->TDNext("class='tlp'"); {
                                $this->Out($this->ButtonBlank);
                                $this->out("<input type='radio' class='tlp' name='fQuickTakeTo'  value = 'a'>", " ", $TEMPL[opt_tl_take_to_guest]);
                            }
                        }
                    } $this->SubTableDone();

                    $this->out("<img src='$INET_IMG/filler1x1.gif'>");

                    $this->SubTable("width = '100%' border = '0' cellpadding = '5' cellspacing = '0'"); {
                        $this->TRNext(); {
                            $this->TDNext("class='tlp'"); {
                                $this->Out($TEMPL[opt_tl_select_perm], $this->ButtonBlank);
                                $this->Out("<input type='radio' class='tlp' name='fQuickPermLev'  value = 'r' CHECKED> ro  ");
                                $this->Out("<input type='radio' class='tlp' name='fQuickPermLev'  value = 'w'>         r/w ");
                                $this->Out("<input type='radio' class='tlp' name='fQuickPermLev'  value = 'u'>         wo  ");
                                $this->Out("<input type='radio' class='tlp' name='fQuickPermLev'  value = 'n'>         n/v ");
                                $this->Out($this->ButtonBlank);
                            }
                        }
                        $this->TRNext(); {
                            $this->TDNext("class='tlp'"); {
                                $this->Out($TEMPL[opt_tl_access_tracking], $this->ButtonBlank);
                                $this->Out("<input type='checkbox' class='tlp' name='fQuickAccTracking'  value = '1' CHECKED>");
                            }
                        }
                    } $this->SubTableDone();

                    $this->out("<img src='$INET_IMG/filler1x1.gif'>");

                    $this->SubTable("width = '100%' border = '0' cellpadding = '5' cellspacing = '0'"); {
                        $this->TRNext(); {
                            $this->TDNext("class='tlp'"); {
                                $this->Out(makeButton("type=1&  name=sQuickSharing& value=$TEMPL[bt_quicksharing]& class=toolsbarb"));
                            }
                        }
                    } $this->SubTableDone();
                }
            }
        } $this->SubTableDone();

        //$this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->out("</form>");
    }

    function ScrPostMes() // @METAGS ScrPostMes
    {
          global $s_FileFolder, $SendMessageList, $View;

          $this->out("<form method='post'>");

          $this->OUT("<input type='hidden' name='View' value='$View'>");
          $this->out("<font size='+2'>You have sent permission(s) to:</font><br>\n$SendMessageList<br>");
          $this->OUT("<input type='submit' name='sChangeDir' value='Done'>");

          $this->out("</form>");
    }


    function rNewView()
    {
        global $s_FileFolder, $View;

        $this->ClearSession();

        $View = "";
        $this->refreshScreen();
    }


    function rChangeDir()
    {
        $this->refreshScreen();
    }


    function rNewFolder() // @METAGS NewFolder
    {
        global  $NewName, $Mes, $FS, $R_FS, $TEMPL;
        global $s_FileFolder;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        $owner = 0;
        if ($FS != 0) {
            $owner = $R_FS->owner();
        } else {
            $owner = $this->UID;
        }

        // $NewName = eregi_replace("[\"\'\@\+]", "_", $NewName);
        if (eregi("[\";`]", $NewName)) {
            $s_FileFolder[Mes] = 9;
            $this->refreshScreen();
        }

        $NewName = preg_replace("/'/", "''", $NewName);

        if ($NewName == "") {
            $NewName = $this->GetPasteName($FS, $TEMPL[cnt_newfoldername], $owner);
        }

        $r_fld = DBFind("FS", "ftype = 'f' and name = '$NewName' and up = $FS and owner = $owner", "", __LINE__);
        if ($r_fld->NumRows() != 0) {
            $s_FileFolder[Mes] = 1;
            $this->refreshScreen();
        }

        DBExec("insert into fs (sysnum, name, ftype, up,  sysnumfile, owner, creat) values (NextVal('fs_seq'), '$NewName', 'f',   $FS, 0, '$owner', 'now'::abstime)", __LINE__);

        $s_FileFolder[Status] = array();
        $s_FileFolder[Status] = array();
        $this->refreshScreen();
    } // rNewFolder()


    function rRename()
    {
        global $FS, $TagFile, $NewName, $s_FileFolder;
        global $Mes;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }


        if (!is_array($TagFile)) {
            $s_FileFolder[Mes] = 11;
            $this->refreshScreen();
        }

        if (count($TagFile) != 1) {
            $s_FileFolder[Mes] = 8;
            $this->refreshScreen();
        }


        if ($NewName == "") {
          $this->refreshScreen();
        }

        if (eregi("[\";`]", $NewName)) {
            $s_FileFolder[Mes] = 9;
            $this->refreshScreen();
        }

        $NewName = preg_replace("/'/", "''", $NewName);

        $r_fld = DBFind("FS", "ftype = 'f' and name = '$NewName' and up = $FS and owner = '$this->UID'", "", __LINE__);
        if ($r_fld->NumRows() != 0) {
            $s_FileFolder[Mes] = 1;
            $this->refreshScreen();
        }

        reset($TagFile);
        list($n, $TargetNumber) = each($TagFile);

        if (!$this->Data[Files][$TargetNumber]) {
            $this->refreshScreen();
        }

        $SQL_STRING = "UPDATE fs set name = '$NewName' where sysnum = " . $TargetNumber;
        // echo $s;

        DBExec($SQL_STRING, __LINE__);

        $s_FileFolder[SelectedFiles] = array();
        $s_FileFolder[Status] = array();

        $this->refreshScreen();
    }


    function rCopyFile($b)
    {
        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        DBExec("delete from clip where owner = '$this->USRNAME'", __LINE__);

        $r = $this->Data[Files];
        _reset($r);
        while (list($n, $v) = _each($r)) {
            unset($this->Data[Files][$n][Clip]);
        }

        $this->rCopyAddFile($b);
        //$this->refreshScreen();
    }


    function rCopyAddFile($b) // @METAGS CopyAddFile
    {
        global $s_FileFolder, $TagFile;
        global $Mes;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if (!is_array($TagFile) || count($TagFile) == 0) {
            $s_FileFolder[Mes] = 11;
            $this->refreshScreen();
        }

        reset($TagFile);
        while (list($n, $v) = each($TagFile)) {
            $file = $this->Data[Files][$v];

            //if ($file[sign] == 0) {
            //    $s_FileFolder[Mes] = 7;
            //    continue;
            //}

            if ($file[FileRO] && $b == "r") {
                $s_FileFolder[Mes] = 6;
                continue;
            }

            $this->Data[Files][$v][Clip] = $b;
            DBExec("insert into clip (sysnumfs, owner, ftype) values ('$v', '$this->USRNAME', '$b')", __LINE__);
        }

        $s_FileFolder[SelectedFiles] = array();
        $this->refreshScreen();
    } // rCopyAddFile($b)


    function rPaste()
    {
        global $FS, $R_FS, $Mes, $s_FileFolder;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if ($FS == 0) {
            $Owner = $this->UID;
        } else {
            $Owner = $R_FS->owner();
        }

        // -----------------------------------------------
        // Scaning selected list

        $InsertList = array();
        $this->rPaste_FilesLoop(0, $InsertList, NULL);

        if (count($InsertList) == 0) {
            $s_FileFolder[SelectedFiles] = array();
            $this->refreshScreen();
        }


        reset($InsertList);
        foreach($InsertList as $Item) {
            if ($Item[sysnum] == $FS) {
                $s_FileFolder[Mes] = 20;
                $this->refreshScreen();
            }
        }

        //echo ShArr($InsertList);
        //exit;

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
            $s_FileFolder[Mes] = 15;
            $this->refreshScreen();
        }
        if ($DomainDiskUsage + $InsertSize >= $DomainQuote) {
            $s_FileFolder[Mes] = 19;
            $this->refreshScreen();
        }

        // -----------------------------------------------
        // Pasting

        $SourceList = array();

        DBExec("BEGIN");

        reset($InsertList);
        while(list($n, $v) = each($InsertList)) {
            $Item =& $InsertList[$n];

            if ($Item[newsysnum] != "") {
                continue;
            }

            if ($Item[parent]) {
                $InsertFS       = $Item[parent][newsysnum];
                $Item[newowner] = $Item[parent][newowner];
            } else {
                $InsertFS       = $FS;
                $Item[newowner] = $Owner;

                if ($Item[newowner] != $Item[owner]) {
                    if ($Item[ownername] != $this->USRNAME) {
                        $SourceList[$Item[ownername]][] =& $Item;
                    }
                }
            }
            $Item[newup] = $InsertFS;

            if ($Item['parent']['new']) {
                $Item['new'] = $Item['parent']['new'];
            }

            if ($Item[up] == $InsertFS && $Item[newowner] == $Item[owner] && $Item[prz] == "r") {
                $Item[opr][] = "use without shange";
                $Item[newsysnum] = $Item[sysnum];
                continue;
            }

            if ($Item[sysnumfile] == 0) {
                $this->rPaste_PasteFolder(&$Item, $InsertFS);
            } else {
                $this->rPaste_PasteFile(&$Item, $InsertFS);
            }
        }


        //DBExec("ROLLBACK");
        //echo ShArr($InsertList); exit;

        DBExec("DELETE FROM clip WHERE clip.owner = '$this->USRNAME' and clip.ftype <> 'c'", __LINE__);

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

            // if ($Message != "") {
            //     $d = date("r");
            //     $Message = "User {$this->USRNAME} at {$d} cut/copy file(s)\n" . $Message;
            //     $this->SendMessage(
            //                 $SourceOwnerAddr,
            //                 "System_messager" . preg_replace("/^[^@]+/", "", $SourceOwnerAddr),
            //                 $Message,
            //                 "User cut/copy file(s) $this->USRNAME"
            //            );
            // }
        }

        DBExec("COMMIT");


        $s_FileFolder[SelectedFiles] = array();
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
            $r_fs = DBExec("SELECT fs.*, file.fsize, '' AS prz,          '' AS path,                 '' AS ownername                               FROM fs LEFT JOIN file ON fs.sysnumfile = file.sysnum                    WHERE fs.ftype = 'f' AND fs.up = $FSNum", __LINE__);
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

        $r = DBExec("SELECT pastename('$filename', '$fileext', $owner, $FS) as newname", __LINE__);
        return $r->newname();
    }


    function rDeleteFile() // @METAGS DeleteFile
    {
        global $TagFile, $Mes;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if (!is_array($TagFile)) {
            $s_FileFolder[SelectedFiles] = array();
            $this->refreshScreen();
        }

        reset($TagFile);
        while (list($n, $v) = each($TagFile)) {
            $file = $this->Data[Files][$v];
            // echo "$file[name] ";

            if ($file[FileRO]) {
                $Mes = 5;
                return;
            } else {
                $s .= ($s != "" ? " or " : "") . "fs.sysnum = $v";
                unset($this->Data[Files][$v]);
            }
        }

        // echo "$s<br>";

        $r_fs=DBFind("fs", "($s)", "", __LINE__);

        while(!$r_fs->eof()) {
            if ($r_fs->sysnumfile() == 0) {
                DeleteDirectory($r_fs->sysnum());
            }
            $r_fs->Next();
        }

        DBExec("begin", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
        DBExec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

        DBExec("delete from fs where ($s) and ftype = 'f'", __LINE__);

        DBExec("COMMIT", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

        $s_FileFolder[SelectedFiles] = array();
        $this->refreshScreen();
    }


    function DownloadZip_NEW() // @METAGS DownloadZip
    {
        global $TagFile, $Mes, $PROGRAM_TMP, $INET_SRC, $FACE, $s_FileFolder;
        global $REMOTE_ADDR;


        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if (!is_array($s_FileFolder[SelectedFiles]) || count($s_FileFolder[SelectedFiles]) == 0) {
            $s_FileFolder[Mes] = 11;
            $this->refreshScreen();
        }

        $root = "$PROGRAM_TMP/dwnl_dir_" . posix_getpid();
        if (!@mkdir($root, 0777)) {
            $s_FileFolder[Mes] = 3;
            $this->refreshScreen();
        }


        if(!$this->DlZip($root, $s_FileFolder[SelectedFiles])) {
            system("rm -rf $root");
            $s_FileFolder[Mes] = 3;
            $this->refreshScreen();
        }
        session_write_close();

        header("Content-Type: application/x-msdownload");
        header("Content-Disposition: attachment; filename=\"download.zip\"");
        //header("Content-Length: " . $r_file->fsize());
        header("Accept-Ranges: bytes");

        $f = popen ("cd $root; zip -qr - *", "r");
        while(!feof($f)) {
            $buf = fread($f, 10000);
            echo $buf;
        }
        pclose($f);

        system("rm -rf $root");

        exit;
    }


    function rDownloadZip() // @METAGS DownloadZip
    {
        global $TagFile, $Mes, $PROGRAM_TMP, $INET_SRC, $FACE, $s_FileFolder;
        global $REMOTE_ADDR;


        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if (!is_array($s_FileFolder[SelectedFiles]) || count($s_FileFolder[SelectedFiles]) == 0) {
            $s_FileFolder[Mes] = 11;
            $this->refreshScreen();
        }

        $RootPath = "$PROGRAM_TMP/dwnl_dir_" . posix_getpid();
        if (!@mkdir($RootPath, 0777)) {
            $s_FileFolder[Mes] = 3;
            $this->refreshScreen();
        }


        $ZipSize = 0;
        if(!$this->DlZip($RootPath, $s_FileFolder[SelectedFiles], &$ZipSize)) {
            system("rm -rf $RootPath");
            $s_FileFolder[Mes] = 3;
            $this->refreshScreen();
        }

        $this->Log("Zip Files size " . $ZipSize);
        if($ZipSize > 50 * 1024 * 1024) {
            system("rm -rf $RootPath");
            $s_FileFolder[Mes] = 21;
            $this->refreshScreen();
        }

        $ZipName = "$PROGRAM_TMP/dwnl_zip_" . posix_getpid();
        system("cd $RootPath; zip -uqr $ZipName *");

        system("rm -rf $RootPath");

        if(!file_exists("$ZipName.zip")) {
            $s_FileFolder[Mes] = 3;
            $this->refreshScreen();
        }

        DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('" . $this->UID . "', '" . $this->USR->sysnumdomain() . "', 'downzip', datetime('now'::abstime), '" . filesize($ZipName . ".zip") . "', '0', '" . substr($this->USRNAME, 0, 20) . "', -1, '$REMOTE_ADDR')", __LINE__);

        session_write_close();

        header("Location: $INET_SRC/view_file.php/download.zip?UID=$this->UID&FACE=$FACE&DownloadZip=". urlencode(basename ("$ZipName.zip")));
        exit;
    }


    function DlZip($root, $files, &$ZipSize) // @METAGS DlZip
    {

        global $PROGRAM_FILES, $PROGRAM_TMP;

        if (!is_array($files) || !(Count($files) > 0)) {
            return 1;
        }

        $s = "";
        reset($files);
        while (list($n, $v) = _each($files)) {
            $s .= ($s != "" ? " or " : "") . "fs.sysnum = " . $v;
        }

        $r = DBFind("fs, file", "fs.ftype = 'f' and fs.sysnumfile <> 0 and fs.sysnumfile = file.sysnum and ($s)", "fs.name, fs.sysnumfile, fs.sysnum, file.numstorage, file.fsize", __LINE__);
        while (!$r->eof()) {
            #Debug("2 " . $r->name());

            $ZipSize += $r->fsize();

            $this->Log($PROGRAM_FILES . "/storage" . $r->numstorage() . "/" . $r->sysnumfile());
            if(!@symlink($PROGRAM_FILES . "/storage" . $r->numstorage() . "/" . $r->sysnumfile(), $root. "/" . $r->name())) {
                if(!@symlink($PROGRAM_FILES . "/storage" . $r->numstorage() . "/" . $r->sysnumfile(), $root. "/" . $r->name() . " " . $r->sysnum())) {
                    echo "1 " . $root. "/" . $r->name() . " " . $r->sysnum() . " <br>";
                    return 0;
                }
            }

            $r->Next();
        }

        $r = DBFind("fs", "fs.ftype = 'f' and fs.sysnumfile = 0 and ($s)", "name, sysnum", __LINE__);
        while (!$r->eof()) {
            if(!@mkdir($root . "/" . $r->name(), 0777)) {
                if(!@mkdir($root . "/" . $r->name() . " " . $r->sysnum(), 0777)) {
                    echo "2<br>";
                    return 0;
                }
            }

            $r1 = DBFind("fs", "fs.up = " . $r->sysnum(), "sysnum", __LINE__);

            $Arr = array();
            while (!$r1->eof()) {
                $Arr[] = $r1->sysnum();
                $r1->Next();
            }

            if (!$this->DlZip($root . "/" . $r->name(), $Arr, &$ZipSize)) {
                return 0;
            }

            $r->Next();
        }

        return 1;
    }


    function Sharing()
    {
        global $s_FileFolder, $View;

        unset($s_FileFolder[ScrSharing]);

        if ($this->Data[error]) {
             $this->refreshScreen();
        }

        if (!is_array($s_FileFolder[SelectedFiles]) || count($s_FileFolder[SelectedFiles]) == 0) {
          $s_FileFolder[Mes] = 11;
          $this->refreshScreen();
        }

        $View = "Sharing";
        $this->refreshScreen();
    }


    function rSharingSet()
    {
        global $s_FileFolder;
        global $fTO, $fSubj, $fPermLev, $fTracking, $fSharingMemo, $fSharingStandartMessage, $fSaveSentMessage;
        global $fExpDate, $fExpPeriod;
        global $INET_ROOT, $INET_IMG, $INET_SRC, $INET_CGI;
        global $FS;
        global $Mes;
        global $SendMessageList, $View, $TEMPL_CHARSET;

        $s_FileFolder[ScrSharing][fTO]                     = $fTO;
        $s_FileFolder[ScrSharing][fSubj]                   = $fSubj;
        $s_FileFolder[ScrSharing][fPermLev]                = $fPermLev;
        $s_FileFolder[ScrSharing][fSharingMemo]            = $fSharingMemo;
		$s_FileFolder[ScrSharing][fSharingStandartMessage] = $fSharingStandartMessage;
		$s_FileFolder[ScrSharing][fSaveSentMessage]		   = "$fSaveSentMessage";
        $s_FileFolder[ScrSharing][fExpDate]                = $fExpDate;
        $s_FileFolder[ScrSharing][fExpPeriod]              = $fExpPeriod;

        if ($this->Data[error]) {
            $View = "Files";
            $this->refreshScreen();
        }

        if (!is_array($s_FileFolder[SelectedFiles]) || count($s_FileFolder[SelectedFiles]) == 0) {
            $s_FileFolder[Mes] = 11;
            $this->refreshScreen();
        }

        if (count($s_FileFolder[SelectedFiles]) > 1 && $fPermLev == "u") {
            $s_FileFolder[Mes] = 14;
            $this->refreshScreen();
        }


        $s = "";
        reset($s_FileFolder[SelectedFiles]);
        while (list($n, $v) = each($s_FileFolder[SelectedFiles])) {
            if (ereg("^[0-9]+$", $v)) {
                $s .= ($s != "" ? " or " : "") . "fs.sysnum = '$v'";
            }
        }
        if ($s == "") {
            $s_FileFolder[Mes] = 11;
            $this->refreshScreen();
        }

        $ListFiles = array();
        $r_fs = DBExec("SELECT fs.sysnum from fs where ($s) and fs.owner = $this->UID and fs.ftype = 'f'", __LINE__);
        while (!$r_fs->eof()) {
            $ListFiles[] = $r_fs->sysnum();
            $r_fs->Next();
        }
        if (count($ListFiles) == 0) {
            $s_FileFolder[Mes] = 11;
            $this->refreshScreen();
        }


        if ($fTO == "") {
            $s_FileFolder[Mes] = 12;
            $this->refreshScreen();
        }

        if(ParseAddressesList($fTO, $SharingAddresList) == 0) {
            $s_FileFolder[Mes] = 16;
            $this->refreshScreen();
        }

        if ($fExpDate == "" && $fExpPeriod != "0") {
            $fExpDate = $fExpPeriod;
        }

        if ($fExpDate != "") {
            if (!ereg("^[ ]*((([0-9]{1,2})[\.\/]{1}([0-9]{1,2})[\.\/]{1}([0-9]{4}))|([0-9]{1,4}))[ ]*$", $fExpDate, $DateArr)) {
                $s_FileFolder[Mes] = 10;
                $this->refreshScreen();
            }
            if ($DateArr[2] != "") {
                $LastExpireDate = mktime (0, 0, 0, $DateArr[3], $DateArr[4], $DateArr[5]);
            } else {
                $LastExpireDate = mktime(0,0,0,date("m")  ,date("d")+$DateArr[6], date("Y"));
            }
        } else {
            $LastExpireDate = "";
        }

        if(!preg_match("/^[rwnu]$/", $fPermLev)) {
            $s_FileFolder[Mes] = 13;
            $this->refreshScreen();
        }

        if(!preg_match("/^1?$/", $fTracking)) {
            $fTracking = "1";
        }

        $r_admin = DBFind("domain, usr", "usr.sysnumdomain = domain.sysnum and usr.lev = 1 and usr.sysnumdomain = " . $this->USR->sysnumdomain(), "usr.name as usrname, domain.name as domainname", __LINE__);
        if ($r_admin->NumRows() == 0) {
             $r_admin = DBFind("domain, usr", "usr.sysnumdomain = domain.sysnum and usr.lev = 2", "usr.name as usrname, domain.name as domainname", __LINE__);
        }


        $UsersParams = $this->ReadUserUA($this->UID);

        $AddrFrom = $this->USR->name() . "@" . $this->DOMAIN->name();

        $AddrFromFull = $UsersParams[firstname] . ( ( $UsersParams[firstname] != "" ? " " : "" ) . $UsersParams[lastname] );
        $AddrFromFull = "\"" . ( $AddrFromFull != "" ? $AddrFromFull : $UsersParams[name] ) . "\" <$AddrFrom>";

        //echo htmlspecialchars($AddrFromFull); exit;

        $SendMessageList = "";

        _reset($SharingAddresList);

        while (list($n1, $SharingAddres) = _each($SharingAddresList)) {
            $addr_full = $SharingAddres[addr];

            if(!preg_match("/\@/", $addr_full)) {
                $addr_full .= "@" . $this->DOMAIN->name();
            }

            $addr_name = $SharingAddres[name] != "" ? $SharingAddres[name] : $SharingAddres[addr];

            $Message = "";

            $LastExpireDate = (($LastExpireDate == 0) ? "NULL" : ("'" . date ("Y-m-d", $LastExpireDate) . " 23:59:59'"));
            reset($ListFiles);
            while (list($n2, $v2) = each($ListFiles)) {
                $Hash = FSdependAuthorizeHash(URLDecode($addr_full), $v2);

                DBExec("delete from acc where sysnumfs = $v2 and acc.username = '".URLDecode($addr_full)."'", __LINE__);
                DBExec("insert into acc (sysnum, sysnumfs, username, access, expdate, access_tracking, hash) values (nextval('acc_seq'), $v2, '".URLDecode($addr_full)."', '$fPermLev', $LastExpireDate, '$fTracking', '$Hash')", __LINE__);

                if($fPermLev == "n") {
                    continue;
                }

                $Message .= "<tr bgcolor='#e0e0e0'>";
                if ($fPermLev != "u") {
					$IconName = $this->Data[Files][$v2][sysnumfile] == 0 ? "folder-yellow.gif" : "view.gif";
                    $Name_ = $this->Data[Files][$v2][name];
                    $Name_ = "<span title='$Name_'>" . "<img src='$INET_IMG/{$IconName}' border=0 alt='Enter'>&nbsp;http://..&nbsp;" . (strlen($Name_) <= 20 ? $Name_ : ( substr($Name_, 0, 20) . "...") ) . "</span>";

                    $Message .= "<td><a href='$INET_ROOT/ra.php?HASH=$Hash'>" . $Name_ . "</a></td>";
                    if ($this->Data[Files][$v2][sign] == 0) {
                        $Message .= "<td>File's folders</td><td>&nbsp;</td>";
                    } else {
                        $Message .= "<td>" . $this->nbsp($this->Data[Files][$v2][rem]) . "</td><td align='right'>" . $this->nbsp(AsSize($this->Data[Files][$v2][fsize])) . "</td>";
                    }
                } else {
                    $Name_ = $this->Data[Files][$v2][name];
                    $Name_ = "<span title='$Name_'>" . "<img src='$INET_IMG/folder-yellow.gif' border=0 alt='Enter'>&nbsp;http://..&nbsp;" . (strlen($Name_) <= 20 ? $Name_ : (substr($Name_, 0, 20) . "...")) . "</span>";
                    $Message .= "<td><a href='$INET_SRC/upld.php?UID=$addr_full&Key=".URLEncode(AuthorizeKey($addr_full))."&FACE=en&FS=$v2'>";
                    $Message .= $Name_ . "</a></td>";
                    // $Message .= "<td>File's folders</td><td>&nbsp;</td>";
                }
                $Message .= "</tr>\n";
            } // while all files

            if($fPermLev == "n") {
                continue;
            }

            if($fPermLev != "u") {
                $Message = "<b>List of files :</b><br>" .
                           "<table border='0' bgcolor='#597fbf' width='100%' CELLSPACING='1'>\n" .
                           "<tr bgcolor='#4040a0'>".
                           "<td width='30%'><font color='#fefefe'><b><center>Name</center></b></font></td>".
                           "<td width='50%'><font color='#fefefe'><b><center>Type</center></b></font></td>".
                           "<td width='20%'><font color='#fefefe'><b><center>Size</center></b></font></td>".
                           "</tr>\n" . $Message . "</table>\n";


				if ($s_FileFolder[ScrSharing][fSharingStandartMessage] != 2) {
                	$MessageAll = "\n<html>\n<body bgcolor='#799fbf'>\n".
                              	"<b>Dear " . htmlspecialchars($addr_name) . "</b> !<br><br>\n\n" .
                              	"I am sending you a secured access link to my FTP (my files) Server.<br>\n".
                              	"All you have to do is to click on the file or folder's name in the \"<u><b>List of files</b></u>\" below,<br>\n".
                              	"The browser will be opened in my ftp server so you can <b>DOWNLOAD</b> or <b>UPLOAD</b> files on \n" .
                              	"<a href='$INET_ROOT/ra.php?UID=$addr_full&Key=".URLEncode(AuthorizeKey($addr_full))."&FACE=en&Fri=$this->UID'><font color='white'>http://..&nbsp;my FTP server.</font></a><br><br>\n" .
                              	($fSharingMemo != "" ? "<hr>$fSharingMemo<hr>" : "") .
                              	($LastExpireDate == "NULL" ? "" : ("This permission will be valid till " . date("d M Y", mkspectime($LastExpireDate))) . "<br><br>") .
                              	$Message.
                              	"<br>\n".
                              	"For more information: <a href='mailto:".$this->USR->name()."@".$this->DOMAIN->name()."'><font color='white'>" . $this->USR->name() . "@" . $this->DOMAIN->name()."</font></a><br>".
                              	"For Technical Support: <a href='mailto:".$r_admin->usrname()."@".$r_admin->domainname()."'><font color='white'>" . $r_admin->usrname() . "@" . $r_admin->domainname() . "</font></a><hr>".
                              	"This link has been provided by <b>Afik 1 System &reg;</b> is copyrighted work of <b>WAN Vision Ltd. &copy;</b>. <a href='mailto:marketing@afik1.co.il'><font color='white'>marketing@afik1.co.il</font></a><br>\n" .
                              	"\n</body></html>\n";
				} else {
                	$MessageAll = "\n<html>\n<body>\n".
                              	$fSharingMemo.
                              	"<hr>\n".
                              	$Message.
                              	"<br>\n".
                              	"This message has been provided by <b>Afik 1 System &reg;</b> is copyrighted work of <b>WAN Vision Ltd. &copy;</b>. <a href='mailto:marketing@afik1.co.il'><font color='white'>marketing@afik1.co.il</font></a><br>\n" .
                              	"\n</body></html>\n";
				}
            } else {
                $Message = "List of Folders : <br>" .
                           "<table border=0 bgcolor='#597fbf' width='100%'>\n" .
                           $Message . "</table>\n";

				if ($s_FileFolder[ScrSharing][fSharingStandartMessage] != 2) {
                	$MessageAll = "<html><body bgcolor='#597fbf'>\n".
                              	"<b>Dear " . htmlspecialchars($addr_name) . "</b> !<br><br>\n\n" .
                              	"I am sending you the link to my ftp server.<br>".
                              	"All you have to do is to click on the folder's name in \"List of files\" below,<br>" .
                              	"then the browser will be opened in my ftp server so you can <b>UPLOAD</b> files to the folders on<br>" .
                              	"<a href='$INET_ROOT/ra.php?UID=$addr_full&Key=".URLEncode(AuthorizeKey($addr_full))."&FACE=en&Fri=$this->UID'><font color='white'>http://..&nbsp;my FTP server.</font></a><br><br>\n" .
                              	($fSharingMemo != "" ? "<hr>$fSharingMemo<hr>" : "") .
                              	$Message.
                              	"</body></html>\n";
				} else {
                	$MessageAll = "\n<html>\n<body>\n".
                              	$fSharingMemo.
                              	"<hr>\n".
                              	$Message.
                              	"<br>\n".
                              	"This message has been provided by <b>Afik 1 System &reg;</b> is copyrighted work of <b>WAN Vision Ltd. &copy;</b>. <a href='mailto:marketing@afik1.co.il'><font color='white'>marketing@afik1.co.il</font></a><br>\n" .
                              	"\n</body></html>\n";
				}
            }

            $Boundary_unique = "--" . md5(time()) . "_" . time() . "_" . $this->USRNAME;


            $fSubj_ = $this->EncodeBase64($fSubj);

            $r = mail($addr_full, $fSubj_,
                                      "\r\n--" . $Boundary_unique . "_outsite\r\nContent-Type: multipart/alternative;\r\n\tboundary=\"$Boundary_unique\"\r\n\r\n" .
                                        "\r\n--$Boundary_unique\r\nContent-Type: Text/PLAIN; charset=$TEMPL_CHARSET\r\nContent-Disposition: inline\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n" . imap_8bit(html2text($MessageAll)) . "\r\n" .
                                        "\r\n--$Boundary_unique\r\nContent-Type: Text/HTML; charset=$TEMPL_CHARSET\r\nContent-Disposition: inline\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n" . imap_8bit($MessageAll) . "\r\n--$Boundary_unique--\r\n" .
                                      "\r\n--" . $Boundary_unique . "_outsite--\r\n",

                                      "Content-Type: multipart/mixed; boundary=\"" . $Boundary_unique . "_outsite\"\r\n" .
                                         "From: $AddrFromFull\r\n" .
                                         "Reply-To: $AddrFromFull",
                                      "-f$AddrFrom");
            if (!$r) {
                $s_FileFolder[Mes] = 18;
                $s_FileFolder[MesParam] .= " " . $addr_full;
            } else {
                $SendMessageList .= "<i>" . htmlspecialchars($addr_full) . "</i><br>\n";
            }

        	if ($s_FileFolder[ScrSharing][fSaveSentMessage] != "") {
            	$res = DBFind("usr, fld", "fld.sysnumusr=usr.sysnum and usr.sysnum=$this->UID and fld.ftype = 2", "fld.sysnum as sysnumfld", __LINE__);
            	if ($res->NumRows() == 1) {
                	$this->SendMailToLocalFolder($res->sysnumfld(), $this->UID, $AddrFromFull, $addr_full, $fSubj, $MessageAll);
            	}
        	}
        } // while

        if ($s_FileFolder[Mes] != 18) {
            $s_FileFolder[SelectedFiles] = array();
            $s_FileFolder[Status] = array();
        }

        $this->SharingCancel();
    }


	function SendMailToLocalFolder($ToFLD, $RecivId, $From, $To, $Subj, $Message)
	{
        global $TEMPL_CHARSE;

        $Id_          = URLEncode( "<" . md5(time() . $this->USRNAME) . "XXXXX" . time() . "XXXXX" . $this->USRNAME . ">" );
        $fTO_         = URLEncode( $To );
        $fFrom_       = URLEncode( $From );
        $fSubj_       = URLEncode( $Subj );
        $fMessage_    = URLEncode( $Message );
        $Size_        = strlen($fMessage_);

        $NewMsgSysNum = NextVal("msg_seq");
        DBExec("INSERT INTO msg (sysnum, sysnumfld, id, addrto, addrfrom, subj, size, send, recev, fnew, content, charset) VALUES ($NewMsgSysNum, $ToFLD, '$Id_', '$fTO_', '$fFrom_', '$fSubj_', '$Size_', timenow(), timenow(), true, 'TEXT/HTML', '$TEMPL_CHARSET')", __LINE__);
        DBExec("update fld set fnew = fnew + 1 where sysnum = '$ToFLD'", __LINE__);

        while ($fMessage_ != "") {
            $TMP = substr($fMessage_, 0, 2000);
            DBExec("INSERT INTO msgbody (sysnum, sysnummsg, body) VALUES (NextVal('msgbody_seq'), $NewMsgSysNum, '$TMP')", __LINE__);
            $fMessage_ = substr($fMessage_, 2000);
        }
	}


    function SharingCancel()
    {
          global $s_FileFolder, $View;

          unset($s_FileFolder[ScrSharing]);

          $View = "Files";
          $this->refreshScreen();
    }

    function rEditSharing()
    {
            global $s_FileFolder, $View;

            $View = "EditSharing";
            $this->refreshScreen();
    }


    function rEditSharingSet()
    {
        global $fPermLev, $fTracking;
        global $PermDate;
        global $s_FileFolder;
        global $Mes;

        if ($this->Data[error]) {
            $this->refreshScreen();
        }

        if (!is_array($fPermLev)) {
            $this->refreshScreen();
        }

        reset($fPermLev);
        while(list($n, $v) = each($fPermLev)) {
            $d  = $PermDate[$n];
            $tr = $fTracking[$n];
            $t  = split(" ", $n);

            $LastExpireDate = "NULL";
            if ($d != "") {
                if (!ereg("^[ ]*([0-9]{1,2})[\.\/]{1}([0-9]{1,2})[\.\/]{1}([0-9]{4})[ ]*$", $d, $DateArr)) {
                    $Mes = 10;
                    return;
                }
                $LastExpireDate = "'" . date ("Y-m-d", mktime (0, 0, 0, $DateArr[1], $DateArr[2], $DateArr[3])) . " 23:59:59'";
            }

            // echo "$t[0]=$t[1]=$v<br>";

            if ($v != "") {
                if ($v == "e") {
                    DBExec("DELETE FROM acc where sysnumfs = $t[0] and username = '$t[1]'", __LINE__);
                } else {
                    $r = DBFind("acc", "sysnumfs = $t[0] and username = '$t[1]'", "", __LINE__);
                    if ($r->NumRows() == 1) {
                        DBExec("UPDATE acc set access = '$v', expdate = $LastExpireDate, access_tracking = '$tr' where sysnumfs = $t[0] and username = '$t[1]'", __LINE__);
                    } else {
                        DBExec("insert into acc (sysnum, sysnumfs, username, access, expdate, access_tracking, hash) values (nextval('acc_seq'), $t[0], '$t[1]', '$v', $LastExpireDate, $tr, '" . FSdependAuthorizeHash($t[1], $t[0]) . "')", __LINE__);
                    }
                }
            }
        }

        $this->rEditSharingCancel();
    }


    function rEditSharingCancel()
    {
        global $s_FileFolder, $View;

        $View = "Files";
        //$s_FileFolder[SelectedFiles] = array();
        $this->refreshScreen();
    }


    function rSortSet()
    {
        global $s_FileFolder, $sSortSet, $sSortSetDirection;

        $s_FileFolder[Sort] = $sSortSetDirection;

        //echo sharr($s_FileFolder); exit;


        $this->refreshScreen();
    }


    function rShiftScr($direct)
    {
        global $NPage;

        if ($direct < 0) {
            if ($NPage > 0) {
                $NPage --;
            }
        } else {
            $NPage ++;
        }

        $this->refreshScreen();
    }


    function rOptions()
    {
        global $s_FileFolder, $sOptions, $sOptionsFileNumber, $View;

        $View = "Options";
        $s_FileFolder[OptionsFile] = $sOptionsFileNumber;

        //echo shglobals(); exit;

        $this->refreshScreen();
    }


    function rOptionsSet()
    {
        global $Mes, $Description, $sOptions, $s_FileFolder, $fPermLev;

        if (eregi("[\";`]", $Description))
        {
            $s_FileFolder[Mes] = 9;
            $this->refreshScreen();
        }

        if (strlen($Description) > 50) {
            $s_FileFolder[Mes] = 17;
            $this->refreshScreen();
        }

        $Description = preg_replace("/'/", "''", $Description);

        $file = $this->Data[Files][$s_FileFolder[OptionsFile]];

        if(!$file) {
            $this->rOptionsCancel();
        }

        // ShGlobals();
        DBExec("update fs set rem = '$Description' where sysnum = $file[sysnum]", __LINE__);

        //echo sharr($s_FileFolder); exit;

        if (is_array($fPermLev)) {
            $this->rEditSharingSet();
        }
        $this->rOptionsCancel();
    }


    function rOptionsCancel()
    {
        global $s_FileFolder , $View;

        $View = "Files";
        $s_FileFolder[Status] = array();
        unset($s_FileFolder[OptionsFile]);
        $this->refreshScreen();
    }


    function rQuickSharing()
    {
        global $s_FileFolder;
        global $fQuickPermLev, $fQuickPermName, $fQuickTakeTo, $fQuickAccTracking;

        // Geting target file system number
        $file = $this->Data[Files][$s_FileFolder[OptionsFile]];

        if(!$file) {
            $this->refreshScreen();
        }

        $TargenSysNum = $file[sysnum];
        if ($TargenSysNum == "") {
            $this->refreshScreen();
        }


        // check Level
        if(!preg_match("/^[rwnu]$/", $fQuickPermLev)) {
            $s_FileFolder[Mes] = 13;
            $this->refreshScreen();
        }

        if ($fQuickAccTracking != 1) {
            $fQuickAccTracking = 0;
        }

        if ($fQuickTakeTo == "u") {
            // check Address
            if(ParseAddressesList($fQuickPermName, $SharingAddresList) == 0) {
                $s_FileFolder[Mes] = 16;
                $this->refreshScreen();
            }

            _reset($SharingAddresList);
            while (list($n1, $SharingAddres) = _each($SharingAddresList)) {
                $addr_full = $SharingAddres[addr];
                if(!preg_match("/\@/", $addr_full)) {
                    $addr_full .= "@" . $this->DOMAIN->name();
                }

                DBExec("delete from acc where sysnumfs = '$TargenSysNum' and acc.username = '".URLDecode($addr_full)."'", __LINE__);
                DBExec("insert into acc (sysnum, sysnumfs, username, access, expdate, access_tracking, hash) values (nextval('acc_seq'), $TargenSysNum, '".URLDecode($addr_full)."', '$fQuickPermLev', NULL, '$fQuickAccTracking', '" . FSdependAuthorizeHash(URLDecode($addr_full), $TargenSysNum) . "')", __LINE__);
            }
        } else {
            $addr_full = "GuestOf." . $this->USRNAME;
            DBExec("delete from acc where sysnumfs = '$TargenSysNum' and acc.username = '".URLDecode($addr_full)."'", __LINE__);
            DBExec("insert into acc (sysnum, sysnumfs, username, access, expdate, access_tracking, hash) values (nextval('acc_seq'), $TargenSysNum, '" . URLDecode($addr_full) . "', '$fQuickPermLev', NULL, '$fQuickAccTracking', '" . FSdependAuthorizeHash(URLDecode($addr_full), $TargenSysNum) . "')", __LINE__);
        }

        unset($s_FileFolder[Status][fQuickPermName]);

        $this->refreshScreen();
    }


    function refreshScreen() // overlaped virtuals function
    {
        global $FS, $NPage, $SCRIPT_NAME, $FACE, $s_FileFolder, $View;

        $URL = "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE";
        if ($FS != "") {
            $URL .= "&FS=" . URLENCODE($FS);
        }
        if ($View != "") {
            $URL .= "&View=" . URLENCODE($View);
        }
        if ($NPage != "") {
            $URL .= "&NPage=" . URLENCODE($NPage);
        }

        parent::refreshScreen($URL);
    }


    function rRetMes()
    {
        global $FACE, $s_FileFolder;

        if (eregi("^ADDR$", $s_FileFolder[Ret])) {
          header("Location: address.php?UID=$this->UID&FACE=$FACE");
          exit;
        }

        if (eregi("^MES#([0-9]+)#([0-9]+)$", $s_FileFolder[Ret], $regs)) {
          header("Location: mail_folder.php?UID=$this->UID&FACE=$FACE&Fld=$regs[1]&Msg=$regs[2]");
          exit;
        }

        if (eregi("^MES#([0-9]+)$", $s_FileFolder[Ret], $regs)) {
          header("Location: mail_folder.php?UID=$this->UID&FACE=$FACE&Fld=$regs[1]");
          exit;
        }

        $this->refreshScreen();
    }


    function ReadUserUA($NumEdit)
    {
        $UsrFields = array("name", "password", "lev", "country");
        $r_usr = DBFind("usr", "sysnum = $NumEdit", "", __LINE__);
        while (list($n, $Name) = each($UsrFields)) {
          $Rez[$Name] = URLDecode(trim($r_usr->Field($Name)));
        }

        $r_usr = DBFind("usr_ua", "sysnumusr = $NumEdit", "", __LINE__);
        for ($r_usr->set(0); !$r_usr->eof(); $r_usr->Next()) {
          $Name  = $r_usr->name();
          $Value = URLDecode(trim($r_usr->value()));
          $Rez[$Name] = $Value;
        }

        return $Rez;
    }


    function script()
    {
        global $INET_SRC;
        screen::script();
        echo "<script language='javascript' src='$INET_SRC/file_folder.js'></script>\n";
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

    function EncodeBase64($s)
    {
        global $TEMPL_CHARSET;

        if ($s == "" || preg_match("/^[a-z0-9\-\_\@\.\"\ \,\;]+$/i", $s)) {
            return $s;
        }

        return "=?$TEMPL_CHARSET?B?" . base64_encode($s) . "?=";
    }
} // end of class CFileFolderScreen


function CompareFiles($a, $b)
{
    global $s_FileFolder;

    if ($s_FileFolder[Sort] == "") {
        $s_FileFolder[Sort] = "n";
    }

    // echo "$a[name] $b[name]<br>";


    if ($a[sign] < $b[sign]) return -1;
    if ($a[sign] > $b[sign]) return 1;

    switch ($s_FileFolder[Sort]) {
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
            $tmpa = $a[usrname]."@".$a[domainname];
            $tmpb = $b[usrname]."@".$b[domainname];
            if ($tmpa < $tmpb) return -1;
            if ($tmpa > $tmpb) return 1;
            break;
        case "O" :
            $tmpa = $a[usrname]."@".$a[domainname];
            $tmpb = $b[usrname]."@".$b[domainname];
            if ($tmpa < $tmpb) return 1;
            if ($tmpa > $tmpb) return -1;
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

} // function CompareFiles


function UncaseCmpString($a, $b)
{
    $a1 = strtoupper($a);
    $b1 = strtoupper($b);
    if ($a1 == $b1) return 0;
    return ($a1 > $b1) ? 1 : -1;
} // UncaseCmpString

?>
