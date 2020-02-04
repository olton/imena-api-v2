<?php

include "./../../src/php/ImenaAPIv2.php";
include "auth.php";

$code = 1050870;
$ns = ["ns1.mirohost.net", "ns3.mirohost.net"];

$api = new ImenaAPIv2();

$result = $api->Login(IMENA_API_ENDPOINT, IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
} else {
    echo "Login successful\n";
}

$result = $api->SetNS($code, $ns);

if ($result === false) {
    var_dump($api->Command(true));
    var_dump($api->Error());
    echo "Can't set ns\n";
} else {
    var_dump($result);
}

$api->Logout();
