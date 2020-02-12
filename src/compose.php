<?php

require("cont.inc.php");

ConnectToDB();

$v = new CComposeScreen();
$v->Run();

UnconnectFromDB();

?>
