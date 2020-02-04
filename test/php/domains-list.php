<?php

include "./../../src/php/ImenaAPIv2.php";

$api = new ImenaAPIv2();

$result = $api->Login();

if ($result === false) {
    echo "Login unsuccessful\n";
} else {
    echo "Login successful\n";
}

$result = $api->Domains();

if ($result === false) {
    echo "Can't get domains list\n";
} else {
    var_dump($result);
}

$api->Logout();
