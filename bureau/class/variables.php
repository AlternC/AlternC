<?php

/*
 * $Id: variables.php,v 1.8 2005/04/02 00:26:36 anarcat Exp $
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
 */

/**
 * Persistent variable table
 *
 * @author Drupal Developpement Team
 * @link http://cvs.drupal.org/viewcvs/drupal/drupal/includes/bootstrap.inc?rev=1.38&view=auto
 */

/**
 * Load the persistent variable table.
 *
 * The variable table is composed of values that have been saved in the table
 * with variable_set() as well as those explicitly specified in the configuration
 * file.
 */
function variable_init($conf = array()) {
  global $db;
  $result = $db->query('SELECT * FROM `variable`');
  while ($db->next_record($result)) {
    /* maybe the data is *not* serialized, in that case, take it verbatim */
    $variable = $db->Record;
    if (($variables[$variable['name']] = @unserialize($variable['value'])) === FALSE) {
      $variables[$variable['name']] = $variable['value'];
    }
  }
  
  foreach ($conf as $name => $value) {
    $variables[$name] = $value;
  }

  return $variables;
}

/**
 * Initialize the global $conf array if necessary
 *
 * @global $conf the global conf array
 * @uses variable_init()
 */
function variable_init_maybe() {
  global $conf;
  if (!isset($conf)) {
    $conf = variable_init();
  }
}

/**
 * Return a persistent variable.
 *
 * @param $name
 *   The name of the variable to return.
 * @param $default
 *   The default value to use if this variable has never been set.
 * @return
 *   The value of the variable.
 * @global $conf
 *   A cache of the configuration.
 */
function variable_get($name, $default = null) {
  global $conf;

  variable_init_maybe();
  return isset($conf[$name]) ? $conf[$name] : $default;
}

/**
 * Set a persistent variable.
 *
 * @param $name
 *   The name of the variable to set.
 * @param $value
 *   The value to set. This can be any PHP data type; these functions take care
 *   of serialization as necessary.
 */
function variable_set($name, $value, $comment=null) {
  global $conf, $db;

  $conf[$name] = $value;
  if (is_object($value) || is_array($value)) {
    $value = serialize($value);
  }

  if ( is_null($comment) ) {
    $query = "INSERT INTO variable (name, value) values ('".$name."', '".$value."') on duplicate key update name='$name', value='$value';";
  } else {
    $comment=mysql_real_escape_string($comment);
    $query = "INSERT INTO variable (name, value, comment) values ('".$name."', '".$value."', '$comment') on duplicate key update name='$name', value='$value', comment='$comment';";
  }

  $db->query("$query");

  variable_init_maybe();
}

/**
 * Unset a persistent variable.
 *
 * @param $name
 *   The name of the variable to undefine.
 */
function variable_del($name) {
  global $conf, $db;

  $db->query("DELETE FROM `variable` WHERE name = '".$name."'");

  unset($conf[$name]);
}

function variables_list() {
  global $db;
  $t=array();
  $db->query("SELECT * FROM `variable` WHERE `comment` IS NOT NULL ORDER BY `name`");
  while ($db->next_record()) {
    $t[]=$db->Record;
  }
  return $t;
}

?>
