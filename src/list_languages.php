<?php


include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("cont.inc.php");

require("db.inc.php");
ConnectToDB();

require("screen.inc.php");

class CListLanguagesScreen extends screen
{
    function CListLanguagesScreen()
    {
        global $arrParametersList, $arrLanguagesList, $arrScreensList;
        global $FACE, $_GET, $TEMPL;

        screen::screen();    // inherited constructor
        $this->SetTempl("list_languages");

        if ($this->USR->lev() < 2) {
            echo("<h1>Access denited !</h1>");
            exit;
        }


        $this->ReadLanguages();

        $Language = $_GET[Language];
        $Screen   = $_GET[Screen];

        if ($Language == "" || !in_array($Language, $arrLanguagesList)) {
            $Language = (in_array($FACE, $arrLanguagesList)) ? $FACE : $arrLanguagesList[0];
            header("Location: $INET_SRC/list_languages.php?UID=$this->UID&FACE=$FACE&Language=$Language&Screen=$Screen");
            exit;
        }

        if ($Screen == "" || !in_array($Screen, $arrScreensList)) {
            $Screen = $arrScreensList[0];
            header("Location: $INET_SRC/list_languages.php?UID=$this->UID&FACE=$FACE&Language=$Language&Screen=$Screen");
            exit;
        }

        $this->PgTitle = "<b>$TEMPL[title]</b> ";

        $this->Request_actions["sSelectScreen"]         = "SelectScreen()";
        $this->Request_actions["sSaveChanges"]          = "SaveChanges()";
        $this->Request_actions["sExitToOptions"]        = "ExitToOptions()";
    }

    function display()
    {
      $this->out("<form name='languages_form' method='post'>");
      screen::display();
      $this->out("</form>");
    }


    function ToolsBar()
    {
              global $Language, $Screen;
              global $arrParametersList, $arrLanguagesList, $arrScreensList;
              global $INET_IMG;
              global $TEMPL;

              $this->SubTable("border=0 width='100%' cellpadding=5 cellspacing=0");
              $this->TRNext();
                $this->TDNext("valign='middle' nowrap class='toolsbarl'");

                  $this->out($TEMPL[lang] . $this->ButtonBlank);
                  $this->out("<select name='Language' class='toolsbare'>");
                  while(list($n, $v) = each($arrLanguagesList)) {
                    $this->out("<option value='$v'" . ($Language == $v ? " SELECTED" : "") . ">$v</option>");
                  }
                  $this->out("</select>" . $this->SectionBlank);

                  $this->out($TEMPL[scrn] . $this->ButtonBlank);
                  $this->out("<select name='Screen' class='toolsbare'>");
                  while(list($n, $v) = each($arrScreensList)) {
                    $this->out("<option value='$v'" . ($Screen == $v ? " SELECTED" : "") . ">$v</option>");
                  }
                  $this->out("</select>" . $this->SectionBlank);

                  $this->out(makeButton("type=1& name=sSelectScreen& value=$TEMPL[bt_select]& title=$TEMPL[bt_select_ico] ") . $this->SectionBlank);

                  $this->out(makeButton("type=1& name=sExitToOptions& value=$TEMPL[bt_exit]& title=$TEMPL[bt_exit_ico] ") . $this->SectionBlank);

              $this->SubTableDone();

              $this->out("<img src='$INET_IMG/filler1x1.gif'>");

              $this->SubTable("border=0 width='100%' cellpadding=5 cellspacing=0");
              $this->TRNext();
                $this->TDNext("valign='middle' nowrap class='toolsbarl'");
                  $this->out(makeButton("type=1& name=sSaveChanges& value=$TEMPL[bt_save]& title=$TEMPL[bt_save_ico]"));
              $this->SubTableDone();

              $this->out("<img src='$INET_IMG/filler3x1.gif'>");
    }

    function Scr()
    {
              global $Language, $Screen;
              global $arrParametersList, $arrLanguagesList, $arrScreensList;
              global $INET_IMG;

              $this->SubTable("border=0 grborder cellpadding=0 cellspacing=0 width='100%'");

              if (is_array($arrParametersList[$Screen])) {
                  reset($arrParametersList[$Screen]);
                  while(list($n, $v) = each($arrParametersList[$Screen])) {
                       $this->TRNext("");
                       $this->TDNext("class='tlp' align='center'");
                       $this->out($this->TextShift . "$n" . $this->TextShift);
                       $this->TDNext("class='tlp' ");
                       $this->out($this->TextShift . $this->nbsp(htmlspecialchars($v[en])) . "<br>");
                       if (strlen($v[en]) <= 80) {
                         $this->out($this->TextShift . "<input name='Params[$n]' value=\"" . htmlspecialchars($v[$Language]) . "\" size='80' class='toolsbare'>");
                       } else {
                         $this->out($this->TextShift . "<textarea name='Params[$n]' cols='80' rows='3' wrap='soft' class='toolsbare'>" . htmlspecialchars(preg_replace("/<br>/", "<br>\n", $v[$Language])) . "</textarea>");
                       }
                  }
              } else {
                  $this->out("Error");
              }

              $this->SubTableDone();

              // $this->SubTable("border=1");
              // $this->out(sharr($arrParametersList));
              // $this->SubTableDone();
    } //Src


