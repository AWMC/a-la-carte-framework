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

$query = "SELECT pplaces2.pid,
max(pnames2.name) as searchrename,
pplaces2.en_name,
pplaces2.gr_name,
pplaces2.la_name,
pplaces2.featuretyp,
pplaces2.timeperiod,
pplaces2.perseus_li,
pplaces2.wiki_link,
pplaces2.path,
ST_AsGeoJSON(pplaces2.the_geom_disp) as geom
FROM pplaces2 INNER JOIN pnames2 ON pplaces2.pid = pnames2.pid";

if (strlen(trim($name)) > 0) {

    $query .= " WHERE Upper(pnames2.name) LIKE UPPER('$name%')";
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
    $query .= " Upper(pplaces2.featuretyp) LIKE UPPER('$type%')";
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
    $query .= " Upper(pplaces2.timeperiod) LIKE UPPER('%$timeperiod%')";
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
    $query .= " pplaces2.pid LIKE '$pid%'";
    $queryadd++;
}

$query .=" group by pplaces2.pid, pplaces2.en_name, pplaces2.gr_name, pplaces2.la_name, pplaces2.featuretyp, pplaces2.timeperiod, pplaces2.perseus_li, pplaces2.wiki_link, pplaces2.path, geom order by en_name";

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
	"path" => $row[path]
	),
"id" => $i
);
$i++;


}


$geojson = '{"type":"FeatureCollection","features":'.json_encode($arr).'}';
echo $geojson;


?>
