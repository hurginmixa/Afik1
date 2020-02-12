<?php
    require("db.inc.php");
    require("utils.inc.php");
    require("view_file.inc.php");

    include("_config.inc.php");

    if (!isset($HASH) || $HASH == "") {
        header("Location: $INET_SRC/remote_access.php?$QUERY_STRING");
        exit;
    }


    if (!eregi("^[0-9a-z]+$", $HASH)) {
        echo "Invalid HASH " . htmlspecialchars($HASH);
        exit;
    }

    ConnectToDB();

    $db = DBExec("SELECT acc.username, fs.sysnum, fs.name, fs.owner, fs.up, fs.sysnumfile FROM acc, fs WHERE acc.sysnumfs = fs.sysnum AND acc.hash = '$HASH'");
    if ($db->NumRows() != 1) {
        header("Location: $INET_SRC/remote_access.php?$QUERY_STRING");
        exit;
    }

    if ($db->sysnumfile() == 0) {
        header("Location: $INET_SRC/remote_access.php?UID=" . URLEncode($db->username()) . "&Key=" . URLEncode(AuthorizeKey($db->username())) . "&FACE=en" . "&Fri=" . URLEncode($db->owner()) . "&FS=" . URLEncode($db->sysnum()));
    } else {
        //header("Location: $INET_SRC/remote_access.php/" . URLEncode($db->name()) . "?UID=" . URLEncode($db->username()) . "&Key=" . URLEncode(AuthorizeKey($db->username())) . "&FS=" . 0 . "&FACE=en&sDownload=2" . "&Fri=" . URLEncode($db->owner()) . "&" .  URLEncode("TagFile[]") . "=" . $db->sysnum());

        $GLOBALS[UID]        = $db->username();
        $GLOBALS[Key]        = AuthorizeKey($db->username());
        $GLOBALS[TagFile]    = $db->sysnum();
        $GLOBALS[sDownload]  = 1;

        $ViewFile = new CViewFile();
        $ViewFile->run();
    }

    UnconnectFromDB();
    exit;
?>

