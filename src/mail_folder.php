<?php


/*

// INBOX - 1
// SEND  - 2
// DRAFT - 3
// OTHER - 4
// TRASH - 5
// NOTIF - 6



        function Scr()
          function ScrFld()
          function ScrMsg()
            function PrevNextMsg($n)
            function FieldToSort($direct)
          function ToolsBar()
          function ScrAddressList()
          function ScrPrint()

        function MsgsMove()
        function MsgsDelete()

        function MsgMove()
        function MsgDelete()

        function CopyAttach()
        function CopyAddAttach()
        function DownloadZip()
        function ToFileFolder()
        function ToAddressList()
        function rReply($kind)
        function mes()
        function MakeFolderLangName($FolderType, $OriginalFolderName)
        function AddressListSave()
        function AddressListExit()
        function AccessRefused()
        function rPrint()
        function refreshScreen()
*/

// header("Cache-Control: no-cache, must-revalidate");           // HTTP/1.1

include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("cont.inc.php");

require("db.inc.php");
ConnectToDB();

require("screen.inc.php");

class CMailFolderScreen extends screen
{

    function CMailFolderScreen() // constructor
    {
        global $R_FLD, $Fld, $Msg, $Sort, $NPage, $TEMPL, $s_MailFolder;
        global $_SERVER;

        screen::screen();    // inherited constructor
        $this->SetTempl("mail_folder");

        if(!ereg("^[0-9]+$", $Msg)) {
            $Msg = 0;
        }

        if(!ereg("^[0-9]+$", $Fld)) {
            $Fld = 0;
        }

        if(!ereg("^[0-9]+$", $NPage)) {
            $NPage = 0;
        }

        if ($Msg != 0) {
            $this->Numer = $Msg;
            $R_FLD = DBFind("fld, msg", "fld.sysnum = msg.sysnumfld and msg.sysnum = $Msg", "", __LINE__);
            if ($R_FLD->NumRows() == 0) {
                $this->AccessRefused();
            }
            $Fld = $R_FLD->sysnum();
        } else {
            if ($Fld != 0) {
                $this->Numer = $Fld;
                $R_FLD = DBFind("fld", "fld.sysnum = $Fld", "", __LINE__);
                if ($R_FLD->NumRows() == 0) {
                    $this->AccessRefused();
                }
            } else {
                $R_FLD = DBFind("fld", "fld.sysnumusr = '$this->UID' and ftype = '1'", "", __LINE__);
                if ($R_FLD->NumRows() == 0) {
                    $this->AccessRefused();
                }
                $this->Numer = $Fld = $R_FLD->sysnum();
            }
        }

        $R_FLD->sort_ = $R_FLD->sort();
        $R_FLD->Set(0);

        if ($R_FLD->sysnumusr() != $this->UID) {
            $this->AccessRefused();
        }

        if (($Sort != "") and (isset($R_FLD))){
            DBExec("Update fld set sort = '$Sort' where sysnum = ".$R_FLD->sysnum(), __LINE__);
            $R_FLD->sort_ = $Sort;
        }

        $this->Request_actions["sNewView"]           = "NewView()";
        $this->Request_actions["sRefresh"]           = "SaveRefreshStatus()";

        $this->Request_actions["sReply"]             = "rReply(1)";
        $this->Request_actions["sForward"]           = "rReply(2)";
        $this->Request_actions["sReplyAll"]          = "rReply(3)";

        $this->Request_actions["sToAddressList"]     = "ToAddressList()";
        $this->Request_actions["sShow"]              = "JmpToFolder()";
        $this->Request_actions["sMsgsMove"]          = "MsgsMove()";
        $this->Request_actions["sMsgsDelete"]        = "MsgsDelete()";
        $this->Request_actions["sMsgMove"]           = "MsgMove()";
        $this->Request_actions["sMsgDelete"]         = "MsgDelete()";
        $this->Request_actions["sCopy"]              = "CopyAttach()";
        $this->Request_actions["sCopyAdd"]           = "CopyAddAttach()";
        $this->Request_actions["sDownloadZip"]       = "DownloadZip()";
        $this->Request_actions["sToFileFolder"]      = "ToFileFolder()";
        $this->Request_actions["sPrint"]             = "rPrint()";
        $this->Request_actions["sPrevScr"]           = "rShiftScr(-1)";
        $this->Request_actions["sNextScr"]           = "rShiftScr(1)";

        $this->Request_actions["sAddressListSave"]   = "AddressListSave()";
        $this->Request_actions["sAddressListExit"]   = "AddressListExit()";

        $this->PgTitle = "<b>$TEMPL[title]</b> " . $this->MakeFolderLangName($R_FLD->ftype(), $R_FLD->name(), 1);
        if ($Msg != 0) {
            $this->PgTitle .= "/$TEMPL[subtitle]";
        }

        if ($_SERVER[REQUEST_METHOD] == "POST") {
            $this->SaveScreenStatus("s_MailFolder", array("TagMSG", "CheckAddress", "MailAddress",  "NameAddress") );
        }
    }


    function OpenSession() // overlaped virtuals function
    {
        global $sDownloadZip, $s_MailFolder;

        parent::OpenSession();
        session_register("s_MailFolder");
    }


    function Referens() // overlaped
	{
        global $Msg;

        if ($Msg == 0) {
            parent::Referens();
        }
    }


    function Scr() // overlaped
    {
        global $R_FLD, $Msg, $Fld, $INET_IMG;
        global $s_MailFolder, $View;

        if ($Msg != 0) {
            if($View == 'AddressList') {
                $this->ScrAddressList();
            } else {
                if($View == 'Print') {
                    $this->ScrPrint();
                } else {
                    $this->ScrMsg();
                }
            }
        } else {
            $this->ScrFld();
            $this->out("<script language='javascript'>");
            $this->out("  setTimeout(\"document.location = document.location;\", 5 * 60 * 1000);");
            $this->out("</script>");
        }

        if ($R_FLD->fnew() > 0) {
            // $this->out("<embed width=\"0\" height=\"0\" src=\"$INET_IMG/tada.wav\" autostart=\"true\" loop=\"False\" hidden>");
            // $this->out("<embed width=\"0\" height=\"0\" src=\"$INET_IMG/sabrswg3.wav\" autostart=\"true\" loop=\"False\" hidden>");
            DBExec("update fld set fnew = 0 where sysnum = $Fld", __LINE__);
        }

        // $this->SubTable("border=1"); {
        //     $this->TRNext(); {
        //         $this->TDNext(); {
        //             $this->out(sharr($s_MailFolder), "&nbsp;");
        //         }
        //     }
        //     $this->TRNext(); {
        //         $this->TDNext(); {
        //             $this->out(sharr($GLOBALS[s_MailFolder]), "&nbsp;");
        //         }
        //     }
        // } $this->SubTableDone();
    }


