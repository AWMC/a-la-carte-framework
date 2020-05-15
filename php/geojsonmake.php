<?php
header('Content-Type: text/html; charset=utf-8');
	// Retrieve data from Query String
$enname = $_GET['enname'];
$grname = $_GET['grname'];
$laname = $_GET['laname'];
$type = $_GET['type'];
$timeperiod = $_GET['timeperiod'];
$pid = $_GET['pid'];
$geom = $_GET['geom'];

$gmap = 1;

  $i=0;

//make a geojson object

$arr[] = array(
"type" => "Feature",
"geometry" => json_decode($geom),
"properties" => array(
	"en_name"=>$enname,
	"featuretyp" => $type,
	"pid" => $pid,
	"gr_name" => $grname,
	"la_name" =>$laname,
	"timeperiod" => $timeperiod,
	"greek_map" => $gmap
	),
"id" => $i
);
$i++;

	


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>