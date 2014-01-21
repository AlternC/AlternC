<?php
require_once("../class/config.php");
include_once("head.php");
/*

Cette page est immonde.

Celui qui veux la refaire est courageux.

*/

$fields = array (
        "var"       => array ("get", "string", null),
        "var_id"    => array ("post", "integer", null),
        "var_value" => array ("post", "string", null),
        "var_name"  => array ("post", "string", null),
        "strata"    => array ("post", "string", null),
        "strata_id" => array ("post", "integer", null),
        "delete"    => array ("post", "boolean", false),
);
getFields($fields);

if (empty($var)) {
  echo "<p class='error'>";__("Missing var name");echo "</p>";
  include_once("foot.php");
}

if ( $var_id && $delete ) {
  $variables->del($var_id);
} else if ( $strata && $var_name && $var_value ) {
  $variables->variable_update_or_create($var_name, $var_value, $strata, $strata_id);
} else if ( $var_id && $var_value ) {
  $variables->variable_update_or_create($var_name, $var_value, null, null, $var_id);
}

echo "<h3>";echo sprintf(_("Edition of var %s"), $var); echo "</h3>";

$members = $admin->get_list();
$panel_url = $fqdn=$dom->get_panel_url_list();

$allvars = $variables->variables_list();

$members_list=array();
foreach($admin->get_list() as $mid=>$mlogin) {
  $members_list[$mid] = $mlogin['login'];
}

$creators_list=array();
foreach($admin->get_creator_list() as $mid=>$mlogin) {
  $creators_list[$mid] = $mlogin['login'];
}

echo "<fieldset><legend>"._("Description")."</legend>";
echo "<p>".$variables->variables_list_name()[$var]."</p>";
echo "</fieldset>";

echo "<br/>";

function edit_var($var_arr) {
  echo "<div id='edit_var_div_{$var_arr['id']}'><form method=post>";
  echo "<input type='hidden' name='var_id' value='";ehe($var_arr['id']);echo "'  />";
  echo "<input type='text' class='int' name='var_value' value='";ehe($var_arr['value']); echo "' size='20' maxlength='16' />";
  echo "<br/>";
  echo "<input type='button' class='inb cancel' name='cancel' value='"._('Cancel')."' onclick=\"$('#edit_var_div_{$var_arr['id']}').toggle();\" />";
  echo "<input type='submit' class='inb delete' name='delete' value='"._("Delete")."' onclick=\"return confirm('"; ehe(_("Are you sure you want to delete it.")); echo "')\" />";
  echo "<input type='submit' class='inb ok' value='"._("Apply")."'/>";
  echo "</form></div>";
  echo "<script type='text/javascript'>$('#edit_var_div_{$var_arr['id']}').toggle();</script>";
  
}

function add_var($stratatata, $stratatata_arr=null) {
  global $var;
  echo "<div id='add_var_div_$stratatata'><form method=post>";
  echo "<input type='hidden' name='strata' value='";ehe($stratatata);echo "'  />";
  echo "<input type='hidden' name='var_name' value='";ehe($var);echo "'  />";
  if (is_array($stratatata_arr)) {
    echo "<select name='strata_id'>";
    eoption($stratatata_arr, null);
    echo "</select> ";
  }
  echo "<input type='text' class='int' name='var_value' value='' size='20' maxlength='16' />";
  echo "<br/>";
  echo "<input type='button' class='inb cancel' name='cancel' value='"._('Cancel')."' onclick=\"$('#add_var_div_$stratatata').toggle();\" />";
  echo "<input type='submit' class='inb ok' value='"._("Apply")."'/>";
  echo "</form></div>";
  echo "<script type='text/javascript'>$('#add_var_div_$stratatata').toggle();</script>";
  
}

echo "<table class='tlist'>";

