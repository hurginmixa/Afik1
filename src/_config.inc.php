<?php

if(!isset($__CONFIG_INC_)) {

$__CONFIG_INC_=0;


function read_config_callback($text)
{
    global $INI_ARG;
    if (isset($INI_ARG[$text]) && $INI_ARG[$text] != "") {
      return $INI_ARG[$text];
    }
    if (isset($GLOBALS[$text]) && $GLOBALS[$text] != "") {
      return $GLOBALS[$text];
    }
    return $text[2];
}


function read_config()
{
    global $INI_ARG;

    $conf = implode("", file("afik1.cf.php"));
    if (preg_match_all("'^[ ]*([a-z0-9][^ =]+?)[ ]*=[ ]*(.*?)[ ]*$'ism", $conf, $find)) {
        while (list($n, $v) = each($find[0])) {
            $INI_ARG[$find[1][$n]] = preg_replace("'(?<!\\\\)(\\\$([a-z0-9_]+))'ise", "read_config_callback('\\2')", $find[2][$n]);
            $GLOBALS[$find[1][$n]] = $INI_ARG[$find[1][$n]];
        }
    }
}


$PROTOCOL = $_SERVER[HTTPS] == "on" ? "https:" : "http:";

read_config();

$COUNTRY[0] = "Other";     $LINKS[0] = "$LOCAL_SERVER";
$COUNTRY[1] = "USA";       $LINKS[1] = "$LOCAL_SERVER";
$COUNTRY[2] = "Israel";    $LINKS[2] = "$LOCAL_SERVER";
$COUNTRY[3] = "Russia";    $LINKS[3] = "$LOCAL_SERVER";
$COUNTRY[4] = "Ukraine";   $LINKS[4] = "$LOCAL_SERVER";

} // !isset($__CONFIG_INC_)

?>
