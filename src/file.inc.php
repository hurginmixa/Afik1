<?php

if(!isset($_FILE_INC_)) {

$_FILE_INC_=0;



require("_config.inc.php");
require("tools.inc.php");
require("db.inc.php");

function DeleteDirectory($FS)
{
    $r_fs = DBFind("fs", "up = $FS", "");

    while(!$r_fs->eof()) {
        if ($r_fs->sysnumfile() == 0) {
            DeleteDirectory($r_fs->sysnum());
        }
        $r_fs->Next();
    }

    DBExec("begin", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    DBExec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    DBExec("delete from fs where up = $FS and fs.ftype = 'f'", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    DBExec("COMMIT", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
}


function OpenRemoteFile($hostname, $UID, &$errno, &$errstr)
{
   $a = parse_url($hostname);
   $host = $a[host];
   $path = $a[path] . "?" . $a[query];


   $fp = fsockopen ($host, 80, &$errno, &$errstr);
   if ($fp) {
       fputs ($fp, "GET $path HTTP/1.0\r\n");
       fputs ($fp, "Cookie: CUID[$UID]=$UID\r\n\r\n");

       while (!feof($fp) && (fgets($fp, 128) != "\r\n")) {
       }
   }

   return $fp;
}


} // $_FILE_INC;

?>
