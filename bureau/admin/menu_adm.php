<?php
/*
 $Id: menu_adm.php,v 1.4 2004/11/04 06:33:23 anonymous Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Menu of the super-admins
 ----------------------------------------------------------------------
*/

/* ############################# */
/* ######### SUPER-ADMIN ########## */
/* ############################# */

if ($mem->checkRight())
{    ?>

<tr><td nowrap>
   <a href="adm_list.php"><font color="red"><?php __("Manage the members"); ?></font></a><br>
   <a href="adm_panel.php"><font color="red"><?php __("Admin Control Panel"); ?></font></a><br>
   <a href="/admin/sql/?server=2"><font color="red"><?php __("General SQL Admin"); ?></font></a><br>

</td></tr>
<?php
	    }
?>
