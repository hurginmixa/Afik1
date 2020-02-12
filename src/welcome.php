<?php


/*
class CWelcomeScreen extends screen {
  var $WidthTools = 15;

  function CWelcomeScreen()
  function mes()
  function ToolsBar()
  function Scr()
  function Tools()
  function CreateFolder()
  function RenameFolder()
  function DeleteFolder()
*/




include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("screen.inc.php");


class CWelcomeScreen extends screen {
    var $WidthTools = 15;

    function CWelcomeScreen()
    {
        global $PROGRAM_SRC, $TEMPL;

        $this->screen(); // inherited constructor
        $this->SetTempl("welcome");

        $this->PgTitle = "<b>$TEMPL[title]</b>";

        $this->r_fld     = DBFind("fld", "sysnumusr = $this->UID order by sysnum", "", __LINE__);
        $this->r_msg     = DBFind("msg, fld", "msg.sysnumfld = fld.sysnum and fld.sysnumusr = $this->UID group by fld.sysnum", "fld.sysnum, count(msg.sysnum) as count, sum(sign(msg.fnew)) as fnew, sum(msg.size) as fsize", __LINE__);
        $this->r_fil     = DBFind("file, fs, msg, fld", "file.sysnum = fs.sysnumfile and fs.ftype = 'a' and fs.up = msg.sysnum and msg.sysnumfld = fld.sysnum and fld.sysnumusr = $this->UID group by fld.sysnum", "fld.sysnum, count(file.sysnum) as count, sum(file.fsize) as fsize", __LINE__);

        $this->Request_actions["sCreate"]       = "CreateFolder()";
        $this->Request_actions["sRename"]       = "RenameFolder()";
        $this->Request_actions["sDelete"]       = "DeleteFolder()";
    }

    function mes()
    {
        global $Mes, $TEMPL;

        switch ($Mes) {
            case 1  : $this->ErrMes($TEMPL[errmes1]);  break;
            case 2  : $this->ErrMes($TEMPL[errmes2]);  break;
            case 3  : $this->ErrMes($TEMPL[errmes3]);  break;
            case 4  : $this->ErrMes($TEMPL[errmes4]);  break;
            case 5  : $this->ErrMes($TEMPL[errmes5]);  break;
        }
    }


    function ToolsBar()
    {
        global $NameFolder, $INET_IMG, $TEMPL, $FACE;
        $r_fld = $this->r_fld;
        $r_msg = $this->r_msg;
        $r_fil = $this->r_fil;

        $this->out("<form method='POST' name='mainform'>");  // close in function Scr

        $this->SubTable("border=0 width='100%' cellspacing = '0' cellpadding = '3' class='toolsbarb'"); {
            $this->TRNext("class='toolsbarl'");
            //$this->TDNext("width='" . $this->WidthTools. "%' nowrap");
            //  $this->out("&nbsp");
            $this->TDNext("nowrap"); {
                $this->out("<input name='NameFolder' value=\"". htmlspecialchars ($NameFolder)."\" class='toolsbare'>&nbsp;&nbsp;");
                $this->out(makeButton("type=1& form=mainform& name=sCreate& class=toolsbarbg& img=$INET_IMG/mailfoldercreate-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfoldercreate.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_new_folder_ico]") ."&nbsp;&nbsp;");
                if ($r_fld->Find("ftype", 4) >= 0) {
                    //$this->out("<input type='submit' value='Rename Folder' name='sRename' class='toolsbarb'  style=\"width : 120; background-image: url($INET_IMG/letter-close.gif); background-position: left; background-repeat: no-repeat; padding-left: 12px\" title='Click To Rename Exiting Customized Folder'>");
                    $this->out(makeButton("type=1&  form=mainform& name=sRename& class=toolsbarbg& img=$INET_IMG/mailfolderrename-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderrename.gif?FACE=$FACE& imgalign=absmiddle& title=$TEMPL[bt_ren_folder_ico]"));
                }
                if ($r_fld->Find("ftype", 4) >= 0) {
                    $this->out($this->SectionBlank);
                    //$this->out("<input type='submit' value='Delete Folder' name='sDelete' class='toolsbarb' style='width : 120; background-image: url($INET_IMG/delete1.gif); background-position: left; background-repeat: no-repeat;' title='Click To Delete Exiting Customized Folder'>");
                    $this->out(makeButton("type=1&  form=mainform& name=sDelete& class=toolsbarbg& img=$INET_IMG/mailfolderdelete-passive.gif?FACE=$FACE& imgact=$INET_IMG/mailfolderdelete.gif?FACE=$FACE& imgalign=absmiddle&  title=$TEMPL[bt_del_folder_ico]"));
                }
            }
            $this->TDNext("width='100%' nowrap"); {
                $this->out("&nbsp;");
            }
        } $this->SubTableDone();
    }


