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
include_once("head.php");

if (!isset($is_include)) {
    $fields = array(
        "fqdnt" => array("request", "string", ""),
        "key" => array("request", "string", ""),
        "crt" => array("request", "string", ""),
        "chain" => array("request", "string", ""),
    );
    getFields($fields);
}

$advice = $ssl->get_new_advice();
?>

<h3><?php __("New SSL Certificate"); ?></h3>

<?php
if (isset($error) && $error) {
    echo "<p class=\"alert alert-danger\">$error</p>";
}
?>
<p>
    <?php __("An SSL certificate is a file which must be obtained from a Certificate Authority, and allow you to enable HTTPS encryption on a domain name."); ?>
</p>
<p>
    <?php __("To obtain one, you need to generate a <i>Certificate Request</i> (CSR) and a <i>RSA Key</i> (KEY) here, then give the CSR to the Certificate Authority, which will give you a certificate (CRT) and also often a chained certificate (CHAIN)."); ?>
</p>
<p>
    <?php __("If you already know what it is and already have all those files (CRT/KEY/CHAIN) You can import them here too."); ?>
</p>


<div id="content">
    <div id="tabsssl">

        <ul>
            <li class="add"><a href="#tabsssl-create"><?php __("Create a CSR/KEY"); ?></a></li>
            <li class="settings"><a href="#tabsssl-import"><?php __("Import existing files"); ?></a></li>
        </ul>

        <div id="tabsssl-create">
            <h3><?php __("Create a CSR/KEY for a given domain name"); ?></h3>

            <p><?php __("Use this form to generate a <i>Certificate Request file</i> (CSR) and a <i>RSA Key file</i> (KEY) for a given domain name"); ?></p>

            <script type="text/javascript">
                function switchmanual() {
                    if ($("#fqdn").val() == -1) {
                        $("#fqdn").hide();
                        $("#fqdnt").show();
                        $("#relist").show();
                        $("#fqdn").val("");
                        $("#fqdnt").focus();
                    }
                }

                function switchlist() {
                    $("#fqdn").show();
                    $("#fqdnt").hide();
                    $("#relist").hide();
                    $("#fqdnt").val("");
                    $("#fqdn").val("");
                    $("#fqdn").focus();
                }

            </script>

            <form method="post" action="ssl_donew.php" name="main" id="main">
                <table border="1" cellspacing="0" cellpadding="4" class="tedit">
                    <tr><td colspan="2">
                            <?php __("Please choose the domain name for which you want a SSL Certificate, or enter it manually"); ?>
                        </td></tr>
                    <tr>
                        <th><label for="fqdn"><?php __("Fully Qualified Domain Name"); ?></label></th>
                        <td>
                            <select name="fqdn" id="fqdn" onchange="switchmanual()"<?php if ($fqdnt != "") echo " style=\"display: none\""; ?>>
                                <option value=""><?php __("--- Choose here ---"); ?></option>
                                <?php
                                foreach ($advice as $a) {
                                    echo "<option>" . $a . "</option>";
                                }
                                ?>
                                <option value="-1" style="font-style: italic; padding-left: 80px"> <?php __("... or click here to enter it manually"); ?></option>
                            </select>
                            <input<?php if ($fqdnt == "") echo " style=\"display: none\""; ?> type="text" class="int" name="fqdnt" id="fqdnt" value="" size="40" maxlength="64" /><input <?php if ($fqdnt == "") echo " style=\"display: none\""; ?> type="button" id="relist" name="relist" value=" list v " onclick="switchlist()" />
                        </td>
                    </tr>
                </table>
                <p>
                    <input type="submit" class="inb ok" name="submit" value="<?php __("Save"); ?>"/> &nbsp; 
                    <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location = 'ssl_list.php'"/>
                </p>
            </form>

            <div style="padding-left: 40px; margin-top: 20px; background: url(/images/warning.png) 5px 5px no-repeat">
                <p><?php __("Please note that a SSL Certificate is only valid for one fully qualified domain name. As a result, a certificate for <code>www.example.com</code> is NOT valid for <code>intranet.example.com</code> or <code>intranet.www.example.com</code> !"); ?> <br /><?php __("<i>(If you want to get a valid certificate for all the subdomains of a domain, use a wildcard notation (eg: *.example.com). Please note that a wildcard certificate is usually more expensive than normal one.)</i>"); ?></p>
            </div>

        </div> <!-- create -->


        <div id="tabsssl-import">
            <h3><?php __("Import existing Private Key, Certificate and Chain files"); ?></h3>

            <p><?php __("If you already have a RSA Private Key file, a Certificate for this key and (maybe) a Chained certificate, please paste their content here."); ?></p>
            <p><?php __("We will verify the content of those files and add them in your certificate repository"); ?></p>

            <form method="post" action="ssl_doimport.php" name="main" id="main">
                <table border="1" cellspacing="0" cellpadding="4" class="tedit">
                    <tr>
                        <th><label for="key"><?php __("RSA Private Key"); ?></label></th>
                        <td><textarea class="int" name="key" id="key" style="width: 420px; height: 120px;"><?php echo htmlentities($key); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="crt"><?php __("SSL Certificate"); ?></label></th>
                        <td><textarea class="int" name="crt" id="crt" style="width: 420px; height: 120px;"><?php echo htmlentities($crt); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="chain"><?php __("Chained Certificate<br />(not mandatory)"); ?></label></th>
                        <td><textarea class="int" name="chain" id="chain" style="width: 420px; height: 120px;"><?php echo htmlentities($chain); ?></textarea></td>
                    </tr>
                </table>
                <p>
                    <input type="submit" class="inb ok" name="submit" value="<?php __("Save"); ?>"/> &nbsp; 
                    <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location = 'ssl_list.php'"/>
                </p>
            </form>
        </div> <!-- create -->

    </div>
</div>
<script type="text/javascript">
		  $(function() {
		      $("#tabsssl").tabs(<?php if ($crt != "" and $key != "") echo "{ active: 1 }"; ?>);
		    });
</script>
<?php
include_once("foot.php");
?>
