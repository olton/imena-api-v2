<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth2.php";

$api = new ImenaAPIv2(IMENA_API_ENDPOINT);

$result = $api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
    exit(0);
} else {
    echo "Login successful\n";
}

$result = $api->DomainsAll();

if ($result === false) {
    echo "Can't get domains list\n";
} else {
    foreach ($result as $code => $domain) {
        echo $code ." : ". $domain['domainName'] . "\n";
    }
}

$api->Logout();
