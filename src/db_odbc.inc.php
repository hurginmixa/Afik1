<?php


if(!isset($_DB_ODBC_INC_PHP_)) {

$_DB_ODBC_INC_PHP_ = 0;



include("_config.inc.php");



function ConnectToDB()
{
      global $DBConn, $DBASE, $ODBC_USER;


      if ($DBConn != "") {
        return;
      }
      $DBConn = odbc_connect($DBASE, $ODBC_USER, "");
      if (!$DBConn) { Exit; }
}


function DBInsertErr($res, $f, $l)
{
      global $DBConn;

      if ($res) { return; }

      $mes = odbc_ErrorMessage($DBConn);

      if (strpos($mes, "unique") == 0) {
        echo " <br><b>Warning</b>: PostgresSQL query failed: $mes in <b>$f</b> on line <b>$l</b><br>";
        Exit;
      }
}


function protect_insert($SQL, $i)
{
  $r = "";

  $r .= "do {\n";
  $r .= "  \$result_protect_insert = @odbc_Exec(\$DBConn, \"$SQL\");\n";

  $r .= "  if (!\$result_protect_insert) {\n";
  $r .= "    \$message_protect_insert = odbc_ErrorMessage(\$DBConn);\n";

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
      global $SQL_LogFileName;

      $f = @fopen($SQL_LogFileName, "a");
      if ($f) {
         fputs($f, strftime("%Y-%m-%d %H:%M:%S") . "> '$SQL'\n");
         fclose($f);
      }

      //echo "$DBConn<br>\n";
      //echo "$SQL<br>\n";
      $res = odbc_EXEC( $DBConn, $SQL );
      if (!$res) {
          echo "<b>SQL Text :</b> <i><u>$SQL</u></i><br>";
          Exit;
      }

      if (preg_match ("/^[ ]*select/i", $SQL)) {
        return ResultToObj($res);
      } else {
        return (new DBCursor(0));
      }
}





class DBCursor {
      var $r_resultId, $r_lineNumer, $r_numRows;

      function DBCursor($ARes)
      {
          $this->r_resultId = $ARes;

          $this->r_numRows = odbc_num_rows($this->r_resultId);
          if ($this->r_numRows == -1) {
             $this->r_numRows = 0;
             while(odbc_fetch_row($this->r_resultId, $this->r_numRows + 1)) {
               $this->r_numRows += 1;
             }
          }

          $this->Set(0);
      }

      function NumRows()
      {
        return $this->r_numRows;
        return $this->r_resultId ? odbc_Num_Rows($this->r_resultId) : 0;
      }


      function NumFields()
      {
        return $this->r_resultId ? odbc_Num_Fields($this->r_resultId) : 0;
      }


      function FieldName($num)
      {
        return $this->r_resultId ? odbc_Field_Name($this->r_resultId, $num) : "";
      }


      function Eof()
      {
        return ($this->r_lineNumer >= $this->NumRows() ? 1 : 0);
      }


      function Field($n)
      {
        if ($this->Eof()) {
          return "[UNV]";
        }

        return @odbc_Result($this->r_resultId, $n);
      }


      function Row() {
        return $this->r_lineNumer;
      }


      function Next()
      {
        $this->Set($this->r_lineNumer + 1);
      }


      function Set($i)
      {
        $i = (int)$i;

        if($i < $this->NumRows()) ;
        {
          if(odbc_fetch_row($this->r_resultId, $i + 1)) {
            $this->r_lineNumer = $i;
          }
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
        if($this->r_resultId) {
          odbc_freeresult($this->r_resultId);
        }

        $this->r_resultId = 0;
        $this->Set(0);
      }
}


function ResultToObj($res)
{
      global $DBConn;

      $ClassDescription = "SetedDBCursor";
      for ($i = 1; $i <= odbc_Num_Fields($res); $i++) {
        $ClassDescription .= "__" . odbc_Field_Name($res, $i);
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


      for ($i = 1; $i <= odbc_Num_Fields($res); $i++) {
        $f = odbc_Field_Name($res, $i);

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

