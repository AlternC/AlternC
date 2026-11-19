<?php

require_once 'oidc_common.php';

global $mem, $msg, $oidc_discovery_json, $oidc_client_id, $oidc_client_secret, $oidc_redirect_uri, $db;

$authorization_code = $_GET['code'];
if (empty($authorization_code)) {
    $msg->raise("ALERT", "mem", _("No authorization code provided"));
    header("Location: /index.php");
    die();
}

$post_data = [
    'grant_type' => 'authorization_code',
    'code' => $authorization_code,
    'client_id' => $oidc_client_id,
    'client_secret' => $oidc_client_secret,
    'redirect_uri' => $oidc_redirect_uri
];

$jwks_response = file_get_contents($oidc_discovery_json['jwks_uri']);
if ($jwks_response === false) {
    $msg->raise("ALERT", "mem", _("Failed to fetch JWKS"));
    header("Location: /index.php");
    die();
}
$jwks = json_decode($jwks_response, true);

$token_endpoint = $oidc_discovery_json['token_endpoint'];
$token_response = file_get_contents(
    $token_endpoint, false, stream_context_create(
        [
        'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($post_data)
        ]
        ]
    )
);

if ($token_response === false) {
    $msg->raise("ALERT", "mem", _("Failed to fetch token"));
    header("Location: /index.php");
    die();
}
$token_data = json_decode($token_response, true);

$public_keys = \Firebase\JWT\JWK::parseKeySet($jwks);
$token = \Firebase\JWT\JWT::decode($token_data['id_token'], $public_keys);

$is_logged_in = $mem->login(
    null,
    null,
    $_REQUEST["restrictip"] ?? 0,
    false,
    $token
);

if ($is_logged_in) {
    header("Location: /main.php");
    die();
} else {
    header("Location: /index.php");
    die();
}