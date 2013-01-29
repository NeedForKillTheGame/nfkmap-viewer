<?php
// extract and save .mapa from .ndm file

// if you use a composer then just include('vendor/autoload.php');
include("../lib/autoloader.php");
Autoloader::register();


use NFK\MapViewer\MapViewer;

$filename = "data/demo.ndm";


// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();


// get map hash
$filename = md5( $nmap->GetMapBytes() );

// save mapa file
$nmap->SaveMap($filename);
