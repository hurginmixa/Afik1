#!/usr/bin/perl


#  sub BEGIN

#  use tools;
#  use db_pg;
#  use sendmail;
#  use strict 'vars';
#  use readconf;
#  $main::BufferSize       = 1024 * 10;
#  $main::AllocateStepSize = 1024 * 100;


#  sub main()
#  sub ReadQuote($)
#  sub DecodeParam()
#  sub CheckParam()
#  sub SaveFiles()
#  sub PutHTML($)
#  sub GetPasteFileName($FS, $name_org, $owner)
#  sub GetPasteFileName1($$$)
#  sub ParseHTTPBody()
#  sub Read()
#  sub ReadAll()

#  sub MessErrorDBConnect()
#  sub MessErrorLogin($)
#  sub MessErrorInvalidMethod()
#  sub MessErrorLength()
#  sub MessErrorFileNameSize($)
#  sub MessErrorContentType()
#  sub MessErrorBoundary()
#  sub MessErrorMIMEStruct($)
#  sub MessErrorInternalError($)
#  sub MessErrorOtherError($)
#  sub MakeErrorHTML($)
#  sub GetUnicID
#  sub EraseTmpFiles()
#  sub PLog


#  local $main::IntrHandle
#######################################################################################


sub BEGIN
{
    my $pwd;
    chomp($pwd = `pwd`);
    my $prog;
    chomp($prog = $0);
    while (-l $prog) {
      if ( $prog =~ /^[^\/]/ && $prog =~ /^(.+)\/[^\/]+$/) {
        $pwd .= "/" . $1 ; #if ($prog != /^\.\//);
      } else {
        $pwd = $prog;
        $pwd =~ s/\/[^\/]*$//;
      }
      $prog = readlink($prog);
      $prog = $pwd . "/" . $prog if ($prog =~ /^[^\/]/);
    }
    $prog =~ s/^\.\///;
    $prog =~ s/[^\/]*$//;
    $prog =~ s/\/$//;
    $prog = $pwd . "/" . $prog if ($prog =~ /^[^\/]/);
    @INC = ($prog, @INC) if $prog ne "";

    #PLog join("\n", @INC);
}

use tools;
use utils;
use db_pg;
use sendmail;
use strict 'vars';

#use File::Copy;

#use Fcntl;


use readconf;

$main::BufferSize       = 1024 * 10;
$main::AllocateStepSize = 1024 * 100;


sub PLog($);

#############################################################################

sub main()
{
    PLog "Started";

    PLog "Open data base dbname=${main::DBASE} user=${main::POSTGRES_USER}";

    if (!ConnectDB("dbname=${main::DBASE} user=${main::POSTGRES_USER}")) {
        MessErrorDBConnect();
        return;
    }

    DecodeParam();

    if (!CheckParam()) {
        return;
    }

    ReadQuote($param::OWNER);

    if ($ENV{'REQUEST_METHOD'} eq "POST") {

        #Previous Check quote
        PLog "Prev Check Disk's Quote: Content Length '$param::ContetnLength'";

        if ($quote::UsrDiskUsage + $param::ContetnLength >= $quote::UsrQuote) {
            MessErrorOtherError(sprintf("Over User Disk's Quote : Disk Usage %s Upload Contetn Length %d Quote %s", $quote::UsrDiskUsage, $param::ContetnLength, $quote::UsrQuote));
            EraseTmpFiles();
            return 0;
        }

        if ($quote::DomainDiskUsage + $param::ContetnLength >= $quote::DomainQuote) {
            MessErrorOtherError(sprintf("Over Domain Disk's Quote : Disk Usage %s Upload Contetn Length %d Quote %s", $quote::DomainDiskUsage, $param::ContetnLength, $quote::DomainQuote));
            EraseTmpFiles();
            return 0;
        }

        # Save
        if (SaveFiles()) {
            PutHTML(1);
        }
        unlink $param::StatusFileName if ($param::StatusFileName ne "" && -e $param::StatusFileName);
    } else {
        PutHTML(0);
    }

    PLog "Ended";
}

#############################################################################

sub ReadQuote($)
{
    my($Owner) = @_;
    my($r);

    $r = DBExec("SELECT usr.quote        as usrquote," .
                       "domain.quote     as domainquote, " .
                       "usr.diskusage    as usrdiskusage, " .
                       "domain.diskusage as domaindiskusage, " .
                       "domain.userquote as defaultusrquote where usr.sysnumdomain = domain.sysnum and usr.sysnum = '$Owner'");

    if ($r->NumRows() != 1) {
        MessErrorOtherError("Unable definition quote's");
        EraseTmpFiles();
        return 0;
    }


    $quote::UsrQuote = $r->Value("usrquote");
    if ($quote::UsrQuote eq "" || $quote::UsrQuote eq "0") {
        $quote::UsrQuote = $r->Value("defaultusrquote");
        PLog("UsrQuote $quote::UsrQuote");
    }
    $quote::DomainQuote = $r->Value("domainquote");
    $quote::UsrDiskUsage = $r->Value("usrdiskusage");
    $quote::DomainDiskUsage = $r->Value("domaindiskusage");

    PLog sprintf("Disk's Quote : Domain's Disk Usage '%s'; User's Disk Usage '%s'; Domain's Quote '%s'; User's Quote '%s'", $quote::DomainDiskUsage, $quote::UsrDiskUsage, $quote::DomainQuote, $quote::UsrQuote);
}

#############################################################################

