<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';
	//Select Database
	// Retrieve data from Query String

$pidRaw = $_GET['pid'];
$pid = pg_escape_string($pidRaw);

$queryadd = 0;
//have to add these parameters to the query

$query = "SELECT all_names.name_display as resname,
all_names.name_language,
ST_AsGeoJSON(pplaces.the_geom) as geom
from all_names JOIN pplaces ON pplaces.id = all_names.pid WHERE all_names.pid = '$pid' group by geom, resname, all_names.name_language";

	//Execute query
$qry_result = pg_query($query);
if (!$qry_result) {
            echo "Problem with query " . $query;
            echo pg_last_error();
            exit();
        }

//set the id
  $i=0;

//make a geojson object
while($row =pg_fetch_assoc($qry_result)){

$arr[] = array(
"type" => "Feature",
"geometry" => json_decode($row[geom]),
"properties" => array(
	"name" =>$row[resname],
	"language"=>$row[name_language]
	),
"id" => $i
);
$i++;


}


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
