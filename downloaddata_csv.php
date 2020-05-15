<?php

//header('Content-disposition: attachment; filename=carte_export.json');
//header('Content-type: application/json; charset=utf-8');

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=a_la_carte_csv_export.csv");

$jsondata = $_POST['jsondata'];

$test = rawurldecode($jsondata);
$res_array = json_decode($test, true);
//print_r($res_array);
$csvText = 'Pleiades Name, Custom Name, English Name, Greek Name, Latin Name, Type, Time Period, Pleiades ID, Map Number, awmc_id, perseus_link, wikipedia_link, geometry';
$csvText .="\n";

foreach ($res_array[features] as $value)
{
$csvText .="\"";
$csvText .= $value[properties]["basename"];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['custom_name'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['en_name'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['gr_name'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['la_name'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['featuretyp'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['timeperiod'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['pid'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['map_num'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['awmcid'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['perseus_li'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvText .= $value[properties]['wiki_link'];
$csvText .="\"";
$csvText .=',';
$csvText .="\"";
$csvTemp = json_encode($value[geometry]);
$csvTempToText = str_replace ('"','""', $csvTemp);
$csvText .= $csvTempToText;
$csvText .="\"";
$csvText .="\n";

} 
echo($csvText);
?>