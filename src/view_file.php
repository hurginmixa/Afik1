<?php

include("_config.inc.php");
require("db.inc.php");
require("view_file.inc.php");


// ShGlobals();

ConnectToDB();

$ViewFile = new CViewFile();
$ViewFile->run();

UnconnectFromDB();
exit;

?>
