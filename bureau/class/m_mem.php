<?php
/*
 $Id: m_mem.php,v 1.19 2006/01/12 08:04:43 anarcat Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Manage Login session on the virtual desktop and
 	member parameters
 ----------------------------------------------------------------------
*/
/**
* This class manage user sessions in the web desktop.
*
* This class manage user sessions and administration in AlternC.
* @copyright    AlternC-Team 2002-2005 http://alternc.org/
*
*/
class m_mem {

  /** Original uid for the temporary uid swapping (for administrators) */
  var $olduid=0;
  
  /** This array contains the Tableau contenant les champs de la table "membres" du membre courant
   * Ce tableau est utilisable globalement par toutes les classes filles.
   */
  var $user;
  /** Tableau contenant les champs de la table "local" du membre courant
   * Ce tableau est utilisable globalement par toutes les classes filles.
   * Note : les champs de "local" sont spécifiques à l'hébergeur.
   */
  var $local;

  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_mem() {
  }

  /* ----------------------------------------------------------------- */
  /**
   * Password kind used in this class (hook for admin class)
   */
  function alternc_password_policy() {
    return array("mem"=>"AlternC's account password");
  }


  /* ----------------------------------------------------------------- */
  /** Check that the current user is an admnistrator.
   * @return boolean TRUE if we are super user, or FALSE if we are not.
   */
  function checkright() {
    return ($this->user["su"]=="1");
  }

