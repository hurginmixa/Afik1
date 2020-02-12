<?php


if(!isset($_DB_PG_INC_PHP_)) {

$_DB_PG_INC_PHP_ = 0;



include("_config.inc.php");



function ConnectToDB()
{
    global $DBConn, $DBASE;
    global $DBResultsStack;


    if ($DBConn != "") {
        return;
    }
    $DBConn = pg_connect("user=postgres dbname=$DBASE");
    if (!$DBConn) { Exit; }

    //SQLLog("Connect " . pg_get_pid ($DBConn));

    $DBResultsStack = array();
}


function UnconnectFromDB()
{
    global $DBConn;
    global $DBResultsStack;

    if ($DBConn == "") {
        return;
    }

    reset($DBResultsStack);
    while(list($n, $result) = each($DBResultsStack)) {
        @pg_freeresult ($result);
    }

    @pg_close($DBConn);
    unset($DBConn);
}


function DBInsertErr($res, $f, $l)
{
    global $DBConn;

    if ($res) { return; }

    $mes = pg_ErrorMessage($DBConn);

    if (strpos($mes, "unique") == 0) {
        echo " <br><b>Warning</b>: PostgresSQL query failed: $mes in <b>$f</b> on line <b>$l</b><br>";
        Exit;
    }
}


function protect_insert($SQL, $i)
{
    $r = "";

    $r .= "do {\n";
    $r .= "  \$result_protect_insert = @pg_Exec(\$DBConn, \"$SQL\");\n";

    $r .= "  if (!\$result_protect_insert) {\n";
    $r .= "    \$message_protect_insert = pg_ErrorMessage(\$DBConn);\n";

    $r .= "    if (strpos(\$message_protect_insert, \"unique\") == 0) {\n";
    $r .= "      echo \" <br><b>Warning1</b>: PostgresSQL query failed: \$message_protect_insert in<br><i>$SQL</i>\";\n";
    $r .= "      Exit;\n";
    $r .= "    }\n";
    $r .= ($i != "") ? "    $i++;\n" :  "";
    $r .= "  }\n";


    $r .= "} while (!\$result_protect_insert);\n";

    return $r;
}


function DBExec($SQL)
{
    global $DBConn;
    global $DBResultsStack;
    global $SQL_LogFileName;

    $DebugInfo = "";
    if (func_num_args() > 1) {
        $DebugInfo = func_get_arg(1);
    }

    SQLLog("DebugInfo '$DebugInfo' > '", $SQL, "'");

    if (preg_match("/(^([^\'\;]*?('([^']|'')*?')[^\'\;]*?)*?;)|(^[^';]*?;)/", $SQL)) {
        echo "<b>WARRING</b> in DBExec <b>impossible to use &quot;;&quot;</b><br>";
        if ($DebugInfo) {
            echo "Debug info $DebugInfo<br>";
        }
        SQLLog("DebugInfo '$DebugInfo' WARRING ;");
        Exit;
    }

    if (substr_count($SQL, "'") % 2 != 0) {
        echo "<b>WARRING</b> in DBExec <b>impossible to use &gt;'&lt;</b><br>";
        if ($DebugInfo) {
            echo "Debug info $DebugInfo<br>";
        }
        SQLLog("DebugInfo '$DebugInfo' WARRING \"'\"");
        Exit;
    }

    $SQL = preg_replace("/;/", "", $SQL);

    // echo "$SQL<br>";
    $res = pg_EXEC( $DBConn, $SQL );
    if (!$res) {
        echo "<b>WARRING</b> in DBExec<br>";
        if ($DebugInfo) {
            echo "Debug info $DebugInfo<br>";
        }
        Exit;
    }

    $DBResultsStack[] = $res;

    if (preg_match ("/^[ ]*select/i", $SQL)) {
        return ResultToObj($res);
    } else {
        return (new DBCursor(0));
    }
}


function SQLLog()
{
    global $SQL_LogFileName, $_SERVER, $UID;

    $Mes = "";
    if (func_num_args() > 0) {
        for ($i=0; $i < func_num_args(); $i++) {
            $Mes .= func_get_arg($i);
        }
    }

    $f = @fopen($SQL_LogFileName, "a");
    if ($f) {
        fputs($f, strftime("%Y-%m-%d %H:%M:%S") . " [ $_SERVER[SCRIPT_NAME] id $UID from $_SERVER[REMOTE_ADDR] ] $Mes\n");
        fclose($f);
    }

    //debug(strftime("%Y-%m-%d %H:%M:%S") . "> '" . $this->USRNAME . "' " . $Mes);
}


class DBCursor {
    var $res, $l;

    function DBCursor($ARes)
    {
        $this->res = $ARes;
        $this->Set(0);
    }

    function NumRows()
    {
        return $this->res ? pg_NumRows($this->res) : 0;
    }


    function NumFields()
    {
        return $this->res ? pg_NumFields($this->res) : 0;
    }


    function FieldName($num)
    {
        return $this->res ? pg_FieldName($this->res, $num) : "";
    }


    function Eof()
    {
        return ($this->l >= $this->NumRows() ? 1 : 0);
    }


    function Top()
    {
        return $this->Set(0);
    }


    function Field($n)
    {
        if ($this->Eof()) {
            return  "[UNV]";
        }

        return @pg_Result($this->res, $this->l, $n);
    }


    function Row()
    {
        return $this->l;
    }


    function Next()
    {
        $this->Set($this->l + 1);
    }


    function Set($i)
    {
        $i = (int)$i;

        if($i <= $this->NumRows()) {
            $this->l = $i;
        }
    }


    function Find($f, $v)
    {
        $r = -1;

        for ($i=0; $i < $this->NumRows() && $r == -1; $i++) {
            $this->set($i);
            if ($this->field($f) == $v) {
                $r = $i;
            }
        }

        return $r;
    }

    function free()
    {
        if($this->res) {
            pg_freeresult($this->res);
        }

        $this->res = 0;
        $this->Set(0);
    }
}


function ResultToObj($res)
{
      global $DBConn;

      $ClassDescription = "SetedDBCursor";
      for ($i=0; $i < pg_NumFields($res); $i++) {
        $ClassDescription .= "__" . pg_FieldName($res, $i);
      }

      if ( class_exists($ClassDescription) ) {
        return new $ClassDescription ($res);
      }

      $t = "";
      $t .= "class $ClassDescription extends DBCursor {\n";

      $t .= "  function $ClassDescription(\$ARes)\n";
      $t .= "  {\n";
      $t .= "    \$this->DBCursor(\$ARes);\n";
      $t .= "  }\n";


      $ExistFields = array();
	  
      for ($i=0; $i < pg_NumFields($res); $i++) {
        $f = pg_FieldName($res, $i);

		if ($ExistFields[$f]) {
			continue;
		}
		$ExistFields[$f] = $f;

        $t .= "  function $f()\n";
        $t .= "  {\n";
        $t .= "    return \$this->Field(\"$f\");\n";
        $t .= "  }\n";
      }

      $t .= "}\n";

      eval($t);

      return new $ClassDescription ($res);
}





} // $_DB_PG_INC_PHP_

