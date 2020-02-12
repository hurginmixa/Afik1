#!/usr/bin/perl -w

use strict 'vars';


my($lang, $sess, $srclang, $tmp);
my($src, $dst, $item, $valu, $new1st);

$lang = $ARGV[0];
$sess = $ARGV[1];

$srclang = "en_US";


if (!open(SRC, "$srclang/$sessi.txt")) { die "Error open src session $srclang/$sess '$!'"; }
$src = join("", <SRC>);
close (SRC);

if (!open(SRC, "$lang/$sessi.txt")) { die "Error open dst session $lang/$sess '$!'"; }
$dst = join("", <SRC>);
close (SRC);


$tmp = $src;
$new1st = 1;
while ($tmp =~ /^[\s]*([^\s]+)[\s]*=[\s]*(.*)$/m) {
  $item = $1;
  $valu = $2;
  $tmp = $';
 

  if (!($dst =~ /^[\s]*$item[\s]*=/m)) {
    if ($new1st == 1) {
      $dst .= "#---------new--------\n";
      $new1st = 0; 
    } 
    $dst .= "$item = $valu\n";
  } 
}


$tmp = $dst;
while ($tmp =~ /^[\s]*([^\s]+)[\s]*=/m) {
  $item = $1;
  $tmp = $';
  
  if (!($src =~ /^[\s]*$item[\s]*=/m)) {
    $dst =~ s/^([\s]*$item[\s]*=)/#$1/mg;
  } 
}

rename "$lang/$sess", "$lang/$sess" . ".old";

open (OUT, ">$lang/$sess");
print OUT $dst;
close OUT;
