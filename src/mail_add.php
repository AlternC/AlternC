#!/usr/bin/php -q
<?php
/**
 * @file helper function to create emails from the commandline
 *
 * automatically generates a password based on the configured
 * password generators
 *
 * Limitations
 * - has snarky comments about how PHP or AlternC is badly designed
 * - can create a mailbox or a forward, not both
 * - configuration is inline here
 * - quotas and "dryrun" options are hardcoded because php's getopt sucks
 */

// just for inspection
global $cuid;

// those will be tried in order, the first one to return more than 7
// chars will win
$generators = array('pwqgen', 'pwgen');
$dryrun = false;
// 1GB default quota
$default_quotas = 1024; // in MB because using bytes would be too
                        // precise (try to guess AlternC, just you try)

require_once("/usr/share/alternc/panel/class/config_nochk.php");

function usage() {
  global $argv;
  // putting {$argv[0]} or $argv[0] in the heredoc fails
  $wtfphp = $argv[0];
  $u = '
Usage: $wtfphp <email> <alias> ...

email: full email adress, including domain, which must exist
alias: one or many aliases the email should forward to, space separated

';
  error_log($u);
  exit(1);
}

if (count($argv) < 2) {
  usage();
}

$user = preg_split('/@/', $argv[1])[0]; // first argument is user@domain
$domain = preg_split('/@/', $argv[1])[1];
$recipients = array_slice($argv, 2); // rest is recipients

// there's no function to do that, oddly enough...
// there's one to extract the compte from the mail_id (!) but we
// haven't created it yet...
$db->query('SELECT id,compte FROM domaines WHERE domaine=?',array($domain));
if ($db->next_record()) {
  $compte = $db->f('compte');
  $domain_id = $db->f('id');
}
else {
  error_log("domain $domain not found");
  exit(2);
}

foreach ($generators as $generator) {
  $password = `$generator 2>/dev/null`;
  if (!is_null($password) and strlen($password) > 7) {
    $password = trim($password);
    break;
  }
}
if (is_null($password)) {
  error_log('password generators failed to produce 8 characters: ' . join("\n", $generators));
  exit(3);
}

/* need not to be $quota because that would replace alternc's global
 * $quota... even though we don't say global $quota anywhere here, yay
 * php scoping.
 */
$quotas = $default_quotas;
$r = join(", ", $recipients);

print '
user: $user
domain: $domain
compte: $compte
password: $password
quota: $default_quotas
recipients: $r

';

if ($dryrun) {
  error_log('not creating email because of $dryrun is true');
  exit(0);
}
print "cuid: $cuid\n";
$mem->su($compte);
print "cuid: $cuid\n";

/* function signature is:
 *  function create($dom_id, $mail,$type="",$dontcheck=false)
 * yet $type is never passed anywhere and is actually empty in the
 * database (!) $dontcheck is undocumented, so we'll ignore it
 *
 * also, this function explicitely tells me to not use it, but doesn't
 * provide an alternative. i choose to disobey instead of rewriting it
 * from scratch
 */
if (!($mail_id = $mail->create($domain_id, $user))) {
  error_log('failed to create: ' . $err->errstr());
  exit(4);
}

/* function set_passwd($mail_id,$pass)
 *
 * just set the password
 *
 * no idea why this is a different function.
 */
if (!$mail->set_passwd($mail_id,$password)) {
  error_log("failed to set password on mail $mail_id: " . $err->errstr());
  exit(5);
}

/*  function set_details($mail_id, $islocal, $quotamb,
 *  $recipients,$delivery="dovecot",$dontcheck=false)
 *
 * you read that right, recipients is a string (!)
 *
 * if we have no aliases, it's a mailbox. deal with it.
 */
if (!$mail->set_details($mail_id, !count($recipients), $quota, join("\n", $recipients))) {
  error_log('failed to set details: ' . $err->errstr());
  exit(6);
}

// maybe we need to call the hooks? i don't know!
  /* $rh=$hooks->invoke("mail_edit_post",array($mail_id)); */
  /* if (in_array(false,$res,true)) { */
  /*   include ("mail_edit.php"); */
  /*   exit(); */
  /* } else { */
  /*   foreach($rh as $h) if ($h) $error.=$h."<br />"; */
  /* } */

