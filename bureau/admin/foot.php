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
 * FOOTER for all HTML pages
 * use with head.php
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

?>
</div> <!-- div content -->
<div style="clear:both;" ></div>
</div> <!-- div global -->
<?php 
if ( isset($debug_alternc) && $debug_alternc->status ) {
  $debug_alternc->dump();
}
?>
</body>
</html>
<?php 
if (DO_XHPROF_STATS) require_once("/usr/share/alternc/panel/admin/xhprof_footer.php");
exit(); // case of include('foot.php');
?>
