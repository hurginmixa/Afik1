<?php

include "_config.inc.php";
require "utils.inc.php";
require "file.inc.php";
require "db.inc.php";

require "view.inc.php";
require "screen.inc.php";

class CAdminTools extends screen
{
    var $WidthTools = 15;

    function CAdminTools()
    {
        global $INET_SRC, $INET_IMG, $TEMPL, $FACE, $INET_HELP;
        global $PROGRAM_SRC, $TEMPL;

        screen::screen(); // inherited constructor

        if ($this->USR->lev() < 1) {
          $this->out("<h1>Access denited !</h1>");
          exit;
        }

        $this->SetTempl("admin_opt");

        $this->PgTitle = "<b>$TEMPL[title]</b>";
    }

    function Tools()
    {
        global $FACE, $TEMPL;

        $this->SubTable("border=0  nowrap cellspacing = '0' cellpadding = '0' grborder width='100%'"); {

            $this->TRNext("class='toolst' align='center'"); {
                $this->TDNext(); {
                    $this->out( "<b>$TEMPL[tools_title]</b>" );
                }
            }

            //$this->TRNext("class='toolsl' "); {
            //    $this->TDNext("nowrap"); {
            //        $this->out("<A HREF= 'list_billing.php?UID=$this->UID&FACE=$FACE' class='toolsa'>$TEMPL[stat]</A>");
            //    }
            //}

            if ($this->USR->Lev() == 2) {
                $this->TRNext("class='toolsl'"); {
                    $this->TDNext("nowrap"); {
                        $this->out("<A HREF= 'list_domains.php?UID=$this->UID&FACE=$FACE'  class='toolsa'>$TEMPL[list_dom] </A>");
                    }
                }
                $this->TRNext("class='toolsl'"); {
                    $this->TDNext("nowrap"); {
                        $this->out("<A HREF= 'list_languages.php?UID=$this->UID&FACE=$FACE'  class='toolsa'>$TEMPL[lang_supp] </A>");
                    }
                }
            } else if ($this->USR->Lev() == 1) {
                $this->TRNext("class='toolsl'"); {
                    $this->TDNext("nowrap"); {
                        $this->out("<A HREF= 'list_users.php?UID=$this->UID&FACE=$FACE&DOMAIN=" . $this->USR->sysnumdomain() . "'  class='toolsa'>$TEMPL[list_users]</A>");
                    }
                }
            }

        } $this->SubTableDone();
    }

    function Scr()
    {
        if ($this->USR->lev() == 2) {
          $this->Scr_SUser();
        } else {
          $this->Scr_DAdmin();
        }
    }


