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
   * Note : les champs de "local" sont sp�cifiques � l'h�bergeur.
   */
  var $local;

  /**
   * Constructeur
   */
   /**
    * 
    */
  function m_mem() {
  }

  /**
   * Password kind used in this class (hook for admin class)
   */
   /**
    * 
    * @return type
    */
  function alternc_password_policy() {
    return array("mem"=>"AlternC's account password");
  }

   /**
    * 
    * @return type
    */
  function hook_menu() {
    $obj = array(
      'title'       => _("Settings"),
      'ico'         => 'images/settings.png',
      'link'        => 'mem_param.php',
      'pos'         => 160,
     ) ;

     return $obj;
  }

  /** Check that the current user is an admnistrator.
   * @return boolean TRUE if we are super user, or FALSE if we are not.
   */
   /**
    * 
    * @return type
    */
  function checkright() {
    return ($this->user["su"]=="1");
  }

  /** Start a session in the web desktop. Check username and password.
   * <b>Note : </b>If the user entered a bas password, the failure will be logged
   * and told to the corresponding user on next successfull login.
   * @param $username string Username that want to get connected.
   * @param $password string User Password.
   * @return boolean TRUE if the user has been successfully connected, or FALSE if an error occured.
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err       $err
    * @global m_mem       $mem
    * @global m_authip    $authip
    * @global m_admin     $admin
    * @param type $username
    * @param type $password
    * @param type $restrictip
    * @param type $authip_token
    * @return boolean
    */
  function login($username,$password,$restrictip=0,$authip_token=false) {
    global $db,$err,$cuid,$authip,$admin;
    $err->log("mem","login",$username);
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

    if (panel_islocked() && $cuid != 2000) {
      $err->raise("mem",_("This website is currently under maintenance, login is currently disabled."));
      return false;
    }

    $allowed_ip=false;
    if ( $authip_token ) $allowed_ip = $this->authip_tokencheck($authip_token);

    $aga = $authip->get_allowed('panel');
    foreach ($aga as $k=>$v ) {
      if ( $authip->is_in_subnet(get_remote_ip(), $v['ip'], $v['subnet']) ) $allowed=true ;
    }

    if ( sizeof($aga)>1 && !$allowed_ip && !$authip->is_wl(get_remote_ip()) ) {
      $err->raise("mem",_("Your IP isn't allowed to connect"));
      return false;
    }

    if ($restrictip) {
      $ip="'".get_remote_ip()."'";
    } else $ip="''";
    $db->query("DELETE FROM sessions WHERE DATE_ADD(ts,INTERVAL 2 DAY)<NOW();");
    if ( isset($_COOKIE["oldid"]) ) setcookie('oldid','',0,'/'); 
    $sess=md5(uniqid(mt_rand()));
    $_REQUEST["session"]=$sess;
    $db->query("insert into sessions (sid,ip,uid) values ('$sess',$ip,'$cuid');");
    setcookie("session",$sess,0,"/");
    $err->error=0;
    $db->query("SELECT * FROM local WHERE uid='$cuid';");
    if ($db->num_rows()) {
      $db->next_record();
      $this->local=$db->Record;
    }
    return true;
  }

  /** Start a session as another user from an administrator account.
   * This function is not the same as su. setid connect the current user in the destination
   * account (for good), and su allow any user to become another account for some commands only.
   * (del_user, add_user ...) and allow to bring back admin rights with unsu
   * 
   * @param $id integer User id where we will connect to.
   * @return boolean TRUE if the user has been successfully connected, FALSE else.
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err       $err
    * @global m_mem       $mem
    * @global m_mysql     $mysql
    * @global m_quota     $quota
    * @param type $id
    * @return boolean
    */
  function setid($id) {
    global $db,$err,$cuid,$mysql,$quota;
    $err->log("mem","setid",$id);
    $db->query("select * from membres where uid='$id';");
    if ($db->num_rows()==0) {
      $err->raise("mem",_("User or password incorrect"));
      return false;
    }
    $db->next_record();
    $this->user=$db->Record;
    $cuid=$db->f("uid");
    $mysql->reload_dbus();

    $ip=get_remote_ip();
    $sess=md5(uniqid(mt_rand()));
    $_REQUEST["session"]=$sess;
    $db->query("insert into sessions (sid,ip,uid) values ('$sess','$ip','$cuid');");
    setcookie("session",$sess,0,"/");
    $err->error=0;
    $db->query("SELECT * FROM local WHERE uid='$cuid';");
    if ($db->num_rows()) {
      $db->next_record();
      $this->local=$db->Record;
    }
    $quota->getquota('', true);
    return true;
  }

  /** Suite � la connexion de l'utilisateur, r�initialise ses param�tres de derni�re connexion
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_mem       $mem
    */
  function resetlast() {
    global $db,$cuid;
    $ip=addslashes(getenv("REMOTE_HOST"));
    if (!$ip) $ip=addslashes(get_remote_ip());
    $db->query("UPDATE membres SET lastlogin=NOW(), lastfail=0, lastip='$ip' WHERE uid='$cuid';");
  }

   /**
    * 
    * @global m_mysql     $db
    * @global m_mem       $mem
    * @param type $bis
    * @return type
    */
  function authip_token($bis=false) {
    global $db,$cuid;
    $db->query("select pass from membres where uid='$cuid';");
    $db->next_record();
    $i=intval(time()/3600);
    if ($bis) ++$i;
    return md5("$i--".$db->f('pass'));
  }

  /**
   * @param boolean $t
  */
   /**
    * 
    * @param type $t
    * @return boolean
    */
  function authip_tokencheck($t) {
    if ($t==$this->authip_token() || $t==$this->authip_token(true) ) return true;
    return false;
  }

