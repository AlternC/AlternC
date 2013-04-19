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
 Original Author of file: Lerider Steven
 Purpose of file: Manage generic actions.
 ----------------------------------------------------------------------
*/
/**
 * 
 * @copyright    AlternC-Team 2002-2013 http://alternc.org/
 */
class m_action {
  /*---------------------------------------------------------------------------*/
  /** Constructor
  */
  function m_action() {
  }
  /*
  * function to set the cration of a file 
  */
  function create_file($file,$user="root") {
    return $this->set('create_file', array('file'=>$file, 'user'=>$user));
  }
  /*
  * function to set the cration of a file 
  */
  function create_dir($dir,$user="root") {
    return $this->set('create_dir', array('dir'=>$dir,'user'=>$user));
  }
  /*
  * function to delete file / folder
  */
  function del($dir) {
    return $this->set('delete', array('dir'=>$dir,'user'=>$user));
  }
  /*
  * function returning the first not locked line of the action table 
  */
  function move($src,$dest) {
    return $this->set('move', array('src'=>$src, 'dst'=>$dst,'user'=$user));
  }
  /*
  * function archiving a directory ( upon account deletion )
  */
  function archive($dir) {
    return $this->set('archive', array('dir'=>$dir,'user'=>$user));
  }
  /*
  *function inserting the action in the sql table 
  */
  function set($type,$parameters) {
    global $db;
  }
  /*
  * function returning the first not locked line of the action table 
  */
  function get_action() {
    global $db;
    return true;
  }
  /*
  * function locking an entry while it is being executed by the action script
  */
  function begin($id) {
    global $db;
    return true;
  }
  /*
  * function locking an entry while it is being executed by the action script
  */
  function finish($id) {
    global $db;
    return true;
  }
  /*
  * function locking an entry while it is being executed by the action script
  */
  function cancel($id) {
    global $db;
    return true;
  }

} /* Class action */

?>
