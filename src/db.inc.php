<?php


if(!isset($_DB_INC_)) {

$_DB_INC_ = 0;

include("_config.inc.php");
include("db_depn.inc.php");

function CountName($table, $Nam)
{
  $res = DBExec("SELECT count(name) FROM $table where name = '$Nam'", __LINE__);

  return $res->count();
}


function DupName($table, $Nam)
{
  return (CountName($table, $Nam) > 0) ? 1 : 0;
}



function MaxSysNum($table)
{
  $res = DBExec("SELECT max(sysnum) FROM $table", __LINE__);
  if (!$res) { Exit; }

  return $res->max();
}


function NextVal($sequence)
{
  $res = DBExec("SELECT nextval('$sequence')", __LINE__);
  if (!$res) { Exit; }

  return $res->nextval();
}


function DelAttach($SysNum)
{
}




function DelMsg($SysNum)
{
    $loc = true;
    if (func_num_args() > 1) {
        $loc = func_get_arg(1);
    }

    if ($loc) {
        DBExec("begin", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
        DBExec("LOCK TABLE fs, file IN ACCESS EXCLUSIVE MODE", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    }

    DBExec("DELETE FROM fs WHERE ftype = 'a' and up = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    DBExec("DELETE FROM msgbody WHERE sysnummsg = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    DBExec("DELETE FROM msgheader WHERE sysnummsg = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    DBExec("DELETE FROM msg WHERE sysnum = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    if ($loc) {
        DBExec("COMMIT", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    }
}




function DelFld($SysNum)
{
      $res = DBExec("SELECT sysnum FROM msg WHERE sysnumfld = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

      for($i=0; $i < $res->NumRows(); $i++) {
        DelMsg($res->sysnum());
      }

      $res = DBExec("DELETE FROM fld WHERE sysnum = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
}




function DelUsr($SysNum)
{
    global $PROGRAM_SRC;

    $res = DBExec("SELECT sysnum FROM fld WHERE sysnumusr = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
    while(!$res->eof()) {
        DelFld($res->sysnum());
        $res->next();
    }

    DBExec("DELETE FROM usr_ua WHERE sysnumusr = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    DBExec("DELETE FROM address WHERE sysnumusr = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    DBExec("DELETE FROM grpaddress WHERE sysnumusr = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    DBExec("DELETE FROM acc WHERE acc.sysnumfs = fs.sysnum and fs.owner = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    DBExec("DELETE FROM fs WHERE owner = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    // DBExec("DELETE FROM clip WHERE clip.sysnumfs = fs.sysnum and fs.owner = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

    DBExec("DELETE FROM usr WHERE sysnum = $SysNum", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");
}




function DelDomain($SysNum)
{
    $res = DBExec("SELECT sysnum FROM usr WHERE sysnumdomain = $SysNum", __LINE__);
    if (!$res) { Exit; }

    while(!$res->Eof()) {
        DelUsr($res->sysnum());
        $res->Next();
    }

    $res = DBExec("DELETE FROM domain WHERE sysnum = $SysNum", __LINE__);
    if (!$res) { Exit; }
}



function DomainUsrResult($UID, &$r_domain, &$r_usr)
{
    global $INET_ROOT;

    if (!eregi("^[0-9]+\$", $UID)) {
        $r_usr    = DBFind("usr",    "sysnum = -1", "", __LINE__);
        $r_domain = DBFind("domain", "sysnum = -1", "", __LINE__);
        return;
    }

    $r_usr = DBExec("SELECT * FROM usr WHERE sysnum = '$UID'", __LINE__);
    if (!$r_usr) { Exit; }

    if ($r_usr->NumRows() == 0) {
        echo "<br><b>Error : No find user</b><i>$s</i><br>";
        echo "<a href='$INET_ROOT'>Login</a>";
        Exit;
    }


    $r_domain = DBExec("SELECT * FROM domain WHERE sysnum = " . $r_usr->sysnumdomain());
    if (!$r_domain) { Exit; }

    if ($r_domain->NumRows() == 0) {
        echo "<br><b>Error : No find user</b><i>$s</i><br>";
        echo "<a href=\"/proj\">Login</a>";
        Exit;
    }
}



function DBFind($table, $where, $what)
{
      // echo "<pre>$where</pre><br>";
    $DebugInfo = "";
    if (func_num_args() > 3) {
        $DebugInfo = func_get_arg(3);
    }

    if ($what == "") {
        $what = "*";
    }

    if ($where != "") {
        $res = DBExec( "SELECT $what FROM $table WHERE $where", $DebugInfo . "<br>DBFind " . __LINE__);
    } else {
        $res = DBExec( "SELECT $what FROM $table", $DebugInfo . "<br>DBFind " . __LINE__);
    }

    return $res;
}



function DBFindE($table, $where, $what)
{
    $DebugInfo = "";
    if (func_num_args() > 3) {
        $DebugInfo = func_get_arg(3);
    }

    $res = DBFind($table, $where, $what, $DebugInfo . "<br>DBFindE " . __LINE__);

    if ($res->NumRows() == 0) {
        user_error($DebugInfo . "<br>DBFindE " . __LINE__ . " not found", E_USER_ERROR);
    }

    return $res;
}


} // $_DB_INC_

?>
