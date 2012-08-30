<?php

 if (!$already) { 
 define('SM_PATH','../../');
 
 /* SquirrelMail required files. */
 require_once(SM_PATH . 'include/validate.php');
 require_once(SM_PATH . 'functions/page_header.php');
 require_once(SM_PATH . 'functions/imap.php');
 require_once(SM_PATH . 'include/load_prefs.php');

 /* get globals */
 sqgetGlobalVar('username', $username, SQ_SESSION);
 
 require_once (SM_PATH . "plugins/alternc_changepass/config.php");
 
 session_start();
 
 textdomain("changepass");

 global $username, $base_uri, $key, $onetimepad ,$admin, $classes, $mail, $err;


if ($_POST['acp_oldpass'] && $_POST['acp_newpass'] && $_POST['acp_verify']) {
  if ($_POST['acp_newpass']!=$_POST['acp_verify']) {
    $errstr=_("Your new passwords are differents, pleasy try again.");
  } else {
    // Check the old password
    $db->query("SELECT password FROM mail_users WHERE alias='".addslashes($username)."'");
    if (!$db->next_record()) {
      $errstr=_("Your account has not been found, please try again later or ask an administrator.");
    } else {
      if ($db->f("password")!=_md5cr($_POST['acp_oldpass'],$db->f("password"))) {
	$errstr=_("Your current password is incorrect, please try again.");
      } else {
	// If available, check the password policy : 
	if (is_callable(array($admin,"checkPolicy"))  && 
	    !$admin->checkPolicy("pop",$username,$_POST['acp_newpass'])) {
	  $errstr=_("This password is not strong enough for your policy, set a stronger password or call your administrator");
	} else {
	  // ok, let's change the password
	  $m=explode("@",$username,2);
	  $acp_newpass=$_POST['acp_newpass'];
	  $newp=_md5cr($acp_newpass);
	  $un1=str_replace("@","_",$username); // version login_domain.tld
	  $un2=substr($un1,0,strlen($un1)-strlen(strrchr($un1,"_")))."@".substr(strrchr($un1,"_"),1); // version login@domain.tld
	  $db->query("UPDATE mail_users SET password='$newp' WHERE alias='$un1' or alias='$un2';");

	  $errstr=_("Your password has been successfully changed. Don't forget to change it in your mail software if you are using one (Outlook, Mozilla, Thunderbird, Eudora ...)");
	  
	  // Write new cookies for the password
	  $onetimepad = OneTimePadCreate(strlen($acp_newpass));
	  sqsession_register($onetimepad,'onetimepad');
	  $key = OneTimePadEncrypt($acp_newpass, $onetimepad);
	  setcookie("key", $key, 0, $base_uri);
	}
      }
    }
  }
 }
 
 
 textdomain("squirrelmail");

 displayPageHeader($color, 'None');

 textdomain("changepass");
 
 }


if ($errstr) echo "<p><b>".$errstr."</b></p>";

?>


<h2><?php __("Changing your mail password"); ?></h2>
<form method="post" action="change.php" name="main" id="main">
    <table>
      <tr>
   <th align="right"><label for="acp_oldpass"><?php __("Old Password:"); ?></label></th>
        <td><input type="password" name="acp_oldpass" id="acp_oldpass" value="" size="20" /></td>
      </tr>

      <tr>
   <th align="right"><label for="acp_newpass"><?php __("New Password:"); ?></label></th>
        <td><input type="password" name="acp_newpass" id="acp_newpass" value="" size="20" /></td>
      </tr>
      <tr>
   <th align="right"><label for="acp_verify"><?php __("Verify New Password:"); ?></label></th>
        <td><input type="password" name="acp_verify" id="acp_verify" value="" size="20" /></td>
      </tr>

      <tr>
        <td align="center" colspan="2"><input type="submit" value="<?php __("Change my mail password"); ?>" name="plugin_changepass" /></td>
      </tr>
    </table>

</form>

<script type="text/javascript">
  document.forms['main'].acp_oldpass.focus();
  document.forms['main'].setAttribute('autocomplete', 'off');
</script>


</body></html>
<?php

textdomain("squirrelmail");

?>
