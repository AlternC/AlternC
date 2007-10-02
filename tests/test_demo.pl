#!/usr/bin/perl

#use strict;
use WWW::Mechanize;

use mechdump;

my $DEBUG=1;
my $DESKTOP="http://demo.alternc.org/admin";
my $ADMIN_ACCOUNT="admin"; 
my $ADMIN_PASSWORD="admin";

# We install this domain, this pop account etc. : 
my $DOMAIN="demo2.alternc.org";
my $EMAIL="test";
my $POPPASS="coinP4n";

my $FTPLOGIN="test";
my $FTPPASS="p4ncoin";
my $FTPFOLD="/";

# We initialize a mechanize object : 
$m = WWW::Mechanize->new(  agent => '(Mozilla 5.0) AlternC test'  );

# We get the desktop absolute url : 
$m->get($DESKTOP);

# We submit the login/password for the adminisrator account : 
my $r = $m->submit_form(form_number => 1, 
			fields => { 'username' => $ADMIN_ACCOUNT, 'password' => $ADMIN_PASSWORD }
			);
if ($DEBUG) {
    print "LINKS AFTER LOGIN : \n"; dump_links $m;
}

$m->follow_link(url_regex => qr/menu/i ) || die "Login or password incorrect ...";

if ($DEBUG) {
    print "LINKS IN LEFT FRAME : \n"; dump_links $m;
}


# For each service, we create one instance of testable one : 


# DOMAIN : 
print "Adding domain $DOMAIN \n";
$m->follow_link(url_regex => qr/dom_add/i ) || die "Cannot add a new domain ...";
my $r = $m->submit_form(form_number => 1,
		fields => { 'newdomain' => $DOMAIN, 'dns' => 1 }
		);
if (!$r->is_success()) {
    # Impossible d'ajouter le domaine : la form n'existe pas ...
    die "Cannot add domain $DOMAIN ...";
}
print "  done \n";



# EMAIL : 
print "Creating a mail $EMAIL\@$DOMAIN \n";
$m->get("menu.php");  # does relative url works ? 
$m->follow_link(url_regex => qr/mail\_list\.php\?domain\=$DOMAIN/i ) || die "Cannot list mails for domain $DOMAIN ...";
$m->follow_link(url_regex => qr/mail\_add\.php\?domain\=$DOMAIN/i ) ||  die "Cannot find the 'add email' link for domain $DOMAIN ...";

my $r = $m->submit_form(form_number => 1,
		fields => { "domain" => $DOMAIN,
			    "email" => $EMAIL,
			    "pop" => 1,
			    "pass" => $POPPASS,
			    "passconf" => $POPPASS,
			    "alias" => "",
			}
		);
if (!$r->is_success()) {
    # Impossible d'ajouter le mail : la form n'existe pas ...
    die "Cannot add email $EMAIL\@$DOMAIN ...";
}
print "  done \n";



# FTP : 
print "Creating a ftp account ${ADMIN_ACCOUNT}_${FTPLOGIN} \n";
$m->get("menu.php");  # does relative url works ? 
$m->follow_link(url_regex => qr/ftp\_list\.php/i ) || die "Cannot list ftp accounts ...";
$m->follow_link(url_regex => qr/ftp\_add\.php/i ) || die "Cannot find the 'add ftp' link ...";
my $r = $m->submit_form(form_number => 1,
			fields => { "id" => 0,
                            "" => $,
				}
			);
if (!$r->is_success()) {
    # Impossible d'ajouter le mail : la form n'existe pas ...
    die "Cannot add email $EMAIL\@$DOMAIN ...";
}
print "  done \n";





# SQL :
# sql_list.php

