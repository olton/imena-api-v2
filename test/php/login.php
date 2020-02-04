<?php

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$api = new ImenaAPIv2();

$result = $api->Login(IMENA_API_ENDPOINT, IMENA_API_LOGIN, IMENA_API_PASSWORD);
if ($result !== false) {
    echo "Login successful";
    $api->Logout();
}