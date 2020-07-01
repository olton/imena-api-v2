<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth2.php";

$api = new ImenaAPIv2(IMENA_API_ENDPOINT_P);

$result = $api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD);
if ($result !== false) {
    echo "Login successful";
    $api->Logout();
}