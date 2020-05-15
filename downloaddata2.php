<?php




header('Content-disposition: attachment; filename=carte_export.json');
header('Content-type: application/json; charset=utf-8');

$jsondata = $_POST['jsondata'];


$test = rawurldecode($jsondata);

    echo ($test);

?>
