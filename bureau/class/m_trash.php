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
 Purpose of file: Manage trash class
 ----------------------------------------------------------------------
*/

class m_trash {

  var $is_trash=false;
  var $expiration_date=null;
  var $expiration_date_db=null;

  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_trash() {
  }
  
  function set_from_db($expiration_date_db) {
    $this->expiration_date_db=$expiration_date_db;
    $this->expiration_date=strtotime($this->expiration_date_db);
    if ($this->expiration_date_db) $this->is_trash=true;
  }

  function human_display() {
    return strftime("%d/%m/%Y",$this->expiration_date);
  }

  function getfromform() {
    $fields = array (
		     "istrash"                => array ("request", "boolean", false),
		     "trash_type_expiration"  => array ("request", "string", ""),
		     "trash_exp_in_value"     => array ("request", "string", ""),
		     "trash_exp_in_unit"      => array ("request", "string", ""),
		     "trash_datepicker"       => array ("request", "string", ""),
    );
    $champs=getFields($fields);
    foreach($champs as $k=>$v) $$k = $v; 

    if (!$istrash) $trash_type_expiration="no_exp";

    switch($trash_type_expiration) {
        case "trash_at_x":
            // We can use date_parse_from_format if we have php 5.3
            //$this->expiration_date=date_parse_from_format("%d/%m/%Y",$trash_datepicker);
            $mydate=strptime($trash_datepicker, "%d/%m/%Y");
            if ($mydate){
                $this->expiration_date=new DateTime("@".mktime( 0, 0, 0, $mydate['tm_mon']+1, $mydate['tm_mday']+1, 1900+$mydate['tm_year']));
            } else {
                $this->expiration_date=new DateTime("@".(time() + (7*24*3600)));
            }
            $this->is_trash=true;
            break;
        case "trash_in_x":
            $this->is_trash=true;
            switch ($trash_exp_in_unit) {
                case "weeks":
                    $trash_unit = 7*24*3600;
                    break;
                case "days":
                    $trash_unit = 24*3600;
                    break;
                case "hours":
                    $trash_unit = 3600;
                    break;
            }
            $this->expiration_date= new DateTime("@".(time() + ($trash_exp_in_value*$trash_unit)) );
            break;
        case "no_exp":
            $this->is_trash=false;
            break;
        default:
            $this->is_trash=false;
    } // switch

    if (!is_null($this->expiration_date)) $this->expiration_date_db=$this->expiration_date->format('Y-m-d H:i:s');
  }

}

?>
