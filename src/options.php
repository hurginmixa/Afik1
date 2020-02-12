<?php

/*

    function Display()
        function Tools()

        function ToolsBar()
            function ToolsBar_Main()
            function ToolsBar_Names()
            function ToolsBar_Contacts()
            function ToolsBar_Password()
            function ToolsBar_ForwardMail()
            function ToolsBar_Pop3()
            function ToolsBar_Signature()
            function ToolsBar_PerScreenCount()
        function MakeToolsBar($Message)

        function Scr()
            function Scr_Main()
            function Scr_Names()
            function Scr_Contacts()
            function Scr_Password()
            function Scr_ForwardMail()
            function Scr_Pop3()
            function Scr_Signature()
            function Scr_PerScreenCount()
        function MakeInputText($name, $value, $size, $maxlength)
        function MakeInputPassword($name, $value, $size, $maxlength)
        function MakeInputCheckbox($name, $value, $checked)
        function MakeInputHidden($name, $value)
        function MakeInputSelect($name, $value, $list)
        function MakeInputTextarea($name, $value, $length, $row, $col, $options)

    function rSave()
    function rCancel()
    function rExit()

    function Mes()

    function refreshScreen()
*/

require "tools.inc.php";
require "file.inc.php";
require("screen.inc.php");

class COptionsScreen extends screen
{
    var $WidthTools = 15;

    function COptionsScreen()
    {
        global $sSubmit, $sCancel;
        global $Mes, $TEMPL, $INET_SRC, $FACE;
        global $_SERVER;

        $this->screen(); // inherited constructor
        $this->SetTempl("options");

        $this->BaseURL = "$INET_SRC/options.php?UID=$this->UID&FACE=$FACE";

        $this->PgTitle = "<b>$TEMPL[title]</b> ";
        $this->Request_actions["sCancel"]       = "rCancel()";
        $this->Request_actions["sSubmit"]       = "rSave()";
        $this->Request_actions["sExit"]         = "rExit()";

        if ($_SERVER[REQUEST_METHOD] == "POST") {
            $this->SaveScreenStatus("s_OptionsScreen", array("Params") );
        }
    }


    function OpenSession() // overlaped virtuals function
    {
        global $sDownloadZip, $s_OptionsScreen;

        parent::OpenSession();
        session_register("s_OptionsScreen");
    }


    function Display()
    {
        global $s_OptionsScreen, $Params;

        if (is_array($s_OptionsScreen[Status])) {
            $Params = $s_OptionsScreen[Status][Params];
        } else {
            $Params = $this->ReadDB($this->UID);
        }

        $this->out("<form method='post' name='optionsform'>");
        parent::Display();
        $this->out("</form>");
    }


    function Scr() // overlaped virtuals function
    {
        global $View, $s_OptionsScreen;

        unset($s_OptionsScreen[FieldsList]);

        if ($View == "names") {
            $this->Scr_Names();
        } else {
            if ($View == "contacts") {
                $this->Scr_Contacts();
            } else {
                if ($View == "password") {
                    $this->Scr_Password();
                } else {
                    if ($View == "frwmail") {
                        $this->Scr_ForwardMail();
                    } else {
                        if ($View == "pop3") {
                            $this->Scr_Pop3();
                        } else {
                            if ($View == "signature") {
                                $this->Scr_Signature();
                            } else {
                                if ($View == "perscreencount") {
                                    $this->Scr_PerScreenCount();
                                } else {
                                    $this->Scr_Main();
                                }
                            }
                        }
                    }
                }
            }
        }

        unset($s_OptionsScreen[Status]);

        // $this->out("<br><br><br><br>");

        // $this->SubTable("border = 1"); {
        //     $this->TRNext(); {
        //         $this->TDNext(); {
        //             $this->out(sharr($s_OptionsScreen), "&nbsp;");
        //         }
        //     }
        // } $this->SubTableDone();
    }


