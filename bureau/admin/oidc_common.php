<?php

require_once "../class/config_nochk.php";

$oidc_discovery_url = variable_get('oidc_discovery_url', '', "The discovery URL of the OpenID Connect provider");
$oidc_client_id = variable_get('oidc_client_id', '', "The client ID for the OpenID Connect provider");
$oidc_client_secret = variable_get('oidc_client_secret', '', "The client secret for the OpenID Connect provider");
$oidc_redirect_uri = variable_get('oidc_redirect_uri', '', "The redirect URI for the OpenID Connect provider");

if (!$oidc_discovery_url || !$oidc_client_id || !$oidc_client_secret || !$oidc_redirect_uri) {
    throw new Exception("Missing required configuration to start OIDC authentication");
}

$oidc_discovery_content = file_get_contents($oidc_discovery_url);

if ($oidc_discovery_content === false) {
    throw new Exception("Failed to fetch discovery document from {$oidc_discovery_url}");
}

$oidc_discovery_json = json_decode($oidc_discovery_content, true);

// Load php-jwt

require_once 'php-jwt/src/BeforeValidException.php';
require_once 'php-jwt/src/CachedKeySet.php';
require_once 'php-jwt/src/ExpiredException.php';
require_once 'php-jwt/src/JWK.php';
require_once 'php-jwt/src/JWT.php';
require_once 'php-jwt/src/JWTExceptionWithPayloadInterface.php';
require_once 'php-jwt/src/Key.php';
require_once 'php-jwt/src/SignatureInvalidException.php';