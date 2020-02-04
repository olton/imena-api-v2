<?php

include "./../../src/php/ImenaAPIv2.php";

$api = new ImenaAPIv2();

$result = $api->Login();
if ($result !== false) {
    echo "Login successful";
    $api->Logout();
}