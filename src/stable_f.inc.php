<?php

class stable {
   var $table_value,
       $table_SetRow,
       $table_SetCol,
       $table_Subst  = "", // "[Table]",
       $table_Substarray=array();


   function table($Val)
   {
     $this->stable($Val);
   }

   function stable($Val)
   {
     $this->table_SetRow = $this->Shift(0);
     $this->table_SetCol = $this->Shift(0);
     $this->TmpName = tempnam("/tmp", "php");
     $this->fp = fopen($this->TmpName, "w");
     $this->tableopt($Val);
   }   
   

   
   function out($Val)
   {
     $this->CValue = $this->CValue + 1;
     $Val = urlencode($Val);
     fputs($this->fp, $this->table_Subst . "[$this->table_SetRow][$this->table_SetCol][".$this->Shift($this->CValue)."][Value]=$Val\n");
   }
   


   function opt($Opt)
   {
     if ($Opt == "") {
       return;
     }

     $this->CValue = $this->CValue + 1;
     $Opt = urlencode($Opt);
     fputs($this->fp, $this->table_Subst . "[$this->table_SetRow][$this->table_SetCol][".$this->Shift($this->CValue)."][ COpt]=$Opt\n");
   }



   function outs($Opt, $Val)
   {
     $this->opt($Opt);
     $this->out($Val);
   }



   function tds($Row, $Col, $Opt, $Val)
   {
     if ("*" == (string)$Row) {
       // echo htmlspecialchars("1 $Row $Col $Opt $Val"), "<br>\n";
       $Row = $this->table_SetRow;
     } else {
       $Row = $this->Shift($Row);
     }
   
     if ("*" == (string)$Col) {
       // echo htmlspecialchars("2 $Row $Col $Opt $Val"), "<br>\n";
       $Col = $this->table_SetCol;
     } else {
       $Col = $this->Shift($Col);
     }

     $this->table_SetRow = $Row;
     $this->table_SetCol = $Col;
     $this->opt($Opt);
     $this->out($Val);
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
     $Opt = urlencode($Opt);
     fputs($this->fp, $this->table_Subst . "[$this->table_SetRow][ ROpt]=$Opt\n");
   }



   function subtable($Opt)
   {
     $c = Count($this->table_Substarray) + 1;
     $this->table_Substarray[$c][Subst]  = $this->table_Subst;
     $this->table_Substarray[$c][SetRow] = $this->table_SetRow;
     $this->table_Substarray[$c][SetCol] = $this->table_SetCol;

     $this->CValue = $this->CValue + 1;
     $this->table_Subst .= "[$this->table_SetRow][$this->table_SetCol][".$this->Shift($this->CValue)."][Table]";
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



   function TRNext($Opt)
   {
     $k = substr(strrchr($this->table_SetRow, "."), 1);
     $this->trs($k + 1, $Opt);
     $this->table_SetCol=$this->Shift(0);
   }



   function TDNext($Opt)
   {
     $k = substr(strrchr($this->table_SetCol, "."), 1);
     //Debug("=$k=$this->table_SetCol=");
     $this->table_SetCol=$this->Shift($k + 1);
     $this->opt($Opt);
   }
   
   
   
   function tableopt($Opt)
   {
     if ($Opt == "") {
       return;
     }

     $Opt = urlencode($Opt);
     fputs($this->fp, $this->table_Subst . "[ TOpt]=$Opt\n");
   }


   function fputs($n, $v)
   {
     fputs($this->fp, $this->table_Subst . "$n=$v\n");
   }


   function Shift($v)
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
     fclose($this->fp);
/*
     $this->fp = popen("sort $this->TmpName", "r");
     $r = $this->TmpName."<pre>";
     while (!feof($this->fp)) {
       $r .= htmlspecialchars(fgets($this->fp, 4096));
     }
     $r .= "</pre>";
     pclose($this->fp);

     echo $r;
     $r = "";
*/
     $this->fp = popen("sort $this->TmpName", "r");
     $this->NextFs();
     $this->ShowTable($r, "", "");
     pclose($this->fp);

     unlink($this->TmpName);
     return $r;
     return "<pre>\n".htmlspecialchars($r)."\n</pre>\n$r";
   }


   function ShowTable(&$r, $lev, $rol)
   {
     $ropt = "";
     $rval = "";

     while (($this->fs != "") && (substr($this->fs, 0, strlen($lev)) == $lev)) {
       if (substr($this->fs, strlen($lev), 7) == "[ TOpt]") {
         $ropt .= substr($this->fs, strlen($lev) + 8);
         $this->NextFs();
       } else {
         $this->ShowRow($rval, $lev . substr($this->fs, strlen($lev), 7), "  " . $rol);
       }
     }

     $r .= "$rol<TABLE $ropt>\n";
     $r .= $rval;
     $r .= "$rol</TABLE>\n";
   }


   function ShowRow(&$r, $lev, $rol)
   {
     $ropt = "";
     $rval = "";

     while (($this->fs != "") && (substr($this->fs, 0, strlen($lev)) == $lev)) {
       if (substr($this->fs, strlen($lev), 7) == "[ ROpt]") {
         $ropt .= substr($this->fs, strlen($lev) + 8);
         $this->NextFs();
       } else {
         $this->ShowCell($rval, $lev . substr($this->fs, strlen($lev), 7), "  " . $rol);
       }
     }

     $r .= "$rol<TR $ropt>\n";
     $r .= $rval;
     $r .= "$rol</TR>\n";
   }


   function ShowCell(&$r, $lev, $rol)
   {
     $ropt = "";
     $rval = "";

     while (($this->fs != "") && (substr($this->fs, 0, strlen($lev)) == $lev)) {
       $control = substr($this->fs, strlen($lev) + 7, 7);
       if ($control == "[Table]") {
         $this->ShowTable($rval, $lev . substr($this->fs, strlen($lev), 7 + 7), "  " . $rol);
       } else if ($control == "[ COpt]") {
                $ropt .= substr($this->fs, strlen($lev) + 8 + 7);
                $this->NextFs();
              } else if ($control == "[Value]") {
                       $rval .= "  " . $rol . substr($this->fs, strlen($lev) + 8 + 7) . "\n";
                       $this->NextFs();
                     }
       // debug("==");
       // debug($this->fs);
     }

     $r .= "$rol<TD $ropt>\n";
     $r .= "$rval";
     $r .= "$rol</TD>\n";
   }

   function NextFs()
   {
     if (feof($this->fp)) {
       $this->fs = "";
       return;
     }

     $this->fs = fgets($this->fp, 1024*1024);
     // while ($this->fs[strlen($this->fs)] == '\n') {
     //}
     $this->fs = Chop($this->fs);

     if ($this->fs == "") {
       return;
     }
     $this->fs = urldecode($this->fs);
   }
} // class stable

?>