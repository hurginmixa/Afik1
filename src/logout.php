<?php

require("_config.inc.php");
require("view.inc.php");
require("file.inc.php");

class CLogoutView extends view
{
    function run() // overlapping inherited function
    {
        global $INET_SRC, $INET_ROOT, $HTTP_HOST, $RememberUser, $_COOKIE;

        $this->Log("user $this->USRNAME logout");

        DBExec("VACUUM");

        session_destroy();

        // clear cookie
        @setcookie("CUID[$UID]",                     "",          time() - 3600, "/", "$HTTP_HOST");
        @setcookie("CUID[$this->UID][time]",         "",          time() - 3600, "/", "$HTTP_HOST");
        @setcookie("CUID[$this->UID][code]",         "",          time() - 3600, "/", "$HTTP_HOST");
        @setcookie("PHPSESSID",                      "",          time() - 3600, "/", "$HTTP_HOST");

        @setcookie("RememberUser[$this->UID][face]", "",          time() - 3600, "/", "$HTTP_HOST");
        @setcookie("RememberUser[$this->UID][hash]", "",          time() - 3600, "/", "$HTTP_HOST");

        header("Location: $INET_ROOT");
    }
}

ConnectToDB();

$LogoutView = new CLogoutView();
$LogoutView->run();

UnconnectFromDB();

exit;
?>
