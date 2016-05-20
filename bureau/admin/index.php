<?php

/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Purpose of file: Main index : show the login page
 ----------------------------------------------------------------------
*/

require_once("../class/config_nochk.php");

if ($mem->checkid()) {
	Header("Location: /main.php");
	exit;
}

if (!$mem->del_session()) {
  // No need to draw an error message ...
  //$error=$err->errstr();
}

$H=getenv("HTTP_HOST");

if (!isset($restrictip)) {
  $restrictip=1;
}
if (!isset($charset) || ! $charset) $charset="UTF-8";
@header("Content-Type: text/html; charset=$charset");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>AlternC Desktop</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<?php
if (file_exists("styles/style-custom.css") ) {
  echo '<link rel="stylesheet" href="styles/style-custom.css" type="text/css" />';
}
?>

<script type="text/javascript" src="js/alternc.js"></script>
<script src="js/jquery.min_embedded.js" type="text/javascript"></script>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
</head>
<body class="login_page">
  <div id="global">

    <div id="content">
<?php
// Getting logo
$logo = variable_get('logo_login', '' ,'You can specify a logo for the login page, example /images/my_logo.png .', array('desc'=>'URL','type'=>'string'));
if ( empty($logo) ||  ! $logo ) { 
  $logo = 'images/logo.png'; 
}
?>

      <p id="logo">  <img src="<?php echo $logo; ?>" border="0" alt="<?php __("Web Hosting Control Panel"); ?>" title="<?php __("Web Hosting Control Panel"); ?>" />
      </p>
      <p>&nbsp;</p>
    <?php if (isset($error) && $error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <br/>
    <?php
    if (isset($_GET['authip_token'])) $authip_token=$_GET['authip_token'];
    if (variable_get('https_warning', true, 'warn users to switch to HTTPS') && !isset($_SERVER['HTTPS'])) {
      echo '<div class="unsecure"><strong>' . sprintf(_('WARNING: you are trying to access the control panel insecurely, click <a href="https://%s">here</a> to go to secure mode'), $_SERVER["HTTP_HOST"]) . '</strong></div>';
    }
    ?>
    <div class="block_list">
      <div class="block_login_page">
        <?php __("To connect to the hosting control panel, enter your AlternC's login and password in the following form and click 'Enter'"); ?>
      </div>
      <div class="block_login_page">
	<div class="menu-box">
        <?php if (!empty($authip_token)) { echo "<p style='color:red;'>";__("You are attemping to connect without IP restriction."); echo "</p>"; } ?>
            <div class="menu-title"><?php __("AlternC access"); ?></div>
	    <form action="login.php" method="post" name="loginform" target="_top">
      <?php csrf_get(); ?>
            <div class="menu-content">
                <div><label for="username"><?php echo _("Username"); ?></label></td><td><input type="text" class="int" name="username" id="username" value="" maxlength="128" autocapitalize="none" /></div>
                <div><label for="password"><?php echo _("Password"); ?></label></td><td><input type="password" class="int" name="password" id="password" value="" maxlength="128" /></div>
                <div class="submit"><input type="submit" class="inb" name="submit" onclick='return logmein();' value="<?php __("Enter"); ?>" /><input type="hidden" id="restrictip" name="restrictip" value="0" />
                <input type="hidden" id="authip_token" name="authip_token" value="<?php echo htmlentities( (empty($authip_token)?'':$authip_token) ) ?>" /></div>
            </div>
          </form>
	</div>
      </div>
      <div class="block_login_page">

        <?php __("You must accept the session cookie to log-in"); ?>
        <br />
        <?php echo _("If you want to use a different language, choose it in the list below"); ?>
        <br />
              <?php 
            foreach($locales as $l) {
              ?>
              <a href="?setlang=<?php echo $l; ?>"><?php if (isset($lang_translation[$l])) echo $lang_translation[$l]; else echo $l;  ?></a>
              <?php } ?>
        <br />
        <?php
         $mem->show_help("login",true); 
        ?>
      </div>
      <div class="block_login_page">
        <?php

          // Here we used to have a form to enter the squirrelmail's webmail.
          // Following the "rule of less astonishment, we try to put it here again, even though the webmail is now a plugin.
          $res=$hooks->invoke("hook_admin_webmail");
        if (($wr=variable_get("webmail_redirect")) && isset($res[$wr]) && $res[$wr]) {
          $url=$res[$wr];
        } else {
          foreach($res as $r) if ($r!==false) { $url=$r; break; }
        }
        if (isset($url) && $url)  {
        ?>
          <p><a href="<?php echo $url; ?>"><?php __("To read your mail in a browser, click here to go to your server's Webmail"); ?></a></p>
        <?php
        }
        ?>
      </div>
    </div>
    <div class="alternc_powered">
        <a href="http://www.alternc.com/"><img src="images/powered_by_alternc2.png" width="128" height="32" alt="Powered by AlternC" /></a>
    </div>

    <script type="text/javascript">
    $('#username').focus();

    function logmein(){
      if ( $('#username').val() =='' || $('#password').val() =='' ) {
        alert("<?php __("Need a login and a password"); ?>");
        return false;
      }
      return true;
    }
    </script>

  </div>
  <div style="clear:both;" ></div>
  </div>
</body>
</html>
