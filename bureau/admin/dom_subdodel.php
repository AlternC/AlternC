<?php
/*
 $Id: dom_subdodel.php,v 1.2 2003/06/10 11:18:27 root Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$dom->lock();

if (!$dom->del_sub_domain($domain,$sub)) {
	$error=$err->errstr();
}

$dom->unlock();

include("head.php");
?>
</head>
<body>
<h3><?=sprintf(_("Deleting the subdomain %s:"),"http://".(($sub)?$sub.".":$sub).$domain); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p></body></html>";
		exit();
	} else {
        # take the current time
        $t = time();
        # that modulo (%) there computes the time of the next cron job
        # XXX: we assume the cron job is at every 5 minutes
        $error=strtr(_("The modifications will take effect at %time.  Server time is %now."), array('%now' => date('H:i:s', $t), '%time' => date('H:i:s', ($t-($t%300)+300)))); 
	    echo "<p class=\"error\">".$error."</p>";
	}
?>
<p><a href="dom_edit.php?domain=<?php echo urlencode($domain) ?>"><?php __("Click here to continue"); ?></a></p>
</body>
</html>
