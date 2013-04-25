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
 Purpose of file: listing of mail accounts for one domain.
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

$fields = array (
		 "mail_arg"    => array ("request", "integer", ""), // from mail_add.php in case of error
		 "domain_id"    => array ("request", "integer", ""), 
		 "show_systemmails"    => array ("request", "integer", ""), 
		 "search"    => array ("request", "string", ""),
		 "offset"    => array ("request", "integer", 0),
		 "count"    => array ("request", "integer", 50),
		 );

$champs=getFields($fields);

$counts=array("10" => "10", "20" => "20", "30" => "30", "50" => "50", "100" => "100", "200" => "200", "500" => "500", "1000" => "1000");

if(!$domain_id ) {
  include("main.php");
  exit();
}

$fatal=false;

if ($domain=$dom->get_domain_byid($domain_id)) {
  if(!($mails_list = $mail->enum_domain_mails($domain_id,$search,$offset,$count,$show_systemmails)) && $search) {
    $error=$err->errstr();
  }
  $allmails_list = $mail->enum_domain_mails($domain_id,$search,$offset,$count,'true');
} else {
  $error=$err->errstr();
  $fatal=true;
}

if ($fatal) {
  echo "<div class=\"error\">$error</div>";
} else {

  if (!empty($error)) { echo "<p class='error'>$error</p>"; }
?>

<table><tr>
<?php
// Mail creation form
if ($quota->cancreate("mail")) {
?>
<td>
<h3><?php __("Create a new mail account");?></h3>
	<form method="post" action="mail_doadd.php" id="main" name="mail_create">
		<input type="text" class="int intleft" style="text-align: right" name="mail_arg" value="<?php ehe($mail_arg); ?>" size="32" id="mail_arg" maxlength="255" /><span id="emaildom" class="int intright"><?php echo "@".$domain; ?></span>
		<input type="hidden" name="domain_id"  value="<?php echo $domain_id;?>" />
		<input type="submit" name="submit" class="inb add" value="<?php __("Create this email address"); ?>"  onClick="return false_if_empty('mail_arg', '<?php echo addslashes(_("Can't have empty mail."));?>');" />
<?php }?>
<span class="inb configure"><a href="mail_manage_catchall.php?domain_id=<?php echo $domain_id?>"><?php __("Manage Catch-all for this domain");?></a></span> 
<?php if ($quota->cancreate("mail")) { ?>
	</form>
</td>
<?php 
}
?>
<td valign='bottom'>
</td>
</tr>
</table>

