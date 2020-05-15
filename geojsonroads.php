<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';
	//Select Database
	// Retrieve data from Query String


$queryadd = 0;
//have to add these parameters to the query

$query = "SELECT length,
major_or_m,
known_or_a,
name,
ST_AsGeoJSON(the_geom) as geom
FROM roads";


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
	"major_or_m" =>$row[major_or_m],
	"known_or_a"=>$row[known_or_a],
	"name" => $row[name],
	),
"id" => $i
);
$i++;


}


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