    function Scr()
    {
        global $INET_IMG, $INET_SRC, $TEMPL, $FACE;

        $r_fld     = $this->r_fld;
        $r_msg     = $this->r_msg;
        $r_fil     = $this->r_fil;
        $TotalSize = 0;
        #$r_msgbody = $this->r_msgbody;

        $this->out("<script language='javascript'>\n");
        $this->out("function refresh()\n");
        $this->out("  {\n");
        $this->out("    document.location = document.location;\n");
        $this->out("  }\n");
        $this->out("  setTimeout(\"refresh();\", 5 * 60 * 1000);\n\n");
        $this->out("</script>");

        $r = DBFind("fld", "sysnumusr = $this->UID and fnew > 0", "", __LINE__);
        if ($r->NumRows() > 0) {
            //$this->out("<embed width=\"0\" height=\"0\" src=\"$INET_IMG/tada.wav\" autostart=\"true\" loop=\"False\">");
            //$this->out("<embed width=\"0\" height=\"0\" src=\"$INET_IMG/sabrswg3.wav\" autostart=\"true\" loop=\"False\">");
            DBExec("update fld set fnew = 0 where sysnumusr = $this->UID", __LINE__);
        }

        $this->out("<img src='$INET_IMG/filler2x1.gif'><br>");
        $this->SubTable("border=0 width='100%' cellspacing = '0' cellpadding = '0' class='tab' grborder");

        $this->TRS(0, "height=20"); {
            $this->TDS(0, 0, "width='44%' class='ttp' nowrap",  $this->TextShift . "<font  class='ttp'><b>$TEMPL[cl_folder]</b>              </font>" . $this->TextShift);
            $this->TDS(0, 3, "width='14%' class='ttp' nowrap",  $this->TextShift . "<font  class='ttp'><b>$TEMPL[cl_new_messages]</b>        </font>" . $this->TextShift);
            $this->TDS(0, 5, "width='14%' class='ttp' nowrap",  $this->TextShift . "<font  class='ttp'><b>$TEMPL[cl_messages]</b>            </font>" . $this->TextShift);
            $this->TDS(0, 7, "width='14%' class='ttp' nowrap",  $this->TextShift . "<font  class='ttp'><b>$TEMPL[cl_attach]</b>              </font>" . $this->TextShift);
            $this->TDS(0, 9, "width='14%' class='ttp' nowrap",  $this->TextShift . "<font  class='ttp'><b>$TEMPL[cl_size]</b>                </font>" . $this->TextShift);
        }

        $r_fil = DBFind("file, fs", "file.sysnum = fs.sysnumfile and fs.ftype = 'f' and fs.owner = $this->UID", "count(fs.sysnum) as fcount, sum(file.fsize) as fsize", __LINE__);
        //$r_fil = DBFind("wel", "sysnum = $this->UID", "");

        $this->TRNext(""); {
            $this->TDNext("class='tlp'"); {
                $this->out($this->TextShift . "<a href='file_folder.php?UID=$this->UID&FACE=$FACE&sNewView=on' title='$TEMPL[fd_ftp_myfile_ico]'><img src='$INET_IMG/disk.gif' border='0' align='absmiddle'><font class='tlpa'>" . $this->TextShift . "<b>$TEMPL[fd_ftp_myfile]</b></font></a>");
            }

            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }

            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }

            $this->TDNext("align='right' class='tlp'"); {
                $this->out($this->TextShift . "<font class='tlp'>".$this->nbsp($r_fil->fcount()) . "</font>" . $this->TextShift);
            }

            $this->TDNext("align='right' class='tlp'"); {
                $k = $r_fil->fsize();
                $TotalSize += $k;
                $k1 = AsSize($k);
                $this->out($this->TextShift . "<span title='$k bytes'><font class='tlp'>".$this->nbsp($k1)."</font></span>" . $this->TextShift);
            }
        }

        $this->TRNext(""); {
            $r_fil = DBFind("file, fs, acc", "file.sysnum = fs.sysnumfile and fs.ftype = 'f' and fs.sysnum = acc.sysnumfs and acc.username = '" . $this->USR->name() . "@" . $this->DOMAIN->name() . "'", "count(fs.sysnum) as count, sum(file.fsize) as fsize", __LINE__);

            $this->TDNext("class='tlp'");
            $this->out($this->TextShift . "<a href='access_folder.php?UID=$this->UID&Key=&FACE=$FACE' title='$TEMPL[fd_ftp_frfile_ico]'><font class='tlpa'><img src='$INET_IMG/friendsfile.gif' border='0' align='absmiddle'>" . $this->TextShift . "<b>$TEMPL[fd_ftp_frfile]</b></font></a>" . $this->TextShift);

            $this->TDNext("class='tlp'");
            $this->out("&nbsp");

            $this->TDNext("class='tlp'");
            $this->out("&nbsp");

            $this->TDNext("align='right' class='tlp'");
            $this->out($this->TextShift . "<font class='tlp'>".$this->nbsp($r_fil->count())."</font>" . $this->TextShift);

            $this->TDNext("align='right' class='tlp'");
            $k = $r_fil->fsize();
            //$TotalSize += $k;
            $k1 = AsSize($k);
            $this->out($this->TextShift . "<span title='$k bytes'><font class='tlp'>".$this->nbsp($k1)."</font></span>" . $this->TextShift);
        }

        $this->TRNext(""); {
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
        }

        $r_fil     = $this->r_fil;
        $isMessFolders = 0;

        for($r_fld->set(0); !$r_fld->eof(); $r_fld->next()) {

            if ($isMessFolders == 0 && ($r_fld->ftype() == 4 || $r_fld->ftype() == 6)) {
                $isMessFolders = 1;
                $this->TRNext(""); {
                    $this->TDNext("class='tlp'"); {
                        $this->out("<span title='$TEMPL[fd_other_ico]'>"); {
                            $this->out($this->TextShift);
                            $this->out("<img src='$INET_IMG/mainmessagefolder.gif' align='absmiddle'>");
                            $this->out($this->TextShift);
                            $this->out($TEMPL[fd_other]);
                        } $this->out("</span>");
                    }
                    $this->TDNext("class='tlp'"); {
                        $this->out("&nbsp");
                    }
                    $this->TDNext("class='tlp'"); {
                        $this->out("&nbsp");
                    }
                    $this->TDNext("class='tlp'"); {
                        $this->out("&nbsp");
                    }
                    $this->TDNext("class='tlp'"); {
                        $this->out("&nbsp");
                    }
                }
            }

            $this->TRNext("height=20"); {
                switch ($r_fld->ftype()) {
                    case 1: $FldName = $TEMPL[fd_inbox];
                            $FLDName_ico   = "inbox.gif";
                            $FLDName_title = $TEMPL[fd_inbox_ico];
                            break;
                    case 2: $FldName = $TEMPL[fd_sent];
                            $FLDName_ico   = "sent.gif";
                            $FLDName_title = $TEMPL[fd_sent_ico];
                            break;
                    case 5: $FldName = $TEMPL[fd_trash];
                            $FLDName_ico   = "trash.gif";
                            $FLDName_title = $TEMPL[fd_trash];
                            break;
                    case 6: $FldName = $TEMPL[fd_notif];
                            $FLDName_ico   = "messagefolder.gif";
                            $FLDName_title = $TEMPL[fd_notif];
                            break;
                    default:
                            $FldName = $r_fld->name();
                            $FLDName_ico   = "messagefolder.gif";
                            $FLDName_title = $TEMPL[fd_other_ico];
                } //switch

                $s = "<a href='$INET_SRC/mail_folder.php?UID=$this->UID&FACE=$FACE&Fld=".$r_fld->sysnum()."' title='$FLDName_title'><font class='tlpa'><img src='$INET_IMG/$FLDName_ico' border='0' align='absmiddle'>" . $this->TextShift . $FldName . "</font></a>";
                if ($r_fld->ftype() != 4 && $r_fld->ftype() != 6) {
                    $s =  "<b>$s</b>";
                } else {
                    $s = $this->SectionBlank . "<input type='radio' name='CheckFolder' value='".$r_fld->sysnum()."'>" . $this->TextShift . $s;
                }
                //$s = " &nbsp " . $s;
                $s = $this->TextShift . $s;
                $this->TDNext("nowrap align='left' class='tlp'"); {
                    $this->out($s);
                }

                $this->TDNext("align='right' class='tlp'"); {
                    $k = 0;
                    if ($r_msg->Find("sysnum", $r_fld->sysnum()) >= 0) {
                        $k = $r_msg->fnew();
                    }
                    if ($k != 0) {
                        $k = "<b>" . $k . "</b>";
                    }
                    $k = $this->TextShift . "<font class='tlp'>$k</font>" . $this->TextShift;
                    $this->out($k);
                }


                $this->TDNext("align='right' class='tlp'"); {
                    $k = 0;
                    if ($r_msg->Find("sysnum", $r_fld->sysnum()) >= 0) {
                        $k = $r_msg->count();
                    }
                    $k = $this->TextShift . "<font class='tlp'>$k</font>" . $this->TextShift;
                    $this->out($k);
                }

                $this->TDNext("align='right' class='tlp'"); {
                    $k = 0;
                    $size = 0;
                    if ($r_fil->Find("sysnum", $r_fld->sysnum()) >= 0) {
                        $k = $r_fil->count();
                        $size = $r_fil->fsize();
                    }
                    $k = $this->TextShift . "<font class='tlp'>$k</font>" . $this->TextShift;
                    $this->out($k);
                }

                $k = $size;
                if ($r_msg->Find("sysnum", $r_fld->sysnum()) >= 0) {
                    $k += $r_msg->fsize();
                }
                $TotalSize += $k;
                $k1 = asSize($k);
                $k = $this->TextShift . "<span title='$k bytes'><font class='tlpe'>$k1</font></span>" . $this->TextShift;
                $this->TDNext("align='right' class='tlp'"); {
                    $this->out($k);
                }
            }
        }

        // ====================== total ==============================

        $this->TRNext(""); {
            $this->TDNext("class='tlp'"); {
                $this->out($this->TextShift . "<font class='tlp' style='font-size: 14px; font-weight : bold'>$TEMPL[fd_total] :</font>" . $this->TextShift); //
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp' align='right'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp' align='right'"); {
                $k = $TotalSize;
                $k1 = AsSize($k);
                $this->out($this->TextShift . "<span title='$k bytes'><font class='tlp' style='font-size: 14px; font-weight : bold'>".$this->nbsp($k1)."</font></span>" . $this->TextShift);
            }
        }

        $r = DBExec("SELECT usr.quote        as usrquote," .
                           "domain.quote     as domainquote, " .
                           "usr.diskusage    as usrdiskusage, " .
                           "domain.diskusage as domaindiskusage, " .
                           "domain.userquote as defaultusrquote where usr.sysnumdomain = domain.sysnum and usr.sysnum = '$this->UID'");

        // ====================== quote ==============================

        $UsrQuote = $r->usrquote();
        if ($UsrQuote == 0) {
            $UsrQuote = $r->defaultusrquote();
        }
        $UsrDiskUsage = $r->usrdiskusage();


        $this->TRNext(""); {
            $UsrQuote = $r->usrquote();
            if ($UsrQuote == 0) {
                $UsrQuote = $r->defaultusrquote();
            }

            $this->TDNext("class='tlp'"); {
                $this->out($this->TextShift . "<font class='tlp' style='font-size: 14px; font-weight : bold'>$TEMPL[fd_quote] :</font>" . $this->TextShift); //
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp' align='right'"); {
                $this->out("&nbsp");
            }
            $this->TDNext("class='tlp' align='right'"); {
                $k = $UsrQuote;
                $k1 = AsSize($k);
                $this->out($this->TextShift . "<span title='$k bytes'><font class='tlp' style='font-size: 14px; font-weight : bold'>".$this->nbsp($k1)."</font></span>" . $this->TextShift);
            }
        }

        //$this->TRNext(""); {
        //    $this->TDNext("class='tlp'"); {
        //        $this->out($this->TextShift); //
        //    }
        //    $this->TDNext("class='tlp'"); {
        //        $this->out("&nbsp");
        //    }
        //    $this->TDNext("class='tlp'"); {
        //        $this->out("&nbsp");
        //    }
        //    $this->TDNext("class='tlp' align='right'"); {
        //        $this->out("&nbsp");
        //    }
        //    $this->TDNext("class='tlp' align='right'"); {
        //        $k = $UsrDiskUsage;
        //        $k1 = AsSize($k);
        //        $this->out($this->TextShift . "<span title='$k bytes'><font class='tlp' style='font-size: 14px; font-weight : bold'>".$this->nbsp($k1)."</font></span>" . $this->TextShift);
        //    }
        //}
        $this->SubTableDone();

        // $this->ShResult($r_fil);
        $this->out("</form>");   // open in function ToolsBar
    }


    function Tools()
    {
        global $INET_SRC, $INET_IMG, $TEMPL, $FACE, $INET_HELP;

        $this->out("<img src='$INET_IMG/filler2x1.gif'><br>");
        $this->SubTable("border=0 width='100%' nowrap cellspacing = '0' cellpadding = '0' grborder");
        $this->TDS(0, 0, "class='toolst' align='center'", "<b>$TEMPL[tools]</b>");
        $this->TDS(2, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/address.php?UID=$this->UID&FACE=$FACE&sNewView=1' title='$TEMPL[to_address_book_ico]'><font class='toolsa'>$TEMPL[to_address_book]</font></a>");
        $this->TDS(4, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/datebook.php?UID=$this->UID&FACE=$FACE' title='$TEMPL[to_date_book_ico]'><font class='toolsa'>$TEMPL[to_date_book]</font></a>");
        $this->TDS(5, 0, "class='toolsl'", "&nbsp");
        $this->TDS(6, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/options.php?UID=$this->UID&FACE=$FACE' title='$TEMPL[to_options_ico]'><font class='toolsa'>$TEMPL[to_options]</font></a>");
        $this->TDS(8, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/user_tools.php?UID=$this->UID&FACE=$FACE' title='$TEMPL[to_tools_ico]'><font class='toolsa'>$TEMPL[to_tools]</font></a>");
        $this->TDS(10, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/search_files.php?UID=$this->UID&FACE=$FACE&sNewView=on' title='$TEMPL[to_search_ico]'><font class='toolsa'>$TEMPL[to_search]</font></a>");
        //if ($this->USR->Lev() == 2) {
        //  $this->TDS(8, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/list_domains.php?UID=$this->UID&FACE=$FACE' title='$TEMPL[to_admin_ico]'><font class='toolsa'>$TEMPL[to_admin]</font></a>");
        //} else if ($this->USR->Lev() == 1) {
        //  $this->TDS(8, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/list_users.php?UID=$this->UID&FACE=$FACE&DOMAIN=" . $this->USR->SysNumDomain() . "' title='$TEMPL[to_admin_ico]'><font class='toolsa'>$TEMPL[to_admin]</font></a>");
        //} else {
        //  $this->TDS(8, 0, "class='toolsl'", "&nbsp");
        //}
        if ($this->USR->Lev() == 2 || $this->USR->Lev() == 1) {
            $this->TDS(12, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/admin_opt.php?UID=$this->UID&FACE=$FACE' title='$TEMPL[to_admin_ico]'><font class='toolsa'>$TEMPL[to_admin]</font></a>");
        } else {
            $this->TDS(12, 0, "class='toolsl'", "&nbsp");
        }

        $this->TDS(14, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/ziud.php/".urlencode("user_manual.pdf")."?UID=$this->UID&FACE=$FACE&TagFile=".urlencode("user_manual.pdf")."' title='$TEMPL[to_userguide_ico]'><font class='toolsa'>$TEMPL[to_userguide]</font></a>");
        $this->TDS(16, 0, "class='toolsl'", $this->TextShift . "<a href='javascript:showHelp(\"$INET_HELP/AboutUs.html\");' title='$TEMPL[to_about_ico]'><font class='toolsa'>$TEMPL[to_about]</font></a>");
        $this->TDS(18, 0, "class='toolsl'", $this->TextShift . "<a href='$INET_SRC/logout.php?UID=$this->UID&FACE=$FACE' title='$TEMPL[to_logout_ico]'><font class='toolsa'>$TEMPL[to_logout]</font></a>");
        $this->SubTableDone("");
    }


    function CreateFolder()
    {
        global $DBConn;
        global $NameFolder, $Mes;

        if ($NameFolder == "") {
          $Mes = 1;
          return;
        }

        if (strlen($NameFolder) > 15) {
          $Mes = 4;
          return;
        }

        if (!ereg('^[A-Za-z0-9][-A-Za-z0-9_ ]*', $NameFolder)) {
          $Mes = 5;
          return;
        }

        $res = DBFind("fld", "name='$NameFolder' and sysnumusr=$this->UID", "", __LINE__);
        if ($res->NumRows() != 0) {
          $Mes = 2;
          return;
        }

        DBExec("insert into fld (sysnum, sysnumusr, name, ftype, sort) values (NextVal('fld_seq'), $this->UID, '$NameFolder', 4, 'd')", __LINE__);

        $this->refreshScreen();
    }


    function RenameFolder()
    {
        global $DBConn, $Mes;
        global $NameFolder, $CheckFolder;


        if ($NameFolder == "") {
          $Mes = 1;
          return;
        }

        if (strlen($NameFolder) > 15) {
          $Mes = 4;
          return;
        }

        if (!ereg("^[-A-Za-z0-9_ ]*$", $NameFolder)) {
          $Mes = 5;
          return;
        }

        if ($CheckFolder == 0) {
          $Mes = 3;
          return;
        }


        $res = DBFind("fld", "name='$NameFolder' and sysnumusr=$this->UID", "", __LINE__);
        if ($res->NumRows() != 0) {
          $Mes = 2;
          return;
        }


        DBExec("update fld set name = '$NameFolder' where sysnum = $CheckFolder", __LINE__);

        $this->refreshScreen();
    }


    function DeleteFolder()
    {
        global $DBConn, $Mes;
        global $CheckFolder;

        if ($CheckFolder == 0) {
          return;
        }

        $r_msg = DBFind("msg", "sysnumfld = $CheckFolder", "", __LINE__);
        if ($r_msg->NumRows() != 0) {
          return;
        }

        DBExec("DELETE from fld where sysnum = $CheckFolder", __LINE__);

        $this->refreshScreen();
    }

} // end of class CWelcomeScreen extends screen

ConnectToDB();

$WelcomeScreen = new CWelcomeScreen();
$WelcomeScreen->Run();

UnconnectFromDB();
exit;
?>