<br />
<hr id="topbar"/>
<h3><?php printf(_("Email addresses of the domain %s"),$domain); ?> : </h3>
<?php
if (empty($allmails_list) && empty($search)) {
  echo "<p><i>";
  __("No mails for this domain.");
  echo "</i></p><br/>";
} else {

?>

<table class="searchtable"><tr><td>
<form method="get" name="formlist1" id="formlist1" action="mail_list.php">
<input type="hidden" name="domain_id" value="<?php echo $domain_id; ?>" />
<input type="hidden" name="offset" value="0" />
<span class="int intleft"><img alt="<?php __("Search"); ?>" title="<?php __("Search"); ?>" src="/images/search.png" style="vertical-align: middle"/> </span><input type="text" name="search" value="<?php ehe($search); ?>" size="20" maxlength="64" class="int intright" />
</td><td>
<?php pager($offset,$count,$mail->total,"mail_list.php?domain_id=".$domain_id."&amp;count=".$count."&amp;search=".urlencode($search)."&amp;offset=%%offset%%"); ?>
</td>
<td style="text-align:center">
  <input type="checkbox" id="show_systemmails" name="show_systemmails" <?php if($show_systemmails) {echo "checked";}?> value="1"onclick="document.getElementById('formlist1').submit();" /><label for="show_systemmails" ><?php __("Show system emails");?></label>
</td>
</form>
<td style="text-align:right">
<form method="get" name="formlist2" id="formlist2" action="mail_list.php">
 <input type="hidden" name="domain_id" value="<?php echo $domain_id; ?>" />
 <input type="hidden" name="offset" value="0" />
 <?php __("Items per page:"); ?> <select name="count" class="inl" onchange="submit()"><?php eoption($counts,$count); ?></select>
</form>
</td></tr></table>

<form method="post" action="mail_del.php">
 <input type="hidden" name="domain_id" value="<?php echo $domain_id; ?>" />
<table class="tlist">
<tr><th></th><th></th><th><?php __("Enabled");?></th><th style="text-align:right"><?php __("Address"); ?></th><th><?php __("Pop/Imap"); ?></th><th><?php __("Other recipients"); ?></th><th><?php __("Last login time"); ?></th></tr>
<?php

$col=1; $i=0;
//listing of every mail of the current domain.
if(!empty($mails_list)) {
 while (list($key,$val)=each($mails_list)) {
  $col=3-$col; $grey="";
	?>
	<tr class="lst<?php echo $col; ?>">
	  <?php if ($val["mail_action"]=="DELETING") { $grey="grey"; ?>
	    <td colspan="3"><?php __("Deleting..."); ?></td>
	  <?php } else if ($val["mail_action"]=="DELETE") { $grey="grey"; ?>
	    <td></td>
	    <td> 
              <?php if ($val['type'] =='') { ?>
                <div class="ina"><a href="mail_undelete.php?search=<?php ehe($search); ?>&amp;offset=<?php ehe($offset); ?>&amp;count=<?php ehe($count); ?>&amp;domain_id=<?php ehe($domain_id);  ?>&amp;mail_id=<?php echo $val["id"] ?>" title="<?php __("This email will be deleted soon. You may still be able to undelete it by clicking here"); ?>"><img src="images/undelete.png" alt="<?php __("Undelete"); ?>" /><?php __("Undelete"); ?></a></div>
              <?php } // if val[type] ?>
            </td>
	    <td><img src="images/check_no.png" alt="<?php __("Disabled"); ?>" /></td>	  
	  <?php } else if (!$val["type"]) { ?>
          <td align="center">
	    <input class="inc" type="checkbox" id="del_<?php echo $i; ?>" name="d[]" value="<?php ehe($val["id"]); ?>" />
	</td>
	<td class="<?php echo $grey; ?>">
	  <div class="ina edit"><a href="mail_edit.php?mail_id=<?php echo $val["id"] ?>"><?php __("Edit"); ?></a></div></td>
	<td class="<?php echo $grey; ?>"><?php if ($val["enabled"] ) { ?>
			<img src="images/check_ok.png" alt="<?php __("Enabled"); ?>" />
		<?php } else { ?>
			<img src="images/check_no.png" alt="<?php __("Disabled"); ?>" />
		<?php } // if enabled ?>
	</td>
	<?php } else { ?>
	<td colspan="3"></td>
	<?php } ?>
	<td  class="<?php echo $grey; ?>" style="text-align:right"><?php echo $val["address"]."@".$domain ?></td>
	<?php if ($val["type"]) { ?>
	<td colspan="3"><?php echo $val["typedata"]; ?></td>
	<?php } else { ?>
	<td class="<?php echo $grey; ?>"><?php if ($val["islocal"]) echo format_size($val["used"]).( ($val["quotabytes"]==0)?'':"/".format_size($val["quotabytes"])) ; else __("No"); ?></td>
	<td class="<?php echo $grey; ?>"><?php echo $val["recipients"]; /* TODO : if >60chars, use "..." + js close/open */ ?></td>
  <td class="<?php echo $grey; ?>"><?php if ($val["islocal"]) { 
if (date("Y-m-d")==substr($val["lastlogin"],0,10)) echo substr($val["lastlogin"],11,5); else if (substr($val["lastlogin"],0,10)=="0000-00-00") __("Never"); else echo format_date(_('%3$d-%2$d-%1$d'),$val["lastlogin"]);
} ?></td>
	<?php } ?>
	</tr>
	<?php
   $i++;
 }
}
?>

</table>
  <p><input type="submit" class="inb delete" name="submit" value="<?php __("Delete the checked email addresses"); ?>" /></p>
</form>

<?php
    } } // end if no mail for this domain
