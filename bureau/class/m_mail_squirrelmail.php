<?php 

/* 
 * Proof of concept of what a new feature look like with the new mail interface
 *
**/

Class m_mail_squirrelmail{
  var $advanced;
  var $enabled;

  function m_mail_squirrelmail(){
    // Get configuration var
  }

  function hooks_squirrelmail_init($mail,$dom){
		global $err,$cuid,$db;
    $err->log("mail_squirrelmail","squirrelmail_init",$mail."@".$dom);
    $m=substr($mail,0,1);
    $gecos=$mail;
    if (!$mail) {
      // Cas du CATCH-ALL
      $gecos="Catch-All";
      $m="_";
    }

		
    $f=fopen("/var/lib/squirrelmail/data/".$mail."_".$dom.".pref","wb");
    $g=0; $g=@fopen("/etc/squirrelmail/default_pref","rb");
    fputs($f,"email_address=$mail@$dom\nchosen_theme=default_theme.php\n");
    if ($g) {
      while ($s=fgets($g,1024)) {
	      if (substr($s,0,14)!="email_address=" && substr($s,0,13)!="chosen_theme=") {
	        fputs($f,$s);
      	}
      }
      fclose($g);
    }
    fclose($f);
    @copy("/var/lib/squirrelmail/data/".$mail."_".$dom.".pref","/var/lib/squirrelmail/data/".$mail."@".$dom.".pref");
 		return true; 
	}

	function hooks_squirrelmail_delete($mail,$dom){
		global $err,$cuid,$db;
    $err->log("mail_squirrelmail","squirrelmail_delete",$mail."@".$dom);

    @unlink("/var/lib/squirrelmail/data/".$mail."_".$dom.".pref");
    @unlink("/var/lib/squirrelmail/data/".$mail."_".$dom.".abook");
    @unlink("/var/lib/squirrelmail/data/".$mail."@".$dom.".pref");
    @unlink("/var/lib/squirrelmail/data/".$mail."@".$dom.".abook");
		return true;

	}

}

?>
