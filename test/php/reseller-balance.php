<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$api = new ImenaAPIv2(IMENA_API_ENDPOINT);

if (!$api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD)) {
    echo "Login unsuccessful\n";
    echo $api->ErrorCode() . " : " . $api->ErrorMessage() . "\n";
    var_dump($api->Command());
    var_dump($api->Error(true));
    exit(0);
}

echo "Login with: " . $api->GetLogin()."\n";
echo "Reseller code: " . $api->GetResellerCode() . "\n";

$result = $api->ResellerBalance($api->GetResellerCode());

if ($result === false) {
    echo "Can't get Reseller balance\n";
    echo $api->ErrorCode() . " : " . $api->ErrorMessage() . "\n";
    var_dump($api->Command());
} else {
    var_dump($result);
}

$api->Logout();
