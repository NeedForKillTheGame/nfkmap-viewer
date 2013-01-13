<?php
// show map map image in a browser

require_once("../nfkmap.class.php");

// php gd can allocate ~64MB of RAM for when drawing a very large map (250x250) 
ini_set('memory_limit', '-1');


$filename = "cpm3.mapa";

// create map object
$nmap = new NFKMap($filename);

// load map data in memory
$nmap->LoadMap();

// draw map in memory
$im = $nmap->DrawMap();


// set header
header('Content-Type: image/png;');

// show image
imagepng($im);
