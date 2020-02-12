package tools;

require 5.000;
require Exporter;

@ISA = qw(Exporter);
@EXPORT = qw(PackAddr UnPackAddr OpenSocket StripNL AsSize
            Hendler2HendlerCopy md5 md5sum
            tohex urlencode Decode urldecode GetCurrDate
            print_results disk_total_space disk_free_space disk_avail_space);

use strict 'vars';

use Socket;
use Digest::MD5;
use IO::Select;
use Filesys::Statvfs;

sub PackAddr($;$)
{
    my($NAME, $Domain) = @_;
    my($HName, $HPort);
    my($pat) = 'S n C4 x8';
    my($name, $aliases, $addrtype, $length, @addr);

    if (!defined($Domain)) {
      $Domain = AF_INET;
    }

    if ($NAME eq "") { return 0; }

    if ($NAME =~ /:/) {
	$HName = $`;
	$HPort = $';
    } else {
	$HName = $NAME;
	$HPort = 0;
    }

    if ($HName eq "") { $HName = "127.0.0.1"; }
    #if ($HPort == 0)  { $HPort = 21; }

    if (!(($name, $aliases, $addrtype, $length, @addr) = gethostbyname($HName))) {
      $tools::Mes = "error with name $HName : $!";
      return undef;
    };

    @addr = unpack("C4", $addr[0]);
    #print @addr, " ", $HPort, "\n";

    $tools::Mes = "";
    return pack($pat, $Domain, $HPort, $addr[0], $addr[1], $addr[2], $addr[3]);
}


sub UnPackAddr($)
{
    my($ADDR) = @_;
    my($pat) = 'S n C4 x8';
    my($HName, $HPort, @addr, @addr_, $prot);
    if (defined($ADDR)) {
      ($prot, $HPort, $addr[0], $addr[1], $addr[2], $addr[3]) = unpack($pat, $ADDR);
       @addr = ($addr[0] . "." . $addr[1] . "." . $addr[2] . "." . $addr[3], $HPort);
    } else {
       @addr = ("", 0);
    }

    #print "UnPackAddr =$ADDR=$addr[0]=$addr[1]=$addr[2]=$addr[3]=$,=@addr=\n";

    return @addr;
}


sub OpenSocket($$;$)
{
    my($Handle, $NAME, $NumListen) = @_;
    my($Addrs);

    if (!defined($NumListen)) {
      $NumListen = 0;
    }

    $Addrs = PackAddr($NAME);

    if (defined($Addrs) || $NAME eq "") {
        my ($proto) = getprotobyname("tcp"); $proto = 6;
        if (!socket($Handle, AF_INET, SOCK_STREAM, $proto))  { $tools::Mes = "error with socket : $!"; return 0; }
        my($savsel) = select($Handle); $| = 1; select($savsel);
    }

    if (defined($Addrs) && $NAME ne "") {
      if (!bind($Handle, $Addrs))       { $tools::Mes = "error with bind : $!";       return 0; }
    }

    if (defined($Addrs) && $NAME ne "" && $NumListen > 0) {
      if (!listen($Handle, $NumListen)) { $tools::Mes = "error with listen : $!";     return 0; }
    }

    $tools::Mes = "";
    return 1;
}


sub StripNL($)
{
    my($a) = @_;

    if ( !defined($a) ) {
        return undef;
    }

    chop($a);
    if ($a =~ /\r/) {
      $a = $`;
    }

    return $a;
}


sub AsSize($)
{
    my($size) = @_;

    #$size = int($size);
    my(@r) = ("B", "KB", "MB", "GB");
    my($i) = 0;
    while($size >= 1024 && $i < $#r) {
      $i++;
      $size = $size / 1024.0;
    }

    my($size_f) = sprintf("%7.2f", $size);
    if ($size == int($size_f)) {
      return $size . " " . $r[$i];
    }
    return  $size_f . " " . $r[$i];
}


