<?php
/*
 $Id: m_ftp.php,v 1.12 2005/12/18 09:51:32 benjamin Exp $
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
 Purpose of file: Manage FTP accounts
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des comptes FTP de l'hébergé.
*
* Cette classe permet de gérer les Comptes FTP d'un membre hébergé.<br />
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/
class m_ftp {

  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_ftp() {
  }

  /* ----------------------------------------------------------------- */
  /**
   * Quota name
   */
  function alternc_quota_names() {
    return "ftp";
  }


  /* ----------------------------------------------------------------- */
  /**
   * Password kind used in this class (hook for admin class)
   */
  function alternc_password_policy() {
    return array("ftp"=>"FTP accounts");
  }


  // Return the values needed to activate security access. See get_auth_class()
  // in authip for more informations
  function authip_class() {
    $c = Array();
    $c['name']="FTP";
    $c['protocol']="ftp";
    $c['values']=Array();

    $tt = $this->get_list();
    if (empty($tt) || !is_array($tt)) return $c;
    foreach ($this->get_list() as $v ) {
      $c['values'][$v['id']]=$v['login'];
    }

    return $c;
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des comptes FTP du compte hébergé
   * Retourne la liste des comptes FTP sous forme de tableau indexé de
   * tableaus associatifs comme suit :
   * $a["id"]= ID du compte ftp
   * $a["login"]= Nom de login du compte
   * $a["pass"]= Mot de passe du compte
   * $a["dir"]= Dossier relatif à la racine du compte de l'utilisateur
   * @return array Retourne le tableau des comptes ou FALSE si une erreur s'est produite.
   */
  function get_list() {
    global $db,$err,$cuid;
    $err->log("ftp","get_list");
    $r=array();
    $db->query("SELECT id, name, homedir FROM ftpusers WHERE uid='$cuid' ORDER BY homedir;");
    if ($db->num_rows()) {
      while ($db->next_record()) {
	      // On passe /var/alternc/html/u/user
	      $tr=preg_match("/^\/var\/alternc\/html\/.\/[^\/]*\/(.*)$/", $db->f("homedir"),$match);    /* " */
	      $r[]=array(
		        "id"=>$db->f("id"),
		        "login"=>$db->f("name"),
		        //"dir"=>$match[1]
		        "dir"=>$db->f("homedir")
		   );
      }
      return $r;
    } else {
      $err->raise("ftp",1);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne les détails d'un compte FTP (voir get_list)
   * Le tableau est celui du compte d'id spécifié
   * @param integer $id Numéro du compte dont on souhaite obtenir les détails
   * @return array Tableau associatif contenant les infos du comptes ftp
   */
  function get_ftp_details($id) {
    global $db,$err,$cuid;
    $err->log("ftp","get_ftp_details",$id);
    $r=array();
    $db->query("SELECT id, name, homedir FROM ftpusers WHERE uid='$cuid' AND id='$id';");
    if ($db->num_rows()) {
      $db->next_record();
      $tr=preg_match("/^\/var\/alternc\/html\/.\/[^\/]*\/(.*)$/", $db->f("homedir"),$match);
      $lg=explode("_",$db->f("name"));
      if ((!is_array($lg)) || (count($lg)!=2)) {
	      $lg[0]=$db->f("name");
	      $lg[1]="";
      }
      $r[]=array(
		   "id"=>$db->f("id"),
		   "prefixe"=> $lg[0],
		   "login"=>$lg[1],
		   "dir"=>$match[1]
		   );
	return $r;
    } else {
      $err->raise("ftp",2);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des prefixes utilisables par le compte courant
   * @return array tableau contenant la liste des prefixes (domaines + login)
   *  du compte actuel.
   */
  function prefix_list() {
    global $db,$mem,$cuid;
    $r=array();
    $r[]=$mem->user["login"];
    $db->query("SELECT domaine FROM domaines WHERE compte='$cuid' ORDER BY domaine;");
    while ($db->next_record()) {
      $r[]=$db->f("domaine");
    }
    return $r;
  }

  /* ----------------------------------------------------------------- */
  /** Affiche (ECHO) la liste des prefixes disponibles sous forme de champs d'option
   * Les champs sont affichés sous la forme <option>prefixe</option>...
   * La valeur $current se voit affublée de la balise SELECTED.
   * @param string $current Prefixe sélectionné par défaut
   * @return boolean TRUE.
   */
  function select_prefix_list($current) {
    $r=$this->prefix_list();
    reset($r);
    while (list($key,$val)=each($r)) {
      if ($current==$val) $c=" selected=\"selected\""; else $c="";
      echo "<option$c>$val</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Modifie les paramètres du comptes FTP $id.
   * @param integer $id Numéro du compte dont on veut modifier les paramètres
   * @param string $prefixe Prefixe du compte FTP
   * @param string $login login ajouté au préfixe ($prefixe_$login)
   * @param string $pass mot de passe
   * @param string $dir Répertoire racine du compte
   * @return boolean TRUE si le compte a été modifié, FALSE si une erreur est survenue.
   */
  function put_ftp_details($id,$prefixe,$login,$pass,$dir) {
    global $mem,$db,$err,$bro,$cuid,$admin;
    $err->log("ftp","put_ftp_details",$id);
    $db->query("SELECT count(*) AS cnt FROM ftpusers WHERE id='$id' and uid='$cuid';");
    $db->next_record();
    if (!$db->f("cnt")) {
      $err->raise("ftp",2);
      return false;
    }
    $dir=$bro->convertabsolute($dir);
    if (substr($dir,0,1)=="/") {
      $dir=substr($dir,1);
    }
    $r=$this->prefix_list();
    if (!in_array($prefixe,$r)) {
      $err->raise("ftp",3);
      return false;
    }
    $lo=$mem->user["login"];
    $l=substr($lo,0,1);
    if ($login) $login="_".$login;
    $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE id!='$id' AND name='$prefixe$login';");
    $db->next_record();
    if ($db->f("cnt")) {
      $err->raise("ftp",4);
      return false;
    }
    $absolute="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/$dir";
    if (!file_exists($absolute)) {
      system("/bin/mkdir -p $absolute");
    }
    if (!is_dir($absolute)) {
      $err->raise("ftp",6);
      return false;
    }
    if (trim($pass)) {

      // Check this password against the password policy using common API : 
      if (is_callable(array($admin,"checkPolicy"))) {
        if (!$admin->checkPolicy("ftp",$prefixe.$login,$pass)) {
          return false; // The error has been raised by checkPolicy()
        }
      }
      $encrypted_password = crypt($pass,strrev(microtime(true)));
      $db->query("UPDATE ftpusers SET name='".$prefixe.$login."', password='', encrypted_password='$encrypted_password', homedir='/var/alternc/html/$l/$lo/$dir', uid='$cuid' WHERE id='$id';");
    } else {
      $db->query("UPDATE ftpusers SET name='".$prefixe.$login."', homedir='/var/alternc/html/$l/$lo/$dir', uid='$cuid' WHERE id='$id';");
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Efface le compte ftp spécifié.
   * @param integer $id Numéro du compte FTP à supprimer.
   * @return boolean TRUE si le compte a été effacé, FALSE sinon.
   */
  function delete_ftp($id) {
    global $db,$err,$cuid;
    $err->log("ftp","delete_ftp",$id);
    $db->query("SELECT name FROM ftpusers WHERE id='$id' and uid='$cuid';");
    $db->next_record();
    $name=$db->f("name");
    if (!$name) {
      $err->raise("ftp",2);
      return false;
    }
    $db->query("DELETE FROM ftpusers WHERE id='$id'");
    return $name;
  }

  /* ----------------------------------------------------------------- */
  /** Crée un nouveau compte FTP.
   * @param string $prefixe Prefixe au login
   * @param string $login Login ftp (login=prefixe_login)
   * @param string $pass Mot de passe FTP
   * @param string $dir Répertoire racine du compte relatif à la racine du membre
   * @return boolean TRUE si le compte a été créé, FALSE sinon.
   *
   */
  function add_ftp($prefixe,$login,$pass,$dir) {
    global $mem,$db,$err,$quota,$bro,$cuid,$admin;
    $err->log("ftp","add_ftp",$prefixe."_".$login);
    $dir=$bro->convertabsolute($dir);
    if (substr($dir,0,1)=="/") {
      $dir=substr($dir,1);
    }
    $r=$this->prefix_list();
    if (!in_array($prefixe,$r) || $prefixe=="") {
      $err->raise("ftp",3);
      return false;
    }
    if ($login) $login="_".$login;
    $db->query("SELECT count(*) AS cnt FROM ftpusers WHERE name='".$prefixe.$login."'");
    $db->next_record();
    if ($db->f("cnt")) {
      $err->raise("ftp",4);
      return false;
    }
    $db->query("SELECT login FROM membres WHERE uid='$cuid';");
    $db->next_record();
    $lo=$db->f("login");
    $l=substr($lo,0,1);
    $absolute="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/$dir";
    if (!file_exists($absolute)) {
      system("/bin/mkdir -p $absolute");
    }
    if (!is_dir($absolute)) {
      $err->raise("ftp",6);
      return false;
    }

    // Check this password against the password policy using common API : 
    if (is_callable(array($admin,"checkPolicy"))) {
      if (!$admin->checkPolicy("ftp",$prefixe.$login,$pass)) {
	      return false; // The error has been raised by checkPolicy()
      }
    }

    if ($quota->cancreate("ftp")) {
      $encrypted_password = crypt($pass,strrev(microtime(true)));
      $db->query("INSERT INTO ftpusers (name,password, encrypted_password,homedir,uid) VALUES ('".$prefixe.$login."', '', '$encrypted_password', '/var/alternc/html/$l/$lo/$dir', '$cuid')");
      return true;
    } else {
      $err->raise("ftp",5);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne TRUE si $dir possède un compte FTP
   * @param string $dir Dossier à tester, relatif à la racine du compte courant
   * @return boolean retourne TRUE si $dir à un compte FTP, FALSE sinon.
   */
  function is_ftp($dir) {
    global $mem,$db,$err;
    $err->log("ftp","is_ftp",$dir);
    $lo=$mem->user["login"];
    $l=substr($lo,0,1);
    if (substr($dir,0,1)=="/") $dir=substr($dir,1);
    $db->query("SELECT id FROM ftpusers WHERE homedir='/var/alternc/html/$l/$lo/$dir';");
    if ($db->num_rows()) {
      $db->next_record();
      return $db->f("id");
    } else {
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Fonction appellée par domains quand un deomaine est supprimé pour le membre
   * @param string $dom Domaine à détruire.
   * @access private
   */
  function alternc_del_domain($dom) {
    global $db,$err,$cuid;
    $err->log("ftp","del_dom",$dom);
    $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE uid='$cuid' AND name LIKE '$dom%'");
    $db->next_record();
    $cnt=$db->Record["cnt"];
    $db->query("DELETE FROM ftpusers WHERE uid='$cuid' AND name LIKE '$dom%'");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Fonction appellée par membres quand un membre est effacé.
   * @param integer $uid Numéro de membre effacé.
   * @access private
   */
  function alternc_del_member() {
    global $db,$err,$cuid;
    $err->log("ftp","del_member");
    $db->query("DELETE FROM ftpusers WHERE uid='$cuid'");
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
    if ($name=="ftp") {
      $err->log("ftp","getquota");
      $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE uid='$cuid'");
      $db->next_record();
      return $db->f("cnt");
    } else return false;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exporte toutes les informations ftp du compte AlternC
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export_conf() {
    global $db,$err;
    $err->log("ftp","export");
    $f=$this->get_list();
    $str="<table border=\"1\"><caption> FTP </caption>\n";
    foreach ($f as $d=>$v) {
      $str.="  <tr>\n";
      $str.="    <td>".$v["id"]."</td>\n";
      $str.="    <td>".($v["encrypted_password"])."</td>\n";
      $str.="    <td>".($v["login"])."</td>\n";
      $str.="    <td>".($v["dir"])."<td>\n";
      $str.="  </tr>\n";
    }
    $str.="</table>\n";
    return $str;
  }
  
  
} /* Class m_ftp */

?>
