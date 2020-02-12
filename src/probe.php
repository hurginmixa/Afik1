<?php

require("db.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("screen.inc.php");


class myscreen extends screen {
   function myscreen()
   {
     $this->screen();

     $this->Request_actions["sSub"]  = "Sub()";
   }

   function Scr() {
     global $Inp, $Out;

     $this->Out("<form method='post'>");
     $this->Out("<input name='Inp' value='$Inp'>");
     $this->Out("<input type='submit' name='sSub' value='Sub'><br>");
     $this->Out("=$Out=");

     $this->Out("</form>");
   }

   function Sub()
   {
     global $Inp, $Out;
     //$Out = crypt(md5($Inp));
     ShGlobals();
   }
}



$v = new myscreen();
$v->Run();

?>
