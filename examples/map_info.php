<?php
// display full map info

require_once("../nfkmap.class.php");

use NFK\MapViewer\MapViewer;

$filename = "cpm3.mapa";

// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();

echo '<pre>';
print_r($nmap->Header);
print_r($nmap->Bricks);
print_r($nmap->Objects);
print_r($nmap->Locations);
