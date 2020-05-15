<?php
ini_set("allow_url_fopen", "on");
$pid = $_GET['pid'];
$pelagApiNeighbourhood ='http://pelagios.dme.ait.ac.at/api/network/neighbourhood.json?forPlace=http%3A%2F%2Fpleiades.stoa.org%2Fplaces%2F';

$pelagApiNeighbourhood .=$pid;

$pleagPage = file_get_contents($pelagApiNeighbourhood);

echo $pleagPage;



?>