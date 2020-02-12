<?php

include "_config.inc.php";
// require "tools.inc.php";
// require "stable1.inc.php";
require "utils.inc.php";
require "file.inc.php";
require "db.inc.php";
ConnectToDB();

require "view.inc.php";

class my extends view
{
        function display()
        {
                global $INET_SRC, $INET_IMG;
                global $e1;
                $this->out("<form method='post' hidden>");
                $this->out("<a href='$INET_SRC/2.php?UID=$this->UID'>2.php</a><br>");
                $this->out("<input name='e1' value='$e1'>");
                $this->out("<input name='s1' value='s1' type='submit'>");
                $this->out("<input name='h[1][2]' value='mixa' type='hidden'>");
                $this->out("</form>");
                $this->out(time()."<br>");
                $this->out(ShGlobals());
        }

        function BodyScripts()
        {
                return "onload=\"javascript:onLoad();\"";
        }

        function Script()
        {
          echo "<script language='javascript'>";
          echo "function onLoad() { window.alert(\"mixa\"); }";
          echo "</script>";
        }
}

// debugger("localhost");
// $CCC = time();
// ShGlobals();
$r = new my();
$r->run();



/*
$r_file = DBFind("file", "", "");
while (!$r_file->eof()) {
  if (!file_exists($PROGRAM_FILES . "/" . $r_file->sysnum())) {
    echo $PROGRAM_FILES . "/" . $r_file->sysnum(), "<br>\n";
    // DBExec("delete from file where sysnum = " . $r_file->sysnum());

    $r_fs = DBFind("fs", "sysnumfile=" . $r_file->sysnum(), "");
    while (!$r_fs->eof()) {
      echo "=", $r_fs->sysnum(), "=", $r_fs->ftype(), "=<br>\n";
      if ($r_fs->ftype() == "a") {
        $r_com = DBFind("msg, fld, usr, domain", "domain.sysnum = usr.sysnumdomain and usr.sysnum = fld.sysnumusr and fld.sysnum = msg.sysnumfld and msg.sysnum=" . $r_fs->up(), "usr.name as uname, domain.name as dname");
        echo "===", $r_com->uname(), "=", $r_com->dname(), "=<br>\n";
      }
      $r_fs->Next();
    }
  }
  $r_file->Next();
}
*/


/*
$r_fs=DBExec("select * from fs where sysnumfile not in (select sysnum from file) and sysnumfile <> 0");
while (!$r_fs->eof()) {
  // DBExec("delete from fs where sysnum = " . $r_fs->sysnum());

  echo "=", $r_fs->sysnum(), "=", $r_fs->ftype(), "=", $r_fs->name(), "=", $r_fs->sysnumfile(), "=<br>\n";

  if ($r_fs->ftype() == "a") {
    $r_com = DBFind("msg, fld, usr, domain", "domain.sysnum = usr.sysnumdomain and usr.sysnum = fld.sysnumusr and fld.sysnum = msg.sysnumfld and msg.sysnum=" . $r_fs->up(), "usr.name as uname, domain.name as dname, usr.sysnum as usysnum");
  } else {
    $r_com = DBFind("usr, domain", "domain.sysnum = usr.sysnumdomain and usr.sysnum = fs.owner and fs.sysnum=" . $r_fs->sysnum(), "usr.name as uname, domain.name as dname, fs.up as usysnum");
  }

  echo "==================", $r_com->usysnum(), "=", $r_com->uname(), "=", $r_com->dname(), "=<br>\n";
  $r_fs->Next();
}
*/

?>
