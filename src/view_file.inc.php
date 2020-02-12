<?php

include("_config.inc.php");
require("tools.inc.php");
require("file.inc.php");
require("db.inc.php");
require("view.inc.php");

class CViewFile extends view
{

    function Run()
    {
        global $DownloadZip;

        if (isset($DownloadZip)) {
            $this->DownLoadZip();
        } else {
            $this->DownloadFiles();
        }
    }


    function DownloadFiles()
    {
        global $UID, $TagFile, $PROGRAM_FILES, $sDownload, $REMOTE_ADDR, $REQUEST_METHOD, $HTTP_RANGE;
        global $_SERVER;

        $this->Log("REQUEST_METHOD='{$REQUEST_METHOD}' sDownload='{$sDownload}' HTTP_RANGE='{$HTTP_RANGE}'");

        if (!ereg("^[0-9]+$", $TagFile)) {
            $this->AuthorizeError("Access Failed  1!!!");
            return;
        }
        if (ereg("\;", $UID)) {
            $this->AuthorizeError("Access Failed  1.1!!!");
            return;
        }

        if (!ereg("^[0-9]+$", $UID) && ereg("^([^@]+)\@(.*)$", $UID, $MATH)) {
            $r_usr = DBFind("usr, domain",
                            "usr.sysnumdomain = domain.sysnum and usr.name = '$MATH[1]' and domain.name='$MATH[2]'",
                            "usr.sysnum");
            if ($r_usr->NumRows() == 1) {
                $UID = $r_usr->sysnum();
            }
        }

        $r_fs = DBFind("file, fs, usr, domain",
                       "fs.sysnum = '$TagFile' and fs.sysnumfile = file.sysnum and fs.owner = usr.sysnum and usr.sysnumdomain = domain.sysnum",
                       "fs.sysnum, fs.sysnumfile, fs.creat, file.fsize, file.ftype, file.numstorage, file.url, fs.owner, fs.name, gettree(fs.sysnum) as path, (usr.name || '@' || domain.name) as ownername", __LINE__);
        if ($r_fs->NumRows() != 1) {
            $this->AuthorizeError("File not found !!!");
            return;
        }

        if (ereg("^[0-9]+$", $UID) && ($r_fs->owner() == $UID)) {
            $SendMailFlag = "";
        } else {
            $r_acc = DBExec("select * from acc where sysnum = (select get_acc('$this->USRNAME', '$TagFile'))", "file: " . __FILE__ . " line: " . __LINE__);
            if ($r_acc->NumRows() == 0 || (DecodeAccessFlag($r_acc->access()) & 1) != 1) {
                $this->AuthorizeError("Access Failed 2 !!!");
                return;
            }

            $SendMailFlag = $r_acc->access_tracking();
        }

        if ($r_fs->url() != "") {
            $f = OpenRemoteFile($r_fs->url() . "&UID=$this->UID", $this->UID, &$errno, &$errstr);
        } else {
            $f = @fopen("$PROGRAM_FILES/storage" . $r_fs->numstorage() . "/" . $r_fs->sysnumfile(), "rb");
        }
        if ($f <= 0) {
            header("HTTP/1.0 404 Not Found");
            header("Content-Type: text/plain");
            echo "File not open !";
            return;
        }

        $size = $r_fs->fsize();
        $name = $r_fs->name();
        $time = MkSpecTime($r_fs->creat());
        $etag = sprintf("%x-%x-%x", $r_fs->sysnum(), $size, $time);

        if (isset($_SERVER["HTTP_RANGE"]) && preg_match("/bytes=(\d*)-(\d*)/", $_SERVER["HTTP_RANGE"], $MATH)) {
            $rangeBegin = (int)$MATH[1];
            $rangeEnd   = (int)$MATH[2];

            if ($rangeBegin > $size - 1) {
                if ($MATH[2] != "") {
                    header("HTTP/1.1 416 Requested Range Not Satisfiable");
                    $this->Log("Range Not Satisfiable 1: $rangeBegin - $rangeEnd");
                    return;
                }
                $rangeBegin = $size;
                $rangeEnd   = -1;
            }

            if ($rangeEnd != -1 && ($rangeEnd > $size - 1 || $MATH[2] == "")) {
                $rangeEnd = $size - 1;
            }

            if ($rangeEnd != -1 && $rangeBegin > $rangeEnd) {
                header("HTTP/1.1 416 Requested Range Not Satisfiable");
                $this->Log("Range Not Satisfiable 2: $rangeBegin - $rangeEnd");
                return;
            }
            header("HTTP/1.1 206 Partial Content");
            $isRanged = true;
        } else {
            $rangeBegin = 0;
            $rangeEnd   = $size - 1;
            header("HTTP/1.1 200 OK full range");
            $isRanged = false;
        }

        if ($sDownload == 1) {
            header("Content-Type: application/x-msdownload");
            //header("Content-Disposition: inline; filename=\"$name\"");
            header("Content-Disposition: attachment; filename=\"$name\"");
        } else {
            $Content = $r_fs->ftype();
            if ($Content == "") {
                $Content = "application/octet-stream";
            }
            header("Content-Type: $Content");
        }

        header("Last-Modified: " . gmdate( 'D, d M Y H:i:s T', $time));
        header("ETag: \"$etag\"");

        $this->Log("Range: $rangeBegin - $rangeEnd. isRanged='{$isRanged}'");
        fseek($f, $rangeBegin);

        header("Content-Length: " . ($rangeEnd != -1 ? ($rangeEnd - $rangeBegin + 1) : $rangeEnd));
        header("Accept-Ranges: bytes");
        if ($isRanged) {
            header("Content-Range: bytes $rangeBegin-$rangeEnd/$size");
        }

        if ($REQUEST_METHOD == "HEAD" || $rangeEnd == -1) {
            //echo "mixa";
            return;
        }


        $FSArr = array('ownername' => $r_fs->ownername(), 'owner' => $r_fs->owner(), 'path' => $r_fs->path(), 'fsize' => $rangeEnd - $rangeBegin + 1, 'sysnum' => $r_fs->sysnum());

        if ($FSArr['ownername'] != $this->USRNAME && $SendMailFlag == "1") {
                $message = "User $this->USRNAME at " . date("r") . " download file\n" . $FSArr['path'];
                $this->SendMessage(
                            $FSArr['ownername'],
                            "System_messager" . preg_replace("/^[^@]+/", "", $FSArr['ownername']),
                            $message,
                            "User download file(s) $this->USRNAME"
                       );
        }


        $DownloadSize = 0;
        $this->Log("View_File, 1 Start Download");

		set_time_limit(0);
        ignore_user_abort( true );
        //$this->Log("ignore_user_abort" . ignore_user_abort());

        $bufsize = 40 * 1024;
        while( !feof($f) && $DownloadSize < $rangeEnd - $rangeBegin + 1 && connection_status() == 0 ) {

            $readsize = $bufsize;
            if ($bufsize > $rangeEnd - $rangeBegin + 1 - $DownloadSize) {
                $this->Log("Last Read. DownloadSize : '{$DownloadSize}' Check buffsize : '" . ($rangeEnd - $rangeBegin + 1 - $DownloadSize) . "'");
                $readsize = $rangeEnd - $rangeBegin + 1 - $DownloadSize;
            }

            $buf = fread($f, $readsize);
            print ($buf);
			flush();
			if (connection_status() == 0) {
            	$DownloadSize += strlen( $buf );
				//sleep(1);
			}

			$this->Log("1 connection_status " . connection_status()  . " " . $DownloadSize . " from " . $FSArr[fsize]);
        }

        fclose($f);

        //$this->Log("2 connection_status " . connection_aborted()  . " " . $DownloadSize . " from " . $size);

        DBExec("insert into billing (sysnumusr, sysnumdomain, kind, date, traficsize, sysnumfs, who, direct, ip) values ('{$FSArr['owner']}', getdomain('{$FSArr['owner']}'), 'download', datetime('now'::abstime), '{$DownloadSize}', '{$FSArr['sysnum']}', '" . substr($this->USRNAME, 0, 20) . "', -1, '$REMOTE_ADDR')", "file : '" . __FILE__ . "', line : '" . __LINE__ . "'");

        $this->Log("View_File, 2. DownloadSize='{$DownloadSize}' from '{$FSArr[fsize]}'");

        UnconnectFromDB();
    }


