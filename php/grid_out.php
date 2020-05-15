<?php

header('Content-disposition: attachment; filename=carte_export.json');
header("Content-type: application/json");

$jsondata = $_POST['jsondata'];


//$test = rawurldecode($jsondata);
$res_array = json_decode($jsondata, true);

    echo(var_dump($jsondata))

?>