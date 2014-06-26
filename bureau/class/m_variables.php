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
 * @
 */
class m_variables {
  var $strata_order = array('DEFAULT','GLOBAL','FQDN_CREATOR','FQDN','CREATOR','MEMBER','DOMAIN');
  var $cache_variable_list = false;
  var $replace_array = array();
  var $cache_conf = array();

  const TYPE_STRING                 = "string";
  const TYPE_BOOLEAN                = "boolean";
  const TYPE_INTEGER                = "integer";
  const TYPE_IP                     = "ip";
  
  /**
   * 
   * @global    string  $L_FQDN
   */
  function m_variables() {
    global $L_FQDN;
    $this->replace_array = array(
      "%%FQDN%%"=> $L_FQDN,
    );
  }

  
  
  /**
   *  used by get_impersonated to merge array. Son value overwrite father's value 
   * 
   * @param array $father
   * @param array $son
   * @return array
   */
  private function variable_merge($father, $son) {
    if (! is_array($son)) return $father;
    foreach ($son as $k=>$v) {
      $father[$k] = $v;
    }
    return $father;
  }

  /**
   * Load the persistent variable table.
   *
   * The variable table is composed of values that have been saved in the table
   * with variable_set() as well as those explicitly specified in the configuration
   * file.
   * 
   * @global int $cuid
   * @return array
   */
  function variable_init() {
    global $cuid;
    if ($cuid > 1999) {
      $mid = $cuid;
    } else {
      $mid = null;
    }

    // In case we launch it in a script, there is no $_SERVER
    if (isset($_SERVER['HTTP_HOST'])) {
      $host=$_SERVER['HTTP_HOST'];
    } else {
      $host=null;
    }
    return $this->get_impersonated($host, $mid);
  }

   /**
   * Return the var for a specific environnement :
   *   * logged via $fqdn url
   *   * the user is $uid
   *   * $var if we want only 1 var instead of all of them
   * 
   * If $fqdn and $uid aren't specified, return the default value
   * 
   * @global m_mysql $db
   * @global m_err $err
   * @param string $fqdn
   * @param int $uid
   * @param string $var
   * @return array
   */
  function get_impersonated($fqdn=null, $uid=null, $var=null) {
    global $db, $err;

    $arr_var=$this->variables_list();

    // Get some vars we are going to need.
    if ($fqdn != NULL) {
      $sub_infos=m_dom::get_sub_domain_id_and_member_by_name( strtolower($fqdn) );
    } else {
      $sub_infos=false;
    }

    if ( $uid != NULL ) {
      $creator=m_mem::get_creator_by_uid($uid);
    } else {
      $creator=false;
    }
   
    $variables = array();
    // Browse the array in the specific order of the strata
    foreach ( $this->strata_order as $strata) {
      if (! isset($arr_var[$strata]) || !is_array($arr_var[$strata])) continue;
      switch($strata) {
        case 'DEFAULT':
            
//          $variables = $this->variable_merge(array(),$arr_var['DEFAULT'][NULL]);
          $variablesList = current($arr_var["DEFAULT"]);
          $variables = $this->variable_merge(array(),$variablesList);
          break;
        case 'GLOBAL':
          $variables = $this->variable_merge($variables, $arr_var['GLOBAL'][NULL]);
          break;
        case 'FQDN_CREATOR':
          if ( is_array($sub_infos) && isset($arr_var['FQDN_CREATOR'][$sub_infos['member_id']]) && is_array($arr_var['FQDN_CREATOR'][$sub_infos['member_id']])) {
            $variables = $this->variable_merge($variables, $arr_var['FQDN_CREATOR'][$sub_infos['member_id']]);
          }
          break;
        case 'FQDN':
          if ( is_array($sub_infos) && isset($arr_var['FQDN'][$sub_infos['sub_id']]) && is_array($arr_var['FQDN'][$sub_infos['sub_id']])) {
            $variables = $this->variable_merge($variables, $arr_var['FQDN'][$sub_infos['sub_id']]);
          }
          break;
        case 'CREATOR':
          if ( $creator && isset($arr_var['CREATOR'][$creator]) && is_array($arr_var['CREATOR'][$creator])) {
            $variables = $this->variable_merge($variables, $arr_var['CREATOR'][$creator] );
          }
          break;
        case 'MEMBER':
          if ( $uid && isset($arr_var['MEMBER'][$uid]) && is_array($arr_var['MEMBER'][$uid])) {
            $variables = $this->variable_merge($variables, $arr_var['MEMBER'][$uid] );
          }
          break;
        case 'DOMAIN':
          //FIXME TODO
          break;
      } //switch

    } //foreach

    // Replace needed vars
    foreach ($variables as $vv => $hh) {
        if (!isset($hh['value']) || empty($hh['value'])) {
            continue;
        }
        if(  is_array($hh['value'])){
            foreach($hh["value"] as $key => $val){
                $variables[$vv]['value'][$key]    = strtr($hh['value'][$key], $this->replace_array );
            }
        }
        else{
            $variables[$vv]['value']    = strtr($hh['value'], $this->replace_array );
        }
    }

    if ($var && isset($variables[$var])) {
      return $variables[$var];
    } else {
      return $variables;
    }
  }

