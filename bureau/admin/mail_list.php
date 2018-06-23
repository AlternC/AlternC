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
 * List the email account of a domain 
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
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

if ($domain=$dom->get_domain_byid($domain_id)) {
  $mails_list = $mail->enum_domain_mails($domain_id,$search,$offset,$count,$show_systemmails);
  $allmails_list = $mail->enum_domain_mails($domain_id,$search,$offset,$count,'true');
}
?>

<table>
  <tr>
    <td colspan=2>
<?php if ($quota->cancreate("mail")) {
  echo '<h3>'._("Create a new mail account")."</h3>";
} else {
  echo '<h3>'._("Manage Catch-all")."</h3>";
}

echo $msg->msg_html_all(true, true);
?>
    </td>
  </tr>
  <tr>
    <td>
<?php if ($quota->cancreate("mail")) { ?>
    <form method="post" action="mail_doadd.php" id="main" name="mail_create">
    <?php csrf_get(); ?>
      <input type="text" class="int intleft" style="text-align: right" name="mail_arg" value="<?php ehe($mail_arg); ?>" size="24" id="mail_arg" maxlength="255" /><span id="emaildom" class="int intright"><?php echo "@".$domain; ?></span>
      <input type="hidden" name="domain_id"  value="<?php ehe($domain_id); ?>" />
      <input type="submit" name="submit" class="inb add" value="<?php __("Create this email address"); ?>"  onClick="return false_if_empty('mail_arg', '<?php echo addslashes(_("Can't have empty mail."));?>');" />
    </form>
<?php } // $quota->cancreate("mail") ?>
    </td>
    <td>
      <span class="inb settings" valign='bottom'><a href="mail_manage_catchall.php?domain_id=<?php echo $domain_id?>"><?php __("Manage Catch-all for this domain");?></a></span> 
    </td>
  </tr>
</table>

<br />
<hr id="topbar"/>
<h3><?php printf(_("Email addresses of the domain %s"),$domain); ?> : </h3>
<?php
if (empty($allmails_list) && empty($search)) {
  $msg->raise("ERROR", 'mail', _("No mails for this domain."));
  echo $msg->msg_html_all();
} else {

?>

<table class="searchtable"><tr><td>
<form method="get" name="formlist1" id="formlist1" action="mail_list.php">
<input type="hidden" name="domain_id" value="<?php ehe($domain_id); ?>" />
<input type="hidden" name="offset" value="0" />
<span class="int intleft"><img alt="<?php __("Search"); ?>" title="<?php __("Search"); ?>" src="/images/search.png" style="vertical-align: middle"/> </span><input type="text" name="search" value="<?php ehe($search); ?>" size="20" maxlength="64" class="int intright" />
</td><td>
<?php pager($offset,$count,$mail->total,"mail_list.php?domain_id=".$domain_id."&amp;count=".$count."&amp;search=".urlencode($search)."&amp;offset=%%offset%%"); ?>
</td>
<td style="text-align:center">
  <input type="checkbox" id="show_systemmails" name="show_systemmails" <?php if($show_systemmails) { echo "checked"; } ?> value="1" onclick="document.getElementById('formlist1').submit();" /><label for="show_systemmails" ><?php __("Show system emails");?></label>
</td>
</form>
<td style="text-align:right">
<form method="get" name="formlist2" id="formlist2" action="mail_list.php">
 <input type="hidden" name="domain_id" value="<?php ehe($domain_id); ?>" />
 <input type="hidden" name="offset" value="0" />
 <?php __("Items per page:"); ?> <select name="count" class="inl" onchange="submit()"><?php eoption($counts,$count); ?></select>
</form>
</td></tr></table>

<form method="post" action="mail_del.php">
   <?php csrf_get(); ?>
 <input type="hidden" name="domain_id" value="<?php ehe($domain_id); ?>" />
<table class="tlist">
<tr><th></th><th></th><th><?php __("Enabled");?></th><th style="text-align:right"><?php __("Address"); ?></th><th><?php __("Pop/Imap"); ?></th><th><?php __("Other recipients"); ?></th><th><?php __("Last login time"); ?></th></tr>
<?php

$i=0;
//listing of every mail of the current domain.
if(!empty($mails_list)) {
 while (list($key,$val)=each($mails_list)) {
  $grey="";
	?>
	<tr class="lst">
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
	    <input class="inc" type="checkbox" id="del_<?php ehe($i); ?>" name="d[]" value="<?php ehe($val["id"]); ?>" />
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
    } // end if no mail for this domain
?>
<hr/>

<h3><?php __("Mails configuration information");?></h3>

<?php __("Here are some configuration information you will need to configure your mail application.");?>
<br/>
<br/>

<div id="tabs-mailhelp">

<ul>
  <li class="help"><a href="#tabs-mailhelp-out"><?php __("Outgoing mail (SMTP)"); ?></a></li>
  <li class="help"><a href="#tabs-mailhelp-in"><?php __("Incoming mail"); ?></a></li>
</ul>

<div id="tabs-mailhelp-out">

    <?php __("Which protocol shall you use?"); ?>
    <div id="accordion-mailout">
      <?php if ($mail->srv_postfix) { ?>
      <h4><?php __("Submission");?></h4>
      <div>
        <ul>
        <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_postfix); ?></li>
        <li><b><?php __("Username: ");?></b> <?php __("The mail address you want to access <i>(example : myuser@example.tld)</i>");?></li>
        <li><b><?php __("Port: ");?></b> 587</li>
        <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
        <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
        <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </div>
      <?php } ?>
      <?php if ($mail->srv_postfix) { ?>
      <h4><?php __("SMTP");?></h4>
      <div>
        <ul>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_postfix); ?></li>
          <li><b><?php __("Username: ");?></b> <?php __("The mail address you want to access <i>(example : myuser@example.tld)</i>");?></li>
          <li><b><?php __("Port: ");?></b> 25</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal Password")?></li>
          <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </div>
      <?php } ?>
      <?php if ($mail->srv_postfix) { ?>
      <h4><?php __("SMTPS");?></h4>
      <div>
        <ul>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_postfix); ?></li>
          <li><b><?php __("Username: ");?></b> <?php __("The mail address you want to access <i>(example : myuser@example.tld)</i>");?></li>
          <li><b><?php __("Port: ");?></b> 465</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal Password")?></li>
          <li><b><?php __("Connection security:");?></b> SSL</li>
        </ul>
      </div>
      <?php } ?>
    </div><!-- accordion-mailout -->

