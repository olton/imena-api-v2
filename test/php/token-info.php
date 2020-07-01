<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$api = new ImenaAPIv2(IMENA_API_ENDPOINT_P);

$result = $api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
    exit(0);
} else {
    echo "Login successful\n";
}

$result = $api->TokenInfo();

if ($result === false) {
    echo "Can't get Token Info\n";
} else {
    var_dump($result);
}

$api->Logout();
