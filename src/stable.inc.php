<?php

if(!isset($_STABLE_INC_)) {

$_STABLE_INC_=0;

class stable {
    var $table_value = 6,
        $table_SetRow,
        $table_SetCol,
        $table_Subst  = "", // "[Table]",
        $table_Substarray=array();


    function table() // old constructor
    {
      $Val = "";
      if (func_num_args() > 0) {
         $Val = func_get_arg(0);
      }

      $this->stable($Val);
    }

    function stable() // constructor
    {
      $Val = "";
      if (func_num_args() > 0) {
         $Val = func_get_arg(0);
      }

      $this->table_SetRow = $this->Shift(0);
      $this->table_SetCol = $this->Shift(0);
      #$this->TmpName = tempnam("/tmp", "php");
      $this->fp = array();
      $this->tableopt($Val);
    }


    function OutLn()
    {
        $Val = array();
        for ($i = 0; $i < func_num_args(); $i++) {
            $Val[] = func_get_arg($i);
        }

        call_user_method_array("out", $this, $Val);

        $this->Out("\n");
    }


    function out()
    {
        $Val = "";
        for ($i = 0; $i < func_num_args(); $i++) {
            $Val .= func_get_arg($i);
        }

        if ($Val == "") {
            return;
        }

        $this->CValue = $this->CValue + 1;
        $this->fputs("[$this->table_SetRow][$this->table_SetCol][".$this->Shift($this->CValue)."][" . $this->Control(Value) . "]", $Val);
    }


    function opt($Opt)
    {
        if ($Opt == "") {
            return;
        }

        $this->CValue = $this->CValue + 1;
        $this->fputs("[$this->table_SetRow][$this->table_SetCol][".$this->Shift($this->CValue)."][" . $this->Control(COpt) . "]", $Opt);
    }



    function outs($Opt, $Val)
    {
        $this->opt($Opt);
        $this->out($Val);
    }



    function tds($Row, $Col, $Opt, $Val)
    {
        if ("*" == (string)$Row) {
            $Row = $this->table_SetRow;
        } else {
            $Row = $this->Shift($Row);
        }

        if ("*" == (string)$Col) {
            $Col = $this->table_SetCol;
        } else {
            $Col = $this->Shift($Col);
        }

        $this->table_SetRow = $Row;
        $this->table_SetCol = $Col;
        if ($Opt != "") {
            $this->opt($Opt);
        }
        if ($Val != "") {
            $this->out($Val);
        }
    }



    function trs($Row, $Opt)
    {
        if ($Row == "*") {
            $Row = $this->table_SetRow;
        }

        $this->table_SetRow = $this->Shift((int)$Row);

        if ($Opt == "") {
            return;
        }
        $this->fputs("[$this->table_SetRow][" . $this->Control(ROpt) . "]", $Opt);
    }



    function subtable()
    {
        $Opt = "";
        if (func_num_args() > 0) {
            $Opt = func_get_arg(0);
        }

        $c = Count($this->table_Substarray) + 1;
        $this->table_Substarray[$c][Subst]  = $this->table_Subst;
        $this->table_Substarray[$c][SetRow] = $this->table_SetRow;
        $this->table_Substarray[$c][SetCol] = $this->table_SetCol;

        $this->CValue = $this->CValue + 1;
        $this->table_Subst .= "[$this->table_SetRow][$this->table_SetCol][".$this->Shift($this->CValue)."][" . $this->Control(Table) . "]";
        $this->table_SetRow=$this->Shift(0);
        $this->table_SetCol=$this->Shift(0);

        $this->tableopt($Opt);
    }



    function subtabledone()
    {
      $c = Count($this->table_Substarray);
      if ($c == 0) {
        return;
      }

      $this->table_Subst  = $this->table_Substarray[$c][Subst];
      $this->table_SetRow = $this->table_Substarray[$c][SetRow];
      $this->table_SetCol = $this->table_Substarray[$c][SetCol];

      unset($this->table_Substarray[$c]);
    }



    function TRNext()
    {
      $Opt = "";
      if (func_num_args() > 0) {
         $Opt = func_get_arg(0);
      }

      $k = substr(strrchr($this->table_SetRow, "."), 1);
      $this->trs($k + 1, $Opt);
      $this->table_SetCol=$this->Shift(0);
    }



    function TDNext()
    {
        $Opt = "";
        if (func_num_args() > 0) {
            $Opt = func_get_arg(0);
        }

        $Val = "";
        if (func_num_args() > 1) {
            $Val = func_get_arg(1);
        }

        $k = substr(strrchr($this->table_SetCol, "."), 1);
        $this->table_SetCol=$this->Shift($k + 1);

        if ($Opt != "") {
            $this->opt($Opt);
        }
        if ($Val != "") {
            $this->out($Val);
        }
    }



    function tableopt()
    {
      $Opt = "";
      if (func_num_args() > 0) {
         $Opt = func_get_arg(0);
      }

      if ($Opt == "") {
        return;
      }

      $this->fputs("[" . $this->Control(TOpt) . "]", $Opt);
    }


    function fputs($n, $v)
    {
      //$this->fp[$this->table_Subst . $n] .= $v;
      $this->fp[] = $this->table_Subst . $n . "=" . $v;
    }


    function Shift($v)
    {
      $r = (int)$v;
      while (strlen($r) < $this->table_value) {
        $r = "." . $r;
      }
      return $r;
    }

    function Control($v)
    {
      $r = $v;
      while (strlen($r) < $this->table_value) {
        $r = " " . $r;
      }
      return $r;
    }


    function Shift1($v)
    {
      $v = (int)$v;
      $i = 10000;
      $r = "";
      while ($i > 1 && $i > $v) {
        $r .= ".";
        $i = $i / 10;
      }
      $r .= $v;
      return $r;
    }


    function Show()
    {
      if (!(is_array($this->fp) && count($this->fp))) {
        return "";
      }

      sort($this->fp);
      reset($this->fp);

      $this->NextFs();
      $this->ShowTable($r, "", "");

      return $r;
      return "<pre>".htmlspecialchars($r)."</pre>$r";
      #return "<pre>\n".htmlspecialchars($r)."\n</pre>\n$r";
    }


    function ShowTable(&$r, $lev, $rol)
    {
        global $INET_IMG;
        $ropt = "";
        $rval = "";
        $par[] = array();

        while (($this->fs != "") && (substr($this->fs, 0, strlen($lev)) == $lev)) {
            if (substr($this->fs, strlen($lev), ($this->table_value + 2)) == "[" . $this->Control(TOpt) . "]") {
                $ropt .= " " . substr($this->fs, strlen($lev) + ($this->table_value + 3));
                $this->NextFs();

                if (eregi("(.*[ ]?)GRBORDER([ ]?.*)", $ropt, $arropt)) {
                    $ropt = $arropt[1] . " " . $arropt[2];
                    $par[GRBORDER] = 1;
                }
            } else {
                if ($par[GRBORDER] == 1 && $rval != "") {
                    $rval .= "  $rol<TR heght=1>\n    $rol<TD><img src='$INET_IMG/filler1x1.gif'></TD>\n  $rol</TR>\n";
                }

                $this->ShowRow($rval, $lev . substr($this->fs, strlen($lev), ($this->table_value + 2)), "  " . $rol, $par);
            }
        }

        if ($rol != "") {
            $r .= "<TABLE$ropt>\n"; #$rol<TABLE$ropt> \n
            $r .= "$rval";
            $r .= "</TABLE>"; #$rol</TABLE>\n
        } else {
            $r .= "$rval";
        }
    }


    function ShowRow(&$r, $lev, $rol, $partable)
    {
      global $INET_IMG;

      $ropt = "";
      $rval = "";
      $par = $partable;

      while (($this->fs != "") && (substr($this->fs, 0, strlen($lev)) == $lev)) {
        if (substr($this->fs, strlen($lev), ($this->table_value + 2)) == "[" . $this->Control(ROpt) . "]") {
          $ropt .= " " . substr($this->fs, strlen($lev) + ($this->table_value + 3));
          $this->NextFs();
        } else {
          if ($par[GRBORDER] == 1 && $rval != "") {
            $rval .= "  $rol<TD width=1><img src='$INET_IMG/filler1x1.gif'></TD>\n";
          }

          $this->ShowCell($rval, $lev . substr($this->fs, strlen($lev), ($this->table_value + 2)), "  " . $rol);
        }
      }

      if ($rol != "  ") {
        $r .= "$rol<TR$ropt>\n"; #$rol<TR$ropt> \n
        $r .= "$rval";
        $r .= "$rol</TR>\n"; #$rol</TR>\n
      } else {
        $r .= "$rval";
      }
    }


    function ShowCell(&$r, $lev, $rol)
    {
      $ropt = "";
      $rval = "";

      while (($this->fs != "") && (substr($this->fs, 0, strlen($lev)) == $lev)) {
        $control = substr($this->fs, strlen($lev) + ($this->table_value + 2), ($this->table_value + 2));
        if ($control == "[" . $this->Control(Table) . "]") {
          $this->ShowTable($rval, $lev . substr($this->fs, strlen($lev), ($this->table_value + 2) + ($this->table_value + 2)), "  " . $rol);
        } else if ($control == "[" . $this->Control(COpt) . "]") {
                 $ropt .= " " . substr($this->fs, strlen($lev) + ($this->table_value + 3) + ($this->table_value + 2));
                 $this->NextFs();
               } else if ($control == "[" . $this->Control(Value) . "]") {
                        #$rval .= "  " . $rol . substr($this->fs, strlen($lev) + ($this->table_value + 3) + ($this->table_value + 2)) . "\n";
                        $rval .= substr($this->fs, strlen($lev) + ($this->table_value + 3) + ($this->table_value + 2));
                        $this->NextFs();
                      }
      }

      if ($rol != "    ") {
        $r .= "$rol<TD$ropt>"; #$rol<TD $ropt>\n
        $r .= "$rval";
        $r .= "</TD>\n";  #$rol</TD> \n
      } else {
        $r .= "$rval";
      }
    }

    function NextFs()
    {
      if (list($n, $v) = each($this->fp)) {
        // $this->fs = "$n=$v";
        $this->fs = "$v";
      } else {
        $this->fs = "";
      }
    }
} // class stable

}
?>
