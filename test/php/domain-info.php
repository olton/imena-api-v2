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

$result = $api->DomainInfoShort("cctld.org.ua");

if ($result === false) {
    echo "Can't get domain info\n";
} else {
    var_dump($result);
}

$api->Logout();
