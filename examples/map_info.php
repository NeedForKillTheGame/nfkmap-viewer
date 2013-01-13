<?php
// display full map info

require_once("../nfkmap.class.php");


$filename = "cpm3.mapa";

// create map object
$nmap = new NFKMap($filename);

// load map data in memory
$nmap->LoadMap();

echo '<pre>';
print_r($nmap->Header);
print_r($nmap->Bricks);
print_r($nmap->Objects);
print_r($nmap->Locations);
