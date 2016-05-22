<?php
/*
$Id: adm_email.php,v 1.1 2005/09/05 10:55:48 arnodu59 Exp $
----------------------------------------------------------------------
AlternC - Web Hosting System
Copyright (C) 2005 by the AlternC Development Team.
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
Purpose of file: Show a form to edit a member
----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");

?>
<body>
<h3><?php __("About AlternC"); ?></h3>
<i><?php __("Hosting control panel");?></i>
<hr/>
<p>
<?php
__("AlternC is an automatic hosting software suite. It features a PHP-based administration interface and scripts that manage server configuration. <br/>It manages, among others, email, Web, Web statistics, and mailing list services. It is available in many languages. It is a free software distributed under GPL license.");
?>
<p>

<p>
  <ul>
    <li><?php __("Official website: ");?> <a target="_blank" href="https://alternc.com">http://alternc.com</a></li>
    <li><?php __("Developer website: ");?> <a target="_blank" href="https://github.com/AlternC">https://github.com/AlternC</a></li>
    <li><?php __("Help: ");?> <a target="_blank" href="https://aide-alternc.org">https://aide-alternc.org</a></li>
  </ul>
</li>

<hr/>
<p class="center"><a href="http://www.alternc.com" target="_blank"><img src="images/logo2.png" border="0" alt="AlternC" /></a>
<br />
<?php 
__("You are currently using AlternC ");
echo " $L_VERSION"; 
?>

<?php include_once('foot.php');?>
