<?php

include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("compose.inc.php");

//require("db.inc.php");
//ConnectToDB();


require("view.inc.php");

class CViewImg extends view {

        function OpenSession()
        {
        }


        function run()
        {
               global $HTTP_SERVER_VARS, $PROGRAM_IMG, $PROGRAM_LANG, $REQUEST_METHOD;
               global $FACE, $QUERY_STRING;

               $bufsize = 102400;

               if ($FACE == "") {
                 $FACE = "default";
               }
               //header("X-FACE: $FACE");

               $path_info = $HTTP_SERVER_VARS[PATH_INFO];
               if(!preg_match("'[^/]+$'", $path_info, $arr)) {
                 header("Content-Type: text/plain");
                 echo "Error in URL ! '$HTTP_SERVER_VARS[PATH_INFO]' '$QUERY_STRING'";
                 return;
               }

               $path_info = $arr[0];
               $path_info = "$PROGRAM_LANG/$FACE/img/$path_info";
               //header("X-path_info1: $path_info");


               if (!file_exists($path_info)) {
                 $path_info = $arr[0];
                 $path_info = "$PROGRAM_IMG/$path_info";
               }

               //header("X-path_info2: $path_info");

               $f = @fopen($path_info, "rb");
               if ($f <= 0) {
                 header("Content-Type: text/plain");
                 echo "File not open ! $path_info";
                 return;
               }
               //$this->Log($REQUEST_METHOD . " " . $path_info);

               clearstatcache();
               $stat = stat($path_info);

               #$etag = "$stat[0]-$stat[1]-$stat[7]-$stat[9]-$stat[10]";
               $etag = sprintf("%x-%x-%x", $stat[1], $stat[7], $stat[9]);

               #$size = filesize($path_info);
               $size = $stat[7];

               #$ctime = gmdate( 'D, d M Y H:i:s T', filectime($path_info) );
               $ctime = gmdate( 'D, d M Y H:i:s T', $stat[9]);


               $Content = "APPLICATION/OCTET-STREAM";
               if (preg_match("'\.gif$'i", $path_info)) {
                 $Content = "image/gif";
               }
               if (preg_match("'\.jpg$'i", $path_info)) {
                 $Content = "image/jpeg";
               }

               header("Last-Modified: $ctime");
               header("ETag: \"$etag\"");
               header("Accept-Ranges: bytes");
               header("Content-Type: $Content");
               header("Content-Length: $size");
               header("Cache-Control: public");
               //header("X-Powered-By: ");
               //header("X-Powered-By: ");
               if ($REQUEST_METHOD != "HEAD") {
                   while(!feof($f)) {
                      $buf = fread($f, $bufsize);
                      echo $buf;
                   }
               }

               fclose($f);
        }


        function Log()
        {
        }

        function Authorize()
        {
        }


        function PageHeader()
        {
        }


        function PageFoot()
        {
        }

        function HTTP_Header()
        {
        }

        function CheckConnectionNumber()
        {
        }

}

// ShGlobals();

$ViewImg = new CViewImg();
$ViewImg->run();

exit;

?>
