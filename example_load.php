<?php
require_once("nfkmap.class.php");

// PHP GD can allocate ~64MB of RAM for when drawing a very large map (250x250) 
ini_set('memory_limit', '-1');

$filename = "tourney4.mapa";

try
{
	$nmap = new NFKMap($filename);
	
	#$nmap->background = 2; // setup background index (from 1 to 8); if not set then use background index from the map header
	#$nmap->background = false; // background is black
	#$nmap->replacefineimages = true; // replace some item images to better quality (armor, quad, etc.)
	#$nmap->drawspecialobjects = true; // draw objects: door triggers, arrows, respawns and empty bricks
	#$nmap->drawlocations = false;	// draw location circles (view is not good)

	// load map data into memory
	$nmap->LoadMap();
	
	// draw map into memory
	$nmap->DrawMap();
	
	// then save it to an image file
	$nmap->SaveMapImage(false, basename($filename) ); // false = default file name, basename($filename) = map title in thumbnail
	
	// another save examples
	#$nmap->SaveMapImage("mapname.png"); // save full size image only
	#$nmap->SaveMapImage( "mapname.png", "thumb title" ); // save full image and thumb with text "thumb title"
	
	// or display image in a browser
	#$nmap->ShowImage();
}
catch(Exception $e)
{
	echo $e->getMessage();
}
