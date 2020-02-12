<?php

$supportaddr = "techsupport@pcb.co.il";
$lastnumfile = "probe/calltechnum.txt";
$lastlogfile = "probe/calltechlog.csv";

if ($HTTP_HOST == "") {
  $HTTP_HOST = $HOSTNAME;
}


main();

#foreach($GLOBALS as $n => $v) {
#   echo $n, "=", $v, "<br>\n";
#}

#echo "================================================<br>\n";

#foreach($HTTP_POST_VARS as $n => $v) {
#   echo $n, "=", $v, "<br>\n";
#}

#echo "================================================<br>\n";

foreach($HTTP_COOKIE_VARS as $n => $v) {
   echo $n, "=", $v, "<br>\n";
}

function main()
{
   global $HTTP_POST_VARS, $supportaddr, $HTTP_HOST;
   if($HTTP_POST_VARS[submit] == "") {
     Screen1(0);
     return;
   }

   $Num = GetLastNum();

   $body = GetLetterBody($Num);

   $body = "<HTML>"
         . "<HEAD>"
         . "<META content='text/html; charset=windows-1255' http-equiv=Content-Type>"
         . "<title>���� ���� �����</title>"
         . "</HEAD>"
         . $body
         . "</HTML>";

   $addr = $supportaddr . ($HTTP_POST_VARS[email] != "" ? (", <" . $HTTP_POST_VARS[email]) . ">" : "");


   #$rez = mail($addr, "support message N $Num", $body, "Content-Type: text/html; charset=windows-1255\r\n");
   $rez = @mail($addr, "support message N $Num", $body, "Content-Type: text/html; charset=windows-1255\r\nFrom: \"support system\" <support@pcb.co.il>\r\n");

   if(!$rez) {
     Screen1(1);
     return;
   }


   //echo "<p dir='ltr'>";
   foreach($HTTP_POST_VARS as $n => $v) {
      setcookie($n, $v, time() +  3600, "/", $HTTP_HOST);
      //setcookie
   }
   //echo "</p>";

   WriteLog($Num);

   echo "$body";
}


function WriteLog($Num)
{
    global $lastlogfile;
    global $HTTP_POST_VARS;
    $f = fopen($lastlogfile, "ab");
    flock($f, 2);

    $arr[] = FILE_TXT($Num);
    $arr[] = FILE_TXT($HTTP_POST_VARS[fname]);
    $arr[] = FILE_TXT($HTTP_POST_VARS[lname]);
    $arr[] = FILE_TXT($HTTP_POST_VARS[phone]);
    $arr[] = FILE_TXT($HTTP_POST_VARS[email]);
    $arr[] = FILE_TXT($HTTP_POST_VARS[depart]);
    $arr[] = FILE_TXT($HTTP_POST_VARS[os]);
    $arr[] = FILE_TXT($HTTP_POST_VARS[mess]);

    reset($arr);
    $rez = "";
    while(list($n, $v) = each($arr)) {
      $rez .= ($rez != "" ? ";" : "") . $v;
    }

    fputs($f, $rez . "\n");
    flock($f, 3);
    fclose($f);
}

function GetLetterBody($Num)
{
   global $HTTP_POST_VARS;

   $body = html("BODY", "dir='rtl' lang='he'",
             html("CENTER", "",
               html("H1", "", "���� ���� �����"),
               html("TABLE", "border = 1", html("TR", "", html("TD", "",
                 html("TABLE", "",
                    html("TR", "",
                      html("TD", "", "��' ����"), html("TD", "", ":"),
                      html("TD", "", HTML_TXT($Num))
                    ),
                    html("TR", "",
                      html("TD", "", "�� ����"), html("TD", "", ":"),
                      html("TD", "", HTML_TXT($HTTP_POST_VARS[fname]))
                    ),
                    html("TR", "",
                      html("TD", "", "�� �����"), html("TD", "", ":"),
                      html("TD", "", HTML_TXT($HTTP_POST_VARS[lname]))
                    ),
                    html("TR", "",
                      html("TD", "", "�����"), html("TD", "", ":"),
                      html("TD dir='ltr' align='right'", "", HTML_TXT($HTTP_POST_VARS[phone]))
                    ),
                    html("TR", "",
                      html("TD", "", "���� ��������"), html("TD", "", ":"),
                      html("TD dir='ltr' align='right'", "", HTML_TXT($HTTP_POST_VARS[email]))
                    ),
                    html("TR", "",
                      html("TD", "", "�����"), html("TD", "", ":"),
                      html("TD", "", HTML_TXT($HTTP_POST_VARS[depart]))
                    ),
                    html("TR", "",
                      html("TD", "", "����� �����"), html("TD", "", ":"),
                      html("TD dir='ltr' align='right'", "", HTML_TXT($HTTP_POST_VARS[os]))
                    ),
                    html("TR", "",
                      html("TD", "valign='top'", "���� �����"), html("TD", "valign='top'", ":"),
                      html("TD", "", HTML_TXT($HTTP_POST_VARS[mess]))
                    ),
                    html("A", "href='http://ultra1.pcb.co.il/proj4/src/calltech.php'",
                       "back"
                    )
                 )
               )))
             )
           );

   return $body;
}


