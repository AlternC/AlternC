<?php
/*
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
 * This class manage debug.
 * 
 * @copyright    AlternC-Team 2002-2005 http://alternc.org/
 */
class m_debug_alternc {
  var $infos="";
  var $status=false;
  var $nb_sql_query=0;
  var $tps_sql_query=0;
  var $generation_started=null;

  /*---------------------------------------------------------------------------*/
  /** Constructor
  */
  function m_debug_alternc() {
    if ( isset($_COOKIE['alternc_debugme']) && $_COOKIE['alternc_debugme'] ) {
      $this->status=true;
      ini_set('display_errors', true);
    }
    $this->nb_sql_query=0;
    $this->tps_sql_query=0;
    $this->generation_started=microtime(true);
  }

  function activate() {
    setcookie('alternc_debugme',true, time()+3600); // expire in 1 hour
    $this->status="";
    return true;
  }

  function desactivate() {
    setcookie('alternc_debugme',false);
    $this->status=false;
    return true;
  }

  function add($txt) {
    $this->infos .= "\n$txt";
    return true;
  }

  function dump() {
    global $cuid;
    if ( $cuid!=2000 ) return false;
    if ( ! $this->status ) return false;

    $generation_time = (microtime(true) - $this->generation_started) * 1000;

    echo "<fieldset style='background-color: silver;'>";
    echo "<pre>";
    echo "+++ BEGIN Debug Mode+++\n";
    echo "Total generation time : $generation_time ms\n";
    print_r("\n--- Total SQL Query : ".$this->nb_sql_query." req / ".$this->tps_sql_query." ms ---\n");
    print_r($this->infos);
    echo "\n\n--- GET ---\n";
    print_r($_GET);
    echo "\n\n--- POST ---\n";
    print_r($_POST);
    echo "\n\n--- SESSION ---\n";
    @print_r($_SESSION);
    echo "\n\n--- COOKIE ---\n";
    print_r($_COOKIE);
    echo "\n\n--- SERVER ---\n";
    print_r($_SERVER);
    echo "\n\n+++ END Debug Mode+++";
    echo "</pre>";
    echo "</fieldset>";
    return true;
  }

} /* Class debug_alternc */

?>