sub DecodeParam()
{
    ($param::UID, $param::KEY, $param::KEYR, $param::CUID1, $param::CUID1v, $param::CUID2, $param::CUID2v, $param::FS) = ("", "", "", "", "", "", "", "");

    $param::ContetnLength = 0;
    $param::UnicID = "";
    $param::FACE = "";

    if (defined($ENV{QUERY_STRING})) {
        if ($ENV{QUERY_STRING} =~ /UID=([^&]*)/i)                          { $param::UID = $1; }
        if ($ENV{QUERY_STRING} =~ /FS=([^&]*)/i)                           { $param::FS = $1; }
        if ($ENV{QUERY_STRING} =~ /KEY=([^&]*)/i)                          { $param::KEY = tools::Decode($1); $param::KEYR = utils::CalcPasswHesh($param::UID) }
        if ($ENV{QUERY_STRING} =~ /UNI=([^&]*)/i)                          { $param::UnicID = tools::Decode($1); }
        if ($ENV{QUERY_STRING} =~ /FACE=([^&]*)/i)                         { $param::FACE = $1; }
    }
    if (defined($ENV{HTTP_COOKIE})) {
        if ($ENV{HTTP_COOKIE} =~ /CUID\[($param::UID)\]\[time\]=([^;]*)/i) { $param::CUID1 = $1; $param::CUID1v = $2; }
        if ($ENV{HTTP_COOKIE} =~ /CUID\[($param::UID)\]\[code\]=([^;]*)/i) { $param::CUID2 = $1; $param::CUID2v = $2; }
    }
    if (defined($ENV{'CONTENT_LENGTH'}))                                   { $param::ContetnLength = $ENV{'CONTENT_LENGTH'}; }

    if ($param::UnicID ne "") {
        $param::StatusFileName = "/tmp/wwwupld_stat_" . $param::UnicID;
    } else {
        $param::StatusFileName = "";
    }
    $param::StartTime = time();

    PLog "UID: '$param::UID' FS '$param::FS' UNI '$param::UnicID'";

    return 1;
}

#############################################################################

sub CheckParam()
{
  my($size, $md5, $AccessList, $r_usr, $r_acc);
  my($rez);
  my($r_file, $r_fs, $Name, $tmp);

  # Znachenija $rez
  # 0     - polzovatel' ne proverjalos';
  # 1     - identifikazija proshla uspeshno;
  # 2,3,4 - identifikazija ne proshla
  #-----------------------------------------------------------------------------------------
  $rez = 0;
  if ($param::KEY ne "") {
    $rez =  ($param::KEY eq $param::KEYR) ? 1 : 2;
    $param::USRNAME = $param::UID;
  }

  if ($rez == 0) {
    $r_usr = DBExec("select usr.sysnum as usrsysnum, usr.name as usrname, domain.name as domainname from usr, domain where usr.sysnumdomain = domain.sysnum and usr.sysnum = $param::UID");
    if ($r_usr->NumRows() != 1) {
      $rez = 3;
    } else {
      $param::USRNAME = $r_usr->Value("usrname") . "@" . $r_usr->Value("domainname");
      $rez = ($param::UID    ne "") &&
             ($param::UID    eq $param::CUID1) &&
             ($param::CUID1  eq $param::CUID2) &&
             ($param::CUID2v eq tools::md5(tools::md5($param::CUID1v . $param::UID) . $param::CUID1v . $param::UID))? 1 : 4;
    }
  }

  if ($rez != 1) {
    MessErrorLogin("USRNAME ?$param::USRNAME?<br>KEY ?" . ($param::KEY) . "?<br>KEYR ?$param::KEYR?<br>rez ?$rez?<br>UID ?$param::UID?<br>?$param::CUID1?<br>?$param::CUID2?<br>?$ENV{HTTP_COOKIE}?<br>");
    EraseTmpFiles();
    return 0;
  }


  #
  # Opredelenie dostupnostei directoryi i prav dostupa
  #------------------------------------------------------


  if($param::FS != 0) {
      $param::MainFolder = DBExec("select * from fs where sysnum = $param::FS and sysnumfile = 0");
      if ($param::MainFolder->NumRows() != 1) {
          MessErrorOtherError("Folder for upload not accessible or not found");
          EraseTmpFiles();
          return 0;
      } else {
          $param::OWNER = $param::MainFolder->Value("owner");
      }
  } else {
      $param::OWNER = $param::UID;
  }

  # USRNAME wsegda soderjit imja pol'zovatela, UID imja - ecli zachod po svoistavam ili UID esli zachod cherez sistemu.
  # Esli ne ravny znachit zachod cherez sistemu - w odnom imja wo 2 UID. W takom sluchae UID doljen byt' rawen Owner-u papki kuda delaem upload
  if ($param::USRNAME ne $param::UID) {
      $AccessList = "";
      $param::UpLink = "";
      $param::FullPath = "";

      if ($param::OWNER == $param::UID) {
        return 1;
      }
  }

  if ($param::FS == 0) {
      MessErrorOtherError("Dont acceess to folder root for upload");
      EraseTmpFiles();
      return 0;
  }

  $AccessList = "sysnumfs = " . $param::MainFolder->Value("sysnum"); #PLog "$AccessList";

  $param::UpLink = "";
  $param::FullPath = $param::MainFolder->Value("name");
  $r_fs = DBExec("select fs.sysnum, fs.name, fs.up from fs where fs.sysnum = " . $param::MainFolder->Value("up"));
  while (!$r_fs->Eof()) {
     $AccessList .= " or sysnumfs = " . $r_fs->Value("sysnum");  #PLog "$AccessList";
     $param::UpLink = ($r_fs->Value("sysnum") != $param::FS ? ($r_fs->Value("sysnum") . ":") : "") . $param::UpLink;
     $param::FullPath = $r_fs->Value("name") . "/" . $param::FullPath;
     $r_fs = DBExec("select fs.sysnum, fs.name, fs.up from fs where fs.sysnum = " . $r_fs->Value("up"));
  }

  #PLog "select * from acc where username = '$param::USRNAME' and ($AccessList) and (access = 'u' or access = 'w') and (expdate >= 'now'::abstime or expdate is NULL)";
  $r_acc = DBExec("select * from acc where username = '$param::USRNAME' and ($AccessList) and (access = 'u' or access = 'w') and (expdate >= 'now'::abstime or expdate is NULL)");
  if ($r_acc->NumRows() == 0) {
    MessErrorOtherError("Dont acceess to folder for upload");
    EraseTmpFiles();
    return 0;
  }

  return 1;
}

