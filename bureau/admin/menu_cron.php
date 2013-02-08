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
*/

/* ############# CRON ############# */

$q = $quota->getquota("cron");
if ($q['t'] > 0 || $q['u'] > 0)  {

?>
<div class="menu-box">
<a href="cron.php">
<div class="menu-title">
  <img src="images/schedule.png" alt="<?php __("Scheduled tasks"); ?>" width=16px height=16px/>&nbsp;<?php __("Scheduled tasks"); ?> (<?php echo $q["u"].'/'.$q["t"]; ?>)
  <img src="images/menu_right.png" alt="" style="float:right;" class="menu-right"/>
</div>
</a>
</div>
<?php
} // fin du if pour les quotas 
?>
