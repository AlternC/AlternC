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
 * Show email autoconfiguration xml data for Outlook / Email for windows
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config_nochk.php");

/*
 Test it that way :
 wget http://FQDN/mailautoconfig_outlook.php -O - --post-data="test@example.tls" -q
*/

// Created by Alesandro Slepcevic - alesandro@plus.hr
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){ 
  $postText = file_get_contents('php://input'); 
  $string = $postText;
  $matches = array();
  $pattern = '/[A-Za-z0-9_-]+@[A-Za-z0-9_-]+.([A-Za-z0-9_-][A-Za-z0-9_]+)/'; 
  preg_match($pattern, $string, $matches); 
  $emailDomain = explode('@', $matches[0]);
} else {
  die(__('Missing POST of the mail address', "alternc", true));
}

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?> \n";
?>
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
<Response xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a">
	<Account>
	<AccountType>email</AccountType>
	<Action>settings</Action>
	<Protocol>
		<Type>IMAP</Type>
		<Server><?php echo $mail->srv_dovecot; ?></Server>
		<Port>993</Port>
		<LoginName><?php echo $matches[0];?></LoginName>
		<DomainName><?php echo $emailDomain[1];?></DomainName>
		<SPA>off</SPA>
		<SSL>on</SSL>
		<AuthRequired>on</AuthRequired>
	</Protocol>
	<Protocol>
		<Type>SMTP</Type>
		<Server><?php echo $mail->srv_postfix; ?></Server>
		<Port>587</Port>
		<SPA>off</SPA>
		<SSL>on</SSL>
		<AuthRequired>on</AuthRequired>
		<UsePOPAuth>on</UsePOPAuth>
		<SMTPLast>off</SMTPLast>
	</Protocol>
	</Account>
</Response>
</Autodiscover>
