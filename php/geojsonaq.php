<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';
	//Select Database
	// Retrieve data from Query String


$queryadd = 0;
//have to add these parameters to the query

$query = "SELECT name,
 archaic,
 classical,
 hellenisti,
 roman,
 late_antiq,
 multi_peri,
ST_AsGeoJSON(the_geom) as geom
FROM aqueducts";


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
	"archaic" =>$row[archaic],
	"classical"=>$row[classical],
	"hellenisti" => $row[hellenisti],
		"roman" => $row[roman],
	"late_antiq" => $row[late_antiq],
	"multi_peri" => $row[multi_peri],
		"name" =>$row[name],


	),
"id" => $i
);
$i++;


}


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
