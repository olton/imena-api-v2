<?php

include "./../../src/php/ImenaAPIv2.php";

$api = new ImenaAPIv2();

$result = $api->Login();

if ($result === false) {
    echo "Login unsuccessful\n";
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
