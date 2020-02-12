<?php

if(!isset($_REPORT_INC_PHP_)) {

$_REPORT_INC_PHP_ = 0;


/*
    Abstract's methods :

    function Eof()
    function GoNext()
    function GoTop()
    function GetValue($FieldName)
*/

class CReport
{
    function CReport($dz)
    {
        reset($dz);
        while(list($n, $v) = each($dz)) {
            if (!isset($this->MaxNumberLevel) || $this->MaxNumberLevel < $v[0]) {
                $this->MaxNumberLevel = $v[0];
            }
            if (!isset($this->MinNumberLevel) || $this->MinNumberLevel > $v[0]) {
                $this->MinNumberLevel = $v[0];
            }

            $this->Data[$v[0]][contFieldName] = $v[1];

            $this->Data[$v[0]][title]         = $v[2];
            if (preg_match_all("'\^([a-z0-9\-\_]+?)([^\^]?)\^'ei", $this->Data[$v[0]][title], $MATH)) {
                reset($MATH[1]);
                while(list($n, $fname) = each($MATH[1])) {
                    switch ($MATH[2][$n]) {
                        case '#':
                        case '$':
                                    $this->Data[$v[0]][titleAccum][$fname][flag] = $MATH[2][$n];
                                    $this->Data[$v[0]][titleAccum][$fname][value] = 0;
                                    break;
                    }
                }
            } else {
                $this->Data[$v[0]][amountAccum] = array();
            }

            $this->Data[$v[0]][amount]        = $v[3];
            if (preg_match_all("'\^([a-z0-9\-\_]+)([^\^]?)\^'ei", $this->Data[$v[0]][amount], $MATH)) {
                reset($MATH[1]);
                while(list($n, $fname) = each($MATH[1])) {
                    $this->Data[$v[0]][amountAccum][$fname][flag] = $MATH[2][$n];
                    switch ($MATH[2][$n]) {
                        case '#':
                        case '$':
                        case '+':
                                    $this->Data[$v[0]][amountAccum][$fname][value] = 0;
                                    break;
                        case '' :
                                    $this->Data[$v[0]][amountAccum][$fname][value] = "";
                                    break;
                    }
                }
            } else {
                $this->Data[$v[0]][amountAccum] = array();
            }
        }

        //echo "MaxNumberLevel ", $this->MaxNumberLevel, "<br>";
        //echo ShArr($this->Data);
    }

    function Run()
    {
        $result = "";

        $this->GoTop();
        $SubScaningStepNumber = 1;
        while (!$this->Eof()) {
            $result .= $this->Scaning($this->MaxNumberLevel, $SubScaningStepNumber);
            //$this->GoNext();
        }

        return $result;
    }

    function Scaning($lev, &$LevelScaningStepNumber)
    {
        $result .= "";

        //echo "lev 1 $lev<br>";

        if ($lev < 0) {
            $this->StartLevel($lev, $LevelScaningStepNumber);
            $this->CalcAmounts();
            $this->EndLevel($lev, $LevelScaningStepNumber);
            $this->GoNext();
            return $result;
        }

        if ( !isset($this->Data[$lev]) ) {
            $result .= $this->Scaning($lev - 1, $LevelScaningStepNumber);
            return $result;
        }

        if ($this->Data[$lev][contFieldName] != "") {
            $this->Data[$lev][contFieldValue] = $this->GetValue( $this->Data[$lev][contFieldName] );
        }

        //echo "lev 2 $lev<br>";

        $this->StartLevel($lev, $LevelScaningStepNumber);

        if ($this->Data[$lev][title] != "") {
            $result .= $this->PutTitle($lev, $LevelScaningStepNumber);
        }

        $this->Data[$lev][amountCount] = 0;

        //echo "lev 3 $lev<br>";
        //echo sharr($this->Data[$lev]), "<br>";

        reset($this->Data[$lev][amountAccum]);
        while(list($fname, $value) = each($this->Data[$lev][amountAccum])) {
            switch($value[flag]) {
                case '+':
                            $this->Data[$lev][amountAccum][$fname][value] = 0;
                            break;
                case '' :
                            $this->Data[$lev][amountAccum][$fname][value] = "";
                            break;
            }
        }

        $SubScaningStepNumber = 1;
        if ($this->Data[$lev][contFieldName] != "" || ($lev == $this->MaxNumberLevel && $lev != $this->MinNumberLevel)) {
            while (!$this->isExitLevel($lev)) {
                $result .= $this->Scaning($lev - 1, $SubScaningStepNumber);
            }
        } else {
            $result .= $this->Scaning($lev - 1, $SubScaningStepNumber);
        }

        //echo "lev 2 $lev<br>";

        if ($this->Data[$lev][amount] != "") {
            $result .= $this->PutAmount($lev, $LevelScaningStepNumber);
        }
        $this->EndLevel($lev, $LevelScaningStepNumber);

        $LevelScaningStepNumber++;

        return $result;
    }

    function StartLevel($lev, $LevelScaningStepNumber)
    {
    }

    function EndLevel($lev, $LevelScaningStepNumber)
    {
    }

    function PutTitle($lev, $LevelScaningStepNumber)
    {
        return preg_replace("'\^([a-z0-9\-\_]+)([^\^]?)\^'ei", "\$this->GetTitleField($lev, $LevelScaningStepNumber, '\\1')", $this->Data[$lev][title]);
    }

    function GetTitleField($lev, $LevelScaningStepNumber, $fname)
    {
        if( isset($this->Data[$lev][titleAccum][$fname]) ) {
            if($this->Data[$lev][titleAccum][$fname][flag] == '#') {
                if ($LevelScaningStepNumber == 1) {
                    $this->Data[$lev][titleAccum][$fname][value] = 0;
                }
                $this->Data[$lev][titleAccum][$fname][value]++;
            }

            if($this->Data[$lev][titleAccum][$fname][flag] == '$') {
                $this->Data[$lev][titleAccum][$fname][value]++;
            }

            return $this->Data[$lev][titleAccum][$fname][value];
        } else {
            return $this->GetValue($fname);
        }
    }

    function PutAmount($lev, $LevelScaningStepNumber)
    {
        return preg_replace("'\^([a-z0-9\-\_]+)([^\^]?)\^'ei", "\$this->GetAmountField($lev, $LevelScaningStepNumber, '\\1')", $this->Data[$lev][amount]);
    }

    function GetAmountField($lev, $LevelScaningStepNumber, $fname)
    {
        if ($this->Data[$lev][amountAccum][$fname][flag] == '#') {
            if ($LevelScaningStepNumber == 1) {
                $this->Data[$lev][amountAccum][$fname][value] = 0;
            }
            $this->Data[$lev][amountAccum][$fname][value]++;
        }

        if ($this->Data[$lev][amountAccum][$fname][flag] == '$') {
            $this->Data[$lev][amountAccum][$fname][value]++;
        }

        return $this->Data[$lev][amountAccum][$fname][value];
    }

    function CalcAmounts()
    {
        for($i = 0; $i <= $this->MaxNumberLevel; $i++) {
            if ( !isset($this->Data[$i]) ) {
                continue;
            }

            $this->Data[$i][amountCount]++;

            reset($this->Data[$i][amountAccum]);
            while(list($fname, $value) = each($this->Data[$i][amountAccum])) {
                switch ($value[flag]) {
                    case '+':
                                $this->Data[$i][amountAccum][$fname][value] += $this->GetValue($fname);
                                break;
                    case '' :
                                $this->Data[$i][amountAccum][$fname][value] = $this->GetValue($fname);
                                break;
                }
            }
        }
    }


    function isExitLevel($lev)
    {
        if($this->Eof()) {
            return 1;
        }

        for($i = $this->MaxNumberLevel; $i >= $lev; $i--) {
            if ( !isset($this->Data[$i]) ) {
                continue;
            }

            if ($this->Data[$i][contFieldName] == "") {
                if ($i == $this->MaxNumberLevel && $i != $this->MinNumberLevel) {
                    continue;
                }
                return 0;
            }
            if($this->Data[$i][contFieldValue] != $this->GetValue($this->Data[$i][contFieldName])) {
                return 1;
            }
        }

        return 0;
    }
} // end of class CReport



class CPostgreSQLReport extends CReport
{
    function CPostgreSQLReport($r, $dz)
    {
        CReport::CReport($dz); // inherited constructor
        $this->cursor = $r;
        $this->GoTop();
    }


    function Eof()
    {
        return $this->cursor->Eof();
    }


    function GoNext()
    {
        return $this->cursor->Next();
    }


    function GoTop()
    {
        return $this->cursor->Set(0);
    }


    function GetValue($FieldName)
    {
        return $this->cursor->Field($FieldName);
    }
}


class CMySQLReport extends CReport
{
    function CMySQLReport($r, $dz)
    {
        CReport::CReport($dz); // inherited constructor
        $this->cursor = $r;
        $this->GoTop();
    }

    function Eof()
    {
        return $this->num_row >= mysql_num_rows($this->cursor);
    }

    function GoNext()
    {
        if (!$this->Eof()) {
            $this->num_row++;
        }

        $this->Fetch();

        return $this->num_row;
    }

    function GoTop()
    {
        $this->num_row = 0;

        $this->Fetch();
        return $this->num_row;
    }

    function GetValue($FieldName)
    {
        //echo "$FieldName<hr>";
        return $this->FieldsAddray[$FieldName];
    }

    function Fetch()
    {
        if (!$this->Eof()) {
            mysql_data_seek ( $this->cursor, $this->num_row );
            $this->FieldsAddray = mysql_fetch_array ( $this->cursor );
            //echo sharr($this->FieldsAddray), "<hr>";
        } else {
            $this->FieldsAddray = array();
        }
    }
} // end of class CMySQLReport


} // end of $_REPORT_INC_PHP_

?>
