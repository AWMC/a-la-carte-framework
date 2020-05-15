<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';
	//Select Database
	// Retrieve data from Query String
$json = $_GET['json'];

$querySub;

$json_dec = json_decode($json,true);


$queryadd = 0;


foreach ($json_dec as $pidchange)
  {

  if ($queryadd == 0)
{
$querySub .= " AND (pplaces.id = '$pidchange'";
$queryadd++;
}
else
{
$querySub .= " OR pplaces.id = '$pidchange'";
}
  }




$query = "SELECT pplaces.id,
pplaces.title as base_name,
awmc_additional_data.en_name,
awmc_additional_data.gr_name,
awmc_additional_data.la_name,
pplaces.featuretypes,
pplaces.timeperiods,
awmc_additional_data.perseus_li,
awmc_additional_data.wiki_link,
ST_AsGeoJSON(pplaces.the_geom) as geom
from pplaces INNER JOIN awmc_additional_data ON pplaces.id = awmc_additional_data.pid $querySub) and pplaces.id NOT LIKE ('copy_of_%') and pplaces.display = 1
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
	"en_name"=>$row[en_name],
	"featuretyp" => $row[featuretypes],
	"pid" => $row[id],
	"gr_name" => $row[gr_name],
	"la_name" =>$row[la_name],
	"timeperiod" => $row[timeperiods],
	"perseus_li" => $row[perseus_li],
	"wiki_link" => $row[wiki_link]
	),
"id" => $i
);
$i++;


}


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
