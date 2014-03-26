#!/usr/bin/php
<?php
require_once("/usr/share/alternc/panel/class/config_nochk.php");

/**
 * @param string $msg
 */
function usage($msg=null) {
    if ($msg) {
      echo "Error:\n$msg";
    }
    echo "usage : script -[m|l|d]\n";
}

#arguments can be a mailbox or a domain     or a login
$options = getopt('m:l:d:');

#parser les arguments correctement.
#We check that only one type of option is specified
$nb=count($options);

if ( $nb != 1 ){
    usage();
    exit(1);
}

#we check that  for that type only one option is specified
foreach($options as $opt => $val){
    $nb2=count($options[$opt]);
}

if ( $nb2 != 1 ){
    usage();
    exit(1);
}

#function taking a query used to select the mailbox(es) root and updating their quotas into the mailbox table
function FixQuotaDovecot($conditions){
    global $db;
    $db2=new DB_System();
    $query="SELECT mailbox.id,concat(path, '/Maildir/') as dir 
            FROM 
              mailbox 
              join address on address.id = mailbox.address_id 
              join domaines on domaines.id = address.domain_id
              $conditions ;";

    if(!$db->query($query)){
        usage("failed"); // FIXME real error
        exit(1);
    }
    while ($db->next_record()) {
        $dir=$db->f("dir");
        $id=$db->f("id");
        $size = exec ( "/usr/bin/du -sb $dir|cut -f1" ); // FIXME check return value
        if(!$db2->query("UPDATE mailbox set bytes=".intval($size)." where id=".intval($id).";")){
            echo "Fail updating quota for mailbox : $id\n";
        }
    }

}

#We construct a sql query to get the mailbox root based on the option.
switch($opt){
    case "m":
        if (!filter_var($val,FILTER_VALIDATE_EMAIL)) {
            usage("The email you entered is syntaxically incorrect");
            exit(1);
        }
        $cond = "WHERE concat(address.address,'@',domaines.domaine) ='".$val."'" ;
        break;
    case "l":
        $login=strtolower($val);
        if (!preg_match("#^[a-z0-9]+$#",$login)) { //FIXME use an alternc function for that
          usage("the login you entered is syntaxically incorrect");
          exit(1);
        }
        $cond = "join membres on domaines.compte = membres.uid WHERE membres.login = '".mysql_real_escape_string($login)."'";
        break;
    case "d":
        if(checkfqdn($val) != 0){
            usage("The domain you entered is syntaxically incorrect");
            exit(1);
        }
        $cond = "WHERE domaines.domaine = '".mysql_real_escape_string($val)."'" ;
        break;
    default:
        usage();
        exit(1);
}

FixQuotaDovecot($cond);

exit(0);

?>
