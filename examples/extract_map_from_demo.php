<?php
// extract and save .mapa from .ndm file

require_once("../nfkmap.class.php");

use NFK\MapViewer\MapViewer;

$filename = "demo.ndm";


// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();


// get map hash
$filename = md5( $nmap->GetMapBytes() );

// save mapa file
$nmap->SaveMap($filename);
