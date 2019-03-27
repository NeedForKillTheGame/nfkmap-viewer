<?php
// display full map info

// if you use a composer then just include('vendor/autoload.php');
include("../lib/autoloader.php");
Autoloader::register();


use NFK\MapViewer\MapViewer;

$filename = "data/pro-dm0.mapa";

// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();

echo '<pre>';
print_r($nmap->GetHash());
print_r($nmap->Header);
print_r($nmap->Bricks);
print_r($nmap->Objects);
print_r($nmap->Locations);
