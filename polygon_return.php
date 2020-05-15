<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';

	//Select Database
	// Retrieve data from Query String

$geomParamRaw = $_GET['geom'];
$geom = pg_escape_string($geomParamRaw);


$queryadd = 0;
//have to add these parameters to the query




$query = "SELECT pplaces.id,
awmc_additional_data.en_name,
pplaces.title as base_name,
awmc_additional_data.gr_name,
awmc_additional_data.la_name,
pplaces.featuretypes,
pplaces.timeperiods,
ST_AsGeoJSON(pplaces.the_geom) as geom
from pplaces INNER JOIN awmc_additional_data ON pplaces.id = awmc_additional_data.pid and ST_Intersects(pplaces.the_geom, ST_PolygonFromText('$geom', 4326)) and pplaces.display = 1
group by pplaces.id,
awmc_additional_data.en_name,
pplaces.title,
awmc_additional_data.gr_name,
awmc_additional_data.la_name,
pplaces.featuretypes,
pplaces.timeperiods,
awmc_additional_data.perseus_li,
awmc_additional_data.wiki_link,
geom";




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
	"basename" =>$row[base_name],
	"custom_name" => '',
	"en_name"=>$row[en_name],
	"gr_name"=>$row[gr_name],
	"la_name"=>$row[la_name],
	"featuretyp" => $row[featuretypes],
	"pid" => $row[id],
	"timeperiod" => $row[timeperiods],
	"perseus_li" => $row[perseus_li],
	"wiki_link" => $row[wiki_link],
	"magnitude" => $finalMag
	),
"id" => $i
);
$i++;


}


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
