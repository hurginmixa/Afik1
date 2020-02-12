<?php
    require("inc/report.inc.php");

    class CAfik1Report extends CReport
    {
        function CAfik1Report($r, $dz)
        {
            CReport::CReport($dz); // inherited constructor
            $this->cursor = $r;
        }

        function Eof()
        {
            return $this->cursor->eof();
        }

        function GoNext()
        {
            return $this->cursor->Next();
        }

        function GoTop()
        {
            return $this->cursor->set(0);
        }

        function GetValue($FieldName)
        {
            return $this->cursor->Field($FieldName);
        }
    }

    include "top_report.php";

    $result = mysql_query("SELECT * from enrichments");

    include "footer_rep.php";
?>
