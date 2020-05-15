<?php




header('Content-disposition: attachment; filename=carte_export.kml');
header('Content-type: application/vnd.google-earth.kml+xml');


//$jsondata = to_utf8($_POST['jsondata']);
//$test = urldecode($jsondata);

$jsondata = $_POST['jsondata'];
$test = rawurldecode($jsondata);



echo($test);

?>