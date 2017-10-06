<?php
/*
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
    
        // This is the old method, deprecation warning 
        $this->deprecated();
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
            // new way of handling errors: message directly in the class
            $str = $this->error."\n";
        } else {
            // old way: message in the locales files (ugly)
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
        @file_put_contents($this->logfile, date("d/m/Y H:i:s")." - ERROR - ".$mem->user["login"]." - ".$this->errstr(), FILE_APPEND );
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
        // This is the old method, deprecation warning 
        $this->deprecated();
        return @file_put_contents($this->logfile,date("d/m/Y H:i:s")." - " .get_remote_ip(). " - CALL - ".$mem->user["login"]." - $clsid - $function - $param\n", FILE_APPEND );
    }
    /**
     * This method is present in order to allow slow deprecation 
     */
    function deprecated(){
        global $msg;
        $trace = debug_backtrace();
        $caller = $trace[2];
        $msg->raise( "error","err","Deprecation warning: The old messaging class is still used by ".json_encode( $caller ));
    }

} /* Classe m_err */


