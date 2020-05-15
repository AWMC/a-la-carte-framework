<?php




header('Content-disposition: attachment; filename=carte_export.kml');
header('Content-type: application/vnd.google-earth.kml+xml');

$jsondata = $_POST['jsondata'];
$test = rawurldecode($jsondata);



echo($test);

?>
