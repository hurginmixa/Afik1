#!/usr/bin/perl

if ($#ARGV != 1 && $#ARGV != 0) {
    die "error in parameter's count";
}


sub IPAddr($)
{
    my ($host) = @_;
    my ($name, $alias, $addrtype, $len, @addr);
    my (@res);

    (($name, $alias, $addrtype, $len, @addr) = gethostbyname($host)) || die "gethostbyname: $!";
    @res = unpack("C4", $addr[0]);

    return @res;
}

sub ParseUrl($)
{
    my ($URL) = @_;
    my ($Prot, $Host);

    if ($URL =~ /^\w+:/) {
	$Prot = substr($&, 0, -1);
	$URL = $';
    } else {
	$Prot = "";
    }

    if ($URL =~ /^\/\/[^\/]+/) {
	$Host = substr($&,2);
	$URL  = $';
    } else {
	$Host = "";
    }

    return (Prot => $Prot, Host => $Host, Path => $URL);
}


$Path = $ARGV[0];
#$Path = "http://mixa.afik1.co.il/proj4/src/access_to_file.php?TagFile=68";

$UID  = $ARGV[1];
#$UID  = 1;



%p = ParseUrl($Path);

@a = IPAddr($p{Host});

$that = pack('S n C4 x8',  2,  80,  $a[0],  $a[1],  $a[2],  $a[3]);
$this = pack('S n C4 x8',  2,  0,   212,  143,  71,  164);

socket(S, 2, 1, 6) || die "socket: $!";
#bind(S, $this) || die "bind: $!";
connect(S, $that)  ||  die "connect:$!";

select(S); $| = 1; select(stdout);

print S "POST " . $p{"Path"} . " HTTP/1.0\r\n";
if ($UID ne "") {
  print S "Cookie: CUID[$UID]=$UID\r\n";
}
print S "\r\n";

while (($r=<S>) && ($r ne "\r\n")) {}

while (<S>) {
    print;
}

close(S);
