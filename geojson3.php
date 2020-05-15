<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';
	//Select Database
	// Retrieve data from Query String
$name = $_GET['name'];
$type = $_GET['type'];
$timeperiod = $_GET['timeperiod'];
$pid = $_GET['pid'];

$queryadd = 0;
//have to add these parameters to the query

$query = "SELECT pplaces.pid,
max(pnames.name) as searchrename,
pplaces.en_name,
pplaces.gr_name,
pplaces.la_name,
pplaces.featuretyp,
pplaces.timeperiod,
pplaces.perseus_li,
pplaces.wiki_link,
pplaces.path,
pplaces.greek_map,
ST_AsGeoJSON(pplaces.the_geom_disp) as geom
FROM pplaces Left JOIN pnames ON pplaces.pid = pnames.pid";

if (strlen(trim($name)) > 0) {

    $query .= " WHERE Upper(pnames.name) LIKE UPPER('$name%')";
    $queryadd++;
}

if (strlen(trim($type)) > 0) {
if ($queryadd == 0)
{
$query .= " WHERE";
}
else
{
$query .= " AND ";
}
    $query .= " Upper(pplaces.featuretyp) LIKE UPPER('$type%')";
    $queryadd++;
}

if (strlen(trim($timeperiod)) > 0) {
if ($queryadd == 0)
{
$query .= " WHERE";
}
else
{
$query .= " AND ";
}
    $query .= " Upper(pplaces.timeperiod) LIKE UPPER('%$timeperiod%')";
    $queryadd++;
}

if (strlen(trim($pid)) > 0) {
if ($queryadd == 0)
{
$query .= " WHERE";
}
else
{
$query .= " AND ";
}
    $query .= " pplaces.pid = '$pid'";
    $queryadd++;
}

$query .=" and pplaces.featuretyp <> 'unlocated' group by pplaces.pid, pplaces.en_name, pplaces.gr_name, pplaces.la_name, pplaces.featuretyp, pplaces.timeperiod, pplaces.perseus_li, pplaces.wiki_link, pplaces.path, pplaces.greek_map, geom order by en_name";

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
	"searchrename" =>$row[searchrename],
	"en_name"=>$row[en_name],
	"featuretyp" => $row[featuretyp],
	"pid" => $row[pid],
	"gr_name" => $row[gr_name],
	"la_name" =>$row[la_name],
	"timeperiod" => $row[timeperiod],
	"perseus_li" => $row[perseus_li],
	"wiki_link" => $row[wiki_link],
	"path" => $row[path],
	"greek_map" => $row[greek_map]
	),
"id" => $i
);
$i++;


}


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
