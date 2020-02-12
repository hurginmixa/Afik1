<?php

    require("_config.inc.php");
    require("utils.inc.php");
    require("file.inc.php");
    require("db.inc.php");
    session_start();
    ConnectToDB();

    WebLog("COOKIE\n" . sharr($_COOKIE));

    if (!preg_match("/^[a-z0-9._-]+$/i", $HTTP_HOST)) {
        echo "Host '<b>" . htmlspecialchars($HTTP_HOST) . "</b>' invalid.<br>Hosts server $LOCAL_SERVER";
        exit;
    }

    $r_dm = DBFind("domain", "name = '$HTTP_HOST'", "");
    if ($r_dm->NumRows() != 1) {
        echo "Host 'HOST SERVER <b>$HTTP_HOST</b> NOT AVAILABLE (OR) NOT PRESENT<br>Hosts server $LOCAL_SERVER";
        exit;
    }

    $Mes = "";

    if ($REQUEST_METHOD == "GET" && is_array($RememberUser) && count($RememberUser) == 1) {
        reset($RememberUser);
        list($user, $Param) = each($RememberUser);

        $face = $Param[face];
        $Hash = $Param[hash];

        if ($Hash == AuthorizeKey($user)) {
            $res_usr = DBExec("SELECT * FROM usr WHERE sysnum = '$user'");
            if ($res_usr->NumRows() == 1) {
                LoginUser($user, 1, $face);
            }
        } else {
            $Mes = "Invalid authorization saved user";
        }
    }

    if ($sSign != "" && $REQUEST_METHOD == "POST") {
        if ($r_dm->signup() != "0") {
            header("Location: $INET_SRC/signusr.php?FACE=$FACE");
            //echo shglobals();
            exit;
        }
        $Mes = "Sign up for new user denied";
    }

    if ($REQUEST_METHOD == "POST") {
        $Username = preg_replace("/[^a-z\-\_0-9]/i", "", $HTTP_POST_VARS["Username"]);
        $Password = preg_replace("/[^a-z\-\_0-9]/i", "", $HTTP_POST_VARS["Password"]);
        //$FACE     = $HTTP_POST_VARS["FACE"];

        if ($DM == 0) {
            $res_fs = DBExec("SELECT * FROM usr WHERE name = '$Username' and password = '$Password'");
        } else {
            $res_fs = DBExec("SELECT * FROM usr WHERE name = '$Username' and password = '$Password' and sysnumdomain = $DM");
        }

        if ($res_fs->NumRows() == 1) {
            $UID = $res_fs->sysnum();
            if ($LINKS[$res_fs->country()] != "") {
                //$HTTP_HOST = $LINKS[ $res_fs->country() ];
            }

            $res_ua = DBExec("SELECT * FROM usr_ua WHERE sysnumusr = $UID and name = 'edenaid'");
            if ($res_ua->NumRows() != 0 && $res_ua->value() != "") {
                $Mes = 'Enter for user denied';
            } else {
                if ($res_fs->lev() > 0 && !eregi("^https:$", $PROTOCOL)) {
                    $Mes = 'Administrator should use secure mode (https:)';
                } else {
                    LoginUser($UID, $RememberMe, $FACE);
                }
            }
        } else if ($res_fs->NumRows() == 0) {
            $Mes = 'User not found or invalid password';
        } else if ($res_fs->NumRows() > 1) {
            $Mes = 'Please select Youre domain';
        }
    }
    // setcookie("CUID"); // clear cookie


function LoginUser($UID, $RememberMe, $FACE)
{
    global $HTTP_HOST;

    DBExec("update usr set lastenter = 'now' where sysnum = $UID");

    header("Cache-Control: no-cache, no-store");
    $time = time();
    setcookie("CUID[$UID][time]", $time, 0, "/", "$HTTP_HOST");
    setcookie("CUID[$UID][code]", TimedependAuthorizeHash($UID, $time), 0, "/", "$HTTP_HOST");

    if ($RememberMe) {
        setcookie("RememberUser[$UID][face]", $FACE,               $time + (3600 * 24 * 7), "/", "$HTTP_HOST");
        setcookie("RememberUser[$UID][hash]", AuthorizeKey($UID),  $time + (3600 * 24 * 7), "/", "$HTTP_HOST");
    }

    header("Location: $INET_SRC/welcome.php?UID=$UID&FACE=$FACE");

    Exit;
}


?>

<html>

<head>
<title>Afik1 System. Login Screen</title>
<?php // <META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=UTF-8">
?>
<?php
            echo "<script language=\"JavaScript\" src=\"$INET_SRC/view.js\">\n";
            echo "</script>\n";