  /**
   * Initialize the global conf
   *
   * @uses variable_init()
   * @param boolean $force
   */
  function variable_init_maybe($force=false) {
    if ($force || empty($this->cache_conf) ) {
      $this->cache_variable_list = false;
      $this->cache_conf = $this->variable_init();
    }
  }

  /**
   * Return a persistent variable.
   *
   * @param     string  $name               The name of the variable to return.
   * @param     mixed   $default            The default value to use if this variable has never been set.
   * @param     string  $createit_comment   If variable doesn't exist, create it with the default value
   *                                        and createit_comment value as comment
   * @param     array   $type               An array defining the variable definition 
   *                                        ex: array("ns1"=>array('desc'=>'ns name','type'=>'string'),"ip"=>array("desc"=>"here an ip", "type"=>"ip")
   *                                        ex: array('desc'=>'ns name','type'=>'string')
   *                                        Types can be either 
   *                                        self::TYPE_BOOLEAN
   *                                        self::TYPE_INTEGER
   *                                        self::TYPE_IP
   *                                        self::TYPE_STRING
   * 
   * @return    mixed                       The value of the variable.
   */
  function variable_get($name, $default = null, $createit_comment = null, $type = null) {

    $this->variable_init_maybe();

    // Attempts to retrieve $name
    if (array_key_exists("$name", $this->cache_conf) && !is_null($this->cache_conf["$name"])) {
        return $this->cache_conf["$name"]["value"];
    } 
    if (!is_null($createit_comment)) {
      $this->variable_update_or_create($name, $default, 'DEFAULT', 'null', 'null', $createit_comment, $type);
    }

    return $default;
  }

  /**
   * Create or update a variable
   * 
   * @global m_mysql $db
   * @global m_err $err
   * @param string $var_name
   * @param mixed $var_value
   * @param string $strata
   * @param int $strata_id
   * @param int $var_id
   * @param string $comment
   * @param string $type
   * @return boolean
   */
  function variable_update_or_create($var_name, $var_value, $strata=null, $strata_id=null, $var_id=null, $comment=null, $type=null) {
    global $db, $err;
    $err->log('variable', 'variable_update_or_create');
    if ( strtolower($var_id) == 'null' ) $var_id = null;
    if ( strtolower($strata_id) == 'null' ) $strata_id = null;

    if (is_object($type) || is_array($type)) {
      $type = serialize($type);
    }
    if (is_object($var_value) || is_array($var_value)) {
      $var_value = serialize($var_value);
    }
    
    if ( ! is_null($var_id) ) {
      $sql="UPDATE variable SET value='".mysql_real_escape_string($var_value)."' WHERE id = ".intval($var_id);
    } else {
      if ( empty($strata) ) {
        $this->variable_init_maybe(true);
        $err->raise('variables', _("Err: Missing strata when creating var"));
        return false;
      }
      $sql="INSERT INTO 
              variable (name, value, strata, strata_id, comment, type) 
            VALUES (
              '".mysql_real_escape_string($var_name)."', 
              '".mysql_real_escape_string($var_value)."', 
              '".mysql_real_escape_string($strata)."', 
              ".( is_null($strata_id)?'NULL':"'".mysql_real_escape_string($strata_id)."'").",
              '".mysql_real_escape_string($comment)."',
              '".mysql_real_escape_string($type)."' );";
    }

    $db->query("$sql");

    $this->variable_init_maybe(true);
    return true;
  }

  /**
   * Unset a persistent variable.
   * 
   * @global m_mysql $db
   * @param int $id
   * @return type
   */
  function del($id) {
    global $db;
    $result = $db->query("DELETE FROM `variable` WHERE id = '".intval($id)."'");
    $this->variable_init_maybe(true);
    return $result;
  }

