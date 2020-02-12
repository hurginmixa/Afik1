<?php

require("access_folder.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("screen.inc.php");
require("db.inc.php");

class CRemoteAccessScreen extends CAccessFolderScreen
{

    function ToolsBar() // closing function ToolsBar
    {
    }

    function Referens() // closing function Referens
    {
    }

    function UserName()
    {
        $this->out($this->USRNAME);
    }
}

ConnectToDB();

$v = new CRemoteAccessScreen();
$v->run();

UnconnectFromDB();

?>
