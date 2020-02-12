#!/usr/bin/perl
#
#


use Fcntl ':flock';

$superuser = "apache";

sub ResolLink($)
{
    my($name) = @_;
    my($newname);
    my($pos);

    if (! -l $name) {
	return $name;
    }

    $newname = readlink($name);

    if (substr($newname, 0, 1) ne '/') {
	$pos = rindex($name, "/");
	if ($pos != -1) {
	    $newname = substr($name, 0, $pos + 1) . $newname;
	}
    }

    return $newname;
}

sub FindUser($$)
{
    my($name) = @_[0];
    my($usr) = @_[1];
    my($r, $i);
    

    (-r $name) || die("$name : not readable file");
    open al, "<".$name || die("error open $name for input\n");
    flock(al, LOCK_EX) || die "unable flock an $name";
    $r = 0;
    while (<al>) {
	if (/^$usr[ \t]*:/) {
	    $r = 1;
	}
    }
    flock(al, LOCK_UN);
    close al;

    return $r
}

sub AddUser($$)
{
    my($name) = @_[0];
    my($usr) = @_[1];
    
    (!FindUser($name, $usr)) || die "user $usr exist";

    (-w $name) || die("$name : not writeble file");
    open al, ">>$name" || die "unable open file $name for append";
    flock(al, LOCK_EX) || die "unable flock an $name";
    print al "$usr:\t\"|sudo -u $superuser /usr/bin/afik1_getmail $usr\"\n";
    flock(al, LOCK_UN);
    close al;
}


sub DelUser($$)
{
    my($name) = @_[0];
    my($usr) = @_[1];
    my(@a, $r, $i);
    

    (-r $name) || die("$name : not readable file");
    open al, "<".$name || die("error open $name for input\n");
    flock(al, LOCK_EX) || die "unable flock an $name";
    $i = 0;
    $r = 0;
    while (<al>) {
	if (!/^$usr[ \t]*:/) {
	    $a[$i++] = $_;
	} else {
	    $r = 1;
	}
    }
    flock(al, LOCK_UN);
    close al;
    
    if (!$r) {
	return;
    }

    (-w $name) || die("$name : not writeble file");
    open al, ">$name" || die "unable open file $name for append";
    flock(al, LOCK_EX) || die "unable flock an $name";
    print al @a;
    flock(al, LOCK_UN);
    close al;
}



if ($#ARGV < 0 || $#ARGV > 1) {
    die "error in parameter's count";
}

if ($ARGV[0] eq "-a" || $ARGV[0] eq "-d" || $ARGV[0] eq "-f") {
    if ($#ARGV != 1) {
	die "No name user";
    }
    $job = $ARGV[0];
    $usr = $ARGV[1];
} else {
    if (substr($ARGV[0], 0, 1) eq "-") {
	die "key $ARGV[0] not supported";
    }
    if ($#ARGV == 1) {
	die "error in parameter's count";
    }
    $job = "-a";
    $usr = $ARGV[0];
}


$name = "/etc/aliases";
#$name = 1;

$name = ResolLink($name);
(-f $name) || die("$name : not file");


if ($job eq "-a") {
    AddUser($name, $usr);
}

if ($job eq "-d") {
    DelUser($name, $usr);
}

if ($job eq "-f") {
    if(FindUser($name, $usr))
    {
	print "Alias used\n";
    } else {
	print "Alias not used\n";
    }

    exit;
}

print "runing 'newalias'\n";
system("newaliases");
