<?php

header("Access-Control-Allow-Origin: http://extrafm.lt");

header("Content-Time: application/json");

$f = file_get_contents('http://www.extrafm.eu/mabs/example.php');

echo json_encode($f);