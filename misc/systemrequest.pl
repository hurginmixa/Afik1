#!/usr/bin/perl -w

use strict 'vars';

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

    #print STDERR join("\n", @INC), "\n";
}

use POSIX;
use POSIX qw(setsid);

use IO::File;
use IO::Socket;
use IO::Select;

use ftp_const;
use tools;
use db_pg;
use db_pg_res;
use sendmail;
use ftp_util;
use ftp_cm_user;
use ftp_cm_port;
use ftp_cm_pasv;
use ftp_cm_list;
use ftp_cm_cwd;
use ftp_cm_retr;
use ftp_cm_stor;
use ftp_cm_dele;
use ftp_cm_rmd;
use ftp_cm_mkd;
use ftp_cm_size;
use ftp_cm_rename;
use ftp_cm_copy;
use ftp_cm_rest;
use ftp_cm_site;
use ftp_cm_help;

use ftp_class_root;
use ftp_class_fs;

use readconf; # read configurations file and fille fiels

local $session::CommandSock;
local $session::SelectCommandSock;
local $session::CommandStream;
local $session::SelectCommandStream;
local $session::TransferSock;
local $session::TransferStream;
local $session::CloseTransferStream;
local @session::ClientAddress;
local $session::RestartPoint;
local $session::User;
local $session::Priv;
local $session::UID;
local $session::Login;   # 1 - if user loded
local $session::WDNode;
local $session::FS;
local $session::Prot;
local $session::LUser;
local @session::LUserInfo;
local $session::TransferMode; # undef - not set, passv - passive mode, Other - active mode containt remote address
local %session::Notification;


local $main::DebugLevel = 0;

local $main::SystemUserName = "apache";
#local ($main::SystemUserLogin, $main::SystemUserPass);
local ($main::SystemUserUid, $main::SystemUserGid) = getpwnam($main::SystemUserName);


local $main::MainIntrHandle = sub() {
    my($SIG) = @_;

    PLog("ftp : $SIG signal receving, send to Group : ${main::CountinueJob}");

    if ($main::CountinueJob) {

        $main::CountinueJob = 0;

        if ($main::DebugLevel) {
            &{$main::ChldIntrHandle}($SIG);
        } else {
            $SIG{$SIG} = "IGNORE";
            kill ($SIG, -$$);
        }
    }

    #PLog("ftp : Main tread stoped");
    #exit(0);
};


local $main::ChldIntrHandle = sub() {
    my($SIG) = @_;

    PLog("ftp : $SIG signal receving, terminate");

    $session::CountinueJob = 0;
    $Hendler2HendlerCopy::StopFlag = 1;

    $SIG{$SIG} = "IGNORE";
    #exit(0);
};


