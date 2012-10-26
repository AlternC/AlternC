<?php

/* Read global variables (AlternC configuration) */
$L_VERSION="v. 1.1~rc1~20120826";

// To be able to have displayer version != help version
// (usefull during RC, etc...)
$L_VERSION_HELP="1.1";

/* To ease the transition, we define a lookup table for old names */
$compat = array('DEFAULT_MX'   => 'MX',
                'MYSQL_USER'   => 'MYSQL_LOGIN',
                'MYSQL_PASS'   => 'MYSQL_PWD',
                'NS1_HOSTNAME' => 'NS1',
                'NS2_HOSTNAME' => 'NS2');


$config_file = fopen('/etc/alternc/local.sh', 'r');
while (FALSE !== ($line = fgets($config_file))) {
    if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $regs)) {
        $GLOBALS['L_'.$regs[1]] = $regs[2];
        if (isset($compat[$regs[1]])) {
            $GLOBALS['L_'.$compat[$regs[1]]] = $regs[2];
        }
    }
}

fclose($config_file);

$config_file = fopen('/etc/alternc/my.cnf', 'r');
while (FALSE !== ($line = fgets($config_file))) {
    if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $regs)) {
        switch ($regs[1]) {
        case "user":
            $GLOBALS['L_MYSQL_LOGIN'] = $regs[2];
            break;
        case "password":
            $GLOBALS['L_MYSQL_PWD'] = $regs[2];
            break;
        case "host":
            $GLOBALS['L_MYSQL_HOST'] = $regs[2];
            break;
        case "database":
            $GLOBALS['L_MYSQL_DATABASE'] = $regs[2];
            break;
        }
    }
}

fclose($config_file);
?>
