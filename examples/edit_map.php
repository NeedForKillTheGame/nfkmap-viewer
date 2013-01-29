<?php
// edit exist map

// if you use a composer then just include('vendor/autoload.php');
include("../lib/autoloader.php");
Autoloader::register();


use NFK\MapViewer\MapViewer;
use NFK\MapViewer\MapObject\SimpleObject;

$filename = "data/pro-dm0.mapa";

// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();


// add some text to inner map name
$nmap->Header->MapName .= ' (modified from PHP)';

// add quaddamage
$nmap->Bricks[5][12] = SimpleObject::PowerupQuaddamage();


// save edited mapa file
$nmap->SaveMap($nmap->GetFileName() . "_edited"); 



// -- display result in browser

// draw map in memory
$im = $nmap->DrawMap();

// set header
header('Content-Type: image/png;');

// show image
imagepng($im);