?>


            <script language="JavaScript">
                function hSubmitForm(event)
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
  <center>
  <table border = "0" cellspacing = "0" cellpadding = "0">
     <tr>
       <td width=380 height=470 valign='top'><img src="<?php echo "$INET_IMG/" ?>afik1_fon1.gif"></td>
       <td width=350 height=470 valign="top" style="background-image: url(<?php echo "$INET_IMG/" ?>afik1_fon21.gif); background-repeat: no-repeat">
           <span style="font-family: Arial; font-size: 17px; color: #fefefe">Welcome to: <b><u><?php echo $HTTP_HOST; ?></u></b></span><br><br>
           <table border = 0 cellspacing=0 cellpadding=5 style="border: solid 2 #1785F3" width='100%'>
             <tr>
               <td><?php
                     if ($Mes != "") {
                       echo "<font color=\"red\" size=\"+2\"><b>$Mes</b></font><br>";
                     }
                   ?><table border = 0 cellspacing=0 cellpadding=3 width="100%">
                    <tr>
                      <td>&nbsp;<span style="font-family: Arial; font-size: 12px; color: #fefefe">User Name</span>&nbsp;&nbsp;</td>
                      <td nowrap><input name="Username" type="text"     size = 20 value="<?php echo $Username; ?>" style = "font-size: 15px; color: #FEFEFE; font-family: Ariel; background-color: #0054A8"><input type="hidden" name="DM" value="<?php echo $r_dm->sysnum(); ?>"></td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td>&nbsp;<span style="font-family: Arial; font-size: 12px; color: #fefefe">Password</span>&nbsp;&nbsp;</td>
                      <td nowrap><input name="Password" type="password" size = 20 value=""                         style = "font-size: 15px; color: #FEFEFE; font-family: Ariel; background-color: #0054A8" onkeydown='javascript:hSubmitForm(event);'>&nbsp;&nbsp;</td>
                    </tr>
                    <tr>
                      <td nowrap colspan='2'><input name="RememberMe" type="checkbox">&nbsp;<span style="font-family: Arial; font-size: 12px; color: #fefefe">Remember me on this computer</span></td>
                    </tr>
                    <tr>
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
           </table><!--

           --><img src='<?php echo "$INET_IMG"; ?>/filler3x1.gif'><!--

           --><table border = 0 cellspacing = 0 cellpadding = 5 style="border: solid 2 #1785F3" width='100%'>
             <tr>
               <td>
                <span style="font-family: Arial; font-size: 15px; color: #fefefe">Current mode:</span>&nbsp;
                <?php if(!eregi("^https:$", $PROTOCOL)) { ?>
                    <span style="font-family: Arial; font-size: 15px; color: #CCCCCC"><b>Standard</b></span>&nbsp;&nbsp;&nbsp;
                    <a href='<?php echo "https://$HTTP_HOST"; ?>'><span style="font-family: Arial; font-size: 15px; color: #fefefe; text-decoration: none">To secure mode</span></a>
                <?php } else { ?>
                    <span style="font-family: Arial; font-size: 15px; color: #CCCCCC"><b>Secure</b></span>&nbsp;&nbsp;&nbsp;
                    <a href='<?php echo "http://$HTTP_HOST"; ?>'><span style="font-family: Arial; font-size: 15px; color: #fefefe; text-decoration: none">To standard mode</span></a>
                <?php } ?>
               </td>
             </tr>
             <tr>
               <td>
                 <img src="<?php echo "$INET_IMG/" ?>flag.gif" align="absmiddle">
                 <SELECT name="FACE" style = "font-size: 12px; color: #FEFEFE; font-family: Arial; background-color: #0054A8; font-variant: small-caps;" onkeydown='javascript:hSubmitForm(event);'>
                   <option value="en">-= Select Language =-</option>
                   <option value="en">English</option>
                   <option value="he">Hebrew</option>
                   <option value="ru">Russian</option>
                   <option value="pt">Portuguese</option>
                   <option value="nl">Dutch</option>
                 </SELECT>
                 <?php
                 //echo shglobals();
                 ?>
               </td>
             </tr>
           </table><!--

           --><img src='<?php echo "$INET_IMG"; ?>/filler3x1.gif'><!--

           --><?php if($r_dm->signup() != "0") { ?><!--
               --><table border = 0 cellspacing=0 cellpadding=5 style="border: solid 2 #1785F3"  width='100%'>
                 <tr>
                   <td>
                     <table border = 0 cellspacing=0 cellpadding=0>
                       <tr>
                         <td>
                           <span style="font-family: Arial; font-size: 12px; color: #fefefe">Register new user</span>&nbsp;&nbsp;&nbsp;
                         </td>
                         <td>
                           <?php echo makeButton("type=1& form=LoginForm& name=sSign& img=$INET_IMG/signup-passive.gif?FACE=$FACE&     imgact=$INET_IMG/signup.gif?FACE=$FACE"); ?>
                         </td>
                       </tr>
                     </table>
                   </td>
                 </tr>
               </table>
           <?php } else { ?>
               <input type='hidden' name=sSign>
           <?php } ?>
       </td>
     </tr>
  </table>
  </center>
</form>

<?php echo GetMakeButtonBlankList(); ?>

<?php //echo $PROTOCOL, "<hr>";
?>
<?php //echo shglobals();
?>

</body>

</html>
