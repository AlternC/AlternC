<?php
/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2000-2012 by the AlternC Development Team.
  https://alternc.org/
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
  Purpose of file: Manage hook system.
  ----------------------------------------------------------------------
*/

/**
 * This class manage hooks.
 * 
 * @copyright    AlternC-Team 2002-2005 http://alternc.org/
 */
class m_hooks {

  /*---------------------------------------------------------------------------*/
  /** Constructor
  * hooks([$mid]) Constructeur de la classe hooks, ne fait rien pour le moment
  */
  function m_hooks() {
  }

  /** 
    * invoke() permet de lancer une fonction donné en parametre dans toute les classes
    * connues de alternc, avec les parametres donnés.
    * $hname nom de la fonction "hooks" que l'on cherche dans les classes
    * $hparam tableau contenant les parametres
    * $hclass tableau contenant les classes spécifique qu'on veux appeler (si on veux pas TOUTE les appeler)
  */
  function invoke($hname, $hparam = array(), $hclass = null) {

    // Si $hclass est defini, on veut appeler le hooks QUE pour UNE 
    // classe et pas pour toute.
    if (is_null($hclass)) {
      global $classes;
    } else {
      if (is_array($hclass)) {
        $classes = $hclass;
      } else {
        $classes = array($hclass);
      }
    }

    // On parcourt les classes, si la fonction qu'on cherche
    // existe on l'execute et on rajoute ce qu'elle a retourné dans
    // un tableau
    $val = array();
    foreach ($classes as $c) {
      global $$c;
      if ( method_exists($$c, $hname) ) {
        //$val[$$c]=call_user_func_array(array($$c,$hname), $hparam);
        $val[$c]=call_user_func_array(array($$c,$hname), $hparam);
      }
    }

    // On retourne le tout
    return $val;
  }

  // $scripts a script or a directory
  // invoke each executable script of the directory (or the specified script)
  // with the parameters 
  function invoke_scripts($scripts, $parameters) {

    // First, build the list of script we want to launch
    $to_launch=array();
    if (is_file($scripts)) {
      if (is_executable($script)) {
        $to_launch[]=$scripts;
      }
    } else if (is_dir($scripts)) {
      foreach ( scandir($scripts) as $ccc ) {
        if (is_file($ccc) && is_executable($ccc)) {
          $to_launch[]=$ccc;
        }
      }
    } else {
      // not a file nor a directory
      return false;
    } 
  
    // Protect each parameters
    $parameters = array_map('escapeshellarg', $parameters);
    $params = implode(" ", $parameters);

    // Launch !
    foreach($to_launch as $fi) {
      system($fi." ".$params);
    }
    return true;
  }

} /* Class hooks */

