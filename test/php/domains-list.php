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

$result = $api->Domains();

if ($result === false) {
    echo "Can't get domains list\n";
} else {
    foreach ($result as $domain) {
        echo $domain['domainName'] . "\n";
    }
}

$api->Logout();