// Faut finir de l'impl�menter :) * 
   /**
    * 
    * @global m_mem       $mem
    * @return string
    */
  function authip_class() {
    global $cuid;
    $c = Array();
    $c['name']="Panel access";
    $c['protocol']="mem";
    $c['values']=Array($cuid=>'');

    return $c;
  }
// *

  /** V�rifie que la session courante est correcte (cookie ok et ip valide).
   * Si besoin, et si r�ception des champs username & password, cr�e une nouvelle
   * session pour l'utilisateur annonc�.
   * Cette fonction doit �tre appell�e � chaque page devant �tre authentifi�e.
   * et AVANT d'�mettre des donn�es. (un cookie peut �tre envoy�)
   * @global string $session Le cookie de session eventuel
   * @global string $username/password le login/pass de l'utilisateur
   * @return boolean TRUE si la session est correcte, FALSE sinon.
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err       $err
    * @global m_mem       $mem
    * @return boolean
    */
  function checkid() {
    global $db,$err,$cuid;
    if (isset($_REQUEST["username"])) {
      if ( empty($_REQUEST['password']) ) {
        $err->raise("mem",_("Missing password"));
        return false;
      }
      if ($_REQUEST["username"] && $_REQUEST["password"]) {
      	return $this->login($_REQUEST["username"],$_REQUEST["password"], (isset($_REQUEST["restrictip"])?$_REQUEST["restrictip"]:0) );
      }
    } // end isset
    $_COOKIE["session"]=isset($_COOKIE["session"])?addslashes($_COOKIE["session"]):"";
    if (strlen($_COOKIE["session"])!=32) {
      $err->raise("mem",_("Identity lost or unknown, please login"));
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

    if (panel_islocked() && $cuid != 2000) {
      $err->raise("mem",_("This website is currently under maintenance, login is currently disabled."));
      return false;
    }

    $db->query("select * from membres where uid='$cuid';");
    $db->next_record();
    $this->user=$db->Record;
    $err->error=0;
    $db->query("SELECT * FROM local WHERE uid='$cuid';");
    if ($db->num_rows()) {
      $db->next_record();
      $this->local=$db->Record;
    }
    return true;
  }

  /** Change l'identit� d'un utilisateur temporairement.
   * @global string $uid Utilisateur dont on prends l'identit�
   * @return TRUE si la session est correcte, FALSE sinon.
   */
   /**
    * 
    * @global m_mem       $mem
    * @global m_mysql     $db
    * @global m_err       $err
    * @global m_mysql     $mysql
    * @param type $uid
    * @return boolean
    */
  function su($uid) {
    global $cuid,$db,$err,$mysql;
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

    $mysql->reload_dbus();
    return true;
  }

  /** Retourne a l'identite d'origine de l'utilisateur apres su.
   * @return boolean TRUE si la session est correcte, FALSE sinon.
   */
   /**
    * 
    * @global m_mem       $mem
    * @global m_mysql     $mysql
    * @return boolean
    */
  function unsu() {
    global $cuid,$mysql;
    if (!$this->olduid)
	return false;
    $this->su($this->olduid);
    $this->olduid=0;
    $mysql->reload_dbus();
    return true;
  }


  /** Termine une session du bureau virtuel (logout)
   * @return boolean TRUE si la session a bien �t� d�truite, FALSE sinon.
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err       $err
    * @global m_mem       $mem
    * @global array       $classes
    * @global m_hooks     $hooks
    * @return boolean
    */
  function del_session() {
    global $db,$err,$cuid,$classes,$hooks;
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

    $hooks->invoke("alternc_del_session");
    
    session_unset();
    @session_destroy();
    return true;
  }

  /** Change le mot de passe de l'utilisateur courant.
   * @param string $oldpass Ancien mot de passe.
   * @param string $newpass Nouveau mot de passe
   * @param string $newpass2 Nouveau mot de passe (� nouveau)
   * @return boolean TRUE si le mot de passe a �t� chang�, FALSE sinon.
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err   $err
    * @global m_mem       $mem
    * @global m_admin     $admin
    * @param type $oldpass
    * @param type $newpass
    * @param type $newpass2
    * @return boolean
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

  /** Change les pr�f�rences administrateur d'un compte
   * @param integer $admlist Mode de visualisation des membres (0=large 1=courte)
   * @return boolean TRUE si les pr�f�rences ont �t� chang�es, FALSE sinon.
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err       $err
    * @global m_mem       $mem
    * @param type $admlist
    * @return boolean
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

  /** Envoie en mail le mot de passe d'un compte.
   * <b>Note : </b>On ne peut demander le mot de passe qu'une seule fois par jour.
   * TODO : Translate this mail into the localization program.
   * TODO : Check this function's !
   * @return boolean TRUE si le mot de passe a �t� envoy� avec succ�s, FALSE sinon.
   */
   /**
    * 
    * @global m_err       $err
    * @global m_mysql     $db
    * @global string      $L_HOSTING
    * @global string      $L_FQDN
    * @param type $login
    * @return boolean
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

  /** Change le mail d'un membre (premi�re etape, envoi du CookiE)
   * TODO : insert this mail string into the localization system
   * @param string $newmail Nouveau mail souhait� pour le membre.
   * @return string le cookie si le mail a bien �t� envoy�, FALSE sinon
   */
   /**
    * 
    * @global m_err       $err
    * @global m_mysql     $db
    * @global string      $L_HOSTING
    * @global string      $L_FQDN
    * @global m_mem       $mem
    * @param type $newmail
    * @return boolean
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

    $COOKIE=substr(md5(uniqid(rand(), true)),0,20);
    $KEY=substr(md5(uniqid(rand(), true)),0,6);
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
    $db->query("DELETE FROM chgmail WHERE uid='$cuid';");
    $db->query("INSERT INTO chgmail (cookie,ckey,uid,mail,ts) VALUES ('$COOKIE','$KEY','$cuid','$newmail',".time().");");
    $lts=time()-86400;
    $db->query("DELETE FROM chgmail WHERE ts<'$lts';");
    return $KEY;
  }

    /** Change le mail d'un membre (seconde etape, CookiE+cl� = application)
     * @param string $COOKIE Cookie envoy� par mail
     * @param string $KEY cl� affich�e � l'�cran
     * @param integer $uid Utilisateur concern� (on est hors session)
     * @return boolean TRUE si le mail a bien �t� modifi�, FALSE sinon
     */
     /**
      * 
      * @global m_err       $err
      * @global m_mysql     $db
      * @global string      $L_HOSTING
      * @global string      $L_FQDN
      * @param type $COOKIE
      * @param type $KEY
      * @param type $uid
      * @return boolean
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

      $db->query("UPDATE membres SET mail='".$db->f("mail")."' WHERE uid='$uid';");

      $db->query("DELETE FROM chgmail WHERE uid='$uid';");
      $lts=time()-86400;
      $db->query("DELETE FROM chgmail WHERE ts<'$lts';");
      return true;
    }

    /** Modifie le param�tre d'aide en ligne (1/0)
     * @param integer $show Faut-il (1) ou non (0) afficher l'aide en ligne
     */
     /**
      * 
      * @global m_mysql     $db
      * @global m_err       $err
      * @global m_mem       $mem
      * @param type $show
      */
    function set_help_param($show) {
      global $db,$err,$cuid;
      $err->log("mem","set_help_param",$show);
      $db->query("UPDATE membres SET show_help='$show' WHERE uid='$cuid';");
    }

    /** Dit si l'aide en ligne est demand�e
     * @return boolean TRUE si l'aide en ligne est demand�e, FALSE sinon.
     */
     /**
      * 
      * @return type
      */
    function get_help_param() {
      return $this->user["show_help"];
    }

    /** Affiche (echo) l'aide contextuelle
     * @param integer $file Num�ro de fichier d'aide � afficher.
     * @return boolean TRUE si l'aide contextuelle a �t� trouv�e, FALSE sinon
     */
   /**
    * 
    * @global m_err       $err
    * @param type $file
    * @param type $force
    * @return boolean
    */
  function show_help($file,$force=false) {
    global $err;
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

  /**
   * @param integer $uid
  */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err       $err
    * @param type $uid
    * @return boolean
    */
  function get_creator_by_uid($uid) {
    global $db,$err;
    $err->log("dom","get_creator_by_uid");
    $uid=mysql_real_escape_string(intval($uid));
    $db->query("select creator from membres where uid = '$uid';");
    if (! $db->next_record()) return false;
    return intval($db->f('creator') );
  }


  /**
   * Exports all the personnal user related information for an account.
   * @access private
   */
   /**
    * 
    * @global m_mysql     $db
    * @global m_err       $err
    * @return string
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

   /**
    * 
    * @global   int     $uid
    * @param type $v
    * @return boolean
    */
  function session_tempo_params_get($v) {
    global $uid;
    if (empty($_COOKIE['session'])) return false;
    $sid=$_COOKIE['session'];
    if ( empty($_SESSION[$sid.'-'.$uid]) ) { // si pas de session de params tempo
      return false;
    }
    $j=$_SESSION[$sid.'-'.$uid];
    $j=json_decode($j, true);
    if ( ! empty($j[$v] ) ) { // si on a bien qque chose a retourner :)
      return $j[$v];
    }
    return false;
  }

   /**
    * 
    * @global   int     $uid
    * @param type $k
    * @param type $v
    * @param type $ecrase
    * @return boolean
    */
  function session_tempo_params_set($k, $v, $ecrase=false) {
    global $uid;
    if (empty($_COOKIE['session'])) return false;
    $sid=$_COOKIE['session'];
    $p=Array();
    if ( ! empty($_SESSION[$sid.'-'.$uid]) ) {
      $p = json_decode($_SESSION[$sid.'-'.$uid], true);
    }
    if (! $ecrase && (isset($p[$k]) && is_array($p[$k])) && is_array($v) ) { 
      $v=array_merge($p[$k], $v); // overwrite entry with the same name
    }

    $p[$k]=$v;
    $_SESSION[$sid.'-'.$uid]=json_encode($p);
    return true;
  }

} /* Classe Membre */