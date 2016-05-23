<?php
/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2006 Le r�seau Koumbit Inc.
  http://koumbit.org/
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
  Purpose of file: Show the member list
  ----------------------------------------------------------------------
 */
require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    include_once('foot.php');
    exit();
}

$fields = array(
    "show" => array("request", "string", ""),
    "creator" => array("request", "integer", 0),
    "short" => array("request", "integer", -1),
    "pattern" => array("request", "string", "*"),
    "pattern_type" => array("request", "string", "login"),
);
getFields($fields);

if (empty($pattern))
    $pattern = "*";

if ($short != -1) {
    $mem->adminpref($short);
    $mem->user["admlist"] = $short;
}

$subadmin = variable_get("subadmin_restriction", 0);
// If we ask for all account but we aren't "admin" and
// subadmin var is not 1
if ($show == "all" && !$subadmin == 1 && $cuid != 2000) {
    __("This page is restricted to authorized staff");
    include('foot.php');
    exit();
}

// show all accounts by default for admin-like accounts
if (($show=="")&&($subadmin == 1 || $cuid == 2000)) $show="all";

if ($pattern && $pattern_type) {
    $accountList = $admin->get_list($show == 'all' ? 1 : 0, $creator, $pattern, $pattern_type);
} else {
    $accountList = FALSE;
}
?>

<h3><?php __("AlternC account list"); ?></h3>
<hr id="topbar"/>

<?php
// Depending on the admin's choice, let's show a short list or a long list.
if ($mem->user["admlist"] == 0) { // Normal (large) mode
    ?>
    <p><span class="ina" style="float: right;"><a href="adm_list.php?short=1"><?php __("Minimal view"); ?></a></span></p>
    <?php
} else {
    ?>
    <p><span class="ina" style="float:right;"><a href="adm_list.php?short=0"><?php __("Complete view"); ?></a></span></p>
    <?php
}
?>

<fieldset style="clear:both;">
    <legend><?php __("Filters"); ?></legend>
    <form method="post" action="adm_list.php">
  <?php csrf_get(); ?>
        <p>
            <label>
		<input type="radio" name="pattern_type" value="login" id="pattern_type_login" <?php if (!$pattern_type || $pattern_type === 'login') echo ' checked="checked" '; ?>/>
		<?php __("Search for a Login"); ?>
            </label>
            <label>
                <input type="radio" name="pattern_type" value="domaine" id="pattern_type_domain" <?php if ($pattern_type === 'domaine') echo ' checked="checked" '; ?>/>
                <?php __("Search for a Domain"); ?>
            </label>
            <input type="text" id="pattern" name="pattern" value="<?php ehe($pattern); ?>"/>
            <input type="submit" class="inb filter" value="<?php __("submit"); ?>" />
            <input type="hidden" name="show" value="<?php ehe($show); ?>" />

        </p>
    </form>
    <?php
    $list_creators = $admin->get_creator_list();

    if ($subadmin == 1 || $cuid == 2000) {
        $class=($show=="all") ? "inb" : "ina";
        echo '<p><span class="'.$class.' filter"><a href="adm_list.php?show=all">' . _('List all AlternC accounts') . '</a></span>';

        $class=($show!="all") ? "inb" : "ina";
        echo ' <span class="'.$class.' filter"><a href="adm_list.php?show=me">' . _('List only my accounts') . '</a></span></p>';

        if ($show != 'all') {
           $infos_creators = array();

           foreach ($list_creators as $key => $val) {
              $infos_creators[] = '<a href="adm_list.php?creator=' . $val['uid'] . '">' . $val['login'] . '</a>';
           }

           if (count($infos_creators)) {
              echo ' (' . _("Or only the accounts of:") . " " . implode(', ', $infos_creators) . ')';
           }
        }
    }// END ($subadmin==1 || $cuid==2000)
    ?>
</fieldset>

<?php
if (!empty($error)) {
    echo '<p class="alert alert-danger">', $error, '</p>';
}
?>

<p>
    <?php __("Here is the list of hosted AlternC accounts"); ?> (<?php printf(_("%s accounts"), $accountList? count($accountList) : 0); ?>)
</p>

<p><span class="ina add"><a href="adm_add.php"><?php __("Create a new AlternC account"); ?></a></span></p>

<?php
if (!is_array($accountList) || empty($accountList)) {
    echo '<p class="alert alert-danger">' . _("No account defined for now") . '</p>';
    include('foot.php');
}
?>

<form method="post" action="adm_dodel.php">
      <?php csrf_get(); ?>
    <?php
