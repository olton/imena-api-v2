<?php

namespace Services\ImenaV2;

include "./../../src/php/ImenaAPIv2.php";
include "auth2.php";

$api = new ImenaAPIv2(IMENA_API_ENDPOINT);

if (!$api->Login(IMENA_API_LOGIN, IMENA_API_PASSWORD)) {
    echo "Login unsuccessful\n";
    exit(0);
}

$domain_name = "cctld.org.ua";

echo "Login successful\n";
echo "Get auth code for $domain_name\n";

$result = $api->DomainInfoShort($domain_name);

if ($result === false) {
    echo "Can't get domain info\n";
    $api->Logout();
    exit(0);
}

$service_code = $result["serviceCode"];

$result = $api->GetAuthCode($service_code);

if ($result === false) {
    echo "Can't get auth code for service code: $service_code, for domain $domain_name\n";
    echo $api->ErrorCode() . " : " . $api->ErrorMessage() . "\n";
    $api->Logout();
    exit(0);
}

echo "Auth code (CS - $service_code): $result\n";

$api->Logout();
