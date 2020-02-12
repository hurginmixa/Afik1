<?php

include "_config.inc.php";
require "utils.inc.php";
require "file.inc.php";
require "db.inc.php";
ConnectToDB();

require "view.inc.php";
require "screen.inc.php";

 class CAdminTools extends screen
{

 function Scr()
 {

      global $FACE,$LISTSTEP;


      $this->subtable("width='100%'  border='0' cellspacing='0' cellpadding = '0' class='tab' grborder ");
      $this->TDNext("class='ttp' ");
      $this->out("No." );
      $this->TDNext("class='ttp'");
      $this->out("User Name");
      $this->TDNext("class='ttp'");
      $this->out("First Name");
      $this->TDNext("class='ttp'");
      $this->out("Last Name");
      $this->TDNext("class='ttp'");
      $this->out("Disk Space");
      $this->TDNext("class='ttp'");
      $this->out("Free Space");
      $this->TDNext("class='ttp'");
      $this->out("Billing");

      $h = DBExec( "select usr.name,       ".
                   "       ua1.value as firstname,             " .
                   "       ua2.value as lastname,              " .
                   "       sum(file.fsize) as size             " .
                   "from usr                                   " .
                   "     left join usr_ua ua1                  " .
                   "         on usr.sysnum = ua1.sysnumusr and " .
                   "            ua1.name = 'firstname'         " .
                   "     left join usr_ua ua2                  " .
                   "         on usr.sysnum = ua2.sysnumusr and " .
                   "            ua2.name = 'lastname'          " .
                   "     left join fs                          " .
                   "         on usr.sysnum = fs.owner          " .
                   "     left join file                        " .
                   "         on fs.sysnumfile = file.sysnum    " .
                   "group by                                   " .
                   "     usr.name, ua1.value, ua2.value        ");




         for($i=1; !$h->eof() && $i < 12 * $LISTSTEP; $i++){
         $h->Next();
       }

       $k = 12 * $LISTSTEP + 1;
       for($i = 1; !$h->eof() && $i <= 12; $i++, $k++){
        $this->TRNext("");
        $this->TDNext("class='tlp'");
        $this->out("$k");
        $this->TDNext("class='tlp'");
        $this->out($h->name());
        $this->TDNext("class='tlp'");
        $this->out($h->firstname());
        $this->TDNext("class='tlp'");
        $this->out($h->lastname());
        $this->TDNext("class='tlp' align='right'");
        $this->out(AsSize($h->size()));
        $h->Next();


      }
      if ($LISTSTEP == 0) {
      $this->TRNext("class='toolst' align='center'");
      $this->out("<A HREF= 'admins_tools.php?UID=$this->UID&FACE=$FACE&LISTSTEP=" .($LISTSTEP + 1) . "'<b> <-- NEXT PAGE</b></A>" );
      $this->TDNext("class='toolst' align='center'");
      $this->out("<A HREF= 'admin_opt.php?UID=$this->UID&FACE=$FACE&LISTSTEP=0''<b> BACK PAGE --> </b></A>" );
      } else if ($LISTSTEP >0){
      $this->TRNext("class='toolst' align='center'");
      $this->out("<A HREF= 'admins_tools.php?UID=$this->UID&FACE=$FACE&LISTSTEP=" .($LISTSTEP + 1) . "'<b> <-- NEXT PAGE</b></A>" );
      $this->TDNext("class='toolst' align='center'");
      $this->out("<A HREF= 'admins_tools.php?UID=$this->UID&FACE=$FACE&LISTSTEP=" .($LISTSTEP - 1) . "'<b> BACK PAGE --> </b><A/>" );
      } else if ($LISTSTEP<0){
      $this->out("<A HREF= 'admin_opt.php?UID=$this->UID&FACE=$FACE&LISTSTEP=0'</A>" );
      }

      $this->subtabledone();
    }
  }


$AdminTools = new CAdminTools();
$AdminTools->run();



?>

