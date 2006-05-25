<?php
/*
 $Id: m_mail.php,v 1.31 2006/01/12 06:26:16 anarcat Exp $
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 Purpose of file: Manage Email accounts and aliases.
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des comptes mails de l'hébergé.
*
* Cette classe permet de gérer les comptes pop, alias
* mail des domaines d'un membre hébergé.<br />
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/
class m_mail {

  /** Liste des domaines
   * @access private
   */
  var $domains;


  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_mail() {
  }

  /* ----------------------------------------------------------------- */
  /**
   * Liste des quotas
   */
  function alternc_quota_names() {
    return "mail";
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des domaines hébergés en mails sur le compte.
   * @return array Tableau indexé des domaines hébergés en mail.
   */
  function enum_domains() {
    global $db,$err,$cuid;
    $err->log("mail","enum_domains");
    if (!is_array($this->domains)) {
      $db->query("select * from domaines where compte='$cuid' AND gesmx=1;");
      $this->domains=array();
      if ($db->num_rows()>0) {
	while ($db->next_record()) {
	  $this->domains[]=$db->f("domaine");
	}
      }
    }
    return $this->domains;
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des lettres pour lesquelles un domaine $dom a
   * des e-mails
   * Retourne un tableau indexé où se trouvent les lettres
   * @param string $dom Domaine dont on veut les premières lettres des mails
  * @return array Tableau de lettres ou FALSE si erreur
   */
  function enum_doms_mails_letters($dom) {
    global $err,$cuid,$db;
    $err->log("mail","enum_doms_mails_letters",$dom);
    $db->query("SELECT LEFT(mail,1) as letter FROM mail_domain where uid='$cuid' AND type=0 and mail like '%@".addslashes($dom)."' GROUP BY letter ORDER BY letter;");
    $res=array();
    while($db->next_record()) {
      $res[]=$db->f("letter");
    }
    return $res;
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des mails du domaine $dom et si une lettre est
   * définie, cela retourne les mail qui commencent par celle ci
   * Retourne un tableau indexé de tableaux associatifs sous la forme :
   * $a["mail"]=Adresse email
   * $a["pop"]=1 ou 0 selon s'il s'agit d'un compte pop ou pas
   * $a["size"]=taille en octets de la boite s'il s'agit d'un compte pop.
   * @param string $dom Domaine dont on veut les mails
   * @param integer $sort Champs de tri (0 pour non trié (default), 1 pour email, 2 pour type)
   * @return array Tableau de mails comme indiqué ci-dessus ou FALSE si une erreur
   *  s'est produite
   */
  function enum_doms_mails($dom,$sort=0,$letter="") {
    global $err,$cuid,$db;
    $err->log("mail","enum_doms_mails",$dom);
    $db->query("SELECT mail,pop,alias FROM mail_domain WHERE mail LIKE '".addslashes($letter)."%@".addslashes($dom)."' AND uid='$cuid' AND type=0;");
    $res=array(); $i=0;
    while ($db->next_record()) {
      if ($db->f("pop")) { 
	$size=0;
        $r=mysql_query("SELECT size FROM size_mail WHERE alias='".str_replace("@","_",$db->f("mail"))."';");
        list($size)=@mysql_fetch_array($r);
        $size=$size*1024;
      } else $size=0;
      if ($db->f("pop")) {
	$login=str_replace("@","_",$db->f("mail"));
	$account=str_replace($login,"",$db->f("alias")); 
      } else {
	$account=$db->f("alias");
      }
      $res[]=array("mail" => $db->f("mail"), "pop" => $db->f("pop"), 
		   "alias"=>$account,"size"=>$size);
      $i++;
    }
    if ($sort==1) {
      usort($res,array("m_mail","_cmp_mail"));
    }
    if ($sort==2) {
      usort($res,array("m_mail","_cmp_type"));
    }
    $res["count"]=$i;
    return $res;
  }

  function _cmp_mail($a, $b)
    {
      $al = strtolower($a["mail"]);
      $bl = strtolower($b["mail"]);
      if ($al == $bl) return 0;
      return ($al > $bl) ? +1 : -1;
    }
  function _cmp_type($a, $b)
    {
      $al = strtolower($a["pop"]);
      $bl = strtolower($b["pop"]);
      if ($al == $bl) {
	$al = strtolower($a["mail"]);
	$bl = strtolower($b["mail"]);
	if ($al == $bl) return 0;
      }
      return ($al > $bl) ? +1 : -1;
    }

  /* ----------------------------------------------------------------- */
  /** Retourne les détails d'un mail
   * Le mail $mail est retourné sous la forme d'un tableaau associatif comme suit :
   * $a["mail"]= Adresse email
   * $a["login"]= Login pop
   * $a["password"]= Mot de passe pop (crypté)
   * $a["alias"]= Alias destination, 1 par ligne
   * $a["pop"]= 1 ou 0 s'il s'agit d'un compte pop
   * @param string $mail Mail dont on veut retourner le détail
   * @return array Tableau associatif comme ci-dessus.
   */
  function get_mail_details($mail) {
    global $err,$db,$cuid;
    $err->log("mail","get_mail_details",$mail);
    $db->query("SELECT mail,pop,alias FROM mail_domain WHERE mail='$mail' AND uid='$cuid';");
    if (!$db->next_record()) { 
      $err->raise("mail",3,$mail);
      return false;
    }
    $pop=$db->f("pop"); 
    if ($pop) {
      $login=str_replace("@","_",$db->f("mail"));
      $account=str_replace($login,"",$db->f("alias")); 
    } else {
      $account=$db->f("alias");
    }
    return array("mail" => $mail, "login" => $login, "alias" => $account, "pop" => $pop);
  }

  /*****************************************************************************/
  /** Tell if a mail is available or not */
  function available($mail) {
    global $err,$db,$cuid;
    $err->log("mail","available",$mail);
    $db->query("SELECT mail FROM mail_domain WHERE mail='$mail';");
    if ($db->next_record()) {
      return false;
    } else {
      return true;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Crée un mail 'executeur' (wrapper) pour $login@$domain
   * @param string $login partie gauche du @ pour le mail concerné
   * @param string $domain partie droite du @ pour le mail concerné
   * @param string $command Commande à exécuter, sans le | ni les "" (commande brute)
   * @param string $type ignoré désormais (plus de ldap...) TODO : remplacer cela par une appropriation de wrapper par une classe (donc mettre dans ce champ le nom de la classe appelante ? )
   * @return boolean TRUE si le wrapper a été créé, FALSE si une erreur s'est produite.
   */
  function add_wrapper($login,$domain,$command,$type="") {
    global $err,$cuid,$db;
    if (!$this->available($login."@".$domain)) {
      $err->raise("mail",7,$login."@".$domain);
      return false;
    }
    $db->query("INSERT INTO mail_domain (mail,alias,uid,pop,type) VALUES ('".$login."@".$domain."','".$login."_".$domain."','$cuid',0,1);");
    $db->query("INSERT INTO mail_alias (mail,alias) VALUES ('".$login."_".$domain."','\"| $command\"');");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Efface un mail 'executeur' (wrapper) pour $login@$domain
   * @param string $login partie gauche du @ pour le mail concerné
   * @param string $domain partie droite du @ pour le mail concerné
   * @return boolean TRUE si le wrapper a été effacé, FALSE si une erreur s'est produite.
   */
  function del_wrapper($login,$domain) {
    global $err,$cuid,$db;
    $db->query("DELETE FROM mail_domain WHERE mail='".$login."@".$domain."' AND uid='$cuid' AND type=1;");
    $db->query("DELETE FROM mail_alias WHERE mail='".$login."_".$domain."';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Change le mot de passe du compte pop $mail
   * @param string $mail Compte mail concerné
   * @param string $pass Nouveau mot de passe
   * @return boolean TRUE si le mot de passe a été changé, FALSE si une erreur s'est produite.
   */
  function change_password($mail,$pass) {
    global $err,$db,$cuid;
    $err->log("mail","change_password",$mail);
    $t=explode("@",$mail);
    $email=$t[0];
    $dom=$t[1];
    $db->query("SELECT mail,alias,pop FROM mail_domain WHERE mail='$mail' AND uid='$cuid';");
    if (!$db->next_record()) {
      $err->raise("mail",3,$mail);
      return false;
    }
    if (!$db->f("pop")) {
      $err->raise("mail",15);
      return false;
    }
    if (!$this->_updatepop($email,$dom,$pass)) {
      return false;
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Modifie les paramètres d'un compte email
   * Tout peut être modifié dans l'email (sauf l'adresse elle-même)
   * @param string $mail Adresse à modifier. Le domaine doit appartenir au membre
   * @param integer $pop Doit-il etre un compte pop (1) ou juste un alias (0)
   * @param string $pass Nouveau mot de passe pop, si pop=1
   * @param string $alias Liste des destinataires auxiliaires, un par ligne.
   * @return boolean TRUE si l'email a bien été modifié, FALSE si une erreur s'est produite.
   */
  function put_mail_details($mail,$pop,$pass,$alias) {
    global $err,$cuid,$db;
    $err->log("mail","put_mail_details",$mail);
    $mail=strtolower($mail);
    $t=explode("@",$mail);
    $email=$t[0];
    $dom=$t[1];

    $account=array();

    if ($pop) $pop="1"; else $pop="0";
    //vérifie si les champs obligatoires sont renseignés
    if ($pop=="0" && $alias=="") {
      $err->raise("mail",4);
      return false;
    }
    if ($pop=="1"){
      $account[]=$email."_".$dom;
    }
    //vérifie la validité des alias :
    if ($alias){
      $a=explode("\n",$alias);
      if (count($a)>0) {
	reset($a);
	for ($i=0;$i<count($a);$i++){
	  $a[$i]=trim($a[$i]);
	  if ($a[$i]){
	    if(checkmail($a[$i])>1){
	      $err->raise("mail",14);
	      return false;
	    }
	  }
	  $account[]=$a[$i];
	}
      }
    }

    $db->query("SELECT mail,alias,pop FROM mail_domain WHERE mail='$mail' AND uid='$cuid' AND type=0;");
    if (!$db->next_record()) {
      $err->raise("mail",3,$mail);
      return false;
    }
    $oldpop= $db->f("pop");
    // When we CREATE a pop account, we MUST give a password
    if ($pop=="1" && $oldpop!=1) {
      if (!$pass) {
	$err->raise("mail",4);
	return false;
      }
    }

    $db->query("UPDATE mail_domain SET alias='".implode("\n",$account)."', pop='$pop' WHERE mail='$mail';");

    if ($pop=="1" && $oldpop!=1) { /* Creation du compte pop */
      if (!$this->_createpop($email,$dom,$pass)) {
	return false;
      }
    }
    if ($pop!="1" && $oldpop==1) { /* Destruction du compte pop */
      if (!$this->_deletepop($email,$dom)) {
	return false;
      }
    }
    if ($pop=="1" && $oldpop==1 && $pass!="") { /* Modification du compte pop */
      if (!$this->_updatepop($email,$dom,$pass)) {
	return false;
      }
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Crée un compte email $mail sur le domaine $dom
   * @param string $dom Domaine concerné, il doit appartenir au membre
   * @param string $mail Email à créer, il ne doit pas exister ni en mail, ni en liste.
   * @param integer $pop vaut 1 pour créer un compte pop, 0 pour un alias
   * @param string $alias Liste des alias, un par ligne
   * @return boolean TRUE si le compte a bien été créé, FALSE si une erreur s'est produite.
   */
  function add_mail($dom,$mail,$pop,$pass,$alias) {
    global $quota,$err,$cuid,$db;
    $err->log("mail","add_mail",$dom."/".$mail);
    $account=array();
    $mail=strtolower($mail);
    if ($pop) $pop="1"; else $pop="0";
    if ($mail) {
      //vérifie la validité du login mail
      if (!checkloginmail($mail)) {
	$err->raise("mail",13);
	return false;
      }
    }
    //vérifie si les champs obligatoires sont renseignés
    if (($pop=="1" && $pass=="")||($pop!="1" && $alias=="")){
      $err->raise("mail",4);
      return false;
    }
    if ($pop=="1"){
      $account[]=$mail."_".$dom;
    }
    //vérifie la validité des alias :
    if ($alias){
      $a=explode("\n",$alias);
      if (count($a)>0) {
	reset($a);
	for ($i=0;$i<count($a);$i++){
	  $a[$i]=trim($a[$i]);
	  if ($a[$i]){
	    if(checkmail($a[$i])>1){
	      $err->raise("mail",14);
	      return false;
	    }
	  }
	  $account[]=$a[$i];
	}
      }
    }

    // check that the domains is a user's one ...
    $db->query("SELECT domaine FROM domaines WHERE compte='$cuid' AND domaine='$dom';");
    if (!$db->next_record()) {
      $err->raise("mail",6,$dom);
      return false;
    }    
    $db->query("SELECT mail FROM mail_domain WHERE mail='".$mail."@".$dom."' AND uid='$cuid';");
    if ($db->next_record()) {
      $err->raise("mail",7,$mail."@".$dom);
      return false;
    }    

    /* QuotaCheck */
    if (!$quota->cancreate("mail")) {
      $err->raise("mail",8);
      return false;
    }
    $db->query("INSERT INTO mail_domain (mail,alias,uid,pop,type) VALUES ('".$mail."@".$dom."','".implode("\n",$account)."','$cuid','$pop',0);");

    // Ajout du compte pop dans ldap-users
    if ($pop=="1") {
      if (!$this->_createpop($mail,$dom,$pass))
	return false;
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Efface le compte mail $mail
   * @param string $mail Email à effacer
   * @return boolean TRUE si le compte mail a bien été détruit, FALSE si une erreur s'est produite.
   */
  function del_mail($mail) {
    global $err,$cuid,$db;
    $err->log("mail","del_mail",$mail);
    $mail=strtolower($mail);

    $db->query("SELECT pop,mail FROM mail_domain WHERE mail='$mail' AND uid='$cuid' AND type=0;");
    if (!$db->next_record()) {
      $err->raise("mail",3,$dom);
      return false;
    }    
    /* Ok, le mail existe, on le detruit donc... */
    $t=explode("@",$mail);
    $mdom=$t[0]; $dom=$t[1];
    $pop=$db->f("pop");
    
    $db->query("DELETE FROM mail_domain WHERE mail='$mail' AND uid='$cuid';");

    if ($pop=="1") {
      if (!$this->_deletepop($mdom,$dom)) {
	return false;
      }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Check for a slave account (secondary mx)
   */
  function check_slave_account($login,$pass) {
	global $db,$err;
	$db->query("SELECT * FROM mxaccount WHERE login='$login' AND pass='$pass';");
	if ($db->next_record()) { 
		return true;
	}
	return false;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Out (echo) the complete mx-hosted domain list : 
   */
  function echo_domain_list() {
	global $db,$err;
	$db->query("SELECT domaine FROM domaines WHERE gesmx=1 ORDER BY domaine");
	while ($db->next_record()) {
		echo $db->f("domaine")."\n";
	}
	return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Return the list of allowed slave accounts (secondary-mx)
   */
  function enum_slave_account() {
	global $db,$err;
	$db->query("SELECT * FROM mxaccount;");
	$res=array();
	while ($db->next_record()) {
		$res[]=$db->Record;
	}
	if (!count($res)) return false;
	return $res;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Add a slave account that will be allowed to access the mxdomain list
   */
  function add_slave_account($login,$pass) {
	global $db,$err;
	$db->query("SELECT * FROM mxaccount WHERE login='$login'");
	if ($db->next_record()) {
	  $err->raise("err",23); // FIXME
	  return false;
	}
	$db->query("INSERT INTO mxaccount (login,pass) VALUES ('$login','$pass')");
	return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Remove a slave account
   */
  function del_slave_account($login) {
	global $db,$err;
	$db->query("DELETE FROM mxaccount WHERE login='$login'");
	return true;
  }

  /* ----------------------------------------------------------------- */
  /** Crée le compte pop $mail@$dom, avec pour mot de passe $pass
   * @param string $mail Compte email à créer en pop
   * @param string $dom Domaine sur lequel on crée le compte email
   * @param string $pass Mot de passe du compte email.
   * @return boolean TRUE si le compte pop a bien été créé, FALSE si une erreur est survenur
   * @access private
   */
  function _createpop($mail,$dom,$pass) {
    global $err,$cuid,$db;
    $err->log("mail","_createpop",$mail."@".$dom);
    $m=substr($mail,0,1);
    $gecos=$mail;
    if (!$mail) {
      // Cas du CATCH-ALL
      $gecos="Catch-All";
      $m="_";
    }
    $db->query("INSERT INTO mail_users (uid,alias,path,password) VALUES ('$cuid','".$mail."_".$dom."','/var/alternc/mail/".$m."/".$mail."_".$dom."','"._md5cr($pass)."');");
    $db->query("INSERT INTO mail_users (uid,alias,path,password) VALUES ('$cuid','".$mail."@".$dom."','/var/alternc/mail/".$m."/".$mail."_".$dom."','"._md5cr($pass)."');");
    $db->query("INSERT INTO mail_alias (mail,alias) VALUES ('".$mail."_".$dom."','/var/alternc/mail/".$m."/".$mail."_".$dom."/Maildir/');");

    $f=fopen("/var/lib/squirrelmail/data/".$mail."_".$dom.".pref","wb");
    fputs($f,"email_address=$mail@$dom\nchosen_theme=default_theme.php\n");
    fclose($f);
    $f=fopen("/var/lib/squirrelmail/data/".$mail."@".$dom.".pref","wb");
    fputs($f,"email_address=$mail@$dom\nchosen_theme=default_theme.php\n");
    fclose($f);
    exec("/usr/lib/alternc/mail_add ".$mail."_".$dom." ".$cuid);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Met à jour un compte pop existant
   * @param string $mail mail à modifier
   * @param string $dom Domaine dont on modifie le compte pop
   * @param string $pass Nouveau mot de passe.
   * @return boolean TRUE si le compte pop a bien été modifié, FALSE si une erreur s'est produite.
   * @access private
   */
  function _updatepop($mail,$dom,$pass) {
    global $err,$cuid,$db;
    $err->log("mail","_updatepop",$mail."@".$dom);
    $m=substr($mail,0,1);
    $gecos=$mail;
    $db->query("UPDATE mail_users SET password='"._md5cr($pass)."' WHERE ( alias='". $mail."_".$dom."' OR alias='". $mail."@".$dom."' ) AND uid='$cuid';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Détruit le compte pop $mail@$dom.
   * @param string $mail Email dont on souhaite détruire le compte pop
   * @param string $dom Domaine dont on souhaite détuire le compte pop.
   * @return boolean TRUE si le compte pop a bien été détruit, FALSE si une erreur s'est produite.
   * @access private
   */
  function _deletepop($mail,$dom) {
    global $err,$cuid,$db;
    $err->log("mail","_deletepop",$mail."@".$dom);
    $db->query("DELETE FROM mail_users WHERE uid='$cuid' AND (  alias='". $mail."_".$dom."' OR alias='". $mail."@".$dom."' ) ;");
    $db->query("DELETE FROM mail_alias WHERE mail='".$mail."_".$dom."';");
    @unlink("/var/lib/squirrelmail/data/".$mail."_".$dom.".pref");
    @unlink("/var/lib/squirrelmail/data/".$mail."_".$dom.".abook");
    @unlink("/var/lib/squirrelmail/data/".$mail."@".$dom.".pref");
    @unlink("/var/lib/squirrelmail/data/".$mail."@".$dom.".abook");
    exec("/usr/lib/alternc/mail_del ".$mail."_".$dom);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Fonction appellée par domaines lorsqu'un domaine est effacé.
   * Cette fonction efface tous les comptes mails du domaine concerné.
   * @param string $dom Domaine à effacer
   * @return boolean TRUE si le domaine a bien été effacé, FALSE si une erreur s'est produite.
   * @access private
   */
  function alternc_del_mx_domain($dom) {
    global $err,$db,$cuid;
    $err->error=0;
    $err->log("mail","alternc_del_mx_domain",$dom);

    /*
    $db->query("SELECT domaine FROM domaines WHERE compte='$cuid' AND domaine='$dom';");
    if (!$db->next_record()) {
      $err->raise("mail",6,$dom);
      return false;
    }    
    */

    /* Effacement de tous les mails de ce domaine : */
    $a=$this->enum_doms_mails($dom);
    if (is_array($a)) {
      reset($a);
      for($i=0;$i<$a["count"];$i++) {
	$val=$a[$i];
	if (!$this->del_mail($val["mail"])) {
	  $err->raise("mail",5);
	}
      }
    }
    /* Effacement du domaine himself */
    $db->query("DELETE FROM mail_domain WHERE mail LIKE '%@$dom';");     
    $db->query("DELETE FROM mail_users WHERE alias LIKE '%@$dom' OR alias LIKE '%\\_$dom';");     
    $db->query("DELETE FROM mail_alias WHERE mail LIKE '%\\_$dom';");     
    $db->query("DELETE FROM mail_domain WHERE mail='$dom';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Fonction appellée par domaines lorsqu'un domaine est créé.
   * Cette fonction crée les comptes par défaut du domaine concerné.
   * @param string $dom Domaine à créer
   * @return boolean TRUE si le domaine a bien été créé, FALSE si une erreur s'est produite.
   * @access private
   */
  function alternc_add_mx_domain($dom) {
    global $err,$cuid,$db,$mem;
    $err->log("mail","alternc_add_mx_domain",$dom);
    $db->query("INSERT INTO mail_domain (mail,alias) VALUES ('$dom','$dom');");
    // Create the postmaster email for this new domain : 
    $this->add_mail($dom,"postmaster",0,"",$mem->user["mail"]);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Returns the used quota for the $name service for the current user.
   * @param $name string name of the quota
   * @return integer the number of service used or false if an error occured
   * @access private
   */
  function alternc_get_quota($name) {
    global $db,$err,$cuid;
    if ($name=="mail") {
      $err->log("mail","getquota");
      $db->query("SELECT COUNT(*) AS cnt FROM mail_domain WHERE type=0 AND uid='$cuid'");
      $db->next_record();
      return $db->f("cnt");
    }
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exports all the mail related information for an account.
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export($tmpdir) {
    global $db,$err;
    $err->log("mail","export");
    $domain=$this->enum_domains();
    $str="<mail>\n";
    $tmpfile=$tmpdir."/mail_filelist.txt";
    $f=fopen($tmpfile,"wb");
    $onepop=false;
    foreach ($domain as $d) {
      $str.="  <domain>\n    <name>".xml_entities($d)."</name>\n";
      $s=$this->enum_doms_mails($d);
      unset($s["count"]);
      if (count($s)) {
        foreach($s as $e) {
          $str.="    <address>\n";
          $str.="      <mail>".xml_entities($e["mail"])."</mail>\n";
          $str.="      <ispop>".xml_entities($e["pop"])."</ispop>\n";
          $acc=explode("\n",$e["alias"]);
          foreach($acc as $f) {
	    $f=trim($f);
	    if ($f) {
	      $str.="      <alias>".xml_entities($f)."</alias>\n";
	    }
	  }
	  if ($e["pop"]) {
	    $db->query("SELECT path FROM mail_users WHERE alias='".str_replace("@","_",$e["mail"])."';");
	    if ($db->next_record()) {
	      fputs($f,$db->Record["path"]."\n");
	      $onepop=true;
	    }
	  }
	  $str.="    </address>\n";
	}
      }     
      $str.="  </domain>\n";
    }
    $str.="</mail>\n";
    fclose($f);
    if ($onepop) {
      // Now do the tarball of all pop accounts : 
      exec("/bin/tar -czf ".escapeshellarg($tmpdir."/mail.tar.gz")." -T ".escapeshellarg($tmpfile)); 
    }
    @unlink($tmpfile);
    return $str;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Returns the declaration of all xml rpc exportable functions 
   * related to mail service. Each method is returned as an array
   * containing the function name, function, signature and docstring.
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_xmlrpc_server() {
  }


} /* Class m_mail */

?>
