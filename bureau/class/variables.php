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
    $variables=array();
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
 * @param $createit_comment 
 *   If variable doesn't exist, create it with the default value
 *   and createit_comment value as comment
 * @return
 *   The value of the variable.
 * @global $conf
 *   A cache of the configuration.
 */
function variable_get($name, $default = null, $createit_comment = null) {
    global $conf;

    variable_init_maybe();

    if (isset($conf[$name])) {
        return $conf[$name];
    } elseif (!is_null($createit_comment)) {
        variable_set($name, $default, $createit_comment);
    }
    return $default;
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
function variable_set($name, $value, $comment = null) {
    global $conf, $db, $msg, $hooks;
    $msg->log('variable', 'variable_set', '+' . serialize($value) . '+' . $comment . '+');

    variable_init_maybe();
    
    if (is_object($value) || is_array($value)) {
        $value2 = serialize($value);
    } else {
        $value2 = $value;
    }
    if (array_key_exists($name, $conf)) {
        $previous = $conf[$name];
    } else {
        $previous = null;
    }
    if (!array_key_exists($name, $conf) || $value != $conf[$name]) {
        $conf[$name] = $value;
        if (empty($comment)) {
            $query = "INSERT INTO variable (name, value) values ( ?, ?) on duplicate key update name=  ?, value= ? ;"; 
            $query_args = array($name, $value2, $name, $value2);

        } else {
            $query = "INSERT INTO variable (name, value, comment) values ( ?, ?, ?) on duplicate key update name=  ?, value= ?, comment= ? ;"; 
            $query_args = array($name, $value2, $comment, $name, $value2, $comment);
        }
        $db->query($query, $query_args);
        $hooks->invoke("hook_variable_set", array("name" => $name, "old" => $previous, "new" => $value));
    }
}


/**
 * Unset a persistent variable.
 *
 * @param $name
 *   The name of the variable to undefine.
 */
function variable_del($name) {
    global $conf, $db;
    $db->query("DELETE FROM `variable` WHERE name = ?;", array($name));
    unset($conf[$name]);
}


/**
 * List all variables 
 */
function variables_list() {
    global $db;
    $t = array();
    $db->query("SELECT * FROM `variable` WHERE `comment` IS NOT NULL ORDER BY `name`");
    while ($db->next_record()) {
        $t[] = $db->Record;
    }
    return $t;
}

