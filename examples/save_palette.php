<?php
// create and save fullsize image of map

// if you use a composer then just include('vendor/autoload.php');
include("../lib/autoloader.php");
Autoloader::register();


use NFK\MapViewer\MapViewer;

$filename = "data/pro-dm0.mapa";

// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();


// save png palette with transparent background
if ( $im = $nmap->GetPaletteImage() )
	imagepng($im, 'palette.png');


// save original bmp palette
if ( $bytes = $nmap->GetPaletteBytes() )
	file_put_contents('palette.bmp', $bytes );