#############################################################################

sub SaveFiles()
{
    my($size, $md5, $r_usr);
    my($rez, $tmp);
    my($r_file, $r_fs, $Name, $tmp, $sysnumfile);
    my($Message, $To, $From);
    my($r_usr_ua) = DBExec("select * from usr_ua where sysnumusr = $param::OWNER and name = 'frwmail'");
    my($forwardmail)  = $r_usr_ua->NumRows() != 0;

    if(open (Status, ">" . $param::StatusFileName)) {
        print Status "<table border='0'><tr><td class='title' align='center'>";
        print Status "Upload started ...<br>&nbsp;";
        print Status "</td></tr></table>\n";
        close Status;
    }

    my($TmpFileFreeProc) =  sub($) {
                                my($tmp) = @_;

                                if ($tmp->{Tmp_File_Name} ne "") {
                                    PLog "Deleting " . $tmp->{Tmp_File_Name};
                                    unlink($tmp->{Tmp_File_Name});
                                }

                                if ( $tmp->{Tmp_File_Number} ne "" ) {
                                    PLog "Unallocate TMP Number " . $tmp->{Tmp_File_Number};
                                    DBExec("DELETE FROM file WHERE sysnum = " . $tmp->{Tmp_File_Number});
                                }
                            };


    $param::UploadFS = 0;
    @param::UploadFS = ();

    $rez = ParseHTTPBody();

    PLog "SaveFiles Empty clearing loop";
    $size = 0;
    for (my($i) = 0; $i <= $#parse::Vars; $i++) {
        $tmp = $parse::Vars[$i];

        PLog "'", $i, "' '", $#parse::Vars, "' '", $tmp->{Tmp_File_Name}, "' '" , $tmp->{File_Name}, "'";

        if (!$rez) {
            PLog "Invalid parssing of HTTP package Deleting TMP File number " . $tmp->{Tmp_File_Number};
            &{$TmpFileFreeProc}($tmp);
            splice @parse::Vars, $i;
            next;
        }

        if ($tmp->{File_Name} eq "") {
            next;
        }

        $size += -s $tmp->{Tmp_File_Name};
    }


    #Post Check quote
    PLog "Post Check Disk's Quote: Upload Size '$size'";

    if ($quote::UsrDiskUsage + $size >= $quote::UsrQuote) {
        MessErrorOtherError(sprintf("Over User Disk's Quote : Disk Usage %s Upload Size %d Quote %s", $quote::UsrDiskUsage, $size, $quote::UsrQuote));
        EraseTmpFiles();
        return 0;
    }

    if ($quote::DomainDiskUsage + $size >= $quote::DomainQuote) {
        MessErrorOtherError(sprintf("Over Domain Disk's Quote : Disk Usage %s Upload Size %d Quote %s", $quote::DomainDiskUsage, $size, $quote::DomainQuote));
        EraseTmpFiles();
        return 0;
    }


    #Save Files into Virtual File System
    PLog "SaveFiles Save File loop";

    for (my($i) = 0; $i <= $#parse::Vars; $i++) {
        $tmp = $parse::Vars[$i];

        PLog "Step with TMP File Nuber " . $tmp->{Tmp_File_Number};

        if ($tmp->{File_Name} eq "") {
            PLog "Invalid file name. Deleting TMP File number " . $tmp->{Tmp_File_Number};
            &{$TmpFileFreeProc}($tmp);
            next;
        }

        if ((! -e $tmp->{Tmp_File_Name}) || ($tmp->{Tmp_File_Name} eq "")) {
            PLog "Invalid TMP file name. Deleting TMP File number " . $tmp->{Tmp_File_Number};
            &{$TmpFileFreeProc}($tmp);
            next;
        }

        $md5  = md5sum($tmp->{Tmp_File_Name});
        $size = -s $tmp->{Tmp_File_Name};
        if ($tmp->{Content_Type} eq "") {
            $tmp->{Content_Type} = "application/octet-stream";
        }

        $tmp->{File_Name} =~ s/"/ /g;
        $tmp->{File_Name} =~ s/'/''/g;
        $Name = GetPasteFileName($param::FS, $tmp->{File_Name}, $param::OWNER);

        PLog "File name to save $Name";

        DBExec("Begin");
        DBExec("LOCK TABLE file, fs IN ACCESS EXCLUSIVE MODE");

        $r_file = DBExec("select sysnum from file where fsize = $size and ftype = '$tmp->{Content_Type}' and fcrc = '$md5'");
        if ($r_file->NumRows() == 1) {
            $sysnumfile = $r_file->Value("sysnum");
            PLog "The same file found with sysnum $sysnumfile. Deleting TMP File number " . $tmp->{Tmp_File_Number};
            &{$TmpFileFreeProc}($tmp);
        } else {
            $sysnumfile = $tmp->{Tmp_File_Number};
            DBExec("UPDATE file SET fsize = $size, ftype = '$tmp->{Content_Type}', fcrc = '$md5' WHERE sysnum = $tmp->{Tmp_File_Number}");
        }
        PLog "link's number $sysnumfile";


        $r_fs = DBExec("select NextVal('fs_seq') as maxsysnum");
        $param::UploadFS = $r_fs->Value("maxsysnum");
        PLog "UploadFS : '", $param::UploadFS, "'";

        $r_fs = DBExec("insert into fs (sysnum, name, ftype, up, sysnumfile, owner, creat) values ($param::UploadFS, '$Name', 'f', $param::FS, $sysnumfile, '$param::OWNER', 'now'::abstime)");
        if ($r_fs->Status() == PGRES_FATAL_ERROR) {
            DBExec("COMMIT");
            PLog "DBLastError: '", DBLastError(), "' Deleting TMP File number " . $tmp->{Tmp_File_Number};
            &{$TmpFileFreeProc}($tmp);
            next;
        }

        DBExec("COMMIT");

        DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('$param::OWNER', getdomain('$param::OWNER'), 'wwwupload', datetime('now'::abstime), '$size', '$param::UploadFS', '" . substr($param::USRNAME, 0, 20) . "', 1, '${ENV{REMOTE_ADDR}}')");

        $param::UploadFS[$#param::UploadFS+1] = $param::UploadFS;


        $r_usr = DBExec("select usr.sysnum as usrsysnum, usr.name as usrname, domain.name as domainname from usr, domain where usr.sysnumdomain = domain.sysnum and usr.sysnum = $param::OWNER");
        $To = $r_usr->Value("usrname") . "@" . $r_usr->Value("domainname");
        if ($To ne $param::USRNAME) {
            #PLog "To $To USRNAME $param::USRNAME";

            if ($forwardmail) {
                #$Message .= "\"/$param::FullPath\"";
                $Message .= " file \"$Name\"<br>";
            } else {
                #$Message .= "<a href='$main::INET_SRC/file_folder.php?UID=$param::OWNER&FS=$param::FS&UpLink=$param::UpLink'>\"/$param::FullPath\"</a>";
                $Message .= " file <a href='$main::INET_SRC/file_folder.php/".tools::urlencode($Name)."?UID=$param::OWNER&FS=".$param::MainFolder->Value("sysnum")."&".tools::urlencode("TagFile[]")."=$param::UploadFS&sDownload=1'>\"$Name\"</a><br>";
            }
            ## PLog "Message $Message";
        }
    } # for

    if ($Message ne "") {
        my($d) = `date -R`;

        $From = $To;
        $From =~ s/^[^@]+/System_Manager/;

        PLog("Send notification from '$From' to '$To'");

        if ($forwardmail) {
            $Message = "User " . $param::USRNAME . " upload in folder " . "\"/$param::FullPath\" at $d<br>" . $Message;
        } else {
            $Message = "User " . $param::USRNAME . " upload in folder " .
            "<a href='$main::INET_SRC/file_folder.php?UID=$param::OWNER&FS=$param::FS&UpLink=$param::UpLink'>\"/$param::FullPath\"</a>" .
            " at $d<br>" . $Message;
        }

        $Message = "<html><body>$Message</body></html>";

        if(!sendmail::sendmail({
                            "SMTPServ" => "localhost",
                            "Domain"   => "localhost",
                            "From"     => $From,
                            "To"       => $To,
                            "Message"  => $Message,
                            "Subject"  => "User uploaded file(s) $param::USRNAME",
                            "Header"   => "Content-Type: text/html\r\nFrom: $From\r\nX-Afik1-Access-Notification: on"
                          })) {
            PLog "SendMail Error : ", $sendmail::Mes;
        }
    }

    return $rez;
}

#############################################################################

sub PutHTML($)
{
    my($CloseWindow) = @_;
    print "Cache-Control: no-cache, no-store\r\n";
    print "Content-Type: text/html\r\n\r\n";

    my($CurrentQuote);

    $CurrentQuote = $quote::UsrQuote - $quote::UsrDiskUsage;
    if ($CurrentQuote > $quote::DomainQuote - $quote::DomainDiskUsage) {
        $CurrentQuote = $quote::DomainQuote - $quote::DomainDiskUsage
    }



    SetTempl("wwwupld", $param::FACE);

    print "
      <html>
      <head>
      <title>$main::TEMPL{title}</title>
    ";

    if(open(STYLE, "$main::PROGRAM_SRC/standard.css")) {
        print(join("", <STYLE>));
        close(STYLE);
    }

    print "
      </head>
      <body class='body'>
    ";

    #print $ENV{HTTP_COOKIE}, "<br>\n";
    #print $ENV{QUERY_STRING}, "<br>\n";
    #print $ENV{HTTP_HOST}, "<br>\n";
    #print $ENV{HOSTNAME}, "<br>\n";
    #print "=$param::UID=<br>=$param::KEY=<br>=$param::KEYR=<br>=$param::CUID1=<br>=$param::CUID2=<br>\n";

    #print "=$param::StatusFileName =<br>";


    print "<form name='upload' method='post' ENCTYPE='multipart/form-data'>";
    print "<table width='100%' cellpadding = '0' cellspacing='0'>"; {

        print "<tr>"; {
            print "<td class='title' valign='middle'>"; {
                print "<table width='100%' cellpadding = '5'>"; {
                    print "<tr>"; {
                        print "<td class='title' valign='middle'>"; {
                            print "<font size='+2'>$main::TEMPL{title}</font><br>";
                            print AsSize($CurrentQuote);
                        } print "</td>";
                    } print "</tr>";
                } print "</table>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td class='body' valign='middle'>"; {
                print "<img src='$main::INET_IMG/filler3x1.gif'>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td class='title' valign='middle'>"; {
                print "<table width='100%' cellpadding = '5'>"; {
                    print "<tr>"; {
                        print "<td class='title' valign='middle'>"; {
                            print "<img src='$main::INET_IMG/num-1.gif' align='absmiddle'>&nbsp;&nbsp;";
                            print $main::TEMPL{step1};
                        } print "</td>";
                    } print "</tr>";
                } print "</table>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td class='body' valign='middle'>"; {
                print "<img src='$main::INET_IMG/filler1x1.gif'>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td class='body' valign='middle'>"; {
                print "<table width='100%' cellpadding = '5' class='title'>"; {
                    print "<tr>"; {
                        print "<td class='tlp' nowrap>"; {
                            print "$main::TEMPL{file}&nbsp;1&nbsp;&nbsp;";
                            print "<input type='file' name='Userfile1' class='toolsbare' size=50>";
                        } print "</td>";
                    } print "</tr>";

                    print "<tr>"; {
                        print "<td class='tlp' nowrap>"; {
                            print "$main::TEMPL{file}&nbsp;2&nbsp;&nbsp;";
                            print "<input type='file' name='Userfile2' class='toolsbare' size=50>";
                        } print "</td>";
                    } print "</tr>";

                    print "<tr>"; {
                        print "<td class='tlp' nowrap>"; {
                            print "$main::TEMPL{file}&nbsp;3&nbsp;&nbsp;";
                            print "<input type='file' name='Userfile3' class='toolsbare' size=50>";
                        } print "</td>";
                    } print "</tr>";

                    print "<tr>"; {
                        print "<td class='tlp' nowrap>"; {
                            print "$main::TEMPL{file}&nbsp;4&nbsp;&nbsp;";
                            print "<input type='file' name='Userfile4' class='toolsbare' size=50>";
                        } print "</td>";
                    } print "</tr>";

                    print "<tr>"; {
                        print "<td class='tlp' nowrap>"; {
                            print "$main::TEMPL{file}&nbsp;5&nbsp;&nbsp;";
                            print "<input type='file' name='Userfile5' class='toolsbare' size=50>";
                        } print "</td>";
                    } print "</tr>";
                } print "</table>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td class='body' valign='middle'>"; {
                print "<img src='$main::INET_IMG/filler3x1.gif'>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td class='title' valign='middle'>"; {
                print "<table width='100%' cellpadding = '5' class='title'>"; {
                    print "<tr>"; {
                        print "<td>"; {
                            print "<img src='$main::INET_IMG/num-2.gif' align='absmiddle'>&nbsp;&nbsp;";
                            print $main::TEMPL{step2};
                        } print "</td>";
                    } print "</tr>";
                } print "</table>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td class='body' valign='middle'>"; {
                print "<img src='$main::INET_IMG/filler1x1.gif'>";
            } print "</td>";
        } print "</tr>";

        print "<tr>"; {
            print "<td>"; {
                print "<table width='100%' cellpadding = '5' class='title'>"; {
                    print "<tr>"; {
                        print "<td class='tlp' valign='middle'>"; {
                            print "<INPUT TYPE='button' VALUE='Upload File(s)' name='sUpload' onclick='Submit();' class='toolsbarbg'>";
                        } print "</td>";
                    } print "</tr>";
                } print "</table>";
            } print "</td>";
        } print "</tr>";
    } print "</table>";

    #print join("", (map { $_ . "=" . $ENV{$_} ."<br>\n"; } keys(%ENV)));


    print "</form>";

    print "
      <script language='javascript'>
        function Submit()
        {
           window.parent.frames.prbar.location = \"$main::INET_CGI/wwwupld_mes.pl?UID=$param::UID&FS=$param::FS&UNI=$param::UnicID\";
           setTimeout(\"document.upload.submit();\", 1000);
        }
      </script>
    ";

    if ($CloseWindow) {
        print "
          <script language='javascript'>
            window.parent.frames.prbar.location = \"$main::INET_CGI/wwwupld_mes.pl?UID=$param::UID&FS=$param::FS\";
            window.parent.Reloadd('" . join(", ", @param::UploadFS) . "');
          </script>
        ";
    }

    print "
      </body>
      </html>
    ";

}


#############################################################################

sub GetPasteFileName($FS, $name_org, $owner)
{
    my($FS, $name_org, $owner) = @_;
    $name_org =~ /^([^\.]*)(.*)$/i;
    my($filename) = $1; $filename =~ s/'/''/g;
    my($fileext)  = $2; $fileext  =~ s/'/''/g;
    my($r) = DBExec("SELECT pastename('$filename', '$fileext', $owner, $FS) as newname");
    return $r->Value("newname");
}

sub GetPasteFileName1($$$)
{
    my($FS, $name_org, $owner) = @_;
    my($templ) = "Upload(( \([0-9]+\))*) of (.+)";

    my($i) = 0;
    my($name) = $name_org;

    if ($name =~ $templ) {
      $name = $3;
    }

    my($r_fs) = DBExec("select * from fs where ftype = 'f' and name = '$name' and up = $FS and owner=$owner");
    while ($r_fs->NumRows() != 0) {
      $i++;
      $name = $name_org;

      if ($name =~ $templ) {
        $name = $3;
      }

      $name = "Upload " . ($i == 1 ? "" : "($i) ") . "of $name";
      $r_fs = DBExec("select * from fs where ftype = 'f' and name = '$name' and up = $FS and owner=$owner");
    }

    return $name;
}

#############################################################################

sub ParseHTTPBody()
{
    my($rr, $buf, $flag);
    @parse::Vars = ();

    $main::Boundary    = "";
    $main::ContentType = "";

    #------------------------------------------------

    if (defined($ENV{'CONTENT_LENGTH'})) {
        $param::ContetnLength = $ENV{'CONTENT_LENGTH'};
    }

    if ($param::ContetnLength <= 0) {
        MessErrorLength();
        EraseTmpFiles();
        return 0;
    }

    #$URL = $ENV{'QUERY_STRING'};


    if( $ENV{'CONTENT_TYPE'} =~ /multipart\/form-data; boundary=(.+)$/ ) {
        $main::Boundary = $1;
    } else {
        MessErrorBoundary();
        EraseTmpFiles();
        return 0;
    }

    #------------------------------------------------

    $Read::TotalRead = 0;
    $Read::LastError = 0;
    $main::FieldCount = 0;

    $buf = "\r\n";

    while (1) {

        #------------------------------------------------
        # Searching of Boundary in begin section

        $flag = ($buf =~ /\r\n--$main::Boundary/), $rr = $';
        while( !$flag ) {
            if (length($buf) > length($main::Boundary) + 8) {
                $buf = substr($buf, -(length($main::Boundary) + 8))
            }


            if (Read()) {
                MessErrorMIMEStruct(1);
                EraseTmpFiles();
                return 0;
            }
            $buf .= $Read::Buffer;
            $flag = ($buf =~ /\r\n--$main::Boundary/), $rr = $';
        }

        #------------------------------------------------
        # $buf soderjit ostatik bufera posle otkusywanija BOUNDARY
        # Razbor zagolowka sekzii

        $buf = $rr;

        #------------------------------------------------
        # Proverka na zakrywaushii BOUNDARY
        # Pri poslednem chtenii zakluchitelnye simwoly mogli ne popast'

        if (length($buf) < 4) {
            if (Read()) {
                MessErrorMIMEStruct(2);
                EraseTmpFiles();
                return 0;
            }
            $buf .= $Read::Buffer;
        }

        if (substr($buf, 0, 4) eq "--\r\n") {
            last;
        }


        #------------------------------------------------
        # Proverka nalichija w bufere wsego zagolowka - t.e. 2-ch
        # perewodow ctroki podrjad. Proverku na razmer ne proizwodim,
        # potomuchto razmer bloka chtenija, gorazdo prevoschodit rasmer zagolowka

        if (!($buf =~ /\r\n\r\n|\n\n/)) {
            if (Read()) {
                MessErrorMIMEStruct(3);
                EraseTmpFiles();
                return 0;
            }
            $buf .= $Read::Buffer;
        }

        #------------------------------------------------
        # Naidena novaja sekzija - razbor zagolowka

        $buf =~ /\r\n\r\n|\n\n/;  # Poisk konza zagolowka
        $buf = $';           # Wse chto posle - ostal'noi buffer
        $rr  = $`;           # Wse chto ran'she zagolovok

        my($FieldName) = "";
        my($FileName)  = "";
        my($ContType)  = "";

        if ($rr =~ /Content-Disposition:[ ]*form-data;[ ]*name[ ]*=[ ]*([^;\r\n]*)/i) {
            $FieldName = $1;
            $FieldName = $FieldName =~ /"([^\"]*)"/ ? $1 : $FieldName;
        } else {
            MessErrorMIMEStruct(4);
            EraseTmpFiles();
            return 0;
        }

        if ($rr =~ /filename[ ]*=[ ]*([^;\r\n]*)/i) {
            $FileName = $1;
            $FileName = $FileName =~ /"([^\"]*)"/ ? $1 : $FileName;
            $FileName = $FileName =~ /([^\\\/]*)$/ ? $1 : $FileName;
        }

        if ($rr =~ /Content-Type[ ]*:[ ]*([^;\r\n]*)/i) {
            $ContType = $1;
            $ContType = $ContType =~ /"([^\"]*)"/ ? $1 : $ContType;
        }

        PLog "Parse HTTP BODY FieldName '$FieldName' FileName '$FileName' ContType '$ContType'";

        if (length($FileName) > 150) {
            MessErrorFileNameSize($FileName);
            EraseTmpFiles();
            return 0;
        }

        $main::FieldCount ++;

        $parse::Vars[$main::FieldCount - 1]{Name}          = $FieldName;
        $parse::Vars[$main::FieldCount - 1]{File_Name}     = $FileName;
        $parse::Vars[$main::FieldCount - 1]{Content_Type}  = $ContType;

        my($AllocateSize);
        my($TMPFileNumber);
        if ($FileName ne "") {
            $AllocateSize = $main::AllocateStepSize;

            my($r_storage) = DBExec("SELECT sysnum from storages order by ( size - used ) desc LIMIT 1");
            my($StorageNumber) = $r_storage->Value("sysnum");
            PLog "Selected storage number '$StorageNumber'";

            my($r_file) = DBExec("SELECT nextval('file_seq'::text) as sysnum");
            my($TMPFileNumber) = $r_file->Value("sysnum");
            $parse::Vars[$main::FieldCount - 1]{Tmp_File_Number} = $TMPFileNumber;
            PLog "Allocated TMP file number $TMPFileNumber in size $AllocateSize";
            DBExec("insert into file (sysnum, fsize, numstorage) VALUES (${TMPFileNumber}, ${AllocateSize}, ${StorageNumber})");

            $parse::Vars[$main::FieldCount - 1]{Tmp_File_Name} = "${main::PROGRAM_FILES}/storage${StorageNumber}/${TMPFileNumber}";

            #------------------------------------------------

            if (!(open F, ">${parse::Vars[$main::FieldCount - 1]{Tmp_File_Name}}")) {
                MessErrorInternalError("1.</u>&nbsp;Error with open tmp file '" . ${parse::Vars[$main::FieldCount - 1]{Tmp_File_Name}} . "' Mes:&nbsp;<u>$!.");
                EraseTmpFiles();
                return 0;
            }
            PLog "open tmp file ${parse::Vars[$main::FieldCount - 1]{Tmp_File_Name}}";
            binmode(F);
        }

        my($WriteSize)    = 0;

        $flag = ($buf =~ /\r\n--$main::Boundary/), $rr = $`;
        while(!$flag) {

            if (length($buf) > length($main::Boundary) + 8) {
                if ($FileName ne "") {
                    $WriteSize += syswrite(F, $buf, length($buf) - (length($main::Boundary) + 8));

                    if ($WriteSize > $AllocateSize) {
                        $AllocateSize += $main::AllocateStepSize;
						my($TMPFileNumber) = $parse::Vars[$main::FieldCount - 1]{Tmp_File_Number};
                        DBExec("UPDATE file SET fsize = $AllocateSize WHERE sysnum = $TMPFileNumber");
                        PLog "Reallocated TMP file number $TMPFileNumber in size $AllocateSize";
                    }
                }

                $buf = substr($buf, -(length($main::Boundary) + 8))
            }

            if (Read()) {
                MessErrorMIMEStruct(5);
                close F;
                EraseTmpFiles();
                return 0;
            }
            $buf .= $Read::Buffer;
            $flag = ($buf =~ /\r\n--$main::Boundary/), $rr = $`;
        }

        if ($FileName ne "") {
            $WriteSize += syswrite(F, $rr, length($rr));

	  		my($TMPFileNumber) = $parse::Vars[$main::FieldCount - 1]{Tmp_File_Number};
            DBExec("UPDATE file SET fsize = $WriteSize WHERE sysnum = $TMPFileNumber");
            PLog "Finished Reallocated TMP file number $TMPFileNumber in size $WriteSize";
        }
    }

#    PLog ">$res<>$Read::LastError<";

#    if ($Read::LastError) {
#      return 0;
#    }

    return 1;
}


#############################################################################

sub Read()
{
    if ($Read::LastError) {
        return $Read::LastError;
    }

    my($d, $res);

    if ($Read::TotalRead >= $param::ContetnLength) {
        $Read::LastError = 2;
        return $Read::LastError;
    }

    $d = (($param::ContetnLength - $Read::TotalRead) < $main::BufferSize ? ($param::ContetnLength - $Read::TotalRead) : $main::BufferSize);
    $res = read(STDIN, $Read::Buffer, $d);
    if (!defined($res)) {
        $Read::LastError = 1;
        return $Read::LastError;
    }

    #open(READLOG, ">>/tmp/wwwupld_$$");
    #syswrite(READLOG, $Read::Buffer, length($Read::Buffer));
    #close(READLOG);

    #PLog "$Read::TotalRead $res $param::ContetnLength ", ($param::ContetnLength - $Read::TotalRead);

    if ($res != 0) {
        $Read::TotalRead += $res;

        # PLog "=$param::StatusFileName=$Read::TotalRead $param::ContetnLength=";

        if ($param::StatusFileName ne "") {
            if(open (Status, ">" . $param::StatusFileName)) {
                my($dtimes) = time() - $param::StartTime;
                my($tread) = sprintf("<b>%15.2f</b>", $Read::TotalRead      / 1024);
                my($cleng) = sprintf("<b>%15.2f</b>", $param::ContetnLength / 1024);

                my($prc) = 0;
                $prc = $Read::TotalRead / $param::ContetnLength * 100 if ($param::ContetnLength != 0);
                $prc = sprintf("<b>%8.1f</b>", $prc);

                my($speed) = 0;
                $speed = $Read::TotalRead / $dtimes / 1024 if ($dtimes != 0);
                $speed = sprintf("<b>%8.2f</b>", $speed);

                print Status "<table border='0'><tr><td class='title' align='center'>";
                print Status "$tread kbytes uploaded from $cleng kbytes.";
                print Status "</td></tr><tr><td class='title' align='center'>";
                print Status "$prc% complete.&nbsp;&nbsp;$speed KBytes/Sec.";
                print Status "</td></tr></table>\n";

                close Status;
            }  else {
                PLog "$param::StatusFileName not opened";
            }
        }
    }

    return $Read::LastError;
}


sub ReadAll()
{
    return if ($param::ContetnLength <= 0);

    while (!$Read::LastError) {
        Read();
    }
}


#############################################################################

sub MessErrorDBConnect()
{
    ReadAll();
    MakeErrorHTML("<h>Connect to DB failed !!!</h>");
    PLog "Idenfification failed";
}

sub MessErrorLogin($)
{
    my($ErrNo) = @_;
    ReadAll();
    MakeErrorHTML("<h>Idenfification failed !!! Err message:<br> $ErrNo.</h>");
    PLog "Idenfification failed";
}

sub MessErrorInvalidMethod()
{
    ReadAll();
    MakeErrorHTML("<h>Error method</h>");
}

sub MessErrorLength()
{
    MakeErrorHTML("<h>Error Length</h>");
    PLog "Error Length";
}

sub MessErrorFileNameSize($)
{
    my($FileName) = @_;
    ReadAll();
    MakeErrorHTML("<h>Too Log File Name $FileName</h>");
    PLog "Too Log File Name $FileName";
}

sub MessErrorContentType()
{
    my($pr);
    ReadAll;
    $pr = $main::ContentType;
    MakeErrorHTML("<h>Error Content-Type &gt;$pr&lt;</h>");
    PLog "Error Content-Type $pr";
}

sub MessErrorBoundary()
{
    ReadAll;
    MakeErrorHTML("<h>No Math Boundary</h>");
    PLog "No Math Boundary";
}

sub MessErrorMIMEStruct($)
{
    ReadAll;
    my($ErrNo) = @_;
    #if ($Read::LastError != 1) {
        MakeErrorHTML("<h>Error MIME Structure: $ErrNo</h>");
    #}
    PLog "Error MIME Structure: $ErrNo $Read::LastError";
}

sub MessErrorInternalError($)
{
    my($ErrNo) = @_;
    ReadAll;
    MakeErrorHTML("<h>Internal Error No <u>$ErrNo</u></h>");
    PLog "InternalError N $ErrNo";
}

sub MessErrorOtherError($)
{
    my($ErrNo) = @_;
    ReadAll;
    MakeErrorHTML("<h>Error <u>$ErrNo</u></h>");
    PLog "InternalError $ErrNo";
}

sub MakeErrorHTML($)
{
    local *STYLE;
    my($Mes) = @_;

    SetTempl("wwwupld", $param::FACE);
    #print join(" ", keys(%main::TEMPL)), "<br>";

    print STDOUT "Cache-Control: no-cache, no-store\r\n";
    print STDOUT "Content-Type: Text/HTML\r\n\r\n";
    print STDOUT "<html>"; {
        print "<head>"; {
            if(open(STYLE, "$main::PROGRAM_SRC/standard.css")) {
                print(join("", <STYLE>));
                close(STYLE);
            }
        } print "</head>";

        print STDOUT "<body class='body'>"; {
            print STDOUT "$Mes";
            print STDOUT "<hr>", $main::TEMPL{err_subscript};
        } print STDOUT "</body>";
    } print STDOUT "</html>";

}


sub GetUnicID
{
    my($s) = "";

    map {$s .= $_ . "=".  $ENV{$_} . "<br>"; } keys %ENV;
    $s = localtime() . " $param::UID $param::FS $$";
    $s =~ s/[ :]/_/g;
    $s = tools::md5($s);
    #$s =~ s/[ ]/R/g;
    $s .= "_$$";


    return $s;
}


sub EraseTmpFiles()
{
    if ($param::StatusFileName ne "" && -e $param::StatusFileName) {
        PLog "Deleting ", $param::StatusFileName;
        unlink $param::StatusFileName;
    }

    if(defined(@parse::Vars)) {
        PLog "Count of parse::Vars" . ($#parse::Vars);

        for (my($i) = 0; $i <= $#parse::Vars; $i++) {
            my($tmp) = $parse::Vars[$i];
            PLog "tmp file name" . ($tmp->{Tmp_File_Name});

            if ( $tmp->{Tmp_File_Number} ne "" ) {
                PLog "Unallocate TMP Number " . $tmp->{Tmp_File_Number};
                DBExec("DELETE FROM file WHERE sysnum = " . $tmp->{Tmp_File_Number});
            }

            if ( $tmp->{Tmp_File_Name} ne "" ) {
                if ( -e $tmp->{Tmp_File_Name} ) {
                    PLog "Deleting " . $tmp->{Tmp_File_Name};
                    unlink($tmp->{Tmp_File_Name});
                }
            }
        }
    }
}


sub PLog
{
    local *LOG;
    my($mes) = join("", @_);
    my($UID) = 0;


    if(defined($param::UID)) {
        $UID = $param::UID;
    } else {
        if (defined($ENV{QUERY_STRING}) && $ENV{QUERY_STRING} =~ /UID=([^&]*)/i) {
            $UID = $1;
        } else {
            $UID = '';
        }
    }

    my($out) = GetCurrDate() . "> id: '$UID' script '$ENV{SCRIPT_NAME} $$' ip: '$ENV{REMOTE_ADDR}' method: '$ENV{REQUEST_METHOD}' mes: '$mes'";

    if (!open(LOG, ">>$main::WEB_LogFileName")) {
        return;
    }
    print LOG "$out\n";
    close(LOG)
}

#BEGIN {
#    PLog "Script Started";
#    return 1;
#}

#END {
#    PLog "Script Ended";
#    return 1;
#}

#############################################################################

local $main::IntrHandle = sub
                          {
                             PLog "Interrupted by ", $_[0];

                             PLog "Erase Tmp Files";
                             EraseTmpFiles();

                             PLog "Canceling job with exit error 1";
                             exit 1;
                          };
$SIG{'INT'}  = $main::IntrHandle;
$SIG{'QUIT'} = $main::IntrHandle;
$SIG{'KILL'} = $main::IntrHandle;
$SIG{'TERM'} = $main::IntrHandle;
$SIG{'HUP'}  = $main::IntrHandle;

#open STDERR, ">>" . "${main::WEB_LogFileName}_cgi";
select(STDIN); $| = 1; select(STDERR); $| = 1; select(STDOUT); $| = 1;
main();

