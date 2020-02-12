<?php

/*
class CComposeScreen extends screen {
  function CComposeScreen()
  function Scr()
      function ScrMessage()
      function ScrAttach()
      function ScrPosted()
      function PutListFiles()
      function PutListAttach()
  function rNewView()
  function rChView()
  function rAttach()
  function rDetach()
  function Upload()
  function rSend()
      function SendToInernet($ToInernet)
      function SendToFolder($ToFLD, $ToUSR)
  function rRetMes()
  function rNewUpld()
  function mes()
  function SaveScreenStatus()
}
*/

if (!isset($_COMPOSE_INC_)) {
$_COMPOSE_INC_=0;

include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("screen.inc.php");
require("utils.inc.php");

class CComposeScreen extends screen
{
    function CComposeScreen()
    {
        global $TEMPL;

        $this->screen(); // inherited constructor
        $this->SetTempl("compose");
        session_register("s_Compose");

        $this->PgTitle = "<b>$TEMPL[title]</b>";

        $this->Trans("SelectAll",   "");

        $this->Request_actions["sNewView"]        = "rNewView()";
        $this->Request_actions["sChangeDir"]      = "rChangeDir()";
        $this->Request_actions["sChView"]         = "rChView()";
        $this->Request_actions["sSend"]           = "rSend()";
        $this->Request_actions["sAttach"]         = "rAttach()";
        $this->Request_actions["sDetach"]         = "rDetach()";
        $this->Request_actions["sDone"]           = "rRetMes()";
        $this->Request_actions["sNewUpld"]        = "rNewUpld()";
    }


    function BodyScripts()
    {
        global $s_Compose;
        if ($s_Compose[View] == "Mes" || $s_Compose[View] == "") {
            return "onload='javascript:onLoad();'";
        }

        return "";
    }


    function Referens()
    {
        global $Ret, $s_Compose;

        if ($Ret == "") {
            $Ret = $s_Compose[Ret];
        }

        if ( !eregi("^MES#([0-9]+)#([0-9]+)$", $Ret) ) {
            parent::Referens();
        }
    }


    // redefines method Action for read content of folder
    function Actions()
    {
        global $R_FS, $s_Compose;

        $s_Compose[Status][fFS] = (int)$s_Compose[Status][fFS];
        $this->PATH = "";

        if ($s_Compose[View] == "Att") {
            $this->Data = SetData($this->USR, $this->USRNAME, $s_Compose[Status][fFS], 0, $R_FS);
            if ($this->Data[error]) {
                $s_Compose[View] = "Mes";
                $this->refreshScreen();
            }

            if ($s_Compose[Status][fFS] != 0) {
                $r_fs = DBExec("select gettree(" . $this->Data[sysnum] . ") as path", __LINE__);
                $this->PATH = $r_fs->path();
            }
        }

        parent::Actions();
    }


    function SaveScreenStatus()
    {
        parent::SaveScreenStatus("s_Compose", array("fTO", "fCC", "fSubj",
                                                   "fMessage", "fNMob", "fSaveSent", "fFS", "fTblAttach"), 0);
    }


    function Scr()
    {
        global $s_Compose;

        switch ($s_Compose[View]) {
            case ""    : $s_Compose[View] = "Mes";
            case "Mes" : $this->ScrMessage(); break;
            case "Att" : $this->ScrAttach();  break;
            case "Pst" : $this->ScrPosted();  break;
        }

        //$this->SubTable("border = 1");
        //$this->out("=".sharr($GLOBALS[_SESSION]));
        //$this->out("=".shGLOBALS());
        //$this->SubTableDone();
    }

    function ScrMessage()
    {
        global $s_Compose, $INET_IMG, $INET_SRC, $FACE;
        global $HTTP_USER_AGENT, $TEMPL, $Ret;


        $agent = "Other";
        if     (eregi ("Opera",             $HTTP_USER_AGENT))    { $agent = "Other"; }
        elseif (eregi ("Netscape/?[67]{1}", $HTTP_USER_AGENT))    { $agent = "NSC";   }
        elseif (eregi ("MSIE.*Windows",     $HTTP_USER_AGENT))    { $agent = "MSIE";  }

        $fTO      = htmlspecialchars($s_Compose[Status][fTO]);
        $fCC      = htmlspecialchars($s_Compose[Status][fCC]);
        $fSubj    = htmlspecialchars($s_Compose[Status][fSubj]);

        if ( isset($s_Compose[Status][fMessage]) ) {
            $fMessage = $s_Compose[Status][fMessage];
            $fConType = $s_Compose[Status][fConType];
        } else {
            $fMessage = "";
            $res_fs = DBExec("SELECT * FROM usr_ua WHERE sysnumusr = $this->UID AND name = 'signature' order by nset");
            while(!$res_fs->eof()) {
                $fMessage .= $res_fs->value();
                $res_fs->next();
            }
            if ($fMessage != "") {
                $fMessage = URLDecode($fMessage);
                if (preg_match("/<[^<]*>/", $fMessage)) {
                    $fConType = "TEXT/HTML";
                    $fMessage = "<br><br>" . preg_replace("/\r?\n/", "", $fMessage);
                } else {
                    $fConType = "TEXT/PLAIN";
                    $fMessage = "\n\n" . $fMessage;
                }
            }
        }


        if ($agent == "MSIE") {
            if ($fConType == "TEXT/HTML") {
                $fMessage = preg_replace("'.*<\s*?body[^>]*?>(.*?)<\s*?/\s*?body[^>]*?>.*'si", "\\1", $fMessage);
            } else {
                $fMessage = nl2br($fMessage);
            }
        } else {
            if ($fConType == "TEXT/HTML") {
				$fMessage = HTPM2TEXT($fMessage);
            }
        }

        $fMessage = htmlspecialchars($fMessage);

        $this->out("<form name='ComposeForm' method='POST' onSubmit=\"javascript:return onSubmit();\"'>");
        // Hidden field for dialog with Java Script for upload
        $this->out("<input type='hidden' name='sNewUpld'>");

        $this->SubTable("border='0' width='100%' cellspacing=0 cellpadding=3"); {
            $this->TRNext("class='toolsbarl'"); {
                $this->out(makeButton("type=1& form=ComposeForm& name=sSend&        img=$INET_IMG/compfoldersend-passive.gif?FACE=$FACE&     imgact=$INET_IMG/compfoldersend.gif?FACE=$FACE&     title=$TEMPL[bt_send_ico]") . $this->ButtonBlank);

                $this->out(makeButton("type=2& form=ComposeForm& name=sUpldFolder&  img=$INET_IMG/compfolderuplattch-passive.gif?FACE=$FACE& imgact=$INET_IMG/compfolderuplattch.gif?FACE=$FACE& onclick=javascript:wUpld('$INET_SRC/upld.php?UID=$this->UID%26FACE=$FACE%26FS=0%26ComposeForm=1')& title=$TEMPL[bt_upl_attach_ico]") . $this->ButtonBlank);
                $this->out(makeButton("type=1& form=ComposeForm& name=sChView&      img=$INET_IMG/compfoldermyftp-passive.gif?FACE=$FACE&    imgact=$INET_IMG/compfoldermyftp.gif?FACE=$FACE&       title=$TEMPL[bt_add_my_ftp_ico]") . $this->ButtonBlank);

                if ($Ret != "" || $s_Compose[Ret] != "") {
                #    $this->out(makeButton("type=1& form=ScrPostedForm& name=sDone& value=Exit& class=toolsbarb"));
                    $this->out(makeButton("type=1& form=ComposeForm& name=sDone&      img=$INET_IMG/compfolderexit-passive.gif?FACE=$FACE&    imgact=$INET_IMG/compfolderexit.gif?FACE=$FACE&       title=$TEMPL[bt_exit_ico]") . $this->ButtonBlank);
                }
            }
        } $this->SubTableDone("");

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        //$this->out("<br><center>");
        $this->SubTable("class='body' border='0' cellspacing=0 cellpadding=0"); {
            $this->SubTable("class='body' border='0' cellspacing=0 cellpadding=3 width='100%'"); {
                $this->TDS(0, 0, "class='tlp' width='15%'", ""); {
                    $this->out(makeButton("type=2& form=ComposeForm& name=sAddressView_TO& img=$INET_IMG/compfolderto-passive.gif?FACE=$FACE& imgact=$INET_IMG/compfolderto.gif?FACE=$FACE& onclick=javascript:wSelAddresses('$INET_SRC/pr2.php?UID=$this->UID%26FACE=$FACE')&  title=$TEMPL[bt_to_ico]"));
                }
                $this->TDS(0, 1, "class='tlp' width='85%'", ""); {
                    $this->out("<input name='fTO' value=\"$fTO\" size=95 class='toolsbare'>");
                }
            } $this->SubTableDone("");

            $this->out("<img src='$INET_IMG/filler2x1.gif'>");

            $this->SubTable("class='body' border='0' cellspacing=0 cellpadding=3 width='100%'"); {
                $this->TDS(2, 0, "class='tlp' width='15%'", ""); {
                    $this->out(makeButton("type=2& form=ComposeForm& name=sAddressView_CC& img=$INET_IMG/compfoldercc-passive.gif?FACE=$FACE& imgact=$INET_IMG/compfoldercc.gif?FACE=$FACE& onclick=javascript:wSelAddresses('$INET_SRC/pr2.php?UID=$this->UID%26FACE=$FACE%26Field=CC')&  title=$TEMPL[bt_to_ico]"));
                }
                $this->TDS(2, 1, "class='tlp' width='85%'", ""); {
                    $this->out("<input name='fCC' value=\"$fCC\" size=95 class='toolsbare'>");
                }
            } $this->SubTableDone("");

            $this->out("<img src='$INET_IMG/filler2x1.gif'>");

            $this->SubTable("class='body' border='0' cellspacing=0 cellpadding=3 width='100%'"); {
                $this->TDS(4, 0, "nowrap class='tlp' width='15%'", ""); {
                    $this->out("<font class='tlpa'><b>$TEMPL[lb_subject] :</b></font>&nbsp;&nbsp;");
                }
                $this->TDS(4, 1, "class='tlp' width='85%'", ""); {
                    $this->out("<input name='fSubj' value=\"$fSubj\" size=95 class='toolsbare'>");
                }
            } $this->SubTableDone("");

            $this->out("<hr>");

            $this->TDS(6, 0, "colspan=2", ""); {
                $CHECKED = ( !isset($s_Compose[Status][fSaveSent]) || $s_Compose[Status][fSaveSent] != "" ) ? "CHECKED" : "";
                $this->out("<input type='checkbox' name='fSaveSent' $CHECKED> <font class='body' style='color : white'>$TEMPL[lb_save_sent]</font>");
            }

            $this->TDS(7, 1, "colspan=2", ""); {
                $this->out("<img src='$INET_IMG/filler2x1.gif'>");
            }

            $this->TDS(8, 0, "colspan=2", ""); {
        		$this->out("<script language='javascript' src='$INET_SRC/htmlarea/init_code.js'></script>\n");
            		$this->out("<TEXTAREA name='fMessage' cols='90' rows='15' class='toolsbare' dir='ltr'>");
            			$this->out($fMessage);
            		$this->out("</TEXTAREA>");
        		$this->out("<script language=\"javascript1.2\">editor_generate('fMessage');</script>");
            }

            $this->TDS(9, 0, "colspan=2", ""); {
                $this->out("<hr>");
            }
        } $this->SubTableDone("");

        //$this->out("</center>");

        $this->PutListAttach();

        $this->out("</form>");
    }


    function ScrAttach()
    {
        global $s_Compose;
        global $INET_CGI, $INET_SRC, $INET_IMG;
        global $HTTP_HOST, $FTP_PORT, $FACE, $TEMPL;

        //$this->out(sharr($s_Compose[Status]), "<br>");

        $this->out("<form name='AttForm' method='post'>");
        // Hidden field for dialog with Java Script for upload
        $this->out("<input type='hidden' name='sNewUpld'>");


        $this->SubTable("width='100%' cellspacing='0' cellpadding='3'");
            $this->TRNext("class='toolsbarl'");
                $this->out(makeButton("type=1& form=AttForm& name=sSend&   title=$TEMPL[bt_send_ico]&       img=$INET_IMG/compfoldersend-passive.gif?FACE=$FACE&         imgact=$INET_IMG/compfoldersend.gif?FACE=$FACE") . $this->ButtonBlank);
                $this->out(makeButton("type=1& form=AttForm& name=sChView& title=$TEMPL[bt_return_mes_ico]& img=$INET_IMG/compfolderrettomes-passive.gif?FACE=$FACE&     imgact=$INET_IMG/compfolderrettomes.gif?FACE=$FACE") . $this->SectionBlank);
                $this->out(makeButton("type=2& form=AttForm& name=sUpldFolder& img=$INET_IMG/compfolderuplattch-passive.gif?FACE=$FACE& imgact=$INET_IMG/compfolderuplattch.gif?FACE=$FACE& onclick=javascript:wUpld('$INET_SRC/upld.php?UID=$this->UID%26FACE=$FACE%26FS=" . $s_Compose[Status][fFS] . "%26ComposeForm=1')& title=$TEMPL[bt_upl_attach_ico]") . $this->ButtonBlank);
                $this->out(makeButton("type=2& form=AttForm& name=sFTPFolder&  img=$INET_IMG/runftpclient-passive.gif?FACE=$FACE& imgact=$INET_IMG/runftpclient.gif?FACE=$FACE& onclick=javascript:wFtpOpen('ftp://" . ereg_replace("\@", "\$", $this->USRNAME) . ":" . AuthorizeKey($this->USRNAME) . "@$HTTP_HOST:$FTP_PORT" . "/My_Files/" . ($this->PATH) . "')& title=$TEMPL[bt_run_ftp_ico]"));
        $this->SubTableDone();

        $this->out("<hr>");
        $this->PutListFiles();

        $this->out("<hr>");
        $this->PutListAttach();

        $this->out("</form>");

        // $this->script2();
    }


    function ScrPosted()
    {
          global $fTO, $fCC, $fSubj, $fSaveSent, $fMessage;
          global $s_Compose, $Ret;

          $fTO      = htmlspecialchars(URLDecode($fTO));
          $fCC      = htmlspecialchars(URLDecode($fCC));
          $fSubj    = htmlspecialchars(URLDecode($fSubj));
          $fMessage = htmlspecialchars(URLDecode($fMessage));

          $this->Out("<form method='post' name=ScrPostedForm>");
          $this->Out("Your message with subject : ($fSubj) has been sent<br><b>To : </b><i><u>$fTO</u></i><br><b>Cc : </b><i><u>$fCC</u></i><br><br>");


          if ($Ret != "" || $s_Compose[Ret] != "") {
            $this->out(makeButton("type=1& form=ScrPostedForm& name=sDone& value=Exit& class=toolsbarb"));
          }

          $this->Out("</form>");
    }


    function PutListAttach()
    {
        global $s_Compose, $INET_IMG, $TEMPL, $FACE;

        // Table attachment
        // $this->out("<br>&nbsp&nbsp&nbsp<b>Attachment</b> ");
        if (!is_array($s_Compose[Status][fTblAttach]) || count($s_Compose[Status][fTblAttach]) == 0) {
            if ($s_Compose[View] == "Att") {
                $this->SubTable("width='100%' cellspacing='0' cellpadding='5' class='toolsbarl'");
                $this->out("<font size='+2'><b>$TEMPL[no_attachment]</b></font>");
                $this->SubTableDone();
            }
            return;
        }

        $this->SubTable("width='100%' cellspacing='0' cellpadding='3'");
            $this->TDNext("class='toolsbarl'");
            if ($s_Compose[View] == "Att") {
                $this->out(makeButton("type=1& form=AttForm& name=sDetach& img=$INET_IMG/compfolderremattch-passive.gif?FACE=$FACE& imgact=$INET_IMG/compfolderremattch.gif?FACE=$FACE& title=$TEMPL[bt_rem_attach]"));
            } else {
                $this->out(makeButton("type=1& form=ComposeForm& name=sDetach& img=$INET_IMG/compfolderremattch-passive.gif?FACE=$FACE& imgact=$INET_IMG/compfolderremattch.gif?FACE=$FACE& title=$TEMPL[bt_rem_attach]"));
            }
        $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("border=0 width='100%' class='tab' cellspacing='0' cellpadding='0' grborder");

        $this->TRNext(""); {
            $this->TDNext("class='ttp' width='1%'");  $this->out("&nbsp");
            $this->TDNext("class='ttp' width='59%'"); $this->out("&nbsp<b>$TEMPL[lb_file_name]</b> ");
            $this->TDNext("class='ttp' width='20%'"); $this->out("&nbsp<b>$TEMPL[lb_date]</b>&nbsp");
            $this->TDNext("class='ttp' width='20%'"); $this->out("&nbsp<b>$TEMPL[lb_size]</b>&nbsp");
        }

        // $this->out($this->TRNext(""),
        // $this->TDNext("colspan=4");

        $s = "";
        reset($s_Compose[Status][fTblAttach]);
        while (list($n, $v) = each($s_Compose[Status][fTblAttach])) {
            $s .= ($s != "" ? " OR " : "") . "fs.sysnum = $n";
        }

        $SizeAll = 0;
        $r_fs = DBFind("fs, file", "fs.sysnumfile = file.sysnum and ($s) and fs.owner = $this->UID", "gettree(fs.sysnum) as filepath, fs.sysnum, fs.name, fs.creat, file.ftype, file.fsize", __LINE__);
        while(!$r_fs->eof()) {
            $this->TRNext(); {
                $this->TDNext("class='tlp'");                     $this->out("<input type='checkbox' name='CheckAttach[]' value='".$r_fs->sysnum()."'>");
                $this->TDNext("class='tlp'");                     $this->out(htmlspecialchars($r_fs->filepath()));
                //$this->TDNext("class='tlp'");                     $this->out($r_fs->ftype() != "" ? $r_fs->ftype() : "[N/A]");
                $this->TDNext("class='tlp'");                     $this->out($this->out(mkdatetime($r_fs->creat())));

                $this->TDNext("class='tlp' align='right' nowrap"); $s = (int)$r_fs->fsize(); $s1=AsSize($s); $this->out("<div title='$s'>$s1&nbsp;</div>");
            }

            $SizeAll += (int)$r_fs->fsize();

            $r_fs->Next();
        }

        $this->TRNext("class='tlp'");
            $this->TDNext("class='tlp'");
                $this->out( "&nbsp");
            $this->TDNext("class='tlp' width='60%'");
                $this->out( "&nbsp" );
            $this->TDNext("class='tlp' width='30%' align='right'");
                $this->out( "<b>$TEMPL[cnt_size]</b>");
            $this->TDNext("class='tlp' width='30%' align='right' nowrap");
                $s = $SizeAll;
                $s1 = AsSize($s);
                $this->out( "<div title='$s'><b>$s1</b></div>" );

        $this->SubTableDone();
    }


    function PutListFiles()
    {
        global $s_Compose;
        global $INET_IMG, $INET_SRC, $INET_CGI, $FACE, $TEMPL;

        $CheckToAttachCount = 0;

        // Fields for dialog with JavaScript.
        $this->out("<input type='hidden' name='fFS' value='" . $s_Compose[Status][fFS] . "'>");
        $this->out("<input type='hidden' name='sChangeDir'>");

        if ($this->Data[sysnum] == 0) {
            $UpFolderLink = "Folder name :";
            $UpFolderName = "<b>root</b>";
        } else {
            $UpFolderLink = "<a href='javascript:ChangeDir(".$this->Data[up].");'><img src='$INET_IMG/up2.gif' border='0' align='absmiddle'></a> Folder name :";
            $UpFolderName = "<b>".$this->Data[name]."</b>";
        }

        $this->SubTable("width='100%' cellspacing='0' cellpadding='5' border=0 class='tab'");
            $this->TRNext();
                $this->TDNext("class='toolsbarl' width=10% nowrap");
                    $this->out($UpFolderLink);
                $this->TDNext("class='toolsbarl'");
                    $this->out($UpFolderName);
        $this->SubTableDone("");

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("width='100%' cellspacing='0' cellpadding='3' border=0 class='tab'"); {
            $this->TRNext(); {
                $this->TDNext("class='toolsbarl' colspan = 2"); {
                    $this->out(makeButton("type=1& form=AttForm& name=sAttach& img=$INET_IMG/compfolderaddattch-passive.gif?FACE=$FACE& imgact=$INET_IMG/compfolderaddattch.gif?FACE=$FACE& title=$TEMPL[bt_add_attach]"));
                }
            }
        } $this->SubTableDone("");

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("border=0 width='100%' cellspacing='2' cellpadding='3' class='toolsbarl'");

        $this->TRNext("");
            $this->TDNext("class='ttp'"); $this->out( "<input type='checkbox' name='CheckToAttachAll' alt='$TEMPL[select_all_ico]' onclick='javascript:onCheckToAttachAllClick()'>"); {
                //$this->TDNext("class='ttp'"); $this->out("&nbsp");
                //$this->TDNext("class='ttp'"); $this->out("<input type='image' src='$INET_IMG/sel_all.gif' name='SelectAll_1' border=0 alt='$TEMPL[select_all_ico]'>");
                $this->TDNext("class='ttp' width='60%'"); $this->out("&nbsp<b>$TEMPL[lb_file_name]</b> ");
                $this->TDNext("class='ttp' width='20%'"); $this->out("&nbsp<b>$TEMPL[lb_date]</b>&nbsp");
                $this->TDNext("class='ttp' width='20%'"); $this->out("&nbsp<b>$TEMPL[lb_size]</b>&nbsp");
            }

        $OneFileIsOuted = 0;

        for (_reset($this->Data[Files]); $key=_key($this->Data[Files]); _next($this->Data[Files])) {
            $file = $this->Data[Files][$key];

            if (($file[owner] == $this->UID) || ($file[access][d] != "") || ($file[access][a] != "")) {
                if ($file[sign] == 0) {
                    $OneFileIsOuted = 1;

                    $this->TRNext("class='tlp'"); {
                        $this->TDNext("class='tlp' width='1%'"); {
                            $this->out( "&nbsp" );
                        }
                        $this->TDNext("class='tlp' width='1%'"); {
                            $this->SubTable("width='100%' border=0 cellpadding=0 cellspacing=0"); {
                                $this->TDNext("width='10'"); {
                                    $this->out("<a href='javascript:ChangeDir(".$file[sysnum].");'><img src='$INET_IMG/folder-yellow.gif' border='0' alt='$TEMPL[open_folder]'></a>" );
                                }
                                $this->TDNext("width='5'"); {
                                    $this->out( "&nbsp;" );
                                }
                                $this->TDNext(); {
                                    $this->out("<a href='javascript:ChangeDir(".$file[sysnum].");'><font class='tlpa'><b>" . htmlspecialchars($file[name]) . "</b></font></a>");
                                }
                                $this->TDNext("width='5'"); {
                                    $this->out( "&nbsp;" );
                                }
                             } $this->SubTableDone();
                        }
                        $this->TDNext("class='tlp' width='20%'"); {
                            $this->out(mkdatetime($file[creat]));
                        }
                        $this->TDNext("class='tlp' width='20%'"); {
                            $this->out( "&nbsp" );
                        }
                    }
                } else if (!isset($s_Compose[Status][fTblAttach][$file["sysnum"]])) {
                    $OneFileIsOuted = 1;

                    $this->TRNext("class='tlp'"); {
                        $this->TDNext("class='tlp' width='1%'");
                             $this->out( "<input type='checkbox' name='CheckToAttach[" . $CheckToAttachCount++ . "]' value='".$file[sysnum]."'  onclick='javascript:onCheckToAttachClick()'>");
                        $this->TDNext("width='1%'"); {
                             $this->SubTable("width='100%' border=0 cellpadding=0 cellspacing=0"); {
                                 $this->TDNext("width='10'"); {
                                    $this->out( "<a href='" . MakeOwnerFileDownloadURL($file[name], $file[sysnum], $this->UID, 2) . "' target='_blank'><img src='$INET_IMG/view.gif' alt='$TEMPL[view_file]' border=0></a>");
                                 }
                                 $this->TDNext("width='5'"); {
                                    $this->out( "&nbsp;" );
                                 }
                                 $this->TDNext(); {
                                    $this->out( "<font class='tlpa'>" . htmlspecialchars($file[name]) . "</font>");
                                 }
                                 $this->TDNext("width='5'"); {
                                    $this->out( "&nbsp;" );
                                 }
                             } $this->SubTableDone();
                        }
                        $this->TDNext("class='tlp' width='20%'"); {
                             //$this->out( $file[cont] != "" ? $file[cont] : "&nbsp");
                             $this->out(mkdatetime($file[creat]));
                        }
                        $this->TDNext("class='tlp' width='20%' align='right' nowrap"); {
                             $s = $file[fsize];
                             $s1 = AsSize($s);
                             $this->out( "<div title='$s'>$s1&nbsp;</div>" );
                        }
                        // $this->TDNext(""); {
                             // $this->out($file[usrname]);
                        //}
                    }
                }
            }
        }

        if (!$OneFileIsOuted) {
            $this->TRNext("class='tlp'");
            $this->tdnext("colspan=7 class='tlp'", "");
            $this->out("<center>");
            $this->SubTable("border=1 CELLSPACING=0 CELLPADDING=0");
            $this->tds(0, 0, "width='250' height='70'", "<center><font size='+2'>None files to attach</font></center>");
            $this->SubTableDone();
            $this->out("</center>");
        }

        $this->SubTableDone("");
    }


    function rNewView()
    {
        global $s_Compose, $To;

        $s_Compose = array();

        if ($To) {
            $s_Compose[Status][fTO] = $To;
        }


        $this->refreshScreen();
    }


    function rChangeDir()
    {
        $this->SaveScreenStatus();
        $this->refreshScreen();
    }


    function rChView()
    {
          global $s_Compose;

          $this->SaveScreenStatus();
          $s_Compose[Status][fFS] = 0;

          switch ($s_Compose[View]) {
            case "" :
            case "Mes" : $s_Compose[View] = "Att";
                         break;
            case "Att" : $s_Compose[View] = "Mes";
                         break;
          }

          $this->refreshScreen();
    }


    function rAttach()
    {
        global $s_Compose, $CheckToAttach;
        global $_REQUEST;

        if ( !is_array($CheckToAttach) || count($CheckToAttach) == 0 ) {
            $s_Compose[Mes] = 7;
            $this->refreshScreen();
        }

        $_REQUEST[fTblAttach] = array();

        reset($CheckToAttach);
        while (list($n, $v) = each($CheckToAttach)) {
            // $this->out("$v<br>");
            $_REQUEST[fTblAttach][$v] = $v;
        }

        $this->SaveScreenStatus();
        $this->refreshScreen();
    }


    function rNewUpld()
    {
        global $_REQUEST, $sNewUpld;

        $tmp = split(", ", $sNewUpld);

        if (!is_array($tmp) || count($tmp) == 0) {
            $this->refreshScreen();
        }

        $s = ""; reset($tmp);
        while (list($n, $v) = each($tmp)) {
            if (ereg("^[0-9]+$", $v)) {
                $s .= ($s != "" ? " OR " : "") . "fs.sysnum = $v";
            }
        }

        $_REQUEST[fTblAttach] = array();

        $r_fs = DBFind("file", "($s) and fs.owner = $this->UID", "fs.sysnum", __LINE__);
        while(!$r_fs->eof()) {
            $_REQUEST[fTblAttach][$r_fs->sysnum()] = $r_fs->sysnum();
            $r_fs->Next();
        }

        $this->SaveScreenStatus();
        $this->refreshScreen();
    }


    function rDetach()
    {
        global $s_Compose, $CheckAttach;

        if ( is_array($CheckAttach)) {
            reset($CheckAttach);
            while (list($n, $v) = each($CheckAttach)) {
                // $this->out("$v<br>";
                unset($s_Compose[Status][fTblAttach][$v]);
            }
        }

        $this->SaveScreenStatus();
        $this->refreshScreen();
    }


    function rRetMes()
    {
        global $Ret, $FACE, $s_Compose;

        if ($Ret == "") {
          $Ret = $s_Compose[Ret];
        }

        unset($s_Compose);

        if (eregi("^ADDR$", $Ret)) {
            header("Location: address.php?UID=$this->UID&FACE=$FACE");
            exit;
        }

        if (eregi("^MES#([0-9]+)#([0-9]+)$", $Ret, $regs)) {
            header("Location: mail_folder.php?UID=$this->UID&FACE=$FACE&Fld=$regs[1]&Msg=$regs[2]");
            exit;
        }

        if (eregi("^MES#([0-9]+)$", $Ret, $regs)) {
            header("Location: mail_folder.php?UID=$this->UID&FACE=$FACE&Fld=$regs[1]");
            exit;
        }

        header("Location: welcome.php?UID=$this->UID&FACE=$FACE");
        exit;
    }


    function rSend()
    {
        global $s_Compose;

        $this->SaveScreenStatus();

        $this->Log("Send to " . $s_Compose[Status][fTO]);

        if ($s_Compose[Status][fTO] == "") {
            $s_Compose[Mes] = 1;
            $this->refreshScreen();
        }

        if (strlen(urlencode($s_Compose[Status][fTO])) > 255) {
            $s_Compose[Mes] = 8;
            $this->refreshScreen();
        }

        if(!ParseAddressesList($s_Compose[Status][fTO], &$addr)) {
            $s_Compose[Mes] = 2;
            $this->refreshScreen();
        }

        if ($s_Compose[Status][fCC] != "") {
            if (strlen(urlencode($s_Compose[Status][fCC])) > 255) {
                $s_Compose[Mes] = 9;
                $this->refreshScreen();
            }

            if(!ParseAddressesList($s_Compose[Status][fCC], &$addr_cc)) {
                $s_Compose[Mes] = 4;
                $this->refreshScreen();
            }

            $addr = array_merge($addr, $addr_cc);
        }

        //echo ShArr($addr, "");
        if (strlen(urlencode($s_Compose[Status][fSubj])) > 300) {
            $s_Compose[Mes] = 10;
            $this->refreshScreen();
        }

        if  (is_array($s_Compose[Status][fTblAttach]) and count($s_Compose[Status][fTblAttach])) {
            $s = "";
            reset($s_Compose[Status][fTblAttach]);
            while(list($n, $v) = each($s_Compose[Status][fTblAttach])) {
                $s .= ($s != "" ? " OR " : "") . "fs.sysnum = " . (int)$n;
            }

            $r_fs = DBExec("SELECT sum(file.fsize) AS at_size FROM fs, file WHERE ($s) AND fs.owner = '$this->UID' AND fs.sysnumfile = file.sysnum", __LINE__);

            $this->Log("Attachments size " . $r_fs->at_size());
            if ($r_fs->at_size() > 15 * 1024 * 1024) {
                $s_Compose[Mes] = 11;
                $this->refreshScreen();
            }
        }

        $ToInernet = array();
        reset($addr);
        while(list($n, $v) = each($addr)) {

            // resolving address with all forwards
            while (1) {
                if (strpos($v[addr], "@") == false) {
                    $v[addr] .= "@" . $this->DOMAIN->name();
                }

                $res = DBFind("usr, fld, domain", "fld.sysnumusr=usr.sysnum and usr.sysnumdomain =  domain.sysnum and usr.name || '@' ||  domain.name = '$v[addr]' and fld.ftype = 1", "fld.sysnum as sysnumfld, usr.sysnum as sysnumusr", __LINE__);
                if ($res->NumRows() == 1) {
                    // if address from we's system - send locale

                    // Esli est' pereadressowka - podmenit' adress
                    $Params = $this->ReadUserUA($res->sysnumusr());
                    if ($Params[frwmail] == 1 && $Params[email] != "") {
                        $v[addr] = $Params[email];
                        continue;
                    }
                    if ($Params[frwmail] == 2 && $Params[frwaddres] != "") {
                        $v[addr] = $Params[frwaddres];
                        continue;
                    }

                    // poslat' loakl'no
                    $this->SendToFolder($res->sysnumfld(), $res->sysnumusr());
                    break;
                } else {
                    $domain = preg_replace("/^[^@]*?@/", "", $v[addr]);
                    $res = DBFind("domain", "domain.name = '$domain'", "", __LINE__);
                    if ($res->NumRows() == 1) {
                        // esli adress ne iz nashei system, no domein nash - oshibka w adresse
                        $s_Compose[Mes]      = 6;
                        $s_Compose[MesParam] = $v[addr];
                        $this->refreshScreen();
                        break;
                    }
                    else {
                        // dobawit' adress k otprawke chrez INET
                        $ToInernet[] = $v[addr];
                        break;
                    }
                }
            }

        }

        if ($s_Compose[Status][fSaveSent]) {
            $res = DBFind("usr, fld", "fld.sysnumusr=usr.sysnum and usr.sysnum=$this->UID and fld.ftype = 2", "fld.sysnum as sysnumfld", __LINE__);
            if ($res->NumRows() == 1) {
                $this->SendToFolder($res->sysnumfld(), $this->UID);
            }
        }

        if (count($ToInernet) > 0) {
            $this->SendToInernet($ToInernet);
        }

        $this->rRetMes();

        //$s_Compose[View] = "Mes";
        //$this->refreshScreen();

        // Debug("Send to $s_Compose[Status][fTO] ok!");
    }


    function SendToInernet($ToInernet)
    {
        global $s_Compose;
        global $Mes;
        global $PROGRAM_SCRIPTS, $PROGRAM_FILES;
        global $HTTP_USER_AGENT, $TEMPL_CHARSET;
        global $REMOTE_ADDR;

        $Boundary_unique = "--" . md5(time()) . "_" . time() . "_" . $this->USRNAME;
        $eol = chr(10);

        if(eregi ("MSIE.*Windows", $HTTP_USER_AGENT)) {
            $fMessage     = "<html>\n<head>\n" . ($s_Compose[Status][fSubj] != "" ? ("<title>" . htmlspecialchars($s_Compose[Status][fSubj]). "</title>\n") : "") . "</head>\n<body>\n". $s_Compose[Status][fMessage] . "\n</body>\n</html>\n";
            $fMessage_TXT = html2text($s_Compose[Status][fMessage]);

            $fMessage = "Content-Type: multipart/alternative; boundary=\"--TEXTMESSAGE_$Boundary_unique\""  . $eol . $eol . $eol .
                       "----TEXTMESSAGE_$Boundary_unique"                                                   . $eol .
                       "Content-Type: text/plain; charset=$TEMPL_CHARSET"                                   . $eol .
                       "Content-Transfer-Encoding: quoted-printable"                                        . $eol . $eol .
                       imap_8bit($fMessage_TXT)                                                             . $eol .
                       "----TEXTMESSAGE_$Boundary_unique"                                                   . $eol .
                       "Content-Type: text/html; charset=$TEMPL_CHARSET"                                    . $eol .
                       "Content-Transfer-Encoding: quoted-printable"                                        . $eol . $eol .
                       imap_8bit($fMessage)                                                                 . $eol .
                       "----TEXTMESSAGE_$Boundary_unique--"                                                 . $eol;
        } else {
            $fMessage = "Content-Type: TEXT/PLAIN"                                                          . $eol .
                       "Content-Transfer-Encoding: quoted-printable"                                        . $eol . $eol .
                       imap_8bit($s_Compose[Status][fMessage])                                               . $eol;
        }

        $SendMailFromAddr = $this->USRNAME;

        $Params = $this->ReadUserUA($this->UID);

        $AddrFrom = $Params[firstname] . ( ( $Params[firstname] != "" ? " " : "" ) . $Params[lastname] );
        $AddrFrom = "\"" . ( $AddrFrom != "" ? $AddrFrom : $Params[name] ) . "\" <$SendMailFromAddr>";

        $fTO_       = $this->EncodeAddress ( $s_Compose[Status][fTO]   );
        $fCC_       = $this->EncodeAddress ( $s_Compose[Status][fCC]   );
        $fSubj_     = $this->EncodeBase64  ( $s_Compose[Status][fSubj] );
        $AddrFrom_  = $this->EncodeAddress ( $AddrFrom );
        $Id_        = "<" . md5(time() . $this->USRNAME) . "XXXXX" . time() . "XXXXX" . $this->USRNAME . ">";


        $SendMailToAddr = join(" ,", $ToInernet);

        $fp = popen("$PROGRAM_SCRIPTS/sendmail.sh \"$SendMailToAddr\" \"$SendMailFromAddr\"", "w");

        fputs($fp, "Message-ID: $Id_"                                              . $eol);
        fputs($fp, "To: $fTO_"                                                     . $eol);
        fputs($fp, "From: $AddrFrom_"                                              . $eol);
        fputs($fp, "Reply-To: $AddrFrom_"                                          . $eol);
        fputs($fp, "Return-Path: $AddrFrom_"                                       . $eol);
        fputs($fp, "Cc: $fCC_"                                                     . $eol);
        fputs($fp, "Subject: $fSubj_"                                              . $eol);
        fputs($fp, "X-Mailer: Mailer in PHP " . phpversion()                       . $eol);
        fputs($fp, "MIME-Version: 1.0"                                             . $eol);
        fputs($fp, "Content-Type: multipart/mixed;"                                . $eol);
        fputs($fp, "\t         BOUNDARY=\"$Boundary_unique\""                      . $eol . $eol);

        fputs($fp, "This is a multi-part message in MIME format."                  . $eol . $eol);

        # fMessage -----------------------------------
        fputs($fp, "--$Boundary_unique"                                            . $eol);
        fputs($fp, $fMessage                                                       . $eol);

        if  (is_array($s_Compose[Status][fTblAttach]) and count($s_Compose[Status][fTblAttach])) {
            $s = "";
            reset($s_Compose[Status][fTblAttach]);
            while(list($n, $v) = each($s_Compose[Status][fTblAttach])) {
                $s .= ($s != "" ? " OR " : "") . "fs.sysnum = $n";
            }

            $r_fs = DBFind("fs, file", "fs.sysnumfile = file.sysnum and ($s) and fs.owner = $this->UID", "fs.sysnum, fs.name, fs.sysnumfile, file.fsize, file.ftype, file.numstorage, file.url", __LINE__);
            while(!$r_fs->eof()) {

                DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('" . $this->UID . "', '" . $this->USR->sysnumdomain() . "', 'tomail', datetime('now'::abstime), '" . $r_fs->fsize() * count($ToInernet) . "', '" . $r_fs->sysnum() . "', '" . substr($SendMailToAddr, 0, 20) . "', -1, '$REMOTE_ADDR')", __LINE__);

                $ContentType = $r_fs->ftype();
                if ($ContentType == "") {
                    $ContentType = "application/octet-stream";
                }
                fputs($fp, "--$Boundary_unique"                                                                               . $eol);
                fputs($fp, "Content-Type: $ContentType"                                                            . $eol);
                fputs($fp, "Content-Transfer-Encoding: BASE64"                                                     . $eol);
                fputs($fp, "Content-Disposition: attachment; filename=\"".$this->EncodeBase64($r_fs->name())."\""  . $eol . $eol);

                if ($r_fs->url() != "") {
                    // debug($r_fs->url());
                    $f = popen("$PROGRAM_SCRIPTS/getfile.pl " . $r_fs->url() . " $this->UID" , "r");
                } else {
                    $f = fopen("$PROGRAM_FILES/storage" . $r_fs->numstorage() . "/" . $r_fs->sysnumfile(), "r");
                }

                while (!feof($f)) {
                    fputs($fp, chunk_split(base64_encode(fread($f, 57 * 600))));
                }

                if ($r_fs->url() != "") {
                    pclose($f);
                } else {
                    fclose($f);
                }

                $r_fs->Next();
            }
        }
        fputs($fp, "--$Boundary_unique--" . $eol);
        pclose($fp);
    }


    function EncodeAddress($s)
    {
        if(!ParseAddressesList($s, &$addr)) {
            return $s;
        }

        reset($addr);
        while(list($n, $v)=each($addr)) {
            $Ret .= ($Ret != "") ? ", " : "";
            $Ret .= ($v[name] != "") ? "\"" . $this->EncodeBase64($v[name]) . "\" " : "";
            $Ret .= "<" . $v[addr] . ">";
        }

        return $Ret;
    }


    function EncodeBase64($s)
    {
        global $TEMPL_CHARSET;

        if ($s == "" || preg_match("/^[a-z0-9\-\_\@\.\"\ \,\;]+$/i", $s)) {
            return $s;
        }

        return "=?$TEMPL_CHARSET?B?" . base64_encode($s) . "?=";
    }


    function SendToFolder($ToFLD, $ToUSR)
    {
        global $s_Compose;
        global $Mes;
        global $HTTP_USER_AGENT, $TEMPL_CHARSET;
        global $REMOTE_ADDR;

        if($this->UID == "" || $this->UID != 0) {
            $Params = $this->ReadUserUA($this->UID);

            $AddrFrom = $Params[firstname] . ( ( $Params[firstname] != "" ? " " : "" ) . $Params[lastname] );
            $AddrFrom = "\"" . ( $AddrFrom != "" ? $AddrFrom : $Params[name] ) . "\" <" . $this->USR->name() . "@" . $this->DOMAIN->name() . ">";
        }

        $NewMsgSysNum = NextVal("msg_seq");

        $Id_          = URLEncode( "<" . md5(time() . $this->USRNAME) . "XXXXX" . time() . "XXXXX" . $this->USRNAME . ">" );
        $fTO_         = URLEncode( $s_Compose[Status][fTO]   );
        $fSubj_       = URLEncode( $s_Compose[Status][fSubj] );
        $AddrFrom_    = URLEncode( $AddrFrom );

        if(eregi ("MSIE.*Windows", $HTTP_USER_AGENT)) {
            $ContentType = "TEXT/HTML";
            $fMessage_    = "<html>\n<head>\n<title>\n" . htmlspecialchars($fSubj). "</title>\n</head>\n<body>\n" . $s_Compose[Status][fMessage] . "\n</body>\n</html>\n";
        } else {
            $ContentType = "TEXT/PLAIN";
            $fMessage_    = $s_Compose[Status][fMessage];
        }
        $fMessage_    = URLEncode($fMessage_);
        $Size_        = strlen($fMessage_);

        DBExec("INSERT INTO msg (sysnum, sysnumfld, id, addrto, addrfrom, subj, size, send, recev, fnew, content, charset) VALUES ($NewMsgSysNum, $ToFLD, '$Id_', '$fTO_', '$AddrFrom_', '$fSubj_', '$Size_', timenow(), timenow(), true, '$ContentType', '$TEMPL_CHARSET')", __LINE__);
        DBExec("update fld set fnew = fnew + 1 where sysnum = '$ToFLD'", __LINE__);

        $this->Log("Compose ToUSR '$ToUSR' Size_ $Size_");

        // $this->out(("<br><pre>$sql</bre><br>");

        while ($fMessage_ != "") {
            $TMP = substr($fMessage_, 0, 2000);
            DBExec("INSERT INTO msgbody (sysnum, sysnummsg, body) VALUES (NextVal('msgbody_seq'), $NewMsgSysNum, '$TMP')", __LINE__);
            $fMessage_ = substr($fMessage_, 2000);
        }

        if  ( !is_array( $s_Compose[Status][fTblAttach] ) || count( $s_Compose[Status][fTblAttach] ) == 0 ) {
            return;
        }

        $s = "";
        reset($s_Compose[Status][fTblAttach]);
        while(list($n, $v) = each($s_Compose[Status][fTblAttach])) {
            $s .= ($s != "" ? " OR " : "") . "fs.sysnum = $n";
        }

        $r_fs = DBFind("fs, file", "($s) and fs.owner = '" . $this->UID . "' and fs.sysnumfile = file.sysnum", "fs.*, file.fsize", __LINE__);
        while(!$r_fs->eof()) {

            $r_fs_new = DBExec("select NextVal('fs_seq') as newnum", __LINE__);
            $ToFsNum = $r_fs_new->newnum();

            $NewName = preg_replace("/'/", "''", $r_fs->name());


            DBExec("BEGIN", __LINE__);
            DBExec("LOCK TABLE fs, file, billing IN ACCESS EXCLUSIVE MODE", __LINE__);
            DBExec("INSERT INTO FS (sysnum, ftype, up, name, sysnumfile, creat, owner) VALUES (NextVal('fs_seq'), 'a', $NewMsgSysNum, '$NewName', " . $r_fs->sysnumfile() . ", timenow(), '$ToUSR')", __LINE__);
            DBExec("commit", __LINE__);

            if ($ToUSR != $this->UID) {
                DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('" . $this->UID . "', '" . $this->USR->sysnumdomain() . "', 'tolocalmail', datetime('now'::abstime), '" . $r_fs->fsize() . "', '" . $r_fs->sysnum() . "', '" . substr("To id : $ToUSR", 0, 20) . "', 0, '$REMOTE_ADDR')", __LINE__);
                DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('$ToUSR', getdomain('$ToUSR'), 'fromlocalmail', datetime('now'::abstime), '" . $r_fs->fsize() . "', '$ToFsNum', '" . substr("From id : " . $this->UID, 0, 20) . "', 0, '$REMOTE_ADDR')", __LINE__);
            }

            DBExec("commit", __LINE__);
            $r_fs->Next();
        }
    }


    function mes()
    {
        global $Mes, $MesParam, $s_Compose, $TEMPL;


        if ($Mes == "") {
            $Mes = $s_Compose[Mes];
            unset($s_Compose[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_Compose[MesParam];
            unset($s_Compose[MesParam]);
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
        screen::script();
        global $INET_SRC, $FACE;

     	echo "<script language='javascript' src='$INET_SRC/compose.js'></script>\n";
    }


    function refreshScreen()
    {
        global $_SERVER, $SCRIPT_NAME, $INET_SRC;

        parse_str($_SERVER['QUERY_STRING'], $UrlArray);

        unset($UrlArray[sNewView]);
        unset($UrlArray[To]);
        unset($UrlArray[UID]);

        $URLString = "$INET_SRC$SCRIPT_NAME?UID=$this->UID";

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

}

} // $_COMPOSE_INC_;
?>
