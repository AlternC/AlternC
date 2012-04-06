<?php
/*
 $Id: m_err.php,v 1.4 2004/05/19 14:23:06 benjamin Exp $
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
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des erreurs apparaissant lors d'appels API.
*
* <p>Cette classe gère les erreurs qui peuvent apparaitre lors d'appels
* à l'API d'AlternC. Ces erreurs sont stockées sous la forme de 2 nombres
* (Classe ID et Numéro d'erreur) ainsi qu'un texte facultatif associé.
* Des textes d'erreurs localisés sont aussi disponibles.</p>
* <p>Cette classe se charge aussi d'insérer les appels à l'API d'AlternC
* dans les logs du système dans /var/log/alternc/bureau.log
* </p>
* Copyleft {@link http://alternc.net/ AlternC Team}
* 
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*/
class m_err {

  /** Numero de classe d'erreur actuelle */
  var $clsid=0;

  /** Dernière erreur enregistrée par la classe */
  var $error=0;

  /** Paramètre chaine eventuellement associé à la dernière erreur */
  var $param="";

  /** Emplacement du fichier de logs d'AlternC */
  var $logfile="/var/log/alternc/bureau.log"; 

  /**
   * Leve une erreur, signale celle-ci dans les logs et stocke le code erreur
   * 
   * Cette fonction lance une erreur, l'ajoute dans les logs d'AlternC, 
   * et la met à disposition pour le bureau virtuel pour affichage ultérieur.
   *
   * @todo ne plus utiliser $error de façon numérique, nulle part
   *
   * @param integer $clsid Classe qui lève l'erreur
   * @param mixed $error Numéro de l'erreur ou chaîne décrivant l'erreur
   * @param string $param Paramètre chaine associé à l'erreur (facultatif)
   * @return boolean TRUE si l'erreur est connue, FALSE sinon.
   *
   */
  function raise($clsid,$error,$param="") {
    /* Leve une exception. Si elle existe, sinon, stocke un message d'erreur sur erreur ...*/
    if (_("err_".$clsid."_".$error)!="err_".$clsid."_".$error || is_string($error)) {
      $this->clsid=$clsid;
      $this->error=$error;
      $args = func_get_args();
      $this->param=array_slice($args, 2);
      $this->logerr();
      return true;
    } else {
      $this->clsid="err";
      $this->error=1;
      $this->param="Error # $error in Class $clsid, Value is $param. (sorry, no text for this error in your language at the moment)";
      $this->logerr();
      return false;
    }
  }

  /**
   * Retourne la chaine d'erreur correspondant à la dernière erreur rencontrée
   *
   * Si la dernière erreur rencontrée est connue, retourne l'erreur en toute lettre
   * dans la langue actuellement sélectionnée, ou en anglais par défaut.
   * Si l'erreur n'est pas connue, retourne son numéro de classe et d'ereur.
   *
   * @return string Chaine d'erreur.
   *
   */
  function errstr() {
    if (is_string($this->error)) {
      $str = _("err_".$this->clsid."_generic: ")._($this->error)."\n";
    } else {
      $str = _("err_".$this->clsid."_".$this->error)."\n";
    }
    $args = $this->param;
    if (is_array($args)) {
      array_unshift($args, $str);
      $msg = call_user_func_array("sprintf", $args);
      return $msg;
    } else {
      return $args;
    }
  }

  /**
   * Envoi un log d'erreur dans /var/log/alternc/bureau.log
   *
   * Cette fonction Loggue la dernière erreur dans /var/log sur la machine,
   * permettant ainsi aux admins de savoir ce qu'il se passe...
   * Elle est appelée automatiquement par error
   * @access private
   */
  function logerr() {
    global $mem;
    $f=@fopen($this->logfile,"ab");
    if ($f) {
      fputs($f,date("d/m/Y H:i:s")." - ERROR - ");
      fputs($f,$mem->user["login"]." - ");
      fputs($f,$this->errstr());
      fclose($f);
    }
  }

  /**
   * Envoi un log d'appel d'API dans /var/log/alternc/bureau.log
   *
   * Cette fonction loggue dans /var/log l'appel à la fonction de l'API
   * d'AlternC.
   *
   * @param integer $clsid Numéro de la classe dont on a appelé une fonction
   * @param string $function Nom de la fonction appelée
   * @param string $param Paramètre (facultatif) passés à la fonction de l'API.
   * @return boolean TRUE si le log a été ajouté, FALSE sinon
   *
   */
  function log($clsid,$function,$param="") {
    global $mem,$cuid;
    $f=@fopen($this->logfile,"ab");
    if ($f) {
      if (!isset($_SERVER['REMOTE_ADDR'])) $_SERVER['REMOTE_ADDR']="::1";
      fputs($f,date("d/m/Y H:i:s")." - " . $_SERVER['REMOTE_ADDR'] . " - CALL - ");
      fputs($f,$mem->user["login"]." - ");
      fputs($f,$clsid." - ".$function." - ".$param."\n");
      fclose($f);
      return true;
    } else {
      return false;
    }
  }

}; /* Classe m_err */

?>