function GetLastNum()
{
  global $lastnumfile;

  if (($f = @fopen($lastnumfile, "r+"))) {
    flock($f, 2);
    $rez = fgets($f, 10) + 1;
    fseek($f, 0 ,0);
  } else {
    $f = @fopen($lastnumfile, "w");
    flock($f, 2);
    $rez = 1;
  }

  fputs($f, $rez);
  flock($f, 3);
  fclose($f);
  return $rez;
}


function Screen1($isError)
{
  put("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">");
  put("<html>");

  put("<HEAD>");
  put("<META content='text/html; charset=windows-1255' http-equiv=Content-Type>");
  put("<title>���� �����</title>");
  put("</HEAD>");


  put("<body dir='rtl' lang='he'>");

  put("<center>");

  if ($isError) {
    put("<h1><font color='red'>���� �� ����. �����, �����, ��� ������ �����.</font></h1>");
  }


  put("<h1>���� �����</h1>");


  put("<form method='post'>");
      put("<table border = '0'>");

      // first name
      put("<tr>");
        put("<td>");
          put("�� ���� <font color='red'>*</font>:");
        put("</td>");
        put("<td>");
          put("&nbsp" . putinput("fname"));
        put("</td>");
      put("</tr>");

      // last name
      put("<tr>");
        put("<td>");
          put("�� ����� <font color='red'>*</font>:");
        put("</td>");
        put("<td>");
          put("&nbsp" . putinput("lname"));
        put("</td>");
      put("</tr>");

      // phone
      put("<tr>");
        put("<td>");
          put("����� <font color='red'>*</font>:");
        put("</td>");
        put("<td>");
          put("&nbsp" . putinput("phone", "dir='ltr'"));
        put("</td>");
      put("</tr>");

      // e-mail
      put("<tr>");
        put("<td>");
          put("���� ��������:");
        put("</td>");
        put("<td>");
          put("&nbsp" . putinput("email", "dir='ltr'"));
        put("</td>");
      put("</tr>");

      // department
      put("<tr>");
        put("<td>");
          put("����� <font color='red'>*</font>:");
        put("</td>");
        put("<td>");
          put("&nbsp" .  putselect("depart", array("�����"), ""));
        put("</td>");
      put("</tr>");

      // OS
      put("<tr>");
        put("<td>");
          put("����� �����:");
        put("</td>");
        put("<td>");
          put("&nbsp" . putselect("os", array("no selected", "windows 95", "windows 98", "windows NT", "windows 2000", "windows XP"), "dir='ltr'"));
        put("</td>");
      put("</tr>");

      // message
      put("<tr>");
        put("<td valign='top'>");
          put("���� ����� <font color='red'>*</font>:");
        put("</td>");
        put("<td>");
          put("&nbsp<textarea cols=50 rows=10 name='mess'>");
          put("</textarea>");
        put("</td>");
      put("</tr>");

      put("<tr>");
        put("<td colspan='2' align='center'>");
          put("<input type='submit' name='submit' value='&nbsp;&nbsp;&nbsp;���&nbsp;&nbsp;&nbsp;'>");
        put("<td>");
      put("<tr>");

      put("<tr>");
        put("<td colspan='2'>");
           put("<font color='red'>*</font> ���� ���� ��� ������ �������");
        put("<td>");
      put("<tr>");

      put("</table>");
  put("</form>");

  put("</center>");

  put("</body>");
  put("<html>");
}



function putinput($name)
{
   global $HTTP_POST_VARS, $HTTP_COOKIE_VARS;

   $val = $HTTP_POST_VARS[$name];
   if ($val == "") {
     $val = $HTTP_COOKIE_VARS[$name];
   }

   $val = HTML_TXT($val);

   if (func_num_args() > 1) {
      $opt = func_get_arg(1);
   }

   return "<input name='$name' value=\"$val\" $opt>";
}

function putselect($name, $list)
{
   global $HTTP_POST_VARS, $HTTP_COOKIE_VARS;

   $val = $HTTP_POST_VARS[$name];
   if ($val == "") {
     $val = $HTTP_COOKIE_VARS[$name];
   }

   $val = HTML_TXT($val);


   if (func_num_args() > 2) {
      $opt = func_get_arg(2);
   }

   $rez .= "<select name='$name' $opt>";
   foreach($list as $n => $v) {
     $v = HTML_TXT($v);
     $rez .= "<option value=\"" . $v . "\" " . ($v == $val ? "selected" : "") . ">" . $v . "</option>";
   }
   $rez .= "</select>";


   return $rez;
}


function html($tag, $opt)
{
  $rez = "";
  $rez .= "<$tag $opt>\n";
  for ($ndx = 2; $ndx < func_num_args(); ++$ndx) {
        $rez .= func_get_arg($ndx) . "\n";
  }
  $rez .= "</$tag>\n";
  return $rez;
}


function HTML_TXT($s)
{
  return nl2br(htmlspecialchars(stripslashes($s)));
}

function FILE_TXT($s)
{
  $s = stripslashes($s);
  $f = 0;

  if (ereg("[\,\;]", $s)) {
    $f = 1;
  }

  $s = ereg_replace("\n", " ", $s);
  $s = ereg_replace("\r", "", $s);

  if (ereg("\"", $s)) {
    $s = ereg_replace("\"", "\"\"", $s);
    $f = 1;
  }

  if ($f == 1) {
    $s = "\"" . $s . "\"";
  }

  return $s;
}


function put()
{

  for ($ndx = 0; $ndx < func_num_args(); ++$ndx) {
        echo func_get_arg($ndx);
  }

  echo "\n";
}


?>
