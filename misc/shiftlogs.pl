#!/usr/bin/perl

use Getopt::Long;

local @main::ListMasks;
local $main::MaxNumber;

local %main::FileList;


sub LoadFiles()
{
	foreach $mask (@main::ListMasks) {
		#print "$mask\n";

		map {
				#print "=$_\n";
				if (!/\.[1-9][0-9]*$/) {
					my($dno, $ino) = stat;
					if (!defined($dno)) { print STDERR "Warrning : Invalid Mask $_ - not exists\n"; next; }
					if (-d _) { print STDERR "Warrning : directory $_ - passed\n"; next; }

					my($id) = "$dno.$ino";
					if ( !exists($main::FileList{$id}) ) {
						$main::FileList{$id} = $_;
					}
				}
		} glob($mask);
	}

	#print "==============\n";
	#map { print "$_ => $main::FileList{$_}\n" } keys %main::FileList ;
}

sub RotateFiles()
{
	#print "===RotateFiles===\n";

	foreach $file_id (keys %main::FileList) {
		my($file) = $main::FileList{$file_id};
		#print "$file\n";

		my(%Vers);
		foreach $ver ( glob($file . ".*") ) {
			my($dno, $ino) = stat $ver;
			my($id) = "$dno.$ino";

			if (exists( $main::FileList{$id} )) { next; }
			if (!($ver =~ /\.([1-9][0-9]*)$/)) { next; }
			if ($` ne $file) { next; }

			$Vers{$1} = $ver;
		}

		map {
			#print "   =  $_ => $Vers{$_}\n";
			if ($_ >= $main::MaxNumber) {
				unlink $Vers{$_} || die "Error with unlink 1 $Vers{$_}\n";
			} else {
				link $Vers{$_}, $file . "." . ($_ + 1) || die "Error with link $Vers{$_} to next number\n";
				unlink $Vers{$_} || die "Error with unlink 2 $Vers{$_}\n";
			}
													} sort { $b <=> $a } keys %Vers ;
		link ($file, $file . ".1") || die "Error with link $file to number 1\n";
		unlink $file || die "Error with unlink 3 $file\n";
	}
}

sub main()
{
    GetOptions("max_number=i" => \$main::MaxNumber);
    if (!defined($main::MaxNumber)) {
		$main::MaxNumber = 4;
	} else {
		if ($main::MaxNumber <= 0 || $main::MaxNumber > 128) {
			print STDERR "Invalud Max Number\n";
			exit;
		}
	}

	my($par);
	while ( $par = shift( @ARGV ) ) {
		$main::ListMasks[$#main::ListMasks + 1] = $par;
	}

	if ($#main::ListMasks == -1) {
		print STDERR "Empty list file\n";
		exit;
	}

	#print "$main::MaxNumber\n";
	#print "$#main::ListMasks\n";
	#map { print " = $_\n"; } @main::ListMasks;

	LoadFiles();
	RotateFiles();
}

main();
__END__