sub Hendler2HendlerCopy($$;$$)
{
    my($Src, $Dst, $TotalCopySize, $BufSize) = @_;
    my($Buf, $ReadBlockSize, $NeedWrite);
    my($TotalRead, $ReadCount, $WriteCount);
    $Hendler2HendlerCopy::Message = "No errors";

    $TotalCopySize = -1  if (!defined($TotalCopySize));
    $BufSize = 10 * 1024 if (!defined($BufSize));

    my($SelSrc) = new IO::Select( $Src );
    my($SelDst) = new IO::Select( $Dst );
    my(@ReadyArray);


    $TotalRead = 0;

    while(1) {
        ($Hendler2HendlerCopy::Message = "Hendler2HendlerCopy::StopFlag seted", return undef) if (defined($Hendler2HendlerCopy::StopFlag));

        $ReadBlockSize = (($TotalCopySize > 0) && (($TotalCopySize - $TotalRead) < $BufSize)) ? ($TotalCopySize - $TotalRead) : ($BufSize);
        return $TotalRead if ($ReadBlockSize == 0);

        @ReadyArray = $SelSrc->can_read(15);
        if ($#ReadyArray == -1) {
            $Hendler2HendlerCopy::Message = "Read Time out";
            return undef;
        }

        $ReadCount = sysread($Src, $Buf, $ReadBlockSize);
        if (!defined($ReadCount)) {
            $Hendler2HendlerCopy::Message = "Reading Error";
            return undef;
        }
        if ($ReadCount == 0 && $TotalCopySize <= 0) {
            return $TotalRead;
        }
        $TotalRead += $ReadCount;

        if (defined($Hendler2HendlerCopy::ReadProgressSub)) {
            &{$Hendler2HendlerCopy::ReadProgressSub}($ReadCount, $ReadBlockSize);
        }

        $NeedWrite = $ReadCount;
        while ($NeedWrite > 0) {
             if (defined($Hendler2HendlerCopy::StopFlag)) {
                $Hendler2HendlerCopy::Message = "Hendler2HendlerCopy::StopFlag seted";
                return undef;
             }

            @ReadyArray = $SelDst->can_write(15);
            if ($#ReadyArray == -1) {
                $Hendler2HendlerCopy::Message = "Writing Time out";
                return undef;
            }

            $WriteCount = syswrite($Dst, $Buf, $NeedWrite);
            if (!defined($WriteCount)) {
                $Hendler2HendlerCopy::Message = "Writing Error";
                return undef;
            }

            if (defined($Hendler2HendlerCopy::DigestMD5Object) && ref($Hendler2HendlerCopy::DigestMD5Object) eq "Digest::MD5") {
                $Hendler2HendlerCopy::DigestMD5Object->add(substr($Buf, 0, $WriteCount));
            }
            if (defined($Hendler2HendlerCopy::WriteProgressSub)) {
                my($res) = &{$Hendler2HendlerCopy::WriteProgressSub}($WriteCount);
                if (!$res) {
                    if ($TmpStoreProcess::Message) {
                        $Hendler2HendlerCopy::Message = $TmpStoreProcess::Message;
                    } else {
                        $Hendler2HendlerCopy::Message = "Process cancel";
                    }
                    return undef;
                }
            }
            $Buf = substr($Buf, $WriteCount);
            $NeedWrite -= $WriteCount;
        }
    }
}


sub md5($)
{
    return Digest::MD5::md5_hex($_[0]);
}


sub md5sum($)
{
    my($name) = @_;
    my($ctx);

    local *MD5;

    if (! open(MD5, "<" . $name)) {
        return undef;
    }

    $ctx = Digest::MD5->new;
    $ctx->addfile(MD5);

    close MD5;

    return $ctx->hexdigest;
}


sub tohex($)
{
    my($arg) = @_;

    my($lo) = $arg % 16;
    my($hi) = ($arg - $lo) / 16;
    my($HD) = "0123456789ABCDEF";

    return substr($HD, $hi, 1) . substr($HD, $lo, 1);
}


sub urlencode($)
{
    my($Src) = @_;
    my($Dst) = "";
    my($i);



    for($i=0; $i<length($Src); $i++) {
        my($c) = substr($Src, $i, 1);
        if ($c =~ /[A-Z0-9=\-_]/i) {
            $Dst .= $c;
        } else {
            $Dst .= "%" . tohex(ord($c));
        }
    }

    return $Dst;
}


sub Decode($)
{
    my($rez) = @_;
    my($k1, $k2);

    return undef if (!defined($rez));

    while($rez =~ /\%([0-9A-F])([0-9A-F])/i) {
        $k1 = $1;
        $k1 = (ord($k1) - ord('A')) + 10 if (ord($k1) > ord('A'));
        $k2 = $2;
        $k2 = (ord($k2) - ord('A')) + 10 if (ord($k2) > ord('A'));
        $rez = $` . chr($k1 * 16 + $k2) . $'
    }

    return $rez;
}

sub urldecode($)
{
    return Decode($_[0]);
}

sub GetCurrDate()
{
    my($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime();
    $mon ++; $year += 1900;
    return sprintf("%04d/%02d/%02d %02d:%02d:%02d", $year,$mon,$mday,$hour,$min,$sec);
}

sub print_results($)
{
    return $_[0] ? $_[0] : "undef";
}


sub disk_total_space($)
{
    my ($path) = @_;

    my($TmpArr);
    @{$TmpArr} = statvfs($path);

    if ( !defined(@{$TmpArr}) ) {
        $tools::Mes = "error 'disk_total_space' calls with name $path : $!";
        return undef;
    }

    my($bsize, $frsize, $blocks, $bfree, $bavail,
       $files, $ffree, $favail, $fsid, $basetype, $flag,
       $namemax, $fstr) = @{$TmpArr};

    return $blocks * $bsize / 1024;
}

sub disk_free_space($)
{
    my ($path) = @_;

    my($TmpArr);
    @{$TmpArr} = statvfs($path);

    if ( !defined(@{$TmpArr}) ) {
        $tools::Mes = "error 'disk_free_space' calls with name $path : $!";
        return undef;
    }

    my($bsize, $frsize, $blocks, $bfree, $bavail,
       $files, $ffree, $favail, $fsid, $basetype, $flag,
       $namemax, $fstr) = @{$TmpArr};

    return $bfree * $bsize / 1024;
}


sub disk_avail_space($)
{
    my ($path) = @_;

    my($TmpArr);
    @{$TmpArr} = statvfs($path);

    if ( !defined(@{$TmpArr}) ) {
        $tools::Mes = "error 'disk_avail_space' calls with name $path : $!";
        return undef;
    }

    my($bsize, $frsize, $blocks, $bfree, $bavail,
       $files, $ffree, $favail, $fsid, $basetype, $flag,
       $namemax, $fstr) = @{$TmpArr};

    return $bavail * $bsize / 1024;
}

1
