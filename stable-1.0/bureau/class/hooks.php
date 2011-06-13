<?php
/*
 $Id: m_mysql.php,v 1.35 2005/12/18 09:51:32 cam.lafit Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 - 2010 by the AlternC Development Team.
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
 Original Author of file: Camille Lafitte
 Purpose of file: Manage hook system.
 ----------------------------------------------------------------------
*/
/**
 * MySQL user database management for AlternC.
 * This class manage user's databases in MySQL, and user's MySQL accounts.
 * 
 * @copyright    AlternC-Team 2002-2005 http://alternc.org/
 */
class hooks {

  private $dir_hook = '/var/alternc/bureau/hooks/';
  private $plugins = array();

  /*---------------------------------------------------------------------------*/
  /** Constructor
  * hooks([$mid]) Constructeur de la classe hooks, ne fait rien pour le moment
  */
  function hooks() {
  }

  /** 
  * execute un hook determiné
  */
  function invoke() {
    $args = func_get_args();
    $name = $args[0];
    $data = $args[1];
    $this->load_hooks();
    return $this->run_hook($name,$data);    
  }
  /*
  * charge l'ensemble des hooks disponibles
  * pas de gestion de cache, on charge tout à l'invocation
  */
  private function load_hooks() {
    $numargs = func_num_args();
    if ($numargs <> 0)
      return false;
    $args = func_get_args();
    if (!is_dir($this->dir_hook))
      return false;
    foreach(scandir($this->dir_hook) as $file) {
      if ($file === '.' || $file === '..')
        continue;
      $this->plugins[] = basename($file,'.php');
      include($this->dir_hook.$file);
    
    }
  }
  
  /*
  * charge l'ensemble des fonctions disponible pour un hook donné
  */
  private function run_hook() {
    $output = '';
    $numargs = func_num_args();
    if ($numargs <> 2)
      return false;
    $args = func_get_args();
    $hook = $args[0];
    foreach($this->plugins as $plugin) {
      if (function_exists($plugin."_".$hook))
        $output .= call_user_func_array($plugin."_".$hook,array($args[1]));
    }
  return $output;
  }
} /* Class hooks */

?>
