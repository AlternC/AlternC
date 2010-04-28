<?php
/*
 $Id: menu_mail.php,v 1.3 2004/05/19 14:23:06 benjamin Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/

/* ############# MAILS ############# */

$q = $quota->getquota("mail");
$r = $quota->getquota("dom");
if ($q["t"] > 0 && $r["u"] > 0) {

?>
<div class="menu-box">
<div class="menu-title">
<img src="images/mail.png" alt="<?php __("Email Addresses"); ?>" />&nbsp;<?php __("Email Addresses"); ?> (<?= $q["u"]; ?>/<?= $q["t"]; ?>)</div>
<div class="menu-content" id="menu-mail">
<ul>
<?php
	
/* Enumeration des domlistes en mail : */
$domlist = $mail->enum_domains();
reset($domlist);
while (list($key, $val) = each($domlist)) {
$res = $mail->enum_doms_mails($val, 1);
	
?>
	<li><a href="mail_list.php?domain=<?php echo urlencode($val) ?>"><?php echo $val ?> (<?= $res["count"]; ?>)</a></li>
<?php } ?>
</ul>
</div>
</div>
<?php } ?>