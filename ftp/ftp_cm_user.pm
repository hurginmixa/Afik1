use strict 'vars';


sub cm_user($)
{
    my($user, $pas) = @_;
    my($domain, $res, $res_ua);

    #$session::Login = 0;
    #$session::User = "";

    if ($user eq "") {
        Send2Client(501, "Empty user name");
        return 0;
    }

    Send2Client(331, "Password required for $user");

    $pas = ReadClientText();

    if (!($pas =~ /PASS[ ]*/i)) {
        Send2Client(500, "Login Failed. No PASS presend");
        return 0;
    }
    $pas = $';
    PLog("ftp : geted password : '$pas'");

    $user =~ s/\$/\@/;
    PLog("ftp : name : $user");
    if ($user =~ /\@/) {
        $user = $`;
        $domain = $';

        PLog($user . '<>' . $domain. '<>'. CalcPasswHesh($user . "\@" . $domain). '<>'. $pas);
        $res = DBExec("SELECT usr.sysnum, usr.name, usr.password, usr.lev, domain.name AS domain FROM domain, usr WHERE usr.name = '$user' AND domain.name = '$domain' AND domain.sysnum = usr.sysnumdomain");

        if ($res->NumRows() == 0) {
            if (CalcPasswHesh($user . "\@" . $domain) ne $pas) {
                Send2Client(500, "Login Failed. User not find or password incorrect.");
                return 0;
            }

            $session::Login = 1;
            $session::Priv = -1;
            $session::User = $user . "\@" . $domain;
            $session::UID = 0;
            Send2Client(230, "Guest '$session::User' Logged In.");
            $session::WDNode = GetRootFS();
            return 1;
        }
    } else {
        $res = DBExec("select usr.sysnum, usr.name, usr.password, usr.lev, domain.name as domain from domain, usr where usr.name = '$user' and domain.sysnum = usr.sysnumdomain");
    }

    if ($res->NumRows() == 0) {
        Send2Client(500, "Login Failed. User not find or password incorrect.");
        return 0;
    }

    if ($res->NumRows() > 1) {
        Send2Client(500, "Login Failed. Too many users. Domains name need.");
        return 0;
    }
    $domain = $res->Value("domain");

    if ($res->Value("password") ne $pas && CalcPasswHesh($user . "\@" . $domain) ne $pas ) {
        Send2Client(500, "Login Failed. User not find or password incorrect.");
        return 0;
    }

    $session::User = $user . "\@" . $domain;
    $session::UID = $res->Value("sysnum");

    $res_ua = DBExec("select * from usr_ua where sysnumusr = '" . $session::UID . "' and name = 'edenaid'");
    if ($res_ua->NumRows() != 0 && $res_ua->Value("value") ne "") {
        Send2Client(500, "Login Failed. Access for user denied.");
        return 0;
    }

    $session::Login = 1;
    $session::Priv = $res->Value("lev");
    $session::RestartPoint = 0;

    if($session::Priv == 2) {
        $session::LUser = "root";
    }

    $res_ua = DBExec("select * from usr_ua where sysnumusr = '" . $session::UID . "' and name = 'luser'");
    if ($res_ua->NumRows() == 1 && $res_ua->Value("value") ne "") {
        $session::LUser = $res_ua->Value("value");
    }

    if(defined($session::LUser)) {
        @session::LUserInfo = getpwnam($session::LUser);
        PLog("ftp : user map on " . $session::LUser . " " . $session::LUserInfo[7]);
    } else {
        PLog("ftp : user unmap");
    }


    DBExec("UPDATE usr SET lastenter = 'now' WHERE sysnum = " . $session::UID);

    $session::WDNode = GetRootFS();
    Send2Client(230, "User '$session::User' Logged In");
    return 1;
}

1