foreach ( $variables->strata_order as $strata) {
  echo "<tr class='lst'>";
  echo "<td>"; __($strata); echo "</td>";
  switch($strata) {
    case 'DEFAULT':
      echo "<td>".$allvars['DEFAULT'][null][$var]['value']."</td>";
      break;
    case 'GLOBAL':
      echo "<td>";
      if ( isset($allvars['GLOBAL'][null][$var]) && is_array($allvars['GLOBAL'][null][$var])){
        echo "<a href='javascript:edit_var(".$allvars['GLOBAL'][null][$var]['id'].");'>".$allvars['GLOBAL'][null][$var]['value']."</a>";
        edit_var($allvars['GLOBAL'][null][$var]);
      } else {
        echo "<a href='javascript:add_var(\"$strata\");'>"._("Add")."</a>";
        add_var($strata);
      }
      echo "</td>";
      break;
    case 'FQDN_CREATOR':
      echo "<td>";
      foreach ($allvars['FQDN_CREATOR'] as $ttk => $ttv ) {
        if ( isset($ttv[$var]) && is_array( $ttv[$var])) {
          echo sprintf(_("Overwritted by %s"), $members[$ttk]['login'])." &rarr; ";
          echo "<a href='javascript:edit_var(".$ttv[$var]['id'].");'>".$ttv[$var]['value']."</a>";
          edit_var($ttv[$var]);
        }
        echo "<br/>";
      }
      echo "<a href='javascript:add_var(\"$strata\");'>"._("Add")."</a>";
      add_var($strata, $members_list);
      echo "</td>";
      break;
    case 'FQDN':
      echo "<td>";
      foreach ($allvars['FQDN'] as $ttk => $ttv ) {
        if ( isset($ttv[$var]) && is_array( $ttv[$var])) {
          echo sprintf(_("Overwritted by %s"), $panel_url[$ttk])." &rarr; ";
          echo "<a href='javascript:edit_var(".$ttv[$var]['id'].");'>".$ttv[$var]['value']."</a>";
          edit_var($ttv[$var]);
        }
        echo "<br/>";
      }
      echo "<a href='javascript:add_var(\"$strata\");'>"._("Add")."</a>";
      add_var($strata, $panel_url);
      echo "</td>";
      break;
    case 'CREATOR':
      echo "<td>";
      foreach ($allvars['CREATOR'] as $ttk => $ttv ) {
        if ( isset($ttv[$var]) && is_array( $ttv[$var])) {
          echo sprintf(_("Overwritted by %s"), $members[$ttk]['login'])." &rarr; ";
          echo "<a href='javascript:edit_var(".$ttv[$var]['id'].");'>".$ttv[$var]['value']."</a>";
          edit_var($ttv[$var]);
        }
        echo "<br/>";
      }
      echo "<a href='javascript:add_var(\"$strata\");'>"._("Add")."</a>";
      add_var($strata, $creators_list );
      echo "</td>";
      break;
    case 'MEMBER':
      echo "<td>";
      foreach ($allvars['MEMBER'] as $ttk => $ttv ) {
        if ( isset($ttv[$var]) && is_array( $ttv[$var])) {
          echo sprintf(_("Overwritted by %s"), $members[$ttk]['login'])." &rarr; ";
          echo "<a href='javascript:edit_var(".$ttv[$var]['id'].");'>".$ttv[$var]['value']."</a>";
          edit_var($ttv[$var]);
        }
        echo "<br/>";
      }
      echo "<a href='javascript:add_var(\"$strata\");'>"._("Add")."</a>";
      add_var($strata, $members_list);
      echo "</td>";
      break;
    case 'DOMAIN':
      //FIXME TODO
      echo "<td>Todo.</td>";
      break;
  } //switch

  echo "</tr>";
} //foreach
echo "</table>";

?>

<p><span class="ina back"><a href="adm_variables.php"><?php __("Back to the var list"); ?></a></span></p>

<script type="text/javascript">
function edit_var(id) {
  $('#edit_var_div_'+id).toggle();
}
function add_var(st) {
  $('#add_var_div_'+st).toggle();
}
</script>
<?php
include_once("foot.php");
?>
