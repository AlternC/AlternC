#!/usr/bin/php5	-q
<?php
require_once("/usr/share/alternc/panel/class/config_nochk.php");

#arguments can be a mailbox or a domain	 or a login
$options = getopt('m:l:d:');

print_r($options);
#parser les arguments correctement.
#We check that only onei type of option is specified
$nb=count($options);

if ( $nb != 1 ){
  echo "usage : script -[m|l|d]\n";
  exit(1);
}

#we check that  for that type only one option is specified
foreach($options as $opt => $val){
   $nb2=count($options[$opt]);
}

if ( $nb2 != 1 ){
  echo "usage : script -[m|l|d]\n";
  exit(1);
}


#function taking a query used to select the mailbox(es) root and updating their quotas into the mailbox table
function FixQuotaDovecot($query){
	global $db;
	$db2=new DB_System();

	if(!$db->query($query)){
  	echo "failed";
		exit(1);
	}
  while ($db->next_record()) {
  	$dir=$db->f("dir");
  	$id=$db->f("id");
		$size = exec ( "/usr/bin/du -sb $dir|cut -f1" );
		if(!$db2->query("UPDATE mailbox set bytes=$size where id=$id;")){
			echo "fail updating quota for mailbox :".$id."\n";
		}
  }

}

#We construct a sql query to get the mailbox root based on the option.
switch($opt){
	case "m":
		if (!filter_var($val,FILTER_VALIDATE_EMAIL)) {
			echo " the email you entered is syntaxically incorrect\n";
      exit(1);
    }
		$query="select mailbox.id,concat(path, '/Maildir/') as dir from mailbox 
							join address on address.id = mailbox.address_id 
							join domaines on domaines.id = address.domain_id 
							where concat(address.address,'@',domaines.domaine) ='".$val."';";
		break;
  case "l":
		$login=strtolower($val);
    if (!preg_match("#^[a-z0-9]+$#",$login)) { //$
			echo " the login you entered is syntaxically incorrect\n";
      exit(1);
    }
		$query=("select mailbox.id,concat(path, '/Maildir/') as dir from mailbox
							join address on mailbox.address_id = address.id
							join domaines on address.domain_id = domaines.id
							join membres on domaines.compte = membres.uid where membres.login = '".$login."';");
		break;
  case "d":
		if(checkfqdn($val) != 0){
			echo " the domain you entered is syntaxically incorrect\n";
      exit(1);
		}
		$query="select mailbox.id,concat(path, '/Maildir/') as dir from mailbox  
							join address on mailbox.address_id = address.id
							join domaines on address.domain_id = domaines.id where domaines.domaine = '".$val."' ;";
		print_r($query);
		break;
  default:
    echo "usage : script -[m|l|d]\n";
    exit(1);
}

FixQuotaDovecot($query);

exit(0);

?>