// Depending on the admin's choice, let's show a short list or a long list.

    if ($mem->user["admlist"] == 0) { // Normal (large) mode
        ?>
        <p>
            <?php if (count($accountList) > 5) { ?>
                <input type="submit" class="inb delete" name="submit" value="<?php __("Delete checked accounts"); ?>" />
            <?php } ?>
        </p>
        <table class="tlist" style="clear:both;">
            <tr>
                <th></th>
                <th><?php __("Account"); ?></th>
                <th><?php __("Manager"); ?></th>
                <th><?php __("Created by") ?></th>
                <th><?php __("Created on") ?></th>
                <th><?php __("Quotas") ?></th>
                <th><?php __("Last login"); ?></th>
                <th><?php __("Last ip"); ?></th>
                <th><?php __("Fails"); ?></th>
                <th><?php __('Expiry') ?></th>
            </tr>
            <?php
            reset($accountList);

            $col = 1;
            while (list($key, $val) = each($accountList)) {
                $col = 3 - $col;
                ?>
                <tr class="lst<?php echo $col; ?>">

                    <?php if ($val["su"]) { ?>
                        <td id="user_<?php echo $val["uid"]; ?>">&nbsp;</td>
                    <?php } else { ?>
                        <td><input type="checkbox" class="inc" name="accountList[]" id="user_<?php ehe($val["uid"]); ?>" value="<?php ehe($val["uid"]); ?>" /></td>
                    <?php } // val['su']  ?>
                <td <?php if ($val["su"]) echo 'style="color: red"'; ?>><label for="user_<?php ehe($val["uid"]); ?>"><b><?php ehe($val["login"]); ?></b></label></td>
                <td><a title="<?php __("Send an email"); ?>" href="mailto:<?php eue($val["mail"]); ?>"><?php ehe($val["nom"] . " " . $val["prenom"]); ?></a>&nbsp;</td>
                    <td><?php ehe($val["parentlogin"]); ?></td>
                <td><?php ehe(format_date(_('%3$d-%2$d-%1$d'), $val["created"])); ?></td>
                    <td><?php ehe($val["type"]); ?></td>
                    <td><?php ehe($val["lastlogin"]); ?></td>
                    <td><?php ehe($val["lastip"]); ?></td>
                    <td><?php ehe($val["lastfail"]); ?></td>
                <td><div class="<?php echo 'exp' . $admin->renew_get_status($val['uid']) ?>"><?php ehe($admin->renew_get_expiry($val['uid'])); ?></div></td>
                </tr>

                <tr class="lst<?php echo $col; ?>" >
                    <td/><td ><i><?php echo _("DB:") . ' ' . $val['db_server_name'] ?></i></td>
                    <td colspan="8" >
                        <div id="admlistbtn">
                            <span class="ina<?php if ($col == 2) echo "v"; ?>">
                                <a href="adm_login.php?id=<?php echo $val["uid"]; ?>"><?php __("Connect as"); ?></a>
                            </span>&nbsp;
                            &nbsp;
                            <span class="ina<?php if ($col == 2) echo "v"; ?>" >
                                <a href="adm_edit.php?uid=<?php echo $val["uid"] ?>"><?php __("Edit"); ?></a>
                            </span>&nbsp;
                            <span class="ina<?php if ($col == 2) echo "v"; ?>" >
                                <a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>"><?php __("Quotas"); ?></a>
                            </span>&nbsp;
                            <?php if (!$val["su"]) { ?>
                                <span class="ina<?php if ($col == 2) echo "v"; ?>" >
                                    <a href="adm_deactivate.php?uid=<?php echo $val["uid"] ?>"><?php __("Disable"); ?></a>
                                </span>&nbsp;
                            <?php } ?>
                        </div>
                    </td>
                </tr>
                <?php
            } // while (list($key,$val)=each($accountList)) {  
            ?>
        </table>
        <br/>
        <p>
            <input type="submit" class="inb" name="submit" value="<?php __("Delete checked accounts"); ?>" /></p>
    </form>
    <?php
} // NORMAL MODE
if ($mem->user["admlist"] == 1) { // SHORT MODE
    ?>

    [&nbsp;<?php __("C"); ?>&nbsp;] <?php __("Connect as"); ?> &nbsp; &nbsp; 
    [&nbsp;<?php __("E"); ?>&nbsp;] <?php __("Edit"); ?> &nbsp; &nbsp; 
    [&nbsp;<?php __("Q"); ?>&nbsp;] <?php __("Quotas"); ?> &nbsp; &nbsp; 

    <p>
        <?php if (count($accountList) > 50) { ?>
            <input type="submit" class="inb" name="submit" value="<?php __("Delete checked accounts"); ?>" />
        <?php } // finc count > 50 ?>
    </p>

    <table class="tlist" style="clear:both;">
        <tr>
            <th colspan="2"> </th><th><?php __("Account"); ?></th>
            <th colspan="2"> </th><th><?php __("Account"); ?></th>
            <th colspan="2"> </th><th><?php __("Account"); ?></th>
        </tr>
        <?php
        reset($accountList);

        $count_r = 0;
        foreach ($accountList as $val) {
            if (($count_r % 3) == 0) {
                echo '<tr class="lst">';
            }

            if ($val["su"]) {
                echo '<td>&nbsp;</td>';
            } else {
                echo '<td align="center"><input type="checkbox" class="inc" name="accountList[]" value="' . $val["uid"] . '" id="id_c_' . $val["uid"] . '" /></td>';
            } // if $val["su"] 
            ?>
            <td align="center">
                <a href="adm_login.php?id=<?php echo $val["uid"]; ?>" title="<?php __("Connect as"); ?>">[&nbsp;<?php __("C"); ?>&nbsp;]</a>
                <a href="adm_edit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Edit"); ?>">[&nbsp;<?php __("E"); ?>&nbsp;]</a>
                <?php if ($admin->checkcreator($val['uid'])||($show=="all")) { ?>
                    <a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Quotas"); ?>">[&nbsp;<?php __("Q"); ?>&nbsp;]</a><?php
                } // $admin->checkcreator
                $creator_name = ( ($val['creator'] == '0') ? _("himself") : $list_creators[$val['creator']]['login'])
                ?>
            </td>
            <td style="padding-right: 2px; border-right: 1px solid black; <?php if ($val["su"]) echo "color: red"; ?>"><b><label title="<?php printf(_("Creator: %s"), $creator_name); ?>" for="id_c_<?php echo $val["uid"]; ?>"><?php echo $val["login"] ?></label></b></td>
            <?php
            if (($count_r % 3) == 2) {
                echo '</tr>';
            }
            ++$count_r;
        } // foreach $accountList 
        ?>
    </table>
    <p><input type="submit" class="inb" name="submit" value="<?php __("Delete checked accounts"); ?>" /></p>
    </form>
    <?php
} // SHORT MODE
include_once("foot.php");
?>
