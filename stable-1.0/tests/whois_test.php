#!/usr/bin/php
<?php

// test de la fonction whois : 

include("../bureau/class/m_err.php");
include("../bureau/class/m_dom.php");

$err=new m_err();
$dom=new m_dom();

$doms=array("dns.be","eurid.eu","sonntag.name","alternc.com","alternc.org","alternc.net","sonntag.fr");
foreach($doms as $d) {
	echo "\n";
	echo "Searching for whois for domain [$d]\n";
	$srv=$dom->whois($d);
	if (is_array($srv)) {
		foreach($srv as $s) {
			echo "	Nameserver: $s\n";
		}
	} else {
		echo "	No nameserver found !\n";
	}
}

?>