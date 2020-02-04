<?php

include "./../../src/php/ImenaAPIv2.php";

$code = 1050870;

$api = new ImenaAPIv2();

$result = $api->Login();

if ($result === false) {
    echo "Login unsuccessful\n";
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
