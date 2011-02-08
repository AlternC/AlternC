<?php
/*
 $Id: dateselect.php,v 0.0 2010/11/16 23:52:00 root Exp $
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
 Original Author of file: Alan Garcia
 Purpose of file: Show the date selection form for temporary emails
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
?>

<div id="trash_expire_picker">
    <table>
        <tbody>
            <tr>
                <td>
                    <input type="radio" name="trash_type_expiration" value="no_exp" id="no_exp" checked="checked" onclick="trash_exp_none();">
                </td><td>
                    <label for="no_exp"><?php echo __("No auto-deletion date"); ?></label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <input type="radio" name="trash_type_expiration" value="trash_in_x" id="trash_in_x" onclick="trash_exp_in_activate();"> 
                </td><td>
                    <label for="trash_in_x"><?php __('You want it to be deleted in');?></label><br/>
                    <select id="trash_exp_in_value" name="trash_exp_in_value" >
                        <?php for($i=1;$i<=30;$i++) { ?>
                            <option value="<?php echo $i;?>" <?php echo $i==7?'selected="selected"':"" ;?>><?php echo $i;?></option>
                        <?php } // for ?>
                    </select>
                    <select id="trash_exp_in_unit" name="trash_exp_in_unit">
                        <option value="hours"><?php __("Hours"); ?></option>
                        <option value="days" selected="selected"><?php __("Days"); ?></option>
                        <option value="weeks"><?php __("Weeks"); ?></option>
                    </select>
                </td>
            </tr><tr>
                <td valign=top>
                    <input type="radio" name="trash_type_expiration" value="trash_at_x" id="trash_at_x" onclick="trash_exp_at_activate();"> 
                </td><td>
                    <label for="trash_at_x"><?php __('Delete this email the following day,<br/>enter the date using DD/MM/YYYY format');?></label><br/>
                    <input id="trash_datepicker" name="trash_datepicker" type="text" size="10" value="<?php echo strftime("%d/%m/%Y",mktime() + (3600*24*7));?>" />
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $("#trash_datepicker").datepicker({ minDate: '+1d'}); // We can't give an anterior date
        $("#trash_datepicker").datepicker( "option", "dateFormat", "dd/mm/yy" ); // format of the date
        $("#trash_datepicker").datepicker( "option", "defaultDate", "+7d" ); // format of the date
        // I let Vinci make de translation wrapper for jquery and jquery_ui, he have a better view than me
        trash_exp_none();
    });
    
    function trash_exp_none() {
        $('#trash_datepicker').attr('disabled', 'disabled');
        $('#trash_exp_in_value').attr('disabled', true);
        $('#trash_exp_in_unit').attr('disabled', true);
    } 

    function trash_exp_at_activate() {
        $('#trash_datepicker').removeAttr('disabled');
        $('#trash_exp_in_value').attr('disabled', true);
        $('#trash_exp_in_unit').attr('disabled', true);
    }
    function trash_exp_in_activate() {
        $('#trash_datepicker').attr('disabled', 'disabled');
        $('#trash_exp_in_value').removeAttr('disabled');
        $('#trash_exp_in_unit').removeAttr('disabled');
    }

</script>

