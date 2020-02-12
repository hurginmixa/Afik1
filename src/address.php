<?php

require("db.inc.php");
require("cont.inc.php");

ConnectToDB();

$AddressScreen = new CAddressScreen();
$AddressScreen->Run();

UnconnectFromDB();

?>