    function ToolsBar()
	{
        global $INET_IMG;
        global $View;

        if ($View == "names") {
            $this->ToolsBar_Names();
        } else {
            if ($View == "contacts") {
                $this->ToolsBar_Contacts();
            } else {
                if ($View == "password") {
                    $this->ToolsBar_Password();
                } else {
                    if ($View == "frwmail") {
                        $this->ToolsBar_ForwardMail();
                    } else {
                        if ($View == "pop3") {
                            $this->ToolsBar_Pop3();
                        } else {
                            if ($View == "signature") {
                                $this->ToolsBar_Signature();
                            } else {
                                if ($View == "perscreencount") {
                                    $this->ToolsBar_PerScreenCount();
                                } else {
                                    $this->ToolsBar_Main();
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->Out("<img src='$INET_IMG/filler2x1.gif'>");
	}

    function ToolsBar_Main()
    {
        global $TEMPL;
        global $INET_IMG;

        $this->SubTable("border = '0' width = '100%' cellpading='0' cellspacing='0'"); {
            $this->TRNext("class='toolsbarl' align='center' valign='middle' nowrap width='10%'"); {
                $this->TDNext( "class='toolsbarl' align='left'"); {
                        $this->OUT($this->ButtonBlank);
                        $this->OUT(makeButton("type=1& form=optionsform& name=sExit& img=$INET_IMG/optionsexit-passive.gif?FACE=$FACE& imgact=$INET_IMG/optionsexit.gif?FACE=$FACE& title=$TEMPL[bt_exit_ico]"));
                        $this->OUT($this->ButtonBlank);
                }
                $this->TDNext( "class='toolsbarl' align='left' valign='middle' nowrap width='90%'"); {
                    $this->Out("<br><b>$TEMPL[acc_info]</b><br>&nbsp;");
                }
            }
        } $this->SubTableDone();
    }


    function ToolsBar_Names()
    {
        global $TEMPL;
        $this->MakeToolsBar($TEMPL[name_info]);
    }


    function ToolsBar_Contacts()
    {
        global $TEMPL;
        $this->MakeToolsBar($TEMPL[contacts_info]);
    }


    function ToolsBar_Password()
    {
        global $TEMPL;
        $this->MakeToolsBar($TEMPL[password_info]);
    }


    function ToolsBar_ForwardMail()
    {
        global $TEMPL;
        $this->MakeToolsBar($TEMPL[forwardmail_info]);
    }


    function ToolsBar_Pop3()
    {
        global $TEMPL;
        $this->MakeToolsBar($TEMPL[pop3_info]);
    }


    function ToolsBar_Signature()
    {
        global $TEMPL;
        $this->MakeToolsBar($TEMPL[signature_info]);
    }


    function ToolsBar_PerScreenCount()
    {
        global $TEMPL;
        $this->MakeToolsBar($TEMPL[perscreencount_info]);
    }


    function MakeToolsBar($Message)
    {
        global $INET_IMG, $TEMPL;

        $this->SubTable("border = '0' width = '100%' cellpading='0' cellspacing='0'"); {
            $this->TRNext("class='toolsbarl' align='center' valign='middle' nowrap width='10%'"); {
                $this->TDNext( "class='toolsbarl' align='left'"); {
                        $this->OUT($this->ButtonBlank);
                        $this->OUT(makeButton("type=1& form=optionsform& name=sSubmit& img=$INET_IMG/optionssave-passive.gif?FACE=$FACE& imgact=$INET_IMG/optionssave.gif?FACE=$FACE& title=$TEMPL[bt_submit_ico]"));
                        $this->OUT($this->ButtonBlank);
                        $this->OUT(makeButton("type=1& form=optionsform& name=sCancel& img=$INET_IMG/optionscancel-passive.gif?FACE=$FACE& imgact=$INET_IMG/optionscancel.gif?FACE=$FACE& title=$TEMPL[bt_cancel_ico]"));
                        $this->OUT($this->ButtonBlank);
                }
                $this->TDNext( "class='toolsbarl' align='left' valign='middle' nowrap width='90%'"); {
                    $this->Out("<br><b>$Message</b><br>&nbsp;");
                }
            }
        } $this->SubTableDone();
    }


    function Scr_Main()
    {
        global $Params, $TEMPL, $INET_IMG;

        $FieldsList = array(
                        "firstname"         => array("link" => "names",    "check" => 0),
                        "lastname"          => array("link" => "names",    "check" => 0),
                        "password"          => array("link" => "password", "check" => 1),
                        "email"             => array("link" => "contacts", "check" => 0),
                        "frwmail"           => array("link" => "frwmail",  "check" => 1),
                        "country"           => array("link" => "contacts", "check" => 0),
                        "city"              => array("link" => "contacts", "check" => 0),
                        "address"           => array("link" => "contacts", "check" => 0),
                        "zip"               => array("link" => "contacts", "check" => 0),
                        "phone"             => array("link" => "contacts", "check" => 0),
                        "mphone"            => array("link" => "contacts", "check" => 0),
                        "fax"               => array("link" => "contacts", "check" => 0),
                        "nicq"              => array("link" => "contacts", "check" => 0),
                        "pop3link"          => array("link" => "pop3",     "check" => 1),
                        "pop3linkextservis" => array("link" => "pop3",     "check" => 1)
                      );


        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0' cellspacing = '0' grborder"); {
            reset($FieldsList);
            foreach($FieldsList as $key => $value) {
                $this->TRNext(); {
                    $this->TDNext( "class='tlp' nowrap width='20%'"); {
                        if ($value[link] == "") {
                            $this->Out("&nbsp;<b>$TEMPL[$key]</b>&nbsp;");
                        } else {
                            $URL = $this->BaseURL . "&View=$value[link]";
                            $this->Out("&nbsp;<a href='$URL'><span class='tlpa'><b>$TEMPL[$key]</b></span></a>&nbsp;");
                        }
                    }
                    $this->TDNext( "class='tlp' width='80%'"); {
                        if ($value[check]) {
                            if ($Params[$key]) {
                                $this->Out("&nbsp;" . $TEMPL[on] .  "&nbsp;");
                            } else {
                                $this->Out("&nbsp;" . $TEMPL[off] . "&nbsp;");
                            }
                        } else {
                            $this->Out("&nbsp;" . htmlspecialchars($Params[$key]) . "&nbsp;");
                        }
                    }
                }
            }

        } $this->SubTableDone();
    }


    function Scr_Names()
    {
        global $TEMPL, $Params;
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0' class='tab' grborder"); {
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' width='20%'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[firstname]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' width='80%'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("firstname", htmlspecialchars($Params[firstname]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[lastname]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("lastname", htmlspecialchars($Params[lastname]), 30, 30) . $this->TextShift);
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '3'  cellspacing = '0'"); {
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<i>$TEMPL[name_mes]</i>" . $this->TextShift);
                }
            }
        } $this->SubTableDone();
    }


    function Scr_Contacts()
    {
        global $TEMPL, $Params;
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0' class='tab' grborder"); {
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' width='20%'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[email]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' width='80%'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("email", htmlspecialchars($Params[email]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[country]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("country", htmlspecialchars($Params[country]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[city]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("city", htmlspecialchars($Params[city]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[address]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("address", htmlspecialchars($Params[address]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[zip]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("zip", htmlspecialchars($Params[zip]), 6, 6) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[phone]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("phone", htmlspecialchars($Params[phone]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[mphone]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("mphone", htmlspecialchars($Params[mphone]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[fax]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("fax", htmlspecialchars($Params[fax]), 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[nicq]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputText("nicq", htmlspecialchars($Params[nicq]), 30, 30) . $this->TextShift);
                }
            }
        } $this->SubTableDone();
    }


    function Scr_Password()
    {
        global $TEMPL, $Params;
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0' class='tab' grborder"); {
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' width='20%'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[password]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' width='80%'"); {
                    $this->Out($this->TextShift . $this->MakeInputPassword("password", "", 30, 30) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[repassword]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . $this->MakeInputPassword("rpassword", "", 30, 30) . $this->TextShift);
                }
            }
        } $this->SubTableDone();

        $this->out("<img src='$INET_IMG/filler2x1.gif'>");

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '3'  cellspacing = '0'"); {
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'"); {
                    $this->Out($this->TextShift . "<i>$TEMPL[passw_mes]</i>" . $this->TextShift);
                }
            }
        } $this->SubTableDone();
    }


    function Scr_ForwardMail()
    {
        global $TEMPL, $Params;
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0' class='tab' grborder"); {
            // $this->TRNext(); {
            //     $this->TDNext( "class='tlp' align='left' width='20%'"); {
            //         $this->Out($this->TextShift . "<b>$TEMPL[frwmail]</b>" . $this->TextShift);
            //     }
            //     $this->TDNext( "class='tlp' align='left' width='80%'"); {
            //         $this->Out($this->TextShift . $this->MakeInputCheckbox("frwmail", "1", $Params[frwmail]) . $this->TextShift);
            //         $this->Out($this->MakeInputHidden("email", $Params[email]));
            //     }
            // }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' width='20%' nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[frwmail_none]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' width='80%' nowrap"); {
                    $this->Out($this->TextShift . $this->MakeInputRadio("frwmail", "0", $Params[frwmail] == 0 || $Params[frwmail] == "") . $this->TextShift);
                }
            }
            if ($Params[email] != "") {
                $this->TRNext(); {
                    $this->TDNext( "class='tlp' align='left'  nowrap"); {
                        $this->Out($this->TextShift . "<b>" . sprintf($TEMPL[frwmail_email], $Params[email]) . "</b>" . $this->TextShift);
                    }
                    $this->TDNext( "class='tlp' align='left'  nowrap'"); {
                        $this->Out($this->TextShift . $this->MakeInputRadio("frwmail", "1", $Params[frwmail] == 1) . $this->TextShift);
                    }
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left'  nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[frwmail_other]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left'  nowrap"); {
                    $this->Out($this->TextShift);
                    $this->Out($this->MakeInputRadio("frwmail", "2", $Params[frwmail] == 2));
                    $this->Out($this->TextShift);
                    $this->Out($this->MakeInputText("frwaddres", htmlspecialchars($Params[frwaddres]), 30, 30));
                    $this->Out($this->TextShift);
                }
            }
            $this->Out($this->MakeInputHidden("email", $Params[email]));
        } $this->SubTableDone();
    }


    function Scr_Pop3()
    {
        global $TEMPL, $Params;
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0' class='tab' grborder"); {
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap width='20%'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[pop3link]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap width='80%'"); {
                    $this->Out($this->TextShift . $this->MakeInputCheckbox("pop3link", "1", $Params[pop3link]) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[pop3linksize]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift, "&nbsp;");
                    $this->Out($this->MakeInputText("pop3linksize_unit", htmlspecialchars($Params[pop3linksize_unit]), 5, 5));
                    $this->Out($this->TextShift);
                    $this->Out($this->MakeInputSelect("pop3linksize_scale", htmlspecialchars($Params[pop3linksize_scale]), array(1024 => "KB", 1024 * 1024 => "MB", 1024 * 1024 * 1024 => "GB")));
                    $this->Out($this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[pop3linkstoperiod]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . "&nbsp;" . $this->MakeInputText("pop3linkstoperiod", htmlspecialchars($Params[pop3linkstoperiod]), 5, 3) . $this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[pop3linkextservis]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . $this->MakeInputCheckbox("pop3linkextservis", "1", $Params[pop3linkextservis]) . $this->TextShift);
                }
            }
        } $this->SubTableDone();
    }


    function Scr_Signature()
    {
        global $TEMPL, $Params;
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0' class='tab' grborder"); {
            $signature_size = 512;

            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' valign='top' width='20%' nowrap"); {
                    $this->Out($this->TextShift, "<b>$TEMPL[signature]</b>", $this->TextShift, "<br><br>");
                }
                $this->TDNext( "class='tlp' align='left' nowrap width='80%'"); {
                    $this->Out($this->TextShift . $this->MakeInputTextarea("signature", $Params[signature], $signature_size, 5, 70, "onkeyup='javascript:onSignatureChange()'") . $this->TextShift, "<br>");
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' valign='top' width='20%' nowrap"); {
                    $this->Out("&nbsp;");
                }
                $this->TDNext( "class='tlp' align='left' valign='top' width='20%' nowrap"); {
                    $this->Out($this->TextShift);
                    $this->Out($TEMPL[signature_leftchars], $this->TextShift, "<input name='signature_left_chars' readonly size=3>");
                    $this->Out($this->TextShift);
                    $this->Out(makebutton("type=2& form=optionsform& name=sPreview& value=$TEMPL[signature_preview]& onclick=javascript:SignaturePreview()"));
                    $this->Out($this->TextShift);
                }
            }
        } $this->SubTableDone();

        $this->OutLn("<script language='javascript'>");
        $this->OutLn(" function onSignatureChange()");
        $this->OutLn(" {");
        $this->OutLn("   signature_left_chars = $signature_size - document.optionsform[\"Params[signature]\"].value.length");
        $this->OutLn("   document.optionsform[\"signature_left_chars\"].value = signature_left_chars");
        $this->OutLn("   if (signature_left_chars < 0) {");
        $this->OutLn("     alert(\"$TEMPL[signature_invalid_length]\")");
        $this->OutLn("   }");
        $this->OutLn(" }");
        $this->OutLn(" onSignatureChange()");
        $this->OutLn(" function SignaturePreview()");
        $this->OutLn(" {");
        $this->OutLn("   var text = document.optionsform[\"Params[signature]\"].value");
        $this->OutLn("   if (text.match(\"<[^<]*>\")) {");
        $this->OutLn("     text = text.replace(/\\r?\\n/g, \"\")");
        $this->OutLn("   } else {");
        $this->OutLn("     text = text.replace(/\\r?\\n/g, \"<br>\")");
        $this->OutLn("   }");

        $this->OutLn("   hPreView = window.open(\"\", \"\", \"status=yes,toolbar=no,menubar=no,location=no,resizable=yes,width=500,height=300,scrollbars=yes\")");
        $this->OutLn("   hPreViewDocument = hPreView.document");
        $this->OutLn("   hPreViewDocument.open()");
        $this->OutLn("   hPreViewDocument.writeln(\"<html>\")");
        $this->OutLn("   hPreViewDocument.writeln(\"<head>\")");
        $this->OutLn("   hPreViewDocument.writeln(\"<title>Signature preview</title>\")");
        $this->OutLn("   hPreViewDocument.writeln(\"<head>\")");
        $this->OutLn("   hPreViewDocument.writeln(\"<body>\")");
        $this->OutLn("   hPreViewDocument.writeln(text)");
        $this->OutLn("   ");
        $this->OutLn("   hPreViewDocument.writeln(\"</body>\")");
        $this->OutLn("   hPreViewDocument.close()");
        $this->OutLn("   ");
        $this->OutLn(" }");
        $this->OutLn("</script>");
    }


    function Scr_PerScreenCount()
    {
        global $TEMPL, $Params;
        global $INET_IMG;

        $this->SubTable("border='0' width='100%' class='tab' cellpadding = '0'  cellspacing = '0' class='tab' grborder"); {
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap width='20%'"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[perscreencount_myftp]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap width='80%'"); {
                    if ($Params[perscreencount_myftp] == 0) {
                        $Params[perscreencount_myftp] = "";
                    }
                    $this->Out($this->TextShift, "&nbsp;");
                    $this->Out($this->MakeInputText("perscreencount_myftp", htmlspecialchars($Params[perscreencount_myftp]), 5, 5));
                    $this->Out($this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[perscreencount_friendsftp]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    if ($Params[perscreencount_friendsftp] == 0) {
                        $Params[perscreencount_friendsftp] = "";
                    }
                    $this->Out($this->TextShift, "&nbsp;");
                    $this->Out($this->MakeInputText("perscreencount_friendsftp", htmlspecialchars($Params[perscreencount_friendsftp]), 5, 5));
                    $this->Out($this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[perscreencount_mail]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    if ($Params[perscreencount_mail] == 0) {
                        $Params[perscreencount_mail] = "";
                    }
                    $this->Out($this->TextShift, "&nbsp;");
                    $this->Out($this->MakeInputText("perscreencount_mail", htmlspecialchars($Params[perscreencount_mail]), 5, 5));
                    $this->Out($this->TextShift);
                }
            }
            $this->TRNext(); {
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    $this->Out($this->TextShift . "<b>$TEMPL[perscreencount_address]</b>" . $this->TextShift);
                }
                $this->TDNext( "class='tlp' align='left' nowrap"); {
                    if ($Params[perscreencount_address] == 0) {
                        $Params[perscreencount_address] = "";
                    }
                    $this->Out($this->TextShift, "&nbsp;");
                    $this->Out($this->MakeInputText("perscreencount_address", htmlspecialchars($Params[perscreencount_address]), 5, 5));
                    $this->Out($this->TextShift);
                }
            }
        } $this->SubTableDone();
    }


    function MakeForm()
    {
    }


    function MakeInputText($name, $value, $size, $maxlength)
    {
        global $s_OptionsScreen;

        $s_OptionsScreen[FieldsList][$name] = $maxlength;

        return "<INPUT name=\"Params[$name]\" size=\"$size\" maxlength=\"$maxlength\"  type=\"text\"   value=\"$value\">";
    }


    function MakeInputPassword($name, $value, $size, $maxlength)
    {
        global $s_OptionsScreen;

        $s_OptionsScreen[FieldsList][$name] = $maxlength;

        return "<INPUT name=\"Params[$name]\" size=\"$size\" maxlength=\"$maxlength\"  type=\"password\"   value=\"$value\">";
    }


    function MakeInputCheckbox($name, $value, $checked)
    {
        global $s_OptionsScreen;

        if (!isset($s_OptionsScreen[FieldsList][$name]) || $s_OptionsScreen[FieldsList][$name] < strlen($value)) {
            $s_OptionsScreen[FieldsList][$name] = strlen($value);
        }

        $CHECKED = $checked ? "CHECKED" : "";

        return "<INPUT name=\"Params[$name]\" type=\"checkbox\" value=\"$value\" $CHECKED>";
    }


    function MakeInputHidden($name, $value)
    {
        global $s_OptionsScreen;

        $s_OptionsScreen[FieldsList][$name] = strlen($value);

        return "<INPUT name=\"Params[$name]\" type=\"hidden\" value=\"$value\">";
    }


    function MakeInputSelect($name, $value, $list)
    {
        global $s_OptionsScreen;

        if (!is_array($list) || count($list) == 0) {
            return "";
        }

        $result = "<select name=\"Params[$name]\">";

        reset($list);
        while(list($n, $v) = each($list)) {
            if (!isset($s_OptionsScreen[FieldsList][$name]) || $s_OptionsScreen[FieldsList][$name] < strlen($n)) {
                $s_OptionsScreen[FieldsList][$name] = strlen($n);
            }
            $result .= "<option value='$n'" . ($n == $value ? " SELECTED" : "") . ">$v</option>";
        }

        $result .= "</select>";

        return $result;
    }


    function MakeInputTextarea($name, $value, $length, $row, $col, $options)
    {
        global $s_OptionsScreen;

        $s_OptionsScreen[FieldsList][$name] = $length;

        return "<TEXTAREA name = \"Params[$name]\" cols='$col' rows='$row' class='toolsbare' $options>" . htmlspecialchars($value) . "</TEXTAREA>";
    }


    function MakeInputRadio($name, $value, $checked)
    {
        global $s_OptionsScreen;

        if (!isset($s_OptionsScreen[FieldsList][$name]) || $s_OptionsScreen[FieldsList][$name] < strlen($value)) {
            $s_OptionsScreen[FieldsList][$name] = strlen($value);
        }

        $CHECKED = $checked ? "CHECKED" : "";

        return "<INPUT name=\"Params[$name]\" type=\"radio\" value=\"$value\" $CHECKED>";
    }


    function Tools()
    {
        global $TEMPL, $FACE, $REQUEST_URI, $INET_SRC;

        $ListItems = array("" => "tools_common", "names" => "tools_names", "contacts" => "tools_contacts", "signature" => "tools_signature",
                           "password" => "tools_password", "frwmail" => "tools_frwrdmail", "pop3" => "tools_pop3", "perscreencount" => "tools_perscreencount");

        $this->SubTable("border=0 width='100%' nowrap cellspacing = '0' cellpadding = '0' grborder"); {
            // $this->TRNext(); {
            //     $this->TDNext("class='toolst' nowrap"); {
            //         $this->OUT($this->TextShift, $TEMPL[tools_title], $this->TextShift);
            //     }
            // }

            reset($ListItems);
            foreach($ListItems as $key => $value) {
                $this->TRNext(); {
                    $URL = $this->BaseURL . ($key != "" ? "&View=$key" : "");
                    $CLASS   = "toolsl";
                    $CLASS_A = "toolsa";
                    if (substr($URL, strlen($INET_SRC)) == $REQUEST_URI) {
                        $CLASS   = "toolsla";
                        $CLASS_A = "toolsaa";
                    }

                    $this->TDNext("class='$CLASS' nowrap"); {
                        $this->Out($this->TextShift);
                        $this->Out("<a href='$URL'><span class='$CLASS_A'>" . $TEMPL[$value] . "</span></a>");
                        $this->Out($this->TextShift);
                    }
                }
            }
        } $this->SubTableDone();
    }


    function rSave()
    {
        global $s_OptionsScreen, $Params, $View;

        if ( !is_array($s_OptionsScreen[FieldsList]) || count($s_OptionsScreen[FieldsList]) == 0 ) {
            $this->rCancel();
        }

        foreach(array_keys($s_OptionsScreen[FieldsList]) as $field) {
            if (strlen($Params[$field]) > $s_OptionsScreen[FieldsList][$field]) {
                $s_OptionsScreen[Mes] = 4;
                $s_OptionsScreen[MesParam] = $field;
                $this->refreshScreen();
            }

            $NParams[$field] = trim($Params[$field]);
        }

        if ($s_OptionsScreen[FieldsList][password]) {
            if ($NParams[password] != $NParams[rpassword]) {
                $s_OptionsScreen[Mes] = 1;
                $this->refreshScreen();
            }
            unset($NParams[rpassword]);

            if (strlen($NParams[password]) < 6) {
                $s_OptionsScreen[Mes] = 2;
                $this->refreshScreen();
            }


            if (!preg_match("/^[a-z0-9_]+$/i", $NParams[password])) {
                $s_OptionsScreen[Mes] = 3;
                $this->refreshScreen();
            }
        }

        if ($s_OptionsScreen[FieldsList][email]) {
            if ($NParams[email] == "") {
                $NParams[frwmail] = "";
            } else {
                if ( !is_emailaddress($NParams[email], 1) ) {
                    $s_OptionsScreen[Mes] = 6;
                    $this->refreshScreen();
                }
            }
        }

        if ($s_OptionsScreen[FieldsList][frwmail]) {
            if ($NParams[frwmail] == 0) {
                $NParams[frwmail] = "";
            }
            if ($NParams[frwmail] == 1 && $NParams[email] == "") {
                $s_OptionsScreen[Mes] = 5;
                $this->refreshScreen();
            }
            if ( $NParams[frwmail] == 2 && !is_emailaddress($NParams[frwaddres], 1) ) {
                $s_OptionsScreen[Mes] = 10;
                $this->refreshScreen();
            }
        }

        if ($s_OptionsScreen[FieldsList][pop3link]) {
            if ($NParams[pop3link] == "") {
                $NParams[pop3linksize_unit] = "";
                $NParams[pop3linksize_scale] = "";
                $NParams[pop3linkstoperiod] = "";
                $NParams[pop3linkextservis] = "";
            } else {
                if (!preg_match("/^[1-9][0-9]*$/", $NParams[pop3linksize_unit])) {
                    $s_OptionsScreen[Mes] = 7;
                    $this->refreshScreen();
                }
                if (!preg_match("/^[1-9][0-9]*$/", $NParams[pop3linkstoperiod])) {
                    $s_OptionsScreen[Mes] = 8;
                    $this->refreshScreen();
                }
                if (!preg_match("/^[1-9][0-9]*$/", $NParams[pop3linksize_scale])) {
                    $s_OptionsScreen[Mes] = 9;
                    $this->refreshScreen();
                }
            }
        }

        if ($s_OptionsScreen[FieldsList][perscreencount_myftp]) {
            $NParams[perscreencount_myftp]      = abs((int)($NParams[perscreencount_myftp]));
            $NParams[perscreencount_friendsftp] = abs((int)($NParams[perscreencount_friendsftp]));
            $NParams[perscreencount_mail]       = abs((int)($NParams[perscreencount_mail]));
            $NParams[perscreencount_address]    = abs((int)($NParams[perscreencount_address]));
        }

        $this->WriteDB($this->UID, $NParams);

        $this->rCancel();
    }


    function rCancel()
    {
        global $View, $s_OptionsScreen;

        unset($s_OptionsScreen[Status]);
        $View = "";

        $this->refreshScreen();
    }


    function rExit()
    {
        global $INET_SRC, $FACE;

        header("Location: " . "$INET_SRC/welcome.php?UID=" . $this->UID . "&FACE=$FACE");
        exit;
    }


    function ReadDB($NumEdit)
    {
        $UsrFields = array("name", "password");
        $r_usr = DBFind("usr", "sysnum = $NumEdit", "");
        while (list($n, $Name) = each($UsrFields)) {
            $Rez[$Name] = URLDecode(trim($r_usr->Field($Name)));
        }

        $r_usr = DBFind("usr_ua", "sysnumusr = $NumEdit order by name, nset", "");

        $r_usr->set(0);
        while(!$r_usr->eof()) {
            $Name  = $r_usr->name();

            $Value = "";
            while(!$r_usr->eof() && $Name == $r_usr->name()) {
                $Value .= trim($r_usr->value());
                $r_usr->Next();
            }

            $Value = URLDecode($Value);
            $Rez[$Name] = $Value;

        }

        return $Rez;
    }


    function WriteDB($NumEdit, $Params)
    {
        $NParams = $Params;
        if (!is_array($Params) || count($Params) == 0) {
            return 0;
        }

        while (list($n, $v) = each($NParams)) {
            $NParams[$n] = URLEncode($v);
        }

        if ($NumEdit != 0) {
            $r_usr = DBFind("usr", "sysnum = $NumEdit", "");
            if ($r_usr->NumRows()==1) {
                $User = $NumEdit;
            } else {
                $NumEdit = 0;
            }
        }

        if ($NumEdit == 0) {
            $User = NextVal("usr_seq");
            DBExec("INSERT INTO usr (sysnum, sysnumdomain, creat) VALUES ($User, $this->Numer, 'now'::abstime)");

            DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Inbox', 1, 'd')");
            DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Sent Items',  2, 'd')");
            DBExec("INSERT INTO fld (sysnum, sysnumusr, name, ftype, sort) VALUES ( NextVal('fld_seq'), $User, 'Trash', 5, 'd')");
        }

        $UsrFields = array("name", "password");
        while (list($n, $Name) = each($UsrFields)) {
            if (isset($NParams[$Name])) {
                DBExec("UPDATE usr SET $Name = '$NParams[$Name]' WHERE sysnum = $User");
                unset ($NParams[$Name]);
            }
        }
        DBExec("UPDATE usr SET mod = 'now'::abstime WHERE sysnum = $User");


        if (count($NParams) == 0) {
            return $User;
        }

        reset($NParams);
        while (list($Name, $Value) = each($NParams)) {
            DBExec("DELETE FROM usr_ua WHERE sysnumusr = $User AND name = '$Name'", __LINE__);
            if($Value != "") {
                $nset = 0;
                $len = 120;
                do {
                    $TMP = substr($Value, 0, $len);
                    DBExec("INSERT INTO usr_ua (sysnumusr, name, value, nset) VALUES ($User, '$Name', '$TMP', '$nset')", __LINE__);

                    $Value = substr($Value, $len);
                    $nset++;
                } while(strlen($Value) > 0);
            }
        }
    } // function WriteDB


    function Mes()
    {
        global $Mes, $MesParam, $s_OptionsScreen, $TEMPL;


        if ($Mes == "") {
            $Mes = $s_OptionsScreen[Mes];
            unset($s_OptionsScreen[Mes]);
        }

        if ($MesParam == "") {
            $MesParam = $s_OptionsScreen[MesParam];
            unset($s_OptionsScreen[MesParam]);
        }

        if ($Mes == "") {
            return;
        }

        if ($TEMPL[err_mes . $Mes] != "") {
          $this->ErrMes(sprintf($TEMPL[err_mes . $Mes], $MesParam));
        } else {
          $this->ErrMes(sprintf("Unknow error number %s %s", $Mes, $MesParam));
        }
    }


    function refreshScreen()
    {
        global $SCRIPT_NAME, $FACE, $View;

        $URL = "$SCRIPT_NAME?UID=$this->UID&FACE=$FACE";
        if ($View != "") {
            $URL .= "&View=" . URLENCODE($View);
        }

        UnconnectFromDB();

        parent::refreshScreen($URL);

        //header("Location: $URL");
        //exit;
    }


} // class COptionsScreen

ConnectToDB();

$OptionsScreen = new COptionsScreen();
$OptionsScreen->Run();

UnconnectFromDB();


?>
