<?php
// edit exist map

require_once("../nfkmap.class.php");
require_once("../mapobj.class.php");

use NFK\MapViewer\MapViewer;

$filename = "cpm3.mapa";

// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();


// add some text to inner map name
$nmap->Header->MapName .= ' (modified from PHP)';

// add quaddamage
$nmap->Bricks[5][12] = NFK\MapViewer\SimpleObject::PowerupQuaddamage();


// save edited mapa file
$nmap->SaveMap($nmap->GetFileName() . "_edited"); 



// -- display result in browser

// draw map in memory
$im = $nmap->DrawMap();

// set header
header('Content-Type: image/png;');

// show image
imagepng($im);