  /**
   * echo HTML code to display a variable passed in parameters
   * 
   * @param mixed $v
   * @param string $varname
   * @param boolean $echo
   * @return string
   */
  function display_valueraw_html($v,$varname,$echo = true) {
    $output                     = "";
    $varList                    = $this->variables_list();
    if (is_array($v)) {
      if (empty($v)) {
        $output .= "<em>"._("Empty array")."</em>";
      } else {
        $output .= "<ul>";
        foreach ( $v as $k=>$l) {
          $output .= "<li>";
          if (! is_numeric($k)) {
            if (is_null($varname)) {
              $output .= "$k";
            } else {
                if ( !isset($varList['DEFAULT'][null][$varname]['type'][$k]) ||  is_array( $varList['DEFAULT'][null][$varname]['type'][$k] ) ) {
                if (isset($varList['DEFAULT'][null][$varname]['type'][$k]['desc'])) {
                  $output .= $varList['DEFAULT'][null][$varname]['type'][$k]['desc'];
                } else {
                  $output .= $k;
                }
              } else {
                $output .= $varList['DEFAULT'][null][$varname]['type'][$k];
              }
            }
          } else {
            if (isset($varList['DEFAULT'][null][$varname]['type'][$k]['desc'] )) {
              $output .= $varList['DEFAULT'][null][$varname]['type'][$k]['desc'];
            }
          }
          if (is_array($l)) {
            $output .= "<ul>";
            foreach ($l as $m => $n ) {
              $output .= "<li>";
              if ( is_numeric($m)) {
                $output .= "$m";
              } else {
                $output .= $varList['DEFAULT'][null][$varname]['type'][$k][$m]['desc'];
              }
              $output .= " => $n";
              $output .= "</li>";
            }
            $output .= "</ul>";
          } else {
            $output .= " => $l";
          }
          $output .= "</li>";
        }
        $output .= "</ul>";
      } // empty $v
    } else if (empty($v) && $v != '0') {
      $output .= "<em>"._("Empty")."</em>";
    } else {
      $output .= $v;
    }
    if( $echo ){
        echo $output;
    }
    return $output;
  }

  /**
   *  Display a variable if is set
   * 
   * @param array $tab
   * @param string $strata
   * @param int $id
   * @param string $varname
   * @param boolean $echo
   * @return string
   */
  function display_value_html($tab, $strata, $id, $varname, $echo = TRUE) {
    $output = "";
    if (isset($tab[$strata][$id][$varname]['value'])) {
      $v = $tab[$strata][$id][$varname]['value'];
      $output .= $this->display_valueraw_html($v, $varname, false);
    } else {
      $output .= "<em>"._("None defined")."</em>";
    }
    if( $echo){
        echo $output;
    }
    return $output;
  }

  /**
   * return hashtable with variable_name => comment for all the vars
   * 
   * @global m_mysql $db
   * @return type
   */
  function variables_list_name() {
    global $db;

    $result = $db->query('SELECT name, comment FROM `variable` order by name');
    $t=array();
    while ($db->next_record()) {
      $tname = $db->f('name');
      // If not listed of if listed comment is shorter
      if ( ! isset( $t[$tname] ) || strlen($t[$tname]) < $db->f('comment') ) {
        $t[$db->f('name')] = $db->f('comment');
      }
    }
    return $t;
  }

  /**
   * return a multidimensionnal array used to build vars
   * 
   * @global m_mysql $db
   * @return type
   */
  function variables_list() {
    global $db;
    if ( ! $this->cache_variable_list ) {
      $result = $db->query('SELECT * FROM `variable`');

      $arr_var=array();
      while ($db->next_record()) {
        // Unserialize value if needed
        if ( ($value = @unserialize($db->f('value'))) === FALSE) {
          $value=$db->f('value');
        }
        if ( ($type = @unserialize($db->f('type'))) === FALSE) {
          $type=$db->f('type');
        }
        $arr_var[$db->f('strata')][$db->f('strata_id')][$db->f('name')] = array('id'=>$db->f('id') ,'name'=>$db->f('name'), 'value'=>$value, 'comment'=>$db->f('comment'), 'type'=>$type);
      }
      $this->cache_variable_list = $arr_var;
    }
      
    return $this->cache_variable_list;
  }

} /* Class m_variables */
