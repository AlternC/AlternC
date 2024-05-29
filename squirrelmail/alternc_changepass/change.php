<?php

@include_once("/etc/squirrelmail/alternc-changepass.conf");
if (!defined("ALTERNC_CHANGEPASS_LOC")) {
  error_log("No configuration for squirrelmail plugin at /etc/squirrelmail/alternc-changepass.conf, please check");
  exit();
}

bindtextdomain("alternc", ALTERNC_CHANGEPASS_LOC."/bureau/locales");
if (!function_exists("__")) {
  function __($str) { echo __($str, "alternc", true); } 
}

  /* ----------------------------------------------------------------- */
  /** Hashe un mot de passe en clair en MD5 avec un salt aléatoire
   * @param string $pass Mot de passe à crypter (max 32 caractères)
   * @return string Retourne le mot de passe crypté
   * @access private
   */
  function _md5cr($pass,$salt="") {
    if (!$salt) {
      $chars="./0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
      for ($i=0;$i<12;$i++) {
	$salt.=substr($chars,(mt_rand(0,strlen($chars))),1);
      }
      $salt="$1$".$salt;
    }
    return crypt($pass,$salt);
  }

$link=mysql_connect(
		    ALTERNC_CHANGEPASS_MYSQL_HOST,
		    ALTERNC_CHANGEPASS_MYSQL_USER,
		    ALTERNC_CHANGEPASS_MYSQL_PASSWORD
		    );
if ($link) {
  mysql_select_db(ALTERNC_CHANGEPASS_MYSQL_DB);
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

//require_once (SM_PATH . "plugins/alternc_changepass/config.php");
//session_start();

textdomain("alternc");

global $username, $base_uri, $key, $onetimepad;

list($login,$domain)=explode("@",$username,2);
$errstr="";

if ($_POST['acp_oldpass'] && $_POST['acp_newpass'] && $_POST['acp_verify']) {
  if ($_POST['acp_newpass']!=$_POST['acp_verify']) {
    $errstr=__("Your new passwords are differents, pleasy try again.", "alternc", true);
  } else {
    // Check the old password
    $r=mysql_query("SELECT a.password, a.id FROM address a,domaines d WHERE a.address='".addslashes($login)."' AND a.domain_id=d.id AND d.domaine='".addslashes($domain)."';");
    echo mysql_error();
    if (!($c=mysql_fetch_array($r))) {
      $errstr=__("Your account has not been found, please try again later or ask an administrator.", "alternc", true);
    } else {
      if ($c["password"]!=_md5cr($_POST['acp_oldpass'],$c["password"])) {
	$errstr=__("Your current password is incorrect, please try again.", "alternc", true);
      } else {
	// FIXME DO Check the password policy : 
	/*
	if (is_callable(array($admin,"checkPolicy"))  && 
	    !$admin->checkPolicy("pop",$username,$_POST['acp_newpass'])) {
	  $errstr=__("This password is not strong enough for your policy, set a stronger password or call your administrator", "alternc", true);
	} else {
	*/
	  // ok, let's change the password
	  $acp_newpass=$_POST['acp_newpass'];
	  $newp=_md5cr($acp_newpass);
	  mysql_query("UPDATE address SET password='".addslashes($newp)."' WHERE id=".$c["id"]." ;");
	  $errstr=__("Your password has been successfully changed. Don't forget to change it in your mail software if you are using one (Outlook, Mozilla, Thunderbird, Eudora ..., "alternc", true)");
	  
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

textdomain("alternc");
 
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
