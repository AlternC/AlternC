#!/usr/bin/perl

$ENV{PATH} = "/usr/bin:/bin"; 
$ENV{CDPATH} = "";

$A=$ARGV[0];
sub untaint {
 my @list = @_;
 for (@list) { 
  /(.*)/;
  $_ = $1;
 } wantarray ? @list : $list[0];
}

$A=untaint($A);
if ($A=~/^\/var\/alternc\/html\/[a-z0-9]\//) {
    open(SI,"/usr/bin/du -s '$A'|");   
    $B=<SI>;
    $B=~/^([0-9]+).*/;
    printf "$1\n";
}
if ($A=~/^\/var\/alternc\/mail\/[a-z0-9_]\//) {
    open(SI,"/usr/bin/du -s '$A'|");
    $B=<SI>;
    $B=~/^([0-9]+).*/;
    printf "$1\n";
}
if ($A=~/^\/var\/alternc\/db\//) {
    open(SI,"/usr/bin/du -s '$A'|");
    $B=<SI>;
    $B=~/^([0-9]+).*/;
    printf "$1\n";
}
if ($A=~/^\/var\/lib\/mailman\//) {
    open(SI,"/usr/bin/du -s '$A'|");
    $B=<SI>;
    $B=~/^([0-9]+).*/;
    printf "$1\n";
}