    function SelectScreen()
    {
              global $INET_SRC, $_POST, $FACE;


              $Language = $_POST[Language];
              $Screen   = $_POST[Screen];

              header("Location: $INET_SRC/list_languages.php?UID=$this->UID&FACE=$FACE&Language=$Language&Screen=$Screen");
              exit;
    }


    function SaveChanges()
    {
        global $INET_SRC, $_GET, $_POST, $FACE;
        global $arrParametersList, $arrLanguagesList, $arrScreensList;
        global $PROGRAM_LANG;

        $Language = $_GET[Language];
        $Screen   = $_GET[Screen];
        $Params   = $this->StripSlashesFromArray($_POST[Params]);

        if(!is_array($Params)) {
            return;
        }

        // echo "<pre>";
        // print_r($Params);
        // echo "</pre>";

        reset($Params);
        while(list($n, $v) = each($Params)) {
            $v = preg_replace("/<br>\r?\n/s", "<br>", $v);
            $v = preg_replace('/\r?\n/s', " ", $v);


            if (isset($arrParametersList[$Screen][$n])) {
                $arrParametersList[$Screen][$n][$Language] = $v;
            }
        }

        $string = "";
        reset($arrParametersList[$Screen]);
        while(list($n, $v) = each($arrParametersList[$Screen])) {
            #if (isset($v[$Language]) && $v[$Language] != "") {
                $string .= "$n = $v[$Language]\r\n";
            #}
        }

        #echo $string;

        $this->Log("Save language $PROGRAM_LANG/$Language/$Screen");
        $f = fopen("$PROGRAM_LANG/$Language/$Screen", "w");
        if (!$f) {
            $this->Log("Saving language $PROGRAM_LANG/$Language/$Screen failure");
            return;
        }
        fputs($f, $string);
        fclose($f);

        header("Location: $INET_SRC/list_languages.php?UID=$this->UID&FACE=$FACE&Language=$Language&Screen=$Screen");
        exit;
    }


    function ReadLanguages()
    {
        global $PROGRAM_LANG;
        global $arrParametersList, $arrLanguagesList, $arrScreensList;

        $arrParametersList = array();
        $arrScreensList = array();
        $arrLanguagesList = array();
        $list_lang_handle = opendir($PROGRAM_LANG);
        while($lang_dir = readdir($list_lang_handle)) {
            if(is_link("$PROGRAM_LANG/$lang_dir") || is_file("$PROGRAM_LANG/$lang_dir") || $lang_dir == "." || $lang_dir == "..") {
                continue;
            }

            $arrLanguagesList[$lang_dir] = $lang_dir;

            $list_screen_handle = opendir("$PROGRAM_LANG/$lang_dir");
            while($screen_name = readdir($list_screen_handle)) {
                if(!is_file("$PROGRAM_LANG/$lang_dir/$screen_name") || !preg_match('/\.txt$/i', $screen_name) || $screen_name == "." || $screen_name == "..") {
                  continue;
                }

                $arrScreensList[$screen_name] = $screen_name;

                $lines = implode("", file("$PROGRAM_LANG/$lang_dir/$screen_name"));
                $lines = eregi_replace("\r", "", $lines);

                if (preg_match_all("/^[ ]*([a-z0-9][^\t =]+)[\t ]*=[\t ]*(.*)[\t ]*$/im", $lines, $listLines)) {
                    while (list($n, $v) = each($listLines[0])) {
                        if (preg_match("/[\r\n ]+/", $listLines[1][$n])) {
                            continue;
                        }
                        $arrParametersList[$screen_name][$listLines[1][$n]][$lang_dir] = preg_replace("'(?<!\\\\)(\\\$([a-z0-9_]+))'ise", "read_config_callback('\\2')", $listLines[2][$n]);
                    }
                }
            }
        }

        //$this->out(sharr($arrParametersList));
        sort  ($arrLanguagesList);
        reset ($arrLanguagesList);

        ksort ($arrParametersList);
        reset ($arrParametersList);

        sort  ($arrScreensList);
        reset ($arrScreensList);
    } // ReadLanguages


    function ExitToOptions()
    {
        global $INET_SRC, $FACE;

        header("Location: $INET_SRC/admin_opt.php?UID=$this->UID&FACE=$FACE");
        exit;
    }

    function Authorize() {
        screen::Authorize();
    }


} // end class

$ListLanguagesScreen = new CListLanguagesScreen();
$ListLanguagesScreen->run();


?>
