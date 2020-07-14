<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$api = new ImenaAPIv2(IMENA_API_ENDPOINT);

if (!$api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD)) {
    echo "Login unsuccessful\n";
    exit(0);
}

$clientCode = "709286529";
$authCode = "8wEjOM3xT2#jkk@M";
$serviceCode = "800190";

$result = $api->InternalTransfer($serviceCode, $authCode, $clientCode);

if ($result === false) {
    echo "Can't get clients list\n";
    echo $api->ErrorCode() . " : " . $api->ErrorMessage() . "\n";
    echo "Transaction ID: " . $api->ID() . "\n";
    var_dump($api->Error(true));
} else {
    var_dump($result);
}

$api->Logout();
