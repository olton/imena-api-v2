<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth2.php";

$code = 800190;

$api = new ImenaAPIv2(IMENA_API_ENDPOINT);

$result = $api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD);

if ($result === false) {
    echo "Login unsuccessful\n";
    exit(0);
} else {
    echo "Login successful\n";
}

/*
"type" => "A",
"subdomainName" => "@",
"data" => "5.39.10.93"
*/

$records = [
    [
        "type" => "A",
        "subdomainName" => "@",
        "data" => "185.124.168.252"
    ]
];

$result = $api->SetDnsInfo($code, $records);

echo "\nResponse:\n";
if ($result === false) {
    echo "Can't set domains dns data\n";
    var_dump($api->Error());
} else {
    var_dump($api->DnsInfo($code));
}

$api->Logout();
