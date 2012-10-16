<?php

@include_once("/etc/squirrelmail/alternc-changepass.conf");
if (!defined("ALTERNC_LOC")) {
  echo "No AlternC-Changepass configuration, please setup alternc-changepass plugin in /etc/squirrelmail/";
  exit();
}

bindtextdomain("alternc-changepass", ALTERNC_LOC."/bureau/locales");

$link=mysql_connect(
		    ALTERNC_CHANGEPASS_MYSQL_HOST,
		    ALTERNC_CHANGEPASS_MYSQL_USER,
		    ALTERNC_CHANGEPASS_MYSQL_PASSWORD
		    );
if ($link) {
  mysql_select_db(ALTERNC_CHANGEPASS_MYSLQ_DB);
} else {
  __("Can't connect to MySQL server on AlternC!");
}


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

textdomain("alternc-changepass");

global $username, $base_uri, $key, $onetimepad;

list($login,$domain)=explode("@",$username,2);

if ($_POST['acp_oldpass'] && $_POST['acp_newpass'] && $_POST['acp_verify']) {
  if ($_POST['acp_newpass']!=$_POST['acp_verify']) {
    $errstr=_("Your new passwords are differents, pleasy try again.");
  } else {
    // Check the old password
    $r=mysql_query("SELECT a.password FROM address a,domaines d WHERE a.address='".addslashes($login)."' AND a.dom_id=d.id AND d.domaine='".addslashes($domain)."';");
    if (!($c=mysql_fetch_array($r))) {
      $errstr=_("Your account has not been found, please try again later or ask an administrator.");
    } else {
      if ($c["password"]!=_md5cr($_POST['acp_oldpass'],$c["password"])) {
	$errstr=_("Your current password is incorrect, please try again.");
      } else {
	// FIXME DO Check the password policy : 
	/*
	if (is_callable(array($admin,"checkPolicy"))  && 
	    !$admin->checkPolicy("pop",$username,$_POST['acp_newpass'])) {
	  $errstr=_("This password is not strong enough for your policy, set a stronger password or call your administrator");
	} else {
	*/
	  // ok, let's change the password
	  $acp_newpass=$_POST['acp_newpass'];
	  $newp=_md5cr($acp_newpass);
	  mysql_query("UPDATE address SET password='".addslashes($newp)."' WHERE id=".$c["id"]." ;");
	  $errstr=_("Your password has been successfully changed. Don't forget to change it in your mail software if you are using one (Outlook, Mozilla, Thunderbird, Eudora ...)");
	  
	  // Write new cookies for the password
	  $onetimepad = OneTimePadCreate(strlen($acp_newpass));
	  sqsession_register($onetimepad,'onetimepad');
	  $key = OneTimePadEncrypt($acp_newpass, $onetimepad);
	  setcookie("key", $key, 0, $base_uri);
	  //	}
      }
    }
  }
} // POSTED data ? 

 
 textdomain("squirrelmail");

 displayPageHeader($color, 'None');

 textdomain("alternc-changepass");
 
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