    function ScrFld()
    {
        global $SCRIPT_NAME, $INET_IMG;
        global $R_FLD;
        global $TEMPL, $FACE;
        global $s_MailFolder;
        global $NPage;

        $TagMSGCount = 0;
        $LinePerScreen = 50;
        $r_usr_ua = DBExec("SELECT * FROM usr_ua WHERE name = 'perscreencount_mail' AND sysnumusr = '{$this->UID}'", __LINE__);
        if ((int)($r_usr_ua->value()) != 0) {
            $LinePerScreen = (int)($r_usr_ua->value());
        }

        $ftype = $R_FLD->ftype();

        $this->out( "<form method='post' name='ScrFldForm'>" );

        $this->out( "<input type='hidden' name='sRefresh'>" );

        // $r_fld_s = DBFind("fld", "sysnumusr = $this->UID and (ftype = 1 or ftype = 4) and sysnum <> ".$R_FLD->sysnum(), "", __LINE__);

        $sort = $this->FieldToSort(0);
        $r_fld_s = DBFind("fld", "sysnumusr = $this->UID order by sysnum", "", __LINE__);
        $r_msg = DBFind("msg", "sysnumfld=$this->Numer order by $sort[key]", "", __LINE__);
        $r_fs  = DBFind("file, fs, msg", "file.sysnum = fs.sysnumfile and fs.ftype = 'a' and fs.up = msg.sysnum and msg.sysnumfld=$this->Numer order by msg.sysnum", "msg.sysnum as msgsysnum, fs.sysnum, fs.name, file.fsize", __LINE__);

        $MaxNumPage = (int)(($r_msg->NumRows() - 1) / $LinePerScreen);
        if ($NPage > $MaxNumPage) {
            $NPage = $MaxNumPage;
        }
        $r_msg->Set($LinePerScreen * $NPage);

        // ToolsBar
        $this->SubTable("BORDER = '0'  CELLSPACING = '0' CELLPADDING = '4' width='100%'"); {
            $this->TRS(0, "class='toolsbarl'"); {
                $this->TDS(0, 0, "nowrap", ""); {
                    if ($r_fld_s->NumRows() != 0) {
                        $this->OUT("<select name='ShowBox' class='toolsbare'>" . "<option value=''>- $TEMPL[chfolder] -</option>");

                        $r_fld_s->set(0);
                        while (!$r_fld_s->Eof()) {
                            $this->out("<option value='".$r_fld_s->sysnum()."'>" . $this->MakeFolderLangName($r_fld_s->ftype(), $r_fld_s->name(), 0) . " &nbsp </option>");
                            $r_fld_s->Next();
                        }

                        $this->out("</select>" . $this->ButtonBlank);
                        $this->out(makeButton("type=1& form=ScrFldForm& name=sShow& img=$INET_IMG/mailfolderopen-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderopen.gif?FACE=$FACE& title=$TEMPL[bt_open_ico] "));
                    }

                    $this->out($this->SectionBlank);

                    if ($r_fld_s->NumRows() != 0) {
                        // $this->out("<input type='submit' name='sMsgsCopy' value='Copy' class='toolsbarb'>");
                        $this->OUT("<select name='DestBox' class='toolsbare'>" . "<option value=''>- $TEMPL[chfolder] -</option>");

                        $r_fld_s->set(0);
                        while (!$r_fld_s->Eof()) {
                            $this->out("<option value='".$r_fld_s->sysnum()."' class='toolsbarb'>" . $this->MakeFolderLangName($r_fld_s->ftype(), $r_fld_s->name(), 0) . "</option>");
                            $r_fld_s->Next();
                        }

                        $this->out("</select>" . $this->ButtonBlank);
                    }
                    $this->out(makeButton("type=1& form=ScrFldForm& name=sMsgsMove& img=$INET_IMG/mailfoldermovemessages-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfoldermovemessages.gif?FACE=$FACE& title=$TEMPL[bt_move_ico]"));

                    $this->out($this->SectionBlank);

                    $this->out(makeButton("type=1& form=ScrFldForm& name=sMsgsDelete& img=$INET_IMG/mailfolderdelmessages-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderdelmessages.gif?FACE=$FACE& title=$TEMPL[bt_delete_ico] "));
                } //TDS
            } // TRS
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler3x1.gif'>");

        if ($MaxNumPage > 0) {
            $this->SubTable("border = '0' cellspacing = '0' cellpadding = '4' class='tab' width='100%'"); {
                $this->TDNext("class='toolsbarl'"); {
                    if ($NPage > 0) {
                        $s = makeButton("type=1& form=ScrFldForm& name=sPrevScr& img=$INET_IMG/mailfolderarrowleft-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderarrowleft.gif?FACE=$FACE& title=$TEMPL[bt_prev_scr_ico]");
                    } else {
                        $s = "<img src='$INET_IMG/mailfolderarrowleft-unactive.gif' align='absmiddle' alt='$TEMPL[bt_prev_scr_ico]'>";
                    }
                    $this->Out($s);

                    $this->Out($this->ButtonBlank);

                    if ($NPage < $MaxNumPage) {
                        $s = makeButton("type=1& form=ScrFldForm& name=sNextScr& img=$INET_IMG/mailfolderarrowright-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderarrowright.gif?FACE=$FACE& title=$TEMPL[bt_next_scr_ico]");
                    } else {
                        $s = "<img src='$INET_IMG/mailfolderarrowright-unactive.gif' align='absmiddle' alt='$TEMPL[bt_next_scr_ico]'>";
                    }
                    $this->Out($s);

                    $this->Out($this->ButtonBlank);
                    $this->out("Page <b>" . ($NPage + 1) . "</b> From <b>" . ($MaxNumPage + 1) . "</b>");
                }
            } $this->SubTableDone();

            $this->out("<img src='$INET_IMG/filler3x1.gif'>");
        }

        $this->SubTable("border = '0' cellpadding = '0' cellspacing = '0' class='tab' grborder width='100%'"); {
            $this->TRS(0, "height='20'"); {
                $this->TDS(0, 0, "width='1%' class='ttp'", "<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=$this->Numer&Sort=N'><img src='$INET_IMG/letter-close.gif' border=0 alt='$TEMPL[un_read_mail_ico]'></a>");

                $this->TDS(0, 1, "width='1%' class='ttp'", "<input type='checkbox' name='TagMSGAll' title='$TEMPL[select_all_ico]' onClick='javascript:onTagMSGAllClick()'>");

                $this->TDS(0, 2, "width='1%' class='ttp'", "<img src='$INET_IMG/clip.gif' alt='$TEMPL[attach_ico]'>");

                $this->tds(0, 3, "width='35%' class='ttp'", ""); {
                    $this->out((($ftype == 1 || $ftype == 4) ? $TEMPL[lb_from] : $TEMPL[lb_to]) . $this->TextShift);
                    if ($R_FLD->sort_ != "a") {
                        $this->out("<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=$this->Numer&Sort=a'><img src='$INET_IMG/sort1.gif' alt='' border=0></a>&nbsp;");
                    }
                    if ($R_FLD->sort_ != "A") {
                        $this->out("<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=$this->Numer&Sort=A'><img src='$INET_IMG/sort2.gif' alt='' border=0></a>&nbsp;");
                    }
                }

                $this->tds(0, 4, "width='42%' class='ttp'", ""); {
                    $this->out($TEMPL[lb_subject] . $this->TextShift);
                    if ($R_FLD->sort_ != "s") {
                        $this->out("<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=$this->Numer&Sort=s'><img src='$INET_IMG/sort1.gif' alt='' border=0></a>&nbsp;");
                    }
                    if ($R_FLD->sort_ != "S") {
                        $this->out("<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=$this->Numer&Sort=S'><img src='$INET_IMG/sort2.gif' alt='' border=0></a>&nbsp;");
                    }
                } // tds

                $this->tds(0, 5, "width='10%' class='ttp' nowrap", ""); {
                    $this->out($TEMPL[lb_date] . $this->TextShift);
                    if ($R_FLD->sort_ != "d") {
                        $this->out("<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=$this->Numer&Sort=d'><img src='$INET_IMG/sort1.gif' alt='' border=0></a>&nbsp;");
                    }
                    if ($R_FLD->sort_ != "D") {
                        $this->out("<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=$this->Numer&Sort=D'><img src='$INET_IMG/sort2.gif' alt='' border=0></a>&nbsp;");
                    }
                } // tds

                $this->tds(0, 6, "width='10%' class='ttp'", ""); {
                    $this->out($TEMPL[lb_size]);
                } // tds
            } // trs


            $AllSize = 0;
            $AllCount = 0;
            for($i=1; !$r_msg->Eof() && $i <= $LinePerScreen; $i++, $r_msg->Next()) {
                //Debug($AllCount);

                if ($r_msg->fnew() != "t") {
                    $class="tlp";
                } else {
                    $class="tla";
                }
                $class="tlp"; // раньше было с выделением цветами новых и старых а потом убрали

                $this->TRS($i, ""); {

                    //-- col 0
                    if ($r_msg->fnew() != "t") {
                        // $s = "&nbsp";
                        $s = "<img src='$INET_IMG/letter-open.gif' border=0 alt='$TEMPL[read_mail_ico]'>";
                    } else {
                        // $s = "<img src='$INET_IMG/new_mes.gif' border=0 alt='New'>";
                        $s = "<img src='$INET_IMG/letter-close.gif' border=0 alt='$TEMPL[un_read_mail_ico]'>";
                    }
                    //$s_ = "<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=".$R_FLD->sysnum()."&Msg=".$r_msg->sysnum()."' target='_blank'>$s</a>";
                    $s_ = "<a href='javascript:SubViewMessage(\"$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=".$R_FLD->sysnum()."&Msg=".$r_msg->sysnum()."\")'>$s</a>";
                    $this->TDS($i, 0, "class='$class'", $s_);

                    //-- col 1
                    $checked = "";
                    //if ($s_MailFolder[Status][TagMSG][$r_msg->sysnum()] == $r_msg->sysnum()) {
                    if (is_array($s_MailFolder[Status][TagMSG]) && in_array($r_msg->sysnum(), $s_MailFolder[Status][TagMSG])) {
                        $checked = "CHECKED";
                    }
                    $this->TDS($i, 1, "class='$class'", "<input type='checkbox' name='TagMSG[" . $TagMSGCount++ . "]' value='" . $r_msg->sysnum() . "' onClick='javascript:onTagMSGClick()' $checked>");

                    //-- col 2
                    $alt = "";
                    // $FSize = strlen($this->GetMessageBody($r_msg->sysnum()));
                    $FSize = $r_msg->size();
                    $r_fs->Find("msgsysnum", $r_msg->sysnum());
                    while( !$r_fs->Eof() && $r_fs->msgsysnum() == $r_msg->sysnum()) {
                        $alt   .= " " . $r_fs->name();
                        $FSize += $r_fs->fsize();
                        $r_fs->Next();
                    }
                    if ($alt == "") {
                        $s = "&nbsp";
                    } else {
                        $s = "<img src='$INET_IMG/clip.gif' alt='$TEMPL[files_ico] : $alt'>";
                    }
                    $this->TDS($i, 2, "class='$class' align='center'", $s);


                    //-- col 3
                    if ($ftype == 1 || $ftype == 4) {
                        $s = URLDecode($r_msg->addrfrom());
                    } else {
                        $s = URLDecode($r_msg->addrto());
                    }
                    if ($s == "") {
                        $s = "[None]";
                    }
                    $s_ = ReformatToLeft($s, 30);
                    if ($r_msg->fnew() == "t") {
                        $s_ = "<b>$s_</b>";
                    }
                    $s_ = "<span title='" . htmlspecialchars($s) . "' class='$class"."a'>$s_</span>";
                    //$s_ = $this->TextShift . "<a href='$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=".$R_FLD->sysnum()."&Msg=".$r_msg->sysnum()."' target='_blank'>$s_</a>" . $this->TextShift;
                    $s_ = $this->TextShift . "<a href='javascript:SubViewMessage(\"$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=".$R_FLD->sysnum()."&Msg=".$r_msg->sysnum()."\")'>$s_</a>" . $this->TextShift;
                    $this->TDS($i, 3, "class='$class'", $s_);

                    //-- col 4
                    $s = ($r_msg->subj() != "") ? URLDecode($r_msg->subj()) : "[none]";
                    $s_ = ReformatToLeft($s, 40);
                    if ($r_msg->fnew() == "t") {
                        $s_ = "<b>" . $s_ . "</b>";
                    }
                    $s_ = $this->TextShift . "<span title='" . htmlspecialchars($s) . "'>" . $s_ . "</span>" . $this->TextShift;
                    $this->TDS($i, 4, "class='$class'", $s_);

                    //-- col 5
                    if ($ftype == 1 || $ftype == 4) {
                        $s = mkdatetime($r_msg->send());
                    } else {
                        $s = mkdatetime($r_msg->recev());
                    }
                    $s = "<font class='$class'>$s &nbsp</font>";
                    if ($r_msg->fnew() == "t") {
                        $s = "<b>$s &nbsp</b>";
                    }
                    $this->TDS($i, 5, "class='$class' nowrap", $this->TextShift . $s , $this->TextShift);

                    //-- col 6
                    $s = $FSize;
                    $s1 = AsSize($FSize);
                    if ($r_msg->fnew() == "t") {
                        $s1 = "<b>$s1</b>";
                    }
                    $s = "<div title='$s'>$s1 &nbsp</div>";
                    $this->TDS($i, 6, "class='$class' align='right' nowrap", $s);
                }

                $AllCount += 1;
                $AllSize += $FSize;
            }

            unset($s_MailFolder[Status][TagMSG]);
            $this->out("<script language='javascript'>");
            $this->out("  onTagMSGClick()");
            $this->out("</script>");

            if ($r_msg->NumRows() != 0) {
                $class="tlp";
                $this->TRS($i, "");
                $this->TDS($i, 0, "class='$class'",              "&nbsp");
                $this->TDS($i, 1, "class='$class'",              "&nbsp");
                $this->TDS($i, 2, "class='$class'",              "&nbsp");
                $this->TDS($i, 3, "class='$class'",              $this->TextShift . "<b>$AllCount $TEMPL[cnt_messages]</b>");
                if ($MaxNumPage > 0) {
                    $this->TDS($i, 3, "class='$class'",          "<b> $TEMPL[cnt_all_messages] " . $r_msg->NumRows() . "</b>");
                }
                $this->TDS($i, 4, "class='$class'",              "&nbsp");
                $this->TDS($i, 5, "class='$class' align='right'", "<b>$TEMPL[cnt_size]</b>");
                $s = $AllSize;
                $s1 = AsSize($s);
                $this->TDS($i, 6, "class='$class' align='right' nowrap", "<div title='$s'><b>$s1</b></div>");
            } else {
                $this->tds(1, 0, "colspan=13 class='tlp' align='center'", "");
                $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0");
                $this->tds(0, 0, "width='250' height='70'", "<center><font size='+2'>$TEMPL[cnt_empty]</font></center>");
                $this->SubTableDone();
            }
        } $this->SubTableDone();

        $this->out("<br><hr size=5>");

        $this->out("</form>");
    }



    function ScrMsg()
    {
        global $SelectAll;
        global $R_FLD;
        global $SCRIPT_NAME, $HTTP_HOST, $INET_SRC, $INET_IMG, $FACE, $TEMPL;
        global $HTTP_USER_AGENT;

        $TagATTCount = 0;

        DBExec("UPDATE msg SET fnew = false WHERE sysnum = $this->Numer", __LINE__);

        $this->out("<script language='javascript'>\n");
        $this->out("    refresh_opener();\n");
        $this->out("</script>\n");

        $r_msg = DBFindE("msg", "sysnum=$this->Numer", "", __LINE__);

        $this->out("<form method='post' name='ScrMsgForm'>");

        $this->SubTable("width='100%' border='0' cellspacing='0' cellpadding='5'"); {
            $this->TRNext("class='toolsbarl'"); {
                $this->TDNext("nowrap"); {

                    $s = $TEMPL[lb_prev];
                    if (($nmsg = $this->PrevNextMsg( -1 )) != 0) {
                        //$s = "<font class='toolsbara'>$s</font>";
                        //$s = "<a href='//$HTTP_HOST$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=".$R_FLD->sysnum()."&Msg=$nmsg'>$s</a>";
                        $s = makeButton("type=2& form=ScrMsgForm& name=sPrevMes& img=$INET_IMG/mailfolderarrowleft-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderarrowleft.gif?FACE=$FACE& title=$TEMPL[bt_prev_ico]& onclick=javascript:location.href = '$INET_SRC" . "$SCRIPT_NAME?UID=$this->UID%26FACE=$FACE%26Fld=".$R_FLD->sysnum()."%26Msg=$nmsg'");
                    } else {
                        $s = "<img src='$INET_IMG/mailfolderarrowleft-unactive.gif' align='absmiddle' alt='$TEMPL[bt_prev_ico]'>";
                    }
                    $this->Out($s);

                    $this->Out($this->TextShift);

                    //$s = $this->MakeFolderLangName($R_FLD->ftype(), $R_FLD->name(), 0);
                    //$s = "<font class='toolsbara'>$s</font>";
                    //$this->Out(" | <a href='//$HTTP_HOST$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=".$R_FLD->sysnum()."'><b>$s</b></a> | ");

                    $s = $TEMPL[lb_next];
                    if (($nmsg = $this->PrevNextMsg( 1 )) != 0) {
                        //$s = "<font class='toolsbara'>$s</font>";
                        //$s = "<a href='//$HTTP_HOST$SCRIPT_NAME?UID=$this->UID&FACE=$FACE&Fld=".$R_FLD->sysnum()."&Msg=$nmsg'>$s</a>";
                        $s = makeButton("type=2& form=ScrMsgForm& name=sNextMes& img=$INET_IMG/mailfolderarrowright-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderarrowright.gif?FACE=$FACE& title=$TEMPL[bt_next_ico]& onclick=javascript:location.href = '$INET_SRC" . "$SCRIPT_NAME?UID=$this->UID%26FACE=$FACE%26Fld=".$R_FLD->sysnum()."%26Msg=$nmsg'");
                    } else {
                        $s = "<img src='$INET_IMG/mailfolderarrowright-unactive.gif' align='absmiddle' alt='$TEMPL[bt_next_ico]'>";
                    }
                    $this->Out($s);

                    $this->out($this->SectionBlank);

                    $this->out( "&nbsp");
                    $this->out( makeButton("type=1& form=ScrMsgForm& name=sReply& img=$INET_IMG/mailfolderreply-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderreply.gif?FACE=$FACE& title=$TEMPL[bt_reply_ico]") . $this->ButtonBlank );
                    // $this->out( "<input type='submit' value='Reply All' name='sReplyAll'  >");
                    $this->out( makeButton("type=1& form=ScrMsgForm& name=sForward& img=$INET_IMG/mailfolderforward-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderforward.gif?FACE=$FACE& title=$TEMPL[bt_forward_ico]") . $this->SectionBlank );

                    $r_fld_s = DBFind("fld", "sysnumusr = $this->UID and (ftype = 1 or ftype = 4) and sysnum <> ".$R_FLD->sysnum() . " order by ftype", "", __LINE__);
                    if ($r_fld_s->NumRows() != 0) {
                        // $this->out("<input type='submit' name='sMsgCopy' value='Copy1' class='toolsbarb'>");
                        $this->OUT("<select name='DestBox' class='toolsbare'>" . "<option value=''>- $TEMPL[chfolder] -");

                        $r_fld_s->set(0);
                        while (!$r_fld_s->Eof()) {
                            $this->out("<option value='".$r_fld_s->sysnum()."'>" . $this->MakeFolderLangName($r_fld_s->ftype(), $r_fld_s->name(), 0));
                            $r_fld_s->Next();
                        }

                        $this->out("</select>" . $this->TextShift);
                        $this->out( makeButton("type=1& form=ScrMsgForm& name=sMsgMove& img=$INET_IMG/mailfoldermovemessage-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfoldermovemessage.gif?FACE=$FACE& title=$TEMPL[bt_move_mes_ico]"));
                    }

                    $this->out($this->SectionBlank);
                    $this->out( makeButton("type=1& form=ScrMsgForm& name=sMsgDelete& img=$INET_IMG/mailfolderdelmessage-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderdelmessage.gif?FACE=$FACE& title=$TEMPL[bt_delete_mes_ico]" ), $this->SectionBlank);
                    $this->out( makeButton("type=1& form=ScrMsgForm& name=sToAddressList& img=$INET_IMG/mailfoldernewcontact-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfoldernewcontact.gif?FACE=$FACE& title=$TEMPL[bt_new_contact]" ), $this->SectionBlank);
                    $this->out( makeButton("type=1& form=ScrMsgForm& name=sPrint& img=$INET_IMG/mailfolderprint-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderprint.gif?FACE=$FACE& title=$TEMPL[bt_print]" ), $this->SectionBlank);
                    $this->out( makeButton("type=2& form=ScrMsgForm& name=sWinClose& img=$INET_IMG/mailfolderclosewindow-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderclosewindow.gif?FACE=$FACE& title=$TEMPL[bt_close_window_ico]& onclick=javascript:window.close()" ));
                } // TDNext
            } // TRNext
        } $this->SubTableDone();

        $this->out( "<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("width='100%' border=0 class='tab' cellspacing=0 cellpadding=0 grborder"); {
            $this->tds(2, 0, "class='ttp' width='20%'",  "<b>$TEMPL[lb_from] :</b>");

            // $this->tds(3, 0, "class='tlp' valign='top'", $this->nbsp(htmlspecialchars(URLDecode($r_msg->addrfrom()))));
            $titl_ =  htmlspecialchars(URLDecode($r_msg->addrfrom()));
            $this->tds(3, 0, "class='tlp' valign='top' nowrap", "&nbsp;&nbsp;<span title='$titl_'>" . ReformatToLeft(URLDecode($r_msg->addrfrom()), 40) . "</span>&nbsp;&nbsp;");

            $this->tds(2, 1, "class='ttp' width='20%'",  "<b>$TEMPL[lb_to] :</b>");

            //$this->tds(3, 1, "class='tlp' valign='top'", $this->nbsp(htmlspecialchars(URLDecode($r_msg->addrto()))));
            $titl_ =  htmlspecialchars(URLDecode($r_msg->addrto()));
            $this->tds(3, 1, "class='tlp' valign='top' nowrap", $this->TextShift . "<span title='$titl_'>" . ReformatToLeft(URLDecode($r_msg->addrto()), 40) . "</span>" . $this->TextShift);

            //$this->tds(2, 2, "class='ttp' width='15%'",  "<b>CC :</b>");
            //$this->tds(3, 2, "class='tlp' valign='top'", $this->nbsp(""));

            $this->tds(2, 3, "class='ttp' width='45%'",  "<b>$TEMPL[lb_subject] :</b>");

            $titl_ =  htmlspecialchars(URLDecode($r_msg->subj()));
            $this->tds(3, 3, "class='tlp' valign='top'", $this->TextShift . "<span title='$titl_'>" . ReformatToLeft(URLDecode($r_msg->subj()), 40) . "</span>" . $this->TextShift);

            $this->tds(2, 4, "class='ttp' width='15%'",  "<b>$TEMPL[lb_date] :</b>");

            $this->tds(3, 4, "class='tlp' valign='top' nowrap", $this->TextShift . $this->nbsp(mkdatetime($r_msg->send())) . $this->TextShift);
            // $this->tds(2, 2, "",             mkdatetime($r_msg->send()));

        } $this->SubTableDone();

        $this->out( "</form>" );

        if(eregi ("MSIE|Mozilla/5", $HTTP_USER_AGENT)) {
            $this->out( "<Center>" );
            $this->out( "<IFRAME src='$INET_SRC/view_message.php?UID=$this->UID&FACE=$FACE&Fld=" . $r_msg->sysnumfld() . "&Msg=" . $r_msg->sysnum() . "' width='98%' HEIGHT='350px' class='body' style='border: 1px solid'>Need upgrade yours browser</IFRAME>" );
            $this->out( "</Center>" );
        } else {
            $this->out("<hr>");
            $this->SubTable("style='background-color: #99ccff' width='100%'"); {
                $this->rrr = "2";
                if ($r_msg->Content() == "TEXT/HTML") {
                    $this->out(ParseMesHTML(URLDecode($this->GetMessageBody($r_msg->sysnum())), $this->UID));
                } else {
                    $this->out(ParseMesText(URLDecode($this->GetMessageBody($r_msg->sysnum())), $this->UID));
                }
            } $this->SubTableDone();
        }

        $r_fs = DBFind('fs, file', "fs.ftype = 'a' and up=$this->Numer and fs.sysnumfile = file.sysnum", "fs.sysnum, fs.sysnumfile, fs.name, file.ftype, file.fsize", __LINE__);
        if ($r_fs->NumRows() != 0) {
            $this->out("<hr>");

            $TotalCount = 0;
            $TotalSize  = 0;

            $this->out("<form method='post' name='attachfilesform'>");

            $this->out( "<b>$TEMPL[attachment]&nbsp;:</b>" , $this->TextShift ,
                        makeButton("type=1&  form=attachfilesform& name=sCopy&        img=$INET_IMG/mailfoldercopyftp-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfoldercopyftp.gif?FACE=$FACE& title=$TEMPL[bt_copy_my_ftp_ico]") , $this->TextShift ,
                        makeButton("type=1&  form=attachfilesform& name=sDownloadZip& img=$INET_IMG/downloadzip-passive.gif?FACE=$FACE&       imgact=$INET_IMG/downloadzip.gif?FACE=$FACE&       title=$TEMPL[bt_dwnl_zip_ico]"), "<br>"
            );
            // $this->out( "<input type='submit' name='sCopyAdd' value='Copy add' class='toolsbarb'>");
            // $this->out( "<input type='submit' name='sToFileFolder' value='To My Files' class='toolsbarb'>");

            $this->out( "<img src='$INET_IMG/filler3x1.gif'>");

            $this->SubTable("border=0 CELLSPACING=0 CELLPADDING=0 width='100%' class='tab' grborder"); {
                $this->trs(0, "align='center'"); {
                    $this->tds(0, 1, "class='ttp'",             "<input type='checkbox' name='TagATTAll' title='$TEMPL[select_all_ico]' onClick='javascript:onTagATTAllClick()'>");
                    $this->tds(0, 2, "class='ttp'",             "&nbsp");
                    $this->tds(0, 3, "width='50%' class='ttp'", $TEMPL[lb_file_name]);
                    $this->tds(0, 4, "class='ttp'",             "E");
                    $this->tds(0, 5, "width='30%' class='ttp'", $TEMPL[lb_type]);
                    $this->tds(0, 6, "width='20%' class='ttp'", $TEMPL[lb_size]);

                    /*
                    $this->tds(0, 6, "width='20%', align='center', valign = 'top', rowspan='" . ($r_fs->NumRows()+1) . "'", "");;
                    $this->out( "<input type='submit' name='sCopy'    value='Copy' class='toolsbarb'> ");
                    $this->out( "<input type='submit' name='sCopyAdd' value='Copy add' class='toolsbarb'>");
                    */
                } // trs

                for($i=1; ! $r_fs->EOF(); $i++) {
                    $this->trs($i, ""); {

                        $TotalCount += 1;
                        $TotalSize  += $r_fs->fsize();

                        $this->tds($i, 1, "class='tlp'",              "<input type='checkbox' name='TagATT[" . $TagATTCount++ . "]' value='".$r_fs->sysnum()."' onclick='javascript:onTagATTClick()'>");

                        $titl_ =  htmlspecialchars($r_fs->name());
                        $this->tds($i, 2, "class='tlp'",              "<a href='" . MakeOwnerFileDownloadURL($r_fs->name(), $r_fs->sysnum(), $this->UID, 2) . "' target='_blank' title='$TEMPL[view_file_ico]'><img src='$INET_IMG//view.gif' border=0></a>");

                        $this->tds($i, 3, "class='tlp'",              $this->TextShift . "<a href='" . MakeOwnerFileDownloadURL($r_fs->name(), $r_fs->sysnum(), $this->UID, 1) . "'><span title='$titl_'><font class='tlpa'><b>".ReformatToLeft($r_fs->name(), 50)."</b></font></span></a>" . $this->TextShift);

                        $r_nlink = DBExec("select count(*) as num from fs where sysnumfile = '" . $r_fs->sysnumfile() . "' and ftype = 'f' and owner = '$this->UID'", __LINE__);

                        $this->tds($i, 4, "class='tlp' center",       $this->TextShift . ($r_nlink->num() != 0 ? $r_nlink->num() : "N")) . $this->TextShift;

                        $this->tds($i, 5, "class='tlp'",              $this->TextShift . ($r_fs->ftype() != "" ? $r_fs->ftype() : "[N/A]")) . $this->TextShift;

                        $s = $r_fs->fsize();
                        $s1 = AsSize($s);
                        $this->tds($i, 6, "class='tlp' align='right' nowrap",  $this->TextShift . "<span title='$s'>$s1</span>" . $this->TextShift);
                    } // trs

                    $r_fs->Next();
                }


                $this->trs($i, ""); {
                    $this->tds($i, 1, "class='tlp'",               "&nbsp;");
                    $this->tds($i, 2, "class='tlp'",               "&nbsp;");
                    $this->tds($i, 3, "class='tlp'",               $this->TextShift . "<b>$TotalCount $TEMPL[cnt_files]</b>" . $this->TextShift);
                    $this->tds($i, 4, "class='tlp'",               "&nbsp;");
                    $this->tds($i, 5, "class='tlp' align='right'", $this->TextShift . "<b>$TEMPL[cnt_size]</b>" . $this->TextShift);
                    $s = $TotalSize;
                    $s1 = AsSize($s);
                    $this->tds($i, 6, "class='tlp' align='right' nowrap", $this->TextShift . "<span title='$s'><b>$s1</b></span>" . $this->TextShift);
                } // trs
            } $this->SubTableDone();

            $this->out("<hr>");
            $this->out( "</form>" );
        }
    }


    function ScrAddressList()
    {
        global $s_MailFolder, $View;
        global $TEMPL;
        global $INET_IMG;


        if ( is_array($s_MailFolder[Status][NameAddress]) || is_array($s_MailFolder[Status][MailAddress]) ) {
            reset($s_MailFolder[Status][NameAddress]);
            while(list($n, $v) = each($s_MailFolder[Status][NameAddress])){
                $AddresList[$n][name] = $v;
            }
            reset($s_MailFolder[Status][MailAddress]);
            while(list($n, $v) = each($s_MailFolder[Status][MailAddress])){
                $AddresList[$n][addr] = $v;
            }
        } else {
            $r_msg = DBFindE("msg", "sysnum=$this->Numer", "", __LINE__);
            ParseAddressesList(URLDECODE($r_msg->AddrTo()) . ";" . URLDECODE($r_msg->AddrFrom()),   $AddresList);
        }

        $this->out("<form method='post' name='addresslistform'>");

        $this->SubTable("width='100%' border='0' cellspacing='0' cellpadding='5'"); {
            $this->TRNext("class='toolsbarl'"); {
                $this->TDNext("nowrap"); {
                    $this->Out(makeButton("type=1&  name=sAddressListSave& value=Save& class=toolsbarb") . $this->ButtonBlank);
                    $this->Out(makeButton("type=1&  name=sAddressListExit& value=Exit& class=toolsbarb"));
                }
            }
        } $this->SubTableDone();

        $this->Out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("border=0 width='100%' CELLSPACING=0 CELLPADDING=0 grborder"); {
            $this->TRNext(); {
                $this->TDNext("class='ttp' width='5%'"); {
                    $this->out("<input type='checkbox'>");
                }
                $this->TDNext("class='ttp' width='45%'"); {
                    $this->out("name");
                }
                $this->TDNext("class='ttp' width='50%'"); {
                    $this->out("addr");
                }
            }

            if (is_array($AddresList)) {
                while ( list($n, $v) = each($AddresList) ) {
                    $this->TRNext(); {
                        $r_address = DBExec("SELECT * FROM address WHERE sysnumusr = $this->UID AND mailto = '" . urlencode($v[addr]) . "'");

                        if ($r_address->NumRows() == 0) {
                            $FOUND = 0;
                            $CLASS = "tla";
                        } else {
                            $FOUND = 1;
                            $CLASS = "tlp";
                        }

                        if ($v[name] == "") {
                            if(preg_match("/^[^@]+/", $v[addr], $MATH)) {
                                $v[name] = $MATH[0];
                            } else {
                                $v[name] = $v[addr];
                            }
                        }

                        $this->TDNext("class='$CLASS' align='center'"); {
                            if (!$FOUND) {
                                $CHECKED = "";
                                if ($s_MailFolder[Status][CheckAddress][$n] == 1) {
                                    $CHECKED = "CHECKED";
                                }
                                $this->out("<input type='checkbox' name='CheckAddress[$n]' value='1' $CHECKED>");
                            } else {
                                $this->out("&nbsp;");
                            }
                        }

                        $this->TDNext("class='$CLASS'"); {
                            if (!$FOUND) {
                                $this->out("&nbsp;<input type='text' name='NameAddress[$n]' value=\"" . $this->nbsp(HtmlSpecialChars($v[name])) . "\" size=20>&nbsp;");
                            } else {
                                $this->out("&nbsp;" . HtmlSpecialChars($r_address->name()) . "&nbsp;");
                                $this->out("&nbsp;<input type='hidden' name='NameAddress[$n]' value=\"" . $this->nbsp(HtmlSpecialChars($v[name])) . "\">");
                            }
                        }

                        $this->TDNext("class='$CLASS'"); {
                            $this->out("&nbsp;" . $this->nbsp(HtmlSpecialChars($v[addr])));
                            $this->out("<input type='hidden'   name='MailAddress[$n]' value=\"" . HtmlSpecialChars($v[addr]) . "\">");
                        }

                    } // TRNext
                } // while
            } // if

        } $this->SubTableDone();

        $this->out( "</form>" );

        unset($s_MailFolder[Status][CheckAddress]);
        unset($s_MailFolder[Status][NameAddress]);
        unset($s_MailFolder[Status][MailAddress]);
    }


    function ScrPrint()
    {
    }


    function ToolsBar()
    {
    }


    function Tools()
    {
    }


    function PrevNextMsg($n)
    {
        global $R_FLD;


        $res1 = DBFIND("msg", "sysnum = $this->Numer", "", __LINE__);

        $sysnum    = $res1->sysnum();
        $sysnumfld = $res1->sysnumfld();

        $sort      = $this->FieldToSort($n);
        $value     = $res1->field($sort[field]);

        if ($R_FLD->sort_ <= "Z") { // Letter to Upcase
            $n = -$n;
        }

        if ($n >= 0) {
            $res = DBFIND("msg", "sysnumfld = $sysnumfld and ($sort[field] > '$value' or ($sort[field] = '$value' and sysnum > $sysnum)) order by $sort[key] LIMIT 1", "", __LINE__);
        } else {
            $res = DBFIND("msg", "sysnumfld = $sysnumfld and ($sort[field] < '$value' or ($sort[field] = '$value' and sysnum < $sysnum)) order by $sort[key] LIMIT 1", "", __LINE__);
        }

        if ($res->NumRows() == 0) {
            return 0;
        } else {
            return $res->sysnum();
        }
    }


    function FieldToSort($direct)
    {
        global $R_FLD;

        $ftype = $R_FLD->ftype();

        $sort[field] = "sysnum";

        // if ($R_FLD->sort_ <= "Z" || $direct < 0) { // Letter to Upcase
        //     $desc = " desc";
        // } else {
        //     $desc = "";
        // }

        if ( ($R_FLD->sort_ <= "Z" && $direct >= 0) || ($R_FLD->sort_ > "Z" && $direct < 0) ) { // Letter to Upcase
            $desc = " desc";
        } else {
            $desc = "";
        }

        switch ($R_FLD->sort_) {
            case "A" :
            case "a" : ($ftype == 1 || $ftype == 4) ? $sort[field] = "addrfrom"   : $sort[field] = "addrto";   break;

            case "D" :
            case "d" : ($ftype == 1 || $ftype == 4) ? $sort[field] = "send"       : $sort[field] = "recev";    break;

            case "S" :
            case "s" : $sort[field] = "subj";                                                                  break;

            case "N" :
            case "n" : $sort[field] = "fnew";                                                                  break;
        }

        $sort[key] = "$sort[field] $desc, sysnum $desc";

        return $sort;
    }



    function JmpToFolder()
    {
        global $ShowBox, $Fld;
        global $s_MailFolder;

        if($ShowBox == "") {
            $s_MailFolder[Mes] = 1;
            $this->refreshScreen();
        }

        if(!ereg("^[0-9]+$", $ShowBox)) {
            $s_MailFolder[Mes] = 2;
            $this->refreshScreen();
        }

        $r_fld = DBExec("SELECT * FROM fld WHERE sysnum = '$ShowBox' AND sysnumusr = '$this->UID'");
        if ($r_fld->NumRows() == 0) {
            $s_MailFolder[Mes] = 2;
            $this->refreshScreen();
        }

        $Fld = $ShowBox;
        $this->refreshScreen();
    }


    function SaveRefreshStatus()
    {
        global $TagMSG;
        global $s_MailFolder;

        $this->refreshScreen();
    }


    function NewView()
    {
        global $s_MailFolder;

        $s_MailFolder = array();
        $this->refreshScreen();
    }


    function MsgsMove()
    {
        global $TagMSG, $DestBox, $Fld;
        global $s_MailFolder;


        if($DestBox == "") {
            $s_MailFolder[Mes] = 1;
            $this->refreshScreen();
        }

        if(!ereg("^[0-9]+$", $DestBox)) {
            $s_MailFolder[Mes] = 2;
            $this->refreshScreen();
        }

        $r_fld = DBExec("SELECT * FROM fld WHERE sysnum = '$DestBox' AND sysnumusr = '$this->UID'");
        if ($r_fld->NumRows() == 0) {
            $s_MailFolder[Mes] = 2;
            $this->refreshScreen();
        }

        $Dest = $DestBox;

        if (!is_array($TagMSG) && (count($TagMSG) == 0)) {
            $s_MailFolder[Mes] = 3;
            $this->refreshScreen();
        }

        reset($TagMSG);
        $s = "";
        while (list($n, $v) = each($TagMSG)) {
            if(!ereg("^[0-9]+$", $v)) {
                $s_MailFolder[Mes] = 4;
                $this->refreshScreen();
            }
            $s .= (($s != "") ? " or " : "") . "sysnum = '$v'";
        }

        $s = "update msg set sysnumfld = $Dest where ($s) AND sysnumfld = '$Fld'";
        DBExec($s, __LINE__);

        $this->refreshScreen();
    }



    function MsgsDelete()
    {
        global $TagMSG, $R_FLD, $Fld;
        global $s_MailFolder;

        if (!is_array($TagMSG) && (count($TagMSG) == 0)) {
            $s_MailFolder[Mes] = 3;
            $this->refreshScreen();
        }

        reset($TagMSG);
        while (list($n, $v) = each($TagMSG)) {
            if(!ereg("^[0-9]+$", $v)) {
                $s_MailFolder[Mes] = 4;
                $this->refreshScreen();
            }
            $s .= (($s != "") ? " or " : "") . "sysnum = '$v'";
        }

        if ($R_FLD->ftype() == 5) {
            $r_msg = DBExec("SELECT * FROM msg WHERE ($s) AND sysnumfld = '$Fld'");

            while (! $r_msg->Eof()) {
                DelMsg($r_msg->sysnum());
                $r_msg->Next();
            }

            $this->refreshScreen();
        }

        $r_fld = DBFind("fld", "sysnumusr = $this->UID and ftype = 5", "", __LINE__);
        $s = "UPDATE msg SET sysnumfld = " . $r_fld->sysnum() . " WHERE ($s) AND sysnumfld = '$Fld'";
        DBExec($s, __LINE__);

        $this->refreshScreen();
    }


    function MsgMove()
    {
        global $R_FLD;
        global $INET_SRC;
        global $DestBox, $Fld, $Msg;

        if ($Msg == 0) {
            $s_MailFolder[Mes] = 4;
            $this->refreshScreen();
        }

        $nmsg = $this->PrevNextMsg(1);
        if ($nmsg == 0) {
            $nmsg = $this->PrevNextMsg(-1);
        }

        if($DestBox == "") {
            $s_MailFolder[Mes] = 1;
            $this->refreshScreen();
        }

        if(!ereg("^[0-9]+$", $DestBox)) {
            $s_MailFolder[Mes] = 2;
            $this->refreshScreen();
        }

        $r_fld = DBExec("SELECT * FROM fld WHERE sysnum = '$DestBox' AND sysnumusr = '$this->UID'");
        if ($r_fld->NumRows() == 0) {
            $s_MailFolder[Mes] = 2;
            $this->refreshScreen();
        }

        $s = "update msg set sysnumfld = $DestBox where sysnum = $this->Numer";
        DBExec($s, __LINE__);

        if ($nmsg == 0) {
            echo "<html>\n";
            echo "<body>\n";
            echo "<script language='javascript' src='$INET_SRC/mail_folder.js'></script>\n";
            echo "<script language='javascript'>\n";
            echo "  refresh_opener();\n";
            echo "  window.close();\n";
            echo "</script>\n";
            echo "</body>\n";
            echo "</html>\n";
            exit;
        }

        $Msg = $nmsg;
        $this->refreshScreen();
    }



    function MsgDelete()
    {
        global $R_FLD;
        global $INET_SRC;
        global $Fld, $Msg;

        if ($Msg == 0) {
            $s_MailFolder[Mes] = 4;
            $this->refreshScreen();
        }

        $nmsg = $this->PrevNextMsg(1);
        if ($nmsg == 0) {
            $nmsg = $this->PrevNextMsg(-1);
        }

        if ($R_FLD->ftype() == 5) {
            DelMsg($this->Numer);
        } else {
            $r_fld = DBFind("fld", "ftype = 5 and sysnumusr = $this->UID", "", __LINE__);
            if ($r_fld->NumRows() == 0) {
                DelMsg($this->Numer);
            } else {
                DBExec("update msg set sysnumfld = ".$r_fld->sysnum()." where sysnum = $this->Numer", __LINE__);
            }
        }

        if ($nmsg == 0) {
            echo "<html>\n";
            echo "<body>\n";
            echo "<script language='javascript' src='$INET_SRC/mail_folder.js'></script>\n";
            echo "<script language='javascript'>\n";
            echo "  refresh_opener();\n";
            echo "  window.close();\n";
            echo "</script>\n";
            echo "</body>\n";
            echo "</html>\n";
            exit;
        }

        $Msg = $nmsg;
        $this->refreshScreen();
    }


    function CopyAttach()
    {
        DBExec("delete from clip where owner = '$this->USRNAME'", __LINE__);
        $this->CopyAddAttach();
        $this->ToFileFolder();
    }


    function CopyAddAttach()
    {
        global $DBConn;
        global $TagATT;

        if (!is_array($TagATT)) {
            $this->refreshScreen();
        }

        while (list($n, $v) = each($TagATT)) {
            $s .= ($s != "" ? " or " : "") . "fs.sysnum = $v";
        }

        $this->out( "$s<br>" );

        $r_fs = DBExec("select * from fs where ($s) and (sysnum not in (select sysnumfs from clip where owner='$this->USRNAME'))", __LINE__);
        While (!$r_fs->EOF()) {
            $this->out( $r_fs->sysnum()."<br>" );
            DBExec("insert into clip (sysnumfs, owner, ftype) values (".$r_fs->sysnum().", '$this->USRNAME', 'c')", __LINE__);
            $r_fs->Next();
        }
    }

    function DownloadZip()
    {
        global $DBConn, $Mes, $PROGRAM_FILES, $PROGRAM_TMP, $INET_SRC, $FACE;
        global $TagATT, $s_MailFolder, $REMOTE_ADDR;

        if (!is_array($TagATT)) {
            $this->refreshScreen();
        }

        $RootPath = "$PROGRAM_TMP/dwnl_dir_" . posix_getpid() . "_" . time();
        if (!mkdir($RootPath, 0777)) {
            $s_MailFolder[Mes] = 15;
            $this->refreshScreen();
        }

        while (list($n, $v) = each($TagATT)) {
            $s .= ($s != "" ? " or " : "") . "fs.sysnum = $v";
        }

        $r_fs = DBExec("SELECT fs.name, fs.sysnum, fs.sysnumfile, file.numstorage FROM fs, file WHERE ($s) AND fs.sysnumfile = file.sysnum AND fs.owner = " . $this->UID, __LINE__);
        $i = 1;
        While (!$r_fs->EOF()) {
            #Debug("2 " . $r->name());
            if(!@symlink($PROGRAM_FILES . "/storage" . $r_fs->numstorage() . "/" . $r_fs->sysnumfile(), $RootPath. "/" . $r_fs->name())) {
                @symlink($PROGRAM_FILES . "/storage" . $r_fs->numstorage() . "/" . $r_fs->sysnumfile(), $RootPath. "/" . "[" . $i++ ."]_" . $r_fs->name());
                #echo php3_error(), "<br>";
            }
            $r_fs->Next();
        }

        $ZipName = "$PROGRAM_TMP/dwnl_zip_" . posix_getpid() . "_" . time();
        system("cd $RootPath; zip -uqr $ZipName *");

        system("rm -rf $RootPath");
        #system("rm -rf $ZipName.zip");

        DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('" . $this->UID . "', '" . $this->USR->sysnumdomain() . "', 'downzip', datetime('now'::abstime), '" . filesize($ZipName . ".zip") . "', '0', '" . substr($this->USRNAME, 0, 20) . "', -1, '$REMOTE_ADDR')", __LINE__);

        header("Location: $INET_SRC/view_file.php/download.zip?UID=$this->UID&FACE=$FACE&DownloadZip=". urlencode(basename ("$ZipName.zip")));
        exit;
    }


    function ToFileFolder()
    {
        global $Fld, $Msg;
        global $INET_SRC, $FACE;
        global $s_FileFolder;

        session_register("s_FileFolder");

        $s_FileFolder[Ret] = "MES#$Fld#$Msg";

        header("Location: $INET_SRC/file_folder.php?UID=$this->UID&FACE=$FACE");
        exit;
    }


    function ToAddressList()
    {
        global $View;

        $View = "AddressList";
        $this->refreshScreen();
    }


    function AddressListSave()
    {
        global $s_MailFolder;
        global $CheckAddress, $NameAddress, $MailAddress;

        $Status = $s_MailFolder[Status];

        if (!is_array($Status[CheckAddress]) || count($Status[CheckAddress]) == 0) {
            $s_MailFolder[Mes] = 5;
            $this->refreshScreen();
        }

        if (is_array($Status[CheckAddress])) {
            reset($Status[CheckAddress]);
            while( list($n, $v) = each($Status[CheckAddress]) ) {
                $name   = $Status[NameAddress][$n];
                $mailto = $Status[MailAddress][$n];

                if (strpos($name, " ")) {
                    $lastname  = preg_replace("/^(\S+?)\s+(.*)/i", "\\2", $name);
                    $name      = preg_replace("/^(\S+?)\s+(.*)/i", "\\1", $name);
                    if (strpos($lastname, " ")) {
                        $middlename  = preg_replace("/^(\S+?)\s+(.*)/i", "\\1", $lastname);
                        $lastname    = preg_replace("/^(\S+?)\s+(.*)/i", "\\2", $lastname);
                    } else {
                        $middlename = "";
                    }
                } else {
                    $lastname   = "";
                    $middlename = "";
                }



                $name       = urlencode($name);
                $middlename = urlencode($middlename);
                $lastname   = urlencode($lastname);
                $mailto     = urlencode($mailto);
                DBExec("INSERT INTO address (sysnumusr, name, middlename, lastname, mailto) VALUES ('$this->UID', '$name', '$middlename', '$lastname', '$mailto')", __LINE__);
            }
        }

        $this->AddressListExit();
    }


    function AddressListExit()
    {
        global $View, $s_MailFolder;

        unset($s_MailFolder[Status][CheckAddress]);
        unset($s_MailFolder[Status][NameAddress]);
        unset($s_MailFolder[Status][MailAddress]);

        $View = "";
        $this->refreshScreen();
    }


    function rPrint()
    {
        global $View, $INET_SRC, $FACE, $Fld, $Msg;

        header("Location: $INET_SRC/view_message.php?UID=$this->UID&FACE=$FACE&Fld=$Fld&Msg=$Msg&View=Print");
        exit;

        $View = "Print";
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


    function rReply($kind)
	{
        global $s_Compose, $Fld, $Msg, $FACE, $TEMPL;

        $s_Compose = array();

        session_register("s_Compose");

        $r = DBFind("msg", "sysnum = $this->Numer", "", __LINE__);

        // Message
        $mes  = URLDecode($this->GetMessageBody($r->sysnum()));

        // Signature
        $Signature = "";
        $res_fs = DBExec("SELECT * FROM usr_ua WHERE sysnumusr = $this->UID AND name = 'signature' order by nset");
        while(!$res_fs->eof()) {
            $Signature .= $res_fs->value();
            $res_fs->next();
        }
        $Signature = URLDecode($Signature);

        if ($r->content() == "TEXT/HTML") {
            if (preg_match("/<[^<]*>/", $Signature)) {
                $Signature = "<br><br>" . preg_replace("/\r?\n/", "", $Signature);
            } else {
                $Signature = "<br><br>" . nl2br($Signature);
            }

            $TMP  = "<br>";
            $TMP .= "$TEMPL[rpl_original_message]<br>";
            $TMP .= "<b>$TEMPL[rpl_to]</b>&nbsp:&nbsp" . URLDecode($r->addrto()) . "<br>";
            $TMP .= "<b>$TEMPL[rpl_from]</b>&nbsp:&nbsp" . URLDecode($r->addrfrom()) . "<br>";
            $TMP .= "<b>$TEMPL[rpl_subject]</b>&nbsp:&nbsp" . URLDecode($r->subj()) . "<br><hr>";
            $mes = preg_replace("'(.*<\s*?body[^>]*?>)(.*?)(<\s*?/\s*?body[^>]*?>.*)'si", "\\1<div>&nbsp;</div><BLOCKQUOTE>$TMP\\2</BLOCKQUOTE>$Signature\\3", $mes);
        } else {
            if (preg_match("/<[^<]*>/", $Signature)) {
                $Signature = preg_replace(array("'<script[^>]*?>.*?</script>'si",  "'</tr>'i", "'<img[^>]*?>'si"), array("", "<br>", "[IMAGE]"), $Signature);
                $Signature = strip_tags($Signature, "<br><p><img>");
                $Signature = preg_replace(array( "'\n'", "'<br>'i", "'<p>'i", "'&nbsp;?'i" ),
                                          array( "",     "\n",      "\n",     " "          ), $Signature);
            }
            $TMP = "$TEMPL[rpl_original_message]\n";
            $TMP .= "$TEMPL[rpl_to] : " . URLDecode($r->addrto()) . "\n";
            $TMP .= "$TEMPL[rpl_from] : " . URLDecode($r->addrfrom()) . "\n";
            $TMP .= "$TEMPL[rpl_subject] : " . URLDecode($r->subj()) . "\n";
            $mes = "\n\n$TMP\n>" . preg_replace("'(\n)'si", "\\1>", $mes) . $Signature;
        }

        $s_Compose[Status][fMessage] = $mes;

        // Content Type
        $s_Compose[Status][fConType]         = $r->content();
        $s_Compose[Status][fMessage_CharSet] = $r->charset();

        // Subject
        $subj = URLDecode($r->subj());
        $s_Compose[Status][1] = $subj;

        $subj = (($kind == 1) ? "Re: " : "") . $subj;
        $subj = (($kind == 2) ? "Fd: " : "") . $subj;
        $s_Compose[Status][fSubj] = $subj;

        // Field TO
        if ($kind == 1) {
            $s_Compose[Status][fTO] = URLDecode($r->addrfrom());
        }

        // Attachment Files
        if ($kind != 1) {
            $r = DBFind("fs", "up = $this->Numer and ftype = 'a'", "", __LINE__);
            while (!$r->Eof()) {
                $s_Compose[Status][fTblAttach][$r->sysnum()] = $r->sysnum();
                $r->Next();
            }
        }

        $s_Compose[Ret] = "MES#$Fld#$Msg";

        //$this->Log("rReply($kind)\n" . sharr($s_Compose[Status]));

        header("Location: $INET_SRC/compose.php?UID=$this->UID&FACE=$FACE");
        exit;
	}


    function mes()
    {
        global $Mes, $MesParam, $s_MailFolder, $TEMPL;

        if ($Mes == "") {
            $Mes = $s_MailFolder[Mes];
            unset($s_MailFolder[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_MailFolder[MesParam];
            unset($s_MailFolder[MesParam]);
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


    function GetMessageBody($sysnummsg)
    {
        $ret = "";
        $r_msgbody = DBFind("msgbody", "sysnummsg='$sysnummsg' order by sysnum", "", __LINE__);
        // Debug("body ".$r_msgbody->NumRows());
        while( !$r_msgbody->Eof()) {
            $ret .= $r_msgbody->body();
            $r_msgbody->Next();
        }
        return $ret;
    }


    function MakeFolderLangName($FolderType, $OriginalFolderName, $toPageTitle)
    {
       global $TEMPL;

        switch ($FolderType) {
            case 1: // Inbox
                $FldName = $toPageTitle ? $TEMPL[title_inbox] : $TEMPL[fd_inbox];
                break;
            case 2: // Sent items
                $FldName = $toPageTitle ? $TEMPL[title_sent]  : $TEMPL[fd_sent];
                break;
            case 5: // Trash
                $FldName = $toPageTitle ? $TEMPL[title_trash] : $TEMPL[fd_trash];
                break;
            case 6: // Trash
                $FldName = $toPageTitle ? $TEMPL[title_notif] : $TEMPL[fd_notif];
                break;
            default:
                $FldName = $OriginalFolderName;
        }

        //$this->out("$FolderType $OriginalFolderName $FldName =<br>");

        return $FldName;
    }


    function script()
    {
        global $INET_SRC;
        parent::script();
        echo "<script language='javascript' src='$INET_SRC/mail_folder.js'></script>\n";
    }


    function DisplayHeader()
    {
        global $View;

        if ($View != "Print") {
            parent::DisplayHeader();
        }
    }


    function refreshScreen()
    {
        global $FACE, $Fld, $Msg, $View, $NPage;
        global $SCRIPT_NAME;

        $URL = "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE";
        if ($Fld != "") {
            $URL .= "&Fld=" . URLENCODE($Fld);
        }
        if ($Msg != "") {
            $URL .= "&Msg=" . URLENCODE($Msg);
        }
        if ($View != "") {
            $URL .= "&View=" . URLENCODE($View);
        }
        if ($NPage != "" && $NPage != 0) {
            $URL .= "&NPage=" . URLENCODE($NPage);
        }

        parent::refreshScreen($URL);
    }


    function AccessRefused()
    {
        echo "<HTML> <HEAD> <TITLE>Error</TITLE> </HEAD>" .
        "<BODY><TABLE align=center border=0 cellPadding=10 cellSpacing=10 bgColor=lavender><TR><TD bgColor=deeppink><P align=center><FONT color=black>ACCESS REFUSED</FONT></P></TD></TR></TABLE></BODY></HTML>";
        exit;
    }

} // end defunision of class CMailFolderScreen


ConnectToDB();

$MailFolderScreen = new CMailFolderScreen();
$MailFolderScreen->run();

UnconnectFromDB();
exit;
?>
