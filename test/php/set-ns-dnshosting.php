<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$code = 1050870;

$api = new ImenaAPIv2(IMENA_API_ENDPOINT);

$result = $api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
    exit(0);
} else {
    echo "Login successful\n";
}

$result = $api->SetDnshostingNS($code);

if ($result === false) {
    echo "Can't set dnshosting ns\n";
} else {
    var_dump($result);
}

$api->Logout();
