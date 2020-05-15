<?php
header('Content-Type: text/html; charset=utf-8');
include 'PATH TO PASSWORD FILE';
	//Select Database
	// Retrieve data from Query String
$pid = $_GET['pid'];

$pid = str_replace(' ','',$pid);
$pid = str_replace("\n",'',$pid);


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
FROM pplaces INNER JOIN pnames ON pplaces.pid = pnames.pid
WHERE pplaces.pid LIKE '$pid%' group by pplaces.pid, pplaces.en_name, pplaces.gr_name, pplaces.la_name, pplaces.featuretyp, pplaces.timeperiod, pplaces.perseus_li, pplaces.wiki_link, pplaces.path, pplaces.greek_map, geom order by en_name";
//for now just one result. In the future we may expand this
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
