<?php
// Created by Alesandro Slepcevic - alesandro@plus.hr
//header(':', true, 405);
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){ 
    $postText = file_get_contents('php://input'); 
$string = $postText;
$matches = array();
$pattern = '/[A-Za-z0-9_-]+@[A-Za-z0-9_-]+.([A-Za-z0-9_-][A-Za-z0-9_]+)/'; 
preg_match($pattern, $string, $matches); 
$emailDomain = explode('@', $matches[0]);

$f=fopen("/tmp/autodiscover.log","ab");
fputs($f,"Posted: \n".$string."\n");
fclose($f);
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
		<Server><?php echo exec('hostname -f');?></Server>
		<Port>993</Port>
		<LoginName><?php echo $matches[0];?></LoginName>
		<DomainName><?php echo $emailDomain[1];?></DomainName>
		<SPA>off</SPA>
		<SSL>on</SSL>
		<AuthRequired>on</AuthRequired>
	</Protocol>
	<Protocol>
		<Type>SMTP</Type>
		<Server><?php echo exec('hostname -f');?></Server>
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