    function DownLoadZip()
    {
        global $DownloadZip, $PROGRAM_TMP;

        UnconnectFromDB();
        $bufsize = 102400;


        $f = @fopen("$PROGRAM_TMP/$DownloadZip", "r");

        if ($f <= 0) {
            @unlink ("$PROGRAM_TMP/$DownloadZip");

            header("HTTP/1.0 404 Not Found");
            header("Content-Type: text/plain");
            echo "Zip File not opened !";
            return;
        }

        $size = filesize("$PROGRAM_TMP/$DownloadZip");

        //unlink ("$PROGRAM_TMP/$DownloadZip");

        header("Content-Type: application/octet-stream");
        header("Content-Length: $size");
        while( !feof($f) && !connection_aborted() ) {
          $buf = fread($f, $bufsize);

           echo $buf;
        }

        fclose($f);
    }


    function Authorize() // overlaped virtual's function
    {
        global $UID, $Key, $Time, $DownloadZip, $TagFile;

        if (isset($DownloadZip)) {
            return;
        }


        if ($Time != "") {
            if ($Key != TimedependAuthorizeHash($UID, $Time) || (time() - $Time) > (60 * 60)) {
                $this->AuthorizeError("File not accessed 1!");
                return;
            }
        } else {
            if ($Key != AuthorizeKey($UID)) {
                $this->AuthorizeError("File not accessed 2!");
                return;
            }
        }
    }


    function AuthorizeError($mes) // overlaped virtual's function
    {
        header("Content-Type: text/plain");
        echo $mes;
        exit;
    }


    function SetIdentificationCookies() // overlaped virtual's function
    {
    }


    function OpenSession() // overlaped virtual's function
    {
    }


    function SendMessage($To, $From, $Message, $Subj)
    {
        mail(
                $To,
                $Subj,
                $Message,
                "Content-Type : Text/PLAIN\r\nFrom: $From\r\nReply-To: $From\r\nX-Afik1-Access-Notification: on\r\n",
                "-f$From"
        );
    }
}



/*
HTTP request sent, awaiting response... HTTP/1.1 200 OK
Date: Sun, 13 Jul 2003 14:31:18 GMT
Server: Apache/2.0.40
Accept-Ranges: bytes
X-Powered-By: PHP/4.2.2
Content-Disposition: attachment; filename="cvsroot[1].tar.gz"
Content-Length: 1112958
Connection: close
Content-Type: application/octet-stream


Length: 1,112,958 [application/octet-stream]
*/

/*
HTTP request sent, awaiting response... HTTP/1.1 200 OK
Date: Sun, 13 Jul 2003 15:03:57 GMT
Server: Apache/2.0.40 (Red Hat Linux)
Accept-Ranges: bytes
X-Powered-By: PHP/4.2.2
Content-Disposition: attachment; filename="cvsroot.tar.gz"
Content-Length: 1112958
Connection: close
Content-Type: application/x-msdownload


Length: 1,112,958 [application/x-msdownload]
*/




?>
