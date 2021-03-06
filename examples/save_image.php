<?php
// create and save fullsize image of map

// if you use a composer then just include('vendor/autoload.php');
include("../lib/autoloader.php");
Autoloader::register();


use NFK\MapViewer\MapViewer;

// PHP GD can allocate ~256MB of RAM for when drawing a very large map (250x250)
ini_set('memory_limit', '-1');


$filename = "data/pro-dm0.mapa";

// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();

// draw map in memory
$im = $nmap->DrawMap();


// get file md5
$filename = md5( file_get_contents($filename) );

// save image
imagepng($im, $filename . '.png');
