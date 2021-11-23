#!/usr/bin/php-alternc-wrapper
<?php

// we don't check our AlternC session
if(!chdir("/usr/share/alternc/panel"))
exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");


// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// FIRST PART : populate the table db_servers


# Use the dbusers file if exist, else use default alternc configuration
if ( is_readable("/etc/alternc/dbusers.cnf") ) {
  $mysqlconf=file_get_contents("/etc/alternc/dbusers.cnf");
} else {
  $mysqlconf=file_get_contents("/etc/alternc/my.cnf");
}
$mysqlconf=explode("\n",$mysqlconf);

# Read the configuration
foreach ($mysqlconf as $line) {
# First, read the "standard" configuration
  if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $regs)) {
    switch ($regs[1]) {
      case "user":
        $user = $regs[2];
      break;
      case "password":
        $password = $regs[2];
      break;
      case "host":
        $host = $regs[2];
      break;
    }
  }
# Then, read specific alternc configuration
  if (preg_match('/^#alternc_var ([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $regs)) {
    $$regs[1]=$regs[2];
  }
}

# Set value of human_host if unset
if (! isset($human_hostname) || empty($human_hostname)) {
  if ( checkip($host) || checkipv6($host) ) {
    $human_hostname = gethostbyaddr($host);
  } else {
    $human_hostname = $host;
  }
}

// populate it if there is not entry
$db->query("select * from db_servers;");
if ($db->num_rows()==0) {
  $db->query(" insert into db_servers (name, host, login, password, client) values ('".mysql_escape_string($human_hostname)."','".mysql_escape_string($host)."','".mysql_escape_string($user)."','".mysql_escape_string($password)."','".mysql_escape_string($L_MYSQL_CLIENT)."');");
}

// set the membres.db_server_id 
$db->query(" update membres set db_server_id = (select max(id) from db_servers) where db_server_id is null ;");

// END of db_servers part
// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++

?>
