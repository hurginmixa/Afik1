<?php

if(!isset($_TOOLS_INC_)) {

$_TOOLS_INC_=0;



require("db.inc.php");


function ShArr($arr)
{
    if (func_num_args() > 1) {
          $shift = func_get_arg(1);
    }

    $rez = "";
    if (!is_array($arr)) {
        return $rez;
    }

    reset($arr);
    while (list($n, $v) = each($arr)) {
      if (is_array($v)) {
          $rez .= "$shift [$n]=Array<br>" . chr(13) . chr(10);
          $rez .= ShArr($v, $shift . "+____");
      } else {
          $rez .= "$shift [$n]=".htmlspecialchars($v) . "<br>" . chr(13) . chr(10);
      }
    }

    return $rez;
}


function ShGLOBALS()
{
    reset($GLOBALS);
    while (list($n, $v) = each($GLOBALS)) {
      $ar[$n]=$v;
    }
    ksort($ar);
    return ShArr($ar);
}


function StrF($Num, $f)
{
    $s = "1";
    $r = "";
    for($i=1; $i < $f; $i++) {
        $s = $s . "0";
    }


    while(($s > 1) && ($s > $Num)) {
        $r = $r . "0";
        $s = substr($s, 0, strlen($s) - 1);
    }

    $r = $r . $Num;

    return $r;
} // function StrF



function GetStr($f, $l)
{
  for($c=ord($f); $c<=ord($l); $c++) {
    $r .= chr($c);
  }

  return $r;
}


function Debug($mes)
{
    return;

    global $DebFlag;
    global $PROGRAM_ROOT;
    $DebFileName = "/deb/deb";
    $DebFileName = "/dev/tty5";
    $DebFileName = "$PROGRAM_ROOT/debug";

    if($DebFlag == 0) {
        // @unlink($DebFileName);
        $DebFlag = 1;
    }

    $DebFile = fopen($DebFileName, "a");
    fputs($DebFile, $mes . chr(13) . chr(10) );
    // fputs($DebFile, $mes);
    fclose($DebFile);
} // function Debug



function MkSpecTime($S)
{
    if ($S == "") {
        return 0;
    }

    if(eregi("[a-z]{3} ([a-z]{3}) ([0-9]{2}) ([0-9]{2}):([0-9]{2}):[0-9]{2} ([0-9]{4})", $S, $arr)) {
        eregi("[a-z]{3}:([0-9]{1,2})", "Jan:1 Feb:2 Mar:3 Apr:4 May:5 Jun:6 Jul:7 Aug:8 Sep:9 Okt:10 Now:11 Dec:12", $arr1);
        return mktime($arr[3], $arr[4], 0, $arr1[1], $arr[2], $arr[5]);
    } else {
        if(eregi("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):[0-9]{2}", $S, $arr)) {
            return mktime($arr[4], $arr[5], 0, $arr[2], $arr[3], $arr[1]);
        } else {
            return mktime((int)substr($S, 11, 2), (int)substr($S, 14, 2), 0, (int)substr($S, 0, 2), (int)substr($S, 3, 2), (int)substr($S, 6, 4));
        }
    }
}



function mkdate($S)
{
    if ($S == "") {
        return "&nbsp";
    }

    return date("j M Y", mkSpecTime($S));
}


function mktime1($S)
{
    if ($S == "") {
        return "&nbsp";
    }

    return date("G:i", mkSpecTime($S));
}


function mkdatetime($S)
{
    if ($S == "") {
        return "&nbsp";
    }

    return date("j M Y G:i", mkSpecTime($S));
}


function ToLeft($s, $i)
{
    $s = substr($s, 0, $i);
    while (strlen($s) < (int)$i) {
        $s .= " ";
    }

    $r = "";
    while (strlen($s) > 0) {
        $c = substr($s, 0, 1);
        if ($c != " ") {
        $r .= $c;
        } else {
        $r .= "&nbsp";
        }

        $s = substr($s, 1);
    }

    return $r;
}


function ReformatToLeft($s, $i)
{
    #$s = (strlen($s) <= $i) ? str_pad($s, $i) : substr($s, 0, $i) . "...";

    $Delimited = "";
    if (func_num_args() > 2) {
       $Delimited = func_get_arg(2);
    }


    if ($Delimited == "") {
      $s = (strlen($s) <= $i) ? $s : substr($s, 0, $i) . "...";
      $s = str_replace(" ", "&nbsp;", htmlspecialchars($s));
    } else {
      $s = str_replace(" ", "&nbsp;", htmlspecialchars(chunk_split($s, $i, "\n")));
      $s = preg_replace("/\n/ms", "$Delimited", $s);
      $s = preg_replace("/$Delimited$/", "", $s);
    }
    return $s;
}


function AsSize($size)
{
    if (!ereg("^[0-9]+$", $size)) {
        $size = 0;
    }

    $blank = "&nbsp;";
    if (func_num_args() > 1) {
          $blank = func_get_arg(1);
    }

    $r = array("{$blank}B", "KB", "MB", "GB", "TB");

    $i = 0;
    while($size >= 1024 && $i < count($r) - 1) {
      $i++;
      $size = $size / 1024.0;
    }

    $size_f = sprintf("%7.2f", $size);
    if ($size == (int)$size_f) {
      return $size . $blank . $r[$i];
    }
    return  $size_f . $blank . $r[$i];
}


function ParamToURL($arr)
{
    $r = "";

    if (count($arr) == 0) {
      return $r;
    }


    reset($arr);
    while (list($i, $v) = each($arr)) {
      if (!is_array($GLOBALS[$v]) && count($GLOBALS[$v]) != 0) {
        $r = $r . urlencode($v) . "=" . urlencode($GLOBALS[$v]) . "&";
      } else {
        if (is_array($GLOBALS[$v]) && count($GLOBALS[$v]) != 0) {
          reset($GLOBALS[$v]);
          while (list($i1, $v1) = each($GLOBALS[$v])) {
            $r = $r . urlencode($v . "[]") . "=" . urlencode($v1) . "&";
          }
        }
      }
    }

    return $r;
}


$v= phpversion();
if( $v < 4 ) {
    function in_array($elm, $arr)
    {
        if (count($arr) == 0) {
        return 0;
        }


        reset($arr);
        while (list($i, $v) = each($arr)) {
        if ($elm == $v) {
            return true;
        }
        }

        return false;
    }
}


function CheckUser($u)
{
    $k = exec("cat /etc/passwd | grep \"^$u"."[ \\t]*:\"");

    if (ord($k) != 0) {
        return 1;
    } else {
        return 0;
    }
}


function CheckAlias($u)
{
    $k = exec("cat /etc/aliases | grep \"^$u"."[ \\t]*:\"");

    if (ord($k) != 0) {
        return 1;
    } else {
        return 0;
    }
}


function _reset(&$r)
{
     if (!is_array($r)) {
       return 0;
     }

     return reset($r);
}


function _key(&$r)
{
     if (!is_array($r)) {
       return ;
     }

     return key($r);
}


function _next(&$r)
{
     if (!is_array($r)) {
       return ;
     }

     return next($r);
}


function _each(&$r)
{
     if (!is_array($r)) {
       return ;
     }

     return each($r);
}



function ParseAddress($To, &$arr)
{
    $chars = " " . getstr("A", "Z") . getstr("a", "z") . getstr("0", "9") . "@.-_";
    $arr = array();
    $l = 0;
    $name = "";
    $addr = "";

    $i = 0;
    while  ($i < strlen($To) && ($To[$i] == " ")) {
        $i++;
    }

    while ($i < strlen($To)) {
        if (!strpos($chars."\"<>,;", $To[$i]) && ($To[$i] != " ")) {
            echo "=$To[$i]=";
            return 0;
        }

        if ($i < strlen($To) && ($To[$i] == "\"")) {
              $i++;

              while  ($i < strlen($To) && ($To[$i] != "\"")) {
                    $name .= $To[$i];
                    $i++;
              }
              $i++;
        } else if ($i < strlen($To) && ($To[$i] == "<")) {
            $i++;
            while  ($i < strlen($To) && (strpos($chars, $To[$i]))) {
                $addr .= $To[$i];
                $i++;
            }
            if ($i < strlen($To) && ($To[$i] != ">")) {
                  echo "=error1=<br>";
                  return 0;
            }

            $i++;

        } else {
            while  ($i < strlen($To) && (strpos($chars, $To[$i]))) {
                  $addr .= $To[$i];
                  $i++;
            }
        }


        while ($i < strlen($To) && ($To[$i] == " ")) {
            $i++;
        }

        if (($i < strlen($To)) && ($To[$i] != ",") && ($To[$i] != ";")) {
            if ($addr != "") {
                  $name .= ($name != "" ? " " : "") . $addr;
                  $addr = "";
            }
            continue;
        }

        if ($addr == "") {
            echo "=error2=<br>";
            return 0;
        }

        $arr[$l][name] = $name;
        $arr[$l][addr] = $addr;
        $name = "";
        $addr = "";
        $l++;

        $i++; // skip ","
    }

    return 1;
}


function ParseAddressesList($list, &$ResultArray)
{
    $ResultArray = array();

    $TMPArr = preg_split("/\s*[,;]\s*/", $list);

    //echo sharr($TMPArr);

    if (!is_array($TMPArr) || count($TMPArr) == 0) {
        return 0;
    }

    $result = 1;

    reset($TMPArr);
    while(list($n, $v) = each($TMPArr)) {
        //echo htmlspecialchars($v), "<br>";
        $v = preg_replace("/(^\s+)|(\s+$)/", "", $v);
        if (!preg_match("/^(((([\"][^\"]*[\"])|([^\"]+?))\s+)?(([a-z0-9\@\-\_\.]+)|(< *[a-z0-9\@\-\_\.]+ *>)))$/i", $v, $MATH)) {
            $result = 0;
            //echo htmlspecialchars($v), "=<br>";
            continue;
        }
        $MATH[3] = preg_replace("/(^\" *)|( *\"$)/", "", $MATH[3]);
        $MATH[6] = preg_replace("/(^< *)|( *>$)/",   "", $MATH[6]);

        if (!is_emailaddress($MATH[6])) {
            $result = 0;
            //echo htmlspecialchars($v), "=<br>";
            continue;
        }

        $TMPSet = array();
        $TMPSet[name]  = $MATH[3];
        $TMPSet[addr]  = $MATH[6];
        $ResultArray[] = $TMPSet;
    }

    return $result;
}


function is_emailaddress($addr)
{
    if (func_num_args() > 1) {
        $ChardCheck = func_get_arg(1);
    }

    if($ChardCheck) {
        return preg_match("/^[a-z0-9\-\_]+(\.[a-z0-9\-\_]+)*?@[a-z0-9\-\_]+(\.[a-z0-9\-\_]+)+$/i", $addr);
    } else {
        return preg_match("/^[a-z0-9\-\_]+(\.[a-z0-9\-\_]+)*?(@[a-z0-9\-\_]+(\.[a-z0-9\-\_]+)+)?$/i", $addr);
    }
}


function GetCurrDate()
{
    return strftime("%Y-%m-%d %H:%M:%S");
}


function WebLog()
{
    global $WEB_LogFileName, $_SERVER, $UID;

    $Mes = "";
    for ($i=0; $i < func_num_args(); $i++) {
        $Mes .= func_get_arg($i);
    }

    //echo $WEB_LogFileName; exit;

    $f = @fopen($WEB_LogFileName, "a+");
    if ($f) {
        fputs($f, GetCurrDate() . " " . posix_getpid() . " {$UID}@{$_SERVER[REMOTE_ADDR]}:{$_SERVER[REMOTE_PORT]} $_SERVER[REQUEST_METHOD] $_SERVER[SCRIPT_NAME] >$Mes\n");
        fclose($f);
    } else {
        echo "Error with Log";
        exit;
    }

    //debug(strftime("%Y-%m-%d %H:%M:%S") . "> '" . $this->USRNAME . "' " . $Mes);
}


function ShInterval($inter)
{
    $TMP = $inter;

    $sec   = $TMP % 60;
    $TMP   = ($TMP - $sec) / 60;
    if (strlen($sec) < 2) {
        $sec = "0" . $sec;
    }

    $min   = $TMP % 60;
    $TMP   = ($TMP - $min) / 60;
    if (strlen($min) < 2) {
        $min = "0" . $min;
    }

    $hours = $TMP % 24;
    $days   = ($TMP - $hours) / 24;
    if (strlen($hours) < 2) {
        $hours = "0" . $hours;
    }

    $res = "";
    if ($days != 0) {
        $res .= $days . " days";
    }
    if ($hours != 0) {
        $res .= " " . $hours . " hours";
    }
    if ($min != 0 || $sec != 0) {
        $res .= " " . $min . " min";
    }
    if ($sec != 0) {
        $res .= " " . $sec . " sec";
    }

    return $res;
}


function date_pad($year, $mon, $mday)
{
    $ret = str_pad($year, 4, 0, STR_PAD_LEFT) . "-" . str_pad($mon, 2, 0, STR_PAD_LEFT) . "-" . str_pad($mday, 2, 0, STR_PAD_LEFT);

    if (func_num_args() > 3) {
        $hours = func_get_arg(3);
        $mins  = 0;
        if (func_num_args() > 4) {
            $mins = func_get_arg(4);
        }
        $ret .= " " . str_pad($hours, 2, 0, STR_PAD_LEFT) . ":" . str_pad($mins, 2, 0, STR_PAD_LEFT);
    }

    return $ret;
}

function html2text($src)
{
	$res = $src;
	$res = preg_replace(array("'<script[^>]*?>.*?</script>'si",  "'</tr>'i", "'<img[^>]*?>'si"), array("", "<br>", "[IMAGE]"), $res);
	$res = strip_tags($res, "<br><p><img>");
	$res = preg_replace(array( "'\n'", "'<br( \/)>'i", "'<p>'i", "'&nbsp;?'i" ),
                       	array( "",     "\n",           "\n",     " "          ), $res);
	$res = strip_tags($res);
	return $res;
}

} // $_TOOLS_INC_

?>