?>
<hr/>

<h3><?php __("Mails configuration informations");?></h3>

<?php __("Here are some configuration informations you'll need to configure your mail application.");?>
<br/>
<br/>

<table width='100%' class="tedit">
<tr>
<th width='50%'>
  <b><?php __("Outgoing mail (SMTP)"); ?></b> 
</th>
<th width='50%'>
  <b><?php __("Incoming mails"); ?></b>
</th>
</tr>
<tr>
  <td valign='top'>
    <?php __("Which protocol would you use?"); ?>
    <ul>
      <li><b><a href="javascript:;" onClick="$('#cfg-submission').toggle();"><?php __("Submission");?></a></b>
        <ul id='cfg-submission'>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_submission); ?></li>
          <li><b><?php __("Username: ");?></b> <?php __("The mail address you want to access <i>(example : myuser@example.tld)</i>");?></li>
          <li><b><?php __("Port: ");?></b> 587</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </li>
      <li><b><a href="javascript:;" onClick="$('#cfg-smtp').toggle();"><?php __("SMTP");?></a></b>
        <ul id='cfg-smtp'>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_smtp); ?></li>
          <li><b><?php __("Username: ");?></b> <?php __("The mail address you want to access <i>(example : myuser@example.tld)</i>");?></li>
          <li><b><?php __("Port: ");?></b> 25</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal Password")?></li>
          <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </li>
       <li><b><a href="javascript:;" onClick="$('#cfg-smtps').toggle();"><?php __("SMTPS");?></a></b>
        <ul id='cfg-smtps'>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_smtps); ?></li>
          <li><b><?php __("Username: ");?></b> <?php __("The mail address you want to access <i>(example : myuser@example.tld)</i>");?></li>
          <li><b><?php __("Port: ");?></b> 465</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal Password")?></li>
          <li><b><?php __("Connection security:");?></b> SSL</li>
        </ul>
      </li>
    </ul>
  </td>
  <td valign='top'>
    <?php __("Which protocol would you use?"); ?>
    <ul>
      <li><b><a href="javascript:;" onClick="$('#cfg-imap').toggle();"><?php __("IMAP");?></a></b>
        <ul id='cfg-imap'>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_imap); ?></li>
          <li><b><?php __("Port: ");?></b> 143</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </li>

      <li><b><a href="javascript:;" onClick="$('#cfg-imaps').toggle();"><?php __("IMAPS");?></a></b>
        <ul id='cfg-imaps'>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_imaps); ?></li>
          <li><b><?php __("Port: ");?></b> 993</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ")?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> SSL</li>
        </ul>
      </li>

      <li><b><a href="javascript:;" onClick="$('#cfg-pop3').toggle();"><?php __("POP3");?></a></b>
        <ul id='cfg-pop3'>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_pop3); ?></li>
          <li><b><?php __("Port: ");?></b> 110</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </li>

      <li><b><a href="javascript:;" onClick="$('#cfg-pop3s').toggle();"><?php __("POP3S");?></a></b>
        <ul id='cfg-pop3s'>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_pop3s); ?></li>
          <li><b><?php __("Port: ");?></b> 995</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> SSL</li>
        </ul>
      </li>
    </ul>
  </td>
</tr>
</table>

<script type="text/javascript">
  $('#cfg-submission').toggle();
  $('#cfg-smtp').toggle();
  $('#cfg-smtps').toggle();
  $('#cfg-imap').toggle();
  $('#cfg-imaps').toggle();
  $('#cfg-pop3').toggle();
  $('#cfg-pop3s').toggle();
</script>


<?php include_once("foot.php"); ?>
