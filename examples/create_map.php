<?php
// create new example map

// if you use a composer then just include('vendor/autoload.php');
include("../lib/autoloader.php");
Autoloader::register();


use NFK\MapViewer\MapViewer;
use NFK\MapViewer\MapObject\SimpleObject;
use NFK\MapViewer\MapObject\SpecialObject;

$filename = "data/test.mapa";

$nmap = new MapViewer($filename);

$nmap->Header->MapName = 'Example map created from PHP';
$nmap->Header->Author = 'HarpyWar';
$nmap->Header->MapSizeX = 15; // map width (20 by default)
$nmap->Header->MapSizeY = 8; // map height (30 by default)

// next code fills map border with brick #228
for ($x = 0; $x < $nmap->Header->MapSizeX; $x++)
	for ($y = 0; $y < $nmap->Header->MapSizeY; $y++)
		if ($x == 0 || $x == $nmap->Header->MapSizeX - 1 || $y == 0 || $y == $nmap->Header->MapSizeY - 1)
			$nmap->Bricks[$x][$y] = 228;

// create respawn on the left corner
$nmap->Bricks[1][$nmap->Header->MapSizeY - 2] = SimpleObject::Respawn();

// create portal in the right corner, it will teleport player to the left corner
$obj = SpecialObject::Teleport
(
	$nmap->Header->MapSizeX - 2, // x
	$nmap->Header->MapSizeY - 2, // y
	2, // goto x
	$nmap->Header->MapSizeY - 2 // goto y
); 

$nmap->Objects[] = $obj; // add teleport to the map objects


// save mapa file
$nmap->SaveMap(); 



// -- display result in browser

// draw map in memory
$im = $nmap->DrawMap();

// set header
header('Content-Type: image/png;');

// show image
imagepng($im);
