<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';
	//Select Database
	// Retrieve data from Query String
$nameRaw = $_GET['name'];
$name = pg_escape_string($nameRaw);
$typeRaw = $_GET['type'];
$type = pg_escape_string($typeRaw);
$timeperiodRaw = $_GET['timeperiod'];
$timeperiod = pg_escape_string($timeperiodRaw);
$pidRaw = $_GET['pid'];
$pid = pg_escape_string($pidRaw);
$geomParamRaw = $_GET['geomParam'];
$geomParam = pg_escape_string($geomParamRaw);
$magRaw = $_GET['mag'];
$magParam = pg_escape_string($magRaw);

$i=0;

if (strlen(trim($magParam)) > 0) {
$finalMag = $magParam;
}
else
{
	$finalMag = 1;
}


//have to add these parameters to the query


//first build the subquery from the parameters that were passed in
$query = "SELECT pplaces.id
from pplaces, all_names where pplaces.id = all_names.pid AND pplaces.id not like ('copy_of_%') AND pplaces.display = 1 ";


if (strlen(trim($name)) > 0) {

    $query .= " AND Upper(all_names.name_display) LIKE UPPER('%$name%')";
}

if (strlen(trim($type)) > 0) {

    $query .= " AND Upper(pplaces.featuretypes) LIKE UPPER('$type%')";
}

if (strlen(trim($timeperiod)) > 0) {

    $query .= " AND Upper(pplaces.timeperiods) LIKE UPPER('%$timeperiod%')";
}

if (strlen(trim($pid)) > 0) {

    $query .= " AND pplaces.id = '$pid'";
    $queryadd++;
}



if (strlen(trim($geomParam)) > 0) {
if ($queryadd == 0)

    $query .= " AND ST_Intersects(pplaces.the_geom, ST_PolygonFromText('$geomParam', 4326))";

}

$query .=" group by pplaces.id";



//now the main query

$query2 = "SELECT pplaces.id,
awmc_additional_data.en_name,
pplaces.title as base_name,
awmc_additional_data.gr_name,
awmc_additional_data.la_name,
pplaces.featuretypes,
pplaces.timeperiods,
ST_AsGeoJSON(pplaces.the_geom) as geom
from pplaces INNER JOIN awmc_additional_data ON pplaces.id = awmc_additional_data.pid and pplaces.id in ($query) and pplaces.display = 1
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

$qry_result2 = pg_query($query2);
if (!$qry_result2) {
            echo "Problem with query " . $query2;
            echo pg_last_error();
            exit();
        }

//this while is almost certainly redundant, but it is here to catch a duplicate pid if it somehow goes through
while($row2 =pg_fetch_assoc($qry_result2)){
$arr[] = array(
"type" => "Feature",
"geometry" => json_decode($row2[geom]),
"properties" => array(
	"basename" =>$row2[base_name],
	"custom_name" => '',
	"en_name"=>$row2[en_name],
	"gr_name"=>$row2[gr_name],
	"la_name"=>$row2[la_name],
	"featuretyp" => $row2[featuretypes],
	"pid" => $row2[id],
	"timeperiod" => $row2[timeperiods],
	"perseus_li" => $row2[perseus_li],
	"wiki_link" => $row2[wiki_link],
	"magnitude" => $finalMag
	),
"id" => $i
);
$i++;

}



$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
