<?php
 include "_config.inc.php";
require "utils.inc.php";
require "file.inc.php";
require "db.inc.php";
ConnectToDB();

require "view.inc.php";
require "screen.inc.php";

class CAdminTools extends screen {
 var $WidthTools = 15;

 function CAdminTools()
 {


    # global $PROGRAM_ROOT;
          global  $PROGRAM_IMG, $PROGRAM_LANG ;

     screen::screen(); // inherited constructor

     if ($this->USR->lev() < 1) {
       $this->out("<h1>Access denited !</h1>");
       exit;
     }

     $this->SetTempl("admin_opt");

     $this->PgTitle = "<b>$TEMPL[title]</b>";
 }
  function Scr()
  {
      if ($this->USR->lev() == 2) {
        $this->Scr_SUser();
      } else {
        $this->Scr_DAdmin();
      }
  }

    function Scr_SUser()
  {
     #    global $PROGRAM_ROOT;
          global  $PROGRAM_IMG, $PROGRAM_LANG, $INET_IMG;

      $this->SubTable("border=0  width='100%' nowrap cellspacing = '0' cellpadding = '0' grborder");

      $this->TRNext("class='ttp' nowrap");
      #$this->out("EN GIF");
     # $this->TDNext("class='ttp' nowrap");
      $this->out("ENGLISH");
     # $this->TDNext("class='ttp' nowrap");
     # $this->out("He GIF");
      $this->TDNext("class='ttp' nowrap");
      $this->out("HEBREW");
      $this->SubTableDone();
      $this->out("<img src='$INET_IMG/filler1x1.gif'>");

    $this->SubTable("border=0  width='100%' nowrap cellspacing = '0' cellpadding = '0' grborder");
  if ($handle = opendir("$PROGRAM_IMG/")) {

    $file = readdir($handle);
    while ($file !== false) {
      if($file !=".." && $file != "."){
        $Gif[$file][en] = $file;
      }
        $file = readdir($handle);
    }
    closedir($handle);
}


if ($handle2 = opendir("$PROGRAM_LANG/he/img/")) {

    $file2 = readdir($handle2);
    while ($file2 !== false) {
       if ($file2 != ".."  &&  $file != ".") {
         $Gif[$file2][he] = $file2;
       }
       $file2 = readdir($handle2);
    }


}

sort  ($Gif);
reset ($Gif);

while (list ($key, $val) = each ($Gif)) {

     $this->TRNext("");
     $this->TDNext("class='tlp' alige='center'");
   #   $this->out($this->TextShift. $val[en]. $this->TextShift);
     $this->out($this->TextShift."<a href='$INET_IMG/$val[en]?FACE=en'</a>".$val[en].$this->TextShift);
   #  $this->out("<img src='$INET_IMG/$val[en]?FACE=$FACE' border=0 align='absmiddle' title='$TEMPL[step_up_ico]'>");
     # $this->TDNext("class='tlp' alige='center' ");
     $this->TDNext("class='tlp' alige='center'");
     $this->out($this->TextShift."<a href='$INET_IMG/$val[he]?FACE=he'</a>".$val[he].$this->TextShift);

 #

   #  $this->out("<img src='$PROGRAM_LANG/he/img/$val[he]?FACE=$FACE' border=0 align='absmiddle' title='$TEMPL[step_up_ico]'>");
 #   $this->out($this->TextShift. $val[he]. $this->TextShift);


 }
      $this->SubTableDone();


} // end of class CAdminTools

  }
$AdminTools = new CAdminTools();
$AdminTools->run();


?>
