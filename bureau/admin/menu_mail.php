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
*/

/* ############# MAILS ############# */

$q = $quota->getquota("mail");
$r = $quota->getquota("dom");
// there are some existing mail, or there is a domain AND quota authorize mail
if ($q["u"] > 0 || ( $r["u"] > 0 && $q['t'] > 0 )) {

?>
<div class="menu-box">
<a href="javascript:menu_toggle('menu-mail');">
	<div class="menu-title">
	<img src="images/mail.png" alt="<?php __("Email Addresses"); ?>" />&nbsp;<?php __("Email Addresses"); ?> (<?php echo $q["u"]; ?>/<?php echo $q["t"]; ?>)
	<img src="/images/menu_moins.png" alt="" style="float:right;" id="menu-mail-img"/>
	</div>
</a>
<div class="menu-content" id="menu-mail">
<ul>
<?php
	
/* Enumeration des domlistes en mail : */
$domlist = $mail->enum_domains();
foreach($domlist as $l => $v){
?>
  <li><a href="mail_list.php?domain_id=<?php echo urlencode($v["id"]) ?>" title='<?php echo htmlentities($v["domaine"]).'&nbsp;'.htmlentities("(".$v["nb_mail"].")"); ?>'><?php echo $v['domaine']; ?> (<?php echo $v["nb_mail"]; ?>)</a></li>
<?php
}?>
</ul>
</div>
</div>
<?php
} // fin du if pour les quotas 
?>
