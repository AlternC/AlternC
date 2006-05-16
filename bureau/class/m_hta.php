<?php
/*
 $Id: m_hta.php,v 1.5 2004/11/29 17:15:37 anonymous Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des dossiers protégés par .htaccess apache
* 
* Cette classe permet de gérer les dossiers protégés par login/pass
* par le système .htaccess d'apache.
* Copyleft {@link http://alternc.net/ AlternC Team}
* 
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
* 
*/
class m_hta {

  /*---------------------------------------------------------------------------*/
  /**
   * Constructeur de la classe m_webaccess, initialise le membre
   */
  function m_webaccess() {
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Crée un dossier à protéger (.htaccess et .htpasswd)
   * @param string $dir Répertoire relatif au dossier de l'utilisateur 
   * @return boolean TRUE si le dossier a été protégé avec succès, FALSE sinon
   */
  function CreateDir($dir) {
    global $mem,$bro,$err;
    $err->log("hta","createdir",$dir);
    $absolute=$bro->convertabsolute($dir,0);
    if (!$absolute) {
      $err->raise("hta",8,$dir);
      return false;
    }
    if (!file_exists($absolute)) {
      mkdir($absolute,00777);
    }
    if (!file_exists("$absolute/.htaccess")) {
      touch("$absolute/.htaccess");
      $file = fopen("$absolute/.htaccess","r+");
      fseek($file,0);
      $param="AuthUserFile $absolute/.htpasswd\nAuthName \"Zone Protégée\"\nAuthType Basic\nrequire valid-user\n";
      fwrite($file, $param);
      fclose($file);
    }
    if (!file_exists("$absolute/.htpasswd")) {
      touch("$absolute/.htpasswd");
      return true;
    }
    return true;
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Retourne la liste de tous les dossiers de l'utilisateur contenant un .htpasswd
   * @return array Tableau contenant la liste des dossiers protégés de l'utilisateur 
   */
  function ListDir() {
    global $err,$mem;
    $err->log("hta","listdir");
    $sortie=array();
    $absolute="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"];
    exec("find $absolute -name .htpasswd | sort", $sortie);
    if (!count($sortie)) {
      $err->raise("hta",4);
      return false;
    }
    for ($i=0;$i<count($sortie);$i++){
      preg_match("/^\/var\/alternc\/html\/.\/[^\/]*\/(.*)\/\.htpasswd/", $sortie[$i], $matches);
      $r[$i]=$matches[1]."/";
    }
    return $r;
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Retourne TRUE si le dossier paramètre est protégé.
   * @param string $dir Dossier dont on souhaite vérifier la protection
   * @return TRUE si le dossier est protégé, FALSE sinon
   */
  function is_protected($dir){
    global $mem,$err;
    $err->log("hta","is_protected",$dir);
    $absolute="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/$dir";
    $sortie=array();
    if (file_exists("$absolute/.htpasswd")){
      return true;
    }
    else {
      return false;
    }
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Retourne la liste des utilisateurs autorisés dans le dossier
   * @param string $dir Dossier dont on souhaite obtenir la liste des user/pass
   * @return array Tableau contenant la liste des logins du .htpasswd ou FALSE.
   */
  function get_hta_detail($dir) {
    global $mem,$err;
    $err->log("hta","get_hta_detail");
    $absolute="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/$dir";
    if (file_exists("$absolute/.htaccess")) {
      /*		if (!_reading_htaccess($absolute)) {
			return false;
			}
      */	}
    $file = fopen("$absolute/.htpasswd","r");
    $i=0;
    $res=array();
    fseek($file,0);
    // TODO: Tester la validité du .htpasswd
    while (!feof($file)) {
      $s=fgets($file,1024);
      $t=explode(":",$s);
      if ($t[0]!=$s) {
	$res[$i]=$t[0];
	$i=$i+1;
      }
    }
    fclose($file);
    return $res;
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Déprotège un dossier 
   * @param string $dir Dossier à déprotéger
   * @return boolean TRUE si le dossier a été déprotégé, FALSE sinon
   */
  function DelDir($dir) {
    global $mem,$bro,$err;
    $err->log("hta","deldir",$dir);
    $dir=$bro->convertabsolute($dir,0);
    if (!$dir) {
      $err->raise("hta",8,$dir);
      return false;
    }
    if (!unlink("$dir/.htaccess")) {
      $err->raise("hta",5,$dir);
      return false;
    }
    if (!unlink("$dir/.htpasswd")) {
      $err->raise("hta",6,$dir);
      return false;
    }
    return true;
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Ajoute un utilisateur à un dossier protégé. 
   * @param string $login Utilisateur à ajouter
   * @param string $password Mot de passe à ajouter (en clair)
   * @param string $dir Dossier concerné 
   * @return boolean TRUE si l'utilisateur a été ajouté avec succès, FALSE sinon
   */
  function add_user($user,$password,$dir) {
    global $err, $bro;
    $err->log("hta","add_user",$user."/".$dir);
    $absolute=$bro->convertabsolute($dir,0);
    if (!file_exists($absolute)) {
      $err->raise("hta",8,$dir);
      return false;
    }
    if (checkloginmail($user)){
      $file = fopen("$absolute/.htpasswd","a+");
      fseek($file,0);
      while (!feof($file)) {
	$s=fgets($file,1024);
	$t=explode(":",$s);
	if ($t[0]==$user) {
	  $err->raise("hta",10,$user);
	  return false;
	}
      }
      fseek($file,SEEK_END);
      if (substr($t[1],-1)!="\n") {
	fwrite($file,"\n");
      }
      fwrite($file, "$user:"._md5cr($password)."\n");
      fclose($file);
      return true;
    } else {
      $err->raise("hta",11);
      return false;
    }
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Supprime un ou plusieurs utilisateurs d'un dossier protégé.
   * @param array $lst Tableau des logins à supprimer.
   * @param string $dir Dossier dans lequel on souhaite supprimer des utilisateurs
   * @return boolean TRUE si les utilisateurs ont été supprimés avec succès, FALSE sinon
   */
  function del_user($lst,$dir) {
    global $bro,$err;
    $err->log("hta","del_user",$lst."/".$dir);
    $absolute=$bro->convertabsolute($dir,0);
    if (!file_exists($absolute)) {
      $err->raise("hta",8,$dir);
      return false;
    }
    touch("$absolute/.htpasswd.new");
    $file = fopen("$absolute/.htpasswd","r");
    $newf = fopen("$absolute/.htpasswd.new","a");
    reset($lst);
    fseek($file,0);
    while (!feof($file)) {
      $s=fgets($file,1024);
      $t=explode(":",$s);
      if (!in_array($t[0],$lst) && ($t[0]!="\n")) {
	fseek($newf,0);
	fwrite($newf, "$s");
      }
    }
    fclose($file);
    fclose($newf);
    unlink("$absolute/.htpasswd");
    rename("$absolute/.htpasswd.new", "$absolute/.htpasswd");
    return true;
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Change le mot de passe d'un utilisateur d'un dossier protégé.
   * @param string $user Utilisateur dont on souhaite changer le mot de passe 
   * @param string $newpass Nouveau mot de passe de cet utilisateur
   * @param string $dir Dossier protégé concerné
   * @return boolean TRUE si le mot de passe a été changé avec succès, FALSE sinon
   */
  function change_pass($user,$newpass,$dir) {
    global $bro,$err;
    $err->log("hta","change_pass",$user."/".$dir);
    $absolute=$bro->convertabsolute($dir,0);
    if (!file_exists($absolute)) {
      $err->raise("hta",8,$dir);
      return false;
    }
    touch("$absolute/.htpasswd.new");
    $file = fopen("$absolute/.htpasswd","r");
    $newf = fopen("$absolute/.htpasswd.new","a");
    while (!feof($file)) {
      $s=fgets($file,1024);
      $t=explode(":",$s);
      if ($t[0]!=$user) {
	fwrite($newf, "$s");
      }
    }
    fwrite($newf, "$user:"._md5cr($newpass)."\n");
    fclose($file);
    fclose($newf);
    unlink("$absolute/.htpasswd");
    rename("$absolute/.htpasswd.new", "$absolute/.htpasswd");
    return true;
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Vérifie la validité des lignes d'un .htaccess existant.
   * @param string $absolute Dossier que l'on souhaite vérifier
   * @return boolean TRUE si le dossier est correctement protégé par un .htaccess, FALSE sinon
   * @access private
   */
  function _reading_htaccess($absolute) {
    global $err;
    $err->log("hta","_reading_htaccess",$absolute);
    $file = fopen("$absolute/.htaccess","r+");
    $lignes=array(1,1,1);
    $errr=0;
    while (!feof($file) && !$errr) {
      $s=fgets($file,1024);
      if (substr($s,0,12)!="RewriteCond " && substr($s,0,14)!="ErrorDocument " && substr($s,0,12)!="RewriteRule " && substr($s,0,14)!="RewriteEngine " && trim($s)!="") {
	$errr=1;
      }
      if (strtolower(trim($s))==strtolower("authuserfile $absolute/.htpasswd")) {
	$lignes[0]=0;
	$errr=0;
      } // authuserfile
      if (strtolower(trim($s))=="require valid-user") {
	$lignes[1]=0;
	$errr=0;
      } //require
      if (strtolower(trim($s))=="authtype basic") {
	$lignes[2]=0;
	$errr=0;
      } //authtype
    } // Reading config file
    fclose($file);
    if ($errr ||  in_array(0,$lignes)) {
      $err->raise("hta",1);
      return false;
    }
    return true;
  } 

} /* CLASS m_webaccess */

?>