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
        "id" => array("request", "integer", ""),
    );
    getFields($fields);
}

$cert = $ssl->get_certificate($id);
$error = $err->errstr();
if ($error) {
    require_once("ssl_list.php");
    exit();
}

include_once("head.php");

if ($info) {
    echo "<p class=\"alert alert-info\">$info</p>";
}

if ($cert["status"] == $ssl::STATUS_PENDING) {
    ?>
    <h3><?php __("Pending Certificate"); ?></h3>

    <p><?php __("Your <i>Certificate Request File</i> (CSR) has been created, along with its <i>private RSA Key</i> (KEY). Please find below the CSR you must send to your SSL Certificate provider."); ?></p>

    <p><?php __("Once you'll have your <i>Certificate File</i> (CRT) and a <i>Chained Certificate File</i> (CHAIN), please paste them here to finish the enrollment."); ?></p>

    <form method="post" action="ssl_finalize.php" name="main" id="main">
<?php csrf_get(); ?>
        <input type="hidden" name="id" id="id" value="<?php echo $cert["id"]; ?>"/>
        <table border="1" cellspacing="0" cellpadding="4" class="tedit">
            <tr>
                <th><label for="fqdn"><?php __("Fully Qualified Domain Name"); ?></label></th>
                <td><?php echo $cert["fqdn"]; ?></td>
            </tr>
            <tr>
                <th><label for="validstart"><?php __("Date of the request"); ?></label></th>
                <td><?php echo format_date(__('%3$d-%2$d-%1$d %4$d:%5$d', "alternc", true), date("Y-m-d H:i:s", $cert["validstartts"])); ?></td>
            </tr>
            <tr>
                <th><label for="csr"><?php __("Certificate Request File"); ?></label></th>
                <td><textarea readonly="readonly" onclick="this.focus();
                        this.select()" class="int cert" name="csr" id="csr" style="width: 500px; height: 120px;"><?php echo $cert["sslcsr"]; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="crt"><?php __("SSL Certificate"); ?></label></th>
                <td><textarea class="int cert" name="crt" id="crt" style="width: 500px; height: 120px;"><?php echo $cert["sslcrt"]; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="chain"><?php __("Chained Certificate<br />(not mandatory)"); ?></label></th>
                <td><textarea class="int cert" name="chain" id="chain" style="width: 500px; height: 120px;"><?php echo $cert["sslchain"]; ?></textarea></td>
            </tr>
        </table>
        <p>
            <input type="submit" class="inb ok" name="submit" value="<?php __("Save"); ?>"/> &nbsp; 
            <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location = 'ssl_list.php'"/>
        </p><p>
            <input type="submit" class="inb delete" name="delete" value="<?php __("Delete"); ?>" onclick="return confirm('<?php 
            echo addslashes(__("Please confirm that you want to delete this certificate request AND ITS PRIVATE KEY!", "alternc", true)); 
            ?>');"/>

        </p>
    </form>

    <?php
} else {

    if ($cert["status"] == $ssl::STATUS_OK) {
        ?>
        <h3><?php __("Valid Certificate"); ?></h3>
        <p><?php __("Please find below your valid certificate data."); ?></p>

        <?php
    }
    if ($cert["status"] == $ssl::STATUS_EXPIRED) {
        ?>
        <h3><?php __("EXPIRED Certificate"); ?></h3>
        <p><?php __("Your certificate is EXPIRED. You should not use it for any purpose. Please find below its data."); ?></p>

        <?php
    }
    ?>
        <p>
        <span class="inb ok"><a href="ssl_list.php"><?php __("Back to my SSL Certificates"); ?></a></span> 
    </p>
    <table border="1" cellspacing="0" cellpadding="4" class="tedit">
        <tr>
            <th><?php __("Valid From:"); ?></th>
            <td><?php
    echo format_date(__('%3$d-%2$d-%1$d %4$d:%5$d', "alternc", true), date("Y-m-d H:i:s", $cert["validstartts"]));
    echo " ";
    $days = intval((time() - $cert["validstartts"]) / 86400);
    if ($days < 60) {
        printf(__("(%d days ago, "alternc", true)"), $days);
    } else {
        $month = intval($days / 30);
        printf(__("(%d month ago, "alternc", true)"), $month);
    }
    ?></td>
        </tr>
        <tr>
            <th><?php __("Valid Until:"); ?></th>
            <td><?php
                echo format_date(__('%3$d-%2$d-%1$d %4$d:%5$d', "alternc", true), date("Y-m-d H:i:s", $cert["validendts"]));
                echo " ";
                $days = intval(($cert["validendts"] - time()) / 86400);
                if ($days < 60) {
                    printf(__("(%d days from now, "alternc", true)"), $days);
                } else {
                    $month = intval($days / 30);
                    printf(__("(%d month from now, "alternc", true)"), $month);
                }
                ?></td>
        </tr>
        <tr>
            <th><?php __("FQDN:"); ?></th>
            <td><?php echo $cert["fqdn"]; ?></td>
        </tr>
        <tr>
            <th><?php __("Other Valid FQDN:"); ?></th>
            <td><?php echo nl2br($cert["altnames"]); ?></td>
        </tr>

        <tr>
            <th><label for="csr"><?php __("Certificate Request File"); ?></label></th>
            <td><textarea readonly="readonly" onclick="this.focus();
                    this.select()" class="int cert" name="csr" id="csr" style="width: 500px; height: 120px;"><?php echo $cert["sslcsr"]; ?></textarea></td>
        </tr>
        <tr>
            <th><label for="crt"><?php __("SSL Certificate"); ?></label></th>
            <td><textarea readonly="readonly" onclick="this.focus();
                    this.select()" class="int cert" name="crt" id="crt" style="width: 500px; height: 120px;"><?php echo $cert["sslcrt"]; ?></textarea></td>
        </tr>
        <tr>
            <th><label for="chain"><?php __("Chained Certificate<br />(not mandatory)"); ?></label></th>
            <td><textarea readonly="readonly" onclick="this.focus();
                    this.select()" class="int cert" name="chain" id="chain" style="width: 500px; height: 120px;"><?php echo $cert["sslchain"]; ?></textarea></td>
        </tr>
    </table>
    <?php
// The admin is allowed to share (or not share) his valid certificates
    if ($admin->enabled) {
        ?>
        <p><?php __("As an administrator you can allow any account on this server to use this certificate to host his services. <br />(This is only useful for wildcard or multi-domain certificates)."); ?></p>
        <p>
            <?php
            if ($cert["shared"]) {
                echo __("This certificate is currently <b>shared</b>", "alternc", true);
                if ($cert["uid"] == $cuid) {
                    ?>
                </p>
                <form method="post" action="ssl_share.php">
<?php csrf_get(); ?>
                    <input type="hidden" name="id" id="id" value="<?php echo $cert["id"]; ?>"/>
                    <input type="hidden" name="action" id="action" value="0" />
                    <input class="inb cancel" type="submit" name="unshare" value="<?php __("Click here to stop sharing this certificate"); ?>" />
                </form>
                <?php
            } else {
                ?>
                <p><?php __("You are not the owner of this certificate, only its owner can share/unshare this certificate."); ?></p>
                <?php
            }
        } else {
            echo __("This certificate is currently <b>NOT shared</b>", "alternc", true);
            if ($cert["uid"] == $cuid) {
                ?>
                </p>
                <form method="post" action="ssl_share.php">
<?php csrf_get(); ?>
                    <input type="hidden" name="id" id="id" value="<?php echo $cert["id"]; ?>"/>
                    <input type="hidden" name="action" id="action" value="1" />
                    <input class="inb ok" type="submit" name="unshare" value="<?php __("Click here to share this certificate"); ?>" />
                </form>
                <?php
            } else {
                ?>
                <p><?php __("You are not the owner of this certificate, only its owner can share/unshare this certificate."); ?></p>
                <?php
            }
        }
    }
     if ($cert["uid"] == $cuid) {
    ?>
                <p>
    <form method="post" action="ssl_finalize.php" name="main" id="main">
<?php csrf_get(); ?>
        <input type="hidden" name="id" id="id" value="<?php echo $cert["id"]; ?>"/>
            <input type="submit" class="inb delete" name="delete" value="<?php __("Delete"); ?>" onclick="return confirm('<?php 
            echo addslashes(__("Please confirm that you want to delete this certificate AND ITS PRIVATE KEY!", "alternc", true)); 
            ?>');"/>
       </form>
        </p>
    <?php
     }
} // pending or OK ?

?>

        
        
<?php include_once("foot.php"); ?>
