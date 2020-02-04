<?php

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$api = new ImenaAPIv2();

$result = $api->Login(IMENA_API_ENDPOINT, IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
} else {
    echo "Login successful\n";
}

$result = $api->ResellerBalance();

if ($result === false) {
    echo "Can't get Reseller balance\n";
} else {
    var_dump($result);
}

$api->Logout();