  /* ----------------------------------------------------------------- */
  /** Start a session in the web desktop. Check username and password.
   * <b>Note : </b>If the user entered a bas password, the failure will be logged
   * and told to the corresponding user on next successfull login.
   * @param $username string Username that want to get connected.
   * @param $password string User Password.
   * @return boolean TRUE if the user has been successfully connected, or FALSE if an error occured.
   */
  function login($username,$password,$restrictip=0,$authip_token=false) {
    global $db,$err,$cuid, $authip;
    $err->log("mem","login",$username);
    //    $username=addslashes($username);
    //    $password=addslashes($password);
    $db->query("select * from membres where login='$username';");
    if ($db->num_rows()==0) {
      $err->raise("mem",_("User or password incorrect"));
      return false;
    }
    $db->next_record();
    if (_md5cr($password,$db->f("pass"))!=$db->f("pass")) {
      $db->query("UPDATE membres SET lastfail=lastfail+1 WHERE uid='".$db->f("uid")."';");
      $err->raise("mem",_("User or password incorrect"));
      return false;
    } 
    if (!$db->f("enabled")) {
      $err->raise("mem",_("This account is locked, contact the administrator"));
      return false;
    }
    $this->user=$db->Record;
    $cuid=$db->f("uid");

    // AuthIP
    $allowed_ip=false;
    if ( $authip_token ) $allowed_ip = $this->authip_tokencheck($authip_token);

    $aga = $authip->get_allowed('panel');
    foreach ($aga as $k=>$v ) {
      if ( $authip->is_in_subnet(get_remote_ip(), $v['ip'], $v['subnet']) ) $allowed=true ;
    }

    // Error if there is rules, the IP is not allowed and it's not in the whitelisted IP
    if ( sizeof($aga)>1 && !$allowed_ip && !$authip->is_wl(get_remote_ip()) ) {
      $err->raise("mem",_("Your IP isn't allowed to connect"));
      return false;
    }
    // End AuthIP

    if ($restrictip) {
      $ip="'".get_remote_ip()."'";
    } else $ip="''";
    /* Close sessions that are more than 2 days old. */
    $db->query("DELETE FROM sessions WHERE DATE_ADD(ts,INTERVAL 2 DAY)<NOW();");
    /* Open the session : */
    $sess=md5(uniqid(mt_rand()));
    $_REQUEST["session"]=$sess;
    $db->query("insert into sessions (sid,ip,uid) values ('$sess',$ip,'$cuid');");
    setcookie("session",$sess,0,"/");
    $err->error=0;
    /* Fill in $local */
    $db->query("SELECT * FROM local WHERE uid='$cuid';");
    if ($db->num_rows()) {
      $db->next_record();
      $this->local=$db->Record;
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Start a session as another user from an administrator account.
   * This function is not the same as su. setid connect the current user in the destination
   * account (for good), and su allow any user to become another account for some commands only.
   * (del_user, add_user ...) and allow to bring back admin rights with unsu
   * 
   * @param $id integer User id where we will connect to.
   * @return boolean TRUE if the user has been successfully connected, FALSE else.
   */
  function setid($id) {
    global $db,$err,$cuid;
    $err->log("mem","setid",$id);
    $db->query("select * from membres where uid='$id';");
    if ($db->num_rows()==0) {
      $err->raise("mem",_("User or password incorrect"));
      return false;
    }
    $db->next_record();
    $this->user=$db->Record;
    $cuid=$db->f("uid");
    $ip=get_remote_ip();
    $sess=md5(uniqid(mt_rand()));
    $_REQUEST["session"]=$sess;
    $db->query("insert into sessions (sid,ip,uid) values ('$sess','$ip','$cuid');");
    setcookie("session",$sess,0,"/");
    $err->error=0;
    /* Fill in $local */
    $db->query("SELECT * FROM local WHERE uid='$cuid';");
    if ($db->num_rows()) {
      $db->next_record();
      $this->local=$db->Record;
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Suite à la connexion de l'utilisateur, réinitialise ses paramètres de dernière connexion
   */
  function resetlast() {
    global $db,$cuid;
    $ip=addslashes(getenv("REMOTE_HOST"));
    if (!$ip) $ip=addslashes(get_remote_ip());
    $db->query("UPDATE membres SET lastlogin=NOW(), lastfail=0, lastip='$ip' WHERE uid='$cuid';");
  }

  function authip_token($bis=false) {
    global $db,$cuid;
    $db->query("select pass from membres where uid='$cuid';");
    $db->next_record();
    $i=intval(time()/3600);
    if ($bis) ++$i;
    return md5("$i--".$db->f('pass'));
  }

  function authip_tokencheck($t) {
    if ($t==$this->authip_token() || $t==$this->authip_token(true) ) return true;
    return false;
  }

/* Faut finir de l'implémenter :)
  function authip_class() {
    global $cuid;
    $c = Array();
    $c['name']="Panel access";
    $c['protocol']="panel";
    $c['values']=Array($cuid=>'');

    return $c;
  }
*/

  /* ----------------------------------------------------------------- */
  /** Vérifie que la session courante est correcte (cookie ok et ip valide).
   * Si besoin, et si réception des champs username & password, crée une nouvelle
   * session pour l'utilisateur annoncé.
   * Cette fonction doit être appellée à chaque page devant être authentifiée.
   * et AVANT d'émettre des données. (un cookie peut être envoyé)
   * @global string $session Le cookie de session eventuel
   * @global string $username/password le login/pass de l'utilisateur
   * @return TRUE si la session est correcte, FALSE sinon.
   */
  function checkid() {
    global $db,$err,$cuid,$restrictip,$authip;
    if (isset($_REQUEST["username"])) {
      if ($_REQUEST["username"] && $_REQUEST["password"]) {
      	return $this->login($_REQUEST["username"],$_REQUEST["password"],$_REQUEST["restrictip"]);
      }
    } // end isset
    $_COOKIE["session"]=isset($_COOKIE["session"])?addslashes($_COOKIE["session"]):"";
    if (strlen($_COOKIE["session"])!=32) {
      $err->raise("mem",_("Cookie incorrect, please accept the session cookie"));
      return false;
    }
    $ip=get_remote_ip();
    $db->query("select uid,'$ip' as me,ip from sessions where sid='".$_COOKIE["session"]."'");
    if ($db->num_rows()==0) {
      $err->raise("mem",_("Session unknown, contact the administrator"));
      return false;
    }
    $db->next_record();
    if ($db->f("ip")) {
      if ($db->f("me")!=$db->f("ip")) {
	      $err->raise("mem",_("IP address incorrect, please contact the administrator"));
	      return false;
      }
    }
    $cuid=$db->f("uid");
    $db->query("select * from membres where uid='$cuid';");
    $db->next_record();
    $this->user=$db->Record;
    $err->error=0;
    /* Remplissage de $local */
    $db->query("SELECT * FROM local WHERE uid='$cuid';");
    if ($db->num_rows()) {
      $db->next_record();
      $this->local=$db->Record;
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Change l'identité d'un utilisateur temporairement.
   * @global string $uid Utilisateur dont on prends l'identité
   * @return TRUE si la session est correcte, FALSE sinon.
   */
  function su($uid) {
    global $cuid,$db,$err;
    if (!$this->olduid)
	    $this->olduid=$cuid;
    $db->query("select * from membres where uid='$uid';");
    if ($db->num_rows()==0) {
      $err->raise("mem",_("User or password incorrect"));
      return false;
    }
    $db->next_record();
    $this->user=$db->Record;
    $cuid=$db->f("uid");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Retourne a l'identite d'origine de l'utilisateur apres su.
   * @return TRUE si la session est correcte, FALSE sinon.
   */
  function unsu() {
    global $cuid;
    if (!$this->olduid)
	return false;
    $this->su($this->olduid);
    $this->olduid=0;
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Termine une session du bureau virtuel (logout)
   * @return boolean TRUE si la session a bien été détruite, FALSE sinon.
   */
  function del_session() {
    global $db,$user,$err,$cuid,$classes,$hooks;
    $err->log("mem","del_session");
    $_COOKIE["session"]=addslashes(isset($_COOKIE["session"])?$_COOKIE["session"]:'');
    setcookie("session","",0,"/");
    setcookie("oldid","",0,"/");
    if ($_COOKIE["session"]=="") {
      $err->error=0;
      return true;
    }
    if (strlen($_COOKIE["session"])!=32) {
      $err->raise("mem",_("Cookie incorrect, please accept the session cookie"));
      return false;
    }
    $ip=get_remote_ip();
    $db->query("select uid,'$ip' as me,ip from sessions where sid='".$_COOKIE["session"]."'");
    if ($db->num_rows()==0) {
      $err->raise("mem",_("Session unknown, contact the administrator"));
      return false;
    }
    $db->next_record();
    if ($db->f("me")!=$db->f("ip")) {
      $err->raise("mem",_("IP address incorrect, please contact the administrator"));
      return false;
    }
    $cuid=$db->f("uid");
    $db->query("delete from sessions where sid='".$_COOKIE["session"]."';");
    $err->error=0;
    
    # Invoker le logout dans toutes les autres classes
    /*
    foreach($classes as $c) {
      if (method_exists($GLOBALS[$c],"alternc_del_session")) {
	    $GLOBALS[$c]->alternc_del_session();
      }
    }
    */
    $hooks->invoke("alternc_del_session");
    
    session_unset();
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Change le mot de passe de l'utilisateur courant.
   * @param string $oldpass Ancien mot de passe.
   * @param string $newpass Nouveau mot de passe
   * @param string $newpass2 Nouveau mot de passe (à nouveau)
   * @return boolean TRUE si le mot de passe a été changé, FALSE sinon.
   */
  function passwd($oldpass,$newpass,$newpass2) {
    global $db,$err,$cuid,$admin;
    $err->log("mem","passwd");
    $oldpass=stripslashes($oldpass);
    $newpass=stripslashes($newpass);
    $newpass2=stripslashes($newpass2);
    if (!$this->user["canpass"]) {
      $err->raise("mem",_("You are not allowed to change your password."));
      return false;
    }
    if ($this->user["pass"]!=_md5cr($oldpass,$this->user["pass"])) {
      $err->raise("mem",_("The old password is incorrect"));
      return false;
    }
    if ($newpass!=$newpass2) {
      $err->raise("mem",_("The new passwords are differents, please retry"));
      return false;
    }
    $db->query("SELECT login FROM membres WHERE uid='$cuid';");   
    $db->next_record();
    $login=$db->Record["login"];
    if (!$admin->checkPolicy("mem",$login,$newpass)) {
      return false; // The error has been raised by checkPolicy()
    }
    $newpass=_md5cr($newpass);
    $db->query("UPDATE membres SET pass='$newpass' WHERE uid='$cuid';");
    $err->error=0;
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Change les préférences administrateur d'un compte
   * @param integer $admlist Mode de visualisation des membres (0=large 1=courte)
   * @return boolean TRUE si les préférences ont été changées, FALSE sinon.
   */
  function adminpref($admlist) {
    global $db,$err,$cuid;
    $err->log("mem","admlist");
    if (!$this->user["su"]) {
      $err->raise("mem",_("You must be a system administrator to do this."));
      return false;
    }
    $db->query("UPDATE membres SET admlist='$admlist' WHERE uid='$cuid';");
    $err->error=0;
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Envoie en mail le mot de passe d'un compte.
   * <b>Note : </b>On ne peut demander le mot de passe qu'une seule fois par jour.
   * TODO : Translate this mail into the localization program.
   * TODO : Check this function's !
   * @return boolean TRUE si le mot de passe a été envoyé avec succès, FALSE sinon.
   */
  function send_pass($login) {
    global $err,$db,$L_HOSTING,$L_FQDN;
    $err->log("mem","send_pass");
    $db->query("SELECT * FROM membres WHERE login='$login';");
    if (!$db->num_rows()) {
      $err->raise("mem",_("This account is locked, contact the administrator."));
      return false;
    }
    $db->next_record();
    if (time()-$db->f("lastaskpass")<86400) {
      $err->raise("mem",_("The new passwords are differents, please retry"));
      return false;
    }
    $txt=sprintf(_("Hello,

You requested the modification of your password for your
account %s on %s
Here are your username and password to access the panel :

--------------------------------------

Username : %s
Password : %s

--------------------------------------

Note : if you didn't requested that modification, it means that
someone did it instead of you. You can choose to ignore this message.
If it happens again, please contact your server's Administrator. 

Cordially.
"), $login, $L_HOSTING, $db->f("login"), $db->f("pass"));
    mail($db->f("mail"),"Your password on $L_HOSTING",$txt,"From: postmaster@$L_FQDN\nReply-to: postmaster@$L_FQDN");
    $db->query("UPDATE membres SET lastaskpass=".time()." WHERE login='$login';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Change le mail d'un membre (première etape, envoi du CookiE)
   * TODO : insert this mail string into the localization system
   * @param string $newmail Nouveau mail souhaité pour le membre.
   * @return string le cookie si le mail a bien été envoyé, FALSE sinon
   */
  function ChangeMail1($newmail) {
    global $err,$db,$L_HOSTING,$L_FQDN,$cuid;
    $err->log("mem","changemail1",$newmail);
    $db->query("SELECT * FROM membres WHERE uid='$cuid';");
    if (!$db->num_rows()) {
      $err->raise("mem",_("This account is locked, contact the administrator"));
      return false;
    }
    $db->next_record();

    // un cookie de 20 caractères pour le mail
    $COOKIE=substr(md5(uniqid(rand(),1)),0,20);
    // et de 6 pour la clé à entrer. ca me semble suffisant...
    $KEY=substr(md5(uniqid(rand(),1)),0,6);
    $link="https://$L_FQDN/mem_cm.php?usr=$cuid&cookie=$COOKIE";
    $txt=sprintf(_("Hello,

Someone (maybe you) requested an email's address modification of the account
%s on %s
To confirm your request, go to this url :

%s

(Warning : if this address is displayed on 2 lines, don't forgot to
take it on one line).
The panel will ask you the key given when the email address
modification was requested.

If you didn't asked for this modification, it means that someone
did it instead of you. You can choose to ignore this message. If it happens
again, please contact your server's administrator.

Cordially.
"), $db->f("login"), $L_HOSTING, $link);
    mail($newmail,"Email modification request on $L_HOSTING",$txt,"From: postmaster@$L_FQDN\nReply-to: postmaster@$L_FQDN");
    // Supprime les demandes précédentes de ce compte !
    $db->query("DELETE FROM chgmail WHERE uid='$cuid';");
    $db->query("INSERT INTO chgmail (cookie,ckey,uid,mail,ts) VALUES ('$COOKIE','$KEY','$cuid','$newmail',".time().");");
    // Supprime les cookies de la veille :)
    $lts=time()-86400;
    $db->query("DELETE FROM chgmail WHERE ts<'$lts';");
    return $KEY;
  }

  /* ----------------------------------------------------------------- */
    /** Change le mail d'un membre (seconde etape, CookiE+clé = application)
     * @param string $COOKIE Cookie envoyé par mail
     * @param string $KEY clé affichée à l'écran
     * @param integer $uid Utilisateur concerné (on est hors session)
     * @return TRUE si le mail a bien été modifié, FALSE sinon
     */
    function ChangeMail2($COOKIE,$KEY,$uid) {
      global $err,$db,$L_HOSTING,$L_FQDN;
      $err->log("mem","changemail2",$uid);
      $db->query("SELECT * FROM chgmail WHERE cookie='$COOKIE' and ckey='$KEY' and uid='$uid';");
      if (!$db->num_rows()) {
	$err->raise("mem",_("The information you entered is incorrect."));
	return false;
      }
      $db->next_record();

      // met à jour le compte :
      $db->query("UPDATE membres SET mail='".$db->f("mail")."' WHERE uid='$uid';");

      $db->query("DELETE FROM chgmail WHERE uid='$uid';");
      // Supprime les cookies de la veille :)
      $lts=time()-86400;
      $db->query("DELETE FROM chgmail WHERE ts<'$lts';");
      return true;
    }

    /* ----------------------------------------------------------------- */
    /** Modifie le paramètre d'aide en ligne (1/0)
     * @param integer $show Faut-il (1) ou non (0) afficher l'aide en ligne
     */
    function set_help_param($show) {
      global $db,$err,$cuid;
      $err->log("mem","set_help_param",$show);
      $db->query("UPDATE membres SET show_help='$show' WHERE uid='$cuid';");
    }

    /* ----------------------------------------------------------------- */
    /** Dit si l'aide en ligne est demandée
     * @return boolean TRUE si l'aide en ligne est demandée, FALSE sinon.
     */
    function get_help_param() {
      return $this->user["show_help"];
    }

    /* ----------------------------------------------------------------- */
    /** Affiche (echo) l'aide contextuelle
     * @param integer $file Numéro de fichier d'aide à afficher.
     * @return TRUE si l'aide contextuelle a été trouvée, FALSE sinon
     */
  function show_help($file,$force=false) {
    global $err;
    $err->log("mem","show_help");
    if ($this->user["show_help"] || $force) {
      $hlp=_("hlp_$file");
      if ($hlp!="hlp_$file") {
	      $hlp=preg_replace(
			  "#HELPID_([0-9]*)#",
			  "<a href=\"javascript:help(\\1);\"><img src=\"/aide/help.png\" width=\"17\" height=\"17\" style=\"vertical-align: middle;\" alt=\""._("Help")."\" /></a>",$hlp);
      	echo "<p class=\"hlp\">".$hlp."</p>";
       	return true;
      }
      return false;
    } else {
      return true;
    }
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exports all the personnal user related information for an account.
   * @access private
   */
  function alternc_export_conf() {
    global $db,$err;
    $err->log("mem","export");
    $str="  <member>\n";
    $users=$this->user;
      $str.="   <uid>".$users["uid"]."</uid>\n";
      $str.="   <login>".$users["login"]."</login>\n";
      $str.="   <enabled>".$users["enabled"]."</enabled>\n";
      $str.="   <su>".$users["su"]."</su>\n";
      $str.="   <password>".$users["pass"]."</password>\n";
      $str.="   <mail>".$users["mail"]."</mail>\n";
      $str.="   <created>".$users["created"]."</created>\n";
      $str.="   <lastip>".$users["lastip"]."</lastip>\n";
      $str.="   <lastlogin>".$users["lastlogin"]."</lastlogin>\n";
      $str.="   <lastfail>".$users["lastfail"]."</lastfail>\n";
    $str.=" </member>\n";
    return $str;
  }




} /* Classe Membre */

?>
