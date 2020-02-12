<?php

  require("_config.inc.php");
  require("utils.inc.php");
  require("file.inc.php");
  require("db.inc.php");
  ConnectToDB();

  $Mes = "";

  $Username = $HTTP_POST_VARS["Username"];
  $Password = $HTTP_POST_VARS["Password"];
  //$FACE     = $HTTP_POST_VARS["FACE"];


  if ($sSign != "" && $REQUEST_METHOD == "POST") {
    header("Location: $INET_SRC/signusr.php?FACE=$FACE");
    echo shglobals();
    exit;
  }

  if ($Inp == 2) {
      if ($UID != 0) {
        header("Location: $INET_SRC/welcome.php?UID=$UID&FACE=$FACE");
        header("Cache-Control: no-cache, no-store");
        $time = time();
        setcookie("CUID[$UID][time]", $time, 0, "/", "$HTTP_HOST");
        setcookie("CUID[$UID][code]", TimedependAuthorizeHash($UID, $time), 0, "/", "$HTTP_HOST");
        Exit;
      }
  }

  if ($Inp == 1) {
    if ($DM == 0) {
      $res = DBExec("SELECT * FROM usr WHERE name = '$Username' and password = '$Password'");
    } else {
      $res = DBExec("SELECT * FROM usr WHERE name = '$Username' and password = '$Password' and sysnumdomain = $DM");
    }

    if ($res->NumRows() == 1) {
        $UID = (int)$res->sysnum();
        $HTTP_HOST = $LINKS[$res->country()];

        $res = DBExec("SELECT * FROM usr_ua WHERE sysnumusr = $UID and name = 'edenaid'");
        if ($res->NumRows() != 0 && $res->value() != "") {
           $Mes = 'Enter for user denied';
        } else {
            include("_config.inc.php");
            header("Location: $INET_SRC/login.php?Inp=2&UID=$UID&FACE=$FACE");
            header("Cache-Control: no-cache, no-store");
            Exit;
        }
    } else if ($res->NumRows() == 0) {
        $Mes = 'User not found or invalid password';
    } else if ($res->NumRows() > 1) {
        $Mes = 'Please select Youre domain';
    }
  }
  // setcookie("CUID"); // clear cookie
?>

<?php
  $r_dm = DBFind("domain", "name = '$HTTP_HOST'", "");

  if ($r_dm->NumRows() != 1) {
     echo "Host '$HTTP_HOST' not represident.";
     exit;
  }
?>

<html>

<head>
<title>Afik1 System. Login Screen</title>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=UTF-8">
<?php
            echo "<script language=\"JavaScript\" src=\"$INET_SRC/view.js\">\n";
            echo "</script>\n";
?>


            <script language="JavaScript">
              function hKeyPressed(event)
              {
                var Code;
                if (document.all) {
                  Code = window.event.keyCode;
                } else {
                  Code = event.which;
                }

                if (Code == 13) {
                  document.forms["LoginForm"]["sSend"].value = "sSend";
                  document.forms["LoginForm"]["sSign"].value = "";
                  //alert("q =" + document.forms["LoginForm"]["sSign"].value);
                  document.forms["LoginForm"].submit();
                  if (document.all) {
                    window.event.cancelBubble = true;
                  }
                  return false;
                }

                return true;
              }
            </script>

</head>

<BODY bgcolor="#003c78">

<form method="POST" name="LoginForm">
  <input type="hidden" name="Inp" value="1">
  <center>
  <table border =1 cellspacing=0 cellpadding=0>
     <tr>
       <td width=400 rowspan=2><img src="<?php echo "$INET_IMG/" ?>afik1_fon1.gif"></td>
       <td width=400 height=120><img src="<?php echo "$INET_IMG/" ?>afik1_fon2.gif"></td>
     </tr>
     <tr>
       <td width=400 height=480 valign="top" style="background-image: url(<?php echo "$INET_IMG/" ?>afik1_fon3.gif); background-repeat: no-repeat"><table border=1 cellspacing=0 cellpadding=0 style="border: solid 1 #004d90">
             <tr>
               <td><?php
                     if ($Mes != "") {
                       echo "<font color=\"red\" size=\"+2\"><b>$Mes</b></font><br>";
                     }
                  ?><table border=1 cellspacing=0 cellpadding=0 width="100%"><tr><td align="center">User Name</td><td>Password</td><td><font color="#fefefe">&nbsp;</font></td>
                    </tr>
                    <tr>
                      <td nowrap><input name="Username" value="" style = "font-size: 12px; color: #FEFEFE; font-family: Ariel; background-color: #0054A8" onkeydown='javascript:hKeyPressed(event);'><input type="hidden" name="DM" value="<?php echo $r_dm->sysnum(); ?>"></td>
                      <td>
                        <input name="Password" type="password"  style = "font-size: 12px; color: #FEFEFE; font-family: Ariel; background-color: #0054A8" onkeydown='javascript:hKeyPressed(event);'>&nbsp;&nbsp;
                      </td>
                      <td>
                        <?php
                        #<input type='submit' value='Login' style = "font-family: 'Arial'; font-size: 12px; color: #ffffff; background-color: #597fbf;">
                        echo makeButton("type=1& form=LoginForm& name=sSend& img=$INET_IMG/login-passive.gif?FACE=$FACE&     imgact=$INET_IMG/login.gif?FACE=$FACE");
                        ?>
                      </td>
                    </tr>
                    <tr>
                    </tr>
                    <tr>
                    </tr>
                  </table>
               </td>
             </tr>
           </table>
           <br>
           <table border = 0 cellspacing=0 cellpadding=12 style="border: solid 1 #004d90">
             <tr>
               <td>
                 <table border = 0 cellspacing=0 cellpadding=0>
                   <tr>
                     <td>
                       <font color="#fefefe">To Signup</font>&nbsp;&nbsp;&nbsp;
                     </td>
                     <td>
                       <?php echo makeButton("type=1& form=LoginForm& name=sSign& img=$INET_IMG/signup-passive.gif?FACE=$FACE&     imgact=$INET_IMG/signup.gif?FACE=$FACE"); ?>
                     </td>
                   </tr>
                 </table>
               </td>
             </tr>
           </table>
           <br>
           <table border = 0 cellspacing=0 cellpadding=0>
             <tr>
               <td>
                 <img src="<?php echo "$INET_IMG/" ?>flag.gif" align="absmiddle">
                 <SELECT name="FACE" style = "font-size: 12px; color: #FEFEFE; font-family: Arial; background-color: #0054A8; font-variant: small-caps; border: 15 dashed #003C78">
                   <option value="en">-= Select Language =-</option>
                   <option value="en">English</option>
                   <option value="he">עברית</option>
                   <option value="ru">Русский</option>
                   <option value="pt">Portuguese</option>
                   <option value="nl">Nederlands</option>
                 </SELECT>
               </td>
             </tr>
           </table>
       </td>
     </tr>
  </table>
  </center>
</form>

<?php echo GetMakeButtonBlankList(); ?>

</body>

</html>