    function Scr_SUser()
    {
        global $TEMPL, $PROGRAM_FILES;

        $this->SubTable("border=0  width='100%' nowrap cellspacing = '0' cellpadding = '0' grborder"); {

            $this->TRNext(""); {
                $this->TDNext("class='ttp' nowrap"); {
                    $this->out($TEMPL[param_name]);
                }
                $this->TDNext("class='ttp' nowrap"); {
                    $this->out($TEMPL[param_value]);
                }
            }

            $this->TRNext(""); {
                $r = DBExec("select count(*) as count from domain");

                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[number_domain]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out($r->count() . $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $r = DBExec("select count(*) as count from usr");

                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[number_usr]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out($r->count() . $this->TextShift);
                }
            }

            $r = DBExec("select count(*) as count, sum(fsize) as fsize from file");

            $this->TRNext(""); {

                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[actual_number_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out($r->count() . $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[actual_size_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out(AsSize($r->fsize()) . $this->TextShift);
                }
            }

            $r = DBExec("select count(*) as count, sum(fsize) as fsize from fs, file where fs.sysnumfile = file.sysnum");

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[virtual_number_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out($r->count() . $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[virtual_size_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out(AsSize($r->fsize()) . $this->TextShift);
                }
            }

            $r = DBExec("select count(*) as count, sum(fsize) as fsize from file where file.nlink = 0");

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[empty_number_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out($r->count() . $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[empty_size_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out(AsSize($r->fsize()) . $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $r = DBExec("select sum(quote) as sumquote from domain");

                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[quote_size]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out(AsSize($r->sumquote()) . $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap align='left' colspan=3"); {
                    $this->out($this->TextShift . $TEMPL[fs_status] . "<br>");
                    $this->SubTable("class='tab' width='100%' border='1' cellspacing = '0' cellpadding = '0'"); {
                        $this->TRNext(); {
                            $this->TDNext("class='tlp' rowspan=2"); {
                                $this->out("&nbsp;" . "num storage" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center' colspan=5"); {
                                $this->out("&nbsp;" . "In afik1's file system" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center' colspan=2"); {
                                $this->out("&nbsp;" . "In file system" . "&nbsp;");
                            }
                        }
                        $this->TRNext(); {
                            $this->TDNext("class='tlp' align='center'"); {
                                $this->out("&nbsp;" . "total size" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center'"); {
                                $this->out("&nbsp;" . "used" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center'"); {
                                $this->out("&nbsp;" . "free" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center'"); {
                                $this->out("&nbsp;" . "num files" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center'"); {
                                $this->out("&nbsp;" . "files size" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center'"); {
                                $this->out("&nbsp;" . "total size" . "&nbsp;");
                            }
                            $this->TDNext("class='tlp' align='center'"); {
                                $this->out("&nbsp;" . "free size" . "&nbsp;");
                            }
                        }
                        $r = DBExec("SELECT  storages.sysnum, storages.size, storages.used, SUM(file.fsize), COUNT(file.*) FROM storages LEFT JOIN file ON storages.sysnum = file.numstorage GROUP BY storages.sysnum, storages.size, storages.used");
                        while(!$r->eof()) {
                            $StoragePath = "$PROGRAM_FILES/storage" . $r->sysnum();
                            while(is_link($StoragePath)) {
                                $StoragePath = readlink ($StoragePath);
                            }

                            $StorageDiskTotal = disk_total_space($StoragePath);
                            $StorageDiskFree  = disk_free_space($StoragePath);

                            $this->TRNext(); {
                                $this->TDNext("class='tlp' nowrap"); {
                                    $this->out("&nbsp;" . $r->sysnum() . "&nbsp;");
                                }
                                $this->TDNext("class='tlp' align='right' nowrap"); {
                                    $this->out("&nbsp;<span title='", $r->size(), "'>", AsSize($r->size()), "</span>&nbsp;");
                                }
                                $this->TDNext("class='tlp' align='right' nowrap"); {
                                    $this->out("&nbsp;<span title='", $r->used(), "'>", AsSize($r->used()) . "</span>&nbsp;");
                                }
                                $this->TDNext("class='tlp' align='right' nowrap"); {
                                    $this->out("&nbsp;<span title='", $r->size() - $r->used(), "'>", AsSize($r->size() - $r->used()) . "</span>&nbsp;");
                                }
                                $this->TDNext("class='tlp' align='right' nowrap"); {
                                    $this->out("&nbsp;", $r->count() . "&nbsp;");
                                }
                                $this->TDNext("class='tlp' align='right' nowrap"); {
                                    $this->out("&nbsp;<span title='", $r->sum(), "'>", AsSize($r->sum()) . "</span>&nbsp;");
                                }
                                $this->TDNext("class='tlp' align='right' nowrap"); {
                                    $this->out("&nbsp;<span title='", $StorageDiskTotal, "'>", AsSize($StorageDiskTotal) . "</span>&nbsp;");
                                }
                                $this->TDNext("class='tlp' align='right' nowrap"); {
                                    $this->out("&nbsp;<span title='", $StorageDiskFree, "'>", AsSize($StorageDiskFree) . "</span>&nbsp;");
                                }
                            }

                            $r->next();
                        }
                    } $this->SubTableDone();

                    $this->out("<pre>" . htmlspecialchars($text) . "</pre>");
                }
            }

        } $this->SubTableDone();
    }


    function Scr_DAdmin()
    {
        global $TEMPL;

        $this->SubTable("border=0  width='100%' nowrap cellspacing = '0' cellpadding = '0' grborder"); {

            $this->TRNext(""); {
                $this->TDNext("class='ttp' nowrap"); {
                    $this->out($TEMPL[param_name]);
                }
                $this->TDNext("class='ttp' nowrap"); {
                    $this->out($TEMPL[param_value]);
                }
            }

            $r = DBExec("select count(*) as count from usr where usr.sysnumdomain = " . $this->USR->sysnumdomain());

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[number_usr]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out($r->count() . $this->TextShift);
                }
            }

            $r = DBExec("select count(*) as count, sum(fsize) as fsize from fs, file, usr where fs.sysnumfile = file.sysnum and fs.owner = usr.sysnum and usr.sysnumdomain = " . $this->USR->sysnumdomain());

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[actual_number_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out($r->count() . $this->TextShift);
                }
            }

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[actual_size_files]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out(AsSize($r->fsize()) . $this->TextShift);
                }
            }


            $r = DBExec("select sum(quote) as sumquote from domain where sysnum = " . $this->USR->sysnumdomain());

            $this->TRNext(""); {
                $this->TDNext("class='tlp' nowrap"); {
                    $this->out($this->TextShift . $TEMPL[quote_size]);
                }
                $this->TDNext("class='tlp' nowrap align='right'"); {
                    $this->out(AsSize($r->sumquote()) . $this->TextShift);
                }
            }

        } $this->SubTableDone();
    }

} // end of class CAdminTools

ConnectToDB();

$AdminTools = new CAdminTools();
$AdminTools->run();

UnconnectFromDB();
exit;


?>

