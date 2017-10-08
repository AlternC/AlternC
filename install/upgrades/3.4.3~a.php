#!/usr/bin/php
<?php

// we don't check our AlternC session
if(!chdir("/usr/share/alternc/panel"))
exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");

// we do it that way, so that the HOOK will be triggered, changing the zone files:

variable_set('default_spf_value','a mx ?all', 'This variable (if set) tells the SPF/TXT DNS field that will be published as a SPF entry, telling which server(s) are allowed to send email for this one. You can still change it later in the advanced DNS entries for ONE domain, this entry is only set once for new domains (or when you change it here).');

variable_set('default_dmarc_value', 'p=none;pct=100;rua=%%ADMINMAIL%%;aspf=r;adkim=r', 'This variable (if set) tells the DMARC/TXT DNS field that will be published as a DMARC entry, telling which policy you apply to this domain name. You can still change it later in the advanced DNS entries for ONE domain, this entry is only set once for new domains (or when you change it here). You can use %%ADMINMAIL%% or %%USERMAIL%% to substitute to admin-account or alternc user-account email address.');

//  strict dmarc would be  'p=reject;pct=100;rua=%%ADMINMAIL%%;aspf=s;adkim=s'

