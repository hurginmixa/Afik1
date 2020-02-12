use strict 'vars';

sub cm_help($)
{
    my($cont) = @_;
    my($all_help) =
             "214-The following commands are recognized (* =>'s unimplemented).\n" .
             "   USER    PORT    STOR    MSAM*   RNTO    NLST    MKD     CDUP\n" .
             "   PASS    PASV    APPE*   MRSQ*   ABOR*   SITE    XMKD*   XCUP*\n" .
             "   ACCT*   TYPE    MLFL*   MRCP*   DELE*   SYST    RMD     STOU*\n" .
             "   SMNT*   STRU*   MAIL*   ALLO*   CWD     STAT*   XRMD    SIZE\n" .
             "   REIN*   MODE*   MSND*   REST    XCWD*   HELP    PWD     MDTM*\n" .
             "   QUIT    RETR    MSOM*   RNFR    LIST    NOOP    XPWD*   COPY*\n" .
             "214 Direct comments to WanVision.\r\n";

    if ($cont eq "") {
       Send2Client(0, $all_help);
    } else {
       Send2Client(214, "Syntax: " . uc ($cont));
    }

    return 1;
}

1;