</div><!-- tabs-mailhelp-out -->

<div id="tabs-mailhelp-in">

    <?php __("Which protocol shall you use?"); ?>
    <div id="accordion-mailin">
      <h4><?php __("IMAP");?></h4>
      <div>
        <ul>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_dovecot); ?></li>
          <li><b><?php __("Port: ");?></b> 143</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </div>

      <h4><?php __("IMAPS");?></h4>
      <div>
        <ul>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_dovecot); ?></li>
          <li><b><?php __("Port: ");?></b> 993</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ")?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> SSL</li>
        </ul>
      </div>

      <h4><?php __("POP3");?></h4>
      <div>
        <ul>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_dovecot); ?></li>
          <li><b><?php __("Port: ");?></b> 110</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> STARTTLS</li>
        </ul>
      </div>

      <h4><?php __("POP3S");?></h4>
      <div>
        <ul>
          <li><b><?php __("Server name: ");?></b> <?php __($mail->srv_dovecot); ?></li>
          <li><b><?php __("Port: ");?></b> 995</li>
          <li><b><?php __("Authentication: ");?></b><?php __("Yes")?></li>
          <li><b><?php __("Authentication method: ");?></b><?php __("Normal password")?></li>
          <li><b><?php __("Connection security:");?></b> SSL</li>
        </ul>
      </div>
    </div>
</div><!-- tabs-mailhelp-in -->
</div><!-- tabs-mailhelp -->

<script type="text/javascript">

  $(function() {
    $( "#accordion-mailout" ).accordion({
      collapsible: true, active: false, header: "h4", heightStyle: "content"
    });
  });

  $(function() {
    $( "#accordion-mailin" ).accordion({
      collapsible: true, active: false, header: "h4", heightStyle: "content"
    });
  });


$(function() {$( "#tabs-mailhelp" ).tabs();});

</script>


<?php include_once("foot.php"); ?>
