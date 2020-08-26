<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$api = new ImenaAPIv2(IMENA_API_ENDPOINT);

if (!$api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD)) {
    echo "Login unsuccessful\n";
    exit(0);
}

$result = $api->Clients();

if ($result === false) {
    echo "Can't get clients list\n";
} else {
//    foreach ($result as $domain) {
//        echo $domain['domainName'] . "\n";
//    }
    var_dump($result);
}

$api->Logout();
