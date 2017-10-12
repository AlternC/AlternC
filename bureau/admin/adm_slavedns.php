<?php
/*
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
 */

/**
 * Manage the list of SLAVE DNS machines account and IPs
 * used for the transfer of zones in Bind and the list of domains in domlist.php
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

if (!$admin->enabled) {
  $msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
  echo $msg->msg_html_all();
  exit();
}

$fields = array (
    "delaccount"   => array ("request", "string", ""),
    "newlogin"   => array ("post", "string", ""),
    "newpass"    => array ("post", "string", ""),

    "delip"   => array ("request", "string", ""),
    "newip"    => array ("post", "string", ""),
    "newclass" => array ("post", "string", "32"),
    );
getFields($fields);

if ($delaccount) {
  // Delete an account
  if ($dom->del_slave_account($delaccount)) {
    $msg->raise("INFO", "admin", _("The requested account has been deleted. It is now denied."));
  }
}
if ($newlogin) {
  // Add an account
  if ($dom->add_slave_account($newlogin,$newpass)) {
    $msg->raise("INFO", "admin", _("The requested account address has been created. It is now allowed."));
    unset($newlogin); unset($newpass);
  }
}

if ($delip) {
  // Delete an ip address/class
  if ($dom->del_slave_ip($delip)) {
    $msg->raise("INFO", "admin", _("The requested ip address has been deleted. It will be denied in one hour."));
  }
}
if ($newip) {
  // Add an ip address/class
  if ($dom->add_slave_ip($newip,$newclass)) {
    $msg->raise("INFO", "admin", _("The requested ip address has been added to the list. It will be allowed in one hour."));
    unset($newip); unset($newclass);
  }
}

include_once("head.php");

$c=$admin->listPasswordPolicies();
$passwd_classcount = $c['adm']['classcount'];

?>
<h3><?php __("Manage allowed ip for slave zone transfers"); ?></h3>
<hr id="topbar" />
<?php

$c=$dom->enum_slave_ip();

echo $msg->msg_html_all();

if (is_array($c)) { ?>
  <p>
  <?php __("Here is the list of the allowed ip or ip class for slave dns zone transfer requests (AXFR). You must add the ip address of all the slave DNS you have so that those slaves will be allowed to transfer the zone files. There is also some defaults ip from DNS checks made by some third-party technical offices such as afnic (for .fr domains)"); ?>
  </p>

  <table border="0" cellpadding="4" cellspacing="0" class='tlist'>
  <tr><th><?php __("Action"); ?></th><th><?php __("IP Address"); ?></th></tr>
  <?php
  for($i=0;$i<count($c);$i++) {
    ?>

      <tr class="lst">
      <td class="center"><div class="ina delete"><a href="adm_slavedns.php?delip=<?php echo urlencode($c[$i]['ip']); ?>"><?php __("Delete"); ?></a></div></td>
      <td><?php echo $c[$i]["ip"]."/".$c[$i]["class"]; ?></td>
      </tr>
      <?php
  } //for 
  ?>
  </table>
<?php }  // is_array ($c) ?>

<p><?php __("If you want to allow an ip address or class to connect to your dns server, enter it here. Choose 32 as a prefix for single ip address."); ?></p>

<form method="post" action="adm_slavedns.php" name="main" id="main">
  <?php csrf_get(); ?>
  <table class="tedit">
    <tr><th><label for="newip"><?php __("IP Address"); ?></label></th><th><label for="newclass"><?php __("Prefix"); ?></label></th></tr>
    <tr>
      <td style="text-align: right"><input type="text" class="int" value="<?php ehe( (isset($newip)?$newip:'') ); ?>" id="newip" name="newip" maxlength="15" size="20" style="text-align:right" /> / </td>
      <td><input type="text" class="int" value="<?php ehe( (isset($newclass)?$newclass:'') ); ?>" id="newclass" name="newclass" maxlength="2" size="3" /></td>
    </tr>
    <tr>
      <td colspan="2"><input type="submit" value="<?php __("Add this ip to the slave list"); ?>" class="inb" /></td>
    </tr>
  </table>
</form>

<br/>
<br/>
<hr/>

<h3><?php __("Manage allowed accounts for slave zone transfers"); ?></h3>
<hr id="topbar"/>
<br />

<?php
$c=$dom->enum_slave_account();

if (is_array($c)) { ?>
  <p><?php __("Here is the list of the allowed accounts for slave dns synchronization. You can configure the alternc-slavedns package on your slave server and give him the login/pass that will grant him access to your server's domain list. "); ?></p>

 <table class="tlist">
    <tr><th><?php __("Action"); ?></th><th><?php __("Login"); ?></th><th><?php __("Password"); ?></th></tr>
    <?php
    for($i=0;$i<count($c);$i++) { ?>

      <tr class="lst">
        <td class="center"><div class="ina delete"><a href="adm_slavedns.php?delaccount=<?php echo urlencode($c[$i]["login"]); ?>"><?php __("Delete"); ?></a></div></td>
        <td><?php ehe($c[$i]["login"]); ?></td>
        <td><?php ehe($c[$i]["pass"]); ?></td>
      </tr>
      <?php
  } // for ?>
</table>
<?php } // is_array ?>

<p><?php __("If you want to allow a new server to access your domain list, give him an account."); ?></p>

<form method="post" action="adm_slavedns.php" name="main" id="main" autocomplete="off">
  <?php csrf_get(); ?>
<!-- honeypot fields -->
<input type="text" style="display: none" id="fakeUsername" name="fakeUsername" value="" />
<input type="password" style="display: none" id="fakePassword" name="fakePassword" value="" />

  <table class="tedit">
    <tr><th><label for="newlogin"><?php __("Login"); ?></label></th><th><label for="newpass"><?php __("Password"); ?></label></th></tr>
    <tr>
      <td><input type="text" class="int" value="<?php ehe(  isset($newlogin)?$newlogin:'') ; ?>" id="newlogin" name="newlogin" maxlength="64" size="32" /><br/><br/></td>
      <td><input type="password" class="int" autocomplete="off" value="<?php ehe( (isset($newpass)?$newpass:'') ) ; ?>" id="newpass" name="newpass" maxlength="64" size="32" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#newpass","",$passwd_classcount); ?></td>
    </tr>
    <tr class="trbtn"><td colspan="2"><input type="submit" value="<?php __("Add this account to the allowed list"); ?>" class="inb" /></td></tr>
  </table>
</form>

<script type="text/javascript">
  document.forms['main'].newip.focus();
$(function(){
	$(".toggle-next").on("click",function(){

		var next = $(this).next();
		next.toggle();
	})

});
</script>

<style>

.info {
    background: none repeat scroll 0 0 white;
    border: 1px solid #CCCCCC;
    border-radius: 15px 15px 15px 15px;
    margin: 16px 0;
    padding: 0 16px;
}
.info-hide{
display:none;	
}
</style>
<div class="info">
<h4 class="toggle toggle-next"><a href="javascript:void(0)" class="btn"> <?php __("Need open DNS Slave servers?"); ?> &#9660;</a></h4>
<div class="info-hide">
<p><?php __("We offer free of charge DNS servers for alternc users."); ?></p>
<h2><?php __("How does it work?"); ?> </h2>
<ol>
	<li><?php printf(_("<strong>Give access to the alternc.net servers.</strong> Follow the instructions on <a href='%s' target='blank'>this page</a>. They will help you to configure this page and configure your alternc.net account."),"http://aide-alternc.org/go.php?hid=400"); ?></li>
	<li><?php printf(_("<strong>Subscribe to alternc.net.</strong> Go to <a href='%s' target='_blank' class='btn btn-inline btn-link'>the alternc.net site</a> to use the DNS servers provided for free by the AlternC association and enter the required informations for each server you want to connect to the service."),"http://alternc.net/"); ?> </li>
</ol>
<br />
<p><?php __("The alternc.net servers will take care of transfering and distributing to the world your domains zones."); ?> </p>
</div><!-- info-hide -->
</div><!-- info  -->
<?php include_once("foot.php"); ?>
