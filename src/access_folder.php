<?php

require("access_folder.inc.php");
require("db.inc.php");
ConnectToDB();

$s = new CAccessFolderScreen();
$s->run();

UnconnectFromDB();

?>