sub Job()
{
    PLog("ftp : open data base : dbname=${main::DBASE} user=${main::POSTGRES_USER}");
    if(!ConnectDB("dbname=${main::DBASE} user=${main::POSTGRES_USER}")) {
        PLog("Error : Not Connect to DBase");
        return;
    }

    $session::Login               = 0;
    $session::CountinueJob        = 1;
    $session::User                = undef;
    $session::RestartPoint        = 0;
    $session::Priv                = -1;
    $session::UID                 = undef;
    $session::WDNode              = undef;
    $session::FS                  = 0;
    $session::Prot                = "I";
    $session::LUser               = undef;
    @session::LUserInfo           = undef;
    $session::TransferMode        = "undef";  # undef - not set, passv - passive mode, Other - active mode containt remote address
    $session::CloseTransferStream = 1;


    Send2Client(220, "Afik1 System FTP Server (Version ${main::VERSION}) ready");

    $session::SelectCommandStream = new IO::Select( $session::CommandStream );

    while (1) {
        if (!$session::CountinueJob) {
            PLog("ftp : Connection aborted. flag session::CountinueJob cleared.");
            last;
        }

        PLog("ftp : read command from client");
        $a = ReadClientText();

        if (!defined($a)) {
            PLog("ftp : Connection aborted. Session cloused.");
            last;
        }

        # Login
        if ($a =~ /^USER[ ]*/i) {
            cm_user($');
            next;
        }

        if ($a =~ /^NOOP[ ]*/i) {
            Send2Client(200, "Command NOOP");
            next;
        }

        if ($a =~ /^QUIT[ ]*/i) {
            CloseTransferConnection();
            Send2Client(230, "Bye");
            last;
        }

        #Firewolle
        if (!$session::Login) {
            Send2Client(530, "Please login with USER and PASS");
            next;
        }

        if ($a =~ /^PWD[ ]*/i) {
            my($p) = $session::WDNode->{PATH};
            Send2Client(257, "\"/" . ($p ne "" ? $p . "/" : "") . "\" is current directory.");
            next;
        }

        if ($a =~ /^MKD[ ]+/i) {
            cm_mkd($');
            next;
        }

        if ($a =~ /^TYPE[ ]+/i) {
            my($type) = $';
            if ($type =~ /^A$/i) {
                $session::Prot = "A";
                Send2Client(200, "Set TYPE A");
                next;
            }

            if ($type =~ /^I$/i) {
                $session::Prot = "I";
                Send2Client(200, "Set TYPE I");
                next;
            }

            Send2Client(504, "command 'Set TYPE $type' not implemented");
            next;
        }

        if ($a =~ /^SYST[ ]*/i) {
          Send2Client(215, "UNIX Type: L8");
          next;
        }

        if ($a =~ /^LIST[ ]*/i || $a =~ /^NLST[ ]*/i) {
          cm_list($', $a =~ /^NLST[ ]*/i);
          next;
        }

        if ($a =~ /^RETR[ ]+/i) {
          cm_retr($');
          next;
        }

        if ($a =~ /^STOR[ ]+/i) {
          cm_stor($', 0);
          next;
        }

        if ($a =~ /^APPE[ ]+/i) {
          cm_stor($', 1);
          next;
        }

        if ($a =~ /^CDUP[ ]*/i) {
          cm_cwd("..");
          next;
        }

        if ($a =~ /^CWD[ ]+/i) {
          cm_cwd($');
          next;
        }

        if ($a =~ /^DELE[ ]+/i) {
          cm_dele($');
          next;
        }

        if ($a =~ /^RMD[ ]+/i) {
          cm_rmd($');
          next;
        }

        if ($a =~ /^PASV[ ]*$/i) {
          cm_pasv($');
          next;
        }

        if ($a =~ /^PORT[ ]*/i) {
          cm_port($');
          next;
        }

        if ($a =~ /^RNFR[ ]*/i) {
          cm_rename($');
          next;
        }

        if ($a =~ /^RNTO[ ]*/i) {
          Send2Client(500, "RNTO without RNFR");
          next;
        }

        if ($a =~ /^CPFR[ ]*/i) {
          cm_copy($');
          next;
        }

        if ($a =~ /^CPTO[ ]*/i) {
          Send2Client(500, "CPTO without CPFR");
          next;
        }


        if ($a =~ /^REST[ ]+/i) {
          cm_rest($');
          next;
        }


        if ($a =~ /^SIZE[ ]*/i) {
          cm_size($');
          next;
        }

        if ($a =~ /^SITE[ ]*/i) {
          cm_site($');
          next;
        }

        if ($a =~ /^HELP[ ]*/i) {
          cm_help($');
          next;
        }

        Send2Client(502, "Command not implemented");
    }

    # sending access notifications
    map {
        my($Owner) = $_;
        my($r_fs) = DBExec("SELECT usr.name || '\@' || domain.name as usrname FROM usr, domain WHERE usr.sysnumdomain = domain .sysnum AND usr.sysnum = '$Owner'");
        if ($r_fs->NumRows() == 1) {
            my($d) = `date -R`;
            my($To) = $r_fs->Value("usrname");
            my($From) = $To; $From =~ s/^[^@]+/System_messager/;
            my($Message) = "User $session::User at $d\n" . $session::Notification{$Owner};

            PLog("ftp : Send notification from '$From' to '$To'");

            if(!sendmail::sendmail({
                                "SMTPServ" => "localhost",
                                "Domain"   => "localhost",
                                "From"     => $From,
                                "To"       => $To,
                                "Message"  => $Message,
                                "Subject"  => "Ftp Access to file(s) $session::User",
                                "Header"   => "Content-Type: text/plain\r\nFrom: $From\r\nX-Afik1-Access-Notification: on" })) {
                PLog "SendMail Error : ", $sendmail::Mes;
            }
        }
    } keys %session::Notification;

    undef %session::Notification;
}


sub main()
{
    my($pid);

    PLog("ftp : Open Command Socket");
    if (!($session::CommandSock = IO::Socket::INET->new(Type => SOCK_STREAM, Proto => "tcp", Listen => 50, LocalPort => "${main::FTP_PORT}", ReuseAddr, MultiHomed))) {
        return "Error : $! - Socket not created";
    }

    PLog("ftp : Set to Command Socket KEEPALIVE");
    if (!($session::CommandSock->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1))) {
        return "Error session::CommandSock->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1): $!";
    }

    PLog("ftp : Set to Command Socket TOS");
    if (!($session::CommandSock->setsockopt(IPPROTO_IP, IP_TOS, 16))) {
       return "Error session::CommandSock->setsockopt(IPPROTO_IP, IP_TOS, 16): $!";
    }

    if (!$main::DebugLevel) {
        PLog("ftp : Daemonize");
        daemonize();
    }

    PLog("ftp : Save daemon's proc id numer in file '${main::FTP_ProcNumer}'");
    open (PNLoc, ">$main::FTP_ProcNumer");
    print PNLoc "$$\n";
    close (PNLoc);

    $main::CountinueJob = 1;

    PLog("ftp : Create Sclect for session::CommandSock");
    $session::SelectCommandSock = new IO::Select( $session::CommandSock );

    while (1) {
        if(!$main::CountinueJob) {
            PLog("ftp : Job canceled. flag main::CountinueJob cleared.");
            last;
        }

        PLog("ftp : listening again");

        my(@AcceptList);
        if (@AcceptList = $session::SelectCommandSock->can_read()) {

            $session::CommandStream = $session::CommandSock->accept();
            @session::ClientAddress = UnPackAddr($session::CommandStream->peername());

            PLog("ftp : Connection accepted.");
            PLog("ftp : From : '$session::ClientAddress[0]:$session::ClientAddress[1]'");

            $session::CommandStream->autoflush();

            if (!($session::CommandStream->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1))) {
                PLog("Error session::CommandStream->setsockopt(SOL_SOCKET, SO_KEEPALIVE, 1): $!");
                undef $session::CommandStream;
                next;
            }

            if (!($session::CommandStream->setsockopt(IPPROTO_IP, IP_TOS, 16))) {
                PLog("Error session::CommandStream->setsockopt(IPPROTO_IP, IP_TOS, 16): $!");
                undef $session::CommandStream;
                next;
            }

            if (!$main::DebugLevel) {
                $pid = fork();
                if ( $pid == 0 ) {
                    PLog("ftp : Subprocess started");

                    $SIG{CHLD}   = "DEFAULT";
                    $SIG{'INT'}  = $main::ChldIntrHandle;
                    $SIG{'QUIT'} = $main::ChldIntrHandle;
                    $SIG{'TERM'} = $main::ChldIntrHandle;
                    $SIG{'HUP'}  = $main::ChldIntrHandle;

                    Job();
                    undef $session::CommandStream;

                    PLog("ftp : Subprocess stoped");
                    exit(0);
                }
            } else {
                Job();
            }

            undef $session::CommandStream;
        } else {
            PLog("ftp : can_read failure reason: '$!'");
        }
    }

    undef $session::CommandSock;
    undef $session::CommandStream;
    undef $session::TransferSock;
    undef $session::TransferStream;

    return undef;
}


sub daemonize
{
    chdir '/'                  or (PLog("ftp : daemonize : Can't chdir to /: $!") && exit(1));
    open STDIN, "/dev/null"    or (PLog("ftp : daemonize : Can't read /dev/null: $!") && exit(1));
    open STDOUT, ">${main::FTP_LogFileName}_out"  or (PLog("ftp : daemonize : Can't write to ${main::FTP_LogFileName}_out: $!") && exit(1));
    defined(my $pid = fork)    or (PLog("ftp : daemonize : Can't fork: $!") && exit(1));
    exit(0) if $pid;
    setsid()                   or (PLog("ftp : daemonize : Can't start a new session: $!") && exit(1));
    open STDERR, '>&STDOUT'    or (PLog("ftp : daemonize : Can't dup stdout: $!") && exit(1));
}


if ($< != 0) {
    print STDERR "Error: Must be executed only by root.\n";
    exit(1);
}

PLog("");
PLog("ftp : Started");

PLog("ftp : Set handles on singals");
$SIG{CHLD}   = "IGNORE";
$SIG{'INT'}  = $main::MainIntrHandle;
$SIG{'QUIT'} = $main::MainIntrHandle;
$SIG{'TERM'} = $main::MainIntrHandle;
$SIG{'HUP'}  = $main::MainIntrHandle;

PLog("ftp : Main Rout");
$main::ret_main = main();

if (defined($main::ret_main)) {
    PLog("ftp : daemon not initialized : $main::ret_main");
    exit(1);
}


PLog("ftp : Main tread finished");
exit(0);
