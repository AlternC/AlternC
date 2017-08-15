<?php

/*
  $Id: m_messages.php,v 1.4 2004/05/19 14:23:06 benjamin Exp $
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
 * Classe de gestion des messages apparaissant lors d'appels API.
 *
 * <p>Cette classe gère les messages qui peuvent apparaitre lors d'appels
 * à l'API d'AlternC. Ces msgs sont stockées sous la forme d'1 nombre
 * (Classe ID) ainsi que du msg en associé.
 * Des messages localisés sont aussi disponibles.</p>
 * <p>Cette classe se charge aussi d'insérer les appels à l'API d'AlternC
 * dans les logs du système dans /var/log/alternc/bureau.log
 * </p>
 * Copyleft {@link http://alternc.net/ AlternC Team}
 * 
 * @copyright    AlternC-Team 2002-11-01 http://alternc.net/
 */
class m_messages {

    /** Tableau qui va contenir les messages et leur id */
    var $arrMessages = array();

    /** Emplacement du fichier de logs d'AlternC */
    var $logfile = "/var/log/alternc/bureau.log";

    /** Liste of possible type */
    var $ARRTYPES = array("ERROR", "ALERT", "INFO", "OK");

    /** Associate css classes */
    var $ARRCSS = array(
	"ERROR" => "alert-danger",
	"ALERT" => "alert-warning",
	"INFO" => "alert-info",
	"OK" => "alert-success"
    );

    public function __construct() {
	$this->init_msgs();
    }

    /**
     * Enregistre un message, signale celle-ci dans les logs
     * 
     * Cette fonction enregistre un message, l'ajoute dans les logs d'AlternC, 
     * et la met à disposition pour le bureau virtuel pour affichage ultérieur.
     *
     * @param string $cat The category of the msg array to work with
     * @param integer $clsid Classe qui lève le message
     * @param mixed $msg Message
     * @param string $param Paramètre chaine associé au message (facultatif)
     * @return boolean TRUE si le msg est enregistré, FALSE sinon.
     *
     */
    function raise($cat = "Error", $clsid, $msg, $param = "") {
	$arrInfos  = array();

	$type = strtoupper($cat);
	if (! in_array($type, $this->ARRTYPES)) {
	    return false;
	}

	$arrInfos['clsid'] = $clsid;
	$arrInfos['msg'] = $msg;
	$arrInfos['param'] = is_array($param)?$param:(empty($param)?"":array($param));

	$this->arrMessages[$type][] = $arrInfos;

        $this->logAlternC($cat);
        return true;
    }

    function init_msgs() {
	// Initialisation du tableau des message
	foreach ($this->ARRTYPES as $v) {
	    $this->arrMessages[$v] = array();
	}
    }

    /**
     * Indique s'il y a ds msgs enregistrés pour une catégorie si le param $cat contient une catégorie
     * ou pour toutesl es catégories si $cat est vide
     *
     * @param string $cat The category of the msg array to work with
     * @return boolean True if there is/are msg recorded.
     *
     */
    function has_msgs($cat) {
	$type = strtoupper($cat);
	if (in_array($type, $this->ARRTYPES)) {
	    return (count($this->arrMessages[$type]) > 0);
	} else {
	    foreach ($this->arrMessages as $v) {
		if (count($v) > 0)
		    return true;
	    }
	    return false;
	}
    }

    /**
     * Retourne la chaine de message concaténés de l'ensemble des msgs enregistrés
     * ou du dernièr message rencontré
     *
     * @param string $cat The category of the msg array to work with
     * @param string $sep The separator used to concatenate msgs
     * @param boolean $all show all the messages or only the last one
     *
     * @return string Message.
     *
     */
    function msg_str($cat = "Error", $sep = "<li>", $all = true) {
	$str = "";

	$type = strtoupper($cat);
	if (! in_array($type, $this->ARRTYPES)) {
	    return false;
	}

	if (! $this->has_msgs($cat))
	    return "";

	if ($all) {
	    foreach ($this->arrMessages[$type] as $k => $arrMsg) {
		$args = $arrMsg['param'];

		if (is_array($args) && count($args) > 0) {
		    array_unshift($args, $arrMsg['msg']);
		    if ($sep == "<li>")
		        $str .= "<li>" . call_user_func_array("sprintf", $args) . "</li>";
		    else
			$str .= call_user_func_array("sprintf", $args) . $sep;
		} else
		    if ($sep == "<li>")
		        $str .= "<li>" . $arrMsg['msg'] . "</li>";
		    else
			$str .= $arrMsg['msg'] . $sep;
            }

	    if ($sep == "<li>") 
		$str = "<ul>".$str."</ul>";

	} else {
	    $i = count($this->arrMessages[$type]) - 1;
	    if ($i > 0) {
		$arr_msg=$this->arrMessages[$type][$i];
		$args = $arr_msg['param'];
		if (is_array($args) && count($args) > 0) {
		    array_unshift($args, $arr_msg['msg']);
		    $str = call_user_func_array("sprintf", $args);
		} else
		    $str = $arr_msg['msgId'];
	    }
	}

	return $str;
    }

    /**
     * Retourn le message au format Html avec la class Css associée
     *
     * @param string $cat The category of the msg array to work with
     * @param string $sep The separator used to concatenate msgs
     * @param boolean $all show all the messages or only the last one
     *
     * @return string HTML message
     */
    function msg_html($cat = "Error", $sep = "<li>", $all = true) {
	$type = strtoupper($cat);
	if (! in_array($type, $this->ARRTYPES)) {
	    return false;
	}

	if (count($this->arrMessages[$type]) == 0)
	    return "";

	$str = $this->msg_str($cat, $sep, $all);
	$str = "<div class='alert " . $this->ARRCSS[$type] . "'>" . $str . "</div>";

	return $str;
    }

    /**
     * Retourn le message de toutes les catégories au format Html avec la class Css associée
     *
     * @param string $sep The separator used to concatenate msgs
     * @param boolean $all show all the messages or only the last one
     *
     * @return string HTML message
     */
    function msg_html_all($sep = "<li>", $all = true, $init = false) {
	$msg="";

	$msg.=$this->msg_html("Error", $sep, $all);
	$msg.=$this->msg_html("Ok", $sep, $all);
	$msg.=$this->msg_html("Info", $sep, $all);
	$msg.=$this->msg_html("Alert", $sep, $all);

	if ($init)
		$this->init_msgs();

	return $msg;
    }

    /**
     * Envoi un log dans /var/log/alternc/bureau.log
     *
     * Cette fonction Loggue le dernier msg dans /var/log sur la machine,
     * permettant ainsi aux admins de savoir ce qu'il se passe...
     * Elle est appelée automatiquement par error
     * @access private
     */
    function logAlternC($cat = "Error") {
        global $mem;

	$type = strtoupper($cat);
	if (! in_array($type, $this->ARRTYPES)) {
	    return false;
	}

        @file_put_contents($this->logfile, date("d/m/Y H:i:s") . " - " . get_remote_ip() . " - $type - " . $mem->user["login"] . " - " . $this->msg_str($cat, "", false), FILE_APPEND);
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
    function log($clsid, $function, $param = "") {
        global $mem;
        return @file_put_contents($this->logfile, date("d/m/Y H:i:s") . " - " . get_remote_ip() . " - CALL - " . $mem->user["login"] . " - $clsid - $function - $param\n", FILE_APPEND);
    }

}

/* Classe m_messages */
