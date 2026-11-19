<?php

require_once 'oidc_common.php';

global $oidc_discovery_json;
global $oidc_client_id;
global $oidc_redirect_uri;

$oidc_url = $oidc_discovery_json['authorization_endpoint'] . "?response_type=code&client_id={$oidc_client_id}&redirect_uri={$oidc_redirect_uri}&scope=openid profile email";

header("Location: {$oidc_url}", true, 302);
exit();