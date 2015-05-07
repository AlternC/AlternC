<?php
/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2002 by the AlternC Development Team.
  http://alternc.org/
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
  Purpose of file: Create / Import an SSL Certificate
  ----------------------------------------------------------------------
 */
require_once("../class/config.php");

if (!isset($is_include)) {
    $fields = array(
        "filter" => array("request", "integer", null),
        "filter1" => array("request", "integer", 0),
        "filter2" => array("request", "integer", 0),
        "filter4" => array("request", "integer", 0),
        "filter8" => array("request", "integer", 0),
    );
    getFields($fields);
}

if (isset($filter1) && isset($filter2) && isset($filter4) && isset($filter8)) {
    $filter = $filter1 + $filter2 + $filter4 + $filter8;
    if ($filter == 0)
        $filter = null;
}

$r = $ssl->get_list($filter);

if (!$error)
    $error.=$err->errstr();

$astatus = array(
    $ssl::STATUS_PENDING => _("Pending Certificate"),
    $ssl::STATUS_OK => _("Valid"),
    $ssl::STATUS_EXPIRED => "<span style=\"color: red; font-weight:bold\">" . _("Expired") . "</span>",
);

$vhosts = $ssl->get_vhosts();
foreach ($vhosts as $v) {
    if ($v["certif"] == 0) {
        $info=_("Some of your hosting are using a <b>self-signed</b> certificate. <br>Your browser will not let you surf those domains properly<br>To fix this, buy a properly signed certificate")."<br>".$info;
    }
}
include_once("head.php");

if ($error) {
    echo "<p class=\"alert alert-danger\">$error</p>";
}
if ($info) {
    echo "<p class=\"alert alert-info\">$info</p>";
}
?>
<h3><?php __("Your Certificates"); ?></h3>

<p><?php __("Please find below your SSL Certificates. Some may be provided by the administrator of the server, some may be Expired or Pending (waiting for a CRT from your Certificate Provider)"); ?></p>
<form method="get" action="ssl_list.php" name="filter">
    <p><?php __("Only show the following certificates:"); ?> <br />
        <label for="filter1"><input type="checkbox" onclick="document.forms['filter'].submit()" name="filter1" id="filter1" value="1" <?php cbox($filter & $ssl::FILTER_PENDING); ?>><?php __("Pending Certificates"); ?></label>
        <label for="filter2"><input type="checkbox" onclick="document.forms['filter'].submit()" name="filter2" id="filter2" value="2" <?php cbox($filter & $ssl::FILTER_OK); ?>><?php __("Valid Certificates"); ?></label>
        <label for="filter4"><input type="checkbox" onclick="document.forms['filter'].submit()" name="filter4" id="filter4" value="4" <?php cbox($filter & $ssl::FILTER_EXPIRED); ?>><?php __("Expired Certificates"); ?></label>
        <br />
        <label for="filter8"><input type="checkbox" onclick="document.forms['filter'].submit()" name="filter8" id="filter8" value="8" <?php cbox($filter & $ssl::FILTER_SHARED); ?>><?php __("Certificates Shared by the Administrator"); ?></label> 
        &nbsp; &nbsp; 
        <input type="submit" name="go" value="<?php __("Filter"); ?>"/>
</form>
<table class="tlist">
    <tr><th></th><th><?php __("Domain Name"); ?></th><th><?php __("Status"); ?></th><th><?php __("Validity period"); ?></th><th><?php __("Used by"); ?></th></tr>
    <?php
    reset($r);
    while (list($key, $val) = each($r)) {
        ?>
        <tr class="lst">
            <td><div class="ina edit"><a href="ssl_view.php?id=<?php echo $val["id"] ?>"><?php __("Details"); ?></a></div></td>

            <td><?php echo $val["fqdn"]; ?></td>
            <td><?php
                echo $astatus[$val["status"]];
                if ($val["shared"])
                    echo " <i>" . _("(shared)") . "</i>";
                ?></td>
            <?php
            if ($val["status"] != $ssl::STATUS_PENDING) {
                ?>
                <td><?php echo format_date(_('%3$d-%2$d-%1$d %4$d:%5$d'), date("Y-m-d H:i:s", $val["validstartts"])); ?><br>
                    <?php
                    if ($val["validendts"] < (time() + 86400 * 31))
                        echo "<span style=\"color: red; font-weight:bold\">";
                    echo format_date(_('%3$d-%2$d-%1$d %4$d:%5$d'), date("Y-m-d H:i:s", $val["validendts"]));
                    if ($val["validendts"] < (time() + 86400 * 31))
                        echo "</span>";
                    ?></td> 
            <?php } else { ?>
                <td><?php __("Requested on: "); ?><br>
                    <?php echo format_date(_('%3$d-%2$d-%1$d %4$d:%5$d'), date("Y-m-d H:i:s", $val["validstartts"])); ?></td> 
            <?php } ?>
            <td><?php
                foreach ($vhosts as $v) {
                    if ($v["certif"] == $val["id"]) {
                        $v["fqdn"] = (($v["sub"]) ? ($v["sub"] . ".") : "") . $v["domaine"];
                        echo "<a href=\"dom_edit.php?domain=" . $v["domaine"] . "\">" . $v["fqdn"] . "</a><br>\n";
                    }
                }
                ?></td>
        </tr>
        <?php
    }
    // Now we enumerate self-signed certificates
    foreach ($vhosts as $v) {
        if ($v["certif"] == 0) {
            $v["fqdn"] = (($v["sub"]) ? ($v["sub"] . ".") : "") . $v["domaine"];
            echo "<tr><td><div class=\"ina add\"><a href=\"ssl_new.php?fqdn=" . $v["fqdn"] . "\">" . _("Create one") . "</a></div></td>";
            echo "<td colspan=\"3\"><span style=\"color: red; font-weight:bold\">" . _("This hosting has no valid certificate<br>a self-signed one has been created") . "</span></td>";
            echo "<td><a href=\"dom_edit.php?domain=" . $v["domaine"] . "\">" . $v["fqdn"] . "</a></td>";
            echo "</tr>";
        }
    }
    ?>

</table>
<p>&nbsp;</p>
<p>
    <span class="inb add"><a href="ssl_new.php"><?php __("Create or Import a new SSL Certificate"); ?></a></span> 
</p>


<?php include_once("foot.php"); ?>
