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
 * Show email autoconfiguration xml data for Thunderbird 
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config_nochk.php");

header ("Content-Type:text/xml");

/*
 Test it that way:
 wget -O - -q  http://FQDN/mailautoconfig_thunderbird.php?emailaddress=test@example.tld
*/

if (empty($_GET['emailaddress'])) die(_("Error: Missing GET of emailaddress"));

$emailDomain = explode('@', rawurldecode($_GET['emailaddress']));
if (empty($emailDomain)) die(_('Error: Empty $emailDomain'));
?>
<clientConfig version="1.1">
<emailProvider id="<?php echo $L_FQDN ?>">
<domain><?php echo $emailDomain[1];?></domain>
<displayName><?php echo $L_FQDN ?></displayName>
<displayShortName><?php echo $L_FQDN ?></displayShortName>
	<incomingServer type="imap">
		<hostname><?php echo $mail->srv_dovecot ;?></hostname>
		<port>143</port>
		<socketType>STARTTLS</socketType>
		<authentication>password-cleartext</authentication>
		<username>%EMAILADDRESS%</username>
	</incomingServer>
	<incomingServer type="pop3">
		<hostname><?php echo $mail->srv_dovecot;?></hostname>
		<port>110</port>
		<socketType>STARTTLS</socketType>
		<authentication>password-cleartext</authentication>
		<username>%EMAILADDRESS%</username>
	</incomingServer>
	<outgoingServer type="smtp">
		<hostname><?php echo $mail->srv_postfix;?></hostname>
		<port>587</port>
		<socketType>STARTTLS</socketType>
		<username>%EMAILADDRESS%</username>
		<authentication>password-cleartext</authentication>
	</outgoingServer>
	<outgoingServer type="smtp">
		<hostname><?php echo $mail->srv_postfix;?></hostname>
		<port>465</port>
		<socketType>SSL</socketType>
		<authentication>password-cleartext</authentication>
		<username>%EMAILADDRESS%</username>
	</outgoingServer>
</emailProvider>
</clientConfig>
