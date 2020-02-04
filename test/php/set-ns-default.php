<?php

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$code = 1050870;

$api = new ImenaAPIv2();

$result = $api->Login(IMENA_API_ENDPOINT, IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
    exit(0);
} else {
    echo "Login successful\n";
}

$result = $api->SetDefaultNS($code);

if ($result === false) {
    echo "Can't get domain info\n";
} else {
    var_dump($result);
}

$api->Logout();
