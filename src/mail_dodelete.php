#!/usr/bin/php -q
<?php
// Launch the hooks for a real deletion of the mail whose ID is in parameters

$mail_id = @intval($argv[1]);
if (empty($mail_id) ) {
  die('You must specified a valid mail id (integer)');
}

require_once("/usr/lib/alternc/panel/class/config_nochk.php");

// Wich account should I be ?
$uid=$mail->get_account_by_mail_id($mail_id);

// Ok, so be it
$mem->su($uid);

// Get the mails informations
$mailinfos=$mail->get_details($mail_id);
// AND CALL THE HOOKS
$hooks->invoke('hook_mail_delete_for_real', array($mail_id, $mailinfos['address'].'@'.$mailinfos['domain'] ));

// Bye bye

echo "\n\n";

?>
