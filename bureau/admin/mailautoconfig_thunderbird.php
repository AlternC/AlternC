<?php
header ("Content-Type:text/xml");
$emailDomain = explode('@', rawurldecode($_GET['emailaddress']));
?>
<clientConfig version="1.1">
<emailProvider id="octopuce.fr">
<domain><?php echo $emailDomain;?></domain>
<displayName>Octopuce SARL</displayName>
<displayShortName>Octopuce</displayShortName>
	<incomingServer type="imap">
		<hostname><?php echo exec('hostname -f');?></hostname>
		<port>993</port>
		<socketType>SSL</socketType>
		<authentication>password-cleartext</authentication>
		<username>%EMAILADDRESS%</username>
	</incomingServer>
	<incomingServer type="pop3">
		<hostname><?php echo exec('hostname -f');?></hostname>
		<port>995</port>
		<socketType>SSL</socketType>
		<authentication>password-cleartext</authentication>
		<username>%EMAILADDRESS%</username>
	</incomingServer>
	<outgoingServer type="smtp">
		<hostname><?php echo exec('hostname -f');?></hostname>
		<port>587</port>
		<socketType>STARTTLS</socketType>
		<username>%EMAILADDRESS%</username>
		<authentication>password-cleartext</authentication>
	</outgoingServer>
	<outgoingServer type="smtp">
		<hostname><?php echo exec('hostname -f');?></hostname>
		<port>465</port>
		<socketType>SSL</socketType>
		<authentication>password-cleartext</authentication>
		<username>%EMAILADDRESS%</username>
	</outgoingServer>
</emailProvider>
</clientConfig>
