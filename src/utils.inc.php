<?php
if(!isset($_UTILS_INC_)) {

$_UTILS_INC_=0;

require("tools.inc.php");

function SetData($USR, $USRNAME, $FS, $File, &$R_FS)
{
    // debug (1);
    unset($result);

    $DOMAIN = DBFind("domain", "domain.sysnum = '" . (int)$USR->sysnumdomain() . "'", "");

    $File = (int)$File;
    $FS = (int)$FS;

    if ($File != 0) {
        $R_FS = DBFind("fs f1, fs f2", "f1.sysnum = f2.up and f2.sysnum=$File and (fs1.owner = usr.sysnum and usr.sysnumdomain = domain.sysnum)", "f1.*, usr.sysnumdomain, (usr.name || '@' || domain.name) as ownername, gettree(fs.sysnum) as path");
        if ($R_FS->NumRows() != 0) {
            $FS = $R_FS->sysnum();
        } else {
            $FS = 0;
        }
    } else {
        if ($FS == "") {
            $FS = 0;
            $R_FS = new DBCursor(0);
        } else {
            $R_FS = DBFind("domain, usr, fs", "fs.ftype = 'f' and fs.sysnum = $FS and (fs.owner = usr.sysnum and usr.sysnumdomain = domain.sysnum)", "fs.*, usr.sysnumdomain, (usr.name || '@' || domain.name) as ownername, gettree(fs.sysnum) as path");
            if ($R_FS->NumRows() == 0) {
                return array("error" => 1);
            }
            if ($R_FS->sysnumfile() != 0) {
                return array("error" => 1);
            }
        }
    }

    if ($FS != 0) {
        for($R_FS->Set(0); !$R_FS->eof(); $R_FS->Next()) {
            for($i=0; $i < $R_FS->numfields(); $i++) {
                $result[$R_FS->fieldname($i)] = $R_FS->Field($i);
            }
        }
        $R_FS->Set(0);
    }

    $r_acc = DBFind("acc", "sysnumfs = $FS and ((acc.username = '') or " .
                                               "(acc.username = '". "@" . $DOMAIN->name() . "') or " .
                                               "(acc.username = '" . $USRNAME  . "'))", "");
    for($r_acc->Set(0); !$r_acc->eof(); $r_acc->Next()) {
        $username = $r_acc->username();
        if ($username == "")           { $ftype = "a"; }
        else if ($username[0] == "@")  { $ftype = "d"; }
        else                           { $ftype = "p"; };
        $result[access][$ftype] = $r_acc->access();
    }

    $r_acc = DBFind("acc", "sysnumfs = $FS", "");
    for($r_acc->Set(0); !$r_acc->eof(); $r_acc->Next()) {
        $result[linkto][] = array(access => $r_acc->access(), username => $r_acc->username(), expdate => $r_acc->expdate(), access_tracking => $r_acc->access_tracking(), hash => $r_acc->hash());
    }


    // Direct access to folder
    $FldAccDirect = ($FS == 0) || ($result[owner] == $USR->sysnum()) || ($USR->lev() == 2) || ($USR->lev() == 1 && $result[sysnumdomain] == $USR->sysnumdomain());

    // Access to folder
    $FldAcc = $FldAccDirect || isset($result[access]);

    // Folder Read only
    $FldRO = (!$FldAccDirect) && (($result[access][p] == "r") || ($result[access][d] == "r") || ($result[access][a] == "r"));

    $result[FldAccDirect] = $FldAccDirect;
    $result[FldAcc]       = $FldAcc;
    $result[FldRO]        = $FldRO;



    //Debug(1);
    $SQL = "select fs.*, sign(fs.sysnumfile), usr.name as usrname, usr.sysnumdomain, domain.name as domainname, 0 as fsize, ''         as cont from fs,       usr, domain where fs.owner = usr.sysnum and usr.sysnumdomain = domain.sysnum and fs.ftype = 'f' and fs.up = $FS and fs.sysnumfile = 0           " . ($FS == 0 ? " and usr.sysnum = '" . $USR->sysnum() . "' " : "")
         . "order by sign(fs.sysnumfile), fs.name, fs.sysnum";

    $r_fs = DBExec($SQL, "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    for($r_fs->Set(0); !$r_fs->eof(); $r_fs->Next()) {
        for($i=0; $i < $r_fs->numfields(); $i++) {
            $result[Files][$r_fs->sysnum()][$r_fs->fieldname($i)] = $r_fs->Field($i);
        }
    }


    //Debug("2 " . $r_fs->NumRows());
    $SQL = "select fs.*, sign(fs.sysnumfile), usr.name as usrname, usr.sysnumdomain, domain.name as domainname, file.fsize, file.ftype as cont from fs, file, usr, domain where fs.owner = usr.sysnum and usr.sysnumdomain = domain.sysnum and fs.ftype = 'f' and fs.up = $FS and fs.sysnumfile = file.sysnum " . ($FS == 0 ? "and usr.sysnum = '" . $USR->sysnum() . "' " : "")
         . "order by sign(fs.sysnumfile), fs.name, fs.sysnum";

    $r_fs = DBExec($SQL, "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    for($r_fs->Set(0); !$r_fs->eof(); $r_fs->Next()) {
        for($i=0; $i < $r_fs->numfields(); $i++) {
            $result[Files][$r_fs->sysnum()][$r_fs->fieldname($i)] = $r_fs->Field($i);
        }
    }



    $r_fs = DBExec("SELECT fs.sysnum, count(fs1.sysnum) AS itemscount FROM fs LEFT JOIN fs fs1 ON fs.sysnum = fs1.up AND fs.ftype = 'f' WHERE fs.sysnumfile = 0 AND fs.up = $FS AND fs.ftype = 'f'" . ($FS == 0 ? " AND fs.owner = '" . $USR->sysnum() . "'" : "") . " GROUP BY fs.sysnum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    for($r_fs->Set(0); !$r_fs->eof(); $r_fs->Next()) {
        $result[Files][$r_fs->sysnum()][SubItemsCount] = $r_fs->ItemsCount();
    }


    //Debug("3 " . $r_fs->NumRows());
    $r_acc  = DBFind("acc, fs", "acc.sysnumfs = fs.sysnum and fs.ftype = 'f' and fs.up = $FS and ((acc.username = '') or " .
                                                                              "(acc.username = '". "@" . $DOMAIN->name() . "') or " .
                                                                              "(acc.username = '" . $USRNAME . "'))", "acc.*");
    for($r_acc->Set(0); !$r_acc->eof(); $r_acc->Next()) {
        $username = $r_acc->username();
        if ($username == "")          { $ftype = "a"; }
        else if ($username[0] == "@") { $ftype = "d"; }
        else                          { $ftype = "p"; };
        $result[Files][$r_acc->sysnumfs()][access][$ftype] = $r_acc->access();
    }

    $r_acc = DBFind("acc, fs", "acc.sysnumfs = fs.sysnum and fs.ftype = 'f' and fs.up = $FS" . ($FS == 0 ? " and fs.owner = '" . $USR->sysnum() . "'" : ""), "acc.*");
    for($r_acc->Set(0); !$r_acc->eof(); $r_acc->Next()) {
        $result[Files][$r_acc->sysnumfs()][linkto][] = array(access => $r_acc->access(), username => $r_acc->username(), expdate => $r_acc->expdate(), access_tracking => $r_acc->access_tracking(), hash => $r_acc->hash());
    }


    $r_clip = DBFind("clip, fs", "clip.owner = '" . $USR->name() . "@" . $DOMAIN->name() . "' and clip.sysnumfs = fs.sysnum and fs.up = $FS", "");
    for($r_clip->Set(0); !$r_clip->eof(); $r_clip->Next()) {
        $result[Files][$r_clip->sysnumfs()][Clip] = $r_clip->Field("ftype");
    }

    for (_reset($result[Files]); $key=_Key($result[Files]); _next($result[Files])) {
        $file = $result[Files][$key];

        // Direct access to file
        $FileAccDirect = ($file[owner] == $USR->sysnum()) || ($USR->lev() == 2) || ($USR->lev() == 1 && $file[sysnumdomain] == $USR->sysnumdomain());

        // Access to file
        $FileAcc = $result[FldAcc] && ($FileAccDirect || isset($file[access]));

        // File Read only
        $FileRO = (!$FileAccDirect) && ($file[access][p] == "r" || $file[access][d] == "r" || $file[access][a] == "r" || $FldRO);

        $result[Files][$key][FileAccDirect] = $FileAccDirect;
        $result[Files][$key][FileAcc]       = $FileAcc;
        $result[Files][$key][FileRO]        = $FileRO;
    }

    // debug (2);
    return $result;
}


function TimedependAuthorizeHash($UID, $time)
{
    return md5(md5($time . $UID) . $time . $UID);
}


function FSdependAuthorizeHash($UID, $SysNumFs)
{
    return md5(md5($SysNumFs . $UID) . md5($SysNumFs));
}


function AuthorizeKey($UID)
{
    return ereg_replace("[\\\/;,]", "1", crypt( md5( URLDecode($UID) ), "44" ));
}


function MakeOwnerFileDownloadURL($name, $FS, $UID, $DownloadKind)
{
    global $INET_SRC;
    $result = "$INET_SRC/view_file.php/" . urlencode($name) . "?UID=" . urlencode($UID);

    if (ereg("^[0-9]+$", $UID)) {
        $time = time();
        $Key = TimedependAuthorizeHash($UID, $time);
        $result .= "&Key=" . urlencode($Key);
        $result .= "&Time=" . urlencode($time);
    } else {
        $Key = AuthorizeKey($UID);
        $result .= "&Key=" . urlencode($Key);
    }

    $result .= "&sDownload=$DownloadKind";
    $result .= "&TagFile=$FS";
    $result .= "&NamFile=" . urlencode($name);

    return $result;
}

function ParseMesText($mes, $UID)
{
    global $Fld, $Msg;
    global $INET_SRC, $FACE;

    $delim = ", ;[]\'\"<>()".chr(13).chr(10);
    $i=0;
    $k="";
    while($i < strlen($mes)) {
        while (($i < strlen($mes)) && strpos($delim, $mes[$i])) {
            $k .= htmlspecialchars($mes[$i]);
            // $k .= $mes[$i];
            $i++;
        }

        $r = "";
        while (($i < strlen($mes)) && !strpos($delim, $mes[$i])) {
            $r .= htmlspecialchars($mes[$i]);
            // $r .= $mes[$i];
            $i++;
        }

        if ($r == "") {
            continue;
        }

        if (strpos(" $r", "mailto:")==1) {
            if(strlen($r) > 7) {
                $r = "<a href='$INET_SRC/compose.php?UID=$UID&FACE=$FACE&To=".substr($r,7)."&Ret=" . urlencode("MES#$Fld#$Msg") . "&sNewView=on' target='_top'>$r</a>";
            }
        } else {
            if (strpos(" $r", "@")>=1) {
                if (strlen($r) > 1) {
                    $r = "<a href='$INET_SRC/compose.php?UID=$UID&FACE=$FACE&To=$r&Ret=" . urlencode("MES#$Fld#$Msg") . "&sNewView=on' target='_top'>$r</a>";
                    // echo "$r<br>";
                }
            }
        }

        if ((strpos(" $r", "www")==1) && (strpos(" $r", ".") >= 1)) {
            $r = "<a href='http://$r' target=_blank>$r</a>";
        }

        if (strpos(" $r", "http:")==1) {
            if (strlen($r) > 5) {
                $r = "<a href='$r' target=_blank>$r</a>";
            }
        }

        if (strpos(" $r", "ftp:")==1) {
            if (strlen($r) > 4) {
                $r = "<a href='$r' target=_blank>$r</a>";
            }
        }

        $k .= $r;
    }

    //$k = nl2br($k);
    $k = ereg_replace("\n|\r\n", "<br>", $k);

    return $k;
}


function ParseMesHTML($mes, $UID)
{
    global $Fld, $Msg, $INET_SRC, $FACE;

    $mes = eregi_replace("target[ ]*=[ ]*_top", "", $mes);
    $mes = eregi_replace("<[ ]*A[ \$]*([^>]*[ ]*)>", "<a \\1 target='_blank'>", $mes);
    $mes = eregi_replace("<[ ]*A([^>]*href=['\"])mailto:([^>]*)(['\"][^>]*)target='_blank'([^>]*)>", "<a \\1$INET_SRC/compose.php?UID=$UID&FACE=$FACE&sNewView=on&To=\\2&Ret=".urlencode("MES#$Fld#$Msg")."\\3\\4 target='_top'>", $mes);
    $mes = eregi_replace("<[ ]*FORM[ ]+([^>]*[ ]*)>", "<FORM \\1 target='_blank'>", $mes);
    return $mes;
}

function GetMakeButtonBlankList()
{
    global $makeButtonBlankList;

    $rez = "";
    _reset($makeButtonBlankList);
    while(list($n1, $v1) = _each($makeButtonBlankList)) {
        _reset($v1);
        while(list($n2, $v2) = _each($v1)) {
            $rez .= "\n  document.forms[\"$n1\"][\"$v2\"].value = \"\";";
        }
    }

    if ($rez == "") {
        return "";
    }

    return "<!--\n--><script language=\"JavaScript\">\nfunction MakeButtonBlankList() {" . $rez . "\n}\n</script><!--\n-->";
}


function makeButton($param)
{
    global $HTTP_USER_AGENT;

    global $makeButtonBlankList;

    if(!isset($makeButtonBlankList)) {
        $makeButtonBlankList = array();
    }

    parse_str(ereg_replace("&(nbsp|amp);", "%26\\1;", $param));

    $agent = "Other";
    if(eregi ("Opera", $HTTP_USER_AGENT))     { $agent = "Opera"; } else
    if(eregi ("Netscape6", $HTTP_USER_AGENT)) { $agent = "NSC6";  } else
    if(eregi ("MSIE", $HTTP_USER_AGENT))      { $agent = "MSIE";  }

    if ( $type == "" ) { $type = 0; }

    if ($value == "") {
        if($form != "" || $type == 2) {
            if ($type == 1) {
               $rez .= "<input type=\"hidden\" name=\"$name\" value=\"\">";
               $rez .= "<a href=\"JavaScript:buttonSubmit('$form','$name','$name');\" ";
               $makeButtonBlankList[$form][] = $name;
               if($name  != "") { $name .= "_pic"; }
            } else {
               $rez .= "<a href=\"" . stripslashes($onclick) . "\" ";
            }
            if ($imgact != "" && ($form != "" || $type == 2)) {
                $rez .= "onMouseOver=\"JavaScript:document." . ($form != "" ? ($form . ".") : "") . $name . ".src='" . $imgact . "'; window.status='';\" ";
                $rez .= "onMouseOut=\"JavaScript:document." . ($form != "" ? ($form . ".") : "") . $name . ".src='" . $img . "'; window.status = '';\" ";
                $rez .= "onMouseMove=\"window.status = '$title'; return 0;\" ";
            }
            $rez .= ">";
            $rez .= "<img border=0 ";
        } else {
            $rez .= "<input type='image' border=1 ";
        }
        if($name  != "") { $rez .= "name= \"$name\" "; }
        if($title != "") { $rez .= "alt=\"$title\" "; }
        if($class != "") { $rez .= "class=\"$class\" "; }
        if($img != "") {
            $rez .= "src=\"$img\" ";
            if ($imgalign == "") { $imgalign = "absmiddle"; }
            $rez .= "align = \"" . $imgalign . "\" ";
        }
        $rez .= ">";
        if($form != "" || $type == 2) {
            $rez .= "</a>";
        }

        if ($img != "" && $imgact != "" && $name != "") {
          $rez = "<script language=\"JavaScript\">\n".
                 "var $name" . "_picact_cache = new Image;\n" .
                 "$name" . "_picact_cache.src = '" . $imgact ."';\n" .
                 "</script>" . $rez;
        }

        return "<!--\n-->" . $rez . "<!--\n-->";
    }

    switch ($type) {
        case 0:
        case 1:
                if ($form == "" || $name == "") {
                    if($name  != "") { $rez .= "name= \"$name\" "; }
                    if($title != "") { $rez .= "title=\"$title\" "; }
                    if($class != "") { $rez .= "class=\"$class\" "; } else { $rez .= "class=\"toolsbarb\" "; }
                    if($value != "") { $rez .= "value=\"$value\" "; }
                    if($agent == "MSIE" || $agent == "NSC6" || $agent == "Opera") {
                        if($width != "") { $stl .= "width: $width; "; }
                        if($padding != "") { $stl .= "padding: $padding; "; }
                        if ($agent != "Opera") {
                            if($img   != "") { $stl .= "background-image: url($img); background-position: 3; background-repeat: no-repeat; "; }
                        }
                        if($stl   != "") { $style .= ($style != "" ? "; " : "") . $stl; }
                        if($style != "") { $rez .= "style=\"$style\" "; }
                    }
                    $rez = "<input type='submit' $rez>";
                } else {
                    if($title != "") { $rez .= "title=\"$title\" "; }
                    if($class != "") { $rez .= "class=\"$class\" "; } else { $rez .= "class=\"toolsbarb\" "; }
                    if($value != "") { $rez .= "value=\"$value\" "; }
                    //$rez .= "onClick=\"JavaScript:$form.$name.value='$value';  rez = 1; " . ($agent != "NSC6" ? "if ($form.onsubmit) { rez = $form.onsubmit(); };" : "")  . " if (rez) { $form.submit(); }\" ";
                    $rez .= "onClick=\"JavaScript:buttonSubmit('$form','$name','$value');\" ";

                    if($agent == "MSIE" || $agent == "NSC6" || $agent == "Opera") {
                        $stl .= "padding: 0; ";
                        if($width != "") { $stl .= "width: $width; "; }
                        if($padding != "") { $stl .= "padding: $padding; "; }
                        if($stl != "") { $style .= ($style != "" ? "; " : "") . $stl; }
                        if($style != "") { $rez .= "style=\"$style\" "; }
                    }

                    if($agent == "MSIE" || $agent == "NSC6") {
                        $rez = "<button $rez>";
                        if($img != "") {
                            $rez .= "<img src=\"$img\"";
                            if ($imgalign != "") { $rez .= " align=\"$imgalign\""; } else { $rez .= " align=\"bottom\""; }
                            $rez .= ">&nbsp;&nbsp;";
                        }
                        if($value != "") { $rez .= $value; }
                        $rez .= "</button>";
                    } else {
                        if($value != "") { $rez .= "value=\"$value\" "; }
                        $rez = "<input type='button' $rez>";
                    }

                    $rez .= "<input type=\"hidden\" name=\"$name\" value=\"\">";
                    $makeButtonBlankList[$form][] = $name;
                }
                break;

        case 2:
               if($name  != "") { $rez .= "name=\"$name\" "; }
               if($title != "") { $rez .= "alt=\"$title\" "; }
               if($class != "") { $rez .= "class=\"$class\" "; }

               if($onclick != "") { $rez .= "onClick=\"" . stripslashes($onclick) . "\" "; }
               if($class == "") { $rez .= "class=\"toolsbarb\" "; }
               if($agent == "MSIE" || $agent == "NSC6" || $agent == "Opera") {
                   $stl .= "padding: 0; ";
                   if($width != "") { $stl .= "width: $width; "; }
                   if($padding != "") { $stl .= "padding: $padding; "; }
                   if($stl != "") { $style .= ($style != "" ? "; " : "") . $stl; }
                   if($style != "") { $rez .= "style=\"$style\" "; }
               }

               if($agent == "MSIE" || $agent == "NSC6") {
                 $rez = "<button $rez>";
                 if($img != "") {
                    $rez .= "<img src=\"$img\"";
                    if ($imgalign != "") { $rez .= " align=\"$imgalign\""; } else { $rez .= " align=\"bottom\""; }
                    $rez .= ">&nbsp;&nbsp;";
                 }
                 if($value != "") { $rez .= $value; }
                 $rez .= "</button>";
               } else {
                 if($value != "") { $rez .= "value=\"$value\" "; }
                 $rez = "<input type='button' $rez>";
               }
               break;
        default:
               $rez .= "=$type=$agent=";
    }

    return  "<!--\n-->" . $rez . "<!--\n-->";
}

function FoldersSize($fs)
{
    $countFiles = 0;
    $countFolders = 0;
    $sizeFiles = 0;
    $r_fs = DBExec("select fs.sysnumfile, fs.sysnum, file.fsize from fs left join file on fs.sysnumfile = file.sysnum where fs.up = '$fs'");
    while(!$r_fs->eof()) {
      if ($r_fs->sysnumfile()) {
         $countFiles++;
         $sizeFiles += $r_fs->fsize();
      } else {
         $countFolders++;
         $TMP = FoldersSize($r_fs->sysnum());
         $countFiles   += $TMP[0];
         $countFolders += $TMP[1];
         $sizeFiles    += $TMP[2];
      }
      $r_fs->next();
    }

    return (array($countFiles, $countFolders, $sizeFiles));

}


function DecodeAccessFlag($flag)
{
     switch($flag) {
         case "n": return 0;
                   break;
         case "r": return 1;
                   break;
         case "u": return 2;
                   break;
         case "w": return 3;
                   break;
     }

     return 0;
}

} // $_UTILS_INC


?>
