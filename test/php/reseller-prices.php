<?php

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$api = new ImenaAPIv2();

$result = $api->Login(IMENA_API_ENDPOINT, IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
    exit(0);
} else {
    echo "Login successful\n";
}

$result = $api->ResellerPrices();

if ($result === false) {
    echo "Can't get Reseller prices\n";
} else {
    var_dump($result);
}

$api->Logout();
