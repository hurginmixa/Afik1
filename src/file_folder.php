<?php

	
include("_config.inc.php");
require("file_folder.inc.php");

ConnectToDB();

$FileFolderScreen = new CFileFolderScreen();
$FileFolderScreen->run();

UnconnectFromDB();
exit;


?>
