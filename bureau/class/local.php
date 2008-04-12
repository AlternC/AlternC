<?php

/* Read global variables (AlternC configuration) */
$L_VERSION="v. @@REPLACED_DURING_BUILD@@";

/* To ease the transition, we define a lookup table for old names */
$compat = array('DEFAULT_MX' => 'MX',
                'MYSQL_USER' => 'MYSQL_LOGIN',
                'MYSQL_PASS' => 'MYSQL_PWD',
                'NS1_HOSTNAME' => 'NS1',
                'NS2_HOSTNAME' => 'NS2');


$config_file = fopen('/etc/alternc/local.sh', 'r');
while (FALSE !== ($line = fgets($config_file))) {
    if (ereg('^([A-Z0-9_]*)="([^"]*)"', $line, $regs)) {
        $GLOBALS['L_'.$regs[1]] = $regs[2];
        if (isset($compat[$regs[1]])) {
            $GLOBALS['L_'.$compat[$regs[1]]] = $regs[2];
        }
    }
}

fclose($config_file);
